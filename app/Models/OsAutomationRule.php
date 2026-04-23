<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OsAutomationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'name',
        'trigger_type',
        'module_scope',
        'condition_summary',
        'action_summary',
        'status',
        'metadata_json',
    ];

    protected $casts = [
        'metadata_json' => 'array',
    ];

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Founder::class);
    }
}
