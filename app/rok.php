<?php
// use
use thiagoalessio\TesseractOCR\TesseractOCR;

function rok_get_kills(){

	// vars
	$files = sort_filesystem_iterator(ROK_PATH_BLUESTACKS, $offset, $limit);
	$count = 0;    
    $files = ROK_PATH_BLUESTACKS;
    
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
    }    
}