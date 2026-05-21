<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds a 'branch' column to the users table to track which city
     * database the user's data is stored in.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('branch', ['erbil', 'sulaimaniyah', 'duhok'])
                  ->default('erbil')
                  ->after('country')
                  ->comment('The branch/city database where this user\'s data is stored');
            $table->index('branch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['branch']);
            $table->dropColumn('branch');
        });
    }
};
