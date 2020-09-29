<?php
// use
use thiagoalessio\TesseractOCR\TesseractOCR;

function rok_do_ocr(array $args){
	// def
	$def = array(
		// files
		'input_path' => null,
		'output_path' => null,
		'offset' => 0,
		'limit' => -1,

		// image processing
		'image_process' => true,
		'gray' => true,
		'scale' => null,

		// TesseractOCR
		// 0, 4, 11, 12
		'oem' => 3,
		'psm' => 12,
	);

	// args to vars
    $args = cli_parse_args($args, $def);
	extract($args);

	// error checks
	if ( !$input_path or !is_dir($input_path) )
		cli_echo('rok_action_process_images() - DIR does not exist ' . $input_path, array('header' => 'error'));
	if ( !$dataset )
		cli_echo('rok_action_process_images() - Missing --dataset', array('header' => 'error'));

	// vars
	$output = null;
	$count = 0;

	// files
	$files = sort_filesystem_iterator($input_path, $offset, $limit);

	// comparing images
	cli_echo('Processing OCR (' . $dataset . ')', array('format' => 'bold'));
	cli_echo('Files found: '. count($files));

	foreach ($files as $file) {
		// maybe we've already removed this file
		if ( !file_exists($file) )
			continue;

		// manually SKIP_DOTS
		if ( is_dot_file($file) ){
			$mv = $files_mv_path.'/'.basename($file);				
			rename($file, $mv);			
			continue;
		}
		
		// persistent
		$count++;

		// template
		if ( !$template = rok_get_template($dataset) )
			cli_echo('rok_do_ocr() - Missing --template', array('header' => 'error'));

		// file hash
		$hash = md5($file.json_encode($args));
		$cached_file = ROK_PATH_TMP . '/' . $hash . '.png';
		
		// image processing
		if ( !file_exists($cached_file) and $image_process ){
			// add mask
			image_add_mask_ia($template['mask'], $file, $cached_file);
			// enlarge and crop
			image_scale_crop($cached_file, $cached_file, 500);

			// grayscale?
			if ( $gray )
				imagick_convert_gray($cached_file, $cached_file);
			
			// convert image to 300 dpi before processing
			if ( imagick_get_dpi($cached_file) < 300 )
				imagick_convert_dpi($cached_file, $cached_file, 300, $scale);
		}

		// ocr
		$ocr = (new TesseractOCR($cached_file))
			// config
			// ->config('load_freq_dawg', 'false')
			
			// english
			// ->lang('eng')

			// our dictionary
			// ->userWords(ROK_PATH_USERWORDS . '/full.txt')
			// ->whitelist(range('a', 'z'), range(0, 9), '-_@')

            // TESTING:
            ->oem($oem)       
			->psm($psm)
			// ->config('tessedit_write_images', $tessedit_write_images)

			// lets go!
		    ->run();
		
		// cleanup routine
	    switch ($dataset) {
			case 'governor_profile_kills':
				$output = text_remove_extra_lines($ocr);
	    		break;
	    	
	    	default:
	    		break;
	    }

		// output
		echo $output;
	}
}

function rok_get_template(string $option){
	return rok_define_templates()[$option] ?? false;
}

function rok_define_templates(){
	return [
		'governor_profile_kills' => [
			'mask' => ROK_PATH_IMG_MASK . '/governor-profile-kills.png',
		],
	];	
}