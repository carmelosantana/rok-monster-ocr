<?php
declare(strict_types=1);
namespace RoK\OCR;
use carmelosantana\CliTools as CliTools;

function ffmpeg_cmd(array $args): void {
	$def = [
		// task
		'action' => null,

		// file
		'input' => null,
		'output_path' => null,

		// ffmpeg
		'fps_multiplier' => 1.5,
		'ss' => null,
		'threshold' => '0.4',
	];
	
	// args to vars
    $args = array_merge($def, $args);
    extract($args);

	// output file
	$output_file = $output_path . '/' . pathinfo($input)['basename'];

	// frames
	$getID3 = new \getID3;
	$input_info = $getID3->analyze($input);
	$frames = round(round($input_info['video']['frame_rate'])*round($fps_multiplier));

	// start command
	$vf_scale = ' scale='.$input_info['video']['resolution_x'].':-1';
	$output_file_d = '"' . $output_file.'-%d.png" ';

	$cmd = 'ffmpeg -i "'.$input.'" ';

	switch ($action) {		
		// single frame
		case 'single':
			$cmd.= '-ss '.($ss ?? '00:00:01.00');
		break;

		// https://video.stackexchange.com/questions/19725/extract-key-frame-from-video-with-ffmpeg
		case 'interesting':
			$cmd.= '-ss '.($ss ?? '50').' -vf "'.$vf_scale.', thumbnail='.$frames.'" -vsync 0 '.$output_file_d;
		break;

		// https://stackoverflow.com/questions/35675529/using-ffmpeg-how-to-do-a-scene-change-detection-with-timecode
		case 'scene_change':
			$cmd.= ' -vf  "'.$vf_scale.', select=gt(scene\,'.$threshold.')" -vsync vfr ' . $output_file_d;
		break;

		default:
			// multiple frames
			$cmd.= '-y';
		break;
	}

	// command
	CliTools\cli_debug_echo($cmd, ['header' => 'FFmpeg']);	
	$output = exec($cmd);
}