<?php
// use
use thiagoalessio\TesseractOCR\TesseractOCR;
use Treinetic\ImageArtist\lib\Image;

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
function rok_cli_logo($style=true){
	$logo = '
 _____     _____    _____             _           
| __  |___|  |  |  |     |___ ___ ___| |_ ___ ___ 
|    -| . |    -|  | | | | . |   |_ -|  _| -_|  _|
|__|__|___|__|__|  |_|_|_|___|_|_|___|_| |___|_| ';
	$desc = '
 Data aggregator and analysis tools.' . PHP_EOL . PHP_EOL;

    $version = 'v' . ROK_VER . PHP_EOL;

	if ( $style )
		return cli_txt_style($logo, ['fg' => 'green', 'style' => 'bold']) . cli_txt_style($version, ['fg' => 'dark_gray']) . cli_txt_style($desc, ['fg' => 'yellow']);

	return $logo . $desc;
}

// init
function rok_init(){
	// @important for padding
	mb_internal_encoding('utf-8');

	// get args
	cli_parse_get();

	// setup php
    cli_php_setup();

	// welcome
	rok_cli_text('logo_version');

	// exec
	rok_bin();

	// goodbye
	rok_cli_text('exit');
}

// rough
function rok_bin(array $args=[]){
	// change DIR
	chdir(ROK_PATH_TMP);

	// args to vars
	$def = [
        'input_path' => cli_get_arg('input_path', ROK_PATH_INPUT),
        'job' => cli_get_arg('job'),
        
        // output
        'echo' => cli_get_arg('echo', false),
	];
    $args = cli_parse_args($args, $def);

	// perform set of actions
	switch ( $args['job'] ){
		case 'governor_profile_kills':
			$job_args = [
				'dataset' => 'governor_profile_kills',
                
				// ocr
				'raw' => true,
				'oem' => 3,
				'psm' => 11,
                'gray' => false,
                'scale' => false,
                'crop' => false,
                'dpi' => false,
				'image_process' => true,
			];
            $args = cli_parse_args($args, $job_args);
            rok_do_ocr($args);
		break;

		default:
			rok_cli_help();
			break;	
	}
}

function rok_cli_text($text=null, $echo=true){
	$out = null;

	switch($text){
		case 'logo_version':
			$out = rok_cli_logo();
			break;

		case 'exit':
			$out.= PHP_EOL . cli_echo('Finished.', ['fg' => 'green', 'style' => 'bold', 'echo' => false ]);
			$out.= 'Peak memory: ' . format_bytes(memory_get_peak_usage()) . PHP_EOL . PHP_EOL;
			$out.= cli_echo_array(
				// schema
				array(
					'love' => array(
						'title' => cli_txt_style('Made with ', ['fg' => 'cyan', 'style' => 'bold']).cli_txt_style('♥', ['fg' => 'red', 'style' => 'bold']).cli_txt_style(' in NY', ['fg' => 'cyan', 'style' => 'bold']),
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

function rok_cli_help(){
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
		'desc' => 'player, kingdom, inventory',
	];
	cli_echo_array($schema, false, array('header' => 1));
	cli_echo_array($schema, $data);
	cli_echo_array($schema, false, array('footer' => true));	
}

function rok_working_dir_init($purge=false){
    // cleanup 
	if ( cli_get_arg('purge', $purge) ){
		if ( cli_rmdirr(ROK_PATH_WORKING_TMP) )
			cli_echo(cli_txt_color('✖') . ' Deleted ' . cli_txt_color(ROK_PATH_WORKING_TMP, ['fg' => 'yellow']));

		if ( cli_rmdirr(ROK_PATH_WORKING_TRASH) )
			cli_echo(cli_txt_color('✖') . ' Deleted ' . cli_txt_color(ROK_PATH_WORKING_TRASH, ['fg' => 'yellow']));
	}

    // make DIR, happens after cleanup
    cli_mkdir(ROK_PATH_WORKING, 1);
    cli_mkdir(ROK_PATH_WORKING_TMP, 1);
    cli_mkdir(ROK_PATH_WORKING_TRASH, 1);	
}