<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('nickname')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number', 34);
            $table->string('routing_number', 20)->nullable();
            $table->string('swift_code', 11)->nullable();
            $table->string('iban', 34)->nullable();
            $table->enum('type', ['internal', 'domestic', 'international'])->default('internal');
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_favorite']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
