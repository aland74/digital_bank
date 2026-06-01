<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone', 20)->nullable()->unique();
            $table->string('national_id', 50)->nullable()->unique();
            $table->date('date_of_birth')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->default('IQ');
            $table->string('postal_code', 20)->nullable();
            $table->enum('branch', ['erbil', 'sulaimaniyah', 'duhok'])->default('erbil');
            $table->enum('role', ['customer', 'admin', 'super_admin'])->default('customer');
            $table->enum('status', ['active', 'inactive', 'suspended', 'frozen', 'pending_verification'])->default('pending_verification');
            $table->string('avatar')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('phone');
            $table->index('national_id');
            $table->index('role');
            $table->index('status');
            $table->index('branch');
            $table->index(['branch', 'role']);
            $table->index('failed_login_attempts');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
