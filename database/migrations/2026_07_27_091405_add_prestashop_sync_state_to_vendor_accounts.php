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
        Schema::table('vendor_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('prestashop_sync_version')->default(0);
            $table->char('prestashop_payload_hash', 64)->nullable();
            $table->dateTime('prestashop_synced_at')->nullable();
            $table->string('prestashop_sync_error_code')->nullable();
            $table->dateTime('prestashop_sync_error_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'prestashop_sync_version',
                'prestashop_payload_hash',
                'prestashop_synced_at',
                'prestashop_sync_error_code',
                'prestashop_sync_error_at',
            ]);
        });
    }
};
