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
        Schema::create('vendor_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vendor_account_id')
                ->constrained('vendor_accounts')
                ->cascadeOnDelete();

            $table->string('type', 80);
            $table->string('title')->nullable();

            $table->string('original_filename');
            $table->string('path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();

            $table->string('status', 30)->default('PENDING');

            $table->timestamp('expires_at')->nullable();

            $table->foreignId('uploaded_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('reviewed_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['vendor_account_id', 'type']);
            $table->index(['vendor_account_id', 'status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_documents');
    }
};
