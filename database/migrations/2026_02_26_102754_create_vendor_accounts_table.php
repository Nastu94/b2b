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
        Schema::create('vendor_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('company_name')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('category')->nullable(); // 1 categoria per vendor account

            $table->string('status')->default('ACTIVE');  // per ora bypassa il pagamento
            $table->timestamp('activated_at')->nullable(); // aggiunto ma si puo' togliere (CHIEDERE!!) 
            $table->timestamp('deactivated_at')->nullable(); 


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_accounts');
    }
};
