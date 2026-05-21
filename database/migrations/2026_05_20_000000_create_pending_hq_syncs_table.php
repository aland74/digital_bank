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
        Schema::create('pending_hq_syncs', function (Blueprint $table) {
            $table->id();
            $table->string('table');
            $table->unsignedBigInteger('record_id');
            $table->string('action'); // 'insert', 'update', 'delete'
            $table->longText('data'); // JSON payload of the record/changes
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_hq_syncs');
    }
};
