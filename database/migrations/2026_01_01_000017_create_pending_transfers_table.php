<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 32)->unique();
            $connection = Schema::getConnection()->getName();
            $isHq = str_contains($connection, 'hq');

            if ($isHq) {
                $table->foreignId('sender_account_id')->constrained('accounts')->onDelete('cascade');
                $table->foreignId('receiver_account_id')->constrained('accounts')->onDelete('cascade');
                $table->foreignId('sender_user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('receiver_user_id')->constrained('users')->onDelete('cascade');
            } else {
                $table->unsignedBigInteger('sender_account_id');
                $table->unsignedBigInteger('receiver_account_id');
                $table->unsignedBigInteger('sender_user_id');
                $table->unsignedBigInteger('receiver_user_id');
            }
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 18, 8)->nullable();
            $table->string('description')->nullable();
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired', 'cancelled'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['receiver_user_id', 'status']);
            $table->index(['sender_user_id', 'status']);
            $table->index('expires_at');
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_transfers');
    }
};
