<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->string('reference_number', 32)->unique();
            $table->enum('type', [
                'deposit', 'withdrawal', 'transfer_in', 'transfer_out',
                'payment', 'refund', 'fee', 'interest',
                'loan_disbursement', 'loan_repayment',
            ]);
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('balance_before', 18, 2);
            $table->decimal('balance_after', 18, 2);
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled', 'reversed'])->default('pending');
            $table->string('description')->nullable();
            $table->foreignId('recipient_account_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_bank')->nullable();
            $table->string('recipient_account_number')->nullable();
            $table->enum('channel', ['web', 'mobile', 'api', 'atm', 'branch', 'system'])->default('web');
            $table->string('ip_address', 45)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('status');
            $table->index('created_at');
            $table->index(['account_id', 'created_at']);
            $table->index(['type', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
