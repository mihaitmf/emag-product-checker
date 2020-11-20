<?php

use ProductChecker\Common\Container;
//use ProductChecker\Common\Console\ExecutionStatistics;

//$startTime = microtime(true);

require_once __DIR__ . '/vendor/autoload.php';

Container::setDefinitionsFilePath(__DIR__ . DIRECTORY_SEPARATOR . 'di-config.php');

//register_shutdown_function(
//    static function () use ($startTime) {
//        ExecutionStatistics::printStats($startTime);
//    }
//);
