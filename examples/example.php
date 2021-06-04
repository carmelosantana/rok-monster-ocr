<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$args = [
    'job' => 'governor-more-info-kills',
    'input_path' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '1619990072.png',
    'tessdata' => dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'tesseract-ocr' . DIRECTORY_SEPARATOR . 'tessdata',
];

$rok = new carmelosantana\RoKMonster\RoKMonster($args);

if (php_sapi_name() == "cli") {
    // display output via CLI
    $rok->run();
} else {
    // just process the data
    $data = $rok->ocr();
}

// access the data
var_dump($rok->data);
