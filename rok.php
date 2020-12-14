<?php
declare(strict_types=1);
namespace RoK\OCR;
use carmelosantana\CliTools as CliTools;

require_once __DIR__ . '/vendor/autoload.php';

// defines
defined('ROK_CLI_VER') or define('ROK_CLI_VER', '0.2.2');
defined('ROK_CLI_PATH') or define('ROK_CLI_PATH', __DIR__);

// user and packaged config
$GLOBALS['rok_config'] = [];
file_exists(ROK_CLI_PATH . '/config.local.php') and require_once ROK_CLI_PATH . '/config.local.php';
require_once ROK_CLI_PATH . '/config.php';

// dependencies
require_once ROK_CLI_PATH . '/app/lib-cli.php';
require_once ROK_CLI_PATH . '/app/class-autocrop.php';

// rok-monster-cli
require_once ROK_CLI_PATH . '/app/app.php';
require_once ROK_CLI_PATH . '/app/cli.php';
require_once ROK_CLI_PATH . '/app/ffmpeg.php';
require_once ROK_CLI_PATH . '/app/lib-images.php';

// start if CLI
if ( CliTools\is_cli() )
    init();