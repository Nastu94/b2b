<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vendor_accounts', function (Blueprint $table) {
            $table->unsignedInteger('prestashop_product_id')
                ->nullable()
                ->unique()
                ->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_accounts', function (Blueprint $table) {
            $table->dropUnique(['prestashop_product_id']);
            $table->dropColumn('prestashop_product_id');
        });
    }
};