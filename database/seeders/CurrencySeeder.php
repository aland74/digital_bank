<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1.00000000, 'is_default' => true, 'decimal_places' => 2],
            ['code' => 'IQD', 'name' => 'Iraqi Dinar', 'symbol' => 'د.ع', 'exchange_rate' => 1310.00000000, 'decimal_places' => 0],
        ];

        foreach ($currencies as $currency) {
            Currency::create(array_merge([
                'is_active' => true,
                'is_default' => false,
                'decimal_places' => 2,
            ], $currency));
        }
    }
}
