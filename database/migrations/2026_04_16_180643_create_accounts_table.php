<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('account_number', 20)->unique();
            $table->string('account_name')->nullable();
            $table->enum('account_type', ['savings', 'checking', 'business', 'fixed_deposit'])->default('savings');
            $table->string('currency', 3)->default('USD');
            $table->decimal('balance', 18, 2)->default(0.00);
            $table->decimal('available_balance', 18, 2)->default(0.00);
            $table->decimal('hold_amount', 18, 2)->default(0.00);
            $table->enum('status', ['active', 'inactive', 'frozen', 'closed'])->default('active');
            $table->boolean('is_primary')->default(false);
            $table->decimal('daily_transfer_limit', 18, 2)->default(10000.00);
            $table->decimal('monthly_transfer_limit', 18, 2)->default(50000.00);
            $table->decimal('interest_rate', 5, 4)->default(0.0000);
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('account_type');
            $table->index('status');
            $table->index('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
