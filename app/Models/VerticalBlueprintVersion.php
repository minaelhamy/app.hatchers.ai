<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerticalBlueprintVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'vertical_blueprint_id',
        'version_number',
        'version_label',
        'change_summary',
        'snapshot_json',
        'created_by_founder_id',
    ];

    protected $casts = [
        'snapshot_json' => 'array',
    ];

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(VerticalBlueprint::class, 'vertical_blueprint_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Founder::class, 'created_by_founder_id');
    }
}
