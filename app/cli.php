<?php
use jc21\CliTable;
use jc21\CliTableManipulator;

// logo
function rok_cli_logo($style=true){
	$logo = '
 _____     _____    _____             _           
| __  |___|  |  |  |     |___ ___ ___| |_ ___ ___ 
|    -| . |    -|  | | | | . |   |_ -|  _| -_|  _|
|__|__|___|__|__|  |_|_|_|___|_|_|___|_| |___|_| ' . PHP_EOL;
	$desc = 'Data aggregator and analysis tools.' . PHP_EOL . PHP_EOL;

    $version = 'v' . ROK_VER . PHP_EOL;

	if ( $style )
		return cli_txt_style($logo, ['fg' => 'green', 'style' => 'bold']) . cli_txt_style($version, ['fg' => 'dark_gray']) . cli_txt_style($desc, ['fg' => 'yellow']);

	return $logo . $desc;
}

// init
function rok_init(){
	if ( is_cli() ){
		// @important for padding
		mb_internal_encoding('utf-8');
		
		// get args
		cli_parse_get();

		// setup php
		cli_php_setup();		
	}

	// welcome
	rok_cli_text('logo_version');

	// exec
	rok_bin($args=[]);

	// goodbye
	rok_cli_text('exit');
}

// rough
function rok_bin(array $args=[]){
	// change DIR
	chdir(ROK_PATH_TMP);

	// vars
	$output = false;

	// defaults
	$def = [
        'job' => null,
        'echo' => false,
    ];

	// merge with defaults if passed while loading
	if ( empty($args) ){
	    $args = cli_parse_args($_GET, $def);

		// add CLI args
	    $args = array_merge($args, $_GET);    

	// only merge with defaults from external program
	} else {
		$args = array_merge($def, $args);

	}

	// run job if defined
	if ( isset(rok_get_config('samples')[$args['job']]) ){
		$output = rok_do_ocr($args);

	} else {
		rok_cli_help();

	}

	return $output;
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

function rok_cli_table($schema=null, $data=null){
	if ( !$schema or !is_cli() )
		return false;

	$table = new CliTable;
	$table->setTableColor('blue');
	$table->setHeaderColor('cyan');
	foreach ( $schema as $tbl_schema )
		$table->addField($tbl_schema[0], $tbl_schema[1], ($tbl_schema[2] ? new CliTableManipulator($tbl_schema[2]) : false), $tbl_schema[3]);
	$table->injectData($data);
	$table->display();	
}