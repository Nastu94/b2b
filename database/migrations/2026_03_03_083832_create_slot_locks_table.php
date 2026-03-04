<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('slot_locks', function (Blueprint $table) {
            $table->id();

            // Chi blocca cosa
            $table->foreignId('vendor_account_id')
                ->constrained('vendor_accounts')
                ->cascadeOnDelete();

            $table->foreignId('vendor_slot_id')
                ->constrained('vendor_slots')
                ->cascadeOnDelete();

            // Giorno dello slot
            $table->date('date');

            // Stato lock (PDF: HOLD/BOOKED). Aggiungo EXPIRED/CANCELLED per chiusura pulita.
            $table->enum('status', ['HOLD', 'BOOKED', 'EXPIRED', 'CANCELLED'])
                ->default('HOLD');

            // Token che PrestaShop/Laravel si scambiano per confermare
            $table->uuid('hold_token')->unique();

            // Scadenza HOLD (TTL). Per BOOKED può restare valorizzato o null, ma lo teniamo required per semplicità.
            $table->dateTime('expires_at');

            // Flag tecnico per vincolo UNIQUE sugli attivi (MySQL non ha unique parziale)
            $table->boolean('is_active')->default(true);

            // (Opzionale ma utile) riferimento interno a user/sessione in futuro
            $table->unsignedBigInteger('created_by_user_id')->nullable();

            $table->timestamps();

            // Indici per query availability e cleanup
            $table->index(['vendor_account_id', 'date']);
            $table->index(['vendor_account_id', 'vendor_slot_id', 'date']);
            $table->index(['status', 'expires_at']);

            // Vincolo anti doppia prenotazione: un solo lock attivo per vendor+date+slot
            $table->unique(['vendor_account_id', 'vendor_slot_id', 'date', 'is_active'], 'uniq_active_lock');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slot_locks');
    }
};