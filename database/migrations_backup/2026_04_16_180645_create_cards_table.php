<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('card_number_last4', 4);
            $table->text('card_number_encrypted');
            $table->enum('card_type', ['debit', 'credit', 'virtual', 'prepaid'])->default('debit');
            $table->enum('card_brand', ['visa', 'mastercard', 'amex'])->default('visa');
            $table->string('cardholder_name');
            $table->string('expiry_month', 2);
            $table->string('expiry_year', 4);
            $table->text('cvv_encrypted');
            $table->string('pin_hash')->nullable();
            $table->enum('status', ['active', 'inactive', 'frozen', 'expired', 'cancelled', 'lost', 'stolen'])->default('active');
            $table->decimal('daily_limit', 18, 2)->default(5000.00);
            $table->decimal('monthly_limit', 18, 2)->default(25000.00);
            $table->decimal('daily_spent', 18, 2)->default(0.00);
            $table->decimal('monthly_spent', 18, 2)->default(0.00);
            $table->boolean('is_contactless')->default(true);
            $table->boolean('is_online_enabled')->default(true);
            $table->boolean('is_international_enabled')->default(false);
            $table->timestamp('activated_at')->nullable();
            $table->integer('pin_attempts')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('card_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
