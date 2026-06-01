<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('document_type', ['passport', 'national_id', 'drivers_license', 'utility_bill', 'bank_statement', 'tax_return']);
            $table->string('document_number', 50)->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type', 50);
            $table->integer('file_size');
            $table->enum('status', ['pending', 'under_review', 'verified', 'rejected', 'expired'])->default('pending');
            $connection = Schema::getConnection()->getName();
            $isHq = str_contains($connection, 'hq');

            if ($isHq) {
                $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            } else {
                $table->unsignedBigInteger('verified_by')->nullable();
            }
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('document_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_documents');
    }
};
