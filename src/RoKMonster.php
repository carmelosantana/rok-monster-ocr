<?php

declare(strict_types=1);

namespace carmelosantana\RoKMonster;

use carmelosantana\RoKMonster\Media;
use carmelosantana\RoKMonster\TinyCLI;
use carmelosantana\RoKMonster\Transformer;
use thiagoalessio\TesseractOCR\TesseractOCR;

class RokMonster
{
	private $version = '0.3.0';

	public function __construct(array $args = [])
	{

		self::load_config();

		if (TinyCLI::is_cli()) {
			// TODO: Still testing?
			gc_disable();

			// get args
			TinyCLI::parse_get();

			// debug
			if (TinyCLI::get_arg('debug')) {
				ini_set('display_errors', '1');
				ini_set('display_startup_errors', '1');
				error_reporting(E_ALL);
			}
		}

		// welcome
		self::logo();

		// add CLI args
		if (empty($args))
			$args = array_merge($args, $_GET);

		// exec
		$this->ocr($args);

		// goodbye
		TinyCLI::cli_echo_footer();
		TinyCLI::cli_echo_made_with_love('NY');
	}

	public static function load_config(string $config = null)
	{
		// user and packaged config
		$GLOBALS['rok_config'] = [];

		$dir = dirname(dirname(__FILE__));

		$files = [
			'config.local.php',
			'config.php',
		];

		foreach ($files as $file) {
			$path = $dir . DIRECTORY_SEPARATOR . $file;
			file_exists($path) and require_once $path;
		}
	}

	// primary ocr loop
	public function ocr(array $args): array
	{
		// def
		$def = array(
			// what are we doing
			'job' => null,
			'profile' => [],

			// storage paths
			'input_path' => null,	// media source(s)
			'output_path' => null,	// csv
			'tmp_path' => null,	// cropped images, video screen shots

			// output
			'output_csv' => false,

			// image processing
			'compare_to_sample' => true,	// compare to profile image or ignore image differences and try to read data
			'distortion' => 0,	// threshold of difference allowed between profile sample and input image float 0-1

			// video processing
			'video' => true,	// if video is found in input path do we process or skip

			// TesseractOCR
			'lang' => ROK_CLI_LANG,	// languages to try and read
			'oem' => null,	// OCR Engine Mode
			'psm' => null,	// Page Segmentation Method
			'tessdata' => ROK_CLI_TESSDATA,	// path to tessdata models, default to system if none provided

			// echos additional data
			'debug' => TinyCLI::get_arg('debug'),
		);

		// args to vars
		extract(array_merge($def, $args));

		// extract langs
		extract(self::setup_languages($lang));

		// cleanup vars
		$debug = filter_var($debug, FILTER_VALIDATE_BOOLEAN);
		$video = filter_var($video, FILTER_VALIDATE_BOOLEAN);

		// start vars
		$data = [];
		$count = 0;

		// always check if job is provided, if not config lookup will fail
		if (!$job) {
			TinyCLI::cli_echo('Missing --job', ['header' => 'error', 'function' => __FUNCTION__]);
			return $data;
		}

		// log any starting notes
		TinyCLI::cli_echo('Starting ' . $job, ['format' => 'bold']);

		// check for profile
		if (empty($profile)) {
			$profile = $GLOBALS['rok_config'][$job] ?? false;

			if (!$profile) {
				TinyCLI::cli_echo('Missing --job $profile', ['header' => 'error', 'function' => __FUNCTION__]);
				return $data;
			}
		}

		// notify on no input path
		$this->setup_paths($input_path, $tmp_path, $debug);

		// process each image file
		foreach ($this->get_files_ocr($input_path, $tmp_path, $video) as $file) {
			// should only be an image
			if (!Media::is_mime_content_type($file, 'image')) continue;

			// persistent
			$count++;
			TinyCLI::cli_echo(basename($file), ['header' => (string) $count, 'fg' => 'green']);

			// start/reset data for entry
			$tmp = [];
			if ($debug)
				$tmp = ['_image' => basename($file)];

			// prep image
			$file = Media::image_prep_ocr($file, $tmp_path, $profile);

			// match image to sample retrieve templates
			if ($compare_to_sample) {
				$image_distortion = Media::compare_get_distortion($profile['sample'], $file, true);
				TinyCLI::cli_echo('Distortion: ' . $image_distortion);

				if (!$image_distortion) {
					TinyCLI::cli_echo('Skip, missing image' . PHP_EOL);
					continue;
				}

				if ($image_distortion > (float) ($distortion > 0 ? $distortion : $profile['distortion'])) {
					TinyCLI::cli_echo('Skip' . PHP_EOL);

					// TODO: Only remove file that doesn't meet compare threshold if from video
					// delete_file($file, $debug);

					// skip to next
					continue;
				}

				if ($debug)
					$tmp = ['_image_distortion' => $image_distortion];
			}

			// determine image scale factor for crop points
			$scale_factor = Media::get_scale_factor($file, $profile['sample']);

			// slice image for parts
			$images = [];
			foreach ($profile['ocr_schema'] as $key => $schema) {
				// init for further use
				$tmp[$key] = null;

				// skip img process if no crop available
				if (empty($schema['crop']))
					continue;

				// crop image location
				$images[$key] = $tmp_path . '/' . md5($file) . '-' . $key . '.' . pathinfo($file)['extension'];

				// adjust crop scale
				$crop = Media::apply_scale_factor($schema['crop'], $scale_factor);

				// crop
				Media::crop($file, $images[$key], $crop);
			}

			// ocr each image part
			foreach ($images as $key => $image) {
				// ocr
				$ocr = (new TesseractOCR($image))
					->tessdataDir($tessdata)

					// provided by profile
					->configFile(($profile['ocr_schema'][$key]['config'] ?? null))
					->allowlist(($profile['ocr_schema'][$key]['allowlist'] ?? null))

					// TODO: Check language bug with $rus
					// RoK Supported: English, Arabic, Chinese, French, German, Indonesian, Italian, Japanese, Kanuri, Korean, Malay, Portuguese, Russian, Simplified Chinese, Spanish, Thai, Traditional Chinese, Turkish, Vietnamese
					->lang($eng, $ara, $chi_sim, $chi_tra, $fra, $deu, $ind, $ita, $jpn, $kor, $msa, $por, $rus, $spa, $spa_old, $tha, $tur, $vie)

					// dictionary
					// ->userWords($user_words)
					// ->userPatterns($user_patterns)

					// settings:
					->oem((int) ($oem ?? $profile['oem']))
					->psm((int) ($psm ?? $profile['psm']))

					// Reading Rainbow!
					->run();

				TinyCLI::cli_echo(basename($image), ['header' => 'OCR', 'fg' => 'light_gray']);
				$tmp[$key] = Transformer::str_remove_extra_lines($ocr);

				if (isset($profile['ocr_schema'][$key]['callback']))
					self::apply_callback($profile['ocr_schema'][$key]['callback'], $tmp[$key]);

				TinyCLI::cli_debug_echo($tmp[$key]);

				// remove ocr snippet
				self::delete_file($image, $debug);
			}

			// TODO: Only remove file that matched sample if from video
			// delete_file($file, $debug);

			// add entry to others
			$data[] = $tmp;

			// space for next
			echo PHP_EOL;
		}

		// table output
		TinyCLI::cli_echo_table(($profile['table'] ?? null), $data);

		// csv
		if ($output_csv and !empty($data))
			if (!self::output_csv($data, $output_path, $input_path))
				TinyCLI::cli_echo('Issue creating CSV', ['header' => 'error', 'function' => __FUNCTION__]);

		return $data;
	}

	// get files to consider for OCR
	public function get_files_ocr(string $input_path, string $tmp_path = null, bool $video = true): array
	{
		// setup files
		if (is_file($input_path)) {
			$files = [$input_path];

			// setup path to search for files
		} elseif (is_dir($input_path)) {
			$files = TinyCLI::sort_filesystem_iterator($input_path);

			// not sure what we have
		} else {
			return [];
		}

		// go through DIR
		$files_output = [];
		foreach ($files as $file) {
			switch (Media::get_mime_content_type($file)) {
					// add all images
				case 'image':
					$files_output[] = $file;
					break;

					// add exported images from video
				case 'video':
					echo 1;
					if ($video) {
						$save_to = $tmp_path . '/' . pathinfo($file)['filename'];
						@mkdir($save_to, 0775, true);
						Media::video_find_scene_change($file, $save_to);

						// TODO: Remove tmp DIR
						// add these video files to total files
						$files_output += get_files_ocr($save_to);
					}
					break;
			}
		}

		return $files_output;
	}

	public function setup_paths($input_path, &$tmp_path, $debug): void
	{
		if (!$input_path or (!is_dir($input_path) and !is_file($input_path))) {
			TinyCLI::cli_echo('Missing or invalid --input_path', ['header' => 'error', 'function' => __FUNCTION__, 'exit' => true]);
			return;
		}

		// get residing folder if file, we'll add other files there
		if (is_file($input_path))
			$input_path = dirname($input_path);

		// temporary files
		if (!$tmp_path and $debug)
			$tmp_path = $input_path . '/tmp';

		if ($tmp_path and !is_dir($tmp_path)) {
			@mkdir($tmp_path, 0775, true);
		} elseif (!$tmp_path or !is_dir($tmp_path)) {
			$tmp_path = sys_get_temp_dir();
		}

		if (!is_dir($tmp_path))
			TinyCLI::cli_echo('Missing --tmp_path ', ['header' => 'error', 'function' => __FUNCTION__, 'exit' => true]);
	}

	private static function apply_callback($callback, &$arg): void
	{
		if (!$callback)
			return;

		if (is_string($callback) and function_exists($callback)) {
			$arg = $callback($arg);
			return;
		} elseif (is_array($callback)) {
			if (method_exists($callback[0], $callback[1])) {
				$callback = implode('::', $callback);
				$arg = $callback($arg);
				return;
			}
		}

		TinyCLI::cli_echo($callback, ['header' => 'error', 'function' => __FUNCTION__]);
	}

	// build user words
	private static function build_user_words(array $data, $keys = [], string $output_path): bool
	{
		// explode if string
		if (is_string($keys))
			$keys = explode(',', $keys);

		// no keys provided
		if (!is_array($keys))
			return false;

		foreach ($data as $entry) {
			foreach ($keys as $key) {
				if (isset($entry[$key]))
					$user_words[] = $entry[$key];
			}
		}

		// save user words to file
		$output_path .= '/' . time() . '-user-words.txt';
		if (file_put_contents($output_path, implode(PHP_EOL, $user_words)))
			return true;

		return false;
	}

	private static function delete_file(string $file, bool $debug = false): void
	{
		if (!$debug)
			unlink($file);
	}

	private function logo(bool $echo = true)
	{
		$logo = '
 _____     _____    _____             _           
| __  |___|  |  |  |     |___ ___ ___| |_ ___ ___ 
|    -| . |    -|  | | | | . |   |_ -|  _| -_|  _|
|__|__|___|__|__|  |_|_|_|___|_|_|___|_| |___|_| ';
		$desc = 'Rise of Kingdom screenshot analysis' . PHP_EOL . PHP_EOL;

		$version = 'v' . $this->version . PHP_EOL;

		$out = null;
		$out .= TinyCLI::text_style($logo, ['fg' => 'red', 'style' => 'bold']);
		$out .= TinyCLI::text_style($version, ['fg' => 'dark_gray']);
		$out .= TinyCLI::text_style($desc, ['fg' => 'yellow']);

		if (!$echo)
			return $echo;

		echo $out;
	}

	private static function output_csv(array $data, &$output_path, string $input_path): bool
	{
		if (!self::setup_output_path($output_path, $input_path))
			return false;

		$output_path_csv = $output_path . '/' . time() . '.csv';

		// we need at least 1 record
		if (!isset($data[0]))
			return false;

		// build headers
		$headers = array_keys($data[0]);

		// build csv
		$csv = [];
		foreach ($data as $row) {
			$tmp = [];
			foreach ($headers as $key)
				$tmp[] = $row[$key] ?? '';

			$csv[] = $tmp;
		}

		// save to CSV
		$fp = fopen($output_path_csv, 'w');
		fputcsv($fp, $headers);
		foreach ($csv as $row) {
			fputcsv($fp, $row);
		}

		// on success return path of finished CSV
		if (fclose($fp))
			return true;

		TinyCLI::cli_echo('Can\'t close php://output', ['header' => 'error']);

		// something failed while writing
		return false;
	}

	private static function setup_languages($langs = ['eng']): array
	{
		// explode if string
		if (is_string($langs))
			$langs = explode(',', $langs);

		// supported langs
		$def = [
			'eng' => null,
			'ara' => null,
			'chi_sim' => null,
			'chi_tra' => null,
			'fra' => null,
			'deu' => null,
			'ind' => null,
			'ita' => null,
			'jpn' => null,
			'kor' => null,
			'msa' => null,
			'por' => null,
			'rus' => null,
			'spa' => null,
			'spa_old' => null,
			'tha' => null,
			'tur' => null,
			'vie' => null,
		];

		// if lang is supported
		$output = [];
		foreach ($langs as $lang) {
			if (array_key_exists($lang, $def))
				$output[$lang] = $lang;
		}

		// return default lang if none matched
		if (empty($output))
			$output = [ROK_CLI_OCR_LANG => ROK_CLI_OCR_LANG];

		// return all keys
		return array_merge($def, $output);
	}

	private static function setup_output_path(&$output_path, $input_path = null)
	{
		if (!$output_path and $input_path)
			$output_path = $input_path . '/output';

		if (!is_dir($output_path))
			@mkdir($output_path, 0775, true);

		if (!is_dir($output_path))
			TinyCLI::cli_echo('Creating $output_path ' . $output_path, ['header' => 'error', 'function' => __FUNCTION__]);

		return true;
	}
}
