<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FounderLead extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'company_id',
        'lead_name',
        'lead_channel',
        'lead_stage',
        'contact_handle',
        'city',
        'offer_name',
        'estimated_value',
        'source_notes',
        'stage_notes',
        'first_contacted_at',
        'last_followed_up_at',
        'next_follow_up_at',
        'converted_at',
        'lost_at',
        'meta_json',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'first_contacted_at' => 'datetime',
        'last_followed_up_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
        'converted_at' => 'datetime',
        'lost_at' => 'datetime',
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

    public function conversationThreads(): HasMany
    {
        return $this->hasMany(FounderConversationThread::class, 'founder_lead_id');
    }
}
