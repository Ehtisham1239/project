<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoiceCall extends Model
{
    protected $fillable = [
        'contact_id', 'user_id', 'direction', 'status', 'duration_seconds',
        'recording_url', 'provider_call_id', 'raw_payload',
    ];

    protected function casts(): array
    {
        return ['raw_payload' => 'array'];
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
