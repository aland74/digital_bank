<?php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    // Cache key for the current rate
    private const CACHE_KEY = 'iqd_usd_exchange_rate';
    private const CACHE_TTL = 3600; // 1 hour

    // Realistic base rate (approximate market rate)
    private const BASE_RATE = 1310.00;

    // Max fluctuation per update (0.3%)
    private const MAX_FLUCTUATION = 0.003;

    /**
     * Get the current IQD/USD exchange rate.
     * IQD per 1 USD (e.g., 1310 means 1 USD = 1310 IQD)
     */
    public static function getRate(): float
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::fetchLiveRate();
        });
    }

    /**
     * Get the inverse rate (USD per 1 IQD).
     */
    public static function getInverseRate(): float
    {
        return 1 / self::getRate();
    }

    /**
     * Convert USD to IQD.
     */
    public static function usdToIqd(float $amount): float
    {
        return round($amount * self::getRate(), 0);
    }

    /**
     * Convert IQD to USD.
     */
    public static function iqdToUsd(float $amount): float
    {
        return round($amount / self::getRate(), 2);
    }

    /**
     * Convert between currencies.
     */
    public static function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to) {
            return $amount;
        }

        if ($from === 'USD' && $to === 'IQD') {
            return self::usdToIqd($amount);
        }

        if ($from === 'IQD' && $to === 'USD') {
            return self::iqdToUsd($amount);
        }

        throw new \InvalidArgumentException("Unsupported currency pair: {$from}/{$to}");
    }

    /**
     * Fetch live rate from API with fallback to simulated dynamic rate.
     */
    private static function fetchLiveRate(): float
    {
        // Try fetching from a free exchange rate API
        try {
            $response = Http::timeout(5)->get('https://open.er-api.com/v6/latest/USD');

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['rates']['IQD'])) {
                    $rate = (float) $data['rates']['IQD'];

                    // Update the currencies table
                    self::updateCurrencyTable($rate);

                    Log::info("Exchange rate updated from API: 1 USD = {$rate} IQD");
                    return $rate;
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch exchange rate from API: {$e->getMessage()}");
        }

        // Fallback: simulate dynamic rate based on stored rate + small fluctuation
        return self::simulateDynamicRate();
    }

    /**
     * Simulate a dynamic rate with realistic small fluctuations.
     */
    private static function simulateDynamicRate(): float
    {
        $storedRate = Currency::where('code', 'IQD')->value('exchange_rate') ?? self::BASE_RATE;

        // Add small random fluctuation (-0.3% to +0.3%)
        $fluctuation = (mt_rand(-100, 100) / 100) * self::MAX_FLUCTUATION;
        $newRate = round($storedRate * (1 + $fluctuation), 2);

        // Keep within realistic bounds (1280 - 1340)
        $newRate = max(1280.00, min(1340.00, $newRate));

        // Update the currencies table
        self::updateCurrencyTable($newRate, 'simulation');

        Log::info("Exchange rate simulated: 1 USD = {$newRate} IQD");
        return $newRate;
    }

    /**
     * Update the IQD exchange rate in the currencies table and log to history.
     */
    private static function updateCurrencyTable(float $rate, string $source = 'api'): void
    {
        $previousRate = Currency::where('code', 'IQD')->value('exchange_rate');

        Currency::where('code', 'IQD')->update(['exchange_rate' => $rate]);

        // Log rate change to audit trail
        try {
            DB::connection(DistributedDatabaseService::getHqConnection())
                ->table('exchange_rate_history')
                ->insert([
                    'from_currency' => 'USD',
                    'to_currency' => 'IQD',
                    'rate' => $rate,
                    'previous_rate' => $previousRate,
                    'source' => $source,
                    'changed_by' => 'system',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        } catch (\Exception $e) {
            Log::warning("Failed to log exchange rate history: " . $e->getMessage());
        }

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Force refresh the exchange rate (for manual admin use).
     */
    public static function refreshRate(): float
    {
        Cache::forget(self::CACHE_KEY);
        return self::getRate();
    }

    /**
     * Get rate info for display.
     */
    public static function getRateInfo(): array
    {
        $rate = self::getRate();

        return [
            'rate' => $rate,
            'inverse' => round(1 / $rate, 6),
            'formatted' => "1 USD = " . number_format($rate, 0) . " IQD",
            'inverse_formatted' => "1 IQD = $" . number_format(1 / $rate, 6) . " USD",
            'updated_at' => now()->toDateTimeString(),
        ];
    }
}
