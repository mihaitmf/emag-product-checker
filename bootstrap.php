<?php

use ProductChecker\Common\ExecutionStats\ScriptRunStatistics;

$startTime = microtime(true);

$classLoader = require __DIR__ . '/vendor/autoload.php';

register_shutdown_function(
    static function () use ($startTime) {
        ScriptRunStatistics::printStats($startTime);
    }
);
