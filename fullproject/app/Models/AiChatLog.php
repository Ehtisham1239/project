<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiChatLog extends Model
{
    protected $fillable = ['conversation_id', 'prompt', 'response', 'was_auto_sent'];

    protected function casts(): array
    {
        return ['was_auto_sent' => 'boolean'];
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
