<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('beneficiary_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('frequency', ['once', 'daily', 'weekly', 'biweekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_execution_date');
            $table->date('last_executed_at')->nullable();
            $table->integer('execution_count')->default(0);
            $table->integer('max_executions')->nullable();
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled', 'failed'])->default('active');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index('next_execution_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_payments');
    }
};
