<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->string('reference_number', 32)->unique();
            $table->string('biller_name');
            $table->enum('biller_category', [
                'electricity', 'water', 'gas', 'internet', 'phone',
                'insurance', 'tax', 'education', 'other',
            ])->default('other');
            $table->string('bill_number', 50);
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('transaction_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('biller_category');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_payments');
    }
};
