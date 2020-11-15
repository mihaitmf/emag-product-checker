<?php

use Notifier\Common\Container;
use Notifier\EmagProductChecker\EmagProductCheckerRunner;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "bootstrap.php";

Container::get(EmagProductCheckerRunner::class)->run($argv);
