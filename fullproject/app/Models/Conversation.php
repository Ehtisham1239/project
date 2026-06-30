<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = ['contact_id', 'assigned_to', 'status', 'last_message_at', 'unread'];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'unread' => 'boolean',
        ];
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function assignedAgent()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function notes()
    {
        return $this->hasMany(ConversationNote::class)->latest();
    }

    public function assignTo(User $user): void
    {
        $this->update(['assigned_to' => $user->id]);
        AuditLog::record('conversation_assigned', ['agent' => $user->name], $this);
    }
}
