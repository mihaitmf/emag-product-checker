<?php

namespace ProductChecker\EmagProductChecker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EmagProductListCheckerCommand extends Command
{
    private const PRODUCTS_LIST = [
        [
//            "Roborock S5 Max",
//            1800,
//            "https://www.emag.ro/robot-de-aspirare-roborock-cleaner-s5-max-wifi-aspirator-si-mop-smart-top-up-navigare-lidar-setare-bariere-virtuale-zone-no-mop-alb-s5e02-00-white/pd/D888WWBBM/",
        ],
    ];

    private EmagProductCheckerCommand $productCheckerCommand;

    public function __construct(EmagProductCheckerCommand $productCheckerCommand)
    {
        parent::__construct();
        $this->productCheckerCommand = $productCheckerCommand;
    }

    protected function configure(): void
    {
        $commandName = 'check-list';
        $this->setName($commandName)
            ->setDescription(
                'Check a list of Emag products according to some constraints and send push notifications for those that are available'
            )
            ->setHelp(
                "Check a list of Emag products according to some constraints and send push notifications for those that are available\r\n"
                . "Example command: php <script-name>.php $commandName"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach (self::PRODUCTS_LIST as $index => $productParams) {
            $this->addWait($output);

            $productShortName = $productParams[0];
            $output->writeln("Checking product $productShortName...");

            $input->bind($this->productCheckerCommand->getInputDefinition());
            $input->setArgument(EmagProductCheckerCommand::INPUT_PRODUCT_SHORT_NAME, $productShortName);
            $input->setArgument(EmagProductCheckerCommand::INPUT_PRODUCT_MAX_PRICE, $productParams[1]);
            $input->setArgument(EmagProductCheckerCommand::INPUT_PRODUCT_URL, $productParams[2]);

            $this->productCheckerCommand->execute($input, $output);
        }

        return Command::SUCCESS;
    }

    protected function addWait(OutputInterface $output): void
    {
        $waitSeconds = random_int(0, 600); // wait a random time between requests to trick Emag and simulate a human behaviour
        $output->writeln("\nWait for $waitSeconds seconds");
        sleep($waitSeconds);
    }
}
