<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FounderBusinessBrief extends Model
{
    use HasFactory;

    protected $fillable = [
        'founder_id',
        'company_id',
        'vertical_blueprint_id',
        'business_name',
        'business_summary',
        'problem_solved',
        'core_offer',
        'business_type_detail',
        'location_city',
        'location_country',
        'service_radius',
        'delivery_scope',
        'proof_points',
        'founder_story',
        'constraints_json',
        'status',
    ];

    protected $casts = [
        'constraints_json' => 'array',
    ];

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Founder::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function verticalBlueprint(): BelongsTo
    {
        return $this->belongsTo(VerticalBlueprint::class);
    }
}
