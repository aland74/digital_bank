<?php

namespace App\Console\Commands;

use App\Services\ExchangeRateService;
use Illuminate\Console\Command;

class UpdateExchangeRates extends Command
{
    protected $signature = 'rates:update';
    protected $description = 'Update IQD/USD exchange rate from live API or simulate dynamic change';

    public function handle(): int
    {
        $this->info('Updating IQD/USD exchange rate...');

        $rateInfo = ExchangeRateService::getRateInfo();

        $this->info("Current rate: {$rateInfo['formatted']}");
        $this->info("Inverse: {$rateInfo['inverse_formatted']}");
        $this->info("Updated at: {$rateInfo['updated_at']}");

        return self::SUCCESS;
    }
}
