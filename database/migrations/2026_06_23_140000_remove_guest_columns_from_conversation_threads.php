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
            $table->dropColumn([
                'guest_name',
                'guest_email',
                'guest_token_hash',
                'guest_token_expires_at'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversation_threads', function (Blueprint $table) {
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('guest_token_hash')->nullable();
            $table->timestamp('guest_token_expires_at')->nullable();
        });
    }
};
