<?php
declare(strict_types=1);
namespace RoK\OCR;

use thiagoalessio\TesseractOCR\TesseractOCR;
use carmelosantana\CliTools as CliTools;

/**
 * OCR
 */
// primary ocr loop
function ocr(array $args): array {
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
		'debug' => CliTools\get_arg('debug'),
	);

	// args to vars
	extract(array_merge($def, $args));

	// extract langs
	extract(ocr_setup_langs($lang));

	// cleanup vars
	$debug = filter_var($debug, FILTER_VALIDATE_BOOLEAN);
	$video = filter_var($video, FILTER_VALIDATE_BOOLEAN);

	// start vars
	$data = [];
	$count = 0;

	// always check if job is provided, if not config lookup will fail
	if ( !$job ){
		CliTools\cli_echo('Missing --job', ['header' => 'error', 'function' => __FUNCTION__]);
		return $data;
	}

	// log any starting notes
	CliTools\cli_echo('Starting ' . $job, ['format' => 'bold']);

	// check for profile
	if ( empty($profile) ){
		$profile = $GLOBALS['rok_config'][$job] ?? false;

		if ( !$profile ){
			CliTools\cli_echo('Missing --job $profile', ['header' => 'error', 'function' => __FUNCTION__]);
			return $data;
		}
	}

	// notify on no input path
	ocr_setup_paths($input_path, $tmp_path, $debug);

	// process each image file
	foreach (get_files_ocr($input_path, $tmp_path, $video) as $file) {
		// should only be an image
		if ( !is_mime_content_type($file, 'image') ) continue;

		// persistent
		$count++;
		CliTools\cli_echo( basename($file), ['header' => (string) $count, 'fg' => 'green'] );
		
		// start/reset data for entry
		$tmp = [];
		if ( $debug )
			$tmp = [ '_image' => basename($file) ];

		// prep image
		$file = image_prep_ocr($file, $tmp_path, $profile);

		// match image to sample retrieve templates
		if ( $compare_to_sample ){
			$image_distortion = image_compare_get_distortion($profile['sample'], $file, true);
			CliTools\cli_echo('Distortion: ' . $image_distortion);
			
			if ( $image_distortion > (float) ($distortion > 0 ? $distortion : $profile['distortion']) ){
				CliTools\cli_echo('Skip' . PHP_EOL);

				// TODO: Only remove file that doesn't meet compare threshold if from video
				// delete_file($file, $debug);

				// skip to next
				continue;
			}
		
			if ( $debug )
				$tmp = [ '_image_distortion' => $image_distortion ];
		}

		// determine image scale factor for crop points
		$scale_factor = get_image_scale_factor($file, $profile['sample']);

		// slice image for parts
		$images = [];
		foreach ( $profile['ocr_schema'] as $key => $schema ){
			// init for further use
			$tmp[$key] = null;

			// skip img process if no crop available
			if ( empty($schema['crop']) )
				continue;

			// crop image location
			$images[$key] = $tmp_path . '/' . md5($file) . '-' . $key . '.' . pathinfo($file)['extension'];

			// adjust crop scale
			$crop = crop_scale_factor($schema['crop'], $scale_factor);
			
			// crop
			image_crop($file, $images[$key], $crop);
		}

		// ocr each image part
		foreach ( $images as $key => $image ){
			// ocr
			$ocr = (new TesseractOCR($image))
				->tessdataDir($tessdata)
	
				// provided by profile
				->configFile(($profile['ocr_schema'][$key]['config']??null))
				->allowlist(($profile['ocr_schema'][$key]['allowlist']??null))

				// TODO: Check language bug with $rus
				// RoK Supported: English, Arabic, Chinese, French, German, Indonesian, Italian, Japanese, Kanuri, Korean, Malay, Portuguese, Russian, Simplified Chinese, Spanish, Thai, Traditional Chinese, Turkish, Vietnamese
				->lang($eng, $ara, $chi_sim, $chi_tra, $fra, $deu, $ind, $ita, $jpn, $kor, $msa, $por, $rus, $spa, $spa_old, $tha, $tur, $vie)

				// dictionary
				// ->userWords($user_words)
				// ->userPatterns($user_patterns)

				// settings:
				->oem((int) ($oem ?? $profile['oem']) )       
				->psm((int) ($psm ?? $profile['psm']) )

				// Reading Rainbow!
				->run();

			CliTools\cli_echo( basename($image), ['header' => 'OCR', 'fg' => 'light_gray'] );
			$tmp[$key] = text_remove_extra_lines($ocr);

			if ( isset($profile['ocr_schema'][$key]['callback']) )
				apply_callback($profile['ocr_schema'][$key]['callback'], $tmp[$key]);

			CliTools\cli_debug_echo( $tmp[$key] );
				
			// remove ocr snippet
			delete_file($image, $debug);
		}
	
		// TODO: Only remove file that matched sample if from video
		// delete_file($file, $debug);

		// add entry to others
		$data[] = $tmp;

		// space for next
		echo PHP_EOL;
	}

	// table output
	CliTools\cli_echo_table(($profile['table']??null), $data);

	// csv
	if ( $output_csv )
		if ( !output_csv( $data, $output_path, $input_path ) )
			CliTools\cli_echo('Issue creating CSV', ['header' => 'error', 'function' => __FUNCTION__]);

	return $data;
}

// populate language variables
function ocr_setup_langs($langs=['eng']): array {
	// explode if string
	if ( is_string($langs) )
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
	foreach ( $langs as $lang ) {
		if ( array_key_exists($lang, $def) )
			$output[$lang] = $lang;
	}
	
	// return default lang if none matched
	if ( empty($output) )
		$output = [ ROK_CLI_OCR_LANG => ROK_CLI_OCR_LANG ];

	// return all keys
	return array_merge($def, $output);	
}

// setup input + tmp paths
function ocr_setup_paths($input_path, &$tmp_path, $debug): void {
	if ( !is_dir($input_path) and !is_file($input_path) ){
		CliTools\cli_echo('--input_path', ['header' => 'error', 'function' => __FUNCTION__]);
		return;
	}

	// get residing folder if file, we'll add other files there
	if ( is_file($input_path) )
		$input_path = dirname($input_path);

	// temporary files
	if ( !$tmp_path and $debug )
		$tmp_path = $input_path . '/tmp';

	if ( $tmp_path and !is_dir( $tmp_path) ) {
		@mkdir($tmp_path, 0775, true);

	} elseif ( !is_dir( $tmp_path) ) {
		$tmp_path = sys_get_temp_dir();

	}

	if ( !is_dir($tmp_path) )
		CliTools\cli_echo('Missing --tmp_path ' , ['header' => 'error', 'function' => __FUNCTION__, 'exit' => true]);
}

// setup output path
function setup_output_path( &$output_path, $input_path=null ){
	if ( !$output_path and $input_path )
		$output_path = $input_path . '/output';

	if ( !is_dir( $output_path ) )
		@mkdir( $output_path, 0775, true );

	if ( !is_dir( $output_path ) )
		CliTools\cli_echo( 'Creating $output_path ' . $output_path, ['header' => 'error', 'function' => __FUNCTION__] );

	return true;
}

/**
 * Image scaling
 */
// apply scale factor to crop points
function crop_scale_factor(array $crop, float $scale): array {
	$out = [];
	foreach ( $crop as $n )
		$out[] = round( $n * $scale );

	return $out;
}

// get scale factor between 2 images
function get_image_scale_factor(string $img1, string $img2): float {
	list( $img1_width, $img1_height ) = getimagesize($img1);
	list( $img2_width, $img2_height ) = getimagesize($img2);

	return round(($img1_height/$img2_height), 5);
}

/**
 * Image prep for OCR
 */
// prepare image for OCR read
function image_prep_ocr(string $file, string $output_path, array $profile): string {
	$def = [
		'autocrop' => null,
	];
	$profile = array_merge($def, $profile);

	if ( $profile['autocrop'] ){
		$file_crop = $output_path . '/' . pathinfo($file)['basename'];
		new \AutoCrop($file, $file_crop);
		return $file_crop;
	}

	return $file;	
}

/**
 * Video
 */
// find interesting scenes
function video_find_scene_change(string $file, string $output_path): bool {
	// skip non video formats
	if ( !is_mime_content_type( $file, 'video') )
		return false;

	ffmpeg_cmd([
		'action' => 'interesting',
		'input' => $file,
		'output_path' => $output_path,
	]);

	return true;
}

/*
 *	User outputs
 */
// make CSV
function output_csv(array $data, &$output_path, string $input_path): bool {
	if ( !setup_output_path( $output_path, $input_path ) ) 
		return false;

	$output_path_csv = $output_path . '/' . time() . '.csv';

	// we need at least 1 record
	if ( !isset($data[0]) )
		return false;

	// build headers
	$headers = array_keys($data[0]);

	// build csv
	$csv = [];
	foreach($data as $row) {
		$tmp = [];
		foreach ( $headers as $key )
			$tmp[] = $row[$key] ?? '';

		$csv[] = $tmp;
	}

	// save to CSV
	$fp = fopen($output_path_csv, 'w');
	fputcsv($fp, $headers);
	foreach($csv as $row) {
		fputcsv($fp, $row);
	}

	// on success return path of finished CSV
	if ( fclose($fp) )
		return true;

	CliTools\cli_echo('Can\'t close php://output', ['header' => 'error']);

	// something failed while writing
	return false;
}

// build user words
function output_user_words(array $data, $keys=[], string $output_path): bool {
	// explode if string
	if ( is_string($keys) )
		$keys = explode(',', $keys);
		
	// no keys provided
	if ( !is_array($keys) )
		return false;

	foreach ( $data as $entry ) {
		foreach ( $keys as $key ) {
			if ( isset($entry[$key]) )
				$user_words[] = $entry[$key];
		}
	}
	
	// save user words to file
	$output_path.= '/' . time() . '-user-words.txt';
	if ( file_put_contents($output_path, implode(PHP_EOL, $user_words)) )
		return true;

	return false;
}

/*
 *	Build file lists
 */
// generic file iterator + error messages
function get_files(string $path, int $limit=-1, int $offset=0): array {
	// error checks
	if ( !$path or !is_dir($path) )
		CliTools\cli_echo('DIR does not exist ' . $path, ['header' => 'error', 'function' => __FUNCTION__]);

	// files
	$files = CliTools\sort_filesystem_iterator($path, $offset, $limit);
	CliTools\cli_echo('Files found: '. count($files));

	return $files;
}

// get files to consider for OCR
function get_files_ocr(string $input_path, string $tmp_path=null, bool $video=true): array {
	// setup files
	if ( is_file($input_path) ){
		$files = [$input_path];

	// setup path to search for files
	} elseif ( is_dir($input_path) ){
		$files = CliTools\sort_filesystem_iterator($input_path);

	// not sure what we have
	} else {
		return [];

	}

	// go through DIR
	$files_output = [];
	foreach ( $files as $file ){
		switch ( get_mime_content_type($file) ){
			// add all images
			case 'image':
				$files_output[] = $file;
			break;

			// add exported images from video
			case 'video':
				echo 1;
				if ( $video ){
					$save_to = $tmp_path . '/' . pathinfo($file)['filename'];
					@mkdir($save_to, 0775, true);
					video_find_scene_change($file, $save_to);

					// TODO: Remove tmp DIR
					// add these video files to total files
					$files_output+= get_files_ocr($save_to);
				}
			break;
		}
	}

	return $files_output;
}

/*
 *	Mimetype
 */
function is_mime_content_type(string $file, string $type='image'): bool {
	if ( !is_file($file) )
		return false;

	$mime = mime_content_type($file);
	
	if ( substr($mime, 0, strlen($type)) == $type )
		return true;

	return false;
}

function get_mime_content_type(string $file){
	foreach ( ['image', 'video'] as $type )
		if ( is_mime_content_type($file, $type) )
			return $type;

	return false;
}

/*
 *	OCR Callbacks
 */
function text_remove_non_numeric(string $string): string {
	return preg_replace('/[^0-9,.]+/', '', $string);
}

function text_remove_extra_lines(string $string): string {
	return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $string);
}

/**
 *	Helpers
 */
function apply_callback($function, &$arg): void {
	if ( !$function )
		return;

	if ( !function_exists($function) )
		$function = __NAMESPACE__ . '\\' . $function;

	if ( function_exists($function) ) {
		$arg = $function($arg);

	} else {
		CliTools\cli_echo($function, ['header' => 'error', 'function' => __FUNCTION__]);
		
	}
}

function delete_file(string $file, bool $debug=false): void {
	if ( !$debug )
		unlink($file);
}