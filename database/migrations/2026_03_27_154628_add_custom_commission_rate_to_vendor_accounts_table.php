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
            $table->unsignedTinyInteger('custom_commission_rate')->nullable()->after('payment_model')->comment('Se valorizzato sovrascrive la commissione standard della categoria');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_accounts', function (Blueprint $table) {
            $table->dropColumn('custom_commission_rate');
        });    
    }
};
