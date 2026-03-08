<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('prestashop_order_line_id')->nullable()->after('prestashop_order_id');

            // vincolo idempotenza: una booking per riga ordine
            $table->unique(['prestashop_order_id', 'prestashop_order_line_id'], 'uniq_ps_order_line');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique('uniq_ps_order_line');
            $table->dropColumn('prestashop_order_line_id');
        });
    }
};