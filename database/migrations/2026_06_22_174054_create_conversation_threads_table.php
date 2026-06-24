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
        Schema::create('conversation_threads', function (Blueprint $table) {
            $table->id();
            
            // Relazioni
            $table->foreignId('vendor_account_id')->constrained('vendor_accounts')->cascadeOnDelete();
            $table->foreignId('offering_id')->nullable()->constrained('offerings')->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            
            // PrestaShop
            $table->unsignedBigInteger('prestashop_customer_id')->nullable()->index();
            
            // Guest
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('guest_token_hash')->nullable();
            $table->timestamp('guest_token_expires_at')->nullable();
            
            // Metadata
            $table->string('source')->nullable();
            $table->string('status')->default('open')->index();
            
            // Statistiche e Counters
            $table->timestamp('last_message_at')->nullable();
            $table->unsignedInteger('vendor_unread_count')->default(0);
            $table->unsignedInteger('customer_unread_count')->default(0);
            $table->unsignedInteger('admin_unread_count')->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_threads');
    }
};
