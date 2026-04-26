<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VerticalBlueprint extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'business_model',
        'engine',
        'description',
        'default_offer_json',
        'default_pricing_json',
        'default_pages_json',
        'default_tasks_json',
        'default_channels_json',
        'default_cta_json',
        'default_image_queries_json',
        'funnel_framework_json',
        'pricing_preset_json',
        'channel_playbook_json',
        'script_library_json',
        'version_number',
        'status',
    ];

    protected $casts = [
        'default_offer_json' => 'array',
        'default_pricing_json' => 'array',
        'default_pages_json' => 'array',
        'default_tasks_json' => 'array',
        'default_channels_json' => 'array',
        'default_cta_json' => 'array',
        'default_image_queries_json' => 'array',
        'funnel_framework_json' => 'array',
        'pricing_preset_json' => 'array',
        'channel_playbook_json' => 'array',
        'script_library_json' => 'array',
    ];

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(VerticalBlueprintVersion::class);
    }

    public function pods(): HasMany
    {
        return $this->hasMany(FounderPod::class);
    }
}
