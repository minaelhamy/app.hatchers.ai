<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FounderConversationThread extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'company_id',
        'founder_lead_id',
        'founder_lead_channel_id',
        'thread_key',
        'source_channel',
        'status',
        'recommended_sequence_json',
        'latest_message',
        'next_follow_up_at',
        'last_activity_at',
        'meta_json',
    ];

    protected $casts = [
        'recommended_sequence_json' => 'array',
        'next_follow_up_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'meta_json' => 'array',
    ];

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Founder::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(FounderLead::class, 'founder_lead_id');
    }

    public function leadChannel(): BelongsTo
    {
        return $this->belongsTo(FounderLeadChannel::class, 'founder_lead_channel_id');
    }
}
