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
            $table->string('idempotency_hash', 64)->nullable()->after('idempotency_key');
            $table->index('idempotency_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slot_locks', function (Blueprint $table) {
            $table->dropIndex(['idempotency_hash']);
            $table->dropColumn('idempotency_hash');
        });
    }
};
