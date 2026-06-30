<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'language', 'category', 'body', 'variables', 'approval_status'];

    protected function casts(): array
    {
        return ['variables' => 'array'];
    }

    public function render(array $values): string
    {
        $body = $this->body;
        foreach ($values as $i => $value) {
            $body = str_replace('{{'.($i + 1).'}}', $value, $body);
        }
        return $body;
    }
}
