<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendor_offerings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vendor_account_id')
                ->constrained('vendor_accounts')
                ->cascadeOnDelete();

            $table->foreignId('offering_id')
                ->constrained('offerings')
                ->cascadeOnDelete();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['vendor_account_id', 'offering_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_offerings');
    }
};