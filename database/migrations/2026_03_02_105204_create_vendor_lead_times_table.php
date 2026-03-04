<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * vendor_lead_times
 *
 * Preavviso minimo personalizzabile per giorno della settimana.
 * 
 *
 * Logica:
 * - Un record per ogni giorno della settimana per vendor
 * - min_notice_hours: anticipo minimo in ore (es. 72 per sabato)
 * - cutoff_time: ora limite entro cui prenotare (es. 18:00)
 * - Se non esiste un record per un giorno, si usa il default (48h)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_lead_times', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vendor_account_id')
                ->constrained('vendor_accounts')
                ->cascadeOnDelete();

            // 0=domenica, 1=lunedì, ..., 6=sabato
            $table->unsignedTinyInteger('day_of_week');

            // Anticipo minimo in ore (es. 48, 72)
            $table->unsignedSmallInteger('min_notice_hours')->default(48);

            // Ora limite per prenotare il giorno interessato (es. "18:00:00")
            $table->time('cutoff_time')->nullable();

            $table->timestamps();

            // Un solo record per vendor + giorno
            $table->unique(['vendor_account_id', 'day_of_week'], 'vlt_vendor_day_unique');
            $table->index('vendor_account_id', 'vlt_vendor_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_lead_times');
    }
};