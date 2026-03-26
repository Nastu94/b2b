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
            $table->timestamp('privacy_accepted_at')->nullable()->after('status');
            $table->timestamp('contract_accepted_at')->nullable()->after('privacy_accepted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_accounts', function (Blueprint $table) {
            $table->dropColumn(['privacy_accepted_at', 'contract_accepted_at']);
        });
    }
};
