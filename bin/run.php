<?php

use ProductChecker\Common\Command\ConsoleEventListener;
use ProductChecker\Common\Container;
use ProductChecker\EmagProductChecker\Command\EmagProductCheckerCommand;
use ProductChecker\EmagProductChecker\Command\EmagProductListCheckerCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php';

$app = new Application('Emag Product Checker');

$eventDispatcher = new EventDispatcher();
$consoleEventListener = Container::get(ConsoleEventListener::class);

/** @uses ConsoleEventListener::onCommandBegin() */
/** @uses ConsoleEventListener::onCommandFinish() */
$eventDispatcher->addListener(ConsoleEvents::COMMAND, [$consoleEventListener, 'onCommandBegin']);
$eventDispatcher->addListener(ConsoleEvents::TERMINATE, [$consoleEventListener, 'onCommandFinish']);

$app->setDispatcher($eventDispatcher);

$app->add(Container::get(EmagProductCheckerCommand::class));
$app->add(Container::get(EmagProductListCheckerCommand::class));

$app->run();
