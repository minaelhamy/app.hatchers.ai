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
        'email_verified_at',
        'email_verification_token',
        'email_verification_expires_at',
        'login_verification_token',
        'login_verification_expires_at',
        'mentor_entitled_until',
        'last_synced_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'email_verification_expires_at' => 'datetime',
        'login_verification_expires_at' => 'datetime',
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

    public function walletLedgerEntries(): HasMany
    {
        return $this->hasMany(FounderWalletLedger::class);
    }

    public function payoutRequests(): HasMany
    {
        return $this->hasMany(FounderPayoutRequest::class);
    }

    public function payoutAccount(): HasOne
    {
        return $this->hasOne(FounderPayoutAccount::class);
    }

    public function publicCheckoutSessions(): HasMany
    {
        return $this->hasMany(PublicCheckoutSession::class);
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

    public function notifications(): HasMany
    {
        return $this->hasMany(FounderNotification::class);
    }

    public function pricingRecommendations(): HasMany
    {
        return $this->hasMany(FounderPricingRecommendation::class);
    }

    public function firstHundredTrackers(): HasMany
    {
        return $this->hasMany(FounderFirstHundredTracker::class);
    }

    public function podMemberships(): HasMany
    {
        return $this->hasMany(FounderPodMembership::class);
    }

    public function podPosts(): HasMany
    {
        return $this->hasMany(FounderPodPost::class);
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

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }
}
