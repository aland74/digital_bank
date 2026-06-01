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
        Schema::table('pending_transfers', function (Blueprint $table) {
            $table->decimal('exchange_rate', 18, 8)->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('pending_transfers', function (Blueprint $table) {
            $table->dropColumn('exchange_rate');
        });
    }
};
