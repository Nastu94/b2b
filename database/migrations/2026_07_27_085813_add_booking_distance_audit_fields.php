<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slot_locks', function (Blueprint $table) {
            $table->string('event_city')->nullable()->after('date');
            $table->string('event_region')->nullable()->after('event_city');
            $table->decimal('client_distance_km', 10, 2)->nullable()->after('distance_km');
            $table->string('distance_source')->nullable()->after('client_distance_km');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('event_city')->nullable()->after('event_date');
            $table->string('event_region')->nullable()->after('event_city');
            $table->string('distance_source')->nullable()->after('distance_km');
        });
    }

    public function down(): void
    {
        Schema::table('slot_locks', function (Blueprint $table) {
            $table->dropColumn(['event_city', 'event_region', 'client_distance_km', 'distance_source']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['event_city', 'event_region', 'distance_source']);
        });
    }
};
