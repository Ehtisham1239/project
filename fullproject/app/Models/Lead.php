<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id', 'lead_stage_id', 'owner_id', 'title', 'value', 'source', 'notes', 'expected_close_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'expected_close_at' => 'datetime',
        ];
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function stage()
    {
        return $this->belongsTo(LeadStage::class, 'lead_stage_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function moveToStage(LeadStage $stage): void
    {
        $old = $this->stage->name ?? null;
        $this->update(['lead_stage_id' => $stage->id]);
        AuditLog::record('lead_stage_changed', ['from' => $old, 'to' => $stage->name], $this);
    }
}
