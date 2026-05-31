<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_hq_syncs', function (Blueprint $table) {
            $table->id();
            $table->string('table');
            $table->unsignedBigInteger('record_id');
            $table->enum('action', ['insert', 'update', 'delete']);
            $table->longText('data');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'dead_letter'])->default('pending');
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->unsignedTinyInteger('max_retries')->default(5);
            $table->timestamp('last_attempted_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['status', 'created_at']);
            $table->index(['table', 'record_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_hq_syncs');
    }
};
