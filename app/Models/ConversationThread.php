<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationThread extends Model
{
    protected $guarded = [];

    protected $casts = [
        'last_message_at' => 'datetime',
        'guest_token_expires_at' => 'datetime',
        'customer_deleted_at' => 'datetime',
        'vendor_deleted_at' => 'datetime',
        'admin_deleted_at' => 'datetime',
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

    public function scopeVisibleToVendor($query)
    {
        return $query->whereNull('vendor_deleted_at');
    }

    public function scopeVisibleToAdmin($query)
    {
        return $query->whereNull('admin_deleted_at');
    }

    public function deleteForVendor(): void
    {
        $this->update([
            'vendor_deleted_at' => now(),
            'vendor_unread_count' => 0,
        ]);
    }

    public function deleteForAdmin(): void
    {
        $this->update([
            'admin_deleted_at' => now(),
            'admin_unread_count' => 0,
        ]);
    }

    public function restoreForVendor(): void
    {
        $this->update([
            'vendor_deleted_at' => null,
        ]);
    }

    public function restoreForAdmin(): void
    {
        $this->update([
            'admin_deleted_at' => null,
        ]);
    }
}
