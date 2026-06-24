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
        Schema::create('conversation_messages', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('conversation_thread_id')->constrained('conversation_threads')->cascadeOnDelete();
            
            // Sender Info
            $table->string('sender_type'); // 'customer', 'guest', 'vendor', 'admin', 'system'
            $table->unsignedBigInteger('sender_id')->nullable();
            
            // Content & Moderation
            $table->text('body_original');
            $table->text('body_filtered')->nullable();
            $table->string('moderation_status')->default('clean'); // 'clean', 'filtered', 'flagged'
            $table->text('moderation_flags')->nullable();
            
            // Visibility
            $table->boolean('is_visible_to_customer')->default(true);
            $table->boolean('is_visible_to_vendor')->default(true);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_messages');
    }
};
