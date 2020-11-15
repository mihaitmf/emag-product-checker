<?php

use Notifier\Common\Container;
use Notifier\PushNotification\PushNotificationService;

$startTime = microtime(true);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "bootstrap.php";

$pushNotificationService = Container::get(PushNotificationService::class);
$response = $pushNotificationService->notify('hello message', 'link');
if ($response->isSuccessful()) {
    print('SUCCESS!');
} else {
    print(sprintf('ERROR! %s', $response->getError()));
}

print(sprintf(
    "\n\nExecution time: %.4f seconds\nMemory peak usage: %.2f MB\n",
    microtime(true) - $startTime,
    memory_get_peak_usage(true) / 1024 / 1024
));
