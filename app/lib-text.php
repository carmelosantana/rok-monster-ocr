<?php
function text_remove_non_numeric($string){
	return preg_replace('/[^0-9,.]+/', '', $string);
}

function text_remove_extra_lines(string $string=null){
	return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $string);
}