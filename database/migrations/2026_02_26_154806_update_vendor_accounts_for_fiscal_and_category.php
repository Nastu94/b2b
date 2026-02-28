<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vendor_accounts', function (Blueprint $table) {
            // 1) Category FK (ogni vendor account appartiene a una sola categoria)
            $table->foreignId('category_id')
                ->nullable()
                ->after('user_id')
                ->constrained('categories')
                ->nullOnDelete();

            // 2) Tipo account: COMPANY o PRIVATE
            $table->string('account_type', 20)
                ->default('COMPANY')
                ->after('category_id');

            // 3) Dati anagrafici/fiscali
            $table->string('company_name')->nullable()->change(); // se era required, ora deve diventare nullable
            $table->string('vat_number')->nullable()->change();   // se era required, ora nullable

            $table->string('first_name')->nullable()->after('vat_number');
            $table->string('last_name')->nullable()->after('first_name');

            $table->string('tax_code', 50)->nullable()->after('last_name'); // CF
            $table->string('legal_entity_type', 50)->nullable()->after('tax_code'); // SRL, SNC, ecc.

            $table->string('pec_email')->nullable()->after('legal_entity_type');
            $table->string('sdi_code', 20)->nullable()->after('pec_email');
            $table->string('billing_email')->nullable()->after('sdi_code');

            $table->string('contact_name')->nullable()->after('billing_email');
            $table->string('phone', 30)->nullable()->after('contact_name');

            // 4) Sede legale
            $table->string('legal_country', 2)->default('IT')->after('phone');
            $table->string('legal_region')->nullable()->after('legal_country');
            $table->string('legal_city')->nullable()->after('legal_region');
            $table->string('legal_postal_code', 20)->nullable()->after('legal_city');
            $table->string('legal_address_line1')->nullable()->after('legal_postal_code');
            $table->string('legal_address_line2')->nullable()->after('legal_address_line1');

            // 5) Sede operativa
            $table->boolean('operational_same_as_legal')->default(true)->after('legal_address_line2');

            $table->string('operational_country', 2)->nullable()->after('operational_same_as_legal');
            $table->string('operational_region')->nullable()->after('operational_country');
            $table->string('operational_city')->nullable()->after('operational_region');
            $table->string('operational_postal_code', 20)->nullable()->after('operational_city');
            $table->string('operational_address_line1')->nullable()->after('operational_postal_code');
            $table->string('operational_address_line2')->nullable()->after('operational_address_line1');
        });

        // 6) Se avevi una colonna "category" string, la togliamo dopo aver migrato i dati
        if (Schema::hasColumn('vendor_accounts', 'category')) {
            // Per ora NON facciamo mapping automatico (potrebbe essere incoerente).
            // La rimuoviamo per evitare doppia source di verità.
            Schema::table('vendor_accounts', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }

    public function down(): void
    {
        // Ripristino (best-effort)
        Schema::table('vendor_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_accounts', 'category_id')) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            }

            if (!Schema::hasColumn('vendor_accounts', 'category')) {
                $table->string('category')->nullable();
            }

            $table->dropColumn([
                'account_type',
                'first_name',
                'last_name',
                'tax_code',
                'legal_entity_type',
                'pec_email',
                'sdi_code',
                'billing_email',
                'contact_name',
                'phone',
                'legal_country',
                'legal_region',
                'legal_city',
                'legal_postal_code',
                'legal_address_line1',
                'legal_address_line2',
                'operational_same_as_legal',
                'operational_country',
                'operational_region',
                'operational_city',
                'operational_postal_code',
                'operational_address_line1',
                'operational_address_line2',
            ]);
        });
    }
};