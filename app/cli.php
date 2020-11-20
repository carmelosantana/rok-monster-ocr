<?php
declare(strict_types=1);
namespace RoK\OCR;

use carmelosantana\CliTools as CliTools;

// logo
function echo_logo(bool $echo=true){
	$logo = '
 _____     _____    _____             _           
| __  |___|  |  |  |     |___ ___ ___| |_ ___ ___ 
|    -| . |    -|  | | | | . |   |_ -|  _| -_|  _|
|__|__|___|__|__|  |_|_|_|___|_|_|___|_| |___|_| ' . PHP_EOL;
	$desc = 'Data aggregator and analysis tools.' . PHP_EOL . PHP_EOL;

    $version = 'v' . ROK_CLI_VER . PHP_EOL;

	$out = null;
	$out.= CliTools\text_style($logo, ['fg' => 'green', 'style' => 'bold']);
	$out.= CliTools\text_style($version, ['fg' => 'dark_gray']);
	$out.= CliTools\text_style($desc, ['fg' => 'yellow']);

	if ( !$echo )
		return $echo;

	echo $out;
}

// init
function init(array $args=[]): void{
	if ( CliTools\is_cli() ){
		// TODO: Still testing?
		gc_disable();

		// get args
		CliTools\parse_get();

		// debug
		if ( CliTools\get_arg('debug') ){
			ini_set('display_errors', '1');
			ini_set('display_startup_errors', '1');
			error_reporting(E_ALL);
		}
	}

	// welcome
	echo_logo();

	// add CLI args
	if ( empty($args) )
	    $args = array_merge($args, $_GET);    

	// exec
	ocr($args);

	// goodbye
	CliTools\cli_echo_footer();
	CliTools\cli_echo_made_with_love('NY');
}