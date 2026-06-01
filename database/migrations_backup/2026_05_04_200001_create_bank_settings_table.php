<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed default settings
        DB::table('bank_settings')->insert([
            [
                'key' => 'loan_reserve_minimum',
                'value' => '50000',
                'description' => 'Minimum bank reserve balance. Loan applications are auto-rejected when total deposits fall below this amount.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'transfer_expiry_hours',
                'value' => '48',
                'description' => 'Hours before a pending transfer auto-expires if not accepted.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'max_pin_attempts',
                'value' => '3',
                'description' => 'Maximum wrong PIN attempts before card is frozen.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_settings');
    }
};
