<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'company_name',
        'business_model',
        'industry',
        'stage',
        'website_status',
        'website_engine',
        'website_path',
        'website_url',
        'custom_domain',
        'custom_domain_status',
        'company_brief',
    ];

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Founder::class);
    }

    public function intelligence(): HasOne
    {
        return $this->hasOne(CompanyIntelligence::class);
    }
}
