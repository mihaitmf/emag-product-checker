<?php

namespace ProductChecker\SummerWellProductChecker\Command;

use Notifier\PushNotification\PushNotificationService;
use ProductChecker\SummerWellProductChecker\SummerWellProductChecker;
use ProductChecker\SummerWellProductChecker\SummerWellProductCheckerException;
use ProductChecker\SummerWellProductChecker\SummerWellProductCheckerResult;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SummerWellProductCheckerCommand extends Command
{
    private const PRODUCT_URL = 'https://summerwell.ro/orange#tickets';

    private const MESSAGE_PRODUCT_AVAILABLE = 'SummerWell ticket available! Price: %s lei. Stock: %s.';
    private const MESSAGE_PRODUCT_UNAVAILABLE = 'SummerWell ticket not available! Price: %s lei. Stock: %s.';

    private SummerWellProductChecker $productChecker;
    private PushNotificationService $pushNotificationService;

    public function __construct(
        SummerWellProductChecker $productChecker,
        PushNotificationService $pushNotificationService
    ) {
        parent::__construct();
        $this->productChecker = $productChecker;
        $this->pushNotificationService = $pushNotificationService;
    }

    protected function configure(): void
    {
        $commandName = 'check-summerwell';
        $this->setName($commandName)
            ->setDescription(
                'Check a SummerWell ticket and send push notification if it is available'
            )
            ->setHelp(
                "Check a SummerWell ticket and send push notification if it is available\r\n"
                . "Example command: php <script-name>.php $commandName "
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $productUrl = self::PRODUCT_URL;
        try {
            $productCheckerResult = $this->productChecker->checkProduct($productUrl);
        } catch (SummerWellProductCheckerException $exception) {
            $errorMessage = sprintf('ERROR checking SummerWell ticket! %s', $exception->getMessage());
            $this->writeLogLine($output, $errorMessage);

            $this->sendNotificationAndPrint($errorMessage, $productUrl, $output);

            return Command::FAILURE;
        }

        if ($productCheckerResult->isAvailable()) {
            $successMessage = $this->getProductMessage(
                self::MESSAGE_PRODUCT_AVAILABLE,
                $productCheckerResult,
            );
            $this->writeLogLine($output, $successMessage);

            $this->sendNotificationAndPrint($successMessage, $productUrl, $output);

            return Command::SUCCESS;
        }

        // do not send notification when product is unavailable and no error occurred
        $this->writeLogLine(
            $output,
            $this->getProductMessage(
                self::MESSAGE_PRODUCT_UNAVAILABLE,
                $productCheckerResult,
            ),
        );

        return Command::SUCCESS;
    }

    private function sendNotificationAndPrint(string $message, string $productUrl, OutputInterface $output): void
    {
        $notificationResponse = $this->pushNotificationService->notify($message, $productUrl);
        if ($notificationResponse->isSuccessful()) {
            $this->writeLogLine($output, 'Push notification sent!');
        } else {
            $this->writeLogLine($output, sprintf('ERROR sending push notification! %s', $notificationResponse->getError()));
        }
    }

    private function getProductMessage(
        string $format,
        SummerWellProductCheckerResult $productCheckerResult,
    ): string {
        return sprintf(
            $format,
            $productCheckerResult->getProductData()->getPrice(),
            $productCheckerResult->getProductData()->getStockLevel(),
        );
    }

    private function writeLogLine(OutputInterface $output, string $message): void
    {
        $output->writeln(sprintf(
            "[%s] %s",
            date('Y-m-d H:i:s'),
            $message,
        ));
    }
}
