<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    protected $fillable = ['name', 'trigger_event', 'trigger_conditions', 'is_active'];

    protected function casts(): array
    {
        return ['trigger_conditions' => 'array', 'is_active' => 'boolean'];
    }

    public function actions()
    {
        return $this->hasMany(WorkflowAction::class)->orderBy('order');
    }
}
