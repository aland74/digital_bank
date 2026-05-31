<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExchangeRateHistorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('exchange_rate_history')->insert([
            'from_currency' => 'USD',
            'to_currency' => 'IQD',
            'rate' => 1310.00000000,
            'previous_rate' => null,
            'source' => 'seed',
            'changed_by' => 'system',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
