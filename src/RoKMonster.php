<?php

declare(strict_types=1);

namespace carmelosantana\RoKMonster;

use carmelosantana\RoKMonster\AutoCrop;
use carmelosantana\RoKMonster\Media;
use carmelosantana\RoKMonster\Templates;
use carmelosantana\RoKMonster\Transformer;
use carmelosantana\TinyCLI\TinyCLI;
use thiagoalessio\TesseractOCR\TesseractOCR;

class RoKMonster
{
	public array $data = [];

	public array $done = [];

	public array $args = [];

	public array $template = [];

	public object $templates;

	const VERSION = '0.3.4';

	/**
	 * Starts instance with provided arguments
	 *
	 * @param array $args User defined arguments for setting environment and job options
	 *
	 * @return object
	 */
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

		// setup environment, args etc
		$this->initEnv();

		// are we debugging?
		$this->initDebug();

		// setup media library, input/output DIRs
		$this->initMediaLibrary();

		// setup templates, user and system
		$this->initTemplates();

		// start debugging output
		$this->debugEnv();
	}

	public function ocr()
	{
		// start vars
		$data = [];
		$count = 0;

		// is -0template loaded or are we searching all?
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
		foreach ($this->getMediaFiles($this->env('input_path', '')) as $file) {
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
			if ($this->env('compare_to_sample', 1)) {
				switch ($this->template('compare_to_sample')) {
					case 'distortion':
						$image_distortion = Media::getCompareDistortion($this->template('sample'), $file, true);
						TinyCLI::echo('Distortion: ' . $image_distortion);
						break;

					default:
						$fingerprint = $this->media->fingerprint($file);
						TinyCLI::echo($fingerprint, ['header' => 'Fingerprint']);

						$image_distortion = $this->media->fingerprintDistance($fingerprint, $this->template('fingerprint'));
						TinyCLI::echo((string) $image_distortion, ['header' => 'Distance']);
						break;
				}

				if ($image_distortion > (float) $this->template('threshold')) {
					TinyCLI::echo('Skip' . PHP_EOL);

					// skip to next
					continue;
				}
			}

			// determine image scale factor for crop points
			$scale_factor = Media::getScaleFactor($file, [$this->template('width'), $this->template('height')]);

			// slice image for parts
			$images = [];
			foreach ($this->template('ocr_schema') as $key => $schema) {
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

				$tmp[$key] = Transformer::strRemoveExtraLineBreaks($ocr);

				TinyCLI::echo($tmp[$key]);

				if (isset($this->template('ocr_schema')[$key]['callback']))
					self::applyCallback($this->template('ocr_schema')[$key]['callback'], $tmp[$key]);

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
		if (empty($this->data))
			return;

		//TODO: remove temp patch for $schema
		$schema = [];
		$c = 0;
		foreach ($this->data[0] as $k => $v) {
			$schema[] = [
				$k,
				$k,
				false,

				// assume the first value is an ID and color green
				$c == 0 ? 'green' : 'white',
			];

			$c++;
		}

		TinyCLI::table($this->data, $schema) . PHP_EOL;
	}

	public function ocrExport()
	{
		if (!$this->env('export') or empty($this->data))
			return;

		$export = explode(',', strtolower($this->env('export')));

		// csv
		if (in_array('csv', $export)) {
			if ($export = $this->exportCSV()) {
				TinyCLI::echo($export, ['header' => 'CSV', 'fg' => 'green']);
			}
		}
	}

	public function run(): void
	{
		$this->logo();

		$this->ocr();

		$this->ocrDisplay();

		$this->ocrExport();
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

	private function exportCSV()
	{
		
		$output_path_csv = $this->env('output_path', getcwd()) . DIRECTORY_SEPARATOR . time() . '.csv';

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
			return $output_path_csv;

		TinyCLI::echo('Can\'t close php://output', ['header' => 'error']);

		// something failed while writing
		return false;
	}

	private function getMediaFiles($path = null): array
	{
		// setup files
		if (is_file($path)) {
			$files = [$path];

			// setup path to search for files
		} elseif (is_dir($path)) {
			$files = new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS);

			// not sure what we have
		} else {
			return [];
		}

		// go through DIR
		$files_output = [];
		foreach ($files as $file) {
			if (!is_string($file))
				$file = $file->getPathname();

			switch (Media::getMIMEContentType($file)) {
				case 'image':
					// add all images
					$files_output[] = $file;
					break;
			}
		}

		return $files_output;
	}

	private function imagePrepare(string $file): string
	{
		if ($this->template('autocrop')) {
			$file_crop = $this->env('input_path') . DIRECTORY_SEPARATOR . pathinfo($file, PATHINFO_BASENAME);
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
		// start template system
		$this->templates = new Templates();

		// system templates
		$path = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'templates';
		$this->templates->load($path);

		// load rok-monster-schema
		$path = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'carmelosantana' . DIRECTORY_SEPARATOR . 'rok-monster-schema' . DIRECTORY_SEPARATOR . 'ocr';
		$this->templates->load($path);

		// load custom template
		if ($this->env('template_path'))
			$this->templates->load($this->env('template_path'));
	}

	private function initMediaLibrary(): void
	{
		// start media tools
		$this->media = new Media();

		$paths = [
			'input_path' => '',
			'tmp_path' => sys_get_temp_dir()
		];

		foreach ($paths as $path => $def) {
			if (file_exists($this->env($path, '')))
				continue;

			if (!is_dir($this->env($path, '')) and $this->env($path)) {
				@mkdir($this->env($path), 0775, true);

				if (is_dir($this->env($path)))
					continue;
			}

			if (file_exists($def)) {
				$this->set($path, $def);
				continue;
			}

			if (!is_dir($this->env($path)))
				TinyCLI::echo('Missing or invalid --' . $path, ['header' => 'error', 'function' => __FUNCTION__, 'exit' => true]);
		}
	}

	private function isDebug()
	{
		if ($this->envEnabled('debug'))
			return true;

		return false;
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

	private function template(string $key, $alt = false)
	{
		return $this->arg[$key] ?? $this->template[$key] ?? $alt;
	}

	private function templateSchema($key, string $option, $alt = false)
	{
		// per crop args, template args, user args, .env defaults
		return $this->args[$option] ?? $this->template['ocr_schema'][$key][$option] ?? $this->template[$option] ?? $this->env($option, $alt);
	}

	private static function applyCallback($callback, &$arg): void
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
