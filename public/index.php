<?php

declare(strict_types=1);

/**
 * Shuvo SMM Panel — Public Entry Point
 * All requests are routed through this file.
 */

define('BASE_PATH', dirname(__DIR__));
define('VERSION', '1.0.0');

require_once BASE_PATH . '/vendor/autoload.php';

use SMMPanel\Core\App;

$app = new App(BASE_PATH);
$app->run();
