<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('slot_locks', function (Blueprint $table) {
            $table->unsignedBigInteger('prestashop_shop_id')->nullable()->after('offering_id');
            $table->unsignedBigInteger('prestashop_cart_id')->nullable()->after('prestashop_shop_id');
            $table->unsignedBigInteger('prestashop_customer_id')->nullable()->after('prestashop_cart_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slot_locks', function (Blueprint $table) {
            $table->dropColumn([
                'prestashop_shop_id',
                'prestashop_cart_id',
                'prestashop_customer_id',
            ]);
        });
    }
};
