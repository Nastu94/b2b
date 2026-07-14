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
        Schema::table('conversation_threads', function (Blueprint $table) {
            $table->timestamp('vendor_deleted_at')->nullable()->index();
            $table->timestamp('admin_deleted_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversation_threads', function (Blueprint $table) {
            $table->dropIndex(['vendor_deleted_at']);
            $table->dropIndex(['admin_deleted_at']);
        });

        Schema::table('conversation_threads', function (Blueprint $table) {
            $table->dropColumn(['vendor_deleted_at', 'admin_deleted_at']);
        });
    }
};
