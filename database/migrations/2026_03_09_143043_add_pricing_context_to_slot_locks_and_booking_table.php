<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slot_locks', function (Blueprint $table) {
            $table->foreignId('offering_id')
                ->nullable()
                ->after('vendor_slot_id')
                ->constrained('offerings')
                ->nullOnDelete();

            $table->decimal('distance_km', 8, 2)
                ->nullable()
                ->after('date');

            $table->unsignedInteger('guests')
                ->nullable()
                ->after('distance_km');

            $table->decimal('quoted_amount', 10, 2)
                ->nullable()
                ->after('guests');

            $table->string('currency', 3)
                ->nullable()
                ->after('quoted_amount');

            $table->json('pricing_breakdown')
                ->nullable()
                ->after('currency');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('offering_id')
                ->nullable()
                ->after('vendor_account_id')
                ->constrained('offerings')
                ->nullOnDelete();

            $table->decimal('distance_km', 8, 2)
                ->nullable()
                ->after('event_date');

            $table->unsignedInteger('guests')
                ->nullable()
                ->after('distance_km');

            $table->string('currency', 3)
                ->nullable()
                ->after('total_amount');

            $table->json('pricing_breakdown')
                ->nullable()
                ->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('offering_id');
            $table->dropColumn([
                'distance_km',
                'guests',
                'currency',
                'pricing_breakdown',
            ]);
        });

        Schema::table('slot_locks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('offering_id');
            $table->dropColumn([
                'distance_km',
                'guests',
                'quoted_amount',
                'currency',
                'pricing_breakdown',
            ]);
        });
    }
};