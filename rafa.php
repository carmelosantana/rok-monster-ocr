<?php
// app
$_RAFA = array();	
defined('RAFA_VER') or define('RAFA_VER', 0.01);
defined('RAFA_PATH') or define('RAFA_PATH', __DIR__);
defined('RAFA_PATH_APP') or define('RAFA_PATH_APP', RAFA_PATH . '/app');
defined('RAFA_PATH_TEMPLATES') or define('RAFA_PATH_TEMPLATES', RAFA_PATH . '/templates');

// working
defined('RAFA_PATH_WORKING') or define('RAFA_PATH_WORKING', dirname(RAFA_PATH) . '/.working');
defined('RAFA_PATH_WORKING_TMP') or define('RAFA_PATH_WORKING_TMP', RAFA_PATH_WORKING . '/tmp');
defined('RAFA_PATH_WORKING_TRASH')	or define('RAFA_PATH_WORKING_TRASH', RAFA_PATH_WORKING . '/.trash');

// input/output
defined('RAFA_PATH_PRIVATE') or define('RAFA_PATH_PRIVATE', dirname(RAFA_PATH));
defined('RAFA_PATH_PUBLIC') or define('RAFA_PATH_PUBLIC', dirname(dirname(RAFA_PATH)) . '/public');
defined('RAFA_PATH_PUBLIC_IN') or define('RAFA_PATH_PUBLIC_IN', RAFA_PATH_PUBLIC . '/in');
defined('RAFA_PATH_PUBLIC_OUT') or define('RAFA_PATH_PUBLIC_OUT', RAFA_PATH_PUBLIC . '/out');

// audio/video (might be externally configured later)
defined('RAFA_PATH_PUBLIC_AUDIO') or define('RAFA_PATH_PUBLIC_AUDIO', RAFA_PATH_PUBLIC_IN . '/audio');
defined('RAFA_PATH_PUBLIC_VIDEO') or define('RAFA_PATH_PUBLIC_VIDEO', RAFA_PATH_PUBLIC_IN . '/video');

// app
require_once RAFA_PATH_APP . '/rafa-cli-app.php';
require_once RAFA_PATH_APP . '/rafa-cli-libs.php';
require_once RAFA_PATH_APP . '/rafa-images.php';

// composer
require_once RAFA_PATH . '/vendor/autoload.php';

// start
rafa_init();