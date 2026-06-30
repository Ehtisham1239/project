<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id', 'user_id', 'direction', 'type', 'body',
        'media_url', 'media_mime_type', 'wa_message_id', 'status', 'raw_payload',
    ];

    protected function casts(): array
    {
        return ['raw_payload' => 'array'];
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
