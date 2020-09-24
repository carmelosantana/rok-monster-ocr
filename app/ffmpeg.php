<?php
function get_screenshots_ffmpeg(array $args){
	$def = [
		'input' => null,
		'input_path' => ROK_PATH_PUBLIC_VIDEO,
		'output_path' => ROK_PATH_WORKING_TMP,
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
		cli_echo('get_screenshots_ffmpeg() - input file does not exist ' . $input_path, ['header' => 'error', 'exit' => 1]);
	if ( !$output_path or !is_dir($output_path) )
		cli_echo('get_screenshots_ffmpeg() - output DIR does not exist ' . $output_path, ['header' => 'error', 'exit' => 1]);

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