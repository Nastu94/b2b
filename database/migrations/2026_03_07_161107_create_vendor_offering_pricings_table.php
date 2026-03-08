<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabella listino base vendor + servizio.
 *
 * Questa tabella rappresenta la configurazione principale del prezzo
 * per una singola coppia vendor_account + offering.
 *
 * Vincoli di dominio:
 * - un solo listino base per vendor + servizio
 * - layer separato da contenuti, disponibilità e booking
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendor_offering_pricings', function (Blueprint $table) {
            $table->id();

            /**
             * Vendor proprietario del listino.
             */
            $table->foreignId('vendor_account_id')
                ->constrained('vendor_accounts')
                ->cascadeOnDelete();

            /**
             * Servizio del vendor a cui appartiene il listino.
             */
            $table->foreignId('offering_id')
                ->constrained('offerings')
                ->cascadeOnDelete();

            /**
             * Stato attivo/disattivo del listino.
             */
            $table->boolean('is_active')->default(true);

            /**
             * Tipo prezzo:
             * - FIXED
             * - STARTING_FROM
             * - FREE
             */
            $table->string('price_type', 30)->default('FIXED');

            /**
             * Prezzo base del servizio.
             * Anche per FREE lo manteniamo esplicito a 0.00 per coerenza dati.
             */
            $table->decimal('base_price', 10, 2)->default(0);

            /**
             * Valuta del listino.
             */
            $table->string('currency', 3)->default('EUR');

            /**
             * Raggio commerciale base del servizio in km.
             * Nullable perché non tutti i servizi potrebbero usarlo subito.
             */
            $table->decimal('service_radius_km', 8, 2)->nullable();

            /**
             * Modalità di gestione distanza:
             * - INCLUDED
             * - SURCHARGE_BY_RULE
             * - NOT_AVAILABLE_OUTSIDE_RADIUS
             */
            $table->string('distance_pricing_mode', 40)->default('INCLUDED');

            /**
             * Note interne di backoffice.
             */
            $table->text('notes_internal')->nullable();

            $table->timestamps();
            $table->softDeletes();

            /**
             * Un solo listino base per coppia vendor + servizio.
             */
            $table->unique(
                ['vendor_account_id', 'offering_id'],
                'vendor_offering_pricings_vendor_offering_unique'
            );

            /**
             * Indici utili per query frequenti.
             */
            $table->index(['vendor_account_id', 'is_active'], 'vop_vendor_active_idx');
            $table->index(['offering_id', 'is_active'], 'vop_offering_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_offering_pricings');
    }
};