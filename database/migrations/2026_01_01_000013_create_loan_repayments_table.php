<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->onDelete('cascade');
            $table->integer('installment_number');
            $table->decimal('amount', 18, 2);
            $table->decimal('principal', 18, 2);
            $table->decimal('interest', 18, 2);
            $table->decimal('penalty', 18, 2)->default(0.00);
            $table->decimal('remaining_balance', 18, 2);
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->enum('status', ['upcoming', 'due', 'paid', 'overdue', 'partial'])->default('upcoming');
            $table->string('transaction_reference')->nullable();
            $table->timestamps();

            $table->index('due_date');
            $table->index('status');
            $table->index(['loan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
    }
};
