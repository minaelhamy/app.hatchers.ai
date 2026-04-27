<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'company_name',
        'business_model',
        'vertical_blueprint_id',
        'industry',
        'stage',
        'primary_city',
        'service_radius',
        'primary_goal',
        'launch_stage',
        'website_generation_status',
        'website_status',
        'website_engine',
        'website_path',
        'website_url',
        'engine_public_url',
        'custom_domain',
        'custom_domain_status',
        'company_brief',
        'company_logo_path',
    ];

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Founder::class);
    }

    public function verticalBlueprint(): BelongsTo
    {
        return $this->belongsTo(VerticalBlueprint::class);
    }

    public function intelligence(): HasOne
    {
        return $this->hasOne(CompanyIntelligence::class);
    }

    public function businessBrief(): HasOne
    {
        return $this->hasOne(FounderBusinessBrief::class);
    }

    public function icpProfiles(): HasMany
    {
        return $this->hasMany(FounderIcpProfile::class);
    }

    public function websiteGenerationRuns(): HasMany
    {
        return $this->hasMany(FounderWebsiteGenerationRun::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(FounderLead::class);
    }

    public function promoLinks(): HasMany
    {
        return $this->hasMany(FounderPromoLink::class);
    }

    public function launchSystems(): HasMany
    {
        return $this->hasMany(FounderLaunchSystem::class);
    }

    public function leadChannels(): HasMany
    {
        return $this->hasMany(FounderLeadChannel::class);
    }

    public function conversationThreads(): HasMany
    {
        return $this->hasMany(FounderConversationThread::class);
    }

    public function pricingRecommendations(): HasMany
    {
        return $this->hasMany(FounderPricingRecommendation::class);
    }

    public function firstHundredTrackers(): HasMany
    {
        return $this->hasMany(FounderFirstHundredTracker::class);
    }
}
