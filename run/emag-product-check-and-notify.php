<?php

use Notifier\Common\Container;
use Notifier\EmagProductChecker\EmagProductChecker;
use Notifier\EmagProductChecker\EmagProductCheckerException;
use Notifier\PushNotification\PushNotificationService;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "bootstrap.php";

if ($argc < 4) {
    print("\nInsufficient arguments for the script. Example command: php <script-name>.php \"<productShortName>\" \"<productMaxPrice>\" \"<productUrl>\"\n");
    exit;
}

$productShortName = $argv[1];
$productMaxPrice = $argv[2];
$productUrl = $argv[3];

$emagProductChecker = Container::get(EmagProductChecker::class);
$pushNotificationService = Container::get(PushNotificationService::class);

try {
    $isProductAvailable = $emagProductChecker->checkProduct($productUrl, (int)$productMaxPrice);

    if ($isProductAvailable) {
        $successMessage = sprintf("Emag product available: %s", $productShortName);
        print("\n$successMessage");

        $notificationResponse = $pushNotificationService->notify($successMessage, $productUrl);
        if ($notificationResponse->isSuccessful()) {
            print("\nPush notification sent!");
        } else {
            print(sprintf("\nERROR sending push notification! %s", $notificationResponse->getError()));
        }
    } else {
        print("\nEmag product not available yet!");
    }

} catch (EmagProductCheckerException $exception) {
    $errorMessage = sprintf('ERROR checking product %s! %s', $productShortName, $exception->getMessage());
    print("\n$errorMessage");

    $notificationResponse = $pushNotificationService->notify($errorMessage);
    if ($notificationResponse->isSuccessful()) {
        print("\nPush notification sent!");
    } else {
        print(sprintf("\nERROR sending push notification! %s", $notificationResponse->getError()));
    }
}
