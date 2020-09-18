<?php
// use
use thiagoalessio\TesseractOCR\TesseractOCR;
use \OpenXBL\Api;

/**
	// Setup
	1. Navigate to DIR
	2. $ composer install 

	// builds a sample index.php at 4 seconds
	php esopower-cli.php  --dataset=inventory --screencap=single --ss=00:00:04.00 --sample=1

	// rebuilds all thumbs
	php esopower-cli.php  --dataset=inventory --screencap=1 --sample=1

	// rebuilds all thumbs
	php esopower-cli.php  --clear=1 --data=inventory --screencap=1 --sample=1

	// screenshot every frame
	ffmpeg -i withdraw.flv thumb%04d.png -hide_banner

	// screenshot every keyframe
	ffmpeg -i withdraw.flv -vf "select=eq(pict_type\,I)" -vsync vfr thumb%04d.png -hide_banner
	ffmpeg -i withdraw.flv -vf "select=eq(pict_type\,I)",scale=720:-1,tile -frames:v 1 -vsync vfr mosiac.png -hide_banner

	// This will create a mosaic composed of the first scenes, and it looks like this:
	ffmpeg -i withdraw.flv -vf select='gt(scene\,0.4)',scale=160:120,tile -frames:v 1 withdraw-mosaic.png
	ffmpeg -ss 00:00:05 -i withdraw.flv -frames 1 -vf "select=not(mod(n\,400)),scale=160:120,tile=4x3" withdraw-mosaic.png

	// https://stackoverflow.com/questions/3827611/ffmpeg-to-capture-screenshot-from-a-video-file-in-a-fine-time-unit#4576802
	ffmpeg -ss 00:00:01.01 -i withdraw.flv -y -f image2 \
	 -vframes 1 withdraw.png
	ffmpeg -ss 00:00:01.01 -i withdraw.flv -f image2 -vframes 1 withdraw-index.png

	// scaled screenshot
	ffmpeg -ss 00:10:20 -t 1 -s 400x300 -i <INPUT_FILE> -f mjpeg <OUTPUT_FILE>

	// helpfull
	- ffmpeg
	https://unix.stackexchange.com/questions/190431/convert-a-video-to-a-fixed-screen-size-by-cropping-and-resizing#192021
	https://www.bugcodemaster.com/article/crop-video-using-ffmpeg	
	- resize images
	https://www.imagemagick.org/discourse-server/viewtopic.php?t=18241
	- compare images
	https://www.imagemagick.org/discourse-server/viewtopic.php?t=20761&start=15
*/
// logo
function rafa_cli_logo($style=true){
	$logo = '
 ,ggggggggggg,                                  
dP"""88""""""Y8,              ,dPYb,            
Yb,  88      `8b              IP\'`Yb            
 `"  88      ,8P              I8  8I            
     88aaaad8P"               I8  8\'            
     88""""Yb,      ,gggg,gg  I8 dP    ,gggg,gg 
     88     "8b    dP"  "Y8I  I8dP    dP"  "Y8I 
     88      `8i  i8\'    ,8I  I8P    i8\'    ,8I 
     88       Yb,,d8,   ,d8b,,d8b,_ ,d8,   ,d8b,
     88        Y8P"Y8888P"`Y8PI8"888P"Y8888P"`Y8
                              I8 `8,            
                              I8  `8,           
                              I8   8I           
                              I8   8I           
                              I8, ,8\'           
                               "Y8P\' 	';
	$desc = '
|_|. _ |_ |. _ |_ _|_  /~_ _  _  _  _ _ _|_ _  _
| ||(_|| |||(_|| | |   \_/(/_| |(/_| (_| | (_)| 
     _|      _| ' . PHP_EOL;

    $version = 'v' . RAFA_VER . PHP_EOL;

	if ( $style )
		return cli_txt_style($logo, ['fg' => 'purple']) . cli_txt_style($version, ['fg' => 'dark_gray']) . cli_txt_style($desc, ['fg' => 'red']);

	return $logo . $desc;
}

// init
function rafa_init(){
	// @important for padding
	mb_internal_encoding('utf-8');

	// get args
	cli_parse_get();

	// setup php
    cli_php_setup();

	// welcome
	rafa_cli_text('logo_version');

	// exec
	rafa_bin();

	// goodbye
	rafa_cli_text('exit');
}

// rough
function rafa_bin(array $args=[]){
	if ( empty($_GET) )
		rafa_cli_help();

	// working dir setup
	rafa_working_dir_init(cli_get_arg('purge'));    

	// change DIR
	chdir(RAFA_PATH_WORKING_TMP);

	// args to vars
	$def = [
		// job + action performed
		'job' => cli_get_arg('job'),
		'action' => cli_get_arg('action'),

		// in/out
		'input' => cli_get_arg('input'),
		// 'input_path' => cli_get_arg('input_path', RAFA_PATH_PUBLIC_IN),
		'output' => cli_get_arg('output'),
		// 'output_path' => cli_get_arg('output_path', ),

		// screenshot
		'screenshot' => cli_get_arg('screenshot'),		
	];
    $args = cli_parse_args($args, $def);
    extract($args);

	// perform set of actions
	switch ( $job ){
		case 'highgen':
			rafa_action_highgen();
			break;

		case 'screenshot':
			rafa_action_screenshot($args);
			break;

		case 'img-process':
			rafa_action_process_images($args);
			break;

		case 'ocr':
			rafa_action_process_ocr($args);
			break;

		default:
			rafa_cli_text('help');
			break;	
	}
}

function rafa_cli_text($text=null, $echo=true){
	$out = null;

	switch($text){
		case 'logo_version':
			$out = rafa_cli_logo();
			break;

		case 'exit':
			$out.= PHP_EOL . cli_echo('Finished.', ['fg' => 'green', 'style' => 'bold', 'echo' => false ]);
			$out.= 'Peak memory: ' . format_bytes(memory_get_peak_usage()) . PHP_EOL . PHP_EOL;
			$out.= cli_echo_array(
				// schema
				array(
					'love' => array(
						'title' => 'Made with '.cli_txt_style('♥', ['fg' => 'red', 'style' => 'bold']).' in NY',
						'size' => 17,
					)
				),

				// data
				false,

				// args
				array(
					'header' => true,
					'echo' => false,
				)
			);
			$out.= PHP_EOL;
			break;
	}

	if ( $echo )
		echo $out;

	return $out;
}

function rafa_cli_help(){
	$schema = array(
		'option' => array(
			'title' => 'Option',
			'size' => 14,
		),
		'desc' => array(
			'title' => 'Description',
			'size' => 28,
		)
	);
	$data = [
		'option' => '--job',
		'desc' => 'auto, inventory, highlights',
	];
	cli_echo_array($schema, false, array('header' => 1));
	cli_echo_array($schema, $data);
	cli_echo_array($schema, false, array('footer' => true));	
}

function rafa_working_dir_init($purge=false){
    // cleanup 
	if ( cli_get_arg('purge', $purge) ){
		if ( cli_rmdirr(RAFA_PATH_WORKING_TMP) )
			cli_echo(cli_txt_color('✖') . ' Deleted ' . cli_txt_color(RAFA_PATH_WORKING_TMP, ['fg' => 'yellow']));

		if ( cli_rmdirr(RAFA_PATH_WORKING_TRASH) )
			cli_echo(cli_txt_color('✖') . ' Deleted ' . cli_txt_color(RAFA_PATH_WORKING_TRASH, ['fg' => 'yellow']));
	}

    // make DIR, happens after cleanup
    cli_mkdir(RAFA_PATH_WORKING, 1);
    cli_mkdir(RAFA_PATH_WORKING_TMP, 1);
    cli_mkdir(RAFA_PATH_WORKING_TRASH, 1);	
}

function rafa_action_highgen(){

}

function rafa_action_screenshot(array $args){
	$def = [
		'input' => null,
		'input_path' => RAFA_PATH_PUBLIC_VIDEO,
		'output_path' => RAFA_PATH_WORKING_TMP,
		'screenshot' => null,
		'action' => null,
	];
	
	// args to vars
    $args = cli_parse_args($args, $def);
    extract($args);

    // append file to path
    if ( $input )
    	$input_path = $input_path . '/' . $input;

	// error checks
	if ( !$input_path or !file_exists($input_path) )
		cli_echo('rafa_action_screenshot() - input file does not exist ' . $input_path, ['header' => 'error', 'exit' => 1]);
	if ( !$output_path or !is_dir($output_path) )
		cli_echo('rafa_action_screenshot() - output DIR does not exist ' . $output_path, ['header' => 'error', 'exit' => 1]);

	switch (cli_get_arg('screenshot', $screenshot)) {
		case 'single':
			// single frame
			$cmd = 'ffmpeg -y -ss '.cli_get_arg('ss', '00:00:01.00').' -i '.$input_path . ' ';
			break;
		
		default:
			// multiple frames
			$cmd = 'ffmpeg -y -i '.$input_path . ' ';
			break;
	}

	// crop for inventory
	switch (cli_get_arg('action', $action)) {
		case 'key_frames':
			$cmd.= ' -vf "select=eq(pict_type\,I)" -vsync vfr ';
			break;

		case 'inventory':
			$cmd.= ' -filter:v "crop=in_w/4.6:in_h/1.4:in_w/3.4:in_h/5.8" ';
			break;

		default:
			if ( !cli_get_arg('dataset') )
				cli_echo('Missing $dataset', ['header' => 'error', 'exit' => 1]);
			break;
	}

	switch (cli_get_arg('screenshot', $screenshot)) {
		case 'single':
			// single frame
			$cmd.= ' -f image2 -vframes 1 index.png';
			break;
		
		default:
			// multiple frames
			$cmd.= $output_path.'/frame-%04d.png -hide_banner';
			break;
	}
	
	// CMD
	cli_echo($cmd, ['header' => 'debug']);	
	$output = exec($cmd);
	echo $output . PHP_EOL;
}

// disregard screens we don't want
function rafa_action_process_images(array $args){
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
		cli_echo('rafa_action_process_images() - DIR does not exist ' . $tmp_path, array('header' => 'error', 'exit' => 1));
	if ( !$dataset )
		cli_echo('rafa_action_process_images() - Missing --dataset', array('header' => 'error', 'exit' => 1));

	// vars
	$files = sort_filesystem_iterator($tmp_path, $offset, $limit);
	$count = 0;
	$dataset_template = rafa_get_templates($dataset);

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
			$data['distortion'] = number_format(rafa_compare_screenshots($file, $next), 9);

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
					echo 'rafa_action_process_images() - Missing $sample_file';

				// add distortion
				$distortion = $distortion_total+= rafa_compare_screenshots($file, $sample_path);

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
function rafa_action_process_ocr(array $args){
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
		cli_echo('rafa_action_process_images() - DIR does not exist ' . $tmp_path, array('header' => 'error'));
	if ( !$dataset )
		cli_echo('rafa_action_process_images() - Missing --dataset', array('header' => 'error'));

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
			->userWords(RAFA_PATH_USERWORDS . '/full.txt')

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

// Templates per view type
function rafa_get_templates($dataset=null){
	switch ($dataset)	{
		case 'inventory':
			return array(
				'Currency' => RAFA_PATH_TEMPLATES . '/' . $dataset . '/template-currency.png',
				'Item' => RAFA_PATH_TEMPLATES . '/' . $dataset . '/template-item.png',
				'Potion' => RAFA_PATH_TEMPLATES . '/' . $dataset . '/template-potion.png',
			);			
			break;
	}
}

// compare images
function rafa_compare_screenshots($image_path_1=null, $image_path_2=null){
	// https://stackoverflow.com/questions/37581147/percentage-of-pixels-that-have-changed-in-an-image-in-php#37594159
	if ( !$image_path_1 or !file_exists($image_path_1) ){
		echo '!' . $image_path_1;
		return false;
	}
	if ( !$image_path_2 or !file_exists($image_path_2) ){
		echo '!' . $image_path_2;
		return false;
	}

	// load up
	$image1 = new Imagick($image_path_1);
	$image2 = new Imagick($image_path_2);

	// compare
	$result = $image1->compareImages($image2,Imagick::METRIC_MEANABSOLUTEERROR);
	$p1 = $image1->getImageProperties();
	return $p1['distortion'];
}