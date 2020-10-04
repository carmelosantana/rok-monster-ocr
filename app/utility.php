<?php
function rok_get_config(string $option, $alt=false){
	return ($GLOBALS['rok_config'][$option] ?? $alt);
}

function rok_get_input_path($dir){
	return ROK_PATH_INPUT . '/' . $dir;
}

function rok_get_public_images($img, $dir, $ext='.png'){
	return ROK_PATH_IMAGES . '/' . $dir . '/' . $img . $ext;
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

function rok_purge_tmp(){
	$files = sort_filesystem_iterator(ROK_PATH_TMP);

	foreach ( $files as $file )
		if ( is_file($file) )
			unlink($file);
}