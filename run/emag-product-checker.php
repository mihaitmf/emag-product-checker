<?php

use ProductChecker\Common\Container;
use ProductChecker\EmagProductChecker\Command\EmagProductCheckerCommand;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php';

Container::get(EmagProductCheckerCommand::class)->run($argv);
