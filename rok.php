<?php
require_once __DIR__ . '/vendor/autoload.php';

// app
defined('ROK_VER') or define('ROK_VER', 0.01);
defined('ROK_PATH') or define('ROK_PATH', __DIR__);
defined('ROK_PATH_APP') or define('ROK_PATH_APP', ROK_PATH . '/app');

// working
defined('ROK_PATH_BLUESTACKS') or define('ROK_PATH_BLUESTACKS', dirname(dirname(ROK_PATH)) . '/BlueStacks');
defined('ROK_PATH_WORKING') or define('ROK_PATH_WORKING', dirname(ROK_PATH) . '/.working');
defined('ROK_PATH_WORKING_TMP') or define('ROK_PATH_WORKING_TMP', ROK_PATH_WORKING . '/tmp');
defined('ROK_PATH_WORKING_TRASH')	or define('ROK_PATH_WORKING_TRASH', ROK_PATH_WORKING . '/.trash');

// app
require_once ROK_PATH_APP . '/app.php';
require_once ROK_PATH_APP . '/ffmpeg.php';
require_once ROK_PATH_APP . '/lib-cli.php';
require_once ROK_PATH_APP . '/lib-images.php';
require_once ROK_PATH_APP . '/images.php';
require_once ROK_PATH_APP . '/rok.php';

// start
rok_init();