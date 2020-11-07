<?php
// governors more info + kills
$GLOBALS['rok_config']['governor_more_info_kills'] = [
    'autocrop' => false,
    'sample' => ROK_PATH_IMAGES . '/sample/governor_more_info_kills-1920.jpg',
    'distortion' => 0.037,
    'oem' => 0,
    'psm' => 7,
    'ocr_schema' => [
        // pos-x, pos-y, size-x, size-y
        'name' => [
            'crop' => [475, 140, 380, 100],
        ],
        'power' => [
            'whitelist' => range(0, 9),
            'crop' => [980, 168, 216, 35],
            'callback' => 'text_remove_non_numeric',
        ],
        'kills' => [
            'whitelist' => range(0, 9),
            'crop' => [1378, 168, 258, 47],  
            'callback' => 'text_remove_non_numeric',
        ],
        'deaths' => [
            'whitelist' => range(0, 9),
            'crop' => [1332, 532, 230, 35], 
            'callback' => 'text_remove_non_numeric', 
        ],            
        't1' => [
            'whitelist' => range(0, 9),
            'crop' => [1151, 325, 141, 34],
            'callback' => 'text_remove_non_numeric',
        ],
        't2' => [
            'whitelist' => range(0, 9),
            'crop' => [1332, 374, 141, 34],
            'callback' => 'text_remove_non_numeric',
        ],
        't3' => [
            'whitelist' => range(0, 9),
            'crop' => [1151, 422, 141, 34],
            'callback' => 'text_remove_non_numeric',
        ],
        't4' => [
            'whitelist' => range(0, 9),
            'crop' => [1332, 374, 141, 34],
            'callback' => 'text_remove_non_numeric',
        ],
        't5' => [
            'whitelist' => range(0, 9),
            'crop' => [1151, 422, 141, 34],
            'callback' => 'text_remove_non_numeric',
        ],
    ],
    'table' => [
        ['Name', 'name', false, 'white'],
        ['Power', 'power', false, 'green'],
        ['Kills', 'kills', false, 'white'],
        ['Deaths', 'deaths', false, 'white'],
        ['T1', 't1', false, 'white'],
        ['T2', 't2', false, 'white'],
        ['T3', 't3', false, 'white'],
        ['T4', 't4', false, 'white'],
        ['T5', 't5', false, 'white'],
    ],
];