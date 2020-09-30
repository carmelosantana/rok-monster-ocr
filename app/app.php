<?php
// use
use thiagoalessio\TesseractOCR\TesseractOCR;
use jc21\CliTable;

function rok_do_ocr(array $args){
	// def
	$def = array(
		// files
		'input_path' => null,
		'output_path' => null,
		'offset' => 0,
		'limit' => -1,
		'echo' => 1,

		// image processing
		'image_process' => true,
		'gray' => false,
		'scale' => null,
		'distortion' => '0.05',

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
	$data = [];
	$output = null;
	$count = 0;

	// files
	$files = sort_filesystem_iterator($input_path, $offset, $limit);

	// comparing images
	cli_echo('Processing OCR (' . $dataset . ')', array('format' => 'bold'));
	cli_echo('Files found: '. count($files));

	// template
	if ( !$template = rok_get_template($dataset) )
		cli_echo('rok_do_ocr() - Missing --template', array('header' => 'error'));

	foreach ($files as $file) {
		// maybe we've already removed this file
		if ( !is_file($file) )
			continue;

		// manually SKIP_DOTS
		if ( is_dot_file($file) ){
			$mv = $files_mv_path.'/'.basename($file);				
			rename($file, $mv);			
			continue;
		}

		// skip non image formats
		if ( !in_array(pathinfo($file)['extension'], ['jpg', 'jpeg', 'png']) )
			continue;

		// persistent
		$count++;
		cli_echo(basename($file), array('header' => $count));

		// file hash
		$hash = md5($file.json_encode($args).filectime($file).filectime($template['mask']));
		$cached_file = ROK_PATH_TMP . '/' . $hash . '.png';

		// image processing
		if ( $image_process ){
			// add mask
			image_add_mask_ia($template['mask'], $file, $cached_file);

			// compare reference image or drop
			$dist = image_compare_get_distortion($cached_file, $template['sample']);
			cli_echo('Distortion: ' . $dist);
			if ( $dist <= $distortion ){
				cli_echo('Skip...');
				echo PHP_EOL;
				continue;
			}

			// crop
			if ( $crop )
				image_crop($cached_file, $cached_file, $crop);

			// enlarge
			if ( $scale )
				image_scale($cached_file, $cached_file, $scale);

			// grayscale?
			if ( $gray )
				imagick_convert_gray($cached_file, $cached_file);
			
			// convert image to 300 dpi before processing
			if ( $dpi and imagick_get_dpi($cached_file) < 300 )
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
		
		cli_echo('OCR: ' . basename($cached_file));
		
		// cleanup routine
	    $ocr_clean = text_remove_extra_lines($ocr);

		// start entry
		$data[$hash] = [
			'image' => $file,
			'image_ocr' => $cached_file,
			'created' => date("F d Y H:i:s.", filectime($file)),
			'ocr' => explode(PHP_EOL, $ocr_clean),
		];

		// output
		if ( $echo )
			echo $ocr_clean;

		echo PHP_EOL;	
	}

	// format for output
	$formatted = [];
	foreach ( $data as $hash => $meta ){
		$tmp = [
			'hash' => $hash,
			'created' => $meta['created'],
		];
		if ( $meta['ocr'][0] != 'Governor' )
			continue;
		
		foreach ( $template['ocr_schema'] as $name => $key ){
			$tmp[$name] = $meta['ocr'][$key] ?? null;
		}
		
		$formatted[] = $tmp;
	}

	// table output
	$table = new CliTable;
	$table->setTableColor('blue');
	$table->setHeaderColor('cyan');
	foreach ( $template['table'] as $tbl_schema )
		$table->addField($tbl_schema[0], $tbl_schema[1], $tbl_schema[2], $tbl_schema[3]);
	$table->injectData($formatted);
	$table->display();
	
	// save to CSV
	$file_output = ROK_PATH_OUTPUT . '/' . $dataset . '-' . time() . '.csv';
	$fp = fopen($file_output, 'w');
	fputcsv($fp, array_keys($formatted[0]));
	foreach($formatted as $row) {
		fputcsv($fp, $row);
	}
	fclose($fp) or die("Can't close php://output");	
}

function rok_get_template(string $option){
	return rok_define_templates()[$option] ?? false;
}

function rok_define_templates(){
	return [
		'governor_profile_kills' => [
			'ocr_schema' => [
				'name' => 1,
				'kills' => 2,
				't1' => 3,
				't2' => 4,
				't3' => 5,
				't4' => 6,
				't5' => 7
			],
			'table' => [
				['Name', 'name', false, 'white'],
				['Kills', 'kills', false, 'white'],
				['T1', 't1', false, 'white'],
				['T2', 't2', false, 'white'],
				['T3', 't3', false, 'white'],
				['T4', 't4', false, 'white'],
				['T5', 't5', false, 'white'],
			],
			'mask' => ROK_PATH_IMG_MASK . '/governor-profile-kills.png',
			'sample' => ROK_PATH_IMG_MASK . '/governor-profile-kills.png'
		]
	];	
}