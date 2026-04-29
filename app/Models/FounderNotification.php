<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FounderNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'kind',
        'title',
        'meta',
        'app_key',
        'link_url',
        'is_read',
        'data_json',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'data_json' => 'array',
    ];

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Founder::class);
    }
}
