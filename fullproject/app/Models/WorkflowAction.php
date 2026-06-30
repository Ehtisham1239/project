<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowAction extends Model
{
    protected $fillable = ['workflow_id', 'order', 'action_type', 'action_params'];

    protected function casts(): array
    {
        return ['action_params' => 'array'];
    }

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }
}
