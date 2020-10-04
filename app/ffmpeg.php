<?php
function rok_do_ffmpeg_cmd(array $args){
	$def = [
		// task
		'action' => null,

		// file
		'input' => null,
		'output_path' => ROK_PATH_TMP,

		// ffmpeg
		'frames' => 60,
		'ss' => null,
	];
	
	// args to vars
    $args = cli_parse_args($args, $def);
    extract($args);

	// error checks
	if ( !$input or !file_exists($input) )
		cli_echo('rok_do_ffmpeg() - input file does not exist ' . $input_path, ['header' => 'error', 'exit' => 1]);
	if ( !$output_path or !is_dir($output_path) )
		cli_echo('rok_do_ffmpeg() - output DIR does not exist ' . $output_path, ['header' => 'error', 'exit' => 1]);

	// output file
	$output_file = $output_path . '/' . pathinfo($input)['basename'];

	// start command
	$cmd = 'ffmpeg -i "'.$input.'" ';

	switch ($action) {		
		case 'single':
			// single frame
			$cmd.= '-ss '.($ss ?? '00:00:01.00');
		break;
		
		case 'interesting':
			$cmd.= '-ss '.($ss ?? '50').' -vf "scale='.rok_get_config('width', 1920).':-1, thumbnail='.$frames.'" -vsync 0 "'.$output_file.'-%d.png" ';
		break;

		default:
			// multiple frames
			$cmd.= '-y';
		break;
	}

	// command
	cli_echo($cmd, ['header' => 'FFmpeg']);	
	$output = exec($cmd);
}