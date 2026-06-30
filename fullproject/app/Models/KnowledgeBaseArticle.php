<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeBaseArticle extends Model
{
    protected $fillable = ['title', 'content', 'category', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }
}
