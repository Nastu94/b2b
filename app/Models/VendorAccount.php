<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorAccount extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'vat_number',
        'category',
        'status',
        'activated_at'
    ];

    // Relazione con User, ogni VendorAccount appartiene a un User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
