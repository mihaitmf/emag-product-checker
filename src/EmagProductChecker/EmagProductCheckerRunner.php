<?php

namespace Notifier\EmagProductChecker;

use Notifier\PushNotification\PushNotificationService;

class EmagProductCheckerRunner
{
    const MESSAGE_PRODUCT_AVAILABLE = 'Emag product available: %s! Price: %s. Stock: %s. Seller: %s.';
    const MESSAGE_PRODUCT_UNAVAILABLE = 'Emag product not available yet: %s! Price: %s. Stock: %s. Seller: %s.';

    /** @var EmagProductChecker */
    private $productChecker;

    /** @var PushNotificationService */
    private $pushNotificationService;

    public function __construct(EmagProductChecker $productChecker, PushNotificationService $pushNotificationService)
    {
        $this->productChecker = $productChecker;
        $this->pushNotificationService = $pushNotificationService;
    }

    /**
     * @param array $argv
     *
     * @return void
     */
    public function run(array $argv)
    {
        if (count($argv) < 4) {
            $this->printMessage("\nInsufficient arguments for the script. Example command: php <script-name>.php \"<productShortName>\" \"<productMaxPrice>\" \"<productUrl>\"\n");
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
        $this->printMessage($this->getProductMessage(
            self::MESSAGE_PRODUCT_UNAVAILABLE,
            $productShortName,
            $productCheckerResult
        ));
    }

    /**
     * @param string $message
     * @param string $productUrl
     *
     * @return void
     */
    private function sendNotificationAndPrint($message, $productUrl)
    {
        $notificationResponse = $this->pushNotificationService->notify($message, $productUrl);
        if ($notificationResponse->isSuccessful()) {
            $this->printMessage('Push notification sent!');
        } else {
            $this->printMessage(sprintf('ERROR sending push notification! %s', $notificationResponse->getError()));
        }
    }

    /**
     * @param string $message
     *
     * @return void
     */
    private function printMessage($message)
    {
        print("\n$message");
    }

    /**
     * @param string $format
     * @param string $productShortName
     * @param EmagProductCheckerResult $productCheckerResult
     *
     * @return string
     */
    private function getProductMessage($format, $productShortName, EmagProductCheckerResult $productCheckerResult)
    {
        return sprintf(
            $format,
            $productShortName,
            $productCheckerResult->getProductData()->getPrice(),
            $productCheckerResult->getProductData()->getStockLevel(),
            $productCheckerResult->getProductData()->getSeller()
        );
    }
}
