<?php

namespace ProductChecker\EmagProductChecker\Command;

use Notifier\PushNotification\PushNotificationService;
use ProductChecker\EmagProductChecker\EmagProductChecker;
use ProductChecker\EmagProductChecker\EmagProductCheckerException;
use ProductChecker\EmagProductChecker\EmagProductCheckerResult;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EmagProductCheckerCommand extends Command
{
    private const INPUT_PRODUCT_SHORT_NAME = 'productShortName';
    private const INPUT_PRODUCT_MAX_PRICE = 'productMaxPrice';
    private const INPUT_PRODUCT_URL = 'productURL';

    private const MESSAGE_PRODUCT_AVAILABLE = 'Emag product available: %s! Price: %s. Stock: %s. Seller: %s.';
    private const MESSAGE_PRODUCT_UNAVAILABLE = 'Emag product not available yet: %s! Price: %s. Stock: %s. Seller: %s.';

    private EmagProductChecker $productChecker;
    private PushNotificationService $pushNotificationService;

    public function __construct(
        EmagProductChecker $productChecker,
        PushNotificationService $pushNotificationService
    ) {
        parent::__construct();
        $this->productChecker = $productChecker;
        $this->pushNotificationService = $pushNotificationService;
    }

    protected function configure(): void
    {
        $commandName = 'check:single';
        $this->setName($commandName)
            ->setDescription(
                'Check a single Emag product according to some constraints and send push notification if it is available'
            )
            ->setHelp(
                "Check a single Emag product according to some constraints and send push notification if it is available\r\n"
                . "Example command: php <script-name>.php $commandName " . '"<productShortName>" "<productMaxPrice>" "<productUrl>"'
            )
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputArgument(
                            self::INPUT_PRODUCT_SHORT_NAME,
                            InputArgument::REQUIRED,
                            'A short name to identify the product that will appear in the notification message. If it contains spaces, make sure to enclose it in double quotes: "<productShortName>"',
                        ),
                        new InputArgument(
                            self::INPUT_PRODUCT_MAX_PRICE,
                            InputArgument::REQUIRED,
                            'Integer value used as constraint for the maximum product price',
                        ),
                        new InputArgument(
                            self::INPUT_PRODUCT_URL,
                            InputArgument::REQUIRED,
                            'The URL of the product page from Emag',
                        ),
                    ]
                )
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $productShortName = $input->getArgument(self::INPUT_PRODUCT_SHORT_NAME);
        $productMaxPrice = (int)$input->getArgument(self::INPUT_PRODUCT_MAX_PRICE);
        $productUrl = $input->getArgument(self::INPUT_PRODUCT_URL);

        try {
            $productCheckerResult = $this->productChecker->checkProduct($productUrl, $productMaxPrice);
        } catch (EmagProductCheckerException $exception) {
            $errorMessage = sprintf('ERROR checking product %s! %s', $productShortName, $exception->getMessage());
            $output->writeln($errorMessage);

            $this->sendNotificationAndPrint($errorMessage, $productUrl, $output);
            return Command::FAILURE;
        }

        if ($productCheckerResult->isAvailable()) {
            $successMessage = $this->getProductMessage(
                self::MESSAGE_PRODUCT_AVAILABLE,
                $productShortName,
                $productCheckerResult
            );
            $output->writeln($successMessage);

            $this->sendNotificationAndPrint($successMessage, $productUrl, $output);
            return Command::SUCCESS;
        }

        // do not send notification when product is unavailable and no error occurred
        $output->writeln(
            $this->getProductMessage(
                self::MESSAGE_PRODUCT_UNAVAILABLE,
                $productShortName,
                $productCheckerResult
            )
        );
        return Command::SUCCESS;
    }

    private function sendNotificationAndPrint(string $message, string $productUrl, OutputInterface $output): void
    {
        $notificationResponse = $this->pushNotificationService->notify($message, $productUrl);
        if ($notificationResponse->isSuccessful()) {
            $output->writeln('Push notification sent!');
        } else {
            $output->writeln(sprintf('ERROR sending push notification! %s', $notificationResponse->getError()));
        }
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
