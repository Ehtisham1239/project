<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'phone', 'email', 'avatar_url', 'source', 'whatsapp_profile', 'last_contacted_at',
    ];

    protected function casts(): array
    {
        return [
            'whatsapp_profile' => 'array',
            'last_contacted_at' => 'datetime',
        ];
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function activeConversation()
    {
        return $this->conversations()->latest('last_message_at')->first()
            ?? $this->conversations()->create(['status' => 'open']);
    }
}
