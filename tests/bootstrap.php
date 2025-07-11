<?php
/**
 * @author      Richard Déloge <richard@teknoo.software>
 */

defined('RUN_CLI_MODE')
    || define('RUN_CLI_MODE', true);

defined('PHPUNIT')
    || define('PHPUNIT', true);

set_time_limit(0);
date_default_timezone_set('UTC');

error_reporting(E_ALL);

ini_set('memory_limit', '128M');

include __DIR__.'/../vendor/autoload.php';
