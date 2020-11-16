<?php

namespace ProductChecker\EmagProductChecker\Command;

use ProductChecker\EmagProductChecker\EmagProductChecker;
use ProductChecker\EmagProductChecker\EmagProductCheckerException;
use ProductChecker\EmagProductChecker\EmagProductCheckerResult;
use Notifier\PushNotification\PushNotificationService;

class EmagProductCheckerCommand
{
    private const MESSAGE_PRODUCT_AVAILABLE = 'Emag product available: %s! Price: %s. Stock: %s. Seller: %s.';
    private const MESSAGE_PRODUCT_UNAVAILABLE = 'Emag product not available yet: %s! Price: %s. Stock: %s. Seller: %s.';

    private EmagProductChecker $productChecker;
    private PushNotificationService $pushNotificationService;

    public function __construct(
        EmagProductChecker $productChecker,
        PushNotificationService $pushNotificationService
    ) {
        $this->productChecker = $productChecker;
        $this->pushNotificationService = $pushNotificationService;
    }

    public function run(array $argv): void
    {
        if (count($argv) < 4) {
            $this->printMessage(
                'Insufficient arguments for the script. Example command: php <script-name>.php "<productShortName>" "<productMaxPrice>" "<productUrl>"'
            );
            return;
        }

        $productShortName = $argv[1];
        $productMaxPrice = (int)$argv[2];
        $productUrl = $argv[3];

        try {
            $productCheckerResult = $this->productChecker->checkProduct($productUrl, $productMaxPrice);
        } catch (EmagProductCheckerException $exception) {
            $errorMessage = sprintf('ERROR checking product %s! %s', $productShortName, $exception->getMessage());
            $this->printMessage($errorMessage);

            $this->sendNotificationAndPrint($errorMessage, $productUrl);
            return;
        }

        if ($productCheckerResult->isAvailable()) {
            $successMessage = $this->getProductMessage(
                self::MESSAGE_PRODUCT_AVAILABLE,
                $productShortName,
                $productCheckerResult
            );
            $this->printMessage($successMessage);

            $this->sendNotificationAndPrint($successMessage, $productUrl);
            return;
        }

        // do not send notification when product is unavailable and no error occurred
        $this->printMessage(
            $this->getProductMessage(
                self::MESSAGE_PRODUCT_UNAVAILABLE,
                $productShortName,
                $productCheckerResult
            )
        );
    }

    private function sendNotificationAndPrint(string $message, string $productUrl): void
    {
        $notificationResponse = $this->pushNotificationService->notify($message, $productUrl);
        if ($notificationResponse->isSuccessful()) {
            $this->printMessage('Push notification sent!');
        } else {
            $this->printMessage(sprintf('ERROR sending push notification! %s', $notificationResponse->getError()));
        }
    }

    private function printMessage(string $message): void
    {
        print("\n$message");
    }

    private function getProductMessage(
        string $format,
        string $productShortName,
        EmagProductCheckerResult $productCheckerResult
    ): string {
        return sprintf(
            $format,
            $productShortName,
            $productCheckerResult->getProductData()->getPrice(),
            $productCheckerResult->getProductData()->getStockLevel(),
            $productCheckerResult->getProductData()->getSeller()
        );
    }
}
