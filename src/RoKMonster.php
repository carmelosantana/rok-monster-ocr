<?php

declare(strict_types=1);

namespace carmelosantana\RoKMonster;

use carmelosantana\RoKMonster\AutoCrop;
use carmelosantana\RoKMonster\Media;
use carmelosantana\RoKMonster\TinyCLI;
use carmelosantana\RoKMonster\Templates;
use carmelosantana\RoKMonster\Transformer;
use thiagoalessio\TesseractOCR\TesseractOCR;

class RoKMonster
{
	public array $data;

	private array $args;
	private array $template;
	private object $templates;

	const VERSION = '0.3.0';

	public function __construct(array $args = [])
	{
		$default = [];

		if (TinyCLI::isCLI()) {
			// set $_GET
			TinyCLI::arguments();

			// merge $_GET options with options passed from _construct
			$args = TinyCLI::parseArgs($_GET, $args);
		}

		// merge requested args with default
		$this->args = TinyCLI::parseArgs($args, $default);
	}

	public function ocr()
	{
		// start vars
		$data = [];
		$count = 0;

		// is template loaded or are we searching all?
		if ($this->env('job')) {
			if ($this->template = $this->templates->get($this->env('job'), [])) {
				TinyCLI::echo('Loaded ' . $this->template[$this->templates::TITLE], ['format' => 'bold']);
			} else {
				TinyCLI::echo('No template found.', ['format' => 'bold', 'header' => 'error', 'exit' => true]);
			}
		} else {
			TinyCLI::echo('Searching available templates.', ['format' => 'bold']);
		}

		// process each image file
		foreach ($this->getMediaFiles() as $file) {
			// should only be an image
			if (!Media::isMIMEContentType($file, 'image')) continue;

			// persistent
			$count++;
			TinyCLI::echo(basename($file), ['header' => (string) $count, 'fg' => 'green']);

			// start/reset data for entry
			$tmp = [];

			// prep image
			$file = $this->imagePrepare($file);

			// match image to sample retrieve templates
			if ($this->template('compare_to_sample')) {
				$image_distortion = Media::getCompareDistortion($this->template('sample'), $file, true);
				TinyCLI::echo('Distortion: ' . $image_distortion);

				if (!$image_distortion) {
					TinyCLI::echo('Skip, missing image' . PHP_EOL);
					continue;
				}

				if ($image_distortion > (float) $this->template('distortion') ) {
					TinyCLI::echo('Skip' . PHP_EOL);

					// skip to next
					continue;
				}
			}

			// determine image scale factor for crop points
			$scale_factor = Media::getScaleFactor($file, $this->template('sample'));

			// slice image for parts
			$images = [];
			foreach ($this->template['ocr_schema'] as $key => $schema) {
				// init for further use
				$tmp[$key] = null;

				// skip img process if no crop available
				if (empty($schema['crop']))
					continue;

				// crop image location
				$images[$key] = $this->env('tmp_path') . '/' . md5($file) . '-' . $key . '.' . pathinfo($file)['extension'];

				// adjust crop scale
				$crop = Media::applyScale($schema['crop'], $scale_factor);

				// crop
				Media::crop($file, $images[$key], $crop);
			}

			// ocr each image part
			foreach ($images as $key => $image) {
				// extract langs
				extract(self::setupLanguages($this->templateSchema($key, 'lang', 'eng')));

				// ocr
				$ocr = (new TesseractOCR($image))
					->tessdataDir($this->env('tessdata', null))

					// provided by profile
					->configFile($this->templateSchema($key, 'config_file', null))
					->allowlist($this->templateSchema($key, 'allowlist', null))

					// TODO: Check language bug with $rus
					// RoK Supported: English, Arabic, Chinese, French, German, Indonesian, Italian, Japanese, Kanuri, Korean, Malay, Portuguese, Russian, Simplified Chinese, Spanish, Thai, Traditional Chinese, Turkish, Vietnamese
					->lang($eng, $ara, $chi_sim, $chi_tra, $fra, $deu, $ind, $ita, $jpn, $kor, $msa, $por, $rus, $spa, $spa_old, $tha, $tur, $vie)

					// dictionary
					// ->userWords($user_words)
					// ->userPatterns($user_patterns)

					// settings:
					->oem((int) $this->templateSchema($key, 'oem'))
					->psm((int) $this->templateSchema($key, 'psm'))

					// Reading Rainbow!
					->run();

				TinyCLI::echo(basename($image), ['header' => 'OCR', 'fg' => 'light_gray']);
				$tmp[$key] = Transformer::strRemoveExtraLineBreaks($ocr);

				if (isset($this->template('ocr_schema')[$key]['callback']))
					self::apply_callback($this->template('ocr_schema')[$key]['callback'], $tmp[$key]);

				TinyCLI::cli_debug_echo($tmp[$key]);

				// remove ocr snippet
				$this->deleteFile($image);
			}

			// add entry to others
			$data[] = $tmp;

			// space for next
			echo PHP_EOL;
		}

		$this->data = $data;
	}

	public function ocrDisplay()
	{
		if (!empty($this->data))
			TinyCLI::cli_echo_table($this->template('table'), $this->data);
	}

	public function ocrExport()
	{
		if (!$this->env('export'))
			return;

		$export = explode(',', strtolower($this->env('export')));

		// csv
		if (in_array('csv', $export) and !empty($this->data))
			$this->exportCSV();
	}

	public function run(): void
	{
		$this->logo();

		$this->initEnv();

		$this->initDebug();

		$this->initMediaLibrary();

		$this->initTemplates();

		$this->debugEnv();

		$this->ocr();

		$this->ocrDisplay();

		$this->ocrExport();

		$this->shutdown();
	}

	public function set(string $key, string $value)
	{
		$this->args[$key] = $value;
	}

	private function env($name, $alt = false)
	{
		return $this->args[$name] ?? $_ENV[strtoupper($name)] ?? $alt;
	}

	private function envEnabled($name, $alt = false)
	{
		return TinyCLI::is_enabled($this->env($name, $alt));
	}

	private function debugEnv()
	{
		if (!$this->isDebug())
			return;

		$headers = ['Debug', 'Value'];

		$data = [];

		foreach (array_merge($this->args, $_ENV) as $key => $value) {
			if (is_array($value)) {
				$value = count($value) . 'x';
			} elseif (is_bool($value)) {
				$value = $value ? 'true' : 'false';
			}
			$data[] = [$key, $value];
		}

		$data[] = ['Loaded templates', (empty($this->templates) ? 'None' : count($this->templates->get()))];

		$table = new \cli\Table();
		$table->setHeaders($headers);
		$table->setRows($data);
		$table->display();
	}

	private function deleteFile(string $file): void
	{
		if (!$this->isDebug())
			unlink($file);
	}

	private function getMediaFiles($path = null): array
	{
		// setup files
		if (is_file($this->env('input_path', ''))) {
			$files = [$this->env('input_path')];

			// setup path to search for files
		} elseif (is_dir($this->env('input_path', ''))) {
			$files = new \FilesystemIterator($this->env('input_path'), \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS);

			// not sure what we have
		} else {
			return [];
		}

		// go through DIR
		$files_output = [];
		foreach ($files as $fi) {
			if (!is_string($fi))
				$file = $fi->getPathname();

			switch (Media::getMIMEContentType($file)) {
				case 'image':
					// add all images
					$files_output[] = $file;
					break;

				case 'video':
					// add exported images from video
					if ($this->env('video')) {
						$save_to = $this->env('tmp') . '/' . pathinfo($file)['filename'];
						@mkdir($save_to, 0775, true);
						Media::video_find_scene_change($file, $save_to);

						// TODO: Remove tmp DIR
						// add these video files to total files
						$files_output += $this->getMediaFiles($save_to);
					}
					break;
			}
		}

		return $files_output;
	}

	private function imagePrepare(string $file): string
	{
		if ($this->template('autocrop')) {
			$file_crop = $this->env('output_path') . DIRECTORY_SEPARATOR . pathinfo($file, PATHINFO_BASENAME);
			new AutoCrop($file, $file_crop);
			return $file_crop;
		}

		return $file;
	}

	private function initDebug()
	{
		if (!$this->isDebug())
			return;

		ini_set('display_errors', '1');
		ini_set('display_startup_errors', '1');
		error_reporting(E_ALL);
	}

	private function initEnv()
	{
		$env = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';

		// setup env if known exists
		if (!is_file($env)) {
			$sample = $env . '.example';
			copy($sample, $env);
		}

		$dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__));
		$dotenv->safeLoad();
	}

	private function initTemplates()
	{
		if (!$this->env('templates')) {
			$path = '';
		} else {
			$path = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $this->env('templates', '');
		}

		$this->args['template_path'] = $path;

		$this->templates = new Templates($path);
	}

	private function isDebug()
	{
		if ($this->envEnabled('debug'))
			return true;

		return false;
	}

	private function initMediaLibrary(): void
	{
		$paths = [
			'input_path',
			'output_path',
			'tmp_path'
		];

		foreach ($paths as $path) {
			if (file_exists($this->env($path, '')))
				continue;

			if (!is_dir($this->env($path)) and $this->env($path))
				@mkdir($this->env($path, ''), 0775, true);

			if (!is_dir($this->env($path, '')))
				TinyCLI::echo('Missing or invalid --' . $path, ['header' => 'error', 'function' => __FUNCTION__, 'exit' => true]);
		}
	}

	private function logo(bool $echo = true)
	{
		$logo = ' _____     _____    _____             _           ' . PHP_EOL . '| __  |___|  |  |  |     |___ ___ ___| |_ ___ ___ ' . PHP_EOL . '|    -| . |    -|  | | | | . |   |_ -|  _| -_|  _|' . PHP_EOL . '|__|__|___|__|__|  |_|_|_|___|_|_|___|_| |___|_| ';

		$version = 'v' . self::VERSION . PHP_EOL;

		$desc = 'Rise of Kingdom screenshot analysis' . PHP_EOL;

		$out = PHP_EOL;
		$out .= TinyCLI::text_style($logo, ['fg' => 'red', 'style' => 'bold']);
		$out .= TinyCLI::text_style($version, ['fg' => 'dark_gray']);
		$out .= TinyCLI::text_style($desc, ['fg' => 'yellow']);

		if (!$echo)
			return $echo;

		TinyCLI::echo($out);
	}

	private function shutdown()
	{
		TinyCLI::cli_echo_footer();
		TinyCLI::cli_echo_made_with_love('NY');
	}

	private function template(string $key, $alt = false)
	{
		return $this->arg[$key] ?? $this->template[$key] ?? $alt;
	}

	private function templateSchema($key, string $option, $alt = false)
	{
		// per crop args, template args, user args, .env defaults
		return $this->template['ocr_schema'][$key][$option] ?? $this->template[$option] ?? $this->args[$option] ?? $this->env($option, $alt);
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

		TinyCLI::echo($callback, ['header' => 'error', 'function' => __FUNCTION__]);
	}

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

	private function exportCSV(): bool
	{
		$output_path_csv = $this->env('output_path') . DIRECTORY_SEPARATOR . time() . '.csv';

		// we need at least 1 record
		if (!isset($this->data[0]))
			return false;

		// build headers
		$headers = array_keys($this->data[0]);

		// build csv
		$csv = [];
		foreach ($this->data as $row) {
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

		TinyCLI::echo('Can\'t close php://output', ['header' => 'error']);

		// something failed while writing
		return false;
	}

	private static function setupLanguages($langs = ['eng']): array
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

		// return all keys
		return array_merge($def, $output);
	}
}
