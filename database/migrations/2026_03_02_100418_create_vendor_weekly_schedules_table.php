<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * vendor_weekly_schedules
 *
 * Definisce quali slot sono aperti per ogni giorno della settimana.
 * È la base del calcolo disponibilità .
 *
 * Logica:
 * - Record con is_open=true → slot aperto quel giorno
 * - Nessun record → slot chiuso (default chiuso, esplicito per aprire)
 * - min_notice_hours e cutoff_time gestiscono il lead time 
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_weekly_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vendor_account_id')
                ->constrained('vendor_accounts')
                ->cascadeOnDelete();

            $table->foreignId('vendor_slot_id')
                ->constrained('vendor_slots')
                ->cascadeOnDelete();

            // 0=domenica, 1=lunedì, 2=martedì, 3=mercoledì,
            // 4=giovedì, 5=venerdì, 6=sabato
            $table->unsignedTinyInteger('day_of_week');

            // true = slot aperto, false = chiuso
            $table->boolean('is_open')->default(false);

            // PDF §5.2 — anticipo minimo in ore (es. 48)
            $table->unsignedSmallInteger('min_notice_hours')->default(48);

            // PDF §5.2 — ora limite per prenotare (es. "18:00:00")
            $table->time('cutoff_time')->nullable();

            $table->timestamps();

            
            $table->unique(['vendor_account_id', 'vendor_slot_id', 'day_of_week'], 'vws_unique');
            $table->index(['vendor_account_id', 'day_of_week'], 'vws_vendor_day_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_weekly_schedules');
    }
};