<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea tabella bookings per tracciare prenotazioni complete.
     * 
     * Collega slot bloccati (slot_locks) con ordini PrestaShop
     * e gestisce workflow conferma vendor.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            
            // Riferimenti esterni
            $table->foreignId('vendor_account_id')->constrained()->onDelete('cascade');
            $table->foreignId('slot_lock_id')->nullable()->constrained()->onDelete('set null');
            $table->string('prestashop_order_id')->nullable()->index();
            
            // Dati evento
            $table->date('event_date');
            $table->foreignId('vendor_slot_id')->constrained()->onDelete('cascade');
            
            // Dati cliente in JSON per flessibilità
            // Esempio: {name, email, phone, address, notes}
            $table->json('customer_data')->nullable();
            
            // Workflow stato prenotazione
            $table->enum('status', [
                'PENDING_VENDOR_CONFIRMATION',  // In attesa conferma vendor
                'CONFIRMED',                     // Vendor ha confermato
                'DECLINED',                      // Vendor ha rifiutato
                'CANCELLED',                     // Cliente ha cancellato
                'REFUNDED',                      // Ordine rimborsato
                'EXPIRED'                        // Scaduto senza conferma
            ])->default('PENDING_VENDOR_CONFIRMATION');
            
            // Importi
            $table->decimal('total_amount', 10, 2)->nullable();
            
            // Timestamp importanti per workflow
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            
            // Note e motivazioni
            $table->text('vendor_notes')->nullable();
            $table->text('decline_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indici per performance query comuni
            $table->index('event_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};