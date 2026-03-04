<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * vendor_blackouts 
 *
 * Tipi di blackout supportati:
 * - Giorno intero: date_from = date_to, slot_id = null
 * - Slot singolo: date_from = date_to, slot_id = valorizzato
 * - Intervallo date: date_from != date_to, slot_id = null o valorizzato
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_blackouts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vendor_account_id')
                ->constrained('vendor_accounts')
                ->cascadeOnDelete();

            // Intervallo date (giorno singolo = date_from == date_to)
            $table->date('date_from');
            $table->date('date_to');

            // Slot specifico bloccato — null = tutti gli slot del giorno
            $table->foreignId('vendor_slot_id')
                ->nullable()
                ->constrained('vendor_slots')
                ->nullOnDelete();

            // Motivo interno (solo admin/vendor lo vede)
            $table->string('reason_internal')->nullable();

            // Motivo pubblico (mostrato al cliente su PrestaShop)
            $table->string('reason_public')->nullable();

            // Chi ha creato il blackout (audit)
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['vendor_account_id', 'date_from', 'date_to'], 'vb_vendor_dates_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_blackouts');
    }
};