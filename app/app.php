<?php
use thiagoalessio\TesseractOCR\TesseractOCR;
use Treinetic\ImageArtist\lib\Image;

/*
 * OCR
 */
// primary ocr loop
function rok_do_ocr(array $args){
	// def
	$def = array(
		// what are we doing
		'job' => null,

		// crop schema and data points for OCR
		'profile' => [],

		// profile overrides 
		'oem' => 0,
		'psm' => 7,
		'distortion' => 0,
				
		// callbacks
		'callback_files_ocr' => null,

		// files
		'input_path' => ROK_PATH_INPUT,
		'output_path' => ROK_PATH_OUTPUT,
		'tmp_path' => ROK_PATH_TMP,
		'offset' => 0,
		'limit' => -1,

		// image processing
		'compare_to_sample' => true,

		// TesseractOCR
		'lang' => 'eng',
		'user_words' => null,
		'user_patterns' => null,

		// after OCR
		'build_user_words' => false,

		// internal vars
		'debug' => false,
	);

	// args to vars
	extract(array_merge($def, $args));

	// extract langs
	extract(rok_ocr_lang_args($lang));

	// always check if job is provided, if not config lookup will fail
	if ( !$job )
		cli_echo('Missing --job', ['header' => 'error', 'function' => __FUNCTION__]);

	// check for profile
	if ( !$profile ){
		$profile = rok_get_config($job);

		if ( !$profile )
			cli_echo('Missing --job template', ['header' => 'error', 'function' => __FUNCTION__]);
	}

	// build profile with CLI args
	$profile_def = [
		'oem' => $oem,
		'psm' => $psm,
		'distortion' => $distortion,		
	];	
	$profile = array_merge($profile_def, $profile);

	// log any starting notes
	cli_echo('Starting ' . $job, ['format' => 'bold']);

	// setup file for OCR
	if ( is_file($input_path) ){
		$args['files_ocr'] = [$input_path];

	// setup path to search for files
	} elseif ( is_dir($input_path) ){
		$args['files_ocr'] = rok_get_files_ocr($input_path, $tmp_path);

	// issue with file or path
	} else {
		cli_echo('Missing $input_path', ['header' => 'error', 'function' => __FUNCTION__]);

	}

	// if files_ocr contains no files
	if ( !isset($args['files_ocr']) or !$args['files_ocr'] or empty($args['files_ocr']))
		cli_echo('Missing $args[\'files_ocr\']', ['header' => 'error', 'function' => __FUNCTION__]);

	// start vars
	$data = $output = [];
	$count = 0;
	
	// process each image file
	foreach ($args['files_ocr'] as $file) {
		rok_callback($callback_files_ocr, $file);

		// check if file, mime type match and SKIP_DOTS
		if ( !is_mime_content_type($file, 'image') or is_dot_file($file) ) continue;	// skip non image formats

		// persistent
		$count++;
		cli_echo(cli_txt_style('['.basename($file).']', ['fg' => 'light_green']) . ' #' . $count);
		
		// start/reset data for entry
		$tmp = [];
		if ( $debug )
			$tmp = [ '_image' => basename($file) ];

		// prep image
		$file = rok_ocr_prep_image($file, $tmp_path, $profile);

		// match image to sample retrieve templates
		if ( $compare_to_sample ){
			$image_distortion = image_compare_get_distortion($file, $profile['sample']);
			cli_echo('Distortion: ' . $image_distortion);
			
			// does not match a template
			if ( $image_distortion > (float) $profile['distortion'] ){
				cli_echo('Skipping ...');
				continue;
			}
		
			if ( $debug )
				$tmp = [ '_image_distortion' => $image_distortion ];
		}

		// slice image for parts
		$images = [];
		foreach ( $profile['ocr_schema'] as $key => $schema ){
			// init for further use
			$tmp[$key] = null;

			// skip img process if no crop available
			if ( empty($schema['crop']) )
				continue;

			// cut image for this key
			$images[$key] = ROK_PATH_TMP . '/' . md5($file) . '-' . $key . '.' . pathinfo($file)['extension'];
			image_crop($file, $images[$key], $schema['crop']);
		}

		// ocr each image part
		foreach ( $images as $key => $image ){
			// ocr
			$ocr = (new TesseractOCR($image))
				// provided by profile
				->configFile(($profile['ocr_schema'][$key]['config']??null))
				->whitelist(($profile['ocr_schema'][$key]['whitelist']??null))

				// TODO: Check language bug with $rus
				// RoK Supported: English, Arabic, Chinese, French, German, Indonesian, Italian, Japanese, Kanuri, Korean, Malay, Portuguese, Russian, Simplified Chinese, Spanish, Thai, Traditional Chinese, Turkish, Vietnamese
				->lang($eng, $ara, $chi_sim, $chi_tra, $fra, $deu, $ind, $ita, $jpn, $kor, $msa, $por, $rus, $spa, $spa_old, $tha, $tur, $vie)

				// dictionary
				->userWords($user_words)
				->userPatterns($user_patterns)

				// settings:
				->oem((int) $oem)       
				->psm((int) $psm)

				// Reading Rainbow!
				->run();
			
			cli_echo('OCR: ' . $key . ' ' . cli_txt_style(basename($image), ['fg' => 'light_gray']));
			$tmp[$key] = text_remove_extra_lines($ocr);

			if ( isset($profile['ocr_schema'][$key]['callback']) and function_exists($profile['ocr_schema'][$key]['callback']) )
				$tmp[$key] = $profile['ocr_schema'][$key]['callback']($tmp[$key]);

			if ( $debug )
				echo $tmp[$key] . PHP_EOL;
		}

		// add entry to others
		$data[] = $tmp;

		// space for next
		echo PHP_EOL;
	}

	// add user words
	if ( $build_user_words )
		$args['output']['user_words_file'] = rok_ocr_build_user_words($user_words, ['name'], $build_user_words);

	// table output
	rok_cli_table(($profile['table']??null), $data);

	// csv
	if ( isset($profile['csv_headers']) ){
		$csv_file = ROK_PATH_OUTPUT . '/' . $job . '-' . time() . '.csv';
		if ( !$args['output']['csv'] = rok_build_csv($data, $profile['csv_headers'], $csv_file) )
			cli_echo("Can't close php://output", ['header' => 'error']);
	}

	return ['data' => $data, 'job' => $args];
}

// prep file for scan
/*
	1. AutoCrop
	2. Compare image
		1. reduce size
	    2. add slight blur
    3. TRUE: Return new image to files_ocr
    4. FALSE: Delete modified compare file 
    4. FALSE: unset($file), continue() $files_oc
*/
function rok_ocr_prep_image($file, $output_path, $profile){
	$def = [
		'autocrop' => null,
	];
	$profile = array_merge($def, $profile);

	if ( $profile['autocrop'] ){
		$file_crop = rok_ocr_get_img_mod_path($file, $output_path);
		new AutoCrop($file, $file_crop);
		return $file_crop;
	}

	return $file;	
}

function rok_ocr_get_img_mod_path($file, $path, $mod=null){
	return $path . '/' . pathinfo($file)['filename'] . ($mod ? '-' . $mod : null ) . '.' . pathinfo($file)['extension'];
}

// language var builder
function rok_ocr_lang_args($langs=['eng']){
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
		$output = [ ROK_LANG => ROK_LANG ];

	// return all keys
	return array_merge($def, $output);	
}

// find interesting scenes
function rok_video_find_scene_change($files_input, $output_path){
	if ( is_string($files_input) )
		$files_input = [$files_input];

	$count = 0;
	$files = [];
	foreach ($files_input as $file) {
		if ( !is_file($file) ) continue;	// maybe we've already removed this file
		if ( is_dot_file($file) ) continue;	// manually SKIP_DOTS

		// skip non video formats
		if ( !is_mime_content_type( $file, 'video') ) continue;

		$count++;

		if ( !is_dir($output_path) and !mkdir($output_path, 0775, true) )
			cli_echo('!mkdir ' . $output_path, ['header' => 'error']);

		cli_echo(cli_txt_style('['.basename($file).']', ['fg' => 'green']) . ' ' . $count);

		rok_do_ffmpeg_cmd([
			'action' => 'interesting',
			'input' => $file,
			'output_path' => $output_path,
		]);
	}
}

/*
 *	User outputs
 */
// make CSV
function rok_build_csv($data, $headers, $output){
	if ( !$headers or empty($headers) )
		$headers = array_keys($data[0]);
	
	// build csv
	$csv = [];
	foreach($data as $row) {
		$tmp = [];
		foreach ( array_values($headers) as $key )
			$tmp[] = $row[$key] ?? '';

		$csv[] = $tmp;
	}

	// make file name if non exist
	if ( !$output )
		$output = ROK_PATH_OUTPUT . '/' . time() . '.csv';

	// save to CSV
	$fp = fopen($output, 'w');
	fputcsv($fp, array_keys($headers));
	foreach($csv as $row) {
		fputcsv($fp, $row);
	}

	// on success return path of finished CSV
	if ( fclose($fp) )
		return $output;
		
	// something failed while writing
	return false;
}

// build user words
function rok_ocr_build_user_words($data, $keys, $output){
	foreach ( $data as $entry ) {
		foreach ( $keys as $key ) {
			if ( isset($entry[$key]) )
				$user_words[] = $entry[$key];
		}
	}
	
	// save user words to file
	$output = ROK_PATH_OUTPUT . '/' . $job . '-user-words.txt';
	if ( file_put_contents($output, implode(PHP_EOL, $user_words)) )
		return $output;

	return false;
}

/*
 *	Build file lists
 */
function rok_get_files_ocr($path, $tmp_path=null, $offset=0, $limit=-1){
	// error checks
	if ( !$path or !is_dir($path) )
		cli_echo('rok_get_dir() - DIR does not exist ' . $path, array('header' => 'error'));

	// files
	$files = sort_filesystem_iterator($path, $offset, $limit);
	$files_output = [];
	cli_echo('Files found: '. count($files));

	foreach ( $files as $file ){
		switch ( get_mime_content_type($file) ){
			// add all images
			case 'image':
				$files_output[] = $file;
			break;

			// add exported images from video
			case 'video':
				if ( !$tmp_path )
					$tmp_path = ROK_PATH_TMP;

				$save_to = $tmp_path . '/' . pathinfo($file)['filename']; 
				rok_video_find_scene_change($file, $save_to);

				// add these video files to total files
				$files_output+= rok_get_files_ocr($save_to);
			break;
		}
	}

	return $files_output;
}