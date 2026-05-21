<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankSetting extends Model
{
    /**
     * Bank settings are global — always read from HQ.
     * Connection is dynamically resolved based on driver.
     */
    protected $connection;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        
        $this->connection = \App\Services\DistributedDatabaseService::getHqConnection();
    }

    protected $fillable = ['key', 'value', 'description'];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, $value, ?string $description = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            array_filter([
                'value' => (string) $value,
                'description' => $description,
            ])
        );
    }

    /**
     * Get the loan reserve minimum.
     */
    public static function loanReserveMinimum(): float
    {
        return (float) static::get('loan_reserve_minimum', 50000);
    }

    /**
     * Get the transfer expiry hours.
     */
    public static function transferExpiryHours(): int
    {
        return (int) static::get('transfer_expiry_hours', 48);
    }

    /**
     * Get max PIN attempts before card freeze.
     */
    public static function maxPinAttempts(): int
    {
        return (int) static::get('max_pin_attempts', 3);
    }

    /**
     * Check if bank reserves are healthy (above minimum).
     */
    public static function areReservesHealthy(): bool
    {
        // Query HQ for global view of all deposits
        $totalDeposits = \Illuminate\Support\Facades\DB::connection(\App\Services\DistributedDatabaseService::getHqConnection())
            ->table('accounts')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->sum('balance');
        return $totalDeposits >= static::loanReserveMinimum();
    }

    /**
     * Get current reserve health data.
     */
    public static function reserveHealth(): array
    {
        // Query HQ for global view of all deposits
        $totalDeposits = \Illuminate\Support\Facades\DB::connection(\App\Services\DistributedDatabaseService::getHqConnection())
            ->table('accounts')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->sum('balance');
        $minimum = static::loanReserveMinimum();
        $ratio = $minimum > 0 ? ($totalDeposits / $minimum) * 100 : 100;

        return [
            'total_deposits' => $totalDeposits,
            'minimum' => $minimum,
            'ratio' => round($ratio, 1),
            'healthy' => $totalDeposits >= $minimum,
            'status' => $ratio >= 150 ? 'excellent' : ($ratio >= 100 ? 'good' : ($ratio >= 75 ? 'warning' : 'critical')),
        ];
    }
}
