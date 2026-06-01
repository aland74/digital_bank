<?php

namespace App\Models;

use App\Traits\SyncsWithHQ;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class Card extends Model
{
    use HasFactory, SoftDeletes, SyncsWithHQ;

    /**
     * Enforce KYC verification before any card is written as 'active'.
     * This is an application-layer guard that sits above the DB trigger.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Card $card) {
            if ($card->status === 'active') {
                $user = \App\Models\User::find($card->user_id);
                if ($user && !$user->isKycVerified()) {
                    throw ValidationException::withMessages([
                        'kyc' => 'KYC_REQUIRED: You must have a verified Passport and National ID before creating an active card.',
                    ]);
                }
            }
        });

        static::updating(function (Card $card) {
            if ($card->isDirty('status') && $card->status === 'active') {
                $user = \App\Models\User::find($card->user_id);
                if ($user && !$user->isKycVerified()) {
                    throw ValidationException::withMessages([
                        'kyc' => 'KYC_REQUIRED: You must have a verified Passport and National ID before activating a card.',
                    ]);
                }
            }
        });
    }


    protected $fillable = [
        'account_id', 'user_id', 'card_number_last4', 'card_number_encrypted',
        'card_type', 'card_brand', 'cardholder_name', 'expiry_month', 'expiry_year',
        'cvv_encrypted', 'pin_hash', 'status', 'daily_limit', 'monthly_limit',
        'daily_spent', 'monthly_spent', 'is_contactless', 'is_online_enabled',
        'is_international_enabled', 'activated_at', 'last_used_at', 'pin_attempts',
    ];

    protected $hidden = [
        'card_number_encrypted', 'cvv_encrypted', 'pin_hash',
    ];

    protected function casts(): array
    {
        return [
            'daily_limit' => 'decimal:2',
            'monthly_limit' => 'decimal:2',
            'daily_spent' => 'decimal:2',
            'monthly_spent' => 'decimal:2',
            'is_contactless' => 'boolean',
            'is_online_enabled' => 'boolean',
            'is_international_enabled' => 'boolean',
            'activated_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isFrozen(): bool
    {
        return $this->status === 'frozen';
    }

    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    public function isPendingActivation(): bool
    {
        return $this->status === 'inactive' && !$this->activated_at;
    }

    public function isExpired(): bool
    {
        $expiry = \Carbon\Carbon::createFromFormat('Y-m', $this->expiry_year . '-' . $this->expiry_month)->endOfMonth();
        return $expiry->isPast();
    }

    // ── PIN Management ─────────────────────────────────────────

    /**
     * Generate a random 4-digit PIN, store its hash, return the plain PIN.
     */
    public static function generatePin(): array
    {
        $pin = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        return [
            'plain' => $pin,
            'hash' => Hash::make($pin),
        ];
    }

    /**
     * Verify a PIN against the stored hash.
     */
    public function verifyPin(string $pin): bool
    {
        if (!$this->pin_hash) {
            return false;
        }
        return Hash::check($pin, $this->pin_hash);
    }

    /**
     * Change the card PIN. Returns the new plain PIN.
     */
    public function changePin(string $newPin): void
    {
        $this->update([
            'pin_hash' => Hash::make($newPin),
            'pin_attempts' => 0,
        ]);
    }

    /**
     * Record a failed PIN attempt. Freeze card if max attempts exceeded.
     */
    public function recordFailedPinAttempt(): bool
    {
        $attempts = ($this->pin_attempts ?? 0) + 1;
        $maxAttempts = BankSetting::maxPinAttempts();

        $update = ['pin_attempts' => $attempts];

        if ($attempts >= $maxAttempts) {
            $update['status'] = 'frozen';
            $this->update($update);

            // Create notification for user
            Notification::create([
                'user_id' => $this->user_id,
                'title' => 'Card Frozen — Security Alert',
                'message' => "Your card ending in {$this->card_number_last4} has been frozen due to {$maxAttempts} incorrect PIN attempts. Please contact support.",
                'type' => 'security',
                'icon' => '🔒',
                'data' => ['card_id' => $this->id, 'reason' => 'max_pin_attempts'],
            ]);

            return true; // Card was frozen
        }

        $this->update($update);
        return false; // Card not frozen
    }

    // ── Card Number Generation ─────────────────────────────────

    /**
     * Generate a realistic 16-digit card number using the Luhn algorithm.
     */
    public static function generateCardNumber(string $brand = 'visa'): string
    {
        // IIN/BIN prefixes
        $prefix = match($brand) {
            'visa' => '4' . random_int(100, 999),
            'mastercard' => '5' . random_int(1, 5) . str_pad(random_int(0, 99), 2, '0', STR_PAD_LEFT),
            'amex' => '3' . (random_int(0, 1) ? '4' : '7') . str_pad(random_int(0, 99), 2, '0', STR_PAD_LEFT),
            default => '4' . random_int(100, 999),
        };

        $length = $brand === 'amex' ? 15 : 16;
        $numberWithoutCheck = $prefix;

        // Fill remaining digits (minus check digit)
        while (strlen($numberWithoutCheck) < $length - 1) {
            $numberWithoutCheck .= random_int(0, 9);
        }

        // Calculate Luhn check digit
        $checkDigit = self::luhnCheckDigit($numberWithoutCheck);
        return $numberWithoutCheck . $checkDigit;
    }

    /**
     * Calculate the Luhn check digit for a number string.
     */
    private static function luhnCheckDigit(string $number): int
    {
        $digits = str_split(strrev($number));
        $sum = 0;
        foreach ($digits as $i => $digit) {
            $d = (int) $digit;
            if ($i % 2 === 0) {
                $d *= 2;
                if ($d > 9) $d -= 9;
            }
            $sum += $d;
        }
        return (10 - ($sum % 10)) % 10;
    }

    /**
     * Generate a 3-digit CVV (4 for Amex).
     */
    public static function generateCvv(string $brand = 'visa'): string
    {
        $length = $brand === 'amex' ? 4 : 3;
        return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    // ── Accessors ──────────────────────────────────────────────

    public function getMaskedNumberAttribute(): string
    {
        return '**** **** **** ' . $this->card_number_last4;
    }

    public function getExpiryDateAttribute(): string
    {
        return $this->expiry_month . '/' . substr($this->expiry_year, -2);
    }

    public function getDecryptedCardNumberAttribute(): string
    {
        try {
            return Crypt::decryptString($this->card_number_encrypted);
        } catch (\Exception $e) {
            return '****' . $this->card_number_last4;
        }
    }

    public function getBrandIconAttribute(): string
    {
        return match($this->card_brand) {
            'visa' => '💳',
            'mastercard' => '💳',
            'amex' => '💳',
            default => '💳',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => 'Active',
            'inactive' => $this->activated_at ? 'Inactive' : 'Pending Activation',
            'frozen' => 'Frozen',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
            'lost' => 'Reported Lost',
            'stolen' => 'Reported Stolen',
            default => ucfirst($this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'active' => 'badge-success',
            'inactive' => 'badge-warning',
            'frozen' => 'badge-info',
            default => 'badge-danger',
        };
    }
}
