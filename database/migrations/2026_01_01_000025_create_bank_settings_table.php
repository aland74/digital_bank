<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            ['key' => 'loan_reserve_minimum', 'value' => '50000', 'description' => 'Minimum loan reserve amount', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'transfer_expiry_hours', 'value' => '48', 'description' => 'Hours before pending transfers expire', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'max_pin_attempts', 'value' => '3', 'description' => 'Maximum PIN attempts before card freeze', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_settings');
    }
};
