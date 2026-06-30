<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'contact_id', 'assigned_to', 'title', 'scheduled_at', 'duration_minutes', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
