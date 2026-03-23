<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vendor_offering_profiles', function (Blueprint $table) {
            // Capacita' massima ospiti per servizi in sede (FIXED_LOCATION).
            // Null = nessun limite impostato.
            // Visibile e modificabile solo quando service_mode = FIXED_LOCATION.
            $table->unsignedSmallInteger('max_guests')->nullable()->after('service_radius_km');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_offering_profiles', function (Blueprint $table) {
            $table->dropColumn('max_guests');
        });
    }
};