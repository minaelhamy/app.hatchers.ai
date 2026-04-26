<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FounderPod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'vertical_blueprint_id',
        'stage',
        'city',
        'status',
        'description',
        'benchmark_json',
    ];

    protected $casts = [
        'benchmark_json' => 'array',
    ];

    public function verticalBlueprint(): BelongsTo
    {
        return $this->belongsTo(VerticalBlueprint::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(FounderPodMembership::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(FounderPodPost::class);
    }
}
