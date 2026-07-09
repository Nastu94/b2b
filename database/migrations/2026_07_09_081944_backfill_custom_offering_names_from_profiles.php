<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $offerings = DB::table('offerings')
            ->where('is_custom', true)
            ->where('name', 'LIKE', 'Proposta vendor #%')
            ->get();

        foreach ($offerings as $offering) {
            $profile = DB::table('vendor_offering_profiles')
                ->where('offering_id', $offering->id)
                ->first();

            if ($profile && !empty($profile->title)) {
                DB::table('offerings')
                    ->where('id', $offering->id)
                    ->update([
                        'name' => $profile->title,
                        'slug' => Str::slug($profile->title) . '-' . $offering->created_by_vendor_account_id . '-' . Str::lower(Str::random(6)),
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nessun rollback sicuro: non possiamo ricostruire il nome tecnico originale.
    }
};
