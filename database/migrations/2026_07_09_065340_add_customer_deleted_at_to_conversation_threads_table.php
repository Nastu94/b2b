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
            $table->timestamp('customer_deleted_at')->nullable()->after('customer_unread_count')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversation_threads', function (Blueprint $table) {
            $table->dropColumn('customer_deleted_at');
        });
    }
};
