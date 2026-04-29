<?php

namespace App\Http\Controllers;

use App\Models\CommercialSummary;
use App\Models\Company;
use App\Models\CompanyIntelligence;
use App\Models\Founder;
use App\Models\FounderActionPlan;
use App\Models\FounderBusinessBrief;
use App\Models\FounderConversationThread;
use App\Models\FounderFirstHundredTracker;
use App\Models\FounderIcpProfile;
use App\Models\FounderLaunchSystem;
use App\Models\FounderLead;
use App\Models\FounderLeadChannel;
use App\Models\FounderPod;
use App\Models\FounderPodMembership;
use App\Models\FounderPodPost;
use App\Models\FounderPricingRecommendation;
use App\Models\FounderPromoLink;
use App\Models\FounderPayoutAccount;
use App\Models\FounderPayoutRequest;
use App\Models\FounderWeeklyState;
use App\Models\OsAutomationRule;
use App\Models\OsOperationException;
use App\Models\PublicCheckoutSession;
use App\Models\Subscription;
use App\Models\VerticalBlueprint;
use App\Models\VerticalBlueprintVersion;
use App\Services\AdminDashboardService;
use App\Services\AdminOperationsService;
use App\Services\AtlasIntelligenceService;
use App\Services\AtlasWorkspaceService;
use App\Services\FounderModuleSyncService;
use App\Services\FounderDashboardService;
use App\Services\FounderRevenueOsService;
use App\Services\IdentitySyncService;
use App\Services\LmsIdentityBridgeService;
use App\Services\MentorDashboardService;
use App\Services\OsAssistantActionService;
use App\Services\OsAssistantTimelineService;
use App\Services\OsOperationsLogService;
use App\Services\OsStripeService;
use App\Services\OsWalletService;
use App\Services\PublicWebsiteService;
use App\Services\WebsiteAutopilotService;
use App\Services\WebsiteProvisioningService;
use App\Services\WebsiteWorkspaceService;
use App\Services\WorkspaceLaunchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class OsShellController extends Controller
{
    public function landing(): RedirectResponse
    {
        return redirect()->route('login');
    }

    public function plans()
    {
        return view('os.plans', [
            'pageTitle' => 'Choose Your Hatchers OS Plan',
            'plans' => collect($this->founderSignupPlans())->values()->all(),
        ]);
    }

    public function onboarding(Request $request)
    {
        $selectedPlan = $this->resolveFounderSignupPlan((string) $request->query('plan', ''));
        if ($selectedPlan === null) {
            return redirect()->route('plans')->with('error', 'Please choose a founder plan before continuing.');
        }

        return view('os.onboarding', [
            'pageTitle' => 'Founder Onboarding',
            'submitted' => session('submitted'),
            'selectedPlan' => $selectedPlan,
            'verticalBlueprintOptions' => array_values($this->verticalBlueprintDefinitions()),
            'industryOptions' => $this->founderIndustryOptions(),
            'coreOfferOptions' => $this->founderCoreOfferOptions(),
        ]);
    }

    public function login()
    {
        return view('os.login', [
            'pageTitle' => 'Hatchers OS Login',
        ]);
    }

    public function verifyEmailNotice(Request $request)
    {
        if ($this->authVerificationDisabled()) {
            return redirect()->route('login')->with('success', 'Verification is disabled for test mode. You can log in directly.');
        }

        return view('os.verify-email', [
            'pageTitle' => 'Verify Founder Email',
            'email' => (string) ($request->query('email', session('verification_email', ''))),
        ]);
    }

    public function verifyEmail(Request $request): RedirectResponse
    {
        if ($this->authVerificationDisabled()) {
            return redirect()->route('login')->with('success', 'Verification is disabled for test mode. You can log in directly.');
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $founder = Founder::query()->where('email', $validated['email'])->first();
        if (!$founder) {
            return back()->withErrors(['email' => 'We could not find a founder account for that email address.'])->withInput();
        }

        if ($founder->hasVerifiedEmail()) {
            return redirect()->route('login')->with('success', 'Your founder email is already verified. Please log in.');
        }

        if (
            empty($founder->email_verification_token) ||
            empty($founder->email_verification_expires_at) ||
            now()->greaterThan($founder->email_verification_expires_at) ||
            !Hash::check((string) $validated['code'], (string) $founder->email_verification_token)
        ) {
            return back()->withErrors(['code' => 'That verification code is invalid or expired.'])->withInput();
        }

        $founder->forceFill([
            'email_verified_at' => now(),
            'email_verification_token' => null,
            'email_verification_expires_at' => null,
        ])->save();

        $this->sendFounderMail(
            $founder->email,
            'Welcome to Hatchers Ai Business OS',
            'emails.founder-welcome',
            [
                'founder' => $founder,
            ]
        );

        $request->session()->forget('verification_email');

        return redirect()->route('login')->with('success', 'Email verified. You can now log in to Hatchers Ai Business OS.');
    }

    public function resendEmailVerification(Request $request): RedirectResponse
    {
        if ($this->authVerificationDisabled()) {
            return redirect()->route('login')->with('success', 'Verification is disabled for test mode. No email code is needed.');
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $founder = Founder::query()->where('email', $validated['email'])->first();
        if (!$founder) {
            return back()->withErrors(['email' => 'We could not find a founder account for that email address.'])->withInput();
        }

        if ($founder->hasVerifiedEmail()) {
            return redirect()->route('login')->with('success', 'Your founder email is already verified. Please log in.');
        }

        $this->issueEmailVerification($founder);
        $request->session()->put('verification_email', $founder->email);

        return back()->with('success', 'A fresh email verification code has been sent.');
    }

    public function verifyLoginNotice(Request $request)
    {
        if ($this->authVerificationDisabled()) {
            return redirect()->route('login')->with('success', 'Verification is disabled for test mode. Sign in directly from the login form.');
        }

        $pendingFounderId = (int) $request->session()->get('pending_login_founder_id', 0);
        $founder = $pendingFounderId > 0 ? Founder::query()->find($pendingFounderId) : null;

        if (!$founder) {
            return redirect()->route('login')->withErrors(['login' => 'Please log in again so we can send a new verification code.']);
        }

        return view('os.verify-login', [
            'pageTitle' => 'Verify Founder Login',
            'email' => $founder->email,
        ]);
    }

    public function verifyLogin(Request $request): RedirectResponse
    {
        if ($this->authVerificationDisabled()) {
            return redirect()->route('login')->with('success', 'Verification is disabled for test mode. Sign in directly from the login form.');
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $pendingFounderId = (int) $request->session()->get('pending_login_founder_id', 0);
        $founder = $pendingFounderId > 0 ? Founder::query()->find($pendingFounderId) : null;

        if (!$founder) {
            return redirect()->route('login')->withErrors(['login' => 'Please log in again so we can send a new verification code.']);
        }

        if (
            empty($founder->login_verification_token) ||
            empty($founder->login_verification_expires_at) ||
            now()->greaterThan($founder->login_verification_expires_at) ||
            !Hash::check((string) $validated['code'], (string) $founder->login_verification_token)
        ) {
            return back()->withErrors(['code' => 'That sign-in verification code is invalid or expired.'])->withInput();
        }

        $founder->forceFill([
            'login_verification_token' => null,
            'login_verification_expires_at' => null,
        ])->save();

        Auth::login($founder, true);
        $request->session()->forget('pending_login_founder_id');
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function resendLoginVerification(Request $request): RedirectResponse
    {
        if ($this->authVerificationDisabled()) {
            return redirect()->route('login')->with('success', 'Verification is disabled for test mode. No sign-in code is needed.');
        }

        $pendingFounderId = (int) $request->session()->get('pending_login_founder_id', 0);
        $founder = $pendingFounderId > 0 ? Founder::query()->find($pendingFounderId) : null;

        if (!$founder) {
            return redirect()->route('login')->withErrors(['login' => 'Please log in again so we can send a new verification code.']);
        }

        $this->issueLoginVerification($founder, $request);

        return back()->with('success', 'A fresh sign-in verification code has been sent.');
    }

    public function dashboard(
        FounderDashboardService $founderDashboardService,
        MentorDashboardService $mentorDashboardService,
        AdminDashboardService $adminDashboardService,
        WorkspaceLaunchService $workspaceLaunchService
    )
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            return view('os.dashboard-admin', [
                'pageTitle' => 'Admin Dashboard',
                'dashboard' => $adminDashboardService->build($user),
                'launchCards' => $workspaceLaunchService->launchCards($user),
            ]);
        }

        if ($user->isMentor()) {
            return view('os.dashboard-mentor', [
                'pageTitle' => 'Mentor Dashboard',
                'dashboard' => $mentorDashboardService->build($user),
                'launchCards' => $workspaceLaunchService->launchCards($user),
            ]);
        }

        return view('os.dashboard', [
            'pageTitle' => 'Founder Dashboard',
            'dashboard' => $founderDashboardService->build($user),
            'launchCards' => $workspaceLaunchService->launchCards($user),
        ]);
    }

    public function founderNotifications(FounderDashboardService $founderDashboardService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        return view('os.notifications', [
            'pageTitle' => 'Notifications',
            'dashboard' => $founderDashboardService->build($user),
        ]);
    }

    public function founderInbox(FounderDashboardService $founderDashboardService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }
        if ($redirect = $this->ensureCompanyIntelligenceComplete($user)) {
            return $redirect;
        }

        return view('os.inbox', [
            'pageTitle' => 'Inbox',
            'dashboard' => $founderDashboardService->build($user),
        ]);
    }

    public function founderActivity(FounderDashboardService $founderDashboardService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }
        if ($redirect = $this->ensureCompanyIntelligenceComplete($user)) {
            return $redirect;
        }

        return view('os.activity', [
            'pageTitle' => 'System Activity',
            'dashboard' => $founderDashboardService->build($user),
        ]);
    }

    public function founderFirstHundred(
        Request $request,
        FounderDashboardService $founderDashboardService,
        FounderRevenueOsService $revenueOsService
    ) {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }
        if ($redirect = $this->ensureCompanyIntelligenceComplete($user)) {
            return $redirect;
        }

        $filters = $request->validate([
            'stage' => ['nullable', 'string', Rule::in($revenueOsService->stageOptions())],
            'channel' => ['nullable', 'string', Rule::in($revenueOsService->channelOptions($user->company?->verticalBlueprint))],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        return view('os.first-100', [
            'pageTitle' => 'Lead Tracker',
            'dashboard' => $founderDashboardService->build($user),
            'tracker' => $revenueOsService->workspace($user, $filters),
        ]);
    }

    public function founderPods(FounderDashboardService $founderDashboardService, FounderRevenueOsService $revenueOsService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        return view('os.pods', [
            'pageTitle' => 'Pods',
            'dashboard' => $founderDashboardService->build($user),
            'workspace' => $revenueOsService->podsWorkspace($user),
        ]);
    }

    public function founderJoinPod(FounderPod $pod): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        FounderPodMembership::query()->updateOrCreate(
            [
                'founder_id' => $user->id,
                'founder_pod_id' => $pod->id,
            ],
            [
                'role' => 'member',
                'status' => 'active',
                'joined_at' => now(),
            ]
        );

        return redirect()->route('founder.pods')->with('success', 'You joined the pod and can now post wins and blockers there.');
    }

    public function founderStorePodPost(Request $request, FounderPod $pod): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $membership = FounderPodMembership::query()
            ->where('founder_id', $user->id)
            ->where('founder_pod_id', $pod->id)
            ->where('status', 'active')
            ->first();

        if (!$membership) {
            return redirect()->route('founder.pods')->with('error', 'Join the pod before posting.');
        }

        $validated = $request->validate([
            'post_type' => ['required', Rule::in(['win', 'blocker', 'prompt'])],
            'title' => ['required', 'string', 'max:191'],
            'body' => ['required', 'string', 'max:3000'],
        ]);

        FounderPodPost::query()->create([
            'founder_pod_id' => $pod->id,
            'founder_id' => $user->id,
            'post_type' => (string) $validated['post_type'],
            'title' => trim((string) $validated['title']),
            'body' => trim((string) $validated['body']),
            'meta_json' => [
                'company_name' => (string) ($user->company?->company_name ?? ''),
            ],
        ]);

        return redirect()->route('founder.pods')->with('success', 'Pod post published.');
    }

    public function founderLearningPlan(FounderDashboardService $founderDashboardService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }
        if ($redirect = $this->ensureCompanyIntelligenceComplete($user)) {
            return $redirect;
        }

        return view('os.learning-plan', [
            'pageTitle' => 'Learning Plan',
            'dashboard' => $founderDashboardService->build($user),
        ]);
    }

    public function founderTasks(FounderDashboardService $founderDashboardService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }
        if ($redirect = $this->ensureCompanyIntelligenceComplete($user)) {
            return $redirect;
        }

        return view('os.tasks', [
            'pageTitle' => 'Tasks',
            'dashboard' => $founderDashboardService->build($user),
        ]);
    }

    public function founderStoreLead(Request $request, FounderRevenueOsService $revenueOsService): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'lead_name' => ['required', 'string', 'max:191'],
            'lead_channel' => ['required', 'string', Rule::in(array_values(array_filter($revenueOsService->channelOptions($user->company?->verticalBlueprint), fn (string $value) => $value !== 'all')))],
            'lead_stage' => ['required', 'string', Rule::in(array_values(array_filter($revenueOsService->stageOptions(), fn (string $value) => $value !== 'all')))],
            'contact_handle' => ['nullable', 'string', 'max:191'],
            'city' => ['nullable', 'string', 'max:191'],
            'offer_name' => ['nullable', 'string', 'max:191'],
            'estimated_value' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'source_notes' => ['nullable', 'string', 'max:2000'],
            'stage_notes' => ['nullable', 'string', 'max:2000'],
            'first_contacted_at' => ['nullable', 'date'],
            'last_followed_up_at' => ['nullable', 'date'],
            'next_follow_up_at' => ['nullable', 'date'],
        ]);

        $stage = (string) $validated['lead_stage'];

        FounderLead::query()->create([
            'founder_id' => $user->id,
            'company_id' => $user->company?->id,
            'lead_name' => $validated['lead_name'],
            'lead_channel' => $validated['lead_channel'],
            'lead_stage' => $stage,
            'contact_handle' => $validated['contact_handle'] ?? null,
            'city' => $validated['city'] ?? null,
            'offer_name' => $validated['offer_name'] ?? null,
            'estimated_value' => $validated['estimated_value'] ?? null,
            'source_notes' => $validated['source_notes'] ?? null,
            'stage_notes' => $validated['stage_notes'] ?? null,
            'first_contacted_at' => $validated['first_contacted_at'] ?? null,
            'last_followed_up_at' => $validated['last_followed_up_at'] ?? null,
            'next_follow_up_at' => $validated['next_follow_up_at'] ?? null,
            'converted_at' => $stage === 'won' ? now() : null,
            'lost_at' => $stage === 'lost' ? now() : null,
        ]);

        return redirect()->route('founder.first-100')->with('success', 'Lead added to your Lead Tracker.');
    }

    public function founderUpdateLead(Request $request, FounderLead $lead, FounderRevenueOsService $revenueOsService): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        if ((int) $lead->founder_id !== (int) $user->id) {
            abort(404);
        }

        $validated = $request->validate([
            'lead_name' => ['sometimes', 'required', 'string', 'max:191'],
            'lead_channel' => ['sometimes', 'required', 'string', Rule::in(array_values(array_filter($revenueOsService->channelOptions($user->company?->verticalBlueprint), fn (string $value) => $value !== 'all')))],
            'lead_stage' => ['sometimes', 'required', 'string', Rule::in(array_values(array_filter($revenueOsService->stageOptions(), fn (string $value) => $value !== 'all')))],
            'contact_handle' => ['nullable', 'string', 'max:191'],
            'city' => ['nullable', 'string', 'max:191'],
            'offer_name' => ['nullable', 'string', 'max:191'],
            'estimated_value' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'source_notes' => ['nullable', 'string', 'max:2000'],
            'stage_notes' => ['nullable', 'string', 'max:2000'],
            'first_contacted_at' => ['nullable', 'date'],
            'last_followed_up_at' => ['nullable', 'date'],
            'next_follow_up_at' => ['nullable', 'date'],
        ]);

        $lead->fill($validated);
        if (array_key_exists('lead_stage', $validated)) {
            $stage = (string) $validated['lead_stage'];
            if ($stage === 'won' && $lead->converted_at === null) {
                $lead->converted_at = now();
            }
            if ($stage !== 'won') {
                $lead->converted_at = null;
            }
            if ($stage === 'lost' && $lead->lost_at === null) {
                $lead->lost_at = now();
            }
            if ($stage !== 'lost') {
                $lead->lost_at = null;
            }
        }

        $lead->save();

        return redirect()->route('founder.first-100')->with('success', 'Lead updated in your Lead Tracker.');
    }

    public function founderLogLeadTouch(Request $request, FounderLead $lead): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        if ((int) $lead->founder_id !== (int) $user->id) {
            abort(404);
        }

        $validated = $request->validate([
            'touch_type' => ['required', Rule::in(['outreach_sent', 'follow_up_sent', 'reply_received', 'proposal_sent', 'won', 'lost'])],
            'message_channel' => ['required', Rule::in(['manual', 'email', 'whatsapp', 'sms', 'instagram', 'facebook_groups', 'nextdoor', 'referral'])],
            'touch_note' => ['nullable', 'string', 'max:2000'],
            'next_follow_up_at' => ['nullable', 'date'],
        ]);

        $meta = is_array($lead->meta_json) ? $lead->meta_json : [];
        $touches = is_array($meta['touches'] ?? null) ? $meta['touches'] : [];
        $touches[] = [
            'type' => (string) $validated['touch_type'],
            'channel' => (string) $validated['message_channel'],
            'note' => trim((string) ($validated['touch_note'] ?? '')),
            'logged_at' => now()->toIso8601String(),
        ];

        $lead->last_followed_up_at = now();
        $lead->next_follow_up_at = $validated['next_follow_up_at'] ?? null;
        $lead->meta_json = array_merge($meta, ['touches' => array_slice($touches, -12)]);

        $stage = match ((string) $validated['touch_type']) {
            'outreach_sent' => 'contacted',
            'follow_up_sent' => 'contacted',
            'reply_received' => 'replied',
            'proposal_sent' => 'proposal_sent',
            'won' => 'won',
            'lost' => 'lost',
            default => (string) $lead->lead_stage,
        };

        $lead->lead_stage = $stage;
        if ($stage === 'won') {
            $lead->converted_at = now();
            $lead->lost_at = null;
            $lead->next_follow_up_at = null;
        } elseif ($stage === 'lost') {
            $lead->lost_at = now();
            $lead->converted_at = null;
            $lead->next_follow_up_at = null;
        } else {
            $lead->lost_at = null;
        }

        if (trim((string) ($validated['touch_note'] ?? '')) !== '') {
            $existing = trim((string) ($lead->stage_notes ?? ''));
            $prefix = '[' . now()->format('Y-m-d H:i') . '] ' . trim((string) $validated['touch_note']);
            $lead->stage_notes = $existing !== '' ? $prefix . "\n" . $existing : $prefix;
        }

        $lead->save();

        return redirect()->route('founder.first-100')->with('success', 'Conversation touch logged in your Lead Tracker.');
    }

    public function founderStorePromoLink(Request $request, FounderRevenueOsService $revenueOsService): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'source_channel' => ['required', Rule::in($revenueOsService->promoSourceChannels())],
            'promo_code' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9\-_]+$/'],
            'cta_label' => ['nullable', 'string', 'max:191'],
            'offer_title' => ['nullable', 'string', 'max:191'],
        ]);

        FounderPromoLink::query()->create([
            'founder_id' => $user->id,
            'company_id' => $user->company?->id,
            'title' => $validated['title'],
            'source_channel' => $validated['source_channel'],
            'promo_code' => strtoupper((string) $validated['promo_code']),
            'destination_path' => (string) ($user->company?->website_path ?? ''),
            'cta_label' => $validated['cta_label'] ?? null,
            'offer_title' => $validated['offer_title'] ?? null,
            'status' => 'active',
        ]);

        return redirect()->route('founder.first-100')->with('success', 'Promo link saved for offline-to-online tracking.');
    }

    public function founderApplyAcquisitionPlaybook(FounderLeadChannel $leadChannel): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        if ((int) $leadChannel->founder_id !== (int) $user->id) {
            abort(404);
        }

        $leadChannel->forceFill([
            'status' => 'adopted',
            'adopted_at' => now(),
            'last_used_at' => now(),
        ])->save();

        $launchSystem = FounderLaunchSystem::query()->firstOrNew([
            'founder_id' => $user->id,
            'status' => 'active',
        ]);

        $acquisitionSystem = is_array($launchSystem->acquisition_system_json) ? $launchSystem->acquisition_system_json : [];
        $adopted = collect($acquisitionSystem['adopted_channels'] ?? [])
            ->filter(fn ($channel) => is_array($channel))
            ->reject(fn (array $channel) => (string) ($channel['channel_key'] ?? '') === (string) $leadChannel->channel_key)
            ->values()
            ->all();
        $adopted[] = [
            'lead_channel_id' => $leadChannel->id,
            'channel_key' => (string) $leadChannel->channel_key,
            'channel_label' => (string) $leadChannel->channel_label,
            'daily_target' => (int) $leadChannel->daily_target,
            'adopted_at' => now()->toDateTimeString(),
        ];

        $launchSystem->forceFill([
            'company_id' => $user->company?->id,
            'vertical_blueprint_id' => $user->company?->vertical_blueprint_id,
            'selected_engine' => (string) ($user->company?->website_engine ?? ''),
            'status' => 'active',
            'acquisition_system_json' => array_merge($acquisitionSystem, [
                'priority_channel' => (string) $leadChannel->channel_key,
                'adopted_channels' => $adopted,
            ]),
            'last_reviewed_at' => now(),
            'applied_at' => $launchSystem->applied_at ?: now(),
        ])->save();

        return redirect()->route('founder.first-100')->with('success', $leadChannel->channel_label . ' playbook is now part of your active launch system.');
    }

    public function founderPromoLinkKit(FounderPromoLink $promoLink, FounderRevenueOsService $revenueOsService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        if ((int) $promoLink->founder_id !== (int) $user->id) {
            abort(403);
        }

        $kit = $revenueOsService->promoKit($user, $promoLink);

        return view('os.promo-kit', [
            'pageTitle' => (string) ($promoLink->title ?: 'Promo Kit'),
            'promoLink' => $promoLink,
            'kit' => $kit,
            'qrSvg' => $this->promoLinkQrSvgMarkup($kit['promo_url']),
        ]);
    }

    public function founderPromoLinkAsset(FounderPromoLink $promoLink, string $variant, FounderRevenueOsService $revenueOsService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        if ((int) $promoLink->founder_id !== (int) $user->id) {
            abort(403);
        }

        $allowedVariants = ['poster', 'referral', 'social', 'business'];
        if (!in_array($variant, $allowedVariants, true)) {
            abort(404);
        }

        $kit = $revenueOsService->promoKit($user, $promoLink);

        return view('os.promo-asset', [
            'pageTitle' => $kit['title'] . ' Asset',
            'variant' => $variant,
            'promoLink' => $promoLink,
            'kit' => $kit,
            'qrSvg' => $this->promoLinkQrSvgMarkup($kit['promo_url']),
        ]);
    }

    public function founderPromoLinkAssetSvg(FounderPromoLink $promoLink, string $variant, FounderRevenueOsService $revenueOsService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        if ((int) $promoLink->founder_id !== (int) $user->id) {
            abort(403);
        }

        $allowedVariants = ['poster', 'referral', 'social', 'business'];
        if (!in_array($variant, $allowedVariants, true)) {
            abort(404);
        }

        $kit = $revenueOsService->promoKit($user, $promoLink);
        $svg = $revenueOsService->promoAssetSvg($kit, $variant);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="' . $this->promoLinkDownloadBaseName($promoLink) . '-' . $variant . '.svg"',
        ]);
    }

    public function founderPromoLinkQrSvg(FounderPromoLink $promoLink, FounderRevenueOsService $revenueOsService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        if ((int) $promoLink->founder_id !== (int) $user->id) {
            abort(403);
        }

        $kit = $revenueOsService->promoKit($user, $promoLink);
        $svg = $this->promoLinkQrSvgMarkup($kit['promo_url']);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="' . $this->promoLinkDownloadBaseName($promoLink) . '-qr.svg"',
        ]);
    }

    public function founderPromoLinkQrPng(FounderPromoLink $promoLink, FounderRevenueOsService $revenueOsService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        if ((int) $promoLink->founder_id !== (int) $user->id) {
            abort(403);
        }

        $kit = $revenueOsService->promoKit($user, $promoLink);
        $png = QrCode::format('png')->size(700)->margin(1)->generate($kit['promo_url']);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="' . $this->promoLinkDownloadBaseName($promoLink) . '-qr.png"',
        ]);
    }

    public function founderLegacyTools(
        FounderDashboardService $founderDashboardService,
        WorkspaceLaunchService $workspaceLaunchService
    ) {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $dashboard = $founderDashboardService->build($user);
        $moduleCards = collect($dashboard['module_cards'] ?? [])->keyBy('key');
        $launchCards = collect($workspaceLaunchService->launchCards($user));

        $legacyModules = $launchCards->map(function (array $launch) use ($moduleCards): array {
            $moduleKey = strtolower((string) ($launch['module'] ?? ''));
            $card = $moduleCards->get($moduleKey, []);

            return [
                'module' => strtoupper($moduleKey),
                'label' => $launch['label'],
                'description' => $launch['description'],
                'status' => $card['status_label'] ?? 'Fallback only',
                'status_reason' => $card['status_reason'] ?? 'Only use this when the OS cannot complete the workflow yet.',
                'url' => route('workspace.launch', $moduleKey),
            ];
        })->values()->all();

        return view('os.legacy-tools', [
            'pageTitle' => 'Legacy Access',
            'dashboard' => $dashboard,
            'legacyModules' => $legacyModules,
            'workspaceTitle' => 'Founder Legacy Access',
            'workspaceIntro' => 'Use this page only when Hatchers Ai Business OS cannot complete a rare fallback step yet. Your normal weekly work should stay inside the OS.',
            'homeRoute' => route('dashboard.founder'),
        ]);
    }

    public function adminSubscribers(
        Request $request,
        AdminDashboardService $adminDashboardService
    ) {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'subscriber_reporting');

        return view('os.admin-subscribers', [
            'pageTitle' => 'Subscriber Reporting',
            'report' => $adminDashboardService->buildSubscriberReport($user, $request->query()),
        ]);
    }

    public function adminSystemAccess(AdminOperationsService $adminOperationsService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'system_access');

        return view('os.admin-system-access', [
            'pageTitle' => 'System Access',
            'workspace' => $adminOperationsService->buildSystemAccess($user),
        ]);
    }

    public function adminIdentity(AdminOperationsService $adminOperationsService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'system_access');

        return view('os.admin-identity', [
            'pageTitle' => 'Identity Workspace',
            'workspace' => $adminOperationsService->buildIdentityWorkspace($user),
        ]);
    }

    public function adminBlueprints(Request $request, AdminDashboardService $adminDashboardService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'commerce_control');

        return view('os.admin-blueprints', [
            'pageTitle' => 'Blueprint Control',
            'workspace' => $adminDashboardService->buildBlueprintWorkspace($user, $request->query()),
        ]);
    }

    public function adminStoreBlueprint(Request $request): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'commerce_control');

        $validated = $request->validate([
            'blueprint_id' => ['nullable', 'integer', 'exists:vertical_blueprints,id'],
            'code' => ['required', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:191'],
            'business_model' => ['required', Rule::in(['product', 'service', 'hybrid'])],
            'engine' => ['required', Rule::in(['bazaar', 'servio'])],
            'status' => ['required', Rule::in(['active', 'paused'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'change_summary' => ['nullable', 'string', 'max:2000'],
            'default_offer' => ['nullable', 'string', 'max:2000'],
            'default_pricing' => ['nullable', 'string', 'max:2000'],
            'default_pages' => ['nullable', 'string', 'max:2000'],
            'default_tasks' => ['nullable', 'string', 'max:2000'],
            'default_channels' => ['nullable', 'string', 'max:2000'],
            'default_cta' => ['nullable', 'string', 'max:2000'],
            'default_image_queries' => ['nullable', 'string', 'max:2000'],
            'funnel_framework' => ['nullable', 'string', 'max:4000'],
            'pricing_presets' => ['nullable', 'string', 'max:4000'],
            'channel_playbooks' => ['nullable', 'string', 'max:4000'],
            'script_library' => ['nullable', 'string', 'max:4000'],
        ]);

        $blueprint = VerticalBlueprint::query()->find((int) ($validated['blueprint_id'] ?? 0));
        $payload = [
            'code' => strtolower(trim((string) $validated['code'])),
            'name' => trim((string) $validated['name']),
            'business_model' => (string) $validated['business_model'],
            'engine' => (string) $validated['engine'],
            'status' => (string) $validated['status'],
            'description' => trim((string) ($validated['description'] ?? '')),
            'default_offer_json' => $this->adminBlueprintList((string) ($validated['default_offer'] ?? '')),
            'default_pricing_json' => $this->adminBlueprintList((string) ($validated['default_pricing'] ?? '')),
            'default_pages_json' => $this->adminBlueprintList((string) ($validated['default_pages'] ?? '')),
            'default_tasks_json' => $this->adminBlueprintList((string) ($validated['default_tasks'] ?? '')),
            'default_channels_json' => $this->adminBlueprintList((string) ($validated['default_channels'] ?? '')),
            'default_cta_json' => $this->adminBlueprintList((string) ($validated['default_cta'] ?? '')),
            'default_image_queries_json' => $this->adminBlueprintList((string) ($validated['default_image_queries'] ?? '')),
            'funnel_framework_json' => $this->adminBlueprintList((string) ($validated['funnel_framework'] ?? '')),
            'pricing_preset_json' => $this->adminBlueprintList((string) ($validated['pricing_presets'] ?? '')),
            'channel_playbook_json' => $this->adminBlueprintList((string) ($validated['channel_playbooks'] ?? '')),
            'script_library_json' => $this->adminBlueprintList((string) ($validated['script_library'] ?? '')),
        ];

        $blueprint = VerticalBlueprint::updateOrCreate(['id' => $blueprint?->id], array_merge($payload, [
            'version_number' => ($blueprint?->version_number ?? 0) + 1,
        ]));

        VerticalBlueprintVersion::query()->create([
            'vertical_blueprint_id' => $blueprint->id,
            'version_number' => (int) $blueprint->version_number,
            'version_label' => 'v' . $blueprint->version_number,
            'change_summary' => trim((string) ($validated['change_summary'] ?? 'Blueprint update from OS')),
            'snapshot_json' => $payload,
            'created_by_founder_id' => $user->id,
        ]);

        FounderPod::query()->firstOrCreate(
            [
                'slug' => strtolower(trim((string) $blueprint->code)) . '-launchers',
            ],
            [
                'name' => $blueprint->name . ' Launchers',
                'vertical_blueprint_id' => $blueprint->id,
                'stage' => 'early_launch',
                'status' => 'active',
                'description' => 'Founders in this vertical share wins, blockers, and scripts while building their first 100 customers.',
                'benchmark_json' => [
                    'weekly_lead_target' => 10,
                    'first_customer_goal' => 1,
                ],
            ]
        );

        return redirect()->route('admin.blueprints')->with('success', 'Blueprint settings saved from the OS.');
    }

    public function adminReviewFounderBlueprint(Request $request): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'commerce_control');

        $validated = $request->validate([
            'founder_id' => ['required', 'integer', 'exists:founders,id'],
            'launch_stage' => ['required', Rule::in(['brief_pending', 'brief_captured', 'generation_ready', 'website_review', 'live'])],
            'website_generation_status' => ['required', Rule::in(['not_started', 'queued', 'in_progress', 'ready_for_review', 'published'])],
            'vertical_blueprint_id' => ['nullable', 'integer', 'exists:vertical_blueprints,id'],
        ]);

        $founder = Founder::query()->with('company')->findOrFail((int) $validated['founder_id']);
        if (!$founder->company) {
            return redirect()->route('admin.blueprints')->with('error', 'This founder does not have a company record yet.');
        }

        $founder->company->forceFill([
            'vertical_blueprint_id' => $validated['vertical_blueprint_id'] ?: $founder->company->vertical_blueprint_id,
            'launch_stage' => (string) $validated['launch_stage'],
            'website_generation_status' => (string) $validated['website_generation_status'],
        ])->save();

        return redirect()->route('admin.blueprints')->with('success', 'Founder launch review updated.');
    }

    public function adminBackfillIdentity(
        AdminOperationsService $adminOperationsService,
        OsOperationsLogService $logService
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'system_access');

        $result = $adminOperationsService->backfillIdentityMetadata();

        $logService->recordAudit(
            $user,
            'identity_backfill',
            'identity',
            null,
            'Admin backfilled OS identity metadata.',
            [
                'updated' => $result['updated'],
                'unchanged' => $result['unchanged'],
            ]
        );

        return redirect()->route('admin.identity')->with('success', $result['message']);
    }

    public function adminModules(AdminDashboardService $adminDashboardService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'module_monitoring');

        return view('os.admin-modules', [
            'pageTitle' => 'Module Monitoring',
            'workspace' => $adminDashboardService->buildModuleMonitoring($user),
        ]);
    }

    public function adminCommerce(Request $request, AdminDashboardService $adminDashboardService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'commerce_control');

        return view('os.admin-commerce', [
            'pageTitle' => 'Commerce Control',
            'workspace' => $adminDashboardService->buildCommerceWorkspace($user, $request->query()),
        ]);
    }

    public function adminFinance(Request $request, AdminDashboardService $adminDashboardService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'commerce_control');

        return view('os.admin-finance', [
            'pageTitle' => 'Finance Control',
            'workspace' => $adminDashboardService->buildFinanceWorkspace($user, $request->query()),
        ]);
    }

    public function adminFinanceExport(Request $request, AdminDashboardService $adminDashboardService): StreamedResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'commerce_control');

        $validated = $request->validate([
            'dataset' => ['required', Rule::in(['wallets', 'payouts', 'ledger', 'checkouts'])],
            'search' => ['nullable', 'string', 'max:255'],
            'payout_status' => ['nullable', 'string', 'max:32'],
            'checkout_status' => ['nullable', 'string', 'max:32'],
        ]);

        $workspace = $adminDashboardService->buildFinanceWorkspace($user, $request->only(['search', 'payout_status', 'checkout_status']));
        $dataset = (string) $validated['dataset'];
        $filename = 'hatchers-os-' . $dataset . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($workspace, $dataset): void {
            $handle = fopen('php://output', 'w');

            if ($dataset === 'wallets') {
                fputcsv($handle, ['Founder', 'Company', 'Email', 'Business Model', 'Currency', 'Available', 'Reserved', 'Pending', 'Gross Sales', 'Refunded Sales', 'Platform Fees', 'Net Earnings', 'Stripe Status', 'Payout Rail']);
                foreach ($workspace['wallet_rows'] as $row) {
                    fputcsv($handle, [
                        $row['founder_name'],
                        $row['company_name'],
                        $row['email'],
                        $row['business_model'],
                        $row['currency'],
                        $row['available_balance'],
                        $row['reserved_balance'],
                        $row['pending_balance'],
                        $row['gross_sales_total'],
                        $row['refunded_sales_total'],
                        $row['platform_fees_total'],
                        $row['net_earnings_total'],
                        $row['stripe_status'],
                        $row['bank_summary'],
                    ]);
                }
            } elseif ($dataset === 'payouts') {
                fputcsv($handle, ['Founder', 'Company', 'Amount', 'Currency', 'Status', 'Requested At', 'Processed At', 'Destination', 'Reference', 'Rejection Reason', 'Notes']);
                foreach ($workspace['payout_requests'] as $row) {
                    fputcsv($handle, [
                        $row['founder_name'],
                        $row['company_name'],
                        $row['amount'],
                        $row['currency'],
                        $row['status'],
                        $row['requested_at'],
                        $row['processed_at'],
                        $row['destination_summary'],
                        $row['reference'],
                        $row['rejection_reason'],
                        $row['notes'],
                    ]);
                }
            } elseif ($dataset === 'ledger') {
                fputcsv($handle, ['Founder', 'Company', 'Platform', 'Category', 'Reference', 'Entry Type', 'Amount', 'Currency', 'Status', 'Created At']);
                foreach ($workspace['recent_ledger_entries'] as $row) {
                    fputcsv($handle, [
                        $row['founder_name'],
                        $row['company_name'],
                        $row['source_platform'],
                        $row['source_category'],
                        $row['source_reference'],
                        $row['entry_type'],
                        $row['amount'],
                        $row['currency'],
                        $row['status'],
                        $row['created_at'],
                    ]);
                }
            } else {
                fputcsv($handle, ['Founder', 'Company', 'Offer', 'Platform', 'Category', 'Amount', 'Currency', 'Checkout Status', 'Stripe Session', 'Payment Intent', 'Commerce Reference', 'Customer Name', 'Customer Email', 'Created At', 'Completed At', 'Reviewed', 'Reviewed At', 'Reviewed By', 'Review Note']);
                foreach ($workspace['checkout_rows'] as $row) {
                    fputcsv($handle, [
                        $row['founder_name'],
                        $row['company_name'],
                        $row['offer_title'],
                        $row['platform'],
                        $row['category'],
                        $row['amount'],
                        $row['currency'],
                        $row['checkout_status'],
                        $row['stripe_session_id'],
                        $row['stripe_payment_intent_id'],
                        $row['commerce_reference'],
                        $row['customer_name'],
                        $row['customer_email'],
                        $row['created_at'],
                        $row['completed_at'],
                        $row['reviewed'] ? 'yes' : 'no',
                        $row['reviewed_at'],
                        $row['reviewed_by'],
                        $row['review_note'],
                    ]);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function adminFinanceReviewCheckout(
        Request $request,
        PublicCheckoutSession $checkoutSession,
        OsOperationsLogService $logService
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'commerce_control');

        $validated = $request->validate([
            'review_action' => ['required', Rule::in(['reviewed', 'reopen'])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $payload = is_array($checkoutSession->payload_json) ? $checkoutSession->payload_json : [];
        $reviewAction = (string) $validated['review_action'];
        $payload['finance_review'] = [
            'reviewed' => $reviewAction === 'reviewed',
            'reviewed_at' => $reviewAction === 'reviewed' ? now()->toDateTimeString() : null,
            'reviewed_by' => $reviewAction === 'reviewed' ? $user->full_name : null,
            'reviewed_by_id' => $reviewAction === 'reviewed' ? $user->id : null,
            'note' => trim((string) ($validated['note'] ?? '')),
        ];

        $checkoutSession->forceFill([
            'payload_json' => $payload,
        ])->save();

        $logService->recordAudit(
            $user,
            'admin_finance_checkout_review',
            'stripe',
            $checkoutSession->founder_id,
            'Admin marked checkout session #' . $checkoutSession->id . ' as ' . ($reviewAction === 'reviewed' ? 'reviewed' : 'open') . '.',
            [
                'checkout_session_id' => $checkoutSession->id,
                'checkout_status' => (string) $checkoutSession->checkout_status,
                'review_action' => $reviewAction,
                'note' => trim((string) ($validated['note'] ?? '')),
            ]
        );

        return redirect()->route('admin.finance', $request->only(['search', 'payout_status', 'checkout_status']))->with('success', 'Checkout finance review updated.');
    }

    public function adminFinanceAdjustment(
        Request $request,
        OsWalletService $walletService,
        OsOperationsLogService $logService
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'commerce_control');

        $validated = $request->validate([
            'founder_id' => ['required', 'integer', 'exists:founders,id'],
            'entry_type' => ['required', Rule::in(['credit', 'debit'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'max:8'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $founder = Founder::query()->where('role', 'founder')->findOrFail((int) $validated['founder_id']);
        $amount = round((float) $validated['amount'], 2);
        $currency = strtoupper(trim((string) ($validated['currency'] ?: $walletService->summary($founder)['currency'])));
        $entryType = (string) $validated['entry_type'];
        $signedAmount = $entryType === 'debit' ? -1 * $amount : $amount;

        if ($entryType === 'debit') {
            $available = (float) $walletService->summary($founder)['available_balance'];
            if ($amount > $available) {
                return redirect()->route('admin.finance')->with('error', 'This debit exceeds the founder available wallet balance.');
            }
        }

        \App\Models\FounderWalletLedger::create([
            'founder_id' => $founder->id,
            'company_id' => $founder->company?->id,
            'source_platform' => 'os',
            'source_category' => 'manual_adjustment',
            'source_reference' => 'admin:' . $user->id . ':' . now()->format('YmdHis'),
            'entry_type' => $entryType,
            'amount' => round($signedAmount, 2),
            'currency' => $currency !== '' ? $currency : 'USD',
            'status' => 'available',
            'available_at' => now(),
            'meta_json' => [
                'reason' => (string) $validated['reason'],
                'admin_id' => $user->id,
                'admin_name' => $user->full_name,
            ],
        ]);

        $logService->recordAudit(
            $user,
            'admin_finance_adjustment',
            'os',
            $founder->id,
            'Admin posted a manual wallet ' . $entryType . ' for ' . $founder->full_name . '.',
            [
                'amount' => $amount,
                'currency' => $currency,
                'entry_type' => $entryType,
                'reason' => (string) $validated['reason'],
            ]
        );

        return redirect()->route('admin.finance')->with('success', 'Manual wallet adjustment posted for ' . $founder->full_name . '.');
    }

    public function adminSupport(AdminDashboardService $adminDashboardService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'exception_resolution');

        $workspace = $adminDashboardService->buildSupportWorkspace($user);
        $workspace['mail_diagnostics'] = $this->mailDiagnostics();

        return view('os.admin-support', [
            'pageTitle' => 'Support Center',
            'workspace' => $workspace,
        ]);
    }

    public function adminStoreCommerceCatalog(
        Request $request,
        OsAssistantActionService $assistantActionService,
        OsOperationsLogService $logService
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'commerce_control');

        $validated = $request->validate([
            'founder_id' => ['required', 'integer', 'exists:founders,id'],
            'platform' => ['required', Rule::in(['bazaar', 'servio'])],
            'resource' => ['required', Rule::in(['category', 'tax', 'staff'])],
            'title' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'tax_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:50'],
        ]);

        $founder = Founder::query()->where('role', 'founder')->findOrFail((int) $validated['founder_id']);
        $platform = (string) $validated['platform'];
        $resource = (string) $validated['resource'];

        $attributes = [
            'title' => (string) $validated['title'],
        ];

        if ($resource === 'tax') {
            $attributes['value'] = (string) ($validated['value'] ?? '');
            $attributes['type'] = (string) ($validated['tax_type'] ?? 'percent');
        }

        if ($resource === 'staff') {
            $attributes['email'] = (string) ($validated['email'] ?? '');
            $attributes['mobile'] = (string) ($validated['mobile'] ?? '');
        }

        $result = $assistantActionService->createCommerceCatalogEntryFromOs(
            $founder,
            $platform,
            $resource,
            $attributes,
            'admin'
        );

        if (!($result['success'] ?? false)) {
            return redirect()
                ->route('admin.commerce', $request->only(['search', 'business_model', 'engine']))
                ->with('error', $result['reply'] ?? 'The OS could not create that commerce catalog item.');
        }

        $logService->recordAudit(
            $user,
            'admin_commerce_catalog_create',
            $platform,
            $founder->id,
            'Admin created a ' . $resource . ' for ' . $founder->full_name . ' in ' . strtoupper($platform) . '.',
            [
                'platform' => $platform,
                'resource' => $resource,
                'title' => $attributes['title'],
            ]
        );

        return redirect()
            ->route('admin.commerce', $request->only(['search', 'business_model', 'engine']))
            ->with('success', $result['reply'] ?? 'Commerce catalog item created.');
    }

    public function adminUpdateCommerceCatalog(
        Request $request,
        OsAssistantActionService $assistantActionService,
        OsOperationsLogService $logService
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'commerce_control');

        $validated = $request->validate([
            'founder_id' => ['required', 'integer', 'exists:founders,id'],
            'platform' => ['required', Rule::in(['bazaar', 'servio'])],
            'resource' => ['required', Rule::in(['category', 'tax', 'staff'])],
            'target_name' => ['required', 'string', 'max:255'],
            'field' => ['required', Rule::in(['title', 'name', 'status', 'value', 'type', 'email', 'mobile'])],
            'value' => ['required', 'string', 'max:255'],
        ]);

        $founder = Founder::query()->where('role', 'founder')->findOrFail((int) $validated['founder_id']);
        $platform = (string) $validated['platform'];
        $resource = (string) $validated['resource'];
        $targetName = (string) $validated['target_name'];
        $field = (string) $validated['field'];
        $value = (string) $validated['value'];

        $result = $assistantActionService->updateCommerceCatalogEntryFromOs(
            $founder,
            $platform,
            $resource,
            $targetName,
            $field,
            $value,
            'admin'
        );

        if (!($result['success'] ?? false)) {
            return redirect()
                ->route('admin.commerce', $request->only(['search', 'business_model', 'engine']))
                ->with('error', $result['reply'] ?? 'The OS could not update that commerce catalog item.');
        }

        $logService->recordAudit(
            $user,
            'admin_commerce_catalog_update',
            $platform,
            $founder->id,
            'Admin updated a ' . $resource . ' for ' . $founder->full_name . ' in ' . strtoupper($platform) . '.',
            [
                'platform' => $platform,
                'resource' => $resource,
                'target_name' => $targetName,
                'field' => $field,
                'value' => $value,
            ]
        );

        return redirect()
            ->route('admin.commerce', $request->only(['search', 'business_model', 'engine']))
            ->with('success', $result['reply'] ?? 'Commerce catalog item updated.');
    }

    public function adminUpdateCommerceOffer(
        Request $request,
        OsAssistantActionService $assistantActionService,
        OsOperationsLogService $logService
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'commerce_control');

        $validated = $request->validate([
            'founder_id' => ['required', 'integer', 'exists:founders,id'],
            'platform' => ['required', Rule::in(['bazaar', 'servio'])],
            'target_name' => ['required', 'string', 'max:255'],
            'field' => ['required', Rule::in(['variants', 'extras', 'additional_services', 'staff_ids', 'availability_days', 'open_time', 'close_time', 'status'])],
            'value' => ['required', 'string', 'max:4000'],
        ]);

        $founder = Founder::query()->where('role', 'founder')->findOrFail((int) $validated['founder_id']);
        $platform = (string) $validated['platform'];
        $targetName = (string) $validated['target_name'];
        $field = (string) $validated['field'];
        $value = (string) $validated['value'];

        $result = $platform === 'bazaar'
            ? $assistantActionService->updateProductFieldFromOs($founder, $targetName, $field, $value, 'admin')
            : $assistantActionService->updateServiceFieldFromOs($founder, $targetName, $field, $value, 'admin');

        if (!($result['success'] ?? false)) {
            return redirect()
                ->route('admin.commerce', $request->only(['search', 'business_model', 'engine']))
                ->with('error', $result['reply'] ?? 'The OS could not update that commerce offer.');
        }

        $logService->recordAudit(
            $user,
            'admin_commerce_offer_update',
            $platform,
            $founder->id,
            'Admin updated a ' . strtoupper($platform) . ' offer field for ' . $founder->full_name . '.',
            [
                'platform' => $platform,
                'target_name' => $targetName,
                'field' => $field,
                'value' => $value,
            ]
        );

        return redirect()
            ->route('admin.commerce', $request->only(['search', 'business_model', 'engine']))
            ->with('success', $result['reply'] ?? 'Commerce offer updated.');
    }

    public function adminUpdateCommerceOperation(
        Request $request,
        OsAssistantActionService $assistantActionService,
        OsOperationsLogService $logService
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'commerce_control');

        $validated = $request->validate([
            'founder_id' => ['required', 'integer', 'exists:founders,id'],
            'platform' => ['required', Rule::in(['bazaar', 'servio'])],
            'category' => ['required', Rule::in(['order', 'booking', 'shipping'])],
            'target_name' => ['required', 'string', 'max:255'],
            'field' => ['required', Rule::in(['status', 'payment_status', 'vendor_note', 'delivery_date', 'delivery_time', 'staff_id', 'booking_date', 'booking_time', 'booking_endtime', 'booking_notes', 'customer_message', 'area_name', 'delivery_charge'])],
            'value' => ['required', 'string', 'max:4000'],
            'message_channel' => ['nullable', Rule::in(['manual', 'email', 'whatsapp', 'sms'])],
        ]);

        $founder = Founder::query()->where('role', 'founder')->findOrFail((int) $validated['founder_id']);
        $platform = (string) $validated['platform'];
        $category = (string) $validated['category'];
        $targetName = (string) $validated['target_name'];
        $field = (string) $validated['field'];
        $value = (string) $validated['value'];

        $result = $assistantActionService->updateCommerceOperationFromOs(
            $founder,
            $platform,
            $category,
            $targetName,
            $field,
            $value,
            [
                'message_channel' => (string) ($validated['message_channel'] ?? 'manual'),
            ],
            'admin'
        );

        if (!($result['success'] ?? false)) {
            return redirect()
                ->route('admin.commerce', $request->only(['search', 'business_model', 'engine']))
                ->with('error', $result['reply'] ?? 'The OS could not update that commerce operation.');
        }

        $logService->recordAudit(
            $user,
            'admin_commerce_operation_update',
            $platform,
            $founder->id,
            'Admin updated a ' . $category . ' for ' . $founder->full_name . ' in ' . strtoupper($platform) . '.',
            [
                'platform' => $platform,
                'category' => $category,
                'target_name' => $targetName,
                'field' => $field,
                'value' => $value,
                'message_channel' => (string) ($validated['message_channel'] ?? 'manual'),
            ]
        );

        return redirect()
            ->route('admin.commerce', $request->only(['search', 'business_model', 'engine']))
            ->with('success', $result['reply'] ?? 'Commerce operation updated.');
    }

    public function adminSendSupportTestMail(Request $request): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'exception_resolution');

        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            Mail::send('emails.admin-test-mail', [
                'admin' => $user,
                'sentAt' => now(),
                'diagnostics' => $this->mailDiagnostics(),
            ], function ($message) use ($validated): void {
                $message->to((string) $validated['email'])->subject('Hatchers Ai Business OS test mail');
            });
        } catch (Throwable $exception) {
            Log::error('Admin support test mail failed.', [
                'email' => $validated['email'],
                'message' => $exception->getMessage(),
            ]);

            return redirect()->route('admin.support')->with('error', 'The OS could not send the test mail. Please review the SMTP setup and try again.');
        }

        return redirect()->route('admin.support')->with('success', 'Test mail sent to ' . $validated['email'] . '.');
    }

    public function adminApprovePayoutRequest(
        Request $request,
        FounderPayoutRequest $payoutRequest,
        OsWalletService $walletService,
        OsOperationsLogService $logService
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'exception_resolution');

        if (!in_array((string) $payoutRequest->status, ['pending', 'processing'], true)) {
            return redirect()->route('admin.support')->with('error', 'That payout request is no longer waiting for action.');
        }

        $validated = $request->validate([
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        $walletService->markPayoutPaid($payoutRequest, (string) ($validated['reference'] ?? ''));

        $logService->recordAudit(
            $user,
            'admin_payout_paid',
            'os',
            $payoutRequest->founder_id,
            'Admin marked payout request #' . $payoutRequest->id . ' as paid.',
            [
                'payout_request_id' => $payoutRequest->id,
                'amount' => (float) $payoutRequest->amount,
                'currency' => (string) $payoutRequest->currency,
                'reference' => (string) ($validated['reference'] ?? ''),
            ]
        );

        return redirect()->route('admin.support')->with('success', 'Payout request #' . $payoutRequest->id . ' marked as paid.');
    }

    public function adminRejectPayoutRequest(
        Request $request,
        FounderPayoutRequest $payoutRequest,
        OsWalletService $walletService,
        OsOperationsLogService $logService
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'exception_resolution');

        if (!in_array((string) $payoutRequest->status, ['pending', 'processing'], true)) {
            return redirect()->route('admin.support')->with('error', 'That payout request is no longer waiting for action.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $walletService->rejectPayout($payoutRequest, (string) $validated['reason']);

        $logService->recordAudit(
            $user,
            'admin_payout_rejected',
            'os',
            $payoutRequest->founder_id,
            'Admin rejected payout request #' . $payoutRequest->id . '.',
            [
                'payout_request_id' => $payoutRequest->id,
                'amount' => (float) $payoutRequest->amount,
                'currency' => (string) $payoutRequest->currency,
                'reason' => (string) $validated['reason'],
            ]
        );

        return redirect()->route('admin.support')->with('success', 'Payout request #' . $payoutRequest->id . ' was rejected and the founder wallet was restored.');
    }

    public function founderUpdateTaskStatus(
        Request $request,
        FounderActionPlan $actionPlan,
        OsAssistantActionService $actionService,
        AtlasIntelligenceService $atlas
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        if ((int) $actionPlan->founder_id !== (int) $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['completed', 'pending'])],
        ]);

        return $this->applyFounderExecutionStatus(
            $user,
            $actionPlan,
            (string) $validated['status'],
            'task',
            $actionService,
            $atlas
        );
    }

    public function founderUpdateLearningStatus(
        Request $request,
        FounderActionPlan $actionPlan,
        OsAssistantActionService $actionService,
        AtlasIntelligenceService $atlas
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        if ((int) $actionPlan->founder_id !== (int) $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['completed', 'pending'])],
        ]);

        return $this->applyFounderExecutionStatus(
            $user,
            $actionPlan,
            (string) $validated['status'],
            'lesson',
            $actionService,
            $atlas
        );
    }

    public function founderAiTools(
        FounderDashboardService $founderDashboardService,
        WorkspaceLaunchService $workspaceLaunchService,
        AtlasWorkspaceService $atlasWorkspaceService
    ) {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }
        if ($redirect = $this->ensureCompanyIntelligenceComplete($user)) {
            return $redirect;
        }

        $dashboard = $founderDashboardService->build($user);
        $atlasWorkspace = $atlasWorkspaceService->summary($user, $dashboard['atlas'] ?? []);

        return view('os.ai-tools', [
            'pageTitle' => 'AI Tools',
            'dashboard' => $dashboard,
            'launchCards' => $workspaceLaunchService->launchCards($user),
            'atlasWorkspace' => $atlasWorkspace,
        ]);
    }

    public function founderAtlasWorkspace(
        Request $request,
        FounderDashboardService $founderDashboardService,
        WorkspaceLaunchService $workspaceLaunchService
    ) {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }
        if ($redirect = $this->ensureCompanyIntelligenceComplete($user)) {
            return $redirect;
        }

        $target = $this->sanitizeAtlasWorkspaceTarget((string) $request->query('target', '/dashboard'));
        $launchUrl = $workspaceLaunchService->buildLaunchUrlForTarget($user, 'atlas', $target);
        if ($launchUrl === null) {
            return redirect()->route('founder.ai-tools')->with('error', 'Atlas workspace launch is not configured yet.');
        }

        return view('os.atlas-workspace', [
            'pageTitle' => (string) $request->query('title', 'Atlas Workspace'),
            'dashboard' => $founderDashboardService->build($user),
            'workspaceLabel' => (string) $request->query('title', 'Atlas Workspace'),
            'launchUrl' => $launchUrl,
            'proxyUrl' => route('founder.ai-tools.proxy', ['proxyPath' => '']) . '?target=' . rawurlencode($target),
            'target' => $target,
        ]);
    }

    public function founderAtlasWorkspaceProxy(
        Request $request,
        WorkspaceLaunchService $workspaceLaunchService
    ) {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $proxyPath = trim((string) $request->route('proxyPath', ''), '/');
        $target = $this->sanitizeAtlasWorkspaceTarget((string) $request->query('target', '/dashboard'));
        $atlasBaseUrl = rtrim((string) config('modules.atlas.base_url'), '/');
        if ($atlasBaseUrl === '') {
            return redirect()->route('founder.ai-tools')->with('error', 'Atlas workspace is not configured yet.');
        }

        $cookiePrefix = 'hatchers_atlas_workspace_';
        $upstreamCookieHeader = $this->upstreamWorkspaceCookieHeader($request, $cookiePrefix);

        if ($proxyPath === '') {
            $targetUrl = $upstreamCookieHeader !== ''
                ? $atlasBaseUrl . $target
                : $workspaceLaunchService->buildLaunchUrlForTarget($user, 'atlas', $target);
        } else {
            $targetUrl = $atlasBaseUrl . '/' . $proxyPath;
        }

        if (!$targetUrl) {
            return redirect()->route('founder.ai-tools')->with('error', 'Atlas workspace launch is not configured yet.');
        }

        $forwardHeaders = array_filter([
            'Accept' => $request->header('Accept'),
            'Accept-Language' => $request->header('Accept-Language'),
            'User-Agent' => $request->userAgent(),
            'Referer' => $request->header('Referer'),
            'X-Requested-With' => $request->header('X-Requested-With'),
        ], static fn ($value) => filled($value));

        if ($upstreamCookieHeader !== '') {
            $forwardHeaders['Cookie'] = $upstreamCookieHeader;
        }

        $method = strtoupper($request->method());
        $options = [
            'allow_redirects' => false,
            'query' => $this->atlasProxyQuery($request, $proxyPath === ''),
        ];

        $client = Http::withHeaders($forwardHeaders)->withOptions($options);
        if (!in_array($method, ['GET', 'HEAD'], true)) {
            $contentType = strtolower((string) $request->header('Content-Type', ''));
            if (str_contains($contentType, 'application/json')) {
                $options['body'] = $request->getContent();
                $forwardHeaders['Content-Type'] = $request->header('Content-Type');
                $client = Http::withHeaders($forwardHeaders)->withOptions($options);
            } else {
                $client = Http::withHeaders($forwardHeaders)->withOptions($options)->asForm();
                $options['form_params'] = $request->except(['_token']);
            }
        }

        $upstream = $client->send($method, $targetUrl, $options);
        $status = $upstream->status();
        $contentType = strtolower((string) $upstream->header('Content-Type', 'text/html; charset=UTF-8'));

        if ($status >= 300 && $status < 400 && filled($upstream->header('Location'))) {
            $location = $this->rewriteAtlasUrlToOsPath((string) $upstream->header('Location'), $atlasBaseUrl);
            return redirect()->away($location, $status);
        }

        $body = $upstream->body();
        if (str_contains($contentType, 'text/html')) {
            $body = $this->rewriteAtlasHtmlForOs($body, $atlasBaseUrl);
        } elseif (
            str_contains($contentType, 'javascript')
            || str_contains($contentType, 'json')
            || str_contains($contentType, 'css')
            || str_contains($contentType, 'svg')
        ) {
            $body = $this->rewriteAtlasTextForOs($body, $atlasBaseUrl);
        }

        $response = response($body, $status);

        foreach ($upstream->headers() as $header => $values) {
            $headerName = (string) $header;
            if (in_array(strtolower($headerName), ['content-length', 'transfer-encoding', 'content-encoding', 'set-cookie', 'x-frame-options'], true)) {
                continue;
            }

            if (strtolower($headerName) === 'content-security-policy') {
                continue;
            }

            foreach ((array) $values as $value) {
                $response->headers->set($headerName, $value, false);
            }
        }

        $setCookieHeaders = $upstream->headers()['Set-Cookie'] ?? [];
        foreach ((array) $setCookieHeaders as $cookieLine) {
            $response->headers->setCookie($this->rewriteAtlasWorkspaceCookie((string) $cookieLine, $request, $cookiePrefix));
        }

        return $response;
    }

    public function founderSearch(Request $request, FounderDashboardService $founderDashboardService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }
        if ($redirect = $this->ensureCompanyIntelligenceComplete($user)) {
            return $redirect;
        }

        $dashboard = $founderDashboardService->build($user);
        $query = trim((string) $request->query('q', ''));

        return view('os.search', [
            'pageTitle' => 'Search',
            'dashboard' => $dashboard,
            'searchQuery' => $query,
            'results' => $this->buildFounderSearchResults($user, $dashboard, $query),
        ]);
    }

    public function founderMediaLibrary(
        FounderDashboardService $founderDashboardService,
        WebsiteWorkspaceService $websiteWorkspaceService,
        AtlasWorkspaceService $atlasWorkspaceService
    ) {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }
        if ($redirect = $this->ensureCompanyIntelligenceComplete($user)) {
            return $redirect;
        }

        $dashboard = $founderDashboardService->build($user);
        $atlasWorkspace = $atlasWorkspaceService->summary($user, $dashboard['atlas'] ?? []);

        return view('os.media-library', [
            'pageTitle' => 'Media Library',
            'dashboard' => $dashboard,
            'website' => $websiteWorkspaceService->build($user),
            'assets' => $this->buildFounderMediaAssets($user, $dashboard),
            'atlasWorkspace' => $atlasWorkspace,
        ]);
    }

    public function founderAnalytics(FounderDashboardService $founderDashboardService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }
        if ($redirect = $this->ensureCompanyIntelligenceComplete($user)) {
            return $redirect;
        }

        $dashboard = $founderDashboardService->build($user);

        return view('os.analytics', [
            'pageTitle' => 'Analytics',
            'dashboard' => $dashboard,
            'analytics' => $this->buildFounderAnalyticsWorkspace($dashboard),
        ]);
    }

    public function founderAutomations(FounderDashboardService $founderDashboardService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }
        if ($redirect = $this->ensureCompanyIntelligenceComplete($user)) {
            return $redirect;
        }

        return view('os.automations', [
            'pageTitle' => 'Automations',
            'dashboard' => $founderDashboardService->build($user),
            'automations' => $user->automationRules()->latest()->get(),
            'triggerOptions' => $this->automationTriggerOptions(),
            'scopeOptions' => $this->automationScopeOptions(),
            'recommendedTemplates' => $this->automationTemplates(),
        ]);
    }

    public function founderStoreAutomation(Request $request): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trigger_type' => ['required', Rule::in(array_keys($this->automationTriggerOptions()))],
            'module_scope' => ['required', Rule::in(array_keys($this->automationScopeOptions()))],
            'condition_summary' => ['required', 'string', 'max:2000'],
            'action_summary' => ['required', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'paused'])],
        ]);

        $user->automationRules()->create($validated);

        return redirect()
            ->route('founder.automations')
            ->with('success', 'Automation rule saved inside Hatchers Ai Business OS.');
    }

    public function founderStoreAutomationTemplate(Request $request): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'template_key' => ['required', Rule::in(array_keys($this->automationTemplates()))],
        ]);

        $template = $this->automationTemplates()[$validated['template_key']];

        $user->automationRules()->create([
            'name' => $template['name'],
            'trigger_type' => $template['trigger_type'],
            'module_scope' => $template['module_scope'],
            'condition_summary' => $template['condition_summary'],
            'action_summary' => $template['action_summary'],
            'status' => 'active',
            'metadata_json' => [
                'template_key' => $validated['template_key'],
                'delivery' => $template['delivery'],
            ],
        ]);

        return redirect()
            ->route('founder.automations')
            ->with('success', $template['name'] . ' automation saved inside Hatchers Ai Business OS.');
    }

    public function mentorFounderDetail(
        Founder $founder,
        MentorDashboardService $mentorDashboardService
    ) {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isMentor()) {
            return redirect()->route('dashboard');
        }

        return view('os.mentor-founder', [
            'pageTitle' => 'Founder Detail',
            'workspace' => $mentorDashboardService->buildFounderDetail($user, $founder),
        ]);
    }

    public function mentorLegacyTools(
        MentorDashboardService $mentorDashboardService,
        WorkspaceLaunchService $workspaceLaunchService
    ) {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isMentor()) {
            return redirect()->route('dashboard');
        }

        $dashboard = $mentorDashboardService->build($user);
        $launchCards = collect($workspaceLaunchService->launchCards($user))
            ->map(fn (array $launch): array => [
                'module' => strtoupper((string) ($launch['module'] ?? '')),
                'label' => $launch['label'],
                'description' => $launch['description'],
                'status' => 'Fallback only',
                'status_reason' => 'Mentor work should stay in the OS. Use this only when support asks for a backend check.',
                'url' => route('workspace.launch', strtolower((string) ($launch['module'] ?? ''))),
            ])
            ->values()
            ->all();

        return view('os.legacy-tools', [
            'pageTitle' => 'Mentor Legacy Access',
            'dashboard' => $dashboard,
            'legacyModules' => $launchCards,
            'workspaceTitle' => 'Mentor Legacy Access',
            'workspaceIntro' => 'Mentor portfolio reviews, notes, and execution updates should stay in Hatchers Ai Business OS. Only use legacy access when the OS cannot complete a support or verification step yet.',
            'homeRoute' => route('dashboard.mentor'),
        ]);
    }

    public function mentorSaveFounderNotes(
        Request $request,
        Founder $founder,
        OsOperationsLogService $logService
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isMentor()) {
            return redirect()->route('dashboard');
        }

        $assignment = $user->assignedFounderLinks()
            ->where('founder_id', $founder->id)
            ->where('status', 'active')
            ->latest('assigned_at')
            ->firstOrFail();

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $assignment->forceFill([
            'notes' => trim((string) ($validated['notes'] ?? '')),
        ])->save();

        $logService->recordAudit(
            $user,
            'mentor_notes_update',
            'founder',
            $founder->id,
            'Mentor updated founder guidance notes from Hatchers Ai Business OS.',
            ['mentor_assignment_id' => $assignment->id]
        );

        return redirect()
            ->route('mentor.founders.show', $founder)
            ->with('success', 'Mentor notes saved inside Hatchers Ai Business OS.');
    }

    public function mentorUpdateFounderActionStatus(
        Request $request,
        Founder $founder,
        FounderActionPlan $actionPlan,
        OsAssistantActionService $actionService,
        AtlasIntelligenceService $atlas,
        OsOperationsLogService $logService
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isMentor()) {
            return redirect()->route('dashboard');
        }

        $assignment = $user->assignedFounderLinks()
            ->where('founder_id', $founder->id)
            ->where('status', 'active')
            ->latest('assigned_at')
            ->firstOrFail();

        if ((int) $actionPlan->founder_id !== (int) $founder->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['completed', 'pending'])],
        ]);

        $response = $this->applyFounderExecutionStatus(
            $founder,
            $actionPlan,
            (string) $validated['status'],
            'task',
            $actionService,
            $atlas,
            'mentor'
        );

        $logService->recordAudit(
            $user,
            'mentor_execution_update',
            'founder',
            $founder->id,
            'Mentor updated founder execution status from Hatchers Ai Business OS.',
            [
                'mentor_assignment_id' => $assignment->id,
                'action_plan_id' => $actionPlan->id,
                'status' => $validated['status'],
            ]
        );

        return $response->setTargetUrl(route('mentor.founders.show', $founder));
    }

    public function founderMarketing(
        FounderDashboardService $founderDashboardService,
        WorkspaceLaunchService $workspaceLaunchService
    ) {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }
        if ($redirect = $this->ensureCompanyIntelligenceComplete($user)) {
            return $redirect;
        }

        return view('os.marketing', [
            'pageTitle' => 'Marketing',
            'dashboard' => $founderDashboardService->build($user),
            'launchCards' => $workspaceLaunchService->launchCards($user),
            'contentRequests' => $this->marketingContentRequests($user),
            'channelAnalytics' => $this->marketingChannelAnalytics($user),
            'publishTargets' => $this->marketingPublishTargets(),
            'atlasHistory' => $this->marketingAtlasHistory($user),
        ]);
    }

    public function founderCommerce(
        FounderDashboardService $founderDashboardService,
        WebsiteWorkspaceService $websiteWorkspaceService,
        WorkspaceLaunchService $workspaceLaunchService,
        OsWalletService $walletService,
        FounderRevenueOsService $revenueOsService
    ) {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }
        if ($redirect = $this->ensureCompanyIntelligenceComplete($user)) {
            return $redirect;
        }

        $offers = $this->commerceOffers($user);

        return view('os.commerce', [
            'pageTitle' => 'Launch Plan',
            'dashboard' => $founderDashboardService->build($user),
            'website' => $websiteWorkspaceService->build($user),
            'launchCards' => $workspaceLaunchService->launchCards($user),
            'catalogOffers' => $offers,
            'commerceConfigs' => $this->commerceConfigs($user),
            'commerceCatalogs' => $this->commerceCatalogs($user),
            'pricingOptimizer' => $revenueOsService->pricingOptimizer($user, $offers),
            'walletSummary' => $walletService->summary($user),
            'payoutAccount' => $user->payoutAccount,
            'recentPayoutRequests' => $user->payoutRequests()->latest()->limit(6)->get(),
        ]);
    }

    public function founderApplyPricingRecommendation(
        Request $request,
        FounderPricingRecommendation $pricingRecommendation,
        OsAssistantActionService $actionService,
        AtlasIntelligenceService $atlas
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        if ((int) $pricingRecommendation->founder_id !== (int) $user->id) {
            abort(404);
        }

        $validated = $request->validate([
            'target_action_plan_id' => ['nullable', 'integer'],
        ]);

        $target = null;
        if (!empty($validated['target_action_plan_id'])) {
            $target = $user->actionPlans()
                ->where('id', (int) $validated['target_action_plan_id'])
                ->whereIn('platform', ['bazaar', 'servio'])
                ->first();
        }

        if (!$target && $pricingRecommendation->founder_action_plan_id) {
            $target = $user->actionPlans()
                ->where('id', (int) $pricingRecommendation->founder_action_plan_id)
                ->whereIn('platform', ['bazaar', 'servio'])
                ->first();
        }

        if (!$target) {
            $preferredPlatform = (string) ($pricingRecommendation->apply_target ?: (($pricingRecommendation->meta_json['business_model'] ?? 'service') === 'product' ? 'bazaar' : 'servio'));
            $target = $user->actionPlans()
                ->where('platform', $preferredPlatform)
                ->where('description', 'not like', 'Config:%')
                ->latest()
                ->first();
        }

        if (!$target) {
            return redirect()->route('founder.commerce')->with('error', 'Create or sync at least one offer before applying a pricing recommendation.');
        }

        $offer = $this->parseCommerceOffer($target);
        $originalTitle = (string) $target->title;
        $syncTitle = $originalTitle;
        $offer['price'] = number_format((float) $pricingRecommendation->price, 2, '.', '');
        $offer['description'] = trim((string) ($pricingRecommendation->description ?: $offer['description']));
        $newTitle = trim((string) $pricingRecommendation->title);
        $warnings = [];

        if ($newTitle !== '' && $newTitle !== (string) $target->title) {
            $result = $target->platform === 'bazaar'
                ? $actionService->updateProductFieldFromOs($user, $originalTitle, 'title', $newTitle)
                : $actionService->updateServiceFieldFromOs($user, $originalTitle, 'title', $newTitle);

            if (!($result['success'] ?? false)) {
                $warnings[] = $result['reply'] ?? 'Title sync is pending.';
            }

            $target->title = $newTitle;
            if (($result['success'] ?? false) && $newTitle !== '') {
                $syncTitle = $newTitle;
            }
        }

        $result = $target->platform === 'bazaar'
            ? $actionService->updateProductFieldFromOs($user, $syncTitle, 'price', (string) $offer['price'])
            : $actionService->updateServiceFieldFromOs($user, $syncTitle, 'price', (string) $offer['price']);
        if (!($result['success'] ?? false)) {
            $warnings[] = $result['reply'] ?? 'Price sync is pending.';
        }

        $result = $target->platform === 'bazaar'
            ? $actionService->updateProductFieldFromOs($user, $syncTitle, 'description', (string) $offer['description'])
            : $actionService->updateServiceFieldFromOs($user, $syncTitle, 'description', (string) $offer['description']);
        if (!($result['success'] ?? false)) {
            $warnings[] = $result['reply'] ?? 'Description sync is pending.';
        }

        $target->description = $this->serializeCommerceOffer(array_merge($offer, [
            'engine' => $target->platform,
            'type' => $target->platform === 'bazaar' ? 'product' : 'service',
        ]));
        $target->save();

        $pricingRecommendation->forceFill([
            'founder_action_plan_id' => $target->id,
            'apply_target' => $target->platform,
            'status' => 'applied',
            'applied_payload_json' => [
                'target_action_plan_id' => $target->id,
                'target_title' => $target->title,
                'target_platform' => $target->platform,
                'applied_price' => (string) $offer['price'],
            ],
            'applied_at' => now(),
        ])->save();

        $atlas->syncFounderMutation($user, [
            'role' => 'founder',
            'action' => 'pricing_recommendation_apply',
            'field' => 'pricing_recommendation',
            'value' => $pricingRecommendation->title,
            'sync_summary' => 'Founder applied a pricing recommendation from Hatchers OS.',
        ]);

        return redirect()
            ->route('founder.commerce')
            ->with('success', $warnings === [] ? 'Pricing recommendation applied to your offer.' : 'Pricing recommendation applied. ' . implode(' ', $warnings));
    }

    public function founderUpdatePricingRecommendationStatus(Request $request, FounderPricingRecommendation $pricingRecommendation): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        if ((int) $pricingRecommendation->founder_id !== (int) $user->id) {
            abort(404);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['rejected', 'superseded'])],
        ]);

        $pricingRecommendation->forceFill([
            'status' => (string) $validated['status'],
            'meta_json' => array_merge(is_array($pricingRecommendation->meta_json) ? $pricingRecommendation->meta_json : [], [
                'status_updated_at' => now()->toDateTimeString(),
            ]),
        ])->save();

        return redirect()->route('founder.commerce')->with('success', 'Recommendation status updated.');
    }

    public function founderWallet(
        Request $request,
        FounderDashboardService $founderDashboardService,
        OsWalletService $walletService
    ) {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }
        if ($redirect = $this->ensureCompanyIntelligenceComplete($user)) {
            return $redirect;
        }

        $filters = $request->validate([
            'entry_type' => ['nullable', 'string', Rule::in(['all', 'credit', 'debit'])],
            'entry_status' => ['nullable', 'string', Rule::in(['all', 'available', 'pending', 'reserved', 'settled', 'released'])],
            'payout_status' => ['nullable', 'string', Rule::in(['all', 'pending', 'processing', 'paid', 'rejected'])],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        return view('os.wallet', [
            'pageTitle' => 'Wallet',
            'dashboard' => $founderDashboardService->build($user),
            'walletWorkspace' => $walletService->workspace($user, $filters),
            'payoutAccount' => $user->payoutAccount,
        ]);
    }

    public function founderWalletExport(Request $request, OsWalletService $walletService): StreamedResponse|RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'dataset' => ['required', Rule::in(['ledger', 'payouts'])],
            'entry_type' => ['nullable', 'string', Rule::in(['all', 'credit', 'debit'])],
            'entry_status' => ['nullable', 'string', Rule::in(['all', 'available', 'pending', 'reserved', 'settled', 'released'])],
            'payout_status' => ['nullable', 'string', Rule::in(['all', 'pending', 'processing', 'paid', 'rejected'])],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $workspace = $walletService->workspace($user, $request->only(['entry_type', 'entry_status', 'payout_status', 'q']));
        $dataset = (string) $validated['dataset'];
        $filename = 'founder-wallet-' . $dataset . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($workspace, $dataset): void {
            $handle = fopen('php://output', 'w');

            if ($dataset === 'ledger') {
                fputcsv($handle, ['headline', 'entry_type', 'amount', 'currency', 'status', 'source_platform', 'source_category', 'reference', 'created_at', 'available_at', 'note']);
                foreach ($workspace['ledger_entries'] as $entry) {
                    fputcsv($handle, [
                        $entry['headline'],
                        $entry['entry_type'],
                        $entry['amount'],
                        $entry['currency'],
                        $entry['status'],
                        $entry['source_platform'],
                        $entry['source_category'],
                        $entry['source_reference'],
                        $entry['created_at'],
                        $entry['available_at'],
                        $entry['note'],
                    ]);
                }
            } else {
                fputcsv($handle, ['amount', 'currency', 'status', 'destination_summary', 'requested_at', 'processed_at', 'reference', 'notes', 'rejection_reason']);
                foreach ($workspace['payout_requests'] as $entry) {
                    fputcsv($handle, [
                        $entry['amount'],
                        $entry['currency'],
                        $entry['status'],
                        $entry['destination_summary'],
                        $entry['requested_at'],
                        $entry['processed_at'],
                        $entry['reference'],
                        $entry['notes'],
                        $entry['rejection_reason'],
                    ]);
                }
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function founderOrders(
        Request $request,
        FounderDashboardService $founderDashboardService,
        WebsiteWorkspaceService $websiteWorkspaceService,
        WorkspaceLaunchService $workspaceLaunchService
    ) {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }
        if ($redirect = $this->ensureCompanyIntelligenceComplete($user)) {
            return $redirect;
        }

        $filters = $request->validate([
            'status' => ['nullable', 'string', Rule::in(['all', 'pending', 'processing', 'completed', 'cancelled'])],
            'queue' => ['nullable', 'string', Rule::in(['all', 'pending', 'unpaid', 'ready_to_ship'])],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        return view('os.orders', [
            'pageTitle' => 'Orders',
            'dashboard' => $founderDashboardService->build($user),
            'website' => $websiteWorkspaceService->build($user),
            'launchCards' => $workspaceLaunchService->launchCards($user),
            'orderWorkspace' => $this->commerceOperationsWorkspace($user, 'bazaar', $filters),
            'orderFilters' => [
                'status' => (string) ($filters['status'] ?? 'all'),
                'queue' => (string) ($filters['queue'] ?? 'all'),
                'q' => (string) ($filters['q'] ?? ''),
            ],
        ]);
    }

    public function founderBookings(
        Request $request,
        FounderDashboardService $founderDashboardService,
        WebsiteWorkspaceService $websiteWorkspaceService,
        WorkspaceLaunchService $workspaceLaunchService
    ) {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }
        if ($redirect = $this->ensureCompanyIntelligenceComplete($user)) {
            return $redirect;
        }

        $filters = $request->validate([
            'status' => ['nullable', 'string', Rule::in(['all', 'pending', 'processing', 'completed', 'cancelled'])],
            'queue' => ['nullable', 'string', Rule::in(['all', 'pending', 'unscheduled', 'needs_staff'])],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        return view('os.bookings', [
            'pageTitle' => 'Bookings',
            'dashboard' => $founderDashboardService->build($user),
            'website' => $websiteWorkspaceService->build($user),
            'launchCards' => $workspaceLaunchService->launchCards($user),
            'bookingWorkspace' => $this->commerceOperationsWorkspace($user, 'servio', $filters),
            'bookingFilters' => [
                'status' => (string) ($filters['status'] ?? 'all'),
                'queue' => (string) ($filters['queue'] ?? 'all'),
                'q' => (string) ($filters['q'] ?? ''),
            ],
        ]);
    }

    public function founderUpdateCommerceOffer(
        Request $request,
        FounderActionPlan $actionPlan,
        OsAssistantActionService $actionService,
        AtlasIntelligenceService $atlas
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        if ((int) $actionPlan->founder_id !== (int) $user->id || !in_array((string) $actionPlan->platform, ['bazaar', 'servio'], true)) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:4000'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'availability' => ['nullable', Rule::in(['active', 'inactive'])],
            'category_name' => ['nullable', 'string', 'max:255'],
            'tax_rules_text' => ['nullable', 'string', 'max:4000'],
            'sku' => ['nullable', 'string', 'max:255'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'low_stock' => ['nullable', 'integer', 'min:0'],
            'adjustment_mode' => ['nullable', Rule::in(['set', 'increase', 'decrease'])],
            'adjustment_amount' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'duration_unit' => ['nullable', Rule::in(['minutes', 'hours'])],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
            'staff_mode' => ['nullable', Rule::in(['auto', 'specific'])],
            'staff_id' => ['nullable', 'string', 'max:255'],
            'staff_ids_text' => ['nullable', 'string', 'max:4000'],
            'availability_days' => ['nullable', 'array'],
            'availability_days.*' => ['string', Rule::in(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])],
            'open_time' => ['nullable', 'date_format:H:i'],
            'close_time' => ['nullable', 'date_format:H:i'],
            'additional_services_text' => ['nullable', 'string', 'max:4000'],
            'variants_text' => ['nullable', 'string', 'max:4000'],
            'extras_text' => ['nullable', 'string', 'max:4000'],
            'payment_collection' => ['nullable', Rule::in(['online_only', 'cash_only', 'both'])],
        ]);

        $offer = $this->parseCommerceOffer($actionPlan);
        $originalTitle = $actionPlan->title;
        $warnings = [];
        $taxRules = $offer['tax_rules'] ?? [];
        $additionalServices = $offer['additional_services'] ?? [];
        $variants = $offer['variants'] ?? [];
        $extras = $offer['extras'] ?? [];
        $staffIds = $offer['staff_ids'] ?? [];

        if ((string) $validated['title'] !== $originalTitle) {
            $result = $actionPlan->platform === 'bazaar'
                ? $actionService->updateProductFieldFromOs($user, $originalTitle, 'title', (string) $validated['title'])
                : $actionService->updateServiceFieldFromOs($user, $originalTitle, 'title', (string) $validated['title']);

            if (!($result['success'] ?? false)) {
                $warnings[] = $result['reply'] ?? 'Title sync is pending.';
            }
        }

        if ((string) ($validated['description'] ?? '') !== (string) $offer['description']) {
            $result = $actionPlan->platform === 'bazaar'
                ? $actionService->updateProductFieldFromOs($user, (string) $validated['title'], 'description', (string) ($validated['description'] ?? ''))
                : $actionService->updateServiceFieldFromOs($user, (string) $validated['title'], 'description', (string) ($validated['description'] ?? ''));

            if (!($result['success'] ?? false)) {
                $warnings[] = $result['reply'] ?? 'Description sync is pending.';
            }
        }

        $newPrice = number_format((float) ($validated['price'] ?? 0), 2, '.', '');
        if ($newPrice !== (string) $offer['price']) {
            $result = $actionPlan->platform === 'bazaar'
                ? $actionService->updateProductFieldFromOs($user, (string) $validated['title'], 'price', $newPrice)
                : $actionService->updateServiceFieldFromOs($user, (string) $validated['title'], 'price', $newPrice);

            if (!($result['success'] ?? false)) {
                $warnings[] = $result['reply'] ?? 'Price sync is pending.';
            }
        }

        $newCategoryName = trim((string) ($validated['category_name'] ?? ($offer['category_name'] ?? '')));
        if ($newCategoryName !== '' && $newCategoryName !== (string) ($offer['category_name'] ?? '')) {
            $result = $actionPlan->platform === 'bazaar'
                ? $actionService->updateProductFieldFromOs($user, (string) $validated['title'], 'category_name', $newCategoryName)
                : $actionService->updateServiceFieldFromOs($user, (string) $validated['title'], 'category_name', $newCategoryName);

            if (!($result['success'] ?? false)) {
                $warnings[] = $result['reply'] ?? 'Category sync is pending.';
            }
        }

        $taxRules = $this->parseTaxRulesText((string) ($validated['tax_rules_text'] ?? $this->formatTaxRulesText($offer['tax_rules'] ?? [])));
        if (json_encode($taxRules) !== json_encode($offer['tax_rules'] ?? [])) {
            $result = $actionPlan->platform === 'bazaar'
                ? $actionService->updateProductFieldFromOs($user, (string) $validated['title'], 'tax_rules', json_encode($taxRules, JSON_UNESCAPED_UNICODE))
                : $actionService->updateServiceFieldFromOs($user, (string) $validated['title'], 'tax_rules', json_encode($taxRules, JSON_UNESCAPED_UNICODE));

            if (!($result['success'] ?? false)) {
                $warnings[] = $result['reply'] ?? 'Tax sync is pending.';
            }
        }

        if ($actionPlan->platform === 'bazaar') {
            $newSku = trim((string) ($validated['sku'] ?? ($offer['sku'] ?? '')));
            if ($newSku !== (string) ($offer['sku'] ?? '')) {
                $result = $actionService->updateProductFieldFromOs($user, (string) $validated['title'], 'sku', $newSku !== '' ? $newSku : 'OS-' . strtoupper(substr(md5((string) $validated['title']), 0, 8)));
                if (!($result['success'] ?? false)) {
                    $warnings[] = $result['reply'] ?? 'SKU sync is pending.';
                }
            }

            $currentStock = (int) ($offer['stock'] ?? 0);
            $requestedStock = (int) ($validated['stock'] ?? $currentStock);
            $adjustmentMode = (string) ($validated['adjustment_mode'] ?? '');
            $adjustmentAmount = (int) ($validated['adjustment_amount'] ?? 0);
            if ($adjustmentAmount > 0) {
                if ($adjustmentMode === 'increase') {
                    $requestedStock = $currentStock + $adjustmentAmount;
                } elseif ($adjustmentMode === 'decrease') {
                    $requestedStock = max(0, $currentStock - $adjustmentAmount);
                } elseif ($adjustmentMode === 'set') {
                    $requestedStock = $adjustmentAmount;
                }
            }

            $newStock = (string) $requestedStock;
            if ($newStock !== (string) ($offer['stock'] ?? '0')) {
                $result = $actionService->updateProductFieldFromOs($user, (string) $validated['title'], 'stock', $newStock);
                if (!($result['success'] ?? false)) {
                    $warnings[] = $result['reply'] ?? 'Stock sync is pending.';
                }
            }

            $newLowStock = (string) ((int) ($validated['low_stock'] ?? ($offer['low_stock'] ?? 0)));
            if ($newLowStock !== (string) ($offer['low_stock'] ?? '0')) {
                $result = $actionService->updateProductFieldFromOs($user, (string) $validated['title'], 'low_stock', $newLowStock);
                if (!($result['success'] ?? false)) {
                    $warnings[] = $result['reply'] ?? 'Low-stock sync is pending.';
                }
            }

            $variants = $this->parseVariantsText((string) ($validated['variants_text'] ?? $this->formatVariantsText($offer['variants'] ?? [])));
            if (json_encode($variants) !== json_encode($offer['variants'] ?? [])) {
                $result = $actionService->updateProductFieldFromOs($user, (string) $validated['title'], 'variants', json_encode($variants, JSON_UNESCAPED_UNICODE));
                if (!($result['success'] ?? false)) {
                    $warnings[] = $result['reply'] ?? 'Variant sync is pending.';
                }
            }

            $extras = $this->parseExtrasText((string) ($validated['extras_text'] ?? $this->formatExtrasText($offer['extras'] ?? [])));
            if (json_encode($extras) !== json_encode($offer['extras'] ?? [])) {
                $result = $actionService->updateProductFieldFromOs($user, (string) $validated['title'], 'extras', json_encode($extras, JSON_UNESCAPED_UNICODE));
                if (!($result['success'] ?? false)) {
                    $warnings[] = $result['reply'] ?? 'Extras sync is pending.';
                }
            }
        } else {
            $newDuration = (string) ((int) ($validated['duration'] ?? ($offer['duration'] ?? 30)));
            if ($newDuration !== (string) ($offer['duration'] ?? '30')) {
                $result = $actionService->updateServiceFieldFromOs($user, (string) $validated['title'], 'duration', $newDuration);
                if (!($result['success'] ?? false)) {
                    $warnings[] = $result['reply'] ?? 'Duration sync is pending.';
                }
            }

            $newDurationUnit = (string) ($validated['duration_unit'] ?? ($offer['duration_unit'] ?? 'minutes'));
            if ($newDurationUnit !== (string) ($offer['duration_unit'] ?? 'minutes')) {
                $result = $actionService->updateServiceFieldFromOs($user, (string) $validated['title'], 'duration_unit', $newDurationUnit);
                if (!($result['success'] ?? false)) {
                    $warnings[] = $result['reply'] ?? 'Duration unit sync is pending.';
                }
            }

            $newCapacity = (string) ((int) ($validated['capacity'] ?? ($offer['capacity'] ?? 1)));
            if ($newCapacity !== (string) ($offer['capacity'] ?? '1')) {
                $result = $actionService->updateServiceFieldFromOs($user, (string) $validated['title'], 'capacity', $newCapacity);
                if (!($result['success'] ?? false)) {
                    $warnings[] = $result['reply'] ?? 'Capacity sync is pending.';
                }
            }

            $newStaffMode = (string) ($validated['staff_mode'] ?? ($offer['staff_mode'] ?? 'auto'));
            if ($newStaffMode !== (string) ($offer['staff_mode'] ?? 'auto')) {
                $result = $actionService->updateServiceFieldFromOs($user, (string) $validated['title'], 'staff_mode', $newStaffMode);
                if (!($result['success'] ?? false)) {
                    $warnings[] = $result['reply'] ?? 'Staff assignment sync is pending.';
                }
            }

            $newStaffId = trim((string) ($validated['staff_id'] ?? ($offer['staff_id'] ?? '')));
            if ($newStaffId !== '' && $newStaffId !== (string) ($offer['staff_id'] ?? '')) {
                $result = $actionService->updateServiceFieldFromOs($user, (string) $validated['title'], 'staff_id', $newStaffId);
                if (!($result['success'] ?? false)) {
                    $warnings[] = $result['reply'] ?? 'Staff member sync is pending.';
                }
            }

            $staffIds = $this->parseStaffIdsText((string) ($validated['staff_ids_text'] ?? $this->formatStaffIdsText($offer['staff_ids'] ?? [])));
            if (implode('|', $staffIds) !== implode('|', $offer['staff_ids'] ?? [])) {
                $result = $actionService->updateServiceFieldFromOs($user, (string) $validated['title'], 'staff_ids', implode('|', $staffIds));
                if (!($result['success'] ?? false)) {
                    $warnings[] = $result['reply'] ?? 'Staff roster sync is pending.';
                }
            }

            $newAvailabilityDays = $this->normalizeAvailabilityDays($validated['availability_days'] ?? ($offer['availability_days'] ?? []));
            if (implode('|', $newAvailabilityDays) !== implode('|', $this->normalizeAvailabilityDays($offer['availability_days'] ?? []))) {
                $result = $actionService->updateServiceFieldFromOs($user, (string) $validated['title'], 'availability_days', implode('|', $newAvailabilityDays));
                if (!($result['success'] ?? false)) {
                    $warnings[] = $result['reply'] ?? 'Availability days sync is pending.';
                }
            }

            $newOpenTime = (string) ($validated['open_time'] ?? ($offer['open_time'] ?? '09:00'));
            if ($newOpenTime !== (string) ($offer['open_time'] ?? '09:00')) {
                $result = $actionService->updateServiceFieldFromOs($user, (string) $validated['title'], 'open_time', $newOpenTime);
                if (!($result['success'] ?? false)) {
                    $warnings[] = $result['reply'] ?? 'Opening time sync is pending.';
                }
            }

            $newCloseTime = (string) ($validated['close_time'] ?? ($offer['close_time'] ?? '17:00'));
            if ($newCloseTime !== (string) ($offer['close_time'] ?? '17:00')) {
                $result = $actionService->updateServiceFieldFromOs($user, (string) $validated['title'], 'close_time', $newCloseTime);
                if (!($result['success'] ?? false)) {
                    $warnings[] = $result['reply'] ?? 'Closing time sync is pending.';
                }
            }

            $additionalServices = $this->parseAdditionalServicesText((string) ($validated['additional_services_text'] ?? $this->formatAdditionalServicesText($offer['additional_services'] ?? [])));
            if (json_encode($additionalServices) !== json_encode($offer['additional_services'] ?? [])) {
                $result = $actionService->updateServiceFieldFromOs($user, (string) $validated['title'], 'additional_services', json_encode($additionalServices, JSON_UNESCAPED_UNICODE));
                if (!($result['success'] ?? false)) {
                    $warnings[] = $result['reply'] ?? 'Additional services sync is pending.';
                }
            }
        }

        $availability = (string) ($validated['availability'] ?? ($offer['status'] ?? 'active'));
        if ($availability !== (string) ($offer['status'] ?? 'active')) {
            $result = $actionPlan->platform === 'bazaar'
                ? $actionService->updateProductFieldFromOs($user, (string) $validated['title'], 'status', $availability)
                : $actionService->updateServiceFieldFromOs($user, (string) $validated['title'], 'status', $availability);

            if (!($result['success'] ?? false)) {
                $warnings[] = $result['reply'] ?? 'Availability sync is pending.';
            }
        }

        $actionPlan->forceFill([
            'title' => (string) $validated['title'],
            'description' => $this->serializeCommerceOffer([
                'type' => $offer['type'],
                'description' => (string) ($validated['description'] ?? ''),
                'price' => $newPrice,
                'engine' => $offer['engine'],
                'category_name' => (string) ($validated['category_name'] ?? ($offer['category_name'] ?? '')),
                'tax_rules' => $taxRules,
                'sku' => (string) ($validated['sku'] ?? ($offer['sku'] ?? '')),
                'stock' => (string) ($validated['stock'] ?? ($offer['stock'] ?? '')),
                'low_stock' => (string) ($validated['low_stock'] ?? ($offer['low_stock'] ?? '')),
                'duration' => (string) ($validated['duration'] ?? ($offer['duration'] ?? '')),
                'duration_unit' => (string) ($validated['duration_unit'] ?? ($offer['duration_unit'] ?? '')),
                'capacity' => (string) ($validated['capacity'] ?? ($offer['capacity'] ?? '')),
                'staff_mode' => (string) ($validated['staff_mode'] ?? ($offer['staff_mode'] ?? '')),
                'staff_id' => (string) ($validated['staff_id'] ?? ($offer['staff_id'] ?? '')),
                'staff_ids' => $staffIds,
                'availability_days' => $validated['availability_days'] ?? ($offer['availability_days'] ?? []),
                'open_time' => (string) ($validated['open_time'] ?? ($offer['open_time'] ?? '')),
                'close_time' => (string) ($validated['close_time'] ?? ($offer['close_time'] ?? '')),
                'additional_services' => $additionalServices ?? ($offer['additional_services'] ?? []),
                'variants' => $variants,
                'extras' => $extras,
                'payment_collection' => (string) ($validated['payment_collection'] ?? ($offer['payment_collection'] ?? 'both')),
            ]),
            'status' => $availability === 'inactive' ? 'paused' : 'created',
        ])->save();

        $atlas->syncFounderMutation($user, [
            'role' => 'founder',
            'action' => 'commerce_offer_update',
            'field' => $offer['type'] . '_offer',
            'value' => (string) $validated['title'],
            'sync_summary' => 'Founder updated a commerce offer from Hatchers OS.',
        ]);

        $redirect = redirect()
            ->route('founder.commerce')
            ->with('success', ucfirst($offer['type']) . ' updated inside Hatchers Ai Business OS.');

        if (!empty($warnings)) {
            $redirect->with('error', implode(' ', $warnings) . ' The OS copy has still been updated.');
        }

        return $redirect;
    }

    public function founderSavePayoutAccount(Request $request): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'account_holder_name' => ['required', 'string', 'max:191'],
            'bank_name' => ['required', 'string', 'max:191'],
            'account_number' => ['nullable', 'string', 'max:191'],
            'iban' => ['nullable', 'string', 'max:191'],
            'swift_code' => ['nullable', 'string', 'max:64'],
            'routing_number' => ['nullable', 'string', 'max:64'],
            'bank_country' => ['nullable', 'string', 'max:64'],
            'bank_currency' => ['nullable', 'string', 'max:8'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        FounderPayoutAccount::updateOrCreate(
            ['founder_id' => $user->id],
            [
                'account_holder_name' => (string) $validated['account_holder_name'],
                'bank_name' => (string) $validated['bank_name'],
                'account_number' => (string) ($validated['account_number'] ?? ''),
                'iban' => (string) ($validated['iban'] ?? ''),
                'swift_code' => (string) ($validated['swift_code'] ?? ''),
                'routing_number' => (string) ($validated['routing_number'] ?? ''),
                'bank_country' => (string) ($validated['bank_country'] ?? ''),
                'bank_currency' => strtoupper((string) ($validated['bank_currency'] ?? 'USD')),
                'notes' => (string) ($validated['notes'] ?? ''),
                'status' => 'active',
            ]
        );

        return redirect()->route('founder.commerce')->with('success', 'Payout account saved in Hatchers Ai Business OS.');
    }

    public function founderStartStripePayoutOnboarding(OsStripeService $stripeService): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        if (!$stripeService->configured()) {
            return redirect()->route('founder.commerce')->with('error', 'Stripe is not configured in Hatchers Ai Business OS yet.');
        }

        $account = FounderPayoutAccount::query()->firstOrCreate(
            ['founder_id' => $user->id],
            [
                'account_holder_name' => (string) $user->full_name,
                'bank_name' => 'Stripe Express',
                'bank_currency' => 'USD',
                'bank_country' => 'US',
                'status' => 'pending',
                'stripe_onboarding_status' => 'not_started',
            ]
        );

        if (trim((string) $account->stripe_account_id) === '') {
            $create = $stripeService->createConnectedAccount([
                'email' => (string) $user->email,
                'business_name' => (string) ($user->company?->company_name ?: $user->full_name),
                'country' => (string) ($account->bank_country ?: 'US'),
                'currency' => (string) ($account->bank_currency ?: 'USD'),
                'metadata' => [
                    'founder_id' => $user->id,
                    'company_id' => (string) ($user->company?->id ?? ''),
                ],
            ]);

            if (!($create['success'] ?? false)) {
                return redirect()->route('founder.commerce')->with('error', $create['message'] ?? 'The OS could not create a Stripe Connect account.');
            }

            $account->forceFill([
                'stripe_account_id' => (string) $create['id'],
                'stripe_onboarding_status' => 'pending',
                'stripe_charges_enabled' => (bool) ($create['charges_enabled'] ?? false),
                'stripe_payouts_enabled' => (bool) ($create['payouts_enabled'] ?? false),
                'stripe_details_submitted_at' => !empty($create['details_submitted']) ? now() : null,
            ])->save();
        }

        $link = $stripeService->createAccountOnboardingLink(
            (string) $account->stripe_account_id,
            route('founder.commerce.payout-account.connect'),
            route('founder.commerce.payout-account.return')
        );

        if (!($link['success'] ?? false) || trim((string) ($link['url'] ?? '')) === '') {
            return redirect()->route('founder.commerce')->with('error', $link['message'] ?? 'The OS could not create the Stripe onboarding link.');
        }

        $account->forceFill([
            'stripe_onboarding_status' => 'pending',
            'status' => 'pending',
        ])->save();

        return redirect()->away((string) $link['url']);
    }

    public function founderHandleStripePayoutReturn(OsStripeService $stripeService): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $account = $user->payoutAccount;
        if (!$account || trim((string) $account->stripe_account_id) === '') {
            return redirect()->route('founder.commerce')->with('error', 'No Stripe payout account was found for this founder.');
        }

        $sync = $this->syncStripePayoutAccountStatus($account, $stripeService);
        if (!($sync['success'] ?? false)) {
            return redirect()->route('founder.commerce')->with('error', $sync['message'] ?? 'The OS could not verify the Stripe payout account.');
        }

        if ($account->stripe_payouts_enabled) {
            return redirect()->route('founder.commerce')->with('success', 'Stripe payout onboarding is complete. Founder withdrawals can now flow through Stripe.');
        }

        return redirect()->route('founder.commerce')->with('error', 'Stripe onboarding is not complete yet. Please finish the required Stripe steps and try again.');
    }

    public function founderRequestPayout(Request $request, OsWalletService $walletService, OsStripeService $stripeService): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $account = $user->payoutAccount;
        if (!$account) {
            return redirect()->route('founder.commerce')->with('error', 'Please save your bank payout account before requesting a withdrawal.');
        }

        $amount = round((float) $validated['amount'], 2);
        if (!$walletService->canRequestPayout($user, $amount)) {
            return redirect()->route('founder.commerce')->with('error', 'Payout requests must be at least USD 50 and cannot exceed the available wallet balance.');
        }

        $summary = trim($account->bank_name . ' · ' . ($account->iban ?: $account->account_number ?: 'Bank account'));
        $payoutRequest = $walletService->requestPayout(
            $user,
            $amount,
            (string) ($account->bank_currency ?: 'USD'),
            $summary,
            (string) ($validated['notes'] ?? '')
        );

        $walletService->reserveForPayout($user, $user->company, $payoutRequest);

        if (trim((string) $account->stripe_account_id) !== '' && $stripeService->configured()) {
            $sync = $this->syncStripePayoutAccountStatus($account, $stripeService);

            if (($sync['success'] ?? false) && $account->stripe_payouts_enabled) {
                $transfer = $stripeService->createTransfer(
                    (string) $account->stripe_account_id,
                    $amount,
                    (string) ($account->bank_currency ?: 'USD'),
                    [
                        'founder_id' => $user->id,
                        'payout_request_id' => $payoutRequest->id,
                    ]
                );

                if ($transfer['success'] ?? false) {
                    $walletService->markPayoutPaid($payoutRequest, (string) ($transfer['id'] ?? ''));

                    return redirect()->route('founder.commerce')->with('success', 'Payout request submitted and sent to Stripe for transfer to the founder bank account.');
                }

                $payoutRequest->forceFill([
                    'status' => 'processing',
                    'meta_json' => array_merge((array) ($payoutRequest->meta_json ?? []), [
                        'stripe_transfer_error' => (string) ($transfer['message'] ?? 'Stripe transfer could not be created.'),
                    ]),
                ])->save();

                return redirect()->route('founder.commerce')->with('error', ($transfer['message'] ?? 'Stripe transfer could not be created.') . ' The payout request is still recorded for support follow-up.');
            }
        }

        return redirect()->route('founder.commerce')->with('success', 'Payout request submitted from Hatchers Ai Business OS.');
    }

    public function founderUpdateCommerceConfig(
        Request $request,
        FounderActionPlan $actionPlan,
        OsAssistantActionService $actionService,
        AtlasIntelligenceService $atlas
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        if ((int) $actionPlan->founder_id !== (int) $user->id || !in_array((string) $actionPlan->platform, ['bazaar', 'servio'], true)) {
            abort(403);
        }

        $config = $this->parseCommerceConfig($actionPlan);
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'field_one' => ['nullable', 'string', 'max:255'],
            'field_two' => ['nullable', 'string', 'max:255'],
            'field_three' => ['nullable', 'string', 'max:255'],
            'field_four' => ['nullable', 'string', 'max:255'],
        ];

        $validated = $request->validate($rules);
        $errors = [];

        if ($config['type'] === 'coupon') {
            $result = $actionService->saveCommerceConfigFromOs(
                $user,
                (string) $actionPlan->platform,
                'coupon',
                (string) $validated['title'],
                [
                    'offer_name' => (string) $validated['title'],
                    'offer_code' => (string) ($validated['field_one'] ?? ''),
                    'offer_type' => (string) (($validated['field_two'] ?? '') === 'percent' ? 'percent' : 'fixed'),
                    'offer_amount' => (string) ($validated['field_three'] ?? ''),
                    'description' => (string) ($validated['field_four'] ?? ''),
                    'is_available' => $actionPlan->status === 'paused' ? 2 : 1,
                ],
                true
            );
            if (!($result['success'] ?? false)) {
                $errors[] = $result['reply'] ?? 'Coupon sync is pending.';
            }
        } elseif ($config['type'] === 'shipping') {
            $result = $actionService->saveCommerceConfigFromOs(
                $user,
                'bazaar',
                'shipping',
                (string) $validated['title'],
                [
                    'area_name' => (string) ($validated['field_one'] ?? $validated['title']),
                    'delivery_charge' => (string) ($validated['field_two'] ?? '0'),
                    'description' => trim(implode(' · ', array_filter([
                        (string) ($validated['field_three'] ?? ''),
                        (string) ($validated['field_four'] ?? ''),
                    ]))),
                    'is_available' => $actionPlan->status === 'paused' ? 2 : 1,
                ],
                true
            );
            if (!($result['success'] ?? false)) {
                $errors[] = $result['reply'] ?? 'Shipping sync is pending.';
            }
        }

        $actionPlan->forceFill([
            'title' => (string) $validated['title'],
            'description' => $this->serializeCommerceConfig([
                'type' => $config['type'],
                'engine' => $config['engine'],
                'field_one' => (string) ($validated['field_one'] ?? ''),
                'field_two' => (string) ($validated['field_two'] ?? ''),
                'field_three' => (string) ($validated['field_three'] ?? ''),
                'field_four' => (string) ($validated['field_four'] ?? ''),
            ]),
        ])->save();

        $atlas->syncFounderMutation($user, [
            'role' => 'founder',
            'action' => 'commerce_config_update',
            'field' => (string) $config['type'],
            'value' => (string) $validated['title'],
            'sync_summary' => 'Founder updated a commerce config from Hatchers OS.',
        ]);

        $redirect = redirect()->route('founder.commerce')->with('success', ucfirst((string) $config['type']) . ' updated inside Hatchers Ai Business OS.');
        if (!empty($errors)) {
            $redirect->with('error', implode(' ', $errors) . ' The OS copy has still been updated.');
        }

        return $redirect;
    }

    public function founderSettings(Request $request, FounderDashboardService $founderDashboardService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $wizard = $this->companyIntelligenceWizardState($user, (string) $request->query('step', ''));

        return view('os.settings', [
            'pageTitle' => 'Company Intelligence',
            'dashboard' => $founderDashboardService->build($user),
            'intelligence' => $user->company?->intelligence,
            'wizard' => $wizard,
        ]);
    }

    public function founderUpdateSettings(Request $request): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $step = (string) $request->input('current_step', 'basics');
        $stepRules = [
            'basics' => [
                'full_name' => ['required', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
                'company_name' => ['required', 'string', 'max:255'],
                'company_brief' => ['required', 'string', 'max:2000'],
                'business_model' => ['required', Rule::in(['product', 'service', 'hybrid'])],
                'company_logo' => ['nullable', 'image', 'max:4096'],
            ],
            'audience' => [
                'target_audience' => ['required', 'string', 'max:255'],
                'primary_icp_name' => ['required', 'string', 'max:255'],
                'ideal_customer_profile' => ['required', 'string', 'max:1000'],
                'problem_solved' => ['required', 'string', 'max:1000'],
            ],
            'offer' => [
                'core_offer' => ['required', 'string', 'max:255'],
                'differentiators' => ['required', 'string', 'max:1000'],
                'objections' => ['required', 'string', 'max:1200'],
                'buying_triggers' => ['required', 'string', 'max:1200'],
            ],
            'brand' => [
                'brand_voice' => ['required', 'string', 'max:255'],
                'visual_style' => ['required', 'string', 'max:500'],
                'primary_growth_goal' => ['required', 'string', 'max:255'],
                'known_blockers' => ['required', 'string', 'max:500'],
                'local_market_notes' => ['nullable', 'string', 'max:1200'],
            ],
        ];

        if (!array_key_exists($step, $stepRules)) {
            $step = 'basics';
        }

        $validated = $request->validate(array_merge([
            'current_step' => ['required', Rule::in(array_keys($stepRules))],
        ], $stepRules[$step]));

        if ($step === 'basics') {
            $user->forceFill([
                'full_name' => (string) $validated['full_name'],
                'phone' => (string) ($validated['phone'] ?? ''),
            ])->save();
        }

        $company = $user->company;
        if ($step !== 'basics' && !$company) {
            return redirect()->route('founder.settings', ['step' => 'basics'])->with('error', 'Complete the company basics first.');
        }

        if (!$company) {
            $company = Company::create([
                'founder_id' => $user->id,
                'company_name' => (string) $validated['company_name'],
                'business_model' => (string) $validated['business_model'],
                'stage' => 'idea',
                'website_status' => 'not_started',
            ]);
        }

        if ($step === 'basics') {
            $logoPath = (string) ($company->company_logo_path ?? '');
            if ($request->hasFile('company_logo')) {
                if ($logoPath !== '' && Storage::disk('public')->exists($logoPath)) {
                    Storage::disk('public')->delete($logoPath);
                }
                $logoPath = (string) $request->file('company_logo')->store('company-logos', 'public');
            }

            $company->forceFill([
                'company_name' => (string) $validated['company_name'],
                'company_brief' => (string) ($validated['company_brief'] ?? ''),
                'business_model' => (string) $validated['business_model'],
                'company_logo_path' => $logoPath !== '' ? $logoPath : null,
            ])->save();
        }

        $currentIntelligence = $company->intelligence;
        $intelligencePayload = [
            'target_audience' => (string) ($currentIntelligence?->target_audience ?? ''),
            'ideal_customer_profile' => (string) ($currentIntelligence?->ideal_customer_profile ?? ''),
            'primary_icp_name' => (string) ($currentIntelligence?->primary_icp_name ?? ''),
            'problem_solved' => (string) ($currentIntelligence?->problem_solved ?? ''),
            'brand_voice' => (string) ($currentIntelligence?->brand_voice ?? ''),
            'differentiators' => (string) ($currentIntelligence?->differentiators ?? ''),
            'core_offer' => (string) ($currentIntelligence?->core_offer ?? ''),
            'primary_growth_goal' => (string) ($currentIntelligence?->primary_growth_goal ?? ''),
            'known_blockers' => (string) ($currentIntelligence?->known_blockers ?? ''),
            'objections' => (string) ($currentIntelligence?->objections ?? ''),
            'buying_triggers' => (string) ($currentIntelligence?->buying_triggers ?? ''),
            'local_market_notes' => (string) ($currentIntelligence?->local_market_notes ?? ''),
            'visual_style' => (string) ($currentIntelligence?->visual_style ?? ''),
        ];

        foreach (array_keys($intelligencePayload) as $field) {
            if (array_key_exists($field, $validated)) {
                $intelligencePayload[$field] = (string) ($validated[$field] ?? '');
            }
        }

        CompanyIntelligence::updateOrCreate(
            ['company_id' => $company->id],
            array_merge($intelligencePayload, [
                'intelligence_updated_at' => now(),
            ])
        );

        $user->unsetRelation('company');
        $user->load('company.intelligence');
        $wizard = $this->companyIntelligenceWizardState($user);
        $nextStep = $wizard['is_complete'] ? 'brand' : $wizard['current_step_key'];

        return redirect()
            ->route('founder.settings', ['step' => $nextStep])
            ->with('success', $wizard['is_complete']
                ? 'Company Intelligence is complete and ready to power the rest of Hatchers OS.'
                : 'Saved. Continue to the next Company Intelligence step.');
    }

    private function companyIntelligenceWizardState(Founder $founder, string $requestedStep = ''): array
    {
        $company = $founder->company;
        $intelligence = $company?->intelligence;

        $steps = [
            'basics' => [
                'key' => 'basics',
                'label' => 'Basics',
                'headline' => 'Define the business basics',
                'copy' => 'Start with the founder, company, and business model details that every other workspace depends on.',
                'fields' => [
                    'full_name' => trim((string) $founder->full_name),
                    'phone' => trim((string) ($founder->phone ?? '')),
                    'company_name' => trim((string) ($company?->company_name ?? '')),
                    'company_brief' => trim((string) ($company?->company_brief ?? '')),
                    'business_model' => trim((string) ($company?->business_model ?? '')),
                ],
                'required' => ['full_name', 'company_name', 'company_brief', 'business_model'],
            ],
            'audience' => [
                'key' => 'audience',
                'label' => 'Audience',
                'headline' => 'Clarify who this business is for',
                'copy' => 'Your target audience and ideal customer profile shape positioning, messaging, and every AI prompt that follows.',
                'fields' => [
                    'target_audience' => trim((string) ($intelligence?->target_audience ?? '')),
                    'primary_icp_name' => trim((string) ($intelligence?->primary_icp_name ?? '')),
                    'ideal_customer_profile' => trim((string) ($intelligence?->ideal_customer_profile ?? '')),
                    'problem_solved' => trim((string) ($intelligence?->problem_solved ?? '')),
                ],
                'required' => ['target_audience', 'primary_icp_name', 'ideal_customer_profile', 'problem_solved'],
            ],
            'offer' => [
                'key' => 'offer',
                'label' => 'Offer',
                'headline' => 'Define the offer and buying logic',
                'copy' => 'This is where we capture what you sell, why people choose you, what they hesitate over, and what makes them buy.',
                'fields' => [
                    'core_offer' => trim((string) ($intelligence?->core_offer ?? '')),
                    'differentiators' => trim((string) ($intelligence?->differentiators ?? '')),
                    'objections' => trim((string) ($intelligence?->objections ?? '')),
                    'buying_triggers' => trim((string) ($intelligence?->buying_triggers ?? '')),
                ],
                'required' => ['core_offer', 'differentiators', 'objections', 'buying_triggers'],
            ],
            'brand' => [
                'key' => 'brand',
                'label' => 'Brand + Growth',
                'headline' => 'Capture voice, growth goals, and market notes',
                'copy' => 'Keep this editable over time so the OS and Atlas keep getting sharper as your business understanding improves.',
                'fields' => [
                    'brand_voice' => trim((string) ($intelligence?->brand_voice ?? '')),
                    'visual_style' => trim((string) ($intelligence?->visual_style ?? '')),
                    'primary_growth_goal' => trim((string) ($intelligence?->primary_growth_goal ?? '')),
                    'known_blockers' => trim((string) ($intelligence?->known_blockers ?? '')),
                    'local_market_notes' => trim((string) ($intelligence?->local_market_notes ?? '')),
                ],
                'required' => ['brand_voice', 'visual_style', 'primary_growth_goal', 'known_blockers'],
            ],
        ];

        $completedCount = 0;
        $firstIncomplete = null;

        foreach ($steps as $key => $step) {
            $completed = true;
            foreach ($step['required'] as $field) {
                if (($step['fields'][$field] ?? '') === '') {
                    $completed = false;
                    break;
                }
            }

            $steps[$key]['is_complete'] = $completed;
            if ($completed) {
                $completedCount++;
            } elseif ($firstIncomplete === null) {
                $firstIncomplete = $key;
            }
        }

        $isComplete = $completedCount === count($steps);
        $currentStepKey = $firstIncomplete ?? 'brand';

        if ($requestedStep !== '' && isset($steps[$requestedStep])) {
            $requestedIndex = array_search($requestedStep, array_keys($steps), true);
            $currentIndex = array_search($currentStepKey, array_keys($steps), true);
            if ($requestedIndex !== false && $currentIndex !== false && ($requestedIndex <= $currentIndex || $isComplete)) {
                $currentStepKey = $requestedStep;
            }
        }

        return [
            'steps' => array_values($steps),
            'step_map' => $steps,
            'current_step_key' => $currentStepKey,
            'current_step' => $steps[$currentStepKey],
            'completed_steps' => $completedCount,
            'total_steps' => count($steps),
            'completion_percent' => (int) round(($completedCount / max(count($steps), 1)) * 100),
            'is_complete' => $isComplete,
        ];
    }

    private function ensureCompanyIntelligenceComplete(Founder $founder): ?RedirectResponse
    {
        $wizard = $this->companyIntelligenceWizardState($founder);

        if ($wizard['is_complete']) {
            return null;
        }

        return redirect()
            ->route('founder.settings', ['step' => $wizard['current_step_key']])
            ->with('error', 'Complete Company Intelligence before using the rest of Hatchers OS.');
    }

    public function founderCreateCampaign(
        Request $request,
        OsAssistantActionService $actionService,
        AtlasIntelligenceService $atlas
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $result = $actionService->createCampaignFromOs(
            $user,
            (string) $validated['title'],
            (string) $validated['description']
        );

        if (!($result['success'] ?? false)) {
            return redirect()
                ->route('founder.marketing')
                ->withInput()
                ->with('error', $result['reply'] ?? 'Hatchers OS could not create that campaign right now.');
        }

        $atlas->syncFounderMutation($user, [
            'role' => 'founder',
            'action' => $result['action_type'] ?? 'platform_record_create',
            'field' => 'campaign',
            'value' => (string) $validated['title'],
            'sync_summary' => $result['sync_summary'] ?? 'Atlas created a campaign from Hatchers OS.',
        ]);

        $this->addCampaignToAtlasSnapshot(
            $user,
            (string) ($result['title'] ?? $validated['title']),
            (string) $validated['description'],
            (string) ($result['edit_url'] ?? '')
        );

        return redirect()
            ->route('founder.marketing')
            ->with('success', $result['reply'] ?? 'Campaign created from Hatchers OS.');
    }

    public function founderArchiveCampaign(
        Request $request,
        OsAssistantActionService $actionService,
        AtlasIntelligenceService $atlas
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'campaign_title' => ['required', 'string', 'max:255'],
        ]);

        $result = $actionService->archiveCampaignFromOs($user, (string) $validated['campaign_title']);
        if (!($result['success'] ?? false)) {
            return redirect()
                ->route('founder.marketing')
                ->with('error', $result['reply'] ?? 'Hatchers OS could not archive that campaign right now.');
        }

        $atlas->syncFounderMutation($user, [
            'role' => 'founder',
            'action' => $result['action_type'] ?? 'platform_record_action',
            'field' => 'campaign_archive',
            'value' => (string) $validated['campaign_title'],
            'sync_summary' => $result['sync_summary'] ?? 'Atlas archived a campaign from Hatchers OS.',
        ]);

        $this->moveCampaignInAtlasSnapshot($user, (string) $validated['campaign_title'], 'recent_campaigns', 'archived_campaigns');

        return redirect()
            ->route('founder.marketing')
            ->with('success', $result['reply'] ?? 'Campaign archived from Hatchers OS.');
    }

    public function founderRestoreCampaign(
        Request $request,
        OsAssistantActionService $actionService,
        AtlasIntelligenceService $atlas
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'campaign_title' => ['required', 'string', 'max:255'],
        ]);

        $result = $actionService->restoreCampaignFromOs($user, (string) $validated['campaign_title']);
        if (!($result['success'] ?? false)) {
            return redirect()
                ->route('founder.marketing')
                ->with('error', $result['reply'] ?? 'Hatchers OS could not restore that campaign right now.');
        }

        $atlas->syncFounderMutation($user, [
            'role' => 'founder',
            'action' => $result['action_type'] ?? 'platform_record_action',
            'field' => 'campaign_restore',
            'value' => (string) $validated['campaign_title'],
            'sync_summary' => $result['sync_summary'] ?? 'Atlas restored a campaign from Hatchers OS.',
        ]);

        $this->moveCampaignInAtlasSnapshot($user, (string) $validated['campaign_title'], 'archived_campaigns', 'recent_campaigns');

        return redirect()
            ->route('founder.marketing')
            ->with('success', $result['reply'] ?? 'Campaign restored from Hatchers OS.');
    }

    public function founderDuplicateCampaign(
        Request $request,
        OsAssistantActionService $actionService,
        AtlasIntelligenceService $atlas
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'campaign_title' => ['required', 'string', 'max:255'],
        ]);

        $sourceCampaign = $this->findAtlasCampaign($user, (string) $validated['campaign_title']);
        if (empty($sourceCampaign)) {
            return redirect()->route('founder.marketing')->with('error', 'Hatchers OS could not find that campaign in the latest Atlas snapshot.');
        }

        $baseTitle = trim((string) ($sourceCampaign['title'] ?? $validated['campaign_title']));
        $duplicateTitle = $baseTitle . ' Copy';
        $duplicateDescription = trim((string) ($sourceCampaign['description'] ?? '')) !== ''
            ? trim((string) $sourceCampaign['description'])
            : 'Duplicated from "' . $baseTitle . '" inside Hatchers Ai Business OS.';

        $result = $actionService->createCampaignFromOs($user, $duplicateTitle, $duplicateDescription);
        if (!($result['success'] ?? false)) {
            return redirect()->route('founder.marketing')->with('error', $result['reply'] ?? 'Hatchers OS could not duplicate that campaign right now.');
        }

        $atlas->syncFounderMutation($user, [
            'role' => 'founder',
            'action' => $result['action_type'] ?? 'platform_record_create',
            'field' => 'campaign_duplicate',
            'value' => $duplicateTitle,
            'sync_summary' => 'Founder duplicated a campaign from Hatchers OS.',
        ]);

        $this->addCampaignToAtlasSnapshot(
            $user,
            (string) ($result['title'] ?? $duplicateTitle),
            $duplicateDescription,
            (string) ($result['edit_url'] ?? '')
        );

        return redirect()->route('founder.marketing')->with('success', 'Campaign duplicated from Hatchers OS.');
    }

    public function founderCreateContentRequest(
        Request $request,
        AtlasIntelligenceService $atlas
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'content_title' => ['required', 'string', 'max:255'],
            'content_channel' => ['required', 'string', Rule::in(['linkedin', 'instagram', 'x', 'email', 'blog', 'landing-page'])],
            'content_publish_target' => ['required', 'string', Rule::in(['atlas', 'website', 'bazaar', 'servio'])],
            'content_goal' => ['required', 'string', 'max:255'],
            'content_brief' => ['required', 'string', 'max:2000'],
        ]);

        $channelLabel = str_replace('-', ' ', ucfirst((string) $validated['content_channel']));
        $publishTargetLabel = $this->marketingPublishTargets()[(string) $validated['content_publish_target']] ?? ucfirst((string) $validated['content_publish_target']);

        FounderActionPlan::create([
            'founder_id' => $user->id,
            'title' => (string) $validated['content_title'],
            'description' => "Channel: {$channelLabel}\nGoal: {$validated['content_goal']}\nPublish target: {$publishTargetLabel}\n\n{$validated['content_brief']}",
            'platform' => 'atlas',
            'priority' => 68,
            'status' => 'draft',
            'cta_label' => 'Open Marketing',
            'cta_url' => route('founder.marketing'),
        ]);

        $atlas->syncFounderMutation($user, [
            'role' => 'founder',
            'action' => 'content_request_create',
            'field' => 'content_request',
            'value' => (string) $validated['content_title'],
            'sync_summary' => 'Founder created a content request from Hatchers OS.',
        ]);

        return redirect()
            ->route('founder.marketing')
            ->with('success', 'Content request added to your OS marketing queue.');
    }

    public function founderUpdateContentRequestStatus(
        Request $request,
        AtlasIntelligenceService $atlas
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'content_request_id' => ['required', 'integer'],
            'status' => ['required', 'string', Rule::in(['draft', 'pending', 'approved', 'completed'])],
        ]);

        $contentRequest = FounderActionPlan::query()
            ->where('founder_id', $user->id)
            ->where('platform', 'atlas')
            ->findOrFail((int) $validated['content_request_id']);

        $status = (string) $validated['status'];
        $contentRequest->forceFill([
            'status' => $status,
            'completed_at' => $status === 'completed' ? now() : null,
        ])->save();

        $atlas->syncFounderMutation($user, [
            'role' => 'founder',
            'action' => 'content_request_status_update',
            'field' => 'content_request_status',
            'value' => $contentRequest->title . ':' . $status,
            'sync_summary' => 'Founder updated a content request status from Hatchers OS.',
        ]);

        $message = match ($status) {
            'pending' => 'Content request queued for generation.',
            'approved' => 'Content draft approved and ready for publish handoff.',
            'completed' => 'Content request marked as completed.',
            default => 'Content request moved back to draft.',
        };

        return redirect()->route('founder.marketing')->with('success', $message);
    }

    public function founderPublishContentRequest(
        Request $request,
        AtlasIntelligenceService $atlas,
        WorkspaceLaunchService $workspaceLaunchService
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'content_request_id' => ['required', 'integer'],
        ]);

        $contentRequest = FounderActionPlan::query()
            ->where('founder_id', $user->id)
            ->where('platform', 'atlas')
            ->findOrFail((int) $validated['content_request_id']);

        $contentRequest->forceFill([
            'status' => 'published',
            'completed_at' => now(),
            'cta_label' => $this->marketingPublishCtaLabel($this->parseMarketingContentRequest($contentRequest->description ?? '')['publish_target_key'] ?? ''),
            'cta_url' => $this->marketingPublishUrl($user, $workspaceLaunchService, $this->parseMarketingContentRequest($contentRequest->description ?? '')['publish_target_key'] ?? ''),
        ])->save();

        $atlas->syncFounderMutation($user, [
            'role' => 'founder',
            'action' => 'content_request_publish_handoff',
            'field' => 'content_request_publish',
            'value' => $contentRequest->title,
            'sync_summary' => 'Founder launched a publish handoff from Hatchers OS.',
        ]);

        $parsed = $this->parseMarketingContentRequest($contentRequest->description ?? '');
        $launchUrl = $this->marketingPublishUrl($user, $workspaceLaunchService, $parsed['publish_target_key'] ?? '');
        if ($launchUrl) {
            return redirect()->away($launchUrl);
        }

        return redirect()->route('founder.marketing')->with('success', 'Publish handoff recorded in Hatchers OS.');
    }

    public function founderGenerateContentDraft(
        Request $request,
        AtlasIntelligenceService $atlas
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'content_request_id' => ['required', 'integer'],
        ]);

        $contentRequest = FounderActionPlan::query()
            ->where('founder_id', $user->id)
            ->where('platform', 'atlas')
            ->findOrFail((int) $validated['content_request_id']);

        $parsed = $this->parseMarketingContentRequest($contentRequest->description ?? '');
        $draft = $this->generateMarketingDraft($user, $contentRequest->title, $parsed);

        $contentRequest->forceFill([
            'description' => $this->serializeMarketingContentRequest(array_merge($parsed, [
                'draft' => $draft,
            ])),
            'status' => 'pending',
            'completed_at' => null,
        ])->save();

        $atlas->syncFounderMutation($user, [
            'role' => 'founder',
            'action' => 'content_request_generate',
            'field' => 'content_request_draft',
            'value' => $contentRequest->title,
            'sync_summary' => 'Founder generated a starter content draft from Hatchers OS.',
        ]);

        return redirect()->route('founder.marketing')->with('success', 'Starter draft generated inside Hatchers OS.');
    }

    public function founderSaveContentDraft(
        Request $request,
        AtlasIntelligenceService $atlas
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'content_request_id' => ['required', 'integer'],
            'draft_body' => ['required', 'string', 'max:8000'],
        ]);

        $contentRequest = FounderActionPlan::query()
            ->where('founder_id', $user->id)
            ->where('platform', 'atlas')
            ->findOrFail((int) $validated['content_request_id']);

        $parsed = $this->parseMarketingContentRequest($contentRequest->description ?? '');
        $contentRequest->forceFill([
            'description' => $this->serializeMarketingContentRequest(array_merge($parsed, [
                'draft' => trim((string) $validated['draft_body']),
            ])),
        ])->save();

        $atlas->syncFounderMutation($user, [
            'role' => 'founder',
            'action' => 'content_request_edit',
            'field' => 'content_request_draft',
            'value' => $contentRequest->title,
            'sync_summary' => 'Founder edited a content draft from Hatchers OS.',
        ]);

        return redirect()->route('founder.marketing')->with('success', 'Content draft saved inside Hatchers OS.');
    }

    public function launchWorkspace(string $module, WorkspaceLaunchService $workspaceLaunchService): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if ($user->isFounder() && ($redirect = $this->ensureCompanyIntelligenceComplete($user))) {
            return $redirect;
        }

        $target = '';
        if (request()->filled('target')) {
            $requestedTarget = (string) request()->query('target', '');
            $target = strtolower($module) === 'atlas'
                ? $this->sanitizeAtlasWorkspaceTarget($requestedTarget)
                : '';
        }

        $url = $target !== ''
            ? $workspaceLaunchService->buildLaunchUrlForTarget($user, $module, $target)
            : $workspaceLaunchService->buildLaunchUrl($user, $module);
        if ($url === null) {
            return redirect()->route('dashboard')->with('error', 'Hatchers OS could not prepare that workspace launch yet.');
        }

        return redirect()->away($url);
    }

    public function website(WebsiteWorkspaceService $websiteWorkspaceService)
    {
        /** @var \App\Models\Founder $founder */
        $founder = Auth::user();
        if ($founder->isFounder() && ($redirect = $this->ensureCompanyIntelligenceComplete($founder))) {
            return $redirect;
        }

        return view('os.website', [
            'pageTitle' => 'Website Workspace',
            'website' => $websiteWorkspaceService->build($founder),
        ]);
    }

    public function generateWebsiteDraft(WebsiteAutopilotService $websiteAutopilotService): RedirectResponse
    {
        /** @var \App\Models\Founder $founder */
        $founder = Auth::user();

        $result = $websiteAutopilotService->generate($founder);
        if (!($result['ok'] ?? false)) {
            return redirect()->route('website')->with('error', (string) ($result['error'] ?? 'Hatchers OS could not generate the website draft yet.'));
        }

        return redirect()->route('website')->with('success', 'Your first website draft is ready. Hatchers OS prefilled the site setup and built the first offer path for review.');
    }

    public function founderApplyLaunchSystem(WebsiteAutopilotService $websiteAutopilotService): RedirectResponse
    {
        /** @var \App\Models\Founder $founder */
        $founder = Auth::user();
        if (!$founder->isFounder()) {
            return redirect()->route('dashboard');
        }

        $company = $founder->company;
        $draft = $websiteAutopilotService->latestDraft($company);
        $latestRun = $company?->websiteGenerationRuns()->latest('id')->first();

        if (!$company || !$latestRun || !$draft) {
            return redirect()->route('website')->with('error', 'Generate a website draft before locking the launch system.');
        }

        $launchSystem = FounderLaunchSystem::query()->updateOrCreate(
            [
                'founder_id' => $founder->id,
                'status' => 'active',
            ],
            [
                'company_id' => $company->id,
                'vertical_blueprint_id' => $company->vertical_blueprint_id,
                'founder_website_generation_run_id' => $latestRun->id,
                'selected_engine' => (string) ($draft['engine'] ?? $company->website_engine ?? ''),
                'launch_strategy_json' => [
                    'website_title' => (string) ($draft['title'] ?? $company->company_name),
                    'website_path' => (string) ($draft['website_path'] ?? $company->website_path ?? ''),
                    'sell_like_crazy' => (array) ($draft['sell_like_crazy'] ?? []),
                    'launch_checklist' => (array) ($draft['launch_checklist'] ?? []),
                ],
                'funnel_blocks_json' => [
                    'hero' => (array) ($draft['hero'] ?? []),
                    'sections' => (array) ($draft['sections'] ?? []),
                ],
                'offer_stack_json' => [
                    'starter_offer' => (array) ($draft['starter_offer'] ?? []),
                    'engine_sync' => (array) ($draft['engine_sync'] ?? []),
                ],
                'acquisition_system_json' => array_merge(
                    is_array($company->launchSystems()->latest('id')->first()?->acquisition_system_json) ? $company->launchSystems()->latest('id')->first()->acquisition_system_json : [],
                    ['source' => 'website_autopilot']
                ),
                'applied_at' => now(),
                'last_reviewed_at' => now(),
            ]
        );

        $company->forceFill([
            'launch_stage' => 'launch_system_locked',
            'website_generation_status' => 'approved',
        ])->save();

        return redirect()->route('website')->with('success', 'Your website funnel is now locked into the active launch system.');
    }

    public function founderRegenerateWebsiteDraftBlock(Request $request, WebsiteAutopilotService $websiteAutopilotService): RedirectResponse
    {
        /** @var \App\Models\Founder $founder */
        $founder = Auth::user();
        if (!$founder->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'block' => ['required', Rule::in(['hero', 'cta', 'offer_stack', 'faq'])],
        ]);

        $result = $websiteAutopilotService->regenerateDraftBlock($founder, (string) $validated['block']);
        if (!($result['ok'] ?? false)) {
            return redirect()->route('website')->with('error', (string) ($result['error'] ?? 'Hatchers OS could not regenerate that draft block.'));
        }

        return redirect()->route('website')->with('success', ucfirst(str_replace('_', ' ', (string) $validated['block'])) . ' regenerated inside your launch system draft.');
    }

    public function adminControl(AdminOperationsService $adminOperationsService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'founder_operations');

        return view('os.admin-control', [
            'pageTitle' => 'Admin Control',
            'workspace' => $adminOperationsService->build($user),
        ]);
    }

    public function adminAssignMentor(
        Request $request,
        AdminOperationsService $adminOperationsService,
        OsOperationsLogService $logService
    ): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'mentor_management');

        $validated = $request->validate([
            'founder_id' => ['required', 'integer', 'exists:founders,id'],
            'mentor_id' => ['nullable', 'integer', 'exists:founders,id'],
        ]);

        $adminOperationsService->assignMentor(
            (int) $validated['founder_id'],
            !empty($validated['mentor_id']) ? (int) $validated['mentor_id'] : null
        );

        $logService->recordAudit(
            $user,
            'mentor_assignment_update',
            'founder',
            (int) $validated['founder_id'],
            !empty($validated['mentor_id'])
                ? 'Admin updated a founder mentor assignment from Hatchers Ai Business OS.'
                : 'Admin removed a founder mentor assignment from Hatchers Ai Business OS.',
            ['mentor_id' => !empty($validated['mentor_id']) ? (int) $validated['mentor_id'] : null]
        );

        return redirect()->route('admin.control')->with('success', 'Mentor assignment updated from Hatchers OS.');
    }

    public function adminUpdateSubscription(
        Request $request,
        AdminOperationsService $adminOperationsService,
        OsOperationsLogService $logService
    ): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'founder_operations');

        $validated = $request->validate([
            'founder_id' => ['required', 'integer', 'exists:founders,id'],
            'plan_code' => ['required', 'string', 'max:120'],
            'plan_name' => ['required', 'string', 'max:255'],
            'billing_status' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $adminOperationsService->updateSubscription((int) $validated['founder_id'], $validated);

        $logService->recordAudit(
            $user,
            'subscription_update',
            'founder',
            (int) $validated['founder_id'],
            'Admin updated founder subscription state from Hatchers Ai Business OS.',
            [
                'plan_code' => $validated['plan_code'],
                'billing_status' => $validated['billing_status'],
                'amount' => (float) $validated['amount'],
            ]
        );

        return redirect()->route('admin.control')->with('success', 'Subscription state updated from Hatchers OS.');
    }

    public function adminUpdateFounder(
        Request $request,
        AdminOperationsService $adminOperationsService,
        OsOperationsLogService $logService
    ): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'founder_operations');

        $founderId = (int) $request->input('founder_id');
        $validated = $request->validate([
            'founder_id' => ['required', 'integer', 'exists:founders,id'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('founders', 'email')->ignore($founderId)],
            'phone' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:10'],
            'status' => ['required', Rule::in(['active', 'paused', 'blocked'])],
            'company_name' => ['required', 'string', 'max:255'],
            'company_brief' => ['nullable', 'string', 'max:2000'],
            'business_model' => ['required', 'in:product,service,hybrid'],
            'industry' => ['nullable', 'string', 'max:255'],
            'stage' => ['required', 'in:idea,launching,operating,scaling'],
            'website_status' => ['required', 'in:not_started,in_progress,live'],
        ]);

        $adminOperationsService->updateFounderProfile((int) $validated['founder_id'], $validated);

        $logService->recordAudit(
            $user,
            'founder_profile_update',
            'founder',
            (int) $validated['founder_id'],
            'Admin updated founder profile data from Hatchers Ai Business OS.',
            [
                'status' => $validated['status'],
                'business_model' => $validated['business_model'],
                'stage' => $validated['stage'],
                'website_status' => $validated['website_status'],
            ]
        );

        return redirect()->route('admin.control')->with('success', 'Founder profile updated from Hatchers OS.');
    }

    public function adminUpdateMentorProfile(
        Request $request,
        AdminOperationsService $adminOperationsService,
        OsOperationsLogService $logService
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'system_access');

        $mentorId = (int) $request->input('mentor_id');
        $validated = $request->validate([
            'mentor_id' => ['required', 'integer', 'exists:founders,id'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('founders', 'email')->ignore($mentorId)],
            'phone' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:10'],
            'timezone' => ['required', 'string', 'max:120'],
            'status' => ['required', Rule::in(['active', 'paused', 'blocked'])],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(['founder_portfolio', 'mentor_notes', 'mentor_execution_updates', 'atlas_context'])],
        ]);

        $adminOperationsService->updateMentorProfile($mentorId, $validated);
        $logService->recordAudit(
            $user,
            'mentor_profile_update',
            'mentor',
            $mentorId,
            'Admin updated mentor profile and access controls from Hatchers Ai Business OS.',
            ['permissions' => array_values($validated['permissions'] ?? [])]
        );

        return redirect()->route('admin.system-access')->with('success', 'Mentor profile updated from Hatchers OS.');
    }

    public function adminUpdateAdminProfile(
        Request $request,
        AdminOperationsService $adminOperationsService,
        OsOperationsLogService $logService
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'system_access');

        $adminId = (int) $request->input('admin_id');
        $validated = $request->validate([
            'admin_id' => ['required', 'integer', 'exists:founders,id'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('founders', 'email')->ignore($adminId)],
            'phone' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:10'],
            'timezone' => ['required', 'string', 'max:120'],
            'status' => ['required', Rule::in(['active', 'paused', 'blocked'])],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(['subscriber_reporting', 'founder_operations', 'mentor_management', 'commerce_control', 'module_monitoring', 'exception_resolution', 'system_access'])],
        ]);

        $adminOperationsService->updateAdminProfile($adminId, $validated);
        $logService->recordAudit(
            $user,
            'admin_profile_update',
            'admin',
            $adminId,
            'Admin updated admin profile and permission controls from Hatchers Ai Business OS.',
            ['permissions' => array_values($validated['permissions'] ?? [])]
        );

        return redirect()->route('admin.system-access')->with('success', 'Admin profile updated from Hatchers OS.');
    }

    public function adminSyncFounder(
        Request $request,
        FounderModuleSyncService $founderModuleSyncService,
        OsOperationsLogService $logService
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'founder_operations');

        $validated = $request->validate([
            'founder_id' => ['required', 'integer', 'exists:founders,id'],
            'target' => ['required', Rule::in(['lms', 'atlas', 'bazaar', 'servio', 'all'])],
        ]);

        $founder = Founder::query()->with('company')->findOrFail((int) $validated['founder_id']);
        $result = $founderModuleSyncService->syncFounder($founder, $validated['target']);

        if (!empty($result['ok'])) {
            $logService->recordAudit(
                $user,
                'founder_sync',
                'founder',
                $founder->id,
                'Admin triggered a founder sync from Hatchers Ai Business OS.',
                ['target' => $validated['target']]
            );
        } else {
            $logService->recordException(
                $validated['target'] === 'all' ? 'multiple' : (string) $validated['target'],
                'founder_sync',
                (string) ($result['message'] ?? 'Founder sync failed.'),
                $founder->id,
                ['target' => $validated['target']]
            );
        }

        return redirect()->route('admin.control')->with(
            empty($result['ok']) ? 'error' : 'success',
            $result['message'] ?? 'Founder sync completed.'
        );
    }

    public function adminRetryModuleSync(
        Request $request,
        FounderModuleSyncService $founderModuleSyncService,
        OsOperationsLogService $logService
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'module_monitoring');

        $validated = $request->validate([
            'target' => ['required', Rule::in(['lms', 'atlas', 'bazaar', 'servio', 'all'])],
        ]);

        $result = $founderModuleSyncService->retryModuleAcrossFounders($validated['target']);

        if (!empty($result['ok'])) {
            $logService->recordAudit(
                $user,
                'module_retry_sync',
                'module',
                null,
                'Admin triggered a module retry sync from Hatchers Ai Business OS.',
                ['target' => $validated['target']]
            );
        } else {
            $logService->recordException(
                $validated['target'] === 'all' ? 'multiple' : (string) $validated['target'],
                'retry_sync',
                (string) ($result['message'] ?? 'Module retry sync failed.'),
                null,
                ['target' => $validated['target']]
            );
        }

        return redirect()->route('dashboard.admin')->with(
            empty($result['ok']) ? 'error' : 'success',
            $result['message'] ?? 'Module retry completed.'
        );
    }

    public function adminResolveException(
        OsOperationException $exception,
        OsOperationsLogService $logService
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'exception_resolution');

        $logService->resolveException($exception);
        $logService->recordAudit(
            $user,
            'exception_resolve',
            'exception',
            $exception->id,
            'Admin resolved an OS exception queue item.',
            ['module' => $exception->module, 'operation' => $exception->operation]
        );

        return redirect()->route('admin.control')->with('success', 'Exception queue item resolved from Hatchers Ai Business OS.');
    }

    public function updateWebsite(
        Request $request,
        WebsiteProvisioningService $websiteProvisioningService
    ): RedirectResponse {
        /** @var \App\Models\Founder $founder */
        $founder = Auth::user();
        $company = $founder->company;

        $validated = $request->validate([
            'website_engine' => ['required', Rule::in(['bazaar', 'servio'])],
            'website_mode' => ['required', Rule::in(['product', 'service', 'hybrid'])],
            'website_title' => ['required', 'string', 'max:255'],
            'website_path' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9\\/-]+$/'],
            'theme_template' => ['required', 'string', 'max:20'],
        ]);

        if ($validated['website_mode'] === 'product') {
            $validated['website_engine'] = 'bazaar';
        } elseif ($validated['website_mode'] === 'service') {
            $validated['website_engine'] = 'servio';
        }
        $validated['website_engine'] = $this->resolveWebsiteEngineForBusinessModel($validated['website_engine'], $validated['website_mode']);

        $result = $websiteProvisioningService->applyWebsiteSetup($founder, $validated);
        if (!$result['ok']) {
            return back()->with('error', $result['error'] ?? 'Hatchers OS could not save the website setup.');
        }

        if (!empty($company)) {
            $company->website_engine = $validated['website_engine'];
            $company->business_model = $validated['website_mode'];
            $company->website_status = 'in_progress';
            $company->website_generation_status = 'ready_for_review';
            $company->website_path = trim(strtolower((string) $validated['website_path']), '/');
            $company->website_url = $this->buildCompanyWebsiteUrl($company, $validated['website_engine']);
            if (!empty($result['public_url']) && !$this->isOsHostedWebsiteUrl((string) $result['public_url'])) {
                $company->engine_public_url = (string) $result['public_url'];
            }
            $company->save();
        }

        $successMessage = ($result['bridge_status'] ?? null) === 'pending'
            ? 'Website setup saved in Hatchers OS. Your public OS website path is ready now, and engine sync can be completed later.'
            : 'Website setup saved. Hatchers OS updated the underlying website engine for you.';

        return redirect()->route('website')->with('success', $successMessage);
    }

    public function publishWebsite(
        Request $request,
        WebsiteProvisioningService $websiteProvisioningService
    ): RedirectResponse {
        /** @var \App\Models\Founder $founder */
        $founder = Auth::user();
        $company = $founder->company;

        $validated = $request->validate([
            'website_engine' => ['required', Rule::in(['bazaar', 'servio'])],
        ]);
        $validated['website_engine'] = $this->resolveWebsiteEngineForBusinessModel(
            $validated['website_engine'],
            (string) ($company?->business_model ?? '')
        );

        $result = $websiteProvisioningService->publishWebsite($founder, $validated['website_engine']);
        if (!$result['ok']) {
            return back()->with('error', $result['error'] ?? 'Hatchers OS could not publish the website.');
        }

        if (!empty($company)) {
            if (blank($company->website_path)) {
                $company->website_path = trim(strtolower((string) str($company->company_name ?: ($founder->full_name ?? 'your-business'))->slug('-')), '/');
            }
            $company->website_engine = $validated['website_engine'];
            $company->website_status = 'live';
            $company->website_generation_status = 'published';
            $company->launch_stage = 'website_live';
            $company->website_url = $this->buildCompanyWebsiteUrl($company, $validated['website_engine']);
            if (!empty($result['public_url']) && !$this->isOsHostedWebsiteUrl((string) $result['public_url'])) {
                $company->engine_public_url = (string) $result['public_url'];
            }
            $company->save();
        }

        $successMessage = ($result['bridge_status'] ?? null) === 'pending'
            ? 'Website published on Hatchers OS. Your app.hatchers.ai public site is live now, while engine bridge sync remains optional.'
            : 'Website published from Hatchers OS.';

        return redirect()->route('website')->with('success', $successMessage);
    }

    public function publicWebsite(string $websitePath, PublicWebsiteService $publicWebsiteService)
    {
        $company = $this->resolvePublicWebsiteCompany($websitePath);
        if (!$company) {
            abort(404);
        }

        $site = $publicWebsiteService->build($company);
        if (($site['uses_engine_storefront'] ?? false) && !empty($site['engine_proxy_url'])) {
            return $this->proxyEngineStorefront($company, '', request(), $publicWebsiteService);
        }

        return view('os.public-website', [
            'pageTitle' => (string) ($company->company_name ?: 'Business Website'),
            'site' => $site,
            'sourceContext' => [
                'src' => trim((string) request()->query('src', '')),
                'promo' => trim((string) request()->query('promo', '')),
                'offer' => trim((string) request()->query('offer', '')),
            ],
        ]);
    }

    public function publicWebsiteProxy(string $websiteRoot, string $proxyPath, Request $request, PublicWebsiteService $publicWebsiteService)
    {
        $company = $this->resolvePublicWebsiteRootCompany($websiteRoot);
        if (!$company) {
            abort(404);
        }

        return $this->proxyEngineStorefront($company, $proxyPath, $request, $publicWebsiteService);
    }

    public function publicWebsiteIntroRequest(string $websitePath, Request $request): RedirectResponse
    {
        $company = $this->resolvePublicWebsiteCompany($websitePath);
        if (!$company || !$company->founder) {
            abort(404);
        }

        $validated = $request->validate([
            'lead_name' => ['required', 'string', 'max:191'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_mobile' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:191'],
            'offer_title' => ['nullable', 'string', 'max:191'],
            'src' => ['nullable', 'string', 'max:64'],
            'promo' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $channel = trim((string) ($validated['src'] ?? 'website'));
        $channel = str_replace('-', '_', strtolower($channel));
        if ($channel === '') {
            $channel = 'website';
        }

        $contactParts = array_filter([
            trim((string) ($validated['customer_email'] ?? '')),
            trim((string) ($validated['customer_mobile'] ?? '')),
        ]);

        FounderLead::query()->create([
            'founder_id' => $company->founder->id,
            'company_id' => $company->id,
            'lead_name' => $validated['lead_name'],
            'lead_channel' => $channel,
            'lead_stage' => 'identified',
            'contact_handle' => implode(' / ', $contactParts),
            'city' => $validated['city'] ?? null,
            'offer_name' => $validated['offer_title'] ?? null,
            'source_notes' => trim(implode(' ', array_filter([
                'Captured from public site intro form.',
                !empty($validated['promo']) ? 'Promo code: ' . $validated['promo'] . '.' : '',
                !empty($validated['notes']) ? 'Visitor note: ' . $validated['notes'] : '',
            ]))),
            'first_contacted_at' => now(),
            'meta_json' => [
                'public_intro' => true,
                'website_path' => $websitePath,
                'source_channel' => $channel,
                'promo_code' => trim((string) ($validated['promo'] ?? '')),
            ],
        ]);

        return redirect()->route('public.website', ['websitePath' => $websitePath, 'src' => $validated['src'] ?? null, 'promo' => $validated['promo'] ?? null])->with('success', 'Request received. The founder now has your details inside Hatchers Ai Business OS.');
    }

    public function publicWebsiteOrderRequest(
        string $websitePath,
        Request $request,
        OsAssistantActionService $assistantActionService,
        PublicWebsiteService $publicWebsiteService,
        OsStripeService $stripeService
    ): RedirectResponse {
        $company = $this->resolvePublicWebsiteCompany($websitePath);
        if (!$company) {
            abort(404);
        }

        $site = $publicWebsiteService->build($company);
        if (($site['business_model'] ?? 'hybrid') === 'service') {
            return redirect()->route('public.website', ['websitePath' => $websitePath])->with('error', 'This website currently accepts booking requests, not product orders.');
        }

        $validated = $request->validate([
            'offer_title' => ['required', 'string', 'max:255'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_mobile' => ['required', 'string', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'selected_variant' => ['nullable', 'string', 'max:255'],
            'selected_extras' => ['nullable', 'array'],
            'selected_extras.*' => ['string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'building' => ['required', 'string', 'max:255'],
            'landmark' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:255'],
            'delivery_area' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'src' => ['nullable', 'string', 'max:64'],
            'promo' => ['nullable', 'string', 'max:64'],
            'payment_method_choice' => ['required', Rule::in(['online', 'cash'])],
        ]);
        $websiteRouteParams = [
            'websitePath' => $websitePath,
            'src' => $validated['src'] ?? null,
            'promo' => $validated['promo'] ?? null,
            'offer' => $validated['offer_title'] ?? null,
        ];

        $founder = $company->founder;
        if (!$founder) {
            return redirect()->route('public.website', $websiteRouteParams)->with('error', 'This public site is missing a founder account connection.');
        }

        $offer = $this->resolvePublicWebsiteOffer($site, 'product', (string) $validated['offer_title']);
        if (!$offer) {
            return redirect()->route('public.website', $websiteRouteParams)->with('error', 'That product is no longer available on the public OS site.');
        }

        $allowedVariants = collect($offer['request_options']['variants'] ?? [])->pluck('name')->filter()->values();
        $selectedVariant = trim((string) ($validated['selected_variant'] ?? ''));
        if ($selectedVariant !== '' && !$allowedVariants->contains($selectedVariant)) {
            return redirect()->route('public.website', $websiteRouteParams)->with('error', 'That product variant is no longer available.');
        }

        $allowedExtras = collect($offer['request_options']['extras'] ?? [])->pluck('name')->filter()->values();
        $selectedExtras = collect($validated['selected_extras'] ?? [])->filter()->values();
        if ($selectedExtras->diff($allowedExtras)->isNotEmpty()) {
            return redirect()->route('public.website', $websiteRouteParams)->with('error', 'One or more selected extras are no longer available.');
        }

        $paymentCollection = (string) ($offer['request_options']['payment_collection'] ?? 'both');
        $allowedPaymentChoices = $this->allowedPublicPaymentChoices($paymentCollection);
        $paymentChoice = (string) $validated['payment_method_choice'];
        $sourceTag = $this->publicWebsiteSourceTag($validated);
        if (!in_array($paymentChoice, $allowedPaymentChoices, true)) {
            return redirect()->route('public.website', $websiteRouteParams)->with('error', 'That payment option is not available for this product.');
        }

        $attributes = [
            'target_name' => (string) $validated['offer_title'],
            'customer_name' => (string) $validated['customer_name'],
            'customer_email' => (string) $validated['customer_email'],
            'customer_mobile' => (string) $validated['customer_mobile'],
            'quantity' => (string) ((int) ($validated['quantity'] ?? 1)),
            'selected_variant' => $selectedVariant,
            'selected_extras' => $selectedExtras->all(),
            'address' => (string) $validated['address'],
            'building' => (string) $validated['building'],
            'landmark' => (string) $validated['landmark'],
            'postal_code' => (string) $validated['postal_code'],
            'delivery_area' => (string) $validated['delivery_area'],
            'description' => $this->appendPublicSourceNote((string) ($validated['notes'] ?? ''), $sourceTag),
            'notes' => $this->appendPublicSourceNote((string) ($validated['notes'] ?? ''), $sourceTag),
            'source_channel' => trim((string) ($validated['src'] ?? '')),
            'promo_code' => trim((string) ($validated['promo'] ?? '')),
        ];

        if ($paymentChoice === 'online') {
            return $this->startPublicCheckout(
                $websitePath,
                $company,
                $site,
                $offer,
                $founder,
                'bazaar',
                'order',
                $attributes,
                $stripeService
            );
        }

        $result = $assistantActionService->createPublicCommerceRequest(
            $founder,
            'bazaar',
            'order',
            (string) $validated['offer_title'],
            array_merge($attributes, [
                'payment_type' => '1',
                'payment_status' => 'unpaid',
                'payment_method_choice' => 'cash',
            ])
        );

        $redirect = redirect()->route('public.website', $websiteRouteParams);
        if (!($result['success'] ?? false)) {
            return $redirect->with('error', $result['reply'] ?? 'The order request could not be created right now.');
        }

        return $redirect->with('success', 'Order request sent. The founder can now manage it from Hatchers Ai Business OS.');
    }

    public function publicWebsiteBookingRequest(
        string $websitePath,
        Request $request,
        OsAssistantActionService $assistantActionService,
        PublicWebsiteService $publicWebsiteService,
        OsStripeService $stripeService
    ): RedirectResponse {
        $company = $this->resolvePublicWebsiteCompany($websitePath);
        if (!$company) {
            abort(404);
        }

        $site = $publicWebsiteService->build($company);
        if (($site['business_model'] ?? 'hybrid') === 'product') {
            return redirect()->route('public.website', ['websitePath' => $websitePath])->with('error', 'This website currently accepts product orders, not service bookings.');
        }

        $validated = $request->validate([
            'offer_title' => ['required', 'string', 'max:255'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_mobile' => ['required', 'string', 'max:255'],
            'booking_date' => ['required', 'date'],
            'booking_time' => ['required', 'date_format:H:i'],
            'booking_endtime' => ['nullable', 'date_format:H:i'],
            'selected_additional_services' => ['nullable', 'array'],
            'selected_additional_services.*' => ['string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'src' => ['nullable', 'string', 'max:64'],
            'promo' => ['nullable', 'string', 'max:64'],
            'payment_method_choice' => ['required', Rule::in(['online', 'cash'])],
        ]);
        $websiteRouteParams = [
            'websitePath' => $websitePath,
            'src' => $validated['src'] ?? null,
            'promo' => $validated['promo'] ?? null,
            'offer' => $validated['offer_title'] ?? null,
        ];

        $founder = $company->founder;
        if (!$founder) {
            return redirect()->route('public.website', $websiteRouteParams)->with('error', 'This public site is missing a founder account connection.');
        }

        $offer = $this->resolvePublicWebsiteOffer($site, 'service', (string) $validated['offer_title']);
        if (!$offer) {
            return redirect()->route('public.website', $websiteRouteParams)->with('error', 'That service is no longer available on the public OS site.');
        }

        $allowedAddOns = collect($offer['request_options']['additional_services'] ?? [])->pluck('name')->filter()->values();
        $selectedAddOns = collect($validated['selected_additional_services'] ?? [])->filter()->values();
        if ($selectedAddOns->diff($allowedAddOns)->isNotEmpty()) {
            return redirect()->route('public.website', $websiteRouteParams)->with('error', 'One or more selected add-ons are no longer available.');
        }

        $durationMinutes = $this->publicServiceDurationMinutes($offer);
        $bookingTime = \Carbon\Carbon::createFromFormat('H:i', (string) $validated['booking_time']);
        $bookingEndTime = trim((string) ($validated['booking_endtime'] ?? ''));
        if ($bookingEndTime === '') {
            $bookingEndTime = $bookingTime->copy()->addMinutes($durationMinutes)->format('H:i');
        }
        $endTime = \Carbon\Carbon::createFromFormat('H:i', $bookingEndTime);
        if ($endTime->lessThanOrEqualTo($bookingTime)) {
            return redirect()->route('public.website', $websiteRouteParams)->with('error', 'Booking end time must be after the start time.');
        }

        $availableDays = collect($offer['request_options']['availability_days'] ?? [])->map(fn ($day) => strtolower(trim((string) $day)))->filter()->values();
        $bookingDate = \Carbon\Carbon::parse((string) $validated['booking_date']);
        if ($availableDays->isNotEmpty() && !$availableDays->contains(strtolower($bookingDate->format('l')))) {
            return redirect()->route('public.website', $websiteRouteParams)->with('error', 'That service is not available on the selected day.');
        }

        $openTime = trim((string) ($offer['request_options']['open_time'] ?? ''));
        $closeTime = trim((string) ($offer['request_options']['close_time'] ?? ''));
        if ($openTime !== '' && $closeTime !== '') {
            $open = \Carbon\Carbon::createFromFormat('H:i', substr($openTime, 0, 5));
            $close = \Carbon\Carbon::createFromFormat('H:i', substr($closeTime, 0, 5));
            if ($bookingTime->lt($open) || $endTime->gt($close)) {
                return redirect()->route('public.website', $websiteRouteParams)->with('error', 'That booking time sits outside the service availability window.');
            }
        }

        $paymentCollection = (string) ($offer['request_options']['payment_collection'] ?? 'both');
        $allowedPaymentChoices = $this->allowedPublicPaymentChoices($paymentCollection);
        $paymentChoice = (string) $validated['payment_method_choice'];
        $sourceTag = $this->publicWebsiteSourceTag($validated);
        if (!in_array($paymentChoice, $allowedPaymentChoices, true)) {
            return redirect()->route('public.website', $websiteRouteParams)->with('error', 'That payment option is not available for this service.');
        }

        $attributes = [
            'target_name' => (string) $validated['offer_title'],
            'customer_name' => (string) $validated['customer_name'],
            'customer_email' => (string) $validated['customer_email'],
            'customer_mobile' => (string) $validated['customer_mobile'],
            'booking_date' => (string) $validated['booking_date'],
            'booking_time' => (string) $validated['booking_time'],
            'booking_endtime' => $bookingEndTime,
            'selected_additional_services' => $selectedAddOns->all(),
            'address' => (string) ($validated['address'] ?? ''),
            'landmark' => (string) ($validated['landmark'] ?? ''),
            'postal_code' => (string) ($validated['postal_code'] ?? ''),
            'city' => (string) ($validated['city'] ?? ''),
            'state' => (string) ($validated['state'] ?? ''),
            'country' => (string) ($validated['country'] ?? ''),
            'description' => $this->appendPublicSourceNote((string) ($validated['notes'] ?? ''), $sourceTag),
            'notes' => $this->appendPublicSourceNote((string) ($validated['notes'] ?? ''), $sourceTag),
            'source_channel' => trim((string) ($validated['src'] ?? '')),
            'promo_code' => trim((string) ($validated['promo'] ?? '')),
        ];

        if ($paymentChoice === 'online') {
            return $this->startPublicCheckout(
                $websitePath,
                $company,
                $site,
                $offer,
                $founder,
                'servio',
                'booking',
                $attributes,
                $stripeService
            );
        }

        $result = $assistantActionService->createPublicCommerceRequest(
            $founder,
            'servio',
            'booking',
            (string) $validated['offer_title'],
            array_merge($attributes, [
                'payment_type' => '1',
                'payment_status' => 'unpaid',
                'payment_method_choice' => 'cash',
            ])
        );

        $redirect = redirect()->route('public.website', $websiteRouteParams);
        if (!($result['success'] ?? false)) {
            return $redirect->with('error', $result['reply'] ?? 'The booking request could not be created right now.');
        }

        return $redirect->with('success', 'Booking request sent. The founder can now manage it from Hatchers Ai Business OS.');
    }

    public function publicCheckoutSuccess(
        string $websitePath,
        Request $request,
        OsStripeService $stripeService,
        OsAssistantActionService $assistantActionService,
        OsWalletService $walletService,
        PublicWebsiteService $publicWebsiteService
    ): RedirectResponse {
        $company = $this->resolvePublicWebsiteCompany($websitePath);
        if (!$company) {
            abort(404);
        }

        $sessionId = trim((string) $request->query('session_id', ''));
        $checkoutSession = PublicCheckoutSession::query()->where('stripe_session_id', $sessionId)->first();
        if (!$checkoutSession) {
            return redirect()->route('public.website', ['websitePath' => $websitePath])->with('error', 'That payment session could not be found in Hatchers Ai Business OS.');
        }

        if ($checkoutSession->checkout_status === 'completed') {
            return redirect()->route('public.website', ['websitePath' => $websitePath])->with('success', 'Payment completed and the founder has the sale in their wallet.');
        }

        $stripeResult = $stripeService->retrieveCheckoutSession($sessionId);
        if (!($stripeResult['success'] ?? false)) {
            return redirect()->route('public.website', ['websitePath' => $websitePath])->with('error', $stripeResult['message'] ?? 'Stripe payment verification failed.');
        }

        if (($stripeResult['payment_status'] ?? '') !== 'paid') {
            return redirect()->route('public.website', ['websitePath' => $websitePath])->with('error', 'Payment has not completed yet. Please finish checkout first.');
        }

        $finalize = $this->finalizePublicCheckoutSession(
            $checkoutSession,
            $stripeResult,
            $assistantActionService,
            $walletService,
            $publicWebsiteService
        );

        if (!($finalize['success'] ?? false)) {
            return redirect()->route('public.website', ['websitePath' => $websitePath])->with('error', $finalize['message'] ?? 'The paid order or booking could not be created right now.');
        }

        return redirect()->route('public.website', ['websitePath' => $websitePath])->with('success', 'Payment completed successfully. The founder can now see the sale in their OS wallet.');
    }

    public function publicCheckoutCancel(string $websitePath): RedirectResponse
    {
        return redirect()->route('public.website', ['websitePath' => $websitePath])->with('error', 'Checkout was canceled before payment completed.');
    }

    public function stripeWebhook(
        Request $request,
        OsStripeService $stripeService,
        OsAssistantActionService $assistantActionService,
        OsWalletService $walletService,
        PublicWebsiteService $publicWebsiteService
    ): JsonResponse {
        $payload = (string) $request->getContent();
        $signature = (string) $request->header('Stripe-Signature', '');
        $verified = $stripeService->verifyWebhookSignature($payload, $signature);

        if (!($verified['success'] ?? false)) {
            Log::warning('Stripe webhook verification failed.', [
                'message' => $verified['message'] ?? 'Verification failed.',
            ]);

            return response()->json(['ok' => false, 'message' => $verified['message'] ?? 'Invalid signature.'], 400);
        }

        $event = is_array($verified['event'] ?? null) ? $verified['event'] : [];
        $type = (string) ($event['type'] ?? '');
        $object = is_array($event['data']['object'] ?? null) ? $event['data']['object'] : [];

        if ($type === 'checkout.session.completed' || $type === 'checkout.session.async_payment_succeeded') {
            $sessionId = (string) ($object['id'] ?? '');
            $checkoutSession = PublicCheckoutSession::query()->where('stripe_session_id', $sessionId)->first();

            if ($checkoutSession && (string) ($object['payment_status'] ?? '') === 'paid') {
                $this->finalizePublicCheckoutSession(
                    $checkoutSession,
                    [
                        'payment_intent' => (string) ($object['payment_intent'] ?? ''),
                        'payment_status' => (string) ($object['payment_status'] ?? ''),
                        'status' => (string) ($object['status'] ?? ''),
                        'amount_total' => ((float) ($object['amount_total'] ?? 0)) / 100,
                        'currency' => strtoupper((string) ($object['currency'] ?? 'USD')),
                    ],
                    $assistantActionService,
                    $walletService,
                    $publicWebsiteService
                );
            }
        }

        if ($type === 'checkout.session.expired') {
            $sessionId = (string) ($object['id'] ?? '');
            PublicCheckoutSession::query()
                ->where('stripe_session_id', $sessionId)
                ->where('checkout_status', 'pending')
                ->update([
                    'checkout_status' => 'expired',
                ]);
        }

        if ($type === 'account.updated') {
            $accountId = (string) ($object['id'] ?? '');
            $payoutAccount = FounderPayoutAccount::query()->where('stripe_account_id', $accountId)->first();

            if ($payoutAccount) {
                $payoutAccount->forceFill([
                    'stripe_onboarding_status' => !empty($object['payouts_enabled']) ? 'complete' : (!empty($object['details_submitted']) ? 'review' : 'pending'),
                    'stripe_charges_enabled' => (bool) ($object['charges_enabled'] ?? false),
                    'stripe_payouts_enabled' => (bool) ($object['payouts_enabled'] ?? false),
                    'stripe_details_submitted_at' => !empty($object['details_submitted']) && !$payoutAccount->stripe_details_submitted_at ? now() : $payoutAccount->stripe_details_submitted_at,
                    'stripe_payouts_enabled_at' => !empty($object['payouts_enabled']) && !$payoutAccount->stripe_payouts_enabled_at ? now() : $payoutAccount->stripe_payouts_enabled_at,
                    'bank_currency' => strtoupper((string) ($object['default_currency'] ?? ($payoutAccount->bank_currency ?: 'USD'))),
                    'bank_country' => strtoupper((string) ($object['country'] ?? ($payoutAccount->bank_country ?: 'US'))),
                    'status' => !empty($object['payouts_enabled']) ? 'active' : 'pending',
                    'meta_json' => array_merge((array) ($payoutAccount->meta_json ?? []), [
                        'stripe_webhook_synced_at' => now()->toDateTimeString(),
                    ]),
                ])->save();
            }
        }

        if ($type === 'charge.refunded') {
            $paymentIntentId = (string) ($object['payment_intent'] ?? '');
            $amountRefunded = ((float) ($object['amount_refunded'] ?? 0)) / 100;

            if ($paymentIntentId !== '' && $amountRefunded > 0) {
                $checkoutSession = PublicCheckoutSession::query()
                    ->where('stripe_payment_intent_id', $paymentIntentId)
                    ->first();

                if ($checkoutSession) {
                    $this->applyStripeRevenueReversal(
                        $checkoutSession,
                        'refund',
                        $amountRefunded,
                        (string) ($object['id'] ?? ''),
                        $assistantActionService,
                        $walletService
                    );
                }
            }
        }

        if ($type === 'charge.dispute.created') {
            $paymentIntentId = (string) ($object['payment_intent'] ?? '');
            $amount = ((float) ($object['amount'] ?? 0)) / 100;

            if ($paymentIntentId !== '' && $amount > 0) {
                $checkoutSession = PublicCheckoutSession::query()
                    ->where('stripe_payment_intent_id', $paymentIntentId)
                    ->first();

                if ($checkoutSession) {
                    $this->applyStripeRevenueReversal(
                        $checkoutSession,
                        'dispute',
                        $amount,
                        (string) ($object['id'] ?? ''),
                        $assistantActionService,
                        $walletService
                    );
                }
            }
        }

        return response()->json(['ok' => true]);
    }

    private function startPublicCheckout(
        string $websitePath,
        Company $company,
        array $site,
        array $offer,
        Founder $founder,
        string $platform,
        string $category,
        array $attributes,
        OsStripeService $stripeService
    ): RedirectResponse {
        $amount = $this->calculatePublicCheckoutAmount($site, $offer, $attributes);
        if ($amount <= 0) {
            return redirect()->route('public.website', ['websitePath' => $websitePath])->with('error', 'This checkout cannot start because the offer total is invalid.');
        }

        $currency = strtoupper(trim((string) ($offer['currency'] ?? $site['metrics'][0]['currency'] ?? 'USD')));
        $session = $stripeService->createCheckoutSession([
            'amount' => $amount,
            'currency' => strtolower($currency),
            'product_name' => (string) ($offer['title'] ?? 'Hatchers purchase'),
            'customer_email' => (string) ($attributes['customer_email'] ?? ''),
            'success_url' => route('public.checkout.success', ['websitePath' => $websitePath]),
            'cancel_url' => route('public.checkout.cancel', ['websitePath' => $websitePath]),
            'metadata' => [
                'website_path' => $websitePath,
                'platform' => $platform,
                'category' => $category,
                'offer_title' => (string) ($offer['title'] ?? ''),
                'founder_id' => $founder->id,
                'company_id' => $company->id,
            ],
        ]);

        if (!($session['success'] ?? false)) {
            return redirect()->route('public.website', ['websitePath' => $websitePath])->with('error', $session['message'] ?? 'Stripe checkout could not be created right now.');
        }

        PublicCheckoutSession::updateOrCreate(
            ['stripe_session_id' => (string) $session['id']],
            [
                'founder_id' => $founder->id,
                'company_id' => $company->id,
                'website_path' => $websitePath,
                'platform' => $platform,
                'category' => $category,
                'offer_title' => (string) ($offer['title'] ?? ''),
                'stripe_payment_intent_id' => (string) ($session['payment_intent'] ?? ''),
                'amount' => $amount,
                'currency' => $currency,
                'payment_method_choice' => 'online',
                'checkout_status' => 'pending',
                'payload_json' => $attributes,
                'expires_at' => $session['expires_at'] ?? null,
            ]
        );

        return redirect()->away((string) $session['url']);
    }

    private function finalizePublicCheckoutSession(
        PublicCheckoutSession $checkoutSession,
        array $stripeResult,
        OsAssistantActionService $assistantActionService,
        OsWalletService $walletService,
        PublicWebsiteService $publicWebsiteService
    ): array {
        return DB::transaction(function () use (
            $checkoutSession,
            $stripeResult,
            $assistantActionService,
            $walletService,
            $publicWebsiteService
        ): array {
            $checkoutSession = PublicCheckoutSession::query()
                ->whereKey($checkoutSession->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((string) $checkoutSession->checkout_status === 'completed') {
                return [
                    'success' => true,
                    'message' => 'Checkout was already completed.',
                ];
            }

            $company = $checkoutSession->company;
            $founder = $checkoutSession->founder;
            if (!$company || !$founder) {
                return [
                    'success' => false,
                    'message' => 'This payment session is missing the founder or company connection.',
                ];
            }

            $payload = is_array($checkoutSession->payload_json) ? $checkoutSession->payload_json : [];
            $site = $publicWebsiteService->build($company);
            $offer = $this->resolvePublicWebsiteOffer(
                $site,
                (string) $checkoutSession->category === 'booking' ? 'service' : 'product',
                (string) $checkoutSession->offer_title
            );

            if (!$offer) {
                return [
                    'success' => false,
                    'message' => 'That offer is no longer available on the public OS site.',
                ];
            }

            $result = $assistantActionService->createPublicCommerceRequest(
                $founder,
                (string) $checkoutSession->platform,
                (string) $checkoutSession->category,
                (string) $checkoutSession->offer_title,
                array_merge($payload, [
                    'payment_type' => '3',
                    'payment_status' => 'paid',
                    'payment_id' => (string) ($stripeResult['payment_intent'] ?? $checkoutSession->stripe_payment_intent_id ?? $checkoutSession->stripe_session_id),
                    'payment_method_choice' => 'online',
                ])
            );

            if (!($result['success'] ?? false)) {
                $checkoutSession->forceFill([
                    'checkout_status' => 'processing',
                    'stripe_payment_intent_id' => (string) ($stripeResult['payment_intent'] ?? $checkoutSession->stripe_payment_intent_id),
                    'payload_json' => array_merge($payload, [
                        'processing_error' => (string) ($result['reply'] ?? 'The paid record could not be created.'),
                    ]),
                ])->save();

                return [
                    'success' => false,
                    'message' => (string) ($result['reply'] ?? 'The paid order or booking could not be created right now.'),
                ];
            }

            $walletService->creditCommerceSale(
                $founder,
                $company,
                (string) $checkoutSession->platform,
                (string) $checkoutSession->category,
                (string) ($result['title'] ?? $checkoutSession->offer_title),
                (float) $checkoutSession->amount,
                (string) $checkoutSession->currency,
                [
                    'checkout_session_id' => $checkoutSession->stripe_session_id,
                    'payment_intent_id' => (string) ($stripeResult['payment_intent'] ?? ''),
                    'offer_title' => (string) $checkoutSession->offer_title,
                ]
            );

            $checkoutSession->forceFill([
                'checkout_status' => 'completed',
                'stripe_payment_intent_id' => (string) ($stripeResult['payment_intent'] ?? $checkoutSession->stripe_payment_intent_id),
                'completed_at' => now(),
                'payload_json' => array_merge($payload, [
                    'finalized_at' => now()->toDateTimeString(),
                    'commerce_reference' => (string) ($result['title'] ?? $checkoutSession->offer_title),
                    'commerce_edit_url' => (string) ($result['edit_url'] ?? ''),
                ]),
            ])->save();

            return [
                'success' => true,
                'message' => 'Checkout completed successfully.',
            ];
        });
    }

    private function applyStripeRevenueReversal(
        PublicCheckoutSession $checkoutSession,
        string $reasonType,
        float $grossAmount,
        string $referenceId,
        OsAssistantActionService $assistantActionService,
        OsWalletService $walletService
    ): void {
        DB::transaction(function () use (
            $checkoutSession,
            $reasonType,
            $grossAmount,
            $referenceId,
            $assistantActionService,
            $walletService
        ): void {
            $checkoutSession = PublicCheckoutSession::query()
                ->whereKey($checkoutSession->id)
                ->lockForUpdate()
                ->first();

            if (!$checkoutSession || !in_array((string) $checkoutSession->checkout_status, ['completed', 'processing'], true)) {
                return;
            }

            $founder = $checkoutSession->founder;
            $company = $checkoutSession->company;
            if (!$founder || !$company) {
                return;
            }

            $payload = is_array($checkoutSession->payload_json) ? $checkoutSession->payload_json : [];
            $commerceReference = trim((string) ($payload['commerce_reference'] ?? ''));
            if ($commerceReference === '') {
                return;
            }

            $walletService->refundCommerceSale(
                $founder,
                $company,
                (string) $checkoutSession->platform,
                (string) $checkoutSession->category,
                $commerceReference,
                $grossAmount,
                (string) $checkoutSession->currency,
                [
                    'source' => 'stripe_' . $reasonType . '_webhook',
                    'stripe_reference' => $referenceId,
                    'payment_intent_id' => (string) ($checkoutSession->stripe_payment_intent_id ?? ''),
                ]
            );

            $recordCategory = (string) $checkoutSession->category === 'booking' ? 'booking' : 'order';
            $platform = (string) $checkoutSession->platform;

            foreach ([
                ['field' => 'status', 'value' => 'cancelled'],
                ['field' => 'payment_status', 'value' => 'unpaid'],
                ['field' => 'vendor_note', 'value' => ucfirst($reasonType) . ' recorded from Stripe webhook in Hatchers OS.'],
            ] as $operation) {
                $assistantActionService->updateCommerceOperationFromOs(
                    $founder,
                    $platform,
                    $recordCategory,
                    $commerceReference,
                    (string) $operation['field'],
                    (string) $operation['value']
                );
            }

            $checkoutSession->forceFill([
                'checkout_status' => $reasonType === 'dispute' ? 'disputed' : 'refunded',
                'payload_json' => array_merge($payload, [
                    'reversal_type' => $reasonType,
                    'reversal_reference' => $referenceId,
                    'reversed_at' => now()->toDateTimeString(),
                ]),
            ])->save();
        });
    }

    public function createWebsiteStarter(
        Request $request,
        WebsiteProvisioningService $websiteProvisioningService
    ): RedirectResponse {
        /** @var \App\Models\Founder $founder */
        $founder = Auth::user();

        $validated = $request->validate([
            'website_engine' => ['required', Rule::in(['bazaar', 'servio'])],
            'starter_mode' => ['required', Rule::in(['product', 'service'])],
            'starter_title' => ['required', 'string', 'max:255'],
            'starter_description' => ['nullable', 'string', 'max:4000'],
            'starter_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validated['website_engine'] = $validated['starter_mode'] === 'product' ? 'bazaar' : 'servio';

        $result = $websiteProvisioningService->createStarterRecord($founder, $validated);
        if (!$result['ok']) {
            return back()->with('error', $result['error'] ?? 'Hatchers OS could not create the first website item.');
        }

        FounderActionPlan::create([
            'founder_id' => $founder->id,
            'title' => (string) $validated['starter_title'],
            'description' => $this->serializeCommerceOffer([
                'type' => (string) $validated['starter_mode'],
                'description' => (string) ($validated['starter_description'] ?? ''),
                'price' => number_format((float) ($validated['starter_price'] ?? 0), 2, '.', ''),
                'engine' => (string) $validated['website_engine'],
            ]),
            'platform' => (string) $validated['website_engine'],
            'priority' => 72,
            'status' => 'created',
            'cta_label' => 'Open ' . strtoupper((string) $validated['website_engine']),
            'cta_url' => route('founder.commerce'),
        ]);

        return redirect()->route('founder.commerce')->with('success', 'Starter ' . $validated['starter_mode'] . ' created from Hatchers OS.');
    }

    public function founderSaveCommerceConfig(
        Request $request,
        OsAssistantActionService $actionService,
        AtlasIntelligenceService $atlas
    ): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'setting_type' => ['required', Rule::in(['coupon', 'shipping', 'booking_policy'])],
            'setting_platform' => ['nullable', Rule::in(['bazaar', 'servio'])],
            'title' => ['required', 'string', 'max:255'],
            'field_one' => ['nullable', 'string', 'max:255'],
            'field_two' => ['nullable', 'string', 'max:255'],
            'field_three' => ['nullable', 'string', 'max:255'],
            'field_four' => ['nullable', 'string', 'max:255'],
        ]);

        $platform = (string) ($validated['setting_platform'] ?? '');
        if ($platform === '') {
            $platform = in_array($validated['setting_type'], ['coupon', 'shipping'], true) ? 'bazaar' : 'servio';
        }

        $existing = FounderActionPlan::query()
            ->where('founder_id', $user->id)
            ->where('platform', $platform)
            ->where('title', (string) $validated['title'])
            ->where('description', 'like', 'Config:%')
            ->first();

        $engineReply = null;
        if ((string) $validated['setting_type'] === 'coupon') {
            $engineReply = $actionService->saveCommerceConfigFromOs(
                $user,
                $platform,
                'coupon',
                (string) $validated['title'],
                [
                    'offer_name' => (string) $validated['title'],
                    'offer_code' => (string) ($validated['field_one'] ?? ''),
                    'offer_type' => (string) (($validated['field_two'] ?? '') === 'percent' ? 'percent' : 'fixed'),
                    'offer_amount' => (string) ($validated['field_three'] ?? ''),
                    'min_amount' => 0,
                    'usage_limit' => 0,
                    'description' => (string) ($validated['field_four'] ?? ''),
                    'is_available' => 1,
                ],
                !empty($existing)
            );
        } elseif ((string) $validated['setting_type'] === 'shipping' && $platform === 'bazaar') {
            $engineReply = $actionService->saveCommerceConfigFromOs(
                $user,
                'bazaar',
                'shipping',
                (string) $validated['title'],
                [
                    'area_name' => (string) ($validated['field_one'] ?? $validated['title']),
                    'delivery_charge' => (string) ($validated['field_two'] ?? '0'),
                    'description' => trim(implode(' · ', array_filter([
                        (string) ($validated['field_three'] ?? ''),
                        (string) ($validated['field_four'] ?? ''),
                    ]))),
                    'is_available' => 1,
                ],
                !empty($existing)
            );
        }

        FounderActionPlan::updateOrCreate(
            [
                'founder_id' => $user->id,
                'platform' => $platform,
                'title' => (string) $validated['title'],
                'cta_label' => 'Manage ' . ucfirst(str_replace('_', ' ', (string) $validated['setting_type'])),
            ],
            [
                'description' => $this->serializeCommerceConfig([
                    'type' => $validated['setting_type'],
                    'engine' => $platform,
                    'field_one' => (string) ($validated['field_one'] ?? ''),
                    'field_two' => (string) ($validated['field_two'] ?? ''),
                    'field_three' => (string) ($validated['field_three'] ?? ''),
                    'field_four' => (string) ($validated['field_four'] ?? ''),
                ]),
                'priority' => 55,
                'status' => 'configured',
                'cta_url' => route('founder.commerce'),
            ]
        );

        $atlas->syncFounderMutation($user, [
            'role' => 'founder',
            'action' => $engineReply['action_type'] ?? 'commerce_config_save',
            'field' => (string) $validated['setting_type'],
            'value' => (string) $validated['title'],
            'sync_summary' => $engineReply['sync_summary'] ?? 'Founder saved a commerce config from Hatchers OS.',
        ]);

        $redirect = redirect()->route('founder.commerce')->with(
            'success',
            ucfirst(str_replace('_', ' ', (string) $validated['setting_type'])) . ' saved in Hatchers Ai Business OS.'
        );

        if ($engineReply && !($engineReply['success'] ?? false)) {
            $redirect->with('error', $engineReply['reply'] ?? 'The OS copy was saved, but the engine sync still needs attention.');
        }

        return $redirect;
    }

    public function founderUpdateOrderOperation(
        Request $request,
        OsAssistantActionService $actionService,
        AtlasIntelligenceService $atlas,
        OsWalletService $walletService
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['pending', 'processing', 'completed', 'cancelled'])],
            'payment_status' => ['required', Rule::in(['unpaid', 'paid'])],
            'vendor_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $operations = [
            ['field' => 'status', 'value' => (string) $validated['status']],
            ['field' => 'payment_status', 'value' => (string) $validated['payment_status']],
        ];

        if (trim((string) ($validated['vendor_note'] ?? '')) !== '') {
            $operations[] = ['field' => 'vendor_note', 'value' => (string) $validated['vendor_note']];
        }

        $errors = [];
        foreach ($operations as $operation) {
            $result = $actionService->updateCommerceOperationFromOs(
                $user,
                'bazaar',
                'order',
                (string) $validated['order_number'],
                (string) $operation['field'],
                (string) $operation['value']
            );

            if (!($result['success'] ?? false)) {
                $errors[] = $result['reply'] ?? ('Could not update order ' . $operation['field'] . '.');
            }
        }

        $atlas->syncFounderMutation($user, [
            'role' => 'founder',
            'action' => 'commerce_order_update',
            'field' => 'order',
            'value' => (string) $validated['order_number'],
            'sync_summary' => 'Founder updated a Bazaar order from Hatchers OS.',
        ]);

        $redirect = redirect()->route('founder.commerce.orders')->with('success', 'Order updated from Hatchers Ai Business OS.');
        if (!empty($errors)) {
            $redirect->with('error', implode(' ', $errors));
        }

        if ((string) $validated['payment_status'] === 'paid') {
            $order = collect($this->commerceOperationsWorkspace($user, 'bazaar', ['status' => 'all', 'queue' => 'all'])['recent_orders'] ?? [])
                ->firstWhere('order_number', (string) $validated['order_number']);
            if (is_array($order)) {
                $walletService->creditCommerceSale(
                    $user,
                    $user->company,
                    'bazaar',
                    'order',
                    (string) $validated['order_number'],
                    (float) ($order['grand_total'] ?? 0),
                    (string) ($this->commerceOperationsWorkspace($user, 'bazaar', ['status' => 'all', 'queue' => 'all'])['currency'] ?? 'USD'),
                    ['source' => 'manual_payment_status_update']
                );
            }
        }

        return $redirect;
    }

    public function founderRefundOrder(
        Request $request,
        OsAssistantActionService $actionService,
        OsStripeService $stripeService,
        OsWalletService $walletService,
        AtlasIntelligenceService $atlas
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $workspace = $this->commerceOperationsWorkspace($user, 'bazaar', ['status' => 'all', 'queue' => 'all']);
        $order = collect($workspace['recent_orders'] ?? [])->firstWhere('order_number', (string) $validated['order_number']);
        if (!is_array($order)) {
            return redirect()->route('founder.commerce.orders')->with('error', 'That Bazaar order is not available in the OS snapshot yet.');
        }

        if ((string) ($order['payment_status'] ?? 'unpaid') !== 'paid') {
            return redirect()->route('founder.commerce.orders')->with('error', 'Only paid orders can be refunded from Hatchers Ai Business OS.');
        }

        $transactionId = trim((string) ($order['transaction_id'] ?? ''));
        $grossAmount = (float) ($order['grand_total'] ?? 0);
        $currency = (string) ($workspace['currency'] ?? 'USD');
        $reason = trim((string) ($validated['reason'] ?? ''));

        if ($transactionId !== '' && str_starts_with($transactionId, 'pi_')) {
            $refund = $stripeService->createRefund($transactionId, $grossAmount, $currency, [
                'order_number' => (string) $validated['order_number'],
                'founder_id' => $user->id,
            ]);

            if (!($refund['success'] ?? false)) {
                return redirect()->route('founder.commerce.orders')->with('error', $refund['message'] ?? 'Stripe refund could not be created for this order.');
            }
        }

        $walletService->refundCommerceSale(
            $user,
            $user->company,
            'bazaar',
            'order',
            (string) $validated['order_number'],
            $grossAmount,
            $currency,
            [
                'source' => 'founder_refund',
                'reason' => $reason,
                'transaction_id' => $transactionId,
            ]
        );

        $note = trim(implode(' | ', array_filter([
            (string) ($order['vendor_note'] ?? ''),
            'Refunded in Hatchers OS' . ($reason !== '' ? ': ' . $reason : ''),
        ])));

        foreach ([
            ['field' => 'status', 'value' => 'cancelled'],
            ['field' => 'payment_status', 'value' => 'unpaid'],
            ['field' => 'vendor_note', 'value' => $note],
        ] as $operation) {
            $actionService->updateCommerceOperationFromOs(
                $user,
                'bazaar',
                'order',
                (string) $validated['order_number'],
                (string) $operation['field'],
                (string) $operation['value']
            );
        }

        $atlas->syncFounderMutation($user, [
            'role' => 'founder',
            'action' => 'commerce_order_refund',
            'field' => 'order',
            'value' => (string) $validated['order_number'],
            'sync_summary' => 'Founder refunded a Bazaar order from Hatchers OS.',
        ]);

        return redirect()->route('founder.commerce.orders')->with('success', 'Order refunded from Hatchers Ai Business OS and the founder wallet was reversed.');
    }

    public function founderUpdateOrderCustomer(
        Request $request,
        OsAssistantActionService $actionService,
        AtlasIntelligenceService $atlas
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:255'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'string', 'max:255'],
            'customer_mobile' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'building' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'delivery_area' => ['nullable', 'string', 'max:255'],
        ]);

        $fields = ['customer_name', 'customer_email', 'customer_mobile', 'address', 'building', 'landmark', 'postal_code', 'delivery_area'];
        $errors = [];
        foreach ($fields as $field) {
            $value = (string) ($validated[$field] ?? '');
            if ($field !== 'customer_name' && $value === '') {
                continue;
            }

            $result = $actionService->updateCommerceOperationFromOs(
                $user,
                'bazaar',
                'order',
                (string) $validated['order_number'],
                $field,
                $value
            );

            if (!($result['success'] ?? false)) {
                $errors[] = $result['reply'] ?? ('Could not update order ' . $field . '.');
            }
        }

        $atlas->syncFounderMutation($user, [
            'role' => 'founder',
            'action' => 'commerce_order_customer_update',
            'field' => 'order_customer',
            'value' => (string) $validated['order_number'],
            'sync_summary' => 'Founder updated Bazaar order customer details from Hatchers OS.',
        ]);

        $redirect = redirect()->route('founder.commerce.orders')->with('success', 'Order customer details updated from Hatchers Ai Business OS.');
        if (!empty($errors)) {
            $redirect->with('error', implode(' ', $errors));
        }

        return $redirect;
    }

    public function founderUpdateOrderFulfillment(
        Request $request,
        OsAssistantActionService $actionService,
        AtlasIntelligenceService $atlas
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:255'],
            'delivery_date' => ['nullable', 'date'],
            'delivery_time' => ['nullable', 'string', 'max:255'],
            'order_notes' => ['nullable', 'string', 'max:2000'],
            'customer_message' => ['nullable', 'string', 'max:2000'],
            'message_channel' => ['nullable', Rule::in(['manual', 'email', 'whatsapp', 'sms'])],
            'message_template' => ['nullable', Rule::in(array_keys($this->orderMessageTemplates()))],
        ]);

        $customerMessage = $this->resolveCommerceMessage(
            'order',
            (string) ($validated['message_template'] ?? ''),
            (string) ($validated['customer_message'] ?? ''),
            $validated
        );

        $errors = [];
        $emailFollowupSent = false;
        foreach (['delivery_date', 'delivery_time', 'order_notes', 'customer_message'] as $field) {
            $value = $field === 'customer_message'
                ? $customerMessage
                : trim((string) ($validated[$field] ?? ''));
            if ($value === '') {
                continue;
            }

            $result = $actionService->updateCommerceOperationFromOs(
                $user,
                'bazaar',
                'order',
                (string) $validated['order_number'],
                $field,
                $value,
                $field === 'customer_message'
                    ? ['message_channel' => (string) ($validated['message_channel'] ?? 'manual')]
                    : []
            );

            if (!($result['success'] ?? false)) {
                $errors[] = $result['reply'] ?? ('Could not update order ' . $field . '.');
                continue;
            }

            if (!empty($result['email_followup_sent'])) {
                $emailFollowupSent = true;
            }
        }

        $atlas->syncFounderMutation($user, [
            'role' => 'founder',
            'action' => 'commerce_order_fulfillment_update',
            'field' => 'order_fulfillment',
            'value' => (string) $validated['order_number'],
            'sync_summary' => 'Founder updated Bazaar order fulfillment details from Hatchers OS.',
        ]);

        $success = 'Order fulfillment updated from Hatchers Ai Business OS.';
        if ($emailFollowupSent) {
            $success .= ' Customer email follow-up was sent through Bazaar.';
        }

        $redirect = redirect()->route('founder.commerce.orders')->with('success', $success);
        if (!empty($errors)) {
            $redirect->with('error', implode(' ', $errors));
        }

        return $redirect;
    }

    public function founderUpdateBookingOperation(
        Request $request,
        OsAssistantActionService $actionService,
        AtlasIntelligenceService $atlas,
        OsWalletService $walletService
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'booking_number' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['pending', 'processing', 'completed', 'cancelled'])],
            'payment_status' => ['required', Rule::in(['unpaid', 'paid'])],
            'vendor_note' => ['nullable', 'string', 'max:1000'],
            'staff_id' => ['nullable', 'string', 'max:255'],
        ]);

        $operations = [
            ['field' => 'status', 'value' => (string) $validated['status']],
            ['field' => 'payment_status', 'value' => (string) $validated['payment_status']],
        ];

        if (trim((string) ($validated['vendor_note'] ?? '')) !== '') {
            $operations[] = ['field' => 'vendor_note', 'value' => (string) $validated['vendor_note']];
        }

        if (trim((string) ($validated['staff_id'] ?? '')) !== '') {
            $operations[] = ['field' => 'staff_id', 'value' => (string) $validated['staff_id']];
        }

        $errors = [];
        foreach ($operations as $operation) {
            $result = $actionService->updateCommerceOperationFromOs(
                $user,
                'servio',
                'booking',
                (string) $validated['booking_number'],
                (string) $operation['field'],
                (string) $operation['value']
            );

            if (!($result['success'] ?? false)) {
                $errors[] = $result['reply'] ?? ('Could not update booking ' . $operation['field'] . '.');
            }
        }

        $atlas->syncFounderMutation($user, [
            'role' => 'founder',
            'action' => 'commerce_booking_update',
            'field' => 'booking',
            'value' => (string) $validated['booking_number'],
            'sync_summary' => 'Founder updated a Servio booking from Hatchers OS.',
        ]);

        $redirect = redirect()->route('founder.commerce.bookings')->with('success', 'Booking updated from Hatchers Ai Business OS.');
        if (!empty($errors)) {
            $redirect->with('error', implode(' ', $errors));
        }

        if ((string) $validated['payment_status'] === 'paid') {
            $booking = collect($this->commerceOperationsWorkspace($user, 'servio', ['status' => 'all', 'queue' => 'all'])['recent_bookings'] ?? [])
                ->firstWhere('booking_number', (string) $validated['booking_number']);
            if (is_array($booking)) {
                $walletService->creditCommerceSale(
                    $user,
                    $user->company,
                    'servio',
                    'booking',
                    (string) $validated['booking_number'],
                    (float) ($booking['grand_total'] ?? 0),
                    (string) ($this->commerceOperationsWorkspace($user, 'servio', ['status' => 'all', 'queue' => 'all'])['currency'] ?? 'USD'),
                    ['source' => 'manual_payment_status_update']
                );
            }
        }

        return $redirect;
    }

    public function founderRefundBooking(
        Request $request,
        OsAssistantActionService $actionService,
        OsStripeService $stripeService,
        OsWalletService $walletService,
        AtlasIntelligenceService $atlas
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'booking_number' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $workspace = $this->commerceOperationsWorkspace($user, 'servio', ['status' => 'all', 'queue' => 'all']);
        $booking = collect($workspace['recent_bookings'] ?? [])->firstWhere('booking_number', (string) $validated['booking_number']);
        if (!is_array($booking)) {
            return redirect()->route('founder.commerce.bookings')->with('error', 'That Servio booking is not available in the OS snapshot yet.');
        }

        if ((string) ($booking['payment_status'] ?? 'unpaid') !== 'paid') {
            return redirect()->route('founder.commerce.bookings')->with('error', 'Only paid bookings can be refunded from Hatchers Ai Business OS.');
        }

        $transactionId = trim((string) ($booking['transaction_id'] ?? ''));
        $grossAmount = (float) ($booking['grand_total'] ?? 0);
        $currency = (string) ($workspace['currency'] ?? 'USD');
        $reason = trim((string) ($validated['reason'] ?? ''));

        if ($transactionId !== '' && str_starts_with($transactionId, 'pi_')) {
            $refund = $stripeService->createRefund($transactionId, $grossAmount, $currency, [
                'booking_number' => (string) $validated['booking_number'],
                'founder_id' => $user->id,
            ]);

            if (!($refund['success'] ?? false)) {
                return redirect()->route('founder.commerce.bookings')->with('error', $refund['message'] ?? 'Stripe refund could not be created for this booking.');
            }
        }

        $walletService->refundCommerceSale(
            $user,
            $user->company,
            'servio',
            'booking',
            (string) $validated['booking_number'],
            $grossAmount,
            $currency,
            [
                'source' => 'founder_refund',
                'reason' => $reason,
                'transaction_id' => $transactionId,
            ]
        );

        $note = trim(implode(' | ', array_filter([
            (string) ($booking['vendor_note'] ?? ''),
            'Refunded in Hatchers OS' . ($reason !== '' ? ': ' . $reason : ''),
        ])));

        foreach ([
            ['field' => 'status', 'value' => 'cancelled'],
            ['field' => 'payment_status', 'value' => 'unpaid'],
            ['field' => 'vendor_note', 'value' => $note],
        ] as $operation) {
            $actionService->updateCommerceOperationFromOs(
                $user,
                'servio',
                'booking',
                (string) $validated['booking_number'],
                (string) $operation['field'],
                (string) $operation['value']
            );
        }

        $atlas->syncFounderMutation($user, [
            'role' => 'founder',
            'action' => 'commerce_booking_refund',
            'field' => 'booking',
            'value' => (string) $validated['booking_number'],
            'sync_summary' => 'Founder refunded a Servio booking from Hatchers OS.',
        ]);

        return redirect()->route('founder.commerce.bookings')->with('success', 'Booking refunded from Hatchers Ai Business OS and the founder wallet was reversed.');
    }

    public function founderUpdateBookingCustomer(
        Request $request,
        OsAssistantActionService $actionService,
        AtlasIntelligenceService $atlas
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'booking_number' => ['required', 'string', 'max:255'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'string', 'max:255'],
            'customer_mobile' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
        ]);

        $fields = ['customer_name', 'customer_email', 'customer_mobile', 'address', 'landmark', 'postal_code', 'city', 'state', 'country'];
        $errors = [];
        foreach ($fields as $field) {
            $value = (string) ($validated[$field] ?? '');
            if ($field !== 'customer_name' && $value === '') {
                continue;
            }

            $result = $actionService->updateCommerceOperationFromOs(
                $user,
                'servio',
                'booking',
                (string) $validated['booking_number'],
                $field,
                $value
            );

            if (!($result['success'] ?? false)) {
                $errors[] = $result['reply'] ?? ('Could not update booking ' . $field . '.');
            }
        }

        $atlas->syncFounderMutation($user, [
            'role' => 'founder',
            'action' => 'commerce_booking_customer_update',
            'field' => 'booking_customer',
            'value' => (string) $validated['booking_number'],
            'sync_summary' => 'Founder updated Servio booking customer details from Hatchers OS.',
        ]);

        $redirect = redirect()->route('founder.commerce.bookings')->with('success', 'Booking customer details updated from Hatchers Ai Business OS.');
        if (!empty($errors)) {
            $redirect->with('error', implode(' ', $errors));
        }

        return $redirect;
    }

    public function founderUpdateBookingSchedule(
        Request $request,
        OsAssistantActionService $actionService,
        AtlasIntelligenceService $atlas
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'booking_number' => ['required', 'string', 'max:255'],
            'booking_date' => ['nullable', 'date'],
            'booking_time' => ['nullable', 'string', 'max:255'],
            'booking_endtime' => ['nullable', 'string', 'max:255'],
            'booking_notes' => ['nullable', 'string', 'max:2000'],
            'customer_message' => ['nullable', 'string', 'max:2000'],
            'message_channel' => ['nullable', Rule::in(['manual', 'email', 'whatsapp', 'sms'])],
            'message_template' => ['nullable', Rule::in(array_keys($this->bookingMessageTemplates()))],
        ]);

        $customerMessage = $this->resolveCommerceMessage(
            'booking',
            (string) ($validated['message_template'] ?? ''),
            (string) ($validated['customer_message'] ?? ''),
            $validated
        );

        $errors = [];
        $emailFollowupSent = false;
        foreach (['booking_date', 'booking_time', 'booking_endtime', 'booking_notes', 'customer_message'] as $field) {
            $value = $field === 'customer_message'
                ? $customerMessage
                : trim((string) ($validated[$field] ?? ''));
            if ($value === '') {
                continue;
            }

            $result = $actionService->updateCommerceOperationFromOs(
                $user,
                'servio',
                'booking',
                (string) $validated['booking_number'],
                $field,
                $value,
                $field === 'customer_message'
                    ? ['message_channel' => (string) ($validated['message_channel'] ?? 'manual')]
                    : []
            );

            if (!($result['success'] ?? false)) {
                $errors[] = $result['reply'] ?? ('Could not update booking ' . $field . '.');
                continue;
            }

            if (!empty($result['email_followup_sent'])) {
                $emailFollowupSent = true;
            }
        }

        $atlas->syncFounderMutation($user, [
            'role' => 'founder',
            'action' => 'commerce_booking_schedule_update',
            'field' => 'booking_schedule',
            'value' => (string) $validated['booking_number'],
            'sync_summary' => 'Founder updated Servio booking schedule details from Hatchers OS.',
        ]);

        $success = 'Booking schedule updated from Hatchers Ai Business OS.';
        if ($emailFollowupSent) {
            $success .= ' Customer email follow-up was sent through Servio.';
        }

        $redirect = redirect()->route('founder.commerce.bookings')->with('success', $success);
        if (!empty($errors)) {
            $redirect->with('error', implode(' ', $errors));
        }

        return $redirect;
    }

    public function founderToggleCommerceConfig(
        Request $request,
        OsAssistantActionService $actionService,
        AtlasIntelligenceService $atlas
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'platform' => ['required', Rule::in(['bazaar', 'servio'])],
            'config_type' => ['required', Rule::in(['coupon', 'shipping'])],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $result = $actionService->updateCommerceOperationFromOs(
            $user,
            (string) $validated['platform'],
            (string) $validated['config_type'],
            (string) $validated['title'],
            'status',
            (string) $validated['status']
        );

        FounderActionPlan::query()
            ->where('founder_id', $user->id)
            ->where('platform', (string) $validated['platform'])
            ->where('title', (string) $validated['title'])
            ->where('description', 'like', 'Config:%')
            ->update([
                'status' => (string) $validated['status'] === 'active' ? 'configured' : 'paused',
            ]);

        $atlas->syncFounderMutation($user, [
            'role' => 'founder',
            'action' => 'commerce_config_status_update',
            'field' => (string) $validated['config_type'],
            'value' => (string) $validated['title'] . ':' . (string) $validated['status'],
            'sync_summary' => 'Founder updated a commerce config status from Hatchers OS.',
        ]);

        $redirect = redirect()->route('founder.commerce')->with('success', ucfirst((string) $validated['config_type']) . ' status updated from Hatchers Ai Business OS.');
        if (!($result['success'] ?? false)) {
            $redirect->with('error', $result['reply'] ?? 'The OS updated locally, but the engine status change still needs attention.');
        }

        return $redirect;
    }

    public function connectWebsiteDomain(
        Request $request,
        WebsiteProvisioningService $websiteProvisioningService
    ): RedirectResponse {
        /** @var \App\Models\Founder $founder */
        $founder = Auth::user();
        $company = $founder->company;

        $validated = $request->validate([
            'website_engine' => ['required', Rule::in(['bazaar', 'servio'])],
            'custom_domain' => ['required', 'string', 'max:255'],
        ]);
        $validated['website_engine'] = $this->resolveWebsiteEngineForBusinessModel(
            $validated['website_engine'],
            (string) ($founder->company?->business_model ?? '')
        );

        $result = $websiteProvisioningService->connectCustomDomain($founder, $validated);
        if (!$result['ok']) {
            return back()->with('error', $result['error'] ?? 'Hatchers OS could not save the custom domain request.');
        }

        if (!empty($company)) {
            $company->website_engine = $validated['website_engine'];
            $company->custom_domain = (string) ($result['domain'] ?? $validated['custom_domain']);
            $company->custom_domain_status = 'pending_dns';
            $company->website_url = $this->buildCompanyWebsiteUrl($company, $validated['website_engine']);
            $company->save();
        }

        return redirect()->route('website')->with(
            'success',
            'Custom domain saved in Hatchers OS. Point it to ' . ($result['dns_target'] ?? 'the platform host') . ' to complete connection.'
        );
    }

    public function storeOnboarding(
        Request $request,
        AtlasIntelligenceService $atlas,
        FounderModuleSyncService $founderModuleSyncService
    ): RedirectResponse
    {
        $validated = $request->validate([
            'plan_code' => ['required', Rule::in(array_keys($this->founderSignupPlans()))],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:founders,email'],
            'username' => ['required', 'string', 'max:255', 'unique:founders,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'company_name' => ['required', 'string', 'max:255'],
            'vertical_blueprint' => ['required', Rule::in(array_keys($this->verticalBlueprintDefinitions()))],
            'business_model' => ['required', 'in:product,service,hybrid'],
            'primary_city' => ['required', 'string', 'max:191'],
            'service_radius' => ['required', 'string', 'max:191'],
            'industry' => ['required', Rule::in($this->founderIndustryOptions())],
            'stage' => ['required', 'in:idea,launching,operating,scaling'],
            'target_audience' => ['required', 'string', 'max:255'],
            'primary_icp_name' => ['required', 'string', 'max:255'],
            'ideal_customer_profile' => ['required', 'string', 'max:1000'],
            'pain_points' => ['required', 'string', 'max:1200'],
            'desired_outcomes' => ['required', 'string', 'max:1200'],
            'objections' => ['required', 'string', 'max:1200'],
            'brand_voice' => ['required', 'string', 'max:255'],
            'differentiators' => ['required', 'string', 'max:1000'],
            'problem_solved' => ['required', 'string', 'max:1000'],
            'core_offer' => ['required', Rule::in($this->founderCoreOfferOptions())],
            'primary_growth_goal' => ['required', 'string', 'max:255'],
            'known_blockers' => ['required', 'string', 'max:255'],
            'company_brief' => ['required', 'string', 'max:2000'],
        ], [
            'plan_code.required' => 'Please choose a founder plan before signing up.',
            'industry.required' => 'Please choose your industry.',
            'industry.in' => 'Please choose an industry from the list.',
            'core_offer.required' => 'Please choose the offer type that fits your business.',
            'core_offer.in' => 'Please choose an offer type from the list.',
        ]);

        $plan = $this->founderSignupPlans()[$validated['plan_code']] ?? null;
        if ($plan === null) {
            return redirect()->route('plans')->with('error', 'Please choose a valid founder plan.');
        }

        $blueprint = $this->upsertVerticalBlueprint((string) $validated['vertical_blueprint']);

        try {
            DB::transaction(function () use ($validated, $atlas, $plan, $blueprint) {
                $founder = Founder::create(
                    [
                        'username' => $validated['username'],
                        'email' => $validated['email'],
                        'full_name' => $validated['full_name'],
                        'password' => Hash::make($validated['password']),
                        'status' => 'active',
                        'role' => 'founder',
                        'auth_source' => 'os',
                        'timezone' => 'Africa/Cairo',
                        'email_verified_at' => null,
                        'mentor_entitled_until' => !empty($plan['mentor_months']) ? now()->addMonths((int) $plan['mentor_months']) : null,
                    ]
                );

                $company = Company::updateOrCreate(
                    ['founder_id' => $founder->id],
                    [
                        'company_name' => $validated['company_name'],
                        'business_model' => $validated['business_model'],
                        'vertical_blueprint_id' => $blueprint->id,
                        'industry' => $validated['industry'],
                        'stage' => $validated['stage'],
                        'primary_city' => $validated['primary_city'],
                        'service_radius' => $validated['service_radius'],
                        'primary_goal' => $validated['primary_growth_goal'],
                        'launch_stage' => 'brief_captured',
                        'website_generation_status' => 'queued',
                        'website_status' => 'not_started',
                        'company_brief' => $validated['company_brief'],
                    ]
                );

                FounderBusinessBrief::updateOrCreate(
                    ['founder_id' => $founder->id, 'company_id' => $company->id],
                    [
                        'vertical_blueprint_id' => $blueprint->id,
                        'business_name' => $validated['company_name'],
                        'business_summary' => $validated['company_brief'],
                        'problem_solved' => $validated['problem_solved'],
                        'core_offer' => $validated['core_offer'],
                        'business_type_detail' => $blueprint->name,
                        'location_city' => $validated['primary_city'],
                        'location_country' => 'Egypt',
                        'service_radius' => $validated['service_radius'],
                        'delivery_scope' => $validated['service_radius'],
                        'proof_points' => $validated['differentiators'],
                        'founder_story' => $validated['company_brief'],
                        'constraints_json' => [
                            'known_blockers' => $validated['known_blockers'],
                        ],
                        'status' => 'captured',
                    ]
                );

                FounderIcpProfile::updateOrCreate(
                    ['founder_id' => $founder->id, 'company_id' => $company->id],
                    [
                        'primary_icp_name' => $validated['primary_icp_name'],
                        'pain_points_json' => $this->commaSeparatedValues($validated['pain_points']),
                        'desired_outcomes_json' => $this->commaSeparatedValues($validated['desired_outcomes']),
                        'objections_json' => $this->commaSeparatedValues($validated['objections']),
                        'price_sensitivity' => 'unknown',
                        'primary_channels_json' => $blueprint->default_channels_json ?? [],
                        'local_area_focus_json' => [$validated['primary_city']],
                        'language_style' => $validated['brand_voice'],
                    ]
                );

                CompanyIntelligence::updateOrCreate(
                    ['company_id' => $company->id],
                    [
                        'target_audience' => $validated['target_audience'],
                        'ideal_customer_profile' => $validated['ideal_customer_profile'],
                        'primary_icp_name' => $validated['primary_icp_name'],
                        'problem_solved' => $validated['problem_solved'],
                        'brand_voice' => $validated['brand_voice'],
                        'differentiators' => $validated['differentiators'],
                        'core_offer' => $validated['core_offer'],
                        'primary_growth_goal' => $validated['primary_growth_goal'],
                        'known_blockers' => $validated['known_blockers'],
                        'objections' => $validated['objections'],
                        'buying_triggers' => $validated['desired_outcomes'],
                        'local_market_notes' => $validated['primary_city'] . ' · ' . $validated['service_radius'],
                        'intelligence_updated_at' => now(),
                    ]
                );

                Subscription::updateOrCreate(
                    ['founder_id' => $founder->id],
                    [
                        'plan_code' => $plan['code'],
                        'plan_name' => $plan['name'],
                        'billing_status' => $plan['billing_status'],
                        'amount' => $plan['amount'],
                        'currency' => 'USD',
                        'started_at' => now(),
                        'mentor_phase_started_at' => !empty($plan['mentor_months']) ? now() : null,
                        'mentor_phase_ends_at' => !empty($plan['mentor_months']) ? now()->addMonths((int) $plan['mentor_months']) : null,
                        'transitions_to_plan_code' => $plan['transitions_to_plan_code'] ?? null,
                        'transitions_on' => !empty($plan['transitions_days']) ? now()->addDays((int) $plan['transitions_days']) : null,
                        'next_billing_at' => !empty($plan['trial_days']) ? now()->addDays((int) $plan['trial_days']) : now()->addMonth(),
                    ]
                );

                CommercialSummary::updateOrCreate(
                    ['founder_id' => $founder->id],
                    [
                        'business_model' => $validated['business_model'],
                        'summary_updated_at' => now(),
                    ]
                );

                FounderWeeklyState::updateOrCreate(
                    ['founder_id' => $founder->id],
                    [
                        'weekly_focus' => 'Complete onboarding and begin the first business build sprint.',
                        'state_updated_at' => now(),
                    ]
                );

                $actions = [
                    [
                        'title' => 'Complete company intelligence',
                        'description' => 'Refine your audience, offer, and positioning so Hatchers can build the right direct-response business system for you.',
                        'platform' => 'atlas',
                        'priority' => 95,
                        'cta_label' => 'Open Atlas',
                        'cta_url' => '/dashboard',
                    ],
                    [
                        'title' => 'Choose your website path',
                        'description' => 'Review the pre-selected launch engine and get your first site ready for publishing.',
                        'platform' => (string) $blueprint->engine,
                        'priority' => 88,
                        'cta_label' => 'Open Website Workspace',
                        'cta_url' => '/website',
                    ],
                    [
                        'title' => 'Review your first revenue sprint',
                        'description' => 'Start with the first tasks Hatchers recommends for getting your first customers fast.',
                        'platform' => 'os',
                        'priority' => 90,
                        'cta_label' => 'Open Tasks',
                        'cta_url' => '/tasks',
                    ],
                ];

                foreach ($actions as $action) {
                    FounderActionPlan::firstOrCreate(
                        [
                            'founder_id' => $founder->id,
                            'title' => $action['title'],
                        ],
                        $action
                    );
                }

                $atlas->syncFounderOnboarding($founder, $company, $validated);
            });
        } catch (Throwable $exception) {
            Log::error('Founder signup failed unexpectedly.', [
                'email' => $validated['email'] ?? '',
                'username' => $validated['username'] ?? '',
                'message' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['signup' => 'Hatchers AI could not complete signup right now. Please review the form and try again.']);
        }

        $founder = Founder::query()->with('company')->where('email', $validated['email'])->first();
        if ($founder instanceof Founder) {
            $syncResult = $founderModuleSyncService->syncFounder($founder, 'all');
            if (empty($syncResult['ok'])) {
                Log::warning('Founder signup completed but module sync had issues.', [
                    'founder_id' => $founder->id,
                    'email' => $founder->email,
                    'message' => $syncResult['message'] ?? 'Founder module sync failed.',
                    'results' => $syncResult['results'] ?? [],
                ]);
            }
        }

        if ($founder && $this->authVerificationDisabled()) {
            $founder->forceFill([
                'email_verified_at' => $founder->email_verified_at ?: now(),
                'email_verification_token' => null,
                'email_verification_expires_at' => null,
                'login_verification_token' => null,
                'login_verification_expires_at' => null,
            ])->save();

            return redirect()->route('login')->with(
                'success',
                'Your founder workspace has been created under the ' . $plan['name'] . ' plan. Verification is disabled for test mode, so you can log in immediately.'
            );
        }

        if ($founder) {
            $this->issueEmailVerification($founder);
            $request->session()->put('verification_email', $founder->email);
        }

        return redirect()->route('verification.email.notice', ['email' => $validated['email']])->with(
            'success',
            'Your founder workspace has been created under the ' . $plan['name'] . ' plan. We sent a verification code to your email before you can log in.'
        );
    }

    public function authenticate(
        Request $request,
        IdentitySyncService $identitySyncService,
        LmsIdentityBridgeService $lmsIdentityBridgeService
    ): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $founder = Founder::where('email', $credentials['login'])
            ->orWhere('username', $credentials['login'])
            ->first();

        try {
            if (!$founder || !Hash::check($credentials['password'], $founder->password)) {
                $bridgeResult = $lmsIdentityBridgeService->authenticate($credentials['login'], $credentials['password']);
                if (empty($bridgeResult['ok'])) {
                    return back()
                        ->withErrors(['login' => 'The provided credentials do not match our records.'])
                        ->onlyInput('login');
                }

                $profile = is_array($bridgeResult['profile'] ?? null) ? $bridgeResult['profile'] : [];
                $role = trim((string) ($profile['role'] ?? 'founder'));
                $founder = $identitySyncService->upsert($role, $profile, $credentials['password']);
            }

            if (!in_array((string) $founder->status, ['active', 'trialing'], true)) {
                return back()
                    ->withErrors(['login' => 'This account is currently ' . $founder->status . '.'])
                    ->onlyInput('login');
            }

            if ($founder->isFounder() && !$this->authVerificationDisabled() && !$founder->hasVerifiedEmail()) {
                $this->issueEmailVerification($founder);
                $request->session()->put('verification_email', $founder->email);

                return redirect()
                    ->route('verification.email.notice', ['email' => $founder->email])
                    ->with('success', 'Please verify your founder email before logging in. We sent you a fresh verification code.');
            }
        } catch (Throwable $exception) {
            Log::error('OS login failed unexpectedly.', [
                'login' => $credentials['login'],
                'message' => $exception->getMessage(),
            ]);

            return back()
                ->withErrors(['login' => 'Hatchers OS could not complete login right now. Please try again in a moment.'])
                ->onlyInput('login');
        }

        if ($founder->isFounder() && $this->authVerificationDisabled()) {
            $founder->forceFill([
                'email_verified_at' => $founder->email_verified_at ?: now(),
                'email_verification_token' => null,
                'email_verification_expires_at' => null,
                'login_verification_token' => null,
                'login_verification_expires_at' => null,
            ])->save();

            Auth::login($founder, true);
            $request->session()->forget('pending_login_founder_id');
            $request->session()->regenerate();

            return redirect()->route('dashboard');
        }

        if ($founder->isFounder()) {
            $this->issueLoginVerification($founder, $request);

            return redirect()->route('verification.login.notice')->with(
                'success',
                'We sent a sign-in verification code to ' . $founder->email . '. Enter it to continue.'
            );
        }

        Auth::login($founder, true);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function assistantChat(
        Request $request,
        AtlasIntelligenceService $atlas,
        OsAssistantActionService $actionService,
        OsAssistantTimelineService $timeline
    ): JsonResponse
    {
        /** @var \App\Models\Founder $founder */
        $founder = Auth::user();

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'current_page' => ['nullable', 'string', 'max:255'],
            'thread_key' => ['nullable', 'string', 'max:120'],
        ]);

        $message = trim((string) $validated['message']);
        $currentPage = trim((string) ($validated['current_page'] ?? 'os_dashboard'));
        $threadKey = trim((string) ($validated['thread_key'] ?? ''));

        $actionResult = $actionService->handle($founder, $request, $message);
        if (!empty($actionResult['handled'])) {
            if (!empty($actionResult['executed'])) {
                $founder->refresh();
                $founder->load([
                    'company.intelligence',
                    'subscription',
                    'weeklyState',
                    'commercialSummary',
                    'moduleSnapshots',
                ]);

                $atlas->syncFounderMutation($founder, [
                    'role' => $actionResult['actor_role'] ?? '',
                    'action' => $actionResult['action_type'] ?? 'os_write_action',
                    'field' => $actionResult['field'] ?? '',
                    'value' => $actionResult['value'] ?? '',
                    'sync_summary' => $actionResult['sync_summary'] ?? 'Atlas completed a confirmed Hatchers OS write action.',
                ]);
            }

            $mappedActions = $this->mapAssistantActionsToOs($actionResult['actions'] ?? []);
            $thread = $timeline->record(
                $founder,
                $threadKey !== '' ? $threadKey : null,
                $currentPage,
                $message,
                (string) ($actionResult['reply'] ?? ''),
                $mappedActions
            );

            return response()->json([
                'success' => true,
                'reply' => $actionResult['reply'] ?? '',
                'actions' => $mappedActions,
                'refresh' => !empty($actionResult['executed']),
                'thread_key' => (string) $thread->thread_key,
            ]);
        }

        $result = $atlas->chatFromOs(
            $founder,
            $message,
            $currentPage
        );

        if (!$result['ok']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Atlas could not respond right now.',
            ], 502);
        }

        $mappedActions = $this->mapAssistantActionsToOs($result['actions'] ?? []);
        $thread = $timeline->record(
            $founder,
            $threadKey !== '' ? $threadKey : null,
            $currentPage,
            $message,
            (string) ($result['reply'] ?? ''),
            $mappedActions
        );

        return response()->json([
            'success' => true,
            'reply' => $result['reply'] ?? '',
            'actions' => $mappedActions,
            'refresh' => false,
            'thread_key' => (string) $thread->thread_key,
        ]);
    }

    public function assistantReset(OsAssistantTimelineService $timeline): JsonResponse
    {
        /** @var \App\Models\Founder $founder */
        $founder = Auth::user();

        $thread = $timeline->startNewThread($founder);

        return response()->json([
            'success' => true,
            'label' => 'New founder chat',
            'thread_key' => (string) $thread->thread_key,
            'reply' => 'New chat started. Ask Atlas anything about your next move, your offer, your website, your marketing, or what is blocking growth.',
        ]);
    }

    public function assistantThread(Request $request, OsAssistantTimelineService $timeline): JsonResponse
    {
        /** @var \App\Models\Founder $founder */
        $founder = Auth::user();

        $validated = $request->validate([
            'thread_key' => ['required', 'string', 'max:120'],
        ]);

        $threadKey = trim((string) $validated['thread_key']);
        $messages = $timeline->timeline($founder, $threadKey, 20);
        $summary = $timeline->summary($founder, $threadKey);

        return response()->json([
            'success' => true,
            'thread_key' => $summary['thread_key'] ?? $threadKey,
            'label' => $summary['label'] ?? 'Founder chat',
            'messages' => $messages,
            'pinned_plan' => $summary['pinned_plan'] ?? [],
        ]);
    }

    public function assistantCreateTasks(Request $request): JsonResponse
    {
        /** @var \App\Models\Founder $founder */
        $founder = Auth::user();

        $validated = $request->validate([
            'thread_key' => ['nullable', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:120'],
            'steps' => ['required', 'array', 'min:1', 'max:6'],
            'steps.*' => ['required', 'string', 'max:255'],
        ]);

        $title = trim((string) ($validated['title'] ?? "Atlas plan"));
        $steps = collect($validated['steps'])
            ->map(fn ($step) => trim((string) $step))
            ->filter()
            ->take(6)
            ->values();

        if ($steps->isEmpty()) {
            return response()->json([
                'success' => false,
                'error' => 'There are no usable plan steps to save.',
            ], 422);
        }

        $created = $steps->map(function (string $step, int $index) use ($founder, $title) {
            return FounderActionPlan::create([
                'founder_id' => $founder->id,
                'title' => $step,
                'description' => 'Created from Atlas assistant plan: ' . $title,
                'platform' => 'atlas_assistant',
                'priority' => max(40, 82 - ($index * 6)),
                'status' => 'pending',
                'cta_label' => 'Open Tasks',
                'cta_url' => route('founder.tasks'),
            ]);
        });

        return response()->json([
            'success' => true,
            'created_count' => $created->count(),
            'reply' => 'Saved this Atlas plan into your OS tasks.',
            'actions' => [[
                'cta' => 'Open Tasks',
                'os_workspace_key' => 'tasks',
                'os_href' => route('founder.tasks'),
            ]],
        ]);
    }

    private function mapAssistantActionsToOs(array $actions): array
    {
        return collect($actions)
            ->filter(fn ($action) => is_array($action))
            ->map(function (array $action): array {
                [$workspaceKey, $href] = $this->resolveAssistantWorkspaceTarget($action);

                if ($workspaceKey !== null) {
                    $action['os_workspace_key'] = $workspaceKey;
                }

                if ($href !== null) {
                    $action['os_href'] = $href;
                }

                return $action;
            })
            ->values()
            ->all();
    }

    private function resolveAssistantWorkspaceTarget(array $action): array
    {
        $title = strtolower(trim((string) ($action['title'] ?? '')));
        $reason = strtolower(trim((string) ($action['reason'] ?? '')));
        $cta = strtolower(trim((string) ($action['cta'] ?? '')));
        $platform = strtolower(trim((string) ($action['platform'] ?? '')));
        $haystack = trim($title . ' ' . $reason . ' ' . $cta . ' ' . $platform);

        $targets = [
            [
                'keywords' => ['company intelligence', 'positioning', 'audience', 'icp', 'offer'],
                'key' => 'settings',
                'href' => route('founder.settings'),
            ],
            [
                'keywords' => ['campaign', 'marketing assets', 'content', 'social'],
                'key' => 'marketing',
                'href' => route('founder.marketing'),
            ],
            [
                'keywords' => ['learning', 'lesson', 'mentor', 'execution sprint', 'milestone', 'lms'],
                'key' => 'learning-plan',
                'href' => route('founder.learning-plan'),
            ],
            [
                'keywords' => ['task', 'weekly progress', 'weekly traction'],
                'key' => 'tasks',
                'href' => route('founder.tasks'),
            ],
            [
                'keywords' => ['product', 'store', 'bazaar', 'order'],
                'key' => 'commerce',
                'href' => route('founder.commerce'),
            ],
            [
                'keywords' => ['service', 'booking', 'servio'],
                'key' => 'website',
                'href' => route('website'),
            ],
            [
                'keywords' => ['builder path', 'website', 'launch-ready', 'theme'],
                'key' => 'website',
                'href' => route('website'),
            ],
            [
                'keywords' => ['atlas', 'ai', 'assistant', 'campaign angle'],
                'key' => 'ai-tools',
                'href' => route('founder.ai-tools'),
            ],
        ];

        foreach ($targets as $target) {
            foreach ($target['keywords'] as $keyword) {
                if (str_contains($haystack, $keyword)) {
                    return [$target['key'], $target['href']];
                }
            }
        }

        return [null, null];
    }

    private function applyFounderExecutionStatus(
        Founder $founder,
        FounderActionPlan $actionPlan,
        string $status,
        string $context,
        OsAssistantActionService $actionService,
        AtlasIntelligenceService $atlas,
        string $actorRole = 'founder'
    ): RedirectResponse {
        $normalizedStatus = $status === 'completed' ? 'completed' : 'pending';
        $lmsStatus = $normalizedStatus === 'completed' ? 'completed' : 'open';
        $isLesson = $context === 'lesson';

        $bridgeResult = $isLesson
            ? $actionService->updateMilestoneStatusFromOs($founder, $actionPlan->title, $lmsStatus, $actorRole)
            : $actionService->updateTaskStatusFromOs($founder, $actionPlan->title, $lmsStatus, $actorRole);

        $actionPlan->forceFill([
            'status' => $normalizedStatus,
            'completed_at' => $normalizedStatus === 'completed' ? now() : null,
        ])->save();

        $this->refreshFounderWeeklyExecutionState($founder);

        $atlas->syncFounderMutation($founder, [
            'role' => $actorRole,
            'action' => 'execution_status_update',
            'field' => $isLesson ? 'milestone_status' : 'task_status',
            'value' => $actionPlan->title . ':' . $normalizedStatus,
            'sync_summary' => $normalizedStatus === 'completed'
                ? ucfirst($actorRole) . ' completed execution work from Hatchers OS.'
                : ucfirst($actorRole) . ' reopened execution work from Hatchers OS.',
        ]);

        $route = $isLesson ? 'founder.learning-plan' : 'founder.tasks';
        $success = $normalizedStatus === 'completed'
            ? ($isLesson ? 'Lesson completed from Hatchers Ai Business OS.' : 'Task completed from Hatchers Ai Business OS.')
            : ($isLesson ? 'Lesson reopened inside Hatchers Ai Business OS.' : 'Task reopened inside Hatchers Ai Business OS.');

        if (!($bridgeResult['success'] ?? false)) {
            return redirect()
                ->route($route)
                ->with('success', $success)
                ->with('error', ($bridgeResult['reply'] ?? 'LMS sync is still pending.') . ' The OS state was updated and will keep moving forward.');
        }

        return redirect()
            ->route($route)
            ->with('success', $success);
    }

    private function ensureAdminPermission(Founder $user, string $permission): void
    {
        if (!$user->isAdmin()) {
            abort(403);
        }

        if (!$user->hasPermission($permission)) {
            abort(403, 'This admin account does not currently have permission to access that OS workspace.');
        }
    }

    private function refreshFounderWeeklyExecutionState(Founder $founder): void
    {
        $totals = $founder->actionPlans()
            ->selectRaw(
                "COUNT(*) as total_count, SUM(CASE WHEN status IN ('completed', 'complete', 'done') OR completed_at IS NOT NULL THEN 1 ELSE 0 END) as completed_count"
            )
            ->first();

        $totalCount = (int) ($totals?->total_count ?? 0);
        $completedCount = (int) ($totals?->completed_count ?? 0);
        $openCount = max(0, $totalCount - $completedCount);
        $progress = $totalCount > 0 ? (int) round(($completedCount / $totalCount) * 100) : 0;

        FounderWeeklyState::updateOrCreate(
            ['founder_id' => $founder->id],
            [
                'open_tasks' => $openCount,
                'completed_tasks' => $completedCount,
                'open_milestones' => $openCount,
                'completed_milestones' => $completedCount,
                'weekly_progress_percent' => max(0, min(100, $progress)),
                'state_updated_at' => now(),
            ]
        );
    }

    private function commerceOffers(Founder $founder): array
    {
        return $founder->actionPlans()
            ->whereIn('platform', ['bazaar', 'servio'])
            ->where('description', 'not like', 'Config:%')
            ->latest()
            ->limit(12)
            ->get()
            ->map(function (FounderActionPlan $actionPlan): array {
                $offer = $this->parseCommerceOffer($actionPlan);

                return [
                    'id' => $actionPlan->id,
                    'title' => $actionPlan->title,
                    'engine' => $offer['engine'],
                    'type' => $offer['type'],
                    'description' => $offer['description'],
                    'price' => $offer['price'],
                    'category_name' => $offer['category_name'],
                    'tax_rules' => $offer['tax_rules'],
                    'tax_rules_text' => $this->formatTaxRulesText($offer['tax_rules']),
                    'sku' => $offer['sku'],
                    'stock' => $offer['stock'],
                    'low_stock' => $offer['low_stock'],
                    'duration' => $offer['duration'],
                    'duration_unit' => $offer['duration_unit'],
                    'capacity' => $offer['capacity'],
                    'staff_mode' => $offer['staff_mode'],
                    'staff_id' => $offer['staff_id'],
                    'staff_ids' => $offer['staff_ids'],
                    'staff_ids_text' => $this->formatStaffIdsText($offer['staff_ids']),
                    'availability_days' => $offer['availability_days'],
                    'open_time' => $offer['open_time'],
                    'close_time' => $offer['close_time'],
                    'additional_services' => $offer['additional_services'],
                    'additional_services_text' => $this->formatAdditionalServicesText($offer['additional_services']),
                    'variants' => $offer['variants'],
                    'variants_text' => $this->formatVariantsText($offer['variants']),
                    'extras' => $offer['extras'],
                    'extras_text' => $this->formatExtrasText($offer['extras']),
                    'payment_collection' => $offer['payment_collection'],
                    'status' => $actionPlan->status === 'paused' ? 'inactive' : 'active',
                    'updated_at' => optional($actionPlan->updated_at)?->diffForHumans(),
                ];
            })
            ->all();
    }

    private function commerceConfigs(Founder $founder): array
    {
        $configs = $founder->actionPlans()
            ->whereIn('platform', ['bazaar', 'servio'])
            ->where('description', 'like', 'Config:%')
            ->latest()
            ->get()
            ->map(function (FounderActionPlan $actionPlan): array {
                $config = $this->parseCommerceConfig($actionPlan);

                return [
                    'id' => $actionPlan->id,
                    'title' => $actionPlan->title,
                    'type' => $config['type'],
                    'engine' => $config['engine'],
                    'status' => $actionPlan->status,
                    'field_one' => $config['field_one'],
                    'field_two' => $config['field_two'],
                    'field_three' => $config['field_three'],
                    'field_four' => $config['field_four'],
                    'updated_at' => optional($actionPlan->updated_at)?->diffForHumans(),
                ];
            })
            ->groupBy('type');

        return [
            'coupon' => $configs->get('coupon', collect())->all(),
            'shipping' => $configs->get('shipping', collect())->all(),
            'booking_policy' => $configs->get('booking_policy', collect())->all(),
        ];
    }

    private function commerceCatalogs(Founder $founder): array
    {
        $bazaar = $founder->moduleSnapshots()->where('module', 'bazaar')->latest('snapshot_updated_at')->first()?->payload_json ?? [];
        $servio = $founder->moduleSnapshots()->where('module', 'servio')->latest('snapshot_updated_at')->first()?->payload_json ?? [];

        return [
            'bazaar' => [
                'categories' => collect($bazaar['recent_categories'] ?? [])->filter(fn ($item) => is_array($item))->values()->all(),
                'taxes' => collect($bazaar['recent_taxes'] ?? [])->filter(fn ($item) => is_array($item))->values()->all(),
                'products' => collect($bazaar['recent_products'] ?? [])->filter(fn ($item) => is_array($item))->values()->all(),
            ],
            'servio' => [
                'categories' => collect($servio['recent_categories'] ?? [])->filter(fn ($item) => is_array($item))->values()->all(),
                'taxes' => collect($servio['recent_taxes'] ?? [])->filter(fn ($item) => is_array($item))->values()->all(),
                'additional_services' => collect($servio['recent_additional_services'] ?? [])->filter(fn ($item) => is_array($item))->values()->all(),
                'services' => collect($servio['recent_services'] ?? [])->filter(fn ($item) => is_array($item))->values()->all(),
                'staff' => collect($servio['recent_staff'] ?? [])->filter(fn ($item) => is_array($item))->values()->all(),
            ],
        ];
    }

    private function commerceOperationsWorkspace(Founder $founder, string $module, array $filters = []): array
    {
        $snapshot = $founder->moduleSnapshots()->where('module', $module)->latest('snapshot_updated_at')->first();
        $payload = $snapshot?->payload_json ?? [];
        $summary = $payload['summary'] ?? [];
        $counts = $payload['key_counts'] ?? [];
        $recentOrders = collect($payload['recent_orders'] ?? [])->filter(fn ($item) => is_array($item))->values()->all();
        $recentBookings = collect($payload['recent_bookings'] ?? [])->filter(fn ($item) => is_array($item))->values()->all();
        $recentCoupons = collect($payload['recent_coupons'] ?? [])->filter(fn ($item) => is_array($item))->values()->all();
        $shippingZones = collect($payload['shipping_zones'] ?? [])->filter(fn ($item) => is_array($item))->values()->all();
        $activity = collect($payload['recent_activity'] ?? [])
            ->filter(fn ($item) => is_string($item) && trim($item) !== '')
            ->values()
            ->all();
        $statusFilter = strtolower(trim((string) ($filters['status'] ?? 'all')));
        $queueFilter = strtolower(trim((string) ($filters['queue'] ?? 'all')));
        $search = strtolower(trim((string) ($filters['q'] ?? '')));

        if ($module === 'bazaar') {
            $recentOrders = collect($recentOrders)
                ->filter(function (array $order) use ($statusFilter, $queueFilter, $search): bool {
                    if ($statusFilter !== '' && $statusFilter !== 'all' && strtolower((string) ($order['status'] ?? '')) !== $statusFilter) {
                        return false;
                    }

                    if ($queueFilter !== '' && $queueFilter !== 'all') {
                        $matchesQueue = match ($queueFilter) {
                            'pending' => in_array((string) ($order['status'] ?? 'pending'), ['pending', 'processing'], true),
                            'unpaid' => (string) ($order['payment_status'] ?? 'unpaid') !== 'paid',
                            'ready_to_ship' => (string) ($order['status'] ?? '') === 'processing'
                                && (string) ($order['payment_status'] ?? '') === 'paid',
                            default => true,
                        };

                        if (!$matchesQueue) {
                            return false;
                        }
                    }

                    if ($search === '') {
                        return true;
                    }

                    $haystack = strtolower(implode(' ', array_filter([
                        (string) ($order['order_number'] ?? ''),
                        (string) ($order['customer_name'] ?? ''),
                        (string) ($order['customer_email'] ?? ''),
                        (string) ($order['customer_mobile'] ?? ''),
                    ])));

                    return str_contains($haystack, $search);
                })
                ->map(function (array $order): array {
                    $order['communication_timeline'] = $this->extractCommunicationTimeline((string) ($order['order_notes'] ?? ''));
                    return $order;
                })
                ->values()
                ->all();
        }

        if ($module === 'servio') {
            $recentBookings = collect($recentBookings)
                ->filter(function (array $booking) use ($statusFilter, $queueFilter, $search): bool {
                    if ($statusFilter !== '' && $statusFilter !== 'all' && strtolower((string) ($booking['status'] ?? '')) !== $statusFilter) {
                        return false;
                    }

                    if ($queueFilter !== '' && $queueFilter !== 'all') {
                        $bookingHasSchedule = trim((string) ($booking['booking_date'] ?? '')) !== ''
                            && trim((string) ($booking['booking_time'] ?? '')) !== '';
                        $matchesQueue = match ($queueFilter) {
                            'pending' => in_array((string) ($booking['status'] ?? 'pending'), ['pending', 'processing'], true),
                            'unscheduled' => !$bookingHasSchedule,
                            'needs_staff' => in_array((string) ($booking['status'] ?? 'pending'), ['pending', 'processing'], true)
                                && trim((string) ($booking['staff_id'] ?? '')) === '',
                            default => true,
                        };

                        if (!$matchesQueue) {
                            return false;
                        }
                    }

                    if ($search === '') {
                        return true;
                    }

                    $haystack = strtolower(implode(' ', array_filter([
                        (string) ($booking['booking_number'] ?? ''),
                        (string) ($booking['customer_name'] ?? ''),
                        (string) ($booking['customer_email'] ?? ''),
                        (string) ($booking['service_name'] ?? ''),
                    ])));

                    return str_contains($haystack, $search);
                })
                ->map(function (array $booking): array {
                    $booking['communication_timeline'] = $this->extractCommunicationTimeline((string) ($booking['booking_notes'] ?? ''));
                    return $booking;
                })
                ->values()
                ->all();
        }

        return [
            'module' => strtoupper($module),
            'website_title' => (string) ($summary['website_title'] ?? ($module === 'bazaar' ? 'Bazaar storefront' : 'Servio workspace')),
            'website_url' => (string) ($summary['website_url'] ?? ''),
            'gross_revenue' => (float) ($summary['gross_revenue'] ?? 0),
            'currency' => strtoupper((string) ($summary['currency'] ?? 'USD')),
            'readiness_score' => (int) ($snapshot?->readiness_score ?? 0),
            'updated_at' => $snapshot?->snapshot_updated_at?->toDayDateTimeString(),
            'counts' => [
                'products' => (int) ($counts['product_count'] ?? 0),
                'orders' => (int) ($counts['order_count'] ?? 0),
                'services' => (int) ($counts['service_count'] ?? 0),
                'bookings' => (int) ($counts['booking_count'] ?? 0),
                'customers' => (int) ($counts['customer_count'] ?? 0),
            ],
            'recent_orders' => $recentOrders,
            'recent_bookings' => $recentBookings,
            'recent_coupons' => $recentCoupons,
            'shipping_zones' => $shippingZones,
            'activity' => $activity,
            'filters' => [
                'status' => $statusFilter !== '' ? $statusFilter : 'all',
                'queue' => $queueFilter !== '' ? $queueFilter : 'all',
                'q' => (string) ($filters['q'] ?? ''),
            ],
        ];
    }

    private function parseCommerceOffer(FounderActionPlan $actionPlan): array
    {
        $description = (string) ($actionPlan->description ?? '');
        $lines = preg_split("/\r\n|\n|\r/", $description) ?: [];
        $price = '0.00';
        $sku = '';
        $stock = '';
        $lowStock = '';
        $duration = '';
        $durationUnit = '';
        $capacity = '';
        $staffMode = '';
        $staffId = '';
        $staffIds = [];
        $availabilityDays = [];
        $openTime = '';
        $closeTime = '';
        $categoryName = '';
        $taxRules = [];
        $additionalServices = [];
        $variants = [];
        $extras = [];
        $paymentCollection = 'both';
        $body = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, 'Price:')) {
                $price = trim((string) substr($line, strlen('Price:')));
                continue;
            }
            if (str_starts_with($line, 'Sku:')) {
                $sku = trim((string) substr($line, strlen('Sku:')));
                continue;
            }
            if (str_starts_with($line, 'Stock:')) {
                $stock = trim((string) substr($line, strlen('Stock:')));
                continue;
            }
            if (str_starts_with($line, 'LowStock:')) {
                $lowStock = trim((string) substr($line, strlen('LowStock:')));
                continue;
            }
            if (str_starts_with($line, 'Duration:')) {
                $duration = trim((string) substr($line, strlen('Duration:')));
                continue;
            }
            if (str_starts_with($line, 'DurationUnit:')) {
                $durationUnit = trim((string) substr($line, strlen('DurationUnit:')));
                continue;
            }
            if (str_starts_with($line, 'Capacity:')) {
                $capacity = trim((string) substr($line, strlen('Capacity:')));
                continue;
            }
            if (str_starts_with($line, 'StaffMode:')) {
                $staffMode = trim((string) substr($line, strlen('StaffMode:')));
                continue;
            }
            if (str_starts_with($line, 'StaffId:')) {
                $staffId = trim((string) substr($line, strlen('StaffId:')));
                continue;
            }
            if (str_starts_with($line, 'StaffIdsJson:')) {
                $staffIds = $this->decodeCommerceStringList(substr($line, strlen('StaffIdsJson:')));
                continue;
            }
            if (str_starts_with($line, 'AvailabilityDays:')) {
                $availabilityDays = $this->normalizeAvailabilityDays(explode('|', trim((string) substr($line, strlen('AvailabilityDays:')))));
                continue;
            }
            if (str_starts_with($line, 'OpenTime:')) {
                $openTime = trim((string) substr($line, strlen('OpenTime:')));
                continue;
            }
            if (str_starts_with($line, 'CloseTime:')) {
                $closeTime = trim((string) substr($line, strlen('CloseTime:')));
                continue;
            }
            if (str_starts_with($line, 'Category:')) {
                $categoryName = trim((string) substr($line, strlen('Category:')));
                continue;
            }
            if (str_starts_with($line, 'TaxRulesJson:')) {
                $taxRules = $this->decodeCommerceJsonList(substr($line, strlen('TaxRulesJson:')), ['name', 'value', 'type']);
                continue;
            }
            if (str_starts_with($line, 'AdditionalServicesJson:')) {
                $additionalServices = $this->decodeCommerceJsonList(substr($line, strlen('AdditionalServicesJson:')), ['name', 'price']);
                continue;
            }
            if (str_starts_with($line, 'VariantsJson:')) {
                $variants = $this->decodeCommerceJsonList(substr($line, strlen('VariantsJson:')), ['name', 'price', 'qty', 'low_stock']);
                continue;
            }
            if (str_starts_with($line, 'ExtrasJson:')) {
                $extras = $this->decodeCommerceJsonList(substr($line, strlen('ExtrasJson:')), ['name', 'price']);
                continue;
            }
            if (str_starts_with($line, 'PaymentCollection:')) {
                $paymentCollection = trim((string) substr($line, strlen('PaymentCollection:')));
                continue;
            }

            if (str_starts_with($line, 'Engine:')) {
                continue;
            }

            if (str_starts_with($line, 'Type:')) {
                continue;
            }

            $body[] = $line;
        }

        return [
            'type' => $actionPlan->platform === 'bazaar' ? 'product' : 'service',
            'engine' => $actionPlan->platform,
            'description' => trim(implode("\n", $body)),
            'price' => $price !== '' ? $price : '0.00',
            'category_name' => $categoryName,
            'tax_rules' => $taxRules,
            'sku' => $sku,
            'stock' => $stock !== '' ? $stock : '0',
            'low_stock' => $lowStock !== '' ? $lowStock : '0',
            'duration' => $duration !== '' ? $duration : '30',
            'duration_unit' => $durationUnit !== '' ? $durationUnit : 'minutes',
            'capacity' => $capacity !== '' ? $capacity : '1',
            'staff_mode' => $staffMode !== '' ? $staffMode : 'auto',
            'staff_id' => $staffId,
            'staff_ids' => $staffIds !== [] ? $staffIds : $this->parseStaffIdsText($staffId),
            'availability_days' => $availabilityDays !== [] ? $availabilityDays : ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'open_time' => $openTime !== '' ? $openTime : '09:00',
            'close_time' => $closeTime !== '' ? $closeTime : '17:00',
            'additional_services' => $additionalServices,
            'variants' => $variants,
            'extras' => $extras,
            'payment_collection' => in_array($paymentCollection, ['online_only', 'cash_only', 'both'], true) ? $paymentCollection : 'both',
        ];
    }

    private function parseCommerceConfig(FounderActionPlan $actionPlan): array
    {
        $description = (string) ($actionPlan->description ?? '');
        $lines = preg_split("/\r\n|\n|\r/", $description) ?: [];
        $payload = [
            'type' => '',
            'engine' => $actionPlan->platform,
            'field_one' => '',
            'field_two' => '',
            'field_three' => '',
            'field_four' => '',
        ];

        foreach ($lines as $line) {
            if (str_starts_with($line, 'Config:')) {
                $payload['type'] = trim((string) substr($line, strlen('Config:')));
                continue;
            }

            if (str_starts_with($line, 'Engine:')) {
                $payload['engine'] = trim((string) substr($line, strlen('Engine:')));
                continue;
            }

            if (str_starts_with($line, 'FieldOne:')) {
                $payload['field_one'] = trim((string) substr($line, strlen('FieldOne:')));
                continue;
            }

            if (str_starts_with($line, 'FieldTwo:')) {
                $payload['field_two'] = trim((string) substr($line, strlen('FieldTwo:')));
                continue;
            }

            if (str_starts_with($line, 'FieldThree:')) {
                $payload['field_three'] = trim((string) substr($line, strlen('FieldThree:')));
                continue;
            }

            if (str_starts_with($line, 'FieldFour:')) {
                $payload['field_four'] = trim((string) substr($line, strlen('FieldFour:')));
            }
        }

        return $payload;
    }

    private function serializeCommerceOffer(array $payload): string
    {
        return trim(implode("\n", array_filter([
            'Type: ' . trim((string) ($payload['type'] ?? 'offer')),
            'Engine: ' . trim((string) ($payload['engine'] ?? '')),
            'Price: ' . trim((string) ($payload['price'] ?? '0.00')),
            'Category: ' . trim((string) ($payload['category_name'] ?? '')),
            'TaxRulesJson: ' . json_encode($this->normalizeTaxRules($payload['tax_rules'] ?? []), JSON_UNESCAPED_UNICODE),
            'Sku: ' . trim((string) ($payload['sku'] ?? '')),
            'Stock: ' . trim((string) ($payload['stock'] ?? '')),
            'LowStock: ' . trim((string) ($payload['low_stock'] ?? '')),
            'Duration: ' . trim((string) ($payload['duration'] ?? '')),
            'DurationUnit: ' . trim((string) ($payload['duration_unit'] ?? '')),
            'Capacity: ' . trim((string) ($payload['capacity'] ?? '')),
            'StaffMode: ' . trim((string) ($payload['staff_mode'] ?? '')),
            'StaffId: ' . trim((string) ($payload['staff_id'] ?? '')),
            'StaffIdsJson: ' . json_encode($this->parseStaffIdsText($this->formatStaffIdsText($payload['staff_ids'] ?? [])), JSON_UNESCAPED_UNICODE),
            'AvailabilityDays: ' . implode('|', $this->normalizeAvailabilityDays($payload['availability_days'] ?? [])),
            'OpenTime: ' . trim((string) ($payload['open_time'] ?? '')),
            'CloseTime: ' . trim((string) ($payload['close_time'] ?? '')),
            'AdditionalServicesJson: ' . json_encode($this->normalizeAdditionalServices($payload['additional_services'] ?? []), JSON_UNESCAPED_UNICODE),
            'VariantsJson: ' . json_encode($this->normalizeVariants($payload['variants'] ?? []), JSON_UNESCAPED_UNICODE),
            'ExtrasJson: ' . json_encode($this->normalizeExtras($payload['extras'] ?? []), JSON_UNESCAPED_UNICODE),
            'PaymentCollection: ' . trim((string) ($payload['payment_collection'] ?? 'both')),
            '',
            trim((string) ($payload['description'] ?? '')),
        ], static fn ($value) => $value !== '')));
    }

    private function allowedPublicPaymentChoices(string $paymentCollection): array
    {
        return match ($paymentCollection) {
            'online_only' => ['online'],
            'cash_only' => ['cash'],
            default => ['online', 'cash'],
        };
    }

    private function publicWebsiteSourceTag(array $validated): string
    {
        $parts = array_filter([
            ($source = trim((string) ($validated['src'] ?? ''))) !== '' ? 'Source: ' . $source : null,
            ($promo = trim((string) ($validated['promo'] ?? ''))) !== '' ? 'Promo: ' . $promo : null,
        ]);

        return implode(' | ', $parts);
    }

    private function appendPublicSourceNote(string $notes, string $sourceTag): string
    {
        $notes = trim($notes);
        $sourceTag = trim($sourceTag);

        if ($sourceTag === '') {
            return $notes;
        }

        if ($notes === '') {
            return '[' . $sourceTag . ']';
        }

        return $notes . "\n\n[" . $sourceTag . ']';
    }

    private function promoLinkQrSvgMarkup(string $url): string
    {
        return (string) QrCode::format('svg')->size(260)->margin(1)->generate($url);
    }

    private function promoLinkDownloadBaseName(FounderPromoLink $promoLink): string
    {
        $base = trim((string) ($promoLink->title ?: 'promo-link'));
        $base = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $base) ?? 'promo-link');
        $base = trim($base, '-');

        return $base !== '' ? $base : 'promo-link';
    }

    private function calculatePublicCheckoutAmount(array $site, array $offer, array $attributes): float
    {
        $basePrice = (float) ($offer['base_price'] ?? 0);

        if (($offer['type'] ?? '') === 'product') {
            $quantity = max(1, (int) ($attributes['quantity'] ?? 1));
            $variantName = trim((string) ($attributes['selected_variant'] ?? ''));
            $variantPrice = $basePrice;
            foreach ((array) ($offer['request_options']['variants'] ?? []) as $variant) {
                if (is_array($variant) && trim((string) ($variant['name'] ?? '')) === $variantName) {
                    $variantPrice = (float) ($variant['price'] ?? $basePrice);
                    break;
                }
            }

            $selectedExtras = collect((array) ($attributes['selected_extras'] ?? []))
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->values();
            $extrasPrice = collect((array) ($offer['request_options']['extras'] ?? []))
                ->filter(fn ($extra) => is_array($extra) && $selectedExtras->contains(trim((string) ($extra['name'] ?? ''))))
                ->sum(fn ($extra) => (float) ($extra['price'] ?? 0));

            $shippingCharge = 0.0;
            $deliveryArea = strtolower(trim((string) ($attributes['delivery_area'] ?? '')));
            foreach ((array) ($site['checkout_context']['shipping_zones'] ?? []) as $zone) {
                if (is_array($zone) && strtolower(trim((string) ($zone['area_name'] ?? ''))) === $deliveryArea) {
                    $shippingCharge = (float) ($zone['delivery_charge'] ?? 0);
                    break;
                }
            }

            return round((($variantPrice + $extrasPrice) * $quantity) + $shippingCharge, 2);
        }

        $selectedAddOns = collect((array) ($attributes['selected_additional_services'] ?? []))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values();
        $addOnsPrice = collect((array) ($offer['request_options']['additional_services'] ?? []))
            ->filter(fn ($extra) => is_array($extra) && $selectedAddOns->contains(trim((string) ($extra['name'] ?? ''))))
            ->sum(fn ($extra) => (float) ($extra['price'] ?? 0));

        return round($basePrice + $addOnsPrice, 2);
    }

    private function serializeCommerceConfig(array $payload): string
    {
        return trim(implode("\n", [
            'Config: ' . trim((string) ($payload['type'] ?? '')),
            'Engine: ' . trim((string) ($payload['engine'] ?? '')),
            'FieldOne: ' . trim((string) ($payload['field_one'] ?? '')),
            'FieldTwo: ' . trim((string) ($payload['field_two'] ?? '')),
            'FieldThree: ' . trim((string) ($payload['field_three'] ?? '')),
            'FieldFour: ' . trim((string) ($payload['field_four'] ?? '')),
        ]));
    }

    private function extractCommunicationTimeline(string $notes): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($notes)) ?: [];
        $events = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^\[(?<time>[^\]]+)\]\[(?<channel>[^\]]+)\]\s*(?<message>.+)$/', $line, $matches) === 1) {
                $events[] = [
                    'timestamp' => trim((string) ($matches['time'] ?? '')),
                    'channel' => trim((string) ($matches['channel'] ?? 'manual')),
                    'message' => trim((string) ($matches['message'] ?? '')),
                ];
            }
        }

        return array_reverse($events);
    }

    private function normalizeAvailabilityDays(array $days): array
    {
        $allowed = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        return collect($days)
            ->map(fn ($day) => trim((string) $day))
            ->filter(fn ($day) => in_array($day, $allowed, true))
            ->unique()
            ->values()
            ->all();
    }

    private function decodeCommerceJsonList(string $json, array $keys): array
    {
        $decoded = json_decode(trim($json), true);
        if (!is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item) use ($keys): array {
                $normalized = [];
                foreach ($keys as $key) {
                    $normalized[$key] = trim((string) ($item[$key] ?? ''));
                }
                return $normalized;
            })
            ->filter(function (array $item) use ($keys): bool {
                foreach ($keys as $key) {
                    if ($item[$key] !== '') {
                        return true;
                    }
                }
                return false;
            })
            ->values()
            ->all();
    }

    private function decodeCommerceStringList(string $json): array
    {
        $decoded = json_decode(trim($json), true);
        if (!is_array($decoded)) {
            return [];
        }

        return collect($decoded)->map(fn ($item) => trim((string) $item))->filter()->values()->all();
    }

    private function parseTaxRulesText(string $text): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($text)) ?: [];

        return $this->normalizeTaxRules(collect($lines)
            ->map(function (string $line): array {
                $parts = array_map('trim', explode('|', $line));
                return [
                    'name' => (string) ($parts[0] ?? ''),
                    'value' => (string) ($parts[1] ?? ''),
                    'type' => strtolower((string) ($parts[2] ?? 'percent')),
                ];
            })
            ->all());
    }

    private function normalizeTaxRules(array $rules): array
    {
        return collect($rules)
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item): array {
                $type = strtolower(trim((string) ($item['type'] ?? 'percent')));
                return [
                    'name' => trim((string) ($item['name'] ?? '')),
                    'value' => trim((string) ($item['value'] ?? '')),
                    'type' => in_array($type, ['fixed', 'flat', 'amount'], true) ? 'fixed' : 'percent',
                ];
            })
            ->filter(fn (array $item) => $item['name'] !== '' && $item['value'] !== '')
            ->values()
            ->all();
    }

    private function formatTaxRulesText(array $rules): string
    {
        return collect($this->normalizeTaxRules($rules))
            ->map(fn (array $rule) => implode(' | ', [$rule['name'], $rule['value'], $rule['type']]))
            ->implode("\n");
    }

    private function parseAdditionalServicesText(string $text): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($text)) ?: [];

        return $this->normalizeAdditionalServices(collect($lines)
            ->map(function (string $line): array {
                $parts = array_map('trim', explode('|', $line));
                return [
                    'name' => (string) ($parts[0] ?? ''),
                    'price' => (string) ($parts[1] ?? '0'),
                ];
            })
            ->all());
    }

    private function normalizeAdditionalServices(array $services): array
    {
        return collect($services)
            ->filter(fn ($item) => is_array($item))
            ->map(fn (array $item) => [
                'name' => trim((string) ($item['name'] ?? '')),
                'price' => trim((string) ($item['price'] ?? '0')),
            ])
            ->filter(fn (array $item) => $item['name'] !== '')
            ->values()
            ->all();
    }

    private function formatAdditionalServicesText(array $services): string
    {
        return collect($this->normalizeAdditionalServices($services))
            ->map(fn (array $service) => implode(' | ', [$service['name'], $service['price'] !== '' ? $service['price'] : '0']))
            ->implode("\n");
    }

    private function parseVariantsText(string $text): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($text)) ?: [];

        return $this->normalizeVariants(collect($lines)->map(function (string $line): array {
            $parts = array_map('trim', explode('|', $line));
            return [
                'name' => (string) ($parts[0] ?? ''),
                'price' => (string) ($parts[1] ?? '0'),
                'qty' => (string) ($parts[2] ?? '0'),
                'low_stock' => (string) ($parts[3] ?? '0'),
            ];
        })->all());
    }

    private function normalizeVariants(array $variants): array
    {
        return collect($variants)
            ->filter(fn ($item) => is_array($item))
            ->map(fn (array $item) => [
                'name' => trim((string) ($item['name'] ?? '')),
                'price' => trim((string) ($item['price'] ?? '0')),
                'qty' => trim((string) ($item['qty'] ?? '0')),
                'low_stock' => trim((string) ($item['low_stock'] ?? '0')),
            ])
            ->filter(fn (array $item) => $item['name'] !== '')
            ->values()
            ->all();
    }

    private function formatVariantsText(array $variants): string
    {
        return collect($this->normalizeVariants($variants))
            ->map(fn (array $variant) => implode(' | ', [$variant['name'], $variant['price'], $variant['qty'], $variant['low_stock']]))
            ->implode("\n");
    }

    private function parseExtrasText(string $text): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($text)) ?: [];

        return $this->normalizeExtras(collect($lines)->map(function (string $line): array {
            $parts = array_map('trim', explode('|', $line));
            return [
                'name' => (string) ($parts[0] ?? ''),
                'price' => (string) ($parts[1] ?? '0'),
            ];
        })->all());
    }

    private function normalizeExtras(array $extras): array
    {
        return collect($extras)
            ->filter(fn ($item) => is_array($item))
            ->map(fn (array $item) => [
                'name' => trim((string) ($item['name'] ?? '')),
                'price' => trim((string) ($item['price'] ?? '0')),
            ])
            ->filter(fn (array $item) => $item['name'] !== '')
            ->values()
            ->all();
    }

    private function formatExtrasText(array $extras): string
    {
        return collect($this->normalizeExtras($extras))
            ->map(fn (array $extra) => implode(' | ', [$extra['name'], $extra['price']]))
            ->implode("\n");
    }

    private function parseStaffIdsText(string $text): array
    {
        return collect(preg_split('/[\s,|]+/', trim($text)) ?: [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    private function formatStaffIdsText(array $staffIds): string
    {
        return implode(', ', $this->parseStaffIdsText(implode('|', $staffIds)));
    }

    private function resolveCommerceMessage(string $type, string $template, string $customMessage, array $payload): string
    {
        $customMessage = trim($customMessage);
        if ($customMessage !== '') {
            return $customMessage;
        }

        $template = trim($template);
        if ($template === '') {
            return '';
        }

        return $this->buildCommerceTemplateMessage($type, $template, $payload);
    }

    private function buildCommerceTemplateMessage(string $type, string $template, array $payload): string
    {
        if ($type === 'order') {
            $deliveryDate = $this->formatCommerceDateForMessage((string) ($payload['delivery_date'] ?? ''));
            $deliveryTime = trim((string) ($payload['delivery_time'] ?? ''));

            return match ($template) {
                'packed' => 'Your order has been packed and is being prepared for dispatch.',
                'out_for_delivery' => 'Your order is out for delivery' . $this->buildScheduleSuffix($deliveryDate, $deliveryTime) . '.',
                'delivered' => 'Your order has been marked as delivered. Thank you for ordering with Hatchers.',
                'delayed' => 'Your order timeline has been updated. We are sorry for the delay' . $this->buildScheduleSuffix($deliveryDate, $deliveryTime) . '.',
                default => '',
            };
        }

        $bookingDate = $this->formatCommerceDateForMessage((string) ($payload['booking_date'] ?? ''));
        $startTime = trim((string) ($payload['booking_time'] ?? ''));
        $endTime = trim((string) ($payload['booking_endtime'] ?? ''));
        $timeRange = trim(implode(' to ', array_filter([$startTime, $endTime])));

        return match ($template) {
            'confirmed' => 'Your booking is confirmed' . $this->buildScheduleSuffix($bookingDate, $timeRange) . '.',
            'rescheduled' => 'Your booking has been rescheduled' . $this->buildScheduleSuffix($bookingDate, $timeRange) . '.',
            'provider_assigned' => 'A team member has been assigned to your booking and we are ready for the scheduled visit.',
            'completed' => 'Your booking has been completed. Thank you for choosing Hatchers.',
            default => '',
        };
    }

    private function buildScheduleSuffix(string $date, string $time): string
    {
        $parts = array_filter([$date, $time]);
        if ($parts === []) {
            return '';
        }

        return ' for ' . implode(' at ', $parts);
    }

    private function formatCommerceDateForMessage(string $date): string
    {
        $date = trim($date);
        if ($date === '') {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($date)->toFormattedDateString();
        } catch (Throwable) {
            return $date;
        }
    }

    private function orderMessageTemplates(): array
    {
        return [
            'packed' => 'Packed and preparing dispatch',
            'out_for_delivery' => 'Out for delivery',
            'delivered' => 'Delivered',
            'delayed' => 'Delayed update',
        ];
    }

    private function bookingMessageTemplates(): array
    {
        return [
            'confirmed' => 'Booking confirmed',
            'rescheduled' => 'Booking rescheduled',
            'provider_assigned' => 'Provider assigned',
            'completed' => 'Booking completed',
        ];
    }

    private function founderSignupPlans(): array
    {
        return [
            'hatchers-os-trial' => [
                'code' => 'hatchers-os-trial',
                'name' => 'Hatchers OS Free Trial',
                'label' => 'Free Trial',
                'amount' => 0,
                'price_display' => '$0',
                'period_display' => '/7 days',
                'description' => 'A 7-day founder trial of Hatchers OS without mentor access so founders can experience the operating system before committing.',
                'billing_status' => 'trialing',
                'trial_days' => 7,
                'transitions_days' => 7,
                'transitions_to_plan_code' => 'hatchers-os',
                'cta' => 'Start free trial',
                'features' => [
                    'Unified founder dashboard',
                    'Atlas assistant across the OS',
                    'Website setup for product or service businesses',
                    '7-day founder-only trial without mentor access',
                ],
            ],
            'hatchers-os' => [
                'code' => 'hatchers-os',
                'name' => 'Hatchers OS',
                'label' => 'Self-serve',
                'amount' => 99,
                'price_display' => '$99',
                'period_display' => '/month',
                'description' => 'For founders who want the full OS, unified AI, website tools, content generation, and business workflows without a mentor.',
                'billing_status' => 'draft',
                'cta' => 'Choose Hatchers OS',
                'features' => [
                    'Unified founder dashboard',
                    'Atlas assistant across all workflows',
                    'Website building for product or service businesses',
                    'Marketing and content studio',
                ],
            ],
            'hatchers-os-mentor' => [
                'code' => 'hatchers-os-mentor',
                'name' => 'Hatchers OS + Mentor',
                'label' => 'Guided growth',
                'amount' => 600,
                'price_display' => '$600',
                'period_display' => '/month',
                'description' => 'Mentor-guided support for the first 6 months, then transitions to the standard OS subscription at $99/month.',
                'billing_status' => 'draft',
                'mentor_months' => 6,
                'transitions_to_plan_code' => 'hatchers-os',
                'cta' => 'Choose OS + Mentor',
                'features' => [
                    'Everything in Hatchers OS',
                    'Assigned mentor and weekly execution rhythm',
                    'Tasks, milestones, and meeting guidance',
                    'Atlas aware of mentor context and founder progress',
                ],
            ],
        ];
    }

    private function resolvePublicWebsiteCompany(string $websitePath): ?Company
    {
        $normalizedPath = trim(strtolower($websitePath), '/');
        if ($normalizedPath === '') {
            return null;
        }

        $company = Company::query()
            ->where('website_status', 'live')
            ->with('founder')
            ->get()
            ->first(function (Company $company) use ($normalizedPath): bool {
                $path = trim(strtolower((string) ($company->website_path ?? '')), '/');
                if ($path === '') {
                    $path = strtolower((string) str($company->company_name ?: 'your-business')->slug('-'));
                }

                return $path === $normalizedPath;
            });

        if ($company && blank($company->website_path)) {
            $company->website_path = $normalizedPath;
            $company->website_url = $this->buildCompanyWebsiteUrl($company, (string) ($company->website_engine ?? ''));
            $company->save();
        }

        return $company;
    }

    private function resolvePublicWebsiteRootCompany(string $websiteRoot): ?Company
    {
        return $this->resolvePublicWebsiteCompany($websiteRoot);
    }

    private function proxyEngineStorefront(Company $company, string $proxyPath, Request $request, PublicWebsiteService $publicWebsiteService)
    {
        $site = $publicWebsiteService->build($company);
        $engineProxyUrl = trim((string) ($site['engine_proxy_url'] ?? ''));
        $websiteRoot = trim((string) ($site['path'] ?? $company->website_path ?? ''), '/');

        if ($engineProxyUrl === '' || $websiteRoot === '') {
            return view('os.public-website', [
                'pageTitle' => (string) ($company->company_name ?: 'Business Website'),
                'site' => $site,
                'sourceContext' => [
                    'src' => trim((string) $request->query('src', '')),
                    'promo' => trim((string) $request->query('promo', '')),
                    'offer' => trim((string) $request->query('offer', '')),
                ],
            ]);
        }

        if ($this->isRecursiveStorefrontTarget($engineProxyUrl, $websiteRoot)) {
            Log::warning('Skipped recursive storefront proxy target.', [
                'company_id' => $company->id,
                'website_root' => $websiteRoot,
                'target' => $engineProxyUrl,
            ]);

            return view('os.public-website', [
                'pageTitle' => (string) ($company->company_name ?: 'Business Website'),
                'site' => $site,
                'sourceContext' => [
                    'src' => trim((string) $request->query('src', '')),
                    'promo' => trim((string) $request->query('promo', '')),
                    'offer' => trim((string) $request->query('offer', '')),
                ],
            ]);
        }

        $targetUrl = rtrim($engineProxyUrl, '/');
        $proxyPath = trim($proxyPath, '/');
        if ($proxyPath !== '') {
            $targetUrl .= '/' . $proxyPath;
        }

        $options = [
            'query' => $request->query(),
            'allow_redirects' => false,
        ];

        $cookiePrefix = $this->engineStorefrontCookiePrefix((string) ($site['engine'] ?? 'storefront'), $websiteRoot);

        $forwardHeaders = array_filter([
            'Accept' => $request->header('Accept'),
            'Accept-Language' => $request->header('Accept-Language'),
            'User-Agent' => $request->userAgent(),
            'Referer' => $request->header('Referer'),
            'X-Requested-With' => $request->header('X-Requested-With'),
        ], static fn ($value) => filled($value));

        $upstreamCookieHeader = $this->upstreamStorefrontCookieHeader($request, $cookiePrefix);
        if ($upstreamCookieHeader !== '') {
            $forwardHeaders['Cookie'] = $upstreamCookieHeader;
        }

        $client = Http::withHeaders($forwardHeaders)->withOptions($options);
        $method = strtoupper($request->method());

        if (!in_array($method, ['GET', 'HEAD'], true)) {
            $contentType = strtolower((string) $request->header('Content-Type', ''));
            if (str_contains($contentType, 'application/json')) {
                $options['body'] = $request->getContent();
                $forwardHeaders['Content-Type'] = $request->header('Content-Type');
                $client = Http::withHeaders($forwardHeaders)->withOptions($options);
            } else {
                $client = Http::withHeaders($forwardHeaders)->withOptions($options)->asForm();
                $options['form_params'] = $request->except(['_token']);
            }
        }

        $upstream = $client->send($method, $targetUrl, $options);
        $status = $upstream->status();
        $contentType = strtolower((string) $upstream->header('Content-Type', 'text/html; charset=UTF-8'));

        if ($status >= 300 && $status < 400 && filled($upstream->header('Location'))) {
            $location = $this->rewriteStorefrontUrlToOsPath((string) $upstream->header('Location'), $engineProxyUrl, $websiteRoot);

            return redirect()->away($location, $status);
        }

        $body = $upstream->body();
        if (str_contains($contentType, 'text/html')) {
            $body = $this->rewriteStorefrontHtmlForOs($body, $engineProxyUrl, $websiteRoot);
        } elseif (str_contains($contentType, 'javascript') || str_contains($contentType, 'json')) {
            $body = $this->rewriteStorefrontTextForOs($body, $engineProxyUrl, $websiteRoot);
        }

        $response = response($body, $status);

        foreach ($upstream->headers() as $header => $values) {
            $headerName = (string) $header;
            if (in_array(strtolower($headerName), ['content-length', 'transfer-encoding', 'content-encoding', 'set-cookie'], true)) {
                continue;
            }

            foreach ((array) $values as $value) {
                $response->headers->set($headerName, $value, false);
            }
        }

        $setCookieHeaders = $upstream->headers()['Set-Cookie'] ?? [];
        foreach ((array) $setCookieHeaders as $cookieLine) {
            $response->headers->setCookie($this->rewriteStorefrontCookie((string) $cookieLine, $request, $cookiePrefix, $websiteRoot));
        }

        return $response;
    }

    private function rewriteStorefrontHtmlForOs(string $html, string $engineProxyUrl, string $websiteRoot): string
    {
        $rewritten = $this->rewriteStorefrontTextForOs($html, $engineProxyUrl, $websiteRoot);
        $prefix = '/' . trim($websiteRoot, '/');

        $rewritten = preg_replace('/\b(href|src|action)=([\"\'])\/(?!\/)/i', '$1=$2' . $prefix . '/', $rewritten) ?? $rewritten;
        $rewritten = preg_replace('/url\(([\"\']?)\/(?!\/)/i', 'url($1' . $prefix . '/', $rewritten) ?? $rewritten;

        return $rewritten;
    }

    private function rewriteStorefrontTextForOs(string $content, string $engineProxyUrl, string $websiteRoot): string
    {
        $osBaseUrl = rtrim((string) config('app.url'), '/') . '/' . trim($websiteRoot, '/');
        $normalizedEngineUrl = rtrim($engineProxyUrl, '/');
        $escapedEngineUrl = str_replace('/', '\\/', $normalizedEngineUrl);
        $escapedOsBaseUrl = str_replace('/', '\\/', $osBaseUrl);

        $rewritten = str_replace(
            [$normalizedEngineUrl, $escapedEngineUrl],
            [$osBaseUrl, $escapedOsBaseUrl],
            $content
        );

        $prefix = '/' . trim($websiteRoot, '/');

        $patterns = [
            '/([="\':(,\s])\/(?!\/)/' => '$1' . $prefix . '/',
            '/(url\([\"\']?)\/(?!\/)/i' => '$1' . $prefix . '/',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $candidate = preg_replace($pattern, $replacement, $rewritten);
            if (is_string($candidate)) {
                $rewritten = $candidate;
            }
        }

        return $rewritten;
    }

    private function rewriteStorefrontUrlToOsPath(string $url, string $engineProxyUrl, string $websiteRoot): string
    {
        $rewritten = $this->rewriteStorefrontTextForOs($url, $engineProxyUrl, $websiteRoot);
        $trimmed = trim($rewritten);

        if (preg_match('/^https?:\/\//i', $trimmed)) {
            return $trimmed;
        }

        if (str_starts_with($trimmed, '/')) {
            return rtrim((string) config('app.url'), '/') . $trimmed;
        }

        return rtrim((string) config('app.url'), '/') . '/' . trim($websiteRoot, '/') . '/' . ltrim($trimmed, '/');
    }

    private function rewriteStorefrontCookie(string $cookieLine, Request $request, string $cookiePrefix, string $websiteRoot): Cookie
    {
        $parts = array_map('trim', explode(';', $cookieLine));
        $nameValue = array_shift($parts) ?: '=';
        [$name, $value] = array_pad(explode('=', $nameValue, 2), 2, '');
        $name = $cookiePrefix . $name;

        $expires = 0;
        $path = '/' . trim($websiteRoot, '/');
        $secure = $request->isSecure();
        $httpOnly = true;
        $sameSite = null;

        foreach ($parts as $part) {
            [$attribute, $attributeValue] = array_pad(explode('=', $part, 2), 2, '');
            $attribute = strtolower(trim($attribute));
            $attributeValue = trim($attributeValue);

            if ($attribute === 'expires' && $attributeValue !== '') {
                $timestamp = strtotime($attributeValue);
                if ($timestamp !== false) {
                    $expires = $timestamp;
                }
            } elseif ($attribute === 'path' && $attributeValue !== '') {
                $path = $attributeValue;
            } elseif ($attribute === 'secure') {
                $secure = true;
            } elseif ($attribute === 'httponly') {
                $httpOnly = true;
            } elseif ($attribute === 'samesite' && $attributeValue !== '') {
                $sameSite = $attributeValue;
            }
        }

        $minutes = $expires > 0 ? max(0, (int) ceil(($expires - time()) / 60)) : 0;

        return Cookie::create($name, $value, $minutes, $path, null, $secure, $httpOnly, false, $sameSite);
    }

    private function engineStorefrontCookiePrefix(string $engine, string $websiteRoot): string
    {
        $normalizedRoot = preg_replace('/[^A-Za-z0-9_]/', '_', trim($websiteRoot, '/')) ?: 'site';
        $normalizedEngine = preg_replace('/[^A-Za-z0-9_]/', '_', trim($engine)) ?: 'storefront';

        return 'hatchers_' . strtolower($normalizedEngine) . '_' . strtolower($normalizedRoot) . '_';
    }

    private function upstreamStorefrontCookieHeader(Request $request, string $cookiePrefix): string
    {
        $pairs = [];

        foreach ($request->cookies->all() as $name => $value) {
            if (!str_starts_with((string) $name, $cookiePrefix)) {
                continue;
            }

            $upstreamName = substr((string) $name, strlen($cookiePrefix));
            if ($upstreamName === '') {
                continue;
            }

            $pairs[] = $upstreamName . '=' . rawurlencode((string) $value);
        }

        return implode('; ', $pairs);
    }

    private function isRecursiveStorefrontTarget(string $targetUrl, string $websiteRoot): bool
    {
        $targetUrl = rtrim(trim($targetUrl), '/');
        $osBaseUrl = rtrim((string) config('app.url'), '/');
        $websiteRoot = trim($websiteRoot, '/');

        if ($targetUrl === '' || $osBaseUrl === '') {
            return false;
        }

        return $targetUrl === $osBaseUrl || ($websiteRoot !== '' && $targetUrl === $osBaseUrl . '/' . $websiteRoot);
    }

    private function atlasProxyQuery(Request $request, bool $excludeWorkspaceMeta = false): array
    {
        $query = $request->query();
        if ($excludeWorkspaceMeta) {
            unset($query['target'], $query['title']);
        }

        return $query;
    }

    private function rewriteAtlasHtmlForOs(string $html, string $atlasBaseUrl): string
    {
        $rewritten = $this->rewriteAtlasTextForOs($html, $atlasBaseUrl);
        $prefix = '/ai-studio/proxy';

        $rewritten = preg_replace('/\b(href|src|action)=([\"\'])\/(?!\/)/i', '$1=$2' . $prefix . '/', $rewritten) ?? $rewritten;
        $rewritten = preg_replace('/url\(([\"\']?)\/(?!\/)/i', 'url($1' . $prefix . '/', $rewritten) ?? $rewritten;

        return $rewritten;
    }

    private function rewriteAtlasTextForOs(string $content, string $atlasBaseUrl): string
    {
        $proxyBase = rtrim((string) config('app.url'), '/') . '/ai-studio/proxy';
        $normalizedAtlasUrl = rtrim($atlasBaseUrl, '/');
        $escapedAtlasUrl = str_replace('/', '\\/', $normalizedAtlasUrl);
        $escapedProxyBase = str_replace('/', '\\/', $proxyBase);

        $rewritten = str_replace(
            [$normalizedAtlasUrl, $escapedAtlasUrl],
            [$proxyBase, $escapedProxyBase],
            $content
        );

        $patterns = [
            '/([="\':(,\s])\/(?!\/)/' => '$1/ai-studio/proxy/',
            '/(url\([\"\']?)\/(?!\/)/i' => '$1/ai-studio/proxy/',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $candidate = preg_replace($pattern, $replacement, $rewritten);
            if (is_string($candidate)) {
                $rewritten = $candidate;
            }
        }

        return $rewritten;
    }

    private function rewriteAtlasUrlToOsPath(string $url, string $atlasBaseUrl): string
    {
        $rewritten = $this->rewriteAtlasTextForOs($url, $atlasBaseUrl);
        $trimmed = trim($rewritten);

        if (preg_match('/^https?:\/\//i', $trimmed)) {
            return $trimmed;
        }

        if (str_starts_with($trimmed, '/')) {
            return rtrim((string) config('app.url'), '/') . $trimmed;
        }

        return rtrim((string) config('app.url'), '/') . '/ai-studio/proxy/' . ltrim($trimmed, '/');
    }

    private function rewriteAtlasWorkspaceCookie(string $cookieLine, Request $request, string $cookiePrefix): Cookie
    {
        $parts = array_map('trim', explode(';', $cookieLine));
        $nameValue = array_shift($parts) ?: '=';
        [$name, $value] = array_pad(explode('=', $nameValue, 2), 2, '');
        $name = $cookiePrefix . $name;

        $expires = 0;
        $path = '/ai-studio/proxy';
        $secure = $request->isSecure();
        $httpOnly = true;
        $sameSite = null;

        foreach ($parts as $part) {
            [$attribute, $attributeValue] = array_pad(explode('=', $part, 2), 2, '');
            $attribute = strtolower(trim($attribute));
            $attributeValue = trim($attributeValue);

            if ($attribute === 'expires' && $attributeValue !== '') {
                $timestamp = strtotime($attributeValue);
                if ($timestamp !== false) {
                    $expires = $timestamp;
                }
            } elseif ($attribute === 'path' && $attributeValue !== '') {
                $path = '/ai-studio/proxy';
            } elseif ($attribute === 'secure') {
                $secure = true;
            } elseif ($attribute === 'httponly') {
                $httpOnly = true;
            } elseif ($attribute === 'samesite' && $attributeValue !== '') {
                $sameSite = $attributeValue;
            }
        }

        $minutes = $expires > 0 ? max(0, (int) ceil(($expires - time()) / 60)) : 0;

        return Cookie::create($name, $value, $minutes, $path, null, $secure, $httpOnly, false, $sameSite);
    }

    private function upstreamWorkspaceCookieHeader(Request $request, string $cookiePrefix): string
    {
        $pairs = [];

        foreach ($request->cookies->all() as $name => $value) {
            if (!str_starts_with((string) $name, $cookiePrefix)) {
                continue;
            }

            $upstreamName = substr((string) $name, strlen($cookiePrefix));
            if ($upstreamName === '') {
                continue;
            }

            $pairs[] = $upstreamName . '=' . rawurlencode((string) $value);
        }

        return implode('; ', $pairs);
    }

    private function resolvePublicWebsiteOffer(array $site, string $type, string $title): ?array
    {
        return collect($site['offers'] ?? [])
            ->first(function ($offer) use ($type, $title): bool {
                return is_array($offer)
                    && (string) ($offer['type'] ?? '') === $type
                    && trim((string) ($offer['title'] ?? '')) === trim($title);
            });
    }

    private function publicServiceDurationMinutes(array $offer): int
    {
        $duration = max(1, (int) ($offer['request_options']['duration'] ?? 30));
        $unit = strtolower(trim((string) ($offer['request_options']['duration_unit'] ?? 'minutes')));

        return $unit === 'hours' ? $duration * 60 : $duration;
    }

    private function founderIndustryOptions(): array
    {
        return [
            'E-commerce and retail',
            'Professional services',
            'Coaching and consulting',
            'Education and training',
            'Health and wellness',
            'Beauty and personal care',
            'Food and hospitality',
            'Creative and media',
            'Technology and software',
            'Real estate and property',
        ];
    }

    private function verticalBlueprintDefinitions(): array
    {
        return [
            'dog-walking' => [
                'code' => 'dog-walking',
                'name' => 'Dog Walking',
                'business_model' => 'service',
                'engine' => 'servio',
                'description' => 'A local dog walking launch system focused on bookings, neighborhood trust, and repeat weekly packages.',
                'default_offer_json' => ['core_offer' => '1:1 services', 'upsells' => ['Extended walk', 'Feeding', 'Photo updates']],
                'default_pricing_json' => ['tier_1' => 'Single walk', 'tier_2' => '3-walk weekly plan', 'tier_3' => '5-walk weekly plan'],
                'default_pages_json' => ['hero', 'how_it_works', 'services', 'service_area', 'trust', 'faq', 'booking_cta'],
                'default_tasks_json' => ['Join local pet groups', 'Post neighborhood intro offer', 'Follow up with leads'],
                'default_channels_json' => ['Facebook groups', 'Nextdoor', 'Local SEO', 'Neighborhood referrals'],
                'default_cta_json' => ['primary' => 'Book a walk', 'secondary' => 'Get a weekly plan'],
                'default_image_queries_json' => ['dog walking', 'pet owner outdoors', 'happy dog on leash'],
                'funnel_framework_json' => ['Lead magnet', 'Problem', 'Proof', 'Offer stack', 'Guarantee', 'Urgency', 'FAQ'],
                'pricing_preset_json' => ['tier_1' => 'Single walk', 'tier_2' => '3-walk weekly plan', 'tier_3' => '5-walk weekly plan'],
                'channel_playbook_json' => ['Facebook groups', 'Nextdoor', 'Neighborhood referrals'],
                'script_library_json' => ['Helpful neighborhood intro', 'Weekly package follow-up', 'Close with recurring convenience'],
            ],
            'home-cleaning' => [
                'code' => 'home-cleaning',
                'name' => 'Home Cleaning',
                'business_model' => 'service',
                'engine' => 'servio',
                'description' => 'A direct-response cleaning launch system focused on quotes, recurring plans, and local trust.',
                'default_offer_json' => ['core_offer' => '1:1 services', 'upsells' => ['Deep clean', 'Move-in clean', 'Weekly cleaning']],
                'default_pricing_json' => ['tier_1' => 'Standard clean', 'tier_2' => 'Deep clean', 'tier_3' => 'Weekly plan'],
                'default_pages_json' => ['hero', 'packages', 'before_after', 'why_choose_us', 'faq', 'quote_cta'],
                'default_tasks_json' => ['List in local groups', 'Reach out to apartment communities', 'Post before/after content'],
                'default_channels_json' => ['Facebook groups', 'Google Business Profile', 'Apartment communities', 'Referrals'],
                'default_cta_json' => ['primary' => 'Book a clean', 'secondary' => 'Request a quote'],
                'default_image_queries_json' => ['home cleaning', 'clean modern apartment', 'professional cleaner'],
                'funnel_framework_json' => ['Lead magnet', 'Mess and stress problem', 'Before / after proof', 'Offer stack', 'Guarantee', 'Urgency', 'FAQ'],
                'pricing_preset_json' => ['tier_1' => 'Standard clean', 'tier_2' => 'Deep clean', 'tier_3' => 'Weekly plan'],
                'channel_playbook_json' => ['Facebook groups', 'Google Business Profile', 'Apartment communities'],
                'script_library_json' => ['Apartment community outreach', 'Before / after follow-up', 'Recurring plan close'],
            ],
            'barber-services' => [
                'code' => 'barber-services',
                'name' => 'Barber Services',
                'business_model' => 'service',
                'engine' => 'servio',
                'description' => 'A barbershop booking system focused on appointment conversion, style proof, and repeat visits.',
                'default_offer_json' => ['core_offer' => '1:1 services', 'upsells' => ['Beard trim', 'Haircut + beard combo', 'Premium grooming package']],
                'default_pricing_json' => ['tier_1' => 'Haircut', 'tier_2' => 'Haircut + beard', 'tier_3' => 'Premium package'],
                'default_pages_json' => ['hero', 'service_menu', 'gallery', 'reviews', 'location_hours', 'booking_cta'],
                'default_tasks_json' => ['Post haircut examples', 'Follow up with prior clients', 'Launch referral offer'],
                'default_channels_json' => ['Instagram', 'Local community groups', 'Referrals', 'Google Business Profile'],
                'default_cta_json' => ['primary' => 'Book your cut', 'secondary' => 'See service menu'],
                'default_image_queries_json' => ['barbershop', 'barber haircut', 'modern grooming'],
                'funnel_framework_json' => ['Style proof', 'Problem', 'Gallery proof', 'Offer stack', 'Guarantee', 'Urgency', 'FAQ'],
                'pricing_preset_json' => ['tier_1' => 'Haircut', 'tier_2' => 'Haircut + beard', 'tier_3' => 'Premium package'],
                'channel_playbook_json' => ['Instagram', 'Referrals', 'Google Business Profile'],
                'script_library_json' => ['Fresh cut outreach', 'Referral ask', 'Premium package close'],
            ],
            'tutoring-coaching' => [
                'code' => 'tutoring-coaching',
                'name' => 'Tutoring / Coaching',
                'business_model' => 'service',
                'engine' => 'servio',
                'description' => 'A session-based authority funnel for tutoring or coaching with consultation and package conversion.',
                'default_offer_json' => ['core_offer' => 'Courses or workshops', 'upsells' => ['Weekly package', 'Monthly package', 'Premium guidance']],
                'default_pricing_json' => ['tier_1' => 'Intro session', 'tier_2' => 'Single session', 'tier_3' => 'Monthly package'],
                'default_pages_json' => ['hero', 'outcomes', 'who_its_for', 'plans', 'credibility', 'faq', 'consult_cta'],
                'default_tasks_json' => ['Post expertise content', 'Contact parent/student groups', 'Follow up with inquiries'],
                'default_channels_json' => ['Facebook groups', 'WhatsApp referrals', 'Local SEO', 'Community groups'],
                'default_cta_json' => ['primary' => 'Book a consultation', 'secondary' => 'See session plans'],
                'default_image_queries_json' => ['tutoring session', 'online coaching', 'teacher with student'],
                'funnel_framework_json' => ['Lead magnet', 'Problem', 'Authority proof', 'Offer stack', 'Guarantee', 'Urgency', 'FAQ'],
                'pricing_preset_json' => ['tier_1' => 'Intro session', 'tier_2' => 'Single session', 'tier_3' => 'Monthly package'],
                'channel_playbook_json' => ['Facebook groups', 'WhatsApp referrals', 'Community groups'],
                'script_library_json' => ['Consultation opener', 'Parent reassurance follow-up', 'Package close'],
            ],
            'handmade-products' => [
                'code' => 'handmade-products',
                'name' => 'Handmade Products',
                'business_model' => 'product',
                'engine' => 'bazaar',
                'description' => 'A small product brand launch system focused on offer clarity, bundles, and direct-response product pages.',
                'default_offer_json' => ['core_offer' => 'Physical products', 'upsells' => ['Bundle', 'Gift wrap', 'Limited edition']],
                'default_pricing_json' => ['tier_1' => 'Single item', 'tier_2' => 'Bundle', 'tier_3' => 'Limited edition'],
                'default_pages_json' => ['hero', 'featured_collection', 'product_grid', 'about_maker', 'trust_shipping', 'offer_cta'],
                'default_tasks_json' => ['Post product photos', 'Launch a bundle offer', 'Reach niche communities'],
                'default_channels_json' => ['Instagram', 'Facebook groups', 'Pinterest', 'Niche communities'],
                'default_cta_json' => ['primary' => 'Shop now', 'secondary' => 'See featured collection'],
                'default_image_queries_json' => ['handmade products', 'artisan workshop', 'product flatlay'],
                'funnel_framework_json' => ['Lead magnet', 'Product problem', 'Maker proof', 'Offer stack', 'Guarantee', 'Urgency', 'FAQ'],
                'pricing_preset_json' => ['tier_1' => 'Single item', 'tier_2' => 'Bundle', 'tier_3' => 'Limited edition'],
                'channel_playbook_json' => ['Instagram', 'Pinterest', 'Niche communities'],
                'script_library_json' => ['Product story caption', 'Bundle follow-up', 'Limited edition close'],
            ],
        ];
    }

    private function upsertVerticalBlueprint(string $code): VerticalBlueprint
    {
        $definition = $this->verticalBlueprintDefinitions()[$code] ?? null;
        if ($definition === null) {
            abort(422, 'The selected business blueprint is invalid.');
        }

        return VerticalBlueprint::updateOrCreate(
            ['code' => $definition['code']],
            [
                'name' => $definition['name'],
                'business_model' => $definition['business_model'],
                'engine' => $definition['engine'],
                'description' => $definition['description'],
                'default_offer_json' => $definition['default_offer_json'],
                'default_pricing_json' => $definition['default_pricing_json'],
                'default_pages_json' => $definition['default_pages_json'],
                'default_tasks_json' => $definition['default_tasks_json'],
                'default_channels_json' => $definition['default_channels_json'],
                'default_cta_json' => $definition['default_cta_json'],
                'default_image_queries_json' => $definition['default_image_queries_json'],
                'funnel_framework_json' => $definition['funnel_framework_json'] ?? [],
                'pricing_preset_json' => $definition['pricing_preset_json'] ?? [],
                'channel_playbook_json' => $definition['channel_playbook_json'] ?? [],
                'script_library_json' => $definition['script_library_json'] ?? [],
                'status' => 'active',
            ]
        );
    }

    private function commaSeparatedValues(string $value): array
    {
        return array_values(array_filter(array_map(
            static fn (string $item): string => trim($item),
            explode(',', $value)
        )));
    }

    private function adminBlueprintList(string $value): array
    {
        return array_values(array_filter(array_map(
            static fn (string $item): string => trim($item),
            preg_split('/[\r\n,]+/', $value) ?: []
        )));
    }

    private function founderCoreOfferOptions(): array
    {
        return [
            'Physical products',
            'Digital products',
            '1:1 services',
            'Group programs',
            'Membership or subscription',
            'Courses or workshops',
            'Agency services',
            'Hybrid offer',
        ];
    }

    private function addCampaignToAtlasSnapshot(Founder $founder, string $title, string $description, string $editUrl = ''): void
    {
        $founder->loadMissing('moduleSnapshots');
        $snapshot = $founder->moduleSnapshots->firstWhere('module', 'atlas');
        if (!$snapshot) {
            return;
        }

        $payload = is_array($snapshot->payload_json) ? $snapshot->payload_json : [];
        $summary = is_array($payload['summary'] ?? null) ? $payload['summary'] : [];
        $recent = array_values(array_filter(
            is_array($summary['recent_campaigns'] ?? null) ? $summary['recent_campaigns'] : [],
            fn (array $campaign) => strcasecmp(trim((string) ($campaign['title'] ?? '')), trim($title)) !== 0
        ));

        array_unshift($recent, [
            'title' => $title,
            'description' => $description,
            'generated_posts_count' => 0,
            'updated_at' => now()->toDateTimeString(),
            'url' => $editUrl,
        ]);

        $summary['recent_campaigns'] = $recent;
        $summary['generated_campaigns_count'] = max(
            (int) ($summary['generated_campaigns_count'] ?? 0),
            count($recent) + count(is_array($summary['archived_campaigns'] ?? null) ? $summary['archived_campaigns'] : [])
        );
        $payload['summary'] = $summary;
        $payload['updated_at'] = now()->toIso8601String();

        $snapshot->forceFill([
            'payload_json' => $payload,
            'snapshot_updated_at' => now(),
        ])->save();
    }

    private function marketingContentRequests(Founder $founder)
    {
        return FounderActionPlan::query()
            ->where('founder_id', $founder->id)
            ->where('platform', 'atlas')
            ->whereIn('status', ['draft', 'pending', 'approved', 'completed', 'published'])
            ->orderByRaw("case when status = 'pending' then 0 when status = 'approved' then 1 when status = 'draft' then 2 when status = 'completed' then 3 else 4 end")
            ->orderByDesc('updated_at')
            ->limit(12)
            ->get()
            ->map(function (FounderActionPlan $requestItem) {
                $parsed = $this->parseMarketingContentRequest($requestItem->description ?? '');

                return [
                    'id' => $requestItem->id,
                    'title' => $requestItem->title,
                    'status' => $requestItem->status,
                    'updated_at' => optional($requestItem->updated_at)?->toDayDateTimeString(),
                    'channel' => $parsed['channel'],
                    'goal' => $parsed['goal'],
                    'publish_target' => $parsed['publish_target'],
                    'publish_target_key' => $parsed['publish_target_key'],
                    'brief' => $parsed['brief'],
                    'draft' => $parsed['draft'],
                    'has_draft' => trim($parsed['draft']) !== '',
                    'preview' => $this->renderMarketingDraftPreview($requestItem->title, $parsed),
                    'cta_url' => $requestItem->cta_url,
                ];
            });
    }

    private function marketingChannelAnalytics(Founder $founder): array
    {
        $analytics = [];

        foreach ($this->marketingContentRequests($founder) as $requestItem) {
            $key = strtolower(trim((string) ($requestItem['channel'] ?? '')));
            if ($key === '') {
                $key = 'unspecified';
            }

            if (!isset($analytics[$key])) {
                $analytics[$key] = [
                    'channel' => $requestItem['channel'] ?: 'Unspecified',
                    'total' => 0,
                    'approved' => 0,
                    'published' => 0,
                ];
            }

            $analytics[$key]['total']++;
            if (($requestItem['status'] ?? '') === 'approved') {
                $analytics[$key]['approved']++;
            }
            if (($requestItem['status'] ?? '') === 'published') {
                $analytics[$key]['published']++;
            }
        }

        uasort($analytics, fn (array $left, array $right) => $right['total'] <=> $left['total']);

        return array_values($analytics);
    }

    private function marketingAtlasHistory(Founder $founder): array
    {
        $founder->loadMissing('moduleSnapshots');
        $snapshot = $founder->moduleSnapshots->firstWhere('module', 'atlas');
        $payload = is_array($snapshot?->payload_json) ? $snapshot->payload_json : [];
        $recentActivity = is_array($payload['recent_activity'] ?? null) ? $payload['recent_activity'] : [];

        $items = [];
        foreach (array_slice($recentActivity, 0, 12) as $entry) {
            $message = trim((string) $entry);
            if ($message === '') {
                continue;
            }

            $kind = 'activity';
            if (stripos($message, 'chat') !== false) {
                $kind = 'chat';
            } elseif (stripos($message, 'agent') !== false) {
                $kind = 'agent';
            } elseif (stripos($message, 'content') !== false || stripos($message, 'post') !== false || stripos($message, 'campaign') !== false) {
                $kind = 'content';
            }

            $items[] = [
                'kind' => $kind,
                'message' => $message,
                'updated_at' => optional($snapshot?->snapshot_updated_at)->toDateTimeString() ?: 'Recently',
            ];
        }

        return $items;
    }

    private function marketingPublishTargets(): array
    {
        return [
            'atlas' => 'Atlas Social Studio',
            'website' => 'Website Workspace',
            'bazaar' => 'Bazaar Store',
            'servio' => 'Servio Services',
        ];
    }

    private function parseMarketingContentRequest(string $description): array
    {
        $parts = preg_split("/\n\n--- GENERATED DRAFT ---\n\n/", $description, 2);
        $metaAndBrief = (string) ($parts[0] ?? '');
        $draft = trim((string) ($parts[1] ?? ''));

        $lines = preg_split("/\n/", $metaAndBrief) ?: [];
        $channel = '';
        $goal = '';
        $publishTarget = '';
        $publishTargetKey = '';
        $briefLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            if (str_starts_with($trimmed, 'Channel: ')) {
                $channel = trim(substr($trimmed, 9));
                continue;
            }

            if (str_starts_with($trimmed, 'Goal: ')) {
                $goal = trim(substr($trimmed, 6));
                continue;
            }

            if (str_starts_with($trimmed, 'Publish target: ')) {
                $publishTarget = trim(substr($trimmed, 16));
                $publishTargetKey = array_search($publishTarget, $this->marketingPublishTargets(), true) ?: '';
                continue;
            }

            $briefLines[] = $line;
        }

        return [
            'channel' => $channel,
            'goal' => $goal,
            'publish_target' => $publishTarget,
            'publish_target_key' => $publishTargetKey,
            'brief' => trim(implode("\n", $briefLines)),
            'draft' => $draft,
        ];
    }

    private function serializeMarketingContentRequest(array $payload): string
    {
        $segments = [
            'Channel: ' . trim((string) ($payload['channel'] ?? '')),
            'Goal: ' . trim((string) ($payload['goal'] ?? '')),
            'Publish target: ' . trim((string) ($payload['publish_target'] ?? '')),
            '',
            trim((string) ($payload['brief'] ?? '')),
        ];

        $description = trim(implode("\n", $segments));
        $draft = trim((string) ($payload['draft'] ?? ''));

        if ($draft !== '') {
            $description .= "\n\n--- GENERATED DRAFT ---\n\n" . $draft;
        }

        return $description;
    }

    private function generateMarketingDraft(Founder $founder, string $title, array $contentRequest): string
    {
        $company = $founder->company;
        $intelligence = $company?->intelligence;
        $companyName = trim((string) ($company?->company_name ?? 'your business'));
        $channel = strtolower(trim((string) ($contentRequest['channel'] ?? 'Content')));
        $goal = trim((string) ($contentRequest['goal'] ?? 'Drive momentum'));
        $brief = trim((string) ($contentRequest['brief'] ?? ''));
        $offer = trim((string) ($intelligence?->core_offer ?? 'offer'));
        $audience = trim((string) ($intelligence?->target_audience ?? 'your audience'));
        $voice = trim((string) ($intelligence?->brand_voice ?? 'clear, helpful, and direct'));

        return match ($channel) {
            'linkedin' => "Hook: {$title}\n\nIf you are {$audience}, this is for you.\n\nAt {$companyName}, we are focused on {$goal}. {$brief}\n\nRight now, the biggest unlock is our {$offer}. We are building it with a {$voice} tone so the message feels clear and useful instead of over-polished.\n\nIf this sounds relevant, reply with your biggest blocker and I will share the next step we would take.\n\nCTA: Reply \"interested\" or send me a DM.",
            'instagram' => "{$title}\n\n{$brief}\n\nWhat we are building at {$companyName} is designed for {$audience}.\n\nWhy it matters:\n- {$goal}\n- Better clarity around the {$offer}\n- Momentum without extra noise\n\nCTA: Comment \"guide\" and we will send the next step.\n\n#{$this->draftHashtag($companyName)} #buildinpublic #founderstory",
            'x / twitter', 'x' => "{$title}\n\nBuilding for {$audience} at {$companyName}.\n\nFocus right now: {$goal}.\n\n{$brief}\n\nThe real unlock is making the {$offer} easier to understand and easier to act on.\n\nIf you are working on something similar, what is your biggest bottleneck?",
            'email' => "Subject: {$title}\n\nHi,\n\nQuick update from {$companyName}.\n\nWe are focused on {$goal} this week.\n\n{$brief}\n\nThis matters because {$audience} usually needs a clearer path to the right {$offer}. We are shaping the message in a {$voice} tone so it feels practical and trustworthy.\n\nIf you want to see the next version before it goes live, reply to this email and I will send it over.\n\nBest,\n{$founder->full_name}",
            'blog' => "# {$title}\n\n## Why this matters\n{$brief}\n\n## Who this is for\nThis is written for {$audience}.\n\n## What we are seeing\nAt {$companyName}, we are currently focused on {$goal}. The strongest opportunity is packaging the {$offer} more clearly and helping people understand the next step faster.\n\n## What to do next\nStart with one clear offer, one clear problem, and one clear CTA. Then test the message in a {$voice} tone across your marketing surfaces.",
            'landing page' => "Hero headline: {$title}\nSubheadline: {$brief}\nPrimary CTA: Get started\n\nProblem section:\n{$audience} often struggle to move from interest to action because the offer is not specific enough.\n\nSolution section:\n{$companyName} helps people through a focused {$offer} experience designed around {$goal}.\n\nProof section:\nUse one founder story, one concrete outcome, and one clear next step.\n\nCTA section:\nReady to move forward? Start here.",
            default => "{$title}\n\nGoal: {$goal}\n\n{$brief}\n\nAudience: {$audience}\nOffer: {$offer}\nVoice: {$voice}\n\nCTA: Invite the audience to take one clear next step.",
        };
    }

    private function draftHashtag(string $companyName): string
    {
        return preg_replace('/[^A-Za-z0-9]/', '', ucwords($companyName)) ?: 'HatchersAI';
    }

    private function renderMarketingDraftPreview(string $title, array $contentRequest): array
    {
        $channel = strtolower(trim((string) ($contentRequest['channel'] ?? '')));
        $draft = trim((string) ($contentRequest['draft'] ?? ''));
        $goal = trim((string) ($contentRequest['goal'] ?? ''));
        $publishTarget = trim((string) ($contentRequest['publish_target'] ?? ''));

        if ($draft === '') {
            return [
                'headline' => 'Preview will appear here',
                'body' => 'Generate a starter draft to see how this content will look in the OS review flow.',
                'meta' => trim(($goal !== '' ? 'Goal: ' . $goal . ' · ' : '') . ($publishTarget !== '' ? 'Publish to: ' . $publishTarget : 'Draft not generated yet')),
            ];
        }

        $lines = preg_split("/\n+/", $draft) ?: [];
        $bodyLines = array_values(array_filter(array_map('trim', $lines), fn (string $line) => $line !== ''));

        return match ($channel) {
            'email' => [
                'headline' => $bodyLines[0] ?? ('Subject: ' . $title),
                'body' => implode("\n", array_slice($bodyLines, 1, 4)),
                'meta' => trim('Email preview' . ($publishTarget !== '' ? ' · Publish to: ' . $publishTarget : '')),
            ],
            'landing page' => [
                'headline' => $bodyLines[0] ?? $title,
                'body' => implode("\n", array_slice($bodyLines, 1, 5)),
                'meta' => trim('Landing page outline' . ($publishTarget !== '' ? ' · Publish to: ' . $publishTarget : '')),
            ],
            default => [
                'headline' => $title,
                'body' => implode("\n", array_slice($bodyLines, 0, 5)),
                'meta' => trim(ucfirst($channel !== '' ? $channel : 'content') . ' preview' . ($publishTarget !== '' ? ' · Publish to: ' . $publishTarget : '')),
            ],
        };
    }

    private function marketingPublishUrl(Founder $founder, WorkspaceLaunchService $workspaceLaunchService, string $target): string
    {
        return match ($target) {
            'website' => route('website'),
            'bazaar' => $workspaceLaunchService->buildLaunchUrl($founder, 'bazaar') ?? route('founder.marketing'),
            'servio' => $workspaceLaunchService->buildLaunchUrl($founder, 'servio') ?? route('founder.marketing'),
            'atlas' => $workspaceLaunchService->buildLaunchUrl($founder, 'atlas') ?? route('founder.marketing'),
            default => route('founder.marketing'),
        };
    }

    private function marketingPublishCtaLabel(string $target): string
    {
        return match ($target) {
            'website' => 'Open Website Workspace',
            'bazaar' => 'Open Bazaar',
            'servio' => 'Open Servio',
            default => 'Open Atlas',
        };
    }

    private function sanitizeAtlasWorkspaceTarget(string $target): string
    {
        $target = trim($target);
        if ($target === '' || !str_starts_with($target, '/')) {
            return '/dashboard';
        }

        foreach ([
            '/dashboard',
            '/company-intelligence',
            '/ai-chat',
            '/ai-chat-bots',
            '/ai-images',
            '/ai-images/campaign',
            '/ai-images/campaign-detail',
            '/ai-images/grid',
            '/ai-templates',
            '/all-images',
            '/all-documents',
            '/document',
        ] as $allowedPrefix) {
            if (str_starts_with($target, $allowedPrefix)) {
                return $target;
            }
        }

        return '/dashboard';
    }

    private function moveCampaignInAtlasSnapshot(Founder $founder, string $title, string $fromKey, string $toKey): void
    {
        $founder->loadMissing('moduleSnapshots');
        $snapshot = $founder->moduleSnapshots->firstWhere('module', 'atlas');
        if (!$snapshot) {
            return;
        }

        $payload = is_array($snapshot->payload_json) ? $snapshot->payload_json : [];
        $summary = is_array($payload['summary'] ?? null) ? $payload['summary'] : [];
        $from = is_array($summary[$fromKey] ?? null) ? $summary[$fromKey] : [];
        $to = is_array($summary[$toKey] ?? null) ? $summary[$toKey] : [];
        $matched = null;

        $from = array_values(array_filter($from, function (array $campaign) use ($title, &$matched) {
            $isMatch = strcasecmp(trim((string) ($campaign['title'] ?? '')), trim($title)) === 0;
            if ($isMatch) {
                $matched = $campaign;
            }

            return !$isMatch;
        }));

        if ($matched === null) {
            return;
        }

        $matched['updated_at'] = now()->toDateTimeString();
        $to = array_values(array_filter(
            $to,
            fn (array $campaign) => strcasecmp(trim((string) ($campaign['title'] ?? '')), trim($title)) !== 0
        ));
        array_unshift($to, $matched);

        $summary[$fromKey] = $from;
        $summary[$toKey] = $to;
        $payload['summary'] = $summary;
        $payload['updated_at'] = now()->toIso8601String();

        $snapshot->forceFill([
            'payload_json' => $payload,
            'snapshot_updated_at' => now(),
        ])->save();
    }

    private function findAtlasCampaign(Founder $founder, string $title): array
    {
        $founder->loadMissing('moduleSnapshots');
        $snapshot = $founder->moduleSnapshots->firstWhere('module', 'atlas');
        $payload = is_array($snapshot?->payload_json) ? $snapshot->payload_json : [];
        $summary = is_array($payload['summary'] ?? null) ? $payload['summary'] : [];
        $campaigns = array_merge(
            is_array($summary['recent_campaigns'] ?? null) ? $summary['recent_campaigns'] : [],
            is_array($summary['archived_campaigns'] ?? null) ? $summary['archived_campaigns'] : []
        );

        foreach ($campaigns as $campaign) {
            if (strcasecmp(trim((string) ($campaign['title'] ?? '')), trim($title)) === 0) {
                return is_array($campaign) ? $campaign : [];
            }
        }

        return [];
    }

    private function buildCompanyWebsiteUrl(Company $company, string $engine): string
    {
        $host = trim((string) $company->custom_domain);
        $host = trim(preg_replace('#^https?://#', '', $host) ?? $host, '/');
        $path = trim((string) ($company->website_path ?? ''), '/');
        $fallbackPath = $company->company_name ? str($company->company_name)->slug('-')->value() : 'your-business';

        if ($host === '') {
            $host = 'app.hatchers.ai';
        }

        if ($path === '') {
            $path = $fallbackPath;
        }

        return 'https://' . $host . ($path !== '' ? '/' . $path : '');
    }

    private function isOsHostedWebsiteUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $osHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        return $host !== '' && $osHost !== '' && $host === $osHost;
    }

    private function resolveWebsiteEngineForBusinessModel(string $engine, string $businessModel): string
    {
        $engine = strtolower(trim($engine));
        $businessModel = strtolower(trim($businessModel));

        if ($businessModel === 'service') {
            return 'servio';
        }

        if ($businessModel === 'product') {
            return 'bazaar';
        }

        return in_array($engine, ['bazaar', 'servio'], true) ? $engine : 'bazaar';
    }

    private function buildFounderSearchResults(Founder $founder, array $dashboard, string $query): array
    {
        if ($query === '') {
            return [];
        }

        $needle = mb_strtolower($query);
        $results = [];

        foreach ($dashboard['workspace']['task_center_entries'] ?? [] as $task) {
            if ($this->matchesSearch($needle, [$task['title'] ?? '', $task['description'] ?? '', $task['mentor_context'] ?? ''])) {
                $results[] = [
                    'type' => 'Task',
                    'title' => (string) ($task['title'] ?? 'Task'),
                    'description' => (string) ($task['description'] ?? ''),
                    'href' => route('founder.tasks'),
                ];
            }
        }

        foreach ($dashboard['workspace']['learning_plan_entries'] ?? [] as $lesson) {
            if ($this->matchesSearch($needle, [$lesson['title'] ?? '', $lesson['subtitle'] ?? '', $lesson['detail_description'] ?? ''])) {
                $results[] = [
                    'type' => 'Lesson',
                    'title' => (string) ($lesson['title'] ?? 'Lesson'),
                    'description' => (string) ($lesson['subtitle'] ?? ''),
                    'href' => route('founder.learning-plan'),
                ];
            }
        }

        foreach (($dashboard['atlas']['recent_campaigns'] ?? []) as $campaign) {
            if ($this->matchesSearch($needle, [$campaign['title'] ?? '', $campaign['description'] ?? ''])) {
                $results[] = [
                    'type' => 'Campaign',
                    'title' => (string) ($campaign['title'] ?? 'Campaign'),
                    'description' => (string) ($campaign['description'] ?? ''),
                    'href' => route('founder.marketing'),
                ];
            }
        }

        foreach ($this->commerceOffers($founder) as $offer) {
            if ($this->matchesSearch($needle, [$offer['title'] ?? '', $offer['description'] ?? '', $offer['type_label'] ?? ''])) {
                $results[] = [
                    'type' => (string) ($offer['type_label'] ?? 'Offer'),
                    'title' => (string) ($offer['title'] ?? 'Offer'),
                    'description' => (string) ($offer['description'] ?? ''),
                    'href' => route('founder.commerce'),
                ];
            }
        }

        foreach ($dashboard['activity_feed'] ?? [] as $activity) {
            if ($this->matchesSearch($needle, [$activity['message'] ?? '', $activity['module'] ?? '', $activity['updated_at'] ?? ''])) {
                $results[] = [
                    'type' => 'Activity',
                    'title' => (string) ($activity['message'] ?? 'Activity'),
                    'description' => (string) (($activity['module'] ?? 'OS') . ' · ' . ($activity['updated_at'] ?? '')),
                    'href' => route('founder.activity'),
                ];
            }
        }

        return array_slice($results, 0, 20);
    }

    private function buildFounderMediaAssets(Founder $founder, array $dashboard): array
    {
        $assets = [];

        foreach (($dashboard['atlas']['recent_campaigns'] ?? []) as $campaign) {
            $assets[] = [
                'type' => 'Campaign',
                'title' => (string) ($campaign['title'] ?? 'Campaign draft'),
                'description' => (string) ($campaign['description'] ?? 'Campaign asset'),
                'source' => 'Campaign Studio',
            ];
        }

        foreach ($this->marketingContentRequests($founder) as $request) {
            $assets[] = [
                'type' => 'Content Draft',
                'title' => (string) ($request['title'] ?? 'Draft'),
                'description' => (string) (($request['draft'] ?? '') !== '' ? mb_strimwidth((string) $request['draft'], 0, 110, '...') : ($request['summary'] ?? 'Queued content draft')),
                'source' => 'OS Marketing',
            ];
        }

        foreach ($this->commerceOffers($founder) as $offer) {
            $assets[] = [
                'type' => (string) ($offer['type_label'] ?? 'Offer'),
                'title' => (string) ($offer['title'] ?? 'Offer'),
                'description' => (string) ($offer['description'] ?? ''),
                'source' => (string) ($offer['engine_label'] ?? 'Commerce'),
            ];
        }

        return array_values($assets);
    }

    private function buildFounderAnalyticsWorkspace(array $dashboard): array
    {
        $metrics = $dashboard['metrics'] ?? [];
        $growth = $dashboard['growth'] ?? [];
        $execution = $dashboard['execution'] ?? [];
        $atlas = $dashboard['atlas'] ?? [];

        return [
            'headline_metrics' => [
                ['label' => 'Weekly progress', 'value' => (int) ($metrics['weekly_progress_percent'] ?? 0) . '%'],
                ['label' => 'Open tasks', 'value' => (int) ($metrics['open_tasks'] ?? 0)],
                ['label' => 'Revenue tracked', 'value' => ($metrics['currency'] ?? 'USD') . ' ' . number_format((float) ($metrics['gross_revenue'] ?? 0), 0)],
                ['label' => 'Orders + bookings', 'value' => (int) ($metrics['orders_bookings'] ?? 0)],
            ],
            'execution' => [
                ['label' => 'Completed tasks', 'value' => (int) ($execution['completed_tasks'] ?? 0)],
                ['label' => 'Open milestones', 'value' => (int) ($execution['open_milestones'] ?? 0)],
                ['label' => 'Completed milestones', 'value' => (int) ($execution['completed_milestones'] ?? 0)],
            ],
            'growth' => [
                ['label' => 'Products', 'value' => (int) ($growth['product_count'] ?? 0)],
                ['label' => 'Services', 'value' => (int) ($growth['service_count'] ?? 0)],
                ['label' => 'Customers', 'value' => (int) ($growth['customer_count'] ?? 0)],
                ['label' => 'Orders', 'value' => (int) ($growth['order_count'] ?? 0)],
                ['label' => 'Bookings', 'value' => (int) ($growth['booking_count'] ?? 0)],
            ],
            'marketing' => [
                ['label' => 'Recent campaigns', 'value' => count($atlas['recent_campaigns'] ?? [])],
                ['label' => 'Archived campaigns', 'value' => count($atlas['archived_campaigns'] ?? [])],
                ['label' => 'Primary growth goal', 'value' => (string) ($atlas['primary_growth_goal'] ?? 'Not set')],
            ],
        ];
    }

    private function automationTriggerOptions(): array
    {
        return [
            'new_lead' => 'When a new lead appears',
            'public_intro_lead' => 'When a public intro lead appears',
            'lead_follow_up_due' => 'When a lead follow-up becomes due',
            'task_blocked' => 'When a task stays blocked',
            'new_order' => 'When a new order arrives',
            'new_booking' => 'When a new booking arrives',
            'campaign_published' => 'When campaign content is published',
            'order_unpaid' => 'When an order stays unpaid',
            'booking_unscheduled' => 'When a booking has no schedule',
            'booking_unassigned' => 'When a booking has no assigned provider',
        ];
    }

    private function automationScopeOptions(): array
    {
        return [
            'os' => 'OS wide',
            'atlas' => 'Atlas',
            'bazaar' => 'Bazaar',
            'servio' => 'Servio',
            'lms' => 'LMS',
        ];
    }

    private function automationTemplates(): array
    {
        return [
            'new-public-intro-lead-reminder' => [
                'name' => 'New public intro follow-up',
                'trigger_type' => 'public_intro_lead',
                'module_scope' => 'os',
                'condition_summary' => 'If a visitor leaves their details on a promo, flyer, QR, or referral landing flow and the founder has not reached out yet.',
                'action_summary' => 'Flag the lead in First 100, recommend the first reply script, and prompt the founder to make contact the same day.',
                'delivery' => 'os_prompt',
            ],
            'lead-follow-up-due-reminder' => [
                'name' => 'Lead follow-up due',
                'trigger_type' => 'lead_follow_up_due',
                'module_scope' => 'os',
                'condition_summary' => 'If a lead has a scheduled follow-up time that is now due and the conversation is still active.',
                'action_summary' => 'Push the lead into the founder daily queue and prepare the right follow-up script for the current stage.',
                'delivery' => 'os_prompt',
            ],
            'unpaid-order-reminder' => [
                'name' => 'Unpaid order reminder',
                'trigger_type' => 'order_unpaid',
                'module_scope' => 'bazaar',
                'condition_summary' => 'If an order remains unpaid after the founder has scheduled fulfillment or marked it as processing.',
                'action_summary' => 'Send a founder prompt in the OS and prepare an email reminder for the customer with payment follow-up language.',
                'delivery' => 'email',
            ],
            'unscheduled-booking-reminder' => [
                'name' => 'Unscheduled booking reminder',
                'trigger_type' => 'booking_unscheduled',
                'module_scope' => 'servio',
                'condition_summary' => 'If a booking is still pending without a confirmed date or time.',
                'action_summary' => 'Flag it in the OS operational queue and prepare a customer update asking them to confirm the preferred slot.',
                'delivery' => 'email',
            ],
            'provider-assignment-reminder' => [
                'name' => 'Provider assignment reminder',
                'trigger_type' => 'booking_unassigned',
                'module_scope' => 'servio',
                'condition_summary' => 'If a booking is active but still has no assigned provider or staff member.',
                'action_summary' => 'Surface the booking in the OS queue and draft a customer message once a provider is assigned.',
                'delivery' => 'email',
            ],
        ];
    }

    private function syncStripePayoutAccountStatus(FounderPayoutAccount $account, OsStripeService $stripeService): array
    {
        if (trim((string) $account->stripe_account_id) === '') {
            return [
                'success' => false,
                'message' => 'No Stripe Connect account is linked yet.',
            ];
        }

        $remote = $stripeService->retrieveConnectedAccount((string) $account->stripe_account_id);
        if (!($remote['success'] ?? false)) {
            return $remote;
        }

        $account->forceFill([
            'stripe_onboarding_status' => !empty($remote['payouts_enabled']) ? 'complete' : (!empty($remote['details_submitted']) ? 'review' : 'pending'),
            'stripe_charges_enabled' => (bool) ($remote['charges_enabled'] ?? false),
            'stripe_payouts_enabled' => (bool) ($remote['payouts_enabled'] ?? false),
            'stripe_details_submitted_at' => !empty($remote['details_submitted']) && !$account->stripe_details_submitted_at ? now() : $account->stripe_details_submitted_at,
            'stripe_payouts_enabled_at' => !empty($remote['payouts_enabled']) && !$account->stripe_payouts_enabled_at ? now() : $account->stripe_payouts_enabled_at,
            'bank_currency' => (string) ($remote['default_currency'] ?? ($account->bank_currency ?: 'USD')),
            'bank_country' => (string) ($remote['country'] ?? ($account->bank_country ?: 'US')),
            'status' => !empty($remote['payouts_enabled']) ? 'active' : 'pending',
            'meta_json' => array_merge((array) ($account->meta_json ?? []), [
                'stripe_synced_at' => now()->toDateTimeString(),
            ]),
        ])->save();

        return [
            'success' => true,
            'message' => 'Stripe Connect account synced.',
        ];
    }

    private function mailDiagnostics(): array
    {
        $host = trim((string) config('mail.mailers.smtp.host', ''));
        $port = trim((string) config('mail.mailers.smtp.port', ''));
        $username = trim((string) config('mail.mailers.smtp.username', ''));
        $fromAddress = trim((string) config('mail.from.address', ''));
        $encryption = trim((string) config('mail.mailers.smtp.encryption', ''));

        return [
            'mailer' => (string) config('mail.default', 'smtp'),
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'from_address' => $fromAddress,
            'encryption' => $encryption,
            'configured' => $host !== '' && $port !== '' && $username !== '' && $fromAddress !== '',
        ];
    }

    private function matchesSearch(string $needle, array $fields): bool
    {
        foreach ($fields as $field) {
            if (mb_stripos((string) $field, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    private function resolveFounderSignupPlan(string $planCode): ?array
    {
        $plans = $this->founderSignupPlans();

        return $plans[$planCode] ?? null;
    }

    private function authVerificationDisabled(): bool
    {
        return (bool) config('app.disable_auth_verification', false);
    }

    private function issueEmailVerification(Founder $founder): void
    {
        $code = $this->generateVerificationCode();

        $founder->forceFill([
            'email_verification_token' => Hash::make($code),
            'email_verification_expires_at' => now()->addMinutes(20),
        ])->save();

        $this->sendFounderMail(
            $founder->email,
            'Verify your founder email',
            'emails.founder-email-verification',
            [
                'founder' => $founder,
                'code' => $code,
                'expiresAt' => now()->addMinutes(20),
            ]
        );
    }

    private function issueLoginVerification(Founder $founder, Request $request): void
    {
        $code = $this->generateVerificationCode();

        $founder->forceFill([
            'login_verification_token' => Hash::make($code),
            'login_verification_expires_at' => now()->addMinutes(15),
        ])->save();

        $request->session()->put('pending_login_founder_id', $founder->id);

        $this->sendFounderMail(
            $founder->email,
            'Your Hatchers Ai Business OS sign-in code',
            'emails.founder-login-verification',
            [
                'founder' => $founder,
                'code' => $code,
                'expiresAt' => now()->addMinutes(15),
            ]
        );
    }

    private function sendFounderMail(string $email, string $subject, string $view, array $data): void
    {
        try {
            Mail::send($view, $data, function ($message) use ($email, $subject): void {
                $message->to($email)->subject($subject);
            });
        } catch (Throwable $exception) {
            Log::error('Founder verification email failed to send.', [
                'email' => $email,
                'subject' => $subject,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function generateVerificationCode(): string
    {
        return (string) random_int(100000, 999999);
    }
}
