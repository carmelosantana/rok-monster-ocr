<?php
require_once __DIR__ . '/vendor/autoload.php';

// app
defined('ROK_VER') or define('ROK_VER', 0.4);
defined('ROK_PATH') or define('ROK_PATH', __DIR__);
defined('ROK_PATH_APP') or define('ROK_PATH_APP', ROK_PATH . '/app');
defined('ROK_PATH_IMAGES') or define('ROK_PATH_IMAGES', ROK_PATH . '/images');

// app
require_once ROK_PATH . '/config.php';
require_once ROK_PATH_APP . '/app.php';
require_once ROK_PATH_APP . '/cli.php';
require_once ROK_PATH_APP . '/ffmpeg.php';
require_once ROK_PATH_APP . '/lib-cli.php';
require_once ROK_PATH_APP . '/lib-images.php';
require_once ROK_PATH_APP . '/lib-text.php';
require_once ROK_PATH_APP . '/utility.php';

// start
rok_init();