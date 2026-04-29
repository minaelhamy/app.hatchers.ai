<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiCurriculumLesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'week_number',
        'day_number',
        'sequence',
        'source_book',
        'slug',
        'title',
        'summary',
        'article_body',
        'action_prompt',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
