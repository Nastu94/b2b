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
        Schema::table('offerings', function (Blueprint $table) {
            $table->foreignId('created_by_vendor_account_id')->nullable()->constrained('vendor_accounts')->nullOnDelete();
            $table->string('status')->default('APPROVED');
            $table->boolean('is_custom')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offerings', function (Blueprint $table) {
            $table->dropForeign(['created_by_vendor_account_id']);
            $table->dropColumn(['created_by_vendor_account_id', 'status', 'is_custom']);
        });
    }
};
