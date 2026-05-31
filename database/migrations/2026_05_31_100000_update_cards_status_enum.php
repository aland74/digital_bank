<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE cards MODIFY COLUMN status ENUM('active', 'inactive', 'frozen', 'expired', 'cancelled', 'lost', 'stolen', 'pending_approval', 'rejected') NOT NULL DEFAULT 'active'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE cards MODIFY COLUMN status ENUM('active', 'inactive', 'frozen', 'expired', 'cancelled', 'lost', 'stolen') NOT NULL DEFAULT 'active'");
        }
    }
};
