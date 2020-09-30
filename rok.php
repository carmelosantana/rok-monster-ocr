<?php
require_once __DIR__ . '/vendor/autoload.php';

// app
defined('ROK_VER') or define('ROK_VER', 0.01);
defined('ROK_PATH') or define('ROK_PATH', __DIR__);
defined('ROK_PATH_APP') or define('ROK_PATH_APP', ROK_PATH . '/app');
defined('ROK_PATH_IMG_MASK') or define('ROK_PATH_IMG_MASK', ROK_PATH . '/images/mask');
defined('ROK_PATH_IMG_SAMPLE') or define('ROK_PATH_IMG_SAMPLE', ROK_PATH . '/images/sample');

// working
defined('ROK_PATH_INPUT') or define('ROK_PATH_INPUT', dirname(ROK_PATH) . '/input');
defined('ROK_PATH_OUTPUT') or define('ROK_PATH_OUTPUT', dirname(ROK_PATH) . '/output');
defined('ROK_PATH_TMP') or define('ROK_PATH_TMP', dirname(ROK_PATH) . '/tmp');

// app
require_once ROK_PATH_APP . '/app.php';
require_once ROK_PATH_APP . '/cli.php';
require_once ROK_PATH_APP . '/ffmpeg.php';
require_once ROK_PATH_APP . '/lib-cli.php';
require_once ROK_PATH_APP . '/lib-images.php';
require_once ROK_PATH_APP . '/lib-text.php';

// start
rok_init();