<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationThread extends Model
{
    protected $guarded = [];

    protected $casts = [
        'last_message_at' => 'datetime',
        'guest_token_expires_at' => 'datetime',
    ];

    public function vendorAccount()
    {
        return $this->belongsTo(VendorAccount::class);
    }

    public function offering()
    {
        return $this->belongsTo(Offering::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function messages()
    {
        return $this->hasMany(ConversationMessage::class);
    }
}
