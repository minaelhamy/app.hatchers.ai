<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FounderPodPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_pod_id',
        'founder_id',
        'post_type',
        'title',
        'body',
        'meta_json',
    ];

    protected $casts = [
        'meta_json' => 'array',
    ];

    public function pod(): BelongsTo
    {
        return $this->belongsTo(FounderPod::class, 'founder_pod_id');
    }

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Founder::class);
    }
}
