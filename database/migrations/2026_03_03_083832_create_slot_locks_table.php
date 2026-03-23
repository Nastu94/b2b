<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('slot_locks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vendor_account_id')
                ->constrained('vendor_accounts')
                ->cascadeOnDelete();

            $table->foreignId('vendor_slot_id')
                ->constrained('vendor_slots')
                ->cascadeOnDelete();

            $table->date('date');

            $table->enum('status', ['HOLD', 'BOOKED', 'EXPIRED', 'CANCELLED'])
                ->default('HOLD');

            $table->uuid('hold_token')->unique();

            $table->dateTime('expires_at');

            $table->boolean('is_active')->default(true);

            /**
             * Chiave tecnica per garantire un solo lock attivo per slot/data.
             * Valorizzata solo per record attivi; null per record chiusi.
             */
            $table->string('active_slot_key', 191)->nullable()->unique();

            $table->unsignedBigInteger('created_by_user_id')->nullable();

            $table->timestamps();

            $table->index(['vendor_account_id', 'date']);
            $table->index(['vendor_account_id', 'vendor_slot_id', 'date']);
            $table->index(['status', 'expires_at']);
            $table->index(['is_active', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slot_locks');
    }
};