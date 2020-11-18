<?php

use ProductChecker\Common\Command\ConsoleEventListener;
use ProductChecker\Common\Container;
use ProductChecker\EmagProductChecker\Command\EmagProductCheckerCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php';

$app = new Application('Emag Product Checker');

$eventDispatcher = new EventDispatcher();
$consoleEventListener = Container::get(ConsoleEventListener::class);

/** @uses ConsoleEventListener::onCommandBegin() */
/** @uses ConsoleEventListener::onCommandFinish() */
/** @uses ConsoleEventListener::onCommandError() */
$eventDispatcher->addListener(ConsoleEvents::COMMAND, [$consoleEventListener, 'onCommandBegin']);
$eventDispatcher->addListener(ConsoleEvents::TERMINATE, [$consoleEventListener, 'onCommandFinish']);
$eventDispatcher->addListener(ConsoleEvents::ERROR, [$consoleEventListener, 'onCommandError']);

$app->setDispatcher($eventDispatcher);

$app->add(Container::get(EmagProductCheckerCommand::class));

$app->run();
