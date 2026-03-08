<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabella regole di pricing collegate al listino base.
 *
 * Ogni record rappresenta una regola applicabile a un listino:
 * - surcharge
 * - discount
 * - override
 *
 * Le condizioni dettagliate verranno interpretate dal resolver
 * in un passaggio successivo.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendor_offering_pricing_rules', function (Blueprint $table) {
            $table->id();

            /**
             * Listino base a cui appartiene la regola.
             */
            $table->foreignId('vendor_offering_pricing_id')
                ->constrained('vendor_offering_pricings')
                ->cascadeOnDelete();

            /**
             * Nome interno della regola.
             */
            $table->string('name');

            /**
             * Stato attivo/disattivo della regola.
             */
            $table->boolean('is_active')->default(true);

            /**
             * Priorità di applicazione.
             * Numero minore = precedenza maggiore.
             */
            $table->unsignedInteger('priority')->default(100);

            /**
             * Tipo regola:
             * - SURCHARGE
             * - DISCOUNT
             * - OVERRIDE
             */
            $table->string('rule_type', 30);

            /**
             * Tipo valore:
             * - FIXED
             * - PERCENT
             */
            $table->string('adjustment_type', 20)->nullable();

            /**
             * Valore della regola.
             * Esempi:
             * - 25.00 per importo fisso
             * - 10.00 per 10%
             */
            $table->decimal('adjustment_value', 10, 2)->nullable();

            /**
             * Prezzo finale forzato nel caso di OVERRIDE.
             */
            $table->decimal('override_price', 10, 2)->nullable();

            /**
             * Intervallo di validità della regola.
             */
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();

            /**
             * Giorni della settimana su cui applicare la regola.
             * Formato JSON, ad esempio:
             * [1,2,3,4,5]
             * dove 1 = lunedì, 7 = domenica
             */
            $table->json('weekdays')->nullable();

            /**
             * Vincoli quantitativi opzionali.
             */
            $table->unsignedInteger('min_quantity')->nullable();
            $table->unsignedInteger('max_quantity')->nullable();

            /**
             * Flag per indicare se la regola è esclusiva.
             * Una regola esclusiva potrà essere gestita dal resolver
             * come non cumulabile con altre.
             */
            $table->boolean('is_exclusive')->default(false);

            /**
             * Configurazione condizioni avanzate.
             *
             * Campo JSON pensato per regole future come:
             * - fascia oraria
             * - distanza
             * - anticipo prenotazione
             * - capienza
             * - tag evento
             */
            $table->json('conditions')->nullable();

            /**
             * Note interne di backoffice.
             */
            $table->text('notes_internal')->nullable();

            $table->timestamps();
            $table->softDeletes();

            /**
             * Indici utili per il resolver e il backoffice.
             */
            $table->index(
                ['vendor_offering_pricing_id', 'is_active', 'priority'],
                'vopr_pricing_active_priority_idx'
            );

            $table->index(
                ['rule_type', 'is_active'],
                'vopr_rule_type_active_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_offering_pricing_rules');
    }
};