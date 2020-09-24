<?php
// disregard screens we don't want
function image_process_for_ocr(array $args){
	// def
	$def = array(
		'tmp_path' => null,
		'trash_path' => null,
		'offset' => 0,
		'limit' => -1,
		'dataset' => null,
	);

	// args to vars
    $args = cli_parse_args($args, $def);
    extract($args);

	// error checks
	if ( !$tmp_path or !is_dir($tmp_path) )
		cli_echo('rok_action_process_images() - DIR does not exist ' . $tmp_path, array('header' => 'error', 'exit' => 1));
	if ( !$dataset )
		cli_echo('rok_action_process_images() - Missing --dataset', array('header' => 'error', 'exit' => 1));

	// vars
	$files = sort_filesystem_iterator($tmp_path, $offset, $limit);
	$count = 0;
	$dataset_template = rok_get_templates($dataset);

	// comparing images
	cli_echo('Checking for changes and duplicates', array('format' => 'bold'));
	cli_echo('Files found: '. count($files));

	// schema for table
	$schema_duplicate = array(
		' ' => array(
			'title' => ' ',
			'size' => 1,
		),		
		'image_1' => array(
			'title' => 'Image 1',
			'size' => 14,
		),
		'image_2' => array(
			'title' => 'Image 2',
			'size' => 14,
		),
		'distortion' => array(
			'title' => 'Distortion',
			'size' => 12,
		),
		'match' => array(
			'title' => 'Match',
			'size' => '5'
		),
		'dpi' => array(
			'title' => 'DPI',
			'size' => 3
		)
	);	

	// loop through casted array
	cli_echo_array($schema_duplicate, false, array('header' => 1));
	
	// foreach ($files as $file) {
	// for($i = 0; $i < count($files) - 1; ++$i) {
	while ($file = current($files) ) {
		// iterate
    	$next = next($files);
	
		// maybe we've already removed this file
		if ( !file_exists($file) )
			continue;

		// manually SKIP_DOTS
		if ( is_dot_file($file) ){
			$mv = $trash_path.'/'.basename($file);				
			rename($file, $mv);			
			continue;
		}

		// persistent
		$count++;

		// reset vars
		$mv = $tmp_out = $tmp_matches = null;
		$distortion_total = 0;

		// duplicates
		$data = array(
			'image_1' => basename($file),
			'image_2' => '',
			'distortion' => '',
			'match' => '',
			'dpi' => imagick_get_dpi($file),
		);
		
		// already processed?
		if ( $data['dpi'] == 300 ){
			$data[' '] = '✔';
			cli_echo_array($schema_duplicate, $data);
			continue;
		}
		
		if ( cli_get_arg('compare_duplicates', 1) and $next ){
			// get current postion + next
			// $next = next($files);
			
			// check if we're on current or next
			// $current = current($files);
			// cli_echo('file: ' . basename($file), array('header' => 'debug'));
			// cli_echo('current: ' . basename($current), array('header' => 'debug'));
			// cli_echo('next: ' . basename($next), array('header' => 'debug'));

			// if ( $current != $file )
			// 	cli_echo(basename($current).' < '.basename(prev($files)) , array('header' => 'debug'));
			
			// // recheck, if not, quick
			// $current = current($files);
			// if ( $current != $file )
			// 	cli_echo(basename($current).' != '.basename($file) , array('header' => 'debug', 'exit' => 1));

			// add to $data
			$data['image_2'] = basename($next);
			$data['distortion'] = number_format(rok_compare_screenshots($file, $next), 9);

			// move file if no matches			
			if ( $data['distortion'] <= '0.021' ){
				$data['match'] = '≈';

				// move
				$mv = $trash_path.'/'.basename($file);
				rename($file, $mv);

				// log
				$data[' '] = '✖';				
				cli_echo_array($schema_duplicate, $data);

				// no need to compare to sample templates
				continue;
			}
		}

		if ( cli_get_arg('compare_templates', 1) and file_exists($file) ){
			$distortion_total = 0;
			$data['match'] = null;
			$data['image_2'] = 'Training';

			// sample per template
			foreach ( $dataset_template as $sample => $sample_path ){
				// error checks
				if ( !file_exists($sample_path) )
					echo 'rok_action_process_images() - Missing $sample_file';

				// add distortion
				$distortion = $distortion_total+= rok_compare_screenshots($file, $sample_path);

				// decide
				$data['match'].= $distortion >= '0.1' ? '✖' : '✔';
			}

			// quick avg
			$data['distortion'] = $distortion_avg = number_format($distortion_total / count($dataset_template), 9);
			
			// move file if no matches			
			if ( $distortion_avg >= '0.1' and cli_get_arg('mv', 1) ){
				$mv = $trash_path.'/'.basename($file);				
				rename($file, $mv);

				// log
				$data[' '] = '✖';				
				cli_echo_array($schema_duplicate, $data);

				// skip remaining actions
				continue;
			}
		}
		
		// if we made it this far, go ahead and add it
		if ( file_exists($file) ){
			// image resize
			$data['dpi'] = imagick_get_dpi($file);
			if ( $data['dpi'] < 300 )
				imagick_convert_dpi($file, 300);

			// image cleanup
			// imagick_convert_gray($file);

			// check + log
			$data['dpi'] = imagick_get_dpi($file);
			$data[' '] = $data['dpi'] == 300 ? '✔' : '!';

			// log
			cli_echo_array($schema_duplicate, $data);
		}
	}
	
	// close table
	cli_echo_array($schema_duplicate, false, array('footer' => true));
}

// process IMGs in this DIR
function image_process_ocr(array $args){
	// def
	$def = array(
		// files
		'tmp_path' => null,
		'output_path' => null,
		'offset' => 0,
		'limit' => -1,
		
		// job
		'dataset' => null,

		// save
		'write_to_disk' => cli_get_arg('write_to_disk', 1),
		'overwrite_disk' => cli_get_arg('overwrite_disk', 1),
		// 'output_filename' => cli_get_arg('output_filename', ),
		'write_to_db' => false,

		// TesseractOCR
		'psm' => cli_get_arg('psm', 11),
		'tessedit_write_images' => (cli_get_arg('debug') ? true : false),
	);

	// args to vars
    $args = cli_parse_args($args, $def);
    extract($args);

	// error checks
	if ( !$tmp_path or !is_dir($tmp_path) )
		cli_echo('rok_action_process_images() - DIR does not exist ' . $tmp_path, array('header' => 'error'));
	if ( !$dataset )
		cli_echo('rok_action_process_images() - Missing --dataset', array('header' => 'error'));

	// vars
	$files = sort_filesystem_iterator($tmp_path, $offset, $limit);
	$count = 0;

	// comparing images
	cli_echo('Processing OCR (' . $dataset . ')', array('format' => 'bold'));
	cli_echo('Files found: '. count($files));
	
	// output raw data from ocr
	if ( cli_get_arg('raw') ){
		// schema for table
		$schema = array(
			'raw' => array(
				'title' => 'Raw Output',
				'size' => 96,
			),
		);
		$args = array(
			'after_item' => true,
			'multi_line' => true,
		);

	} else {
		// schema for table
		$schema = array(
			'item' => array(
				'title' => 'Item',
				'size' => 28,
			),
			'bound' => array(
				'title' => 'Bound',
				'size' => 5,
			),
			'level' => array(
				'title' => 'Level',
				'size' => 5,
			),
			'type' => array(
				'title' => 'Type',
				'size' => 4,
			),			
			'trait' => array(
				'title' => 'Trait',
				'size' => 5,
			),
			'type' => array(
				'title' => 'Type',
				'size' => 5,
			),						
			'weight' => array(
				'title' => 'Weight',
				'size' => 6,
			),
			'quality' => array(
				'title' => 'Quality',
				'size' => 7,
			),
			'count' => array(
				'title' => '#',
				'size' => 1,
			),
		);
		$args = array();
	}	
	cli_echo_array($schema, false, array('header' => 1));
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

		// reset
		$data = array();
		
		// ocr
		$data['raw'] = (new TesseractOCR($file))
			// config
			// ->config('classify_enable_learning', 'false')
			// ->config('load_system_dawg', 'false')
			->config('load_freq_dawg', 'false')
			
			// english
			->lang('eng')

			// our dictionary
			->userWords(ROK_PATH_USERWORDS . '/full.txt')

			// TESTING:
			// ->psm($psm)
			// ->config('tessedit_write_images', $tessedit_write_images)

			// lets go!
		    ->run();
		
		// cleanup routine
	    switch ($dataset) {
	    	case 'inventory':
	    		// remove all extra lines
		    	$data = [
		    		'item' => null,
					'bound' => null,
					'level' => null,
					'type' => null,
					'trait' => null,
					'type' => null,
					'weight' => null,
					'quality' => null,
					'count' => null,
					'raw' => preg_replace('/\s\s+/', "\n", $data['raw']),
				];					
	    		break;
	    	
	    	default:
	    		break;
	    }

		// clean up
		if ( empty($data['raw']) )
			continue;

		// output
		cli_echo_array($schema, $data, $args);
	}
}
