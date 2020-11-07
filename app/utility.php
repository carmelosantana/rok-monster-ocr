<?php
function rok_get_config(string $option, $alt=false){
	return ($GLOBALS['rok_config'][$option] ?? $alt);
}

function rok_get_files($path, $limit=-1, $offset=0){
	// error checks
	if ( !$path or !is_dir($path) )
		cli_echo('rok_get_dir() - DIR does not exist ' . $path, array('header' => 'error'));

	// files
	$files = sort_filesystem_iterator($path, $offset, $limit);
	cli_echo('Files found: '. count($files));

	return $files;
}

function is_mime_content_type($file=null, $type='image'){
	if ( !is_file($file) )
		return false;

	$mime = mime_content_type($file);
	
	if ( substr($mime, 0, strlen($type)) == $type )
		return true;

	return false;
}

function get_mime_content_type($file=null){
	foreach ( ['image', 'video'] as $type )
		if ( is_mime_content_type($file, $type) )
			return $type;

	return false;
}

function rok_callback($function=null, $arg=null){
	if ( $function and function_exists($function) ) 
		return $function($arg);
}