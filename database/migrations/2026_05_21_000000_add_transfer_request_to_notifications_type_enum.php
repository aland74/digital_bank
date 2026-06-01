<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Services\DistributedDatabaseService;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite doesn't enforce enums, so only alter for MySQL
        if (DistributedDatabaseService::getDriver() === 'mysql') {
            $connection = Schema::getConnection()->getName();
            \Illuminate\Support\Facades\DB::connection($connection)->statement(
                "ALTER TABLE notifications MODIFY COLUMN type ENUM('info', 'success', 'warning', 'danger', 'transaction', 'security', 'promotion', 'system', 'transfer_request') NOT NULL"
            );
        }
        // SQLite: no action needed — enum is stored as plain text and not enforced
    }

    public function down(): void
    {
        if (DistributedDatabaseService::getDriver() === 'mysql') {
            $connection = Schema::getConnection()->getName();
            \Illuminate\Support\Facades\DB::connection($connection)->statement(
                "ALTER TABLE notifications MODIFY COLUMN type ENUM('info', 'success', 'warning', 'danger', 'transaction', 'security', 'promotion', 'system') NOT NULL"
            );
        }
    }
};
