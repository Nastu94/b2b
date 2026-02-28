<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vendor_accounts', function (Blueprint $table) {
            // Coordinate sede operativa (preferita)
            $table->decimal('operational_lat', 10, 7)->nullable()->after('operational_address_line1');
            $table->decimal('operational_lng', 10, 7)->nullable()->after('operational_lat');

            // Coordinate sede legale (fallback)
            $table->decimal('legal_lat', 10, 7)->nullable()->after('legal_address_line1');
            $table->decimal('legal_lng', 10, 7)->nullable()->after('legal_lat');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_accounts', function (Blueprint $table) {
            $table->dropColumn(['operational_lat', 'operational_lng', 'legal_lat', 'legal_lng']);
        });
    }
};