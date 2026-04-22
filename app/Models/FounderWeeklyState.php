<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FounderWeeklyState extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'open_tasks',
        'completed_tasks',
        'open_milestones',
        'completed_milestones',
        'next_meeting_at',
        'weekly_focus',
        'weekly_progress_percent',
        'state_updated_at',
    ];

    protected $casts = [
        'next_meeting_at' => 'datetime',
        'state_updated_at' => 'datetime',
    ];

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Founder::class);
    }
}
