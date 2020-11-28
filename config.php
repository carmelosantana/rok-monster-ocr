<?php
declare(strict_types=1);
namespace RoK\OCR;

// PHP
mb_internal_encoding('utf-8');  // @important for padding

// Tesseract
defined('ROK_CLI_TESSDATA') or define('ROK_CLI_TESSDATA', null);
defined('ROK_CLI_LANG') or define('ROK_CLI_LANG', 'eng');

/**
 * Jobs
 * crop => pos-x, pos-y, size-x, size-y
 */
// governors more info + kills
$GLOBALS['rok_config']['governor_more_info_kills'] = [
    'title' => 'Governor More Info + Kills',
    'sample' => ROK_CLI_PATH . '/images/sample-governor_more_info_kills.png',
    'ocr_schema' => [
        'name' => [
            'callback' => 0,
            'crop' => [ 306, 150, 427, 93 ]
        ],
        'power' => [
            'callback' => __NAMESPACE__ . '\text_remove_non_numeric',
            'crop' => [ 837, 163, 205, 46 ],
            'whitelist' => range(0, 9)
        ],
        'kills' => [
            'callback' => __NAMESPACE__ . '\text_remove_non_numeric',
            'crop' => [ 1066, 224, 285, 41 ],
            'whitelist' => range(0, 9)
        ],
        't1' => [
            'callback' => __NAMESPACE__ . '\text_remove_non_numeric',
            'crop' => [ 1012, 332, 144, 44 ],
            'whitelist' => range(0, 9)
        ],
        't2' => [
            'callback' => __NAMESPACE__ . '\text_remove_non_numeric',
            'crop' => [ 1202, 332, 144, 44 ],
            'whitelist' => range(0, 9)
        ],
        't3' => [
            'callback' => __NAMESPACE__ . '\text_remove_non_numeric',
            'crop' => [ 1012, 384, 144, 44 ],
            'whitelist' => range(0, 9)
        ],
        't4' => [
            'callback' => __NAMESPACE__ . '\text_remove_non_numeric',
            'crop' => [ 1202, 384, 144, 44 ],
            'whitelist' => range(0, 9)
        ],
        't5' => [
            'callback' => __NAMESPACE__ . '\text_remove_non_numeric',
            'crop' => [ 1012, 436, 144, 44 ],
            'whitelist' => range(0, 9)
        ],
        'dead' => [
            'callback' => __NAMESPACE__ . '\text_remove_non_numeric',
            'crop' => [ 1208, 557, 244, 50 ],
            'whitelist' => range(0, 9)
        ]
    ],
    'autocrop' => true,
    'distortion' => '0.17',
    'oem' => 0,
    'psm' => 7,
    'table' => [
        ['Name', 'name', false, 'white'],
        ['Power', 'power', false, 'green'],
        ['Kills', 'kills', false, 'white'],
        ['T1', 't1', false, 'white'],
        ['T2', 't2', false, 'white'],
        ['T3', 't3', false, 'white'],
        ['T4', 't4', false, 'white'],
        ['T5', 't5', false, 'white'],
        ['Dead', 'dead', false, 'yellow'],
    ],
];