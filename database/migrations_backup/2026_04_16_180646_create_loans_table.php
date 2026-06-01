<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->string('loan_number', 20)->unique();
            $table->enum('loan_type', ['personal', 'home', 'auto', 'business', 'education'])->default('personal');
            $table->decimal('amount', 18, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->integer('term_months');
            $table->decimal('monthly_payment', 18, 2);
            $table->decimal('total_interest', 18, 2)->default(0.00);
            $table->decimal('total_paid', 18, 2)->default(0.00);
            $table->decimal('remaining_balance', 18, 2);
            $table->enum('status', ['pending', 'approved', 'active', 'rejected', 'completed', 'defaulted', 'cancelled'])->default('pending');
            $table->text('purpose')->nullable();
            $table->decimal('collateral_value', 18, 2)->nullable();
            $table->string('collateral_description')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamp('maturity_date')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('loan_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
