<?php

namespace App\Command;

use App\Service\CBRService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-rates',
    description: 'Updates exchange rates from CBR',
)]
class UpdateExchangeRatesCommand extends Command
{
    public function __construct(
        private CBRService $cbrService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $currencies = ['USD', 'EUR', 'KRW', 'CNY']; // Список валют для обновления
            $this->cbrService->updateExchangeRates($currencies);

            $io->success('Exchange rates have been successfully updated.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error updating exchange rates: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}