<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationMessage extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_visible_to_customer' => 'boolean',
        'is_visible_to_vendor' => 'boolean',
        'moderation_flags' => 'array',
    ];

    public function thread()
    {
        return $this->belongsTo(ConversationThread::class, 'conversation_thread_id');
    }
}
