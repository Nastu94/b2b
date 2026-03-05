<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('slot_locks', function (Blueprint $table) {
            // ora BOOKED non deve per forza avere expires_at
            $table->dateTime('expires_at')->nullable()->change();

            // timestamps di workflow (utili per audit + debug)
            $table->timestamp('booked_at')->nullable()->after('expires_at');
            $table->timestamp('cancelled_at')->nullable()->after('booked_at');
            $table->timestamp('expired_at')->nullable()->after('cancelled_at');

            // collega lock -> booking (facilita idempotenza e riconciliazione)
            $table->foreignId('booking_id')->nullable()->after('expired_at')
                ->constrained('bookings')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('slot_locks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_id');
            $table->dropColumn(['booked_at', 'cancelled_at', 'expired_at']);

            // se vuoi rollback completo: torna non-nullable
            $table->dateTime('expires_at')->nullable(false)->change();
        });
    }
};