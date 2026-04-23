<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Founder extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'username',
        'email',
        'password',
        'status',
        'role',
        'permissions_json',
        'auth_source',
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
        'permissions_json' => 'array',
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

    public function automationRules(): HasMany
    {
        return $this->hasMany(OsAutomationRule::class);
    }

    public function mentorAssignments(): HasMany
    {
        return $this->hasMany(MentorAssignment::class, 'founder_id');
    }

    public function assignedFounderLinks(): HasMany
    {
        return $this->hasMany(MentorAssignment::class, 'mentor_user_id');
    }

    public function assignedFounders(): HasManyThrough
    {
        return $this->hasManyThrough(
            self::class,
            MentorAssignment::class,
            'mentor_user_id',
            'id',
            'id',
            'founder_id'
        );
    }

    public function isFounder(): bool
    {
        return $this->role === 'founder';
    }

    public function isMentor(): bool
    {
        return $this->role === 'mentor';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function permissionList(): array
    {
        $permissions = $this->permissions_json;

        return is_array($permissions) ? array_values(array_filter(array_map('strval', $permissions))) : [];
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin() && empty($this->permissionList())) {
            return true;
        }

        return in_array($permission, $this->permissionList(), true);
    }

    public function identitySourceLabel(): string
    {
        return match ((string) $this->auth_source) {
            'os' => 'OS native',
            'lms_bridge' => 'LMS bridge',
            'integration_sync' => 'Integration sync',
            default => 'Legacy/unknown',
        };
    }
}
