<?php
// Path for media and working tmp
defined('ROK_PATH_INPUT') or define('ROK_PATH_INPUT', dirname(ROK_PATH) . '/input');
defined('ROK_PATH_OUTPUT') or define('ROK_PATH_OUTPUT', dirname(ROK_PATH) . '/output');
defined('ROK_PATH_TMP') or define('ROK_PATH_TMP', dirname(ROK_PATH) . '/tmp');

// Jobs and media config
global $rok_config;
$rok_config = [
    // resolution
    'width' => 1920,
    'height' => 1080,

    // when to capture
    'frames' => 90,

    // job and app settings
    'purge' => 1,   // delete tmp dir on new start
    'echo' => 0,   // echo every OCR result

    // keys to use while building generic user word lists
    'user_words' => [
        'name',
    ],

    // samples by categories
    'samples' => [
        'governor_more_info' => [
            'governor_more_info_kills' => '0.037', 
        ]
    ],
    // oem 1 reads name well, but not numbers
    // oem 0 reads all, messes up some chars in 
    
    // governors more info + kills
    'governor_more_info_kills' => [
        'oem' => 0,
		'psm' => 7,
        'ocr_schema' => [
            // pos-x, pos-y, size-x, size-y
            'name' => [
                'crop' => [472, 147, 407, 91],
            ],
            'power' => [
                'whitelist' => range(0, 9),
                'crop' => [975, 168, 216, 35],
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
        'csv_headers' => [
            'name' => 'name',
            'power' => 'power',
            'kills' => 'kills',
            'deaths' => 'deaths',
            't1' => 't1',
            't2' => 't2',
            't3' => 't3',
            't4' => 't4',
            't5' => 't5',
            'date' => '_created',
        ],
        'table' => [
            ['Name', 'name', false, 'white'],
            ['Power', 'power', 'nicenumber', 'green'],
            ['Kills', 'kills', 'nicenumber', 'white'],
            ['Deaths', 'deaths', 'nicenumber', 'white'],
            ['T1', 't1', 'nicenumber', 'white'],
            ['T2', 't2', 'nicenumber', 'white'],
            ['T3', 't3', 'nicenumber', 'white'],
            ['T4', 't4', 'nicenumber', 'white'],
            ['T5', 't5', 'nicenumber', 'white'],
        ],
    ],
];