<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Founder extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'username',
        'email',
        'password',
        'status',
        'role',
        'full_name',
        'phone',
        'country',
        'timezone',
        'mentor_entitled_until',
        'last_synced_at',
    ];

    protected $casts = [
        'mentor_entitled_until' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function company(): HasOne
    {
        return $this->hasOne(Company::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function weeklyState(): HasOne
    {
        return $this->hasOne(FounderWeeklyState::class);
    }

    public function commercialSummary(): HasOne
    {
        return $this->hasOne(CommercialSummary::class);
    }

    public function moduleSnapshots(): HasMany
    {
        return $this->hasMany(ModuleSnapshot::class);
    }

    public function actionPlans(): HasMany
    {
        return $this->hasMany(FounderActionPlan::class);
    }
}
