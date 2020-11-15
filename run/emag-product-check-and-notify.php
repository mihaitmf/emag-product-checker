<?php

use Notifier\Common\Container;
use Notifier\EmagProductChecker\EmagProductChecker;
use Notifier\EmagProductChecker\EmagProductCheckerException;
use Notifier\PushNotification\PushNotificationService;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "bootstrap.php";

//if ($argc < 4) {
//    print("\nInsufficient arguments for the script. Example command: php <script-name>.php \"<productShortName>\" \"<productMaxPrice>\" \"<productUrl>\"\n");
//    exit;
//}
//
//$productShortName = $argv[1];
//$productMaxPrice = $argv[2];
//$productUrl = $argv[3];

$productShortName = 'Roborock S5 Max';
$productMaxPrice = '1800';
$productUrl = 'https://www.emag.ro/robot-de-aspirare-roborock-cleaner-s5-max-wifi-aspirator-si-mop-smart-top-up-navigare-lidar-setare-bariere-virtuale-zone-no-mop-alb-s5e02-00-white/pd/D888WWBBM/';

$emagProductChecker = Container::get(EmagProductChecker::class);
$pushNotificationService = Container::get(PushNotificationService::class);

try {
    $isProductAvailable = $emagProductChecker->checkProduct($productUrl, (int)$productMaxPrice);

    if ($isProductAvailable) {
        $successMessage = sprintf('Emag product available: %s', $productShortName);
        print($successMessage);

        $notificationResponse = $pushNotificationService->notify($successMessage, $productUrl);
        if ($notificationResponse->isSuccessful()) {
            print("\nPush notification sent!");
        } else {
            print(sprintf('ERROR sending push notification! %s', $notificationResponse->getError()));
        }
    } else {
        print('Emag product not available yet!');
    }

} catch (EmagProductCheckerException $exception) {
    $errorMessage = sprintf('ERROR checking product %s! %s', $productShortName, $exception->getMessage());
    print($errorMessage);

    $notificationResponse = $pushNotificationService->notify($errorMessage);
    if ($notificationResponse->isSuccessful()) {
        print("\nPush notification sent!");
    } else {
        print(sprintf('ERROR sending push notification! %s', $notificationResponse->getError()));
    }
}
