<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_offering_profiles', function (Blueprint $table) {
            // Modalità di erogazione del servizio:
            // FIXED_LOCATION -> il cliente si reca presso il vendor
            // MOBILE -> il vendor si sposta entro un raggio operativo
            $table->string('service_mode', 30)
                ->default('FIXED_LOCATION')
                ->after('cover_image_path')
                ->index();

            // Raggio operativo espresso in chilometri.
            // Usato solo quando service_mode = MOBILE.
            $table->unsignedSmallInteger('service_radius_km')
                ->nullable()
                ->after('service_mode');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_offering_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_offering_profiles', 'service_mode')) {
                $table->dropIndex(['service_mode']);
                $table->dropColumn('service_mode');
            }

            if (Schema::hasColumn('vendor_offering_profiles', 'service_radius_km')) {
                $table->dropColumn('service_radius_km');
            }
        });
    }
};