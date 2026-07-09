<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorDocument extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    public const TYPES = [
        'LICENSE' => 'Licenza / autorizzazione',
        'NCC_LICENSE' => 'Licenza NCC / limousine',
        'INSURANCE' => 'Assicurazione',
        'IDENTITY' => 'Documento identità',
        'BUSINESS_REGISTRATION' => 'Visura / documento aziendale',
        'CERTIFICATION' => 'Certificazione professionale',
        'OTHER' => 'Altro',
    ];

    protected $fillable = [
        'vendor_account_id',
        'type',
        'title',
        'original_filename',
        'path',
        'mime_type',
        'size_bytes',
        'status',
        'expires_at',
        'uploaded_by',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'size_bytes' => 'integer',
    ];

    public function vendorAccount(): BelongsTo
    {
        return $this->belongsTo(VendorAccount::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
