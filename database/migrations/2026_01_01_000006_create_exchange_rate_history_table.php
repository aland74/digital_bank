<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rate_history', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 3)->default('USD');
            $table->string('to_currency', 3)->default('IQD');
            $table->decimal('rate', 18, 8);
            $table->decimal('previous_rate', 18, 8)->nullable();
            $table->string('source', 50)->default('api');
            $table->string('changed_by')->nullable();
            $table->timestamps();

            $table->index(['from_currency', 'to_currency', 'created_at']);
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rate_history');
    }
};
