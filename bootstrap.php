<?php

use Notifier\Common\RunScriptStatistics;

$startTime = microtime(true);

$classLoader = require_once __DIR__ . '/vendor/autoload.php';

register_shutdown_function(function () use ($startTime) {
    RunScriptStatistics::printStats($startTime);
});
