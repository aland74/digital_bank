<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Transactions: most queried table
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['account_id', 'created_at'], 'idx_transactions_account_created');
            $table->index('created_at', 'idx_transactions_created');
        });

        // Notifications: user dashboard queries
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'is_read'], 'idx_notifications_user_read');
        });

        // Pending transfers: expiry cleanup and status queries
        Schema::table('pending_transfers', function (Blueprint $table) {
            $table->index(['status', 'expires_at'], 'idx_pending_transfers_status_expires');
        });

        // Audit logs: admin queries
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('created_at', 'idx_audit_logs_created');
        });

        // Accounts: user dashboard
        Schema::table('accounts', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'idx_accounts_user_status');
        });

        // Cards: user queries
        Schema::table('cards', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'idx_cards_user_status');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_account_created');
            $table->dropIndex('idx_transactions_created');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_user_read');
        });

        Schema::table('pending_transfers', function (Blueprint $table) {
            $table->dropIndex('idx_pending_transfers_status_expires');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_logs_created');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex('idx_accounts_user_status');
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->dropIndex('idx_cards_user_status');
        });
    }
};
