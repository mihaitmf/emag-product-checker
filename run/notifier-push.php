<?php

use Notifier\Common\Container;
use Notifier\PushNotification\PushNotificationService;

$startTime = microtime(true);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "bootstrap.php";

if ($argc < 2) {
    print("\nInsufficient arguments for the script. Example command: php <script-name>.php \"<message>\" \"<link (optional)>\"\n");
    exit;
}

$message = $argv[1];
$linkUrl = isset($argv[2]) ? $argv[2] : '';

$pushNotificationService = Container::get(PushNotificationService::class);
$response = $pushNotificationService->notify($message, $linkUrl);

if ($response->isSuccessful()) {
    print("\nSUCCESS!");
} else {
    print(sprintf('ERROR! %s', $response->getError()));
}

print(sprintf(
    "\n\nExecution time: %.4f seconds\nMemory peak usage: %.2f MB\n",
    microtime(true) - $startTime,
    memory_get_peak_usage(true) / 1024 / 1024
));
