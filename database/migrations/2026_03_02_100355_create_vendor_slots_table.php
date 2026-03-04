<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * vendor_slots 
 *
 * Ogni vendor gestisce i propri slot.
 * Default: se il vendor non crea slot personalizzati,
 * il sistema userà "full-day" (intera giornata).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_slots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vendor_account_id')
                ->constrained('vendor_accounts')
                ->cascadeOnDelete();

            // Identificatore univoco per vendor (es. "mattina", "sera", "full-day")
            $table->string('slug', 80);

            // Label visibile al cliente nel modulo PS
            $table->string('label', 120);

            // Orari opzionali: null = intera giornata 
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Slug univoco per vendor
            $table->unique(['vendor_account_id', 'slug']);
            $table->index(['vendor_account_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_slots');
    }
};