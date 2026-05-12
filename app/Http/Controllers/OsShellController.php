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
use App\Models\FounderWebsiteGenerationRun;
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
use App\Services\FounderAiMentorProgramService;
use App\Services\FounderDashboardService;
use App\Services\FounderNotificationService;
use App\Services\FounderRevenueOsService;
use App\Services\IdentitySyncService;
use App\Services\LmsIdentityBridgeService;
use App\Services\MentorDashboardService;
use App\Services\OpenAiClientService;
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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            'pageTitle' => 'Choose Your Hatchers AI OS Plan',
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
        ]);
    }

    public function login()
    {
        return view('os.login', [
            'pageTitle' => 'Hatchers AI OS Login',
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
            'companyIntelligenceWizard' => $this->companyIntelligenceWizardState($user),
            'chatOnboardingState' => $this->founderChatOnboardingState($user),
            'launchPlanState' => $this->founderLaunchPlanState($user),
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
    ): RedirectResponse|JsonResponse {
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
    ): RedirectResponse|JsonResponse {
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

    public function founderComingSoon(string $feature): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }
        if ($redirect = $this->ensureCompanyIntelligenceComplete($user)) {
            return $redirect;
        }

        $label = match (strtolower(trim($feature))) {
            'automations' => 'Automations',
            'affiliate-network' => 'Affiliate Network',
            'offer-engineering' => 'Offer Engineering',
            default => 'This area',
        };

        return redirect()
            ->route('dashboard.founder')
            ->with('info', $label . ' is coming soon in Hatchers AI OS.');
    }

    public function founderAutomations(FounderDashboardService $founderDashboardService)
    {
        return $this->founderComingSoon('automations');
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

        app(FounderAiMentorProgramService::class)->ensureFounderProgram(
            $user->loadMissing('company.intelligence', 'subscription', 'weeklyState', 'actionPlans')
        );

        $wizard = $this->companyIntelligenceWizardState($user, (string) $request->query('step', ''));

        return view('os.settings', [
            'pageTitle' => 'Company Intelligence',
            'dashboard' => $founderDashboardService->build($user),
            'intelligence' => $user->company?->intelligence,
            'latestIcpProfile' => $user->company?->icpProfiles()->latest()->first(),
            'businessModelOptions' => $this->founderBusinessModelOptions(),
            'stageOptions' => $this->founderStageOptions(),
            'verticalBlueprintOptions' => array_values($this->verticalBlueprintDefinitions()),
            'industryOptions' => $this->founderIndustryOptions(),
            'targetAudienceOptions' => $this->founderTargetAudienceOptions(),
            'brandVoiceOptions' => $this->founderBrandVoiceOptions(),
            'coreOfferOptions' => $this->founderCoreOfferOptions(),
            'growthGoalOptions' => $this->founderPrimaryGrowthGoalOptions(),
            'knownBlockerOptions' => $this->founderKnownBlockerOptions(),
            'wizard' => $wizard,
        ]);
    }

    public function founderUpdateSettings(Request $request, FounderModuleSyncService $founderModuleSyncService): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $step = (string) $request->input('current_step', 'basics');
        $embedMode = $request->boolean('os_embed');
        $stepRules = [
            'account' => [
                'username' => ['required', 'string', 'min:3', 'max:255', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('founders', 'username')->ignore($user->id)],
                'profile_avatar' => ['nullable', 'image', 'max:4096'],
                'current_password' => ['nullable', 'string'],
                'new_password' => ['nullable', 'string', 'min:8', 'confirmed'],
            ],
            'basics' => [
                'full_name' => ['required', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
                'company_name' => ['required', 'string', 'max:255'],
                'company_brief' => ['required', 'string', 'max:2000'],
                'business_model' => ['required', Rule::in(array_keys($this->founderBusinessModelOptions()))],
                'vertical_blueprint' => ['required', Rule::in(array_keys($this->verticalBlueprintDefinitions()))],
                'industry' => ['required', Rule::in($this->founderIndustryOptions())],
                'stage' => ['required', Rule::in(array_keys($this->founderStageOptions()))],
                'primary_city' => ['required', 'string', 'max:191'],
                'service_radius' => ['required', 'string', 'max:191'],
                'company_logo' => ['nullable', 'image', 'max:4096'],
            ],
            'audience' => [
                'target_audience' => ['required', Rule::in($this->founderTargetAudienceOptions())],
                'primary_icp_name' => ['required', 'string', 'max:255'],
                'ideal_customer_profile' => ['required', 'string', 'max:1000'],
                'problem_solved' => ['required', 'string', 'max:1000'],
                'pain_points' => ['required', 'string', 'max:1200'],
            ],
            'offer' => [
                'core_offer' => ['required', Rule::in($this->founderCoreOfferOptions())],
                'differentiators' => ['required', 'string', 'max:1000'],
                'objections' => ['required', 'string', 'max:1200'],
                'desired_outcomes' => ['required', 'string', 'max:1200'],
            ],
            'brand' => [
                'brand_voice' => ['required', Rule::in($this->founderBrandVoiceOptions())],
                'visual_style' => ['required', 'string', 'max:500'],
                'primary_growth_goal' => ['required', Rule::in($this->founderPrimaryGrowthGoalOptions())],
                'known_blockers' => ['required', Rule::in($this->founderKnownBlockerOptions())],
                'local_market_notes' => ['nullable', 'string', 'max:1200'],
            ],
        ];

        if (!array_key_exists($step, $stepRules)) {
            $step = 'basics';
        }

        $validated = $request->validate(array_merge([
            'current_step' => ['required', Rule::in(array_keys($stepRules))],
        ], $stepRules[$step]));

        if ($step === 'account') {
            $avatarPath = (string) ($user->avatar_path ?? '');
            if ($request->hasFile('profile_avatar')) {
                if ($avatarPath !== '' && Storage::disk('public')->exists($avatarPath)) {
                    Storage::disk('public')->delete($avatarPath);
                }
                $avatarPath = (string) $request->file('profile_avatar')->store('founder-avatars', 'public');
            }

            if (!empty($validated['new_password'])) {
                if (empty($validated['current_password']) || !Hash::check((string) $validated['current_password'], (string) $user->password)) {
                    return redirect()
                        ->route('founder.settings', array_filter([
                            'step' => 'account',
                            'os_embed' => $embedMode ? 1 : null,
                        ]))
                        ->withInput($request->except(['current_password', 'new_password', 'new_password_confirmation']))
                        ->with('error', 'Please enter your current password correctly before setting a new one.');
                }
            }

            $user->forceFill([
                'username' => (string) $validated['username'],
                'avatar_path' => $avatarPath !== '' ? $avatarPath : null,
            ]);

            if (!empty($validated['new_password'])) {
                $user->password = Hash::make((string) $validated['new_password']);
            }

            $user->save();

            return redirect()
                ->route('founder.settings', array_filter([
                    'step' => 'account',
                    'os_embed' => $embedMode ? 1 : null,
                ]))
                ->with('success', 'Your account settings have been updated.');
        }

        if ($step === 'basics') {
            $user->forceFill([
                'full_name' => (string) $validated['full_name'],
                'phone' => (string) ($validated['phone'] ?? ''),
            ])->save();
        }

        $company = $user->company;
        if ($step !== 'basics' && !$company) {
            return redirect()->route('founder.settings', array_filter([
                'step' => 'basics',
                'os_embed' => $embedMode ? 1 : null,
            ]))->with('error', 'Complete the company basics first.');
        }

        if (!$company) {
            $company = Company::create([
                'founder_id' => $user->id,
                'company_name' => (string) $validated['company_name'],
                'business_model' => (string) $validated['business_model'],
                'vertical_blueprint_id' => $this->upsertVerticalBlueprint((string) $validated['vertical_blueprint'])->id,
                'industry' => (string) $validated['industry'],
                'stage' => (string) $validated['stage'],
                'primary_city' => (string) $validated['primary_city'],
                'service_radius' => (string) $validated['service_radius'],
                'website_status' => 'not_started',
            ]);
        }

        if ($step === 'basics') {
            $blueprint = $this->upsertVerticalBlueprint((string) $validated['vertical_blueprint']);
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
                'vertical_blueprint_id' => $blueprint->id,
                'industry' => (string) $validated['industry'],
                'stage' => (string) $validated['stage'],
                'primary_city' => (string) $validated['primary_city'],
                'service_radius' => (string) $validated['service_radius'],
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
        if (array_key_exists('desired_outcomes', $validated)) {
            $intelligencePayload['buying_triggers'] = (string) $validated['desired_outcomes'];
        }

        CompanyIntelligence::updateOrCreate(
            ['company_id' => $company->id],
            array_merge($intelligencePayload, [
                'intelligence_updated_at' => now(),
            ])
        );

        $icpProfile = $company->icpProfiles()->latest()->first();
        $icpPayload = [
            'primary_icp_name' => (string) ($validated['primary_icp_name'] ?? $currentIntelligence?->primary_icp_name ?? $icpProfile?->primary_icp_name ?? ''),
            'pain_points_json' => $step === 'audience'
                ? $this->commaSeparatedValues((string) $validated['pain_points'])
                : (is_array($icpProfile?->pain_points_json) ? $icpProfile->pain_points_json : []),
            'desired_outcomes_json' => $step === 'offer'
                ? $this->commaSeparatedValues((string) $validated['desired_outcomes'])
                : (is_array($icpProfile?->desired_outcomes_json) ? $icpProfile->desired_outcomes_json : []),
            'buying_triggers_json' => $step === 'offer'
                ? $this->commaSeparatedValues((string) $validated['desired_outcomes'])
                : (is_array($icpProfile?->buying_triggers_json) ? $icpProfile->buying_triggers_json : []),
            'objections_json' => $step === 'offer'
                ? $this->commaSeparatedValues((string) $validated['objections'])
                : (is_array($icpProfile?->objections_json) ? $icpProfile->objections_json : []),
            'price_sensitivity' => (string) ($icpProfile?->price_sensitivity ?? 'unknown'),
            'primary_channels_json' => is_array($icpProfile?->primary_channels_json) && !empty($icpProfile->primary_channels_json)
                ? $icpProfile->primary_channels_json
                : ($company->verticalBlueprint?->default_channels_json ?? []),
            'local_area_focus_json' => array_values(array_filter([(string) ($company->primary_city ?? '')])),
            'language_style' => (string) ($validated['brand_voice'] ?? $currentIntelligence?->brand_voice ?? $icpProfile?->language_style ?? ''),
        ];

        FounderIcpProfile::updateOrCreate(
            ['founder_id' => $user->id, 'company_id' => $company->id],
            $icpPayload
        );

        if ($step === 'brand' && array_key_exists('primary_growth_goal', $validated)) {
            $company->forceFill([
                'primary_goal' => (string) $validated['primary_growth_goal'],
            ])->save();
        }

        $this->syncFounderBusinessContextModels($user, $company);

        $syncResult = $founderModuleSyncService->syncFounder($user->fresh(['company.intelligence', 'company.businessBrief', 'company.icpProfiles']), 'all');
        if (empty($syncResult['ok'])) {
            Log::warning('Founder settings saved but downstream founder sync had issues.', [
                'founder_id' => $user->id,
                'company_id' => $company->id,
                'message' => $syncResult['message'] ?? 'Founder sync failed.',
                'results' => $syncResult['results'] ?? [],
            ]);
        }

        $user->unsetRelation('company');
        $user->load('company.intelligence');
        $wizard = $this->companyIntelligenceWizardState($user);
        if ($wizard['is_complete']) {
            app(FounderAiMentorProgramService::class)->ensureFounderProgram(
                $user->loadMissing('subscription', 'weeklyState', 'actionPlans')
            );
        }
        $nextStep = $wizard['is_complete'] ? 'brand' : $wizard['current_step_key'];

        return redirect()
            ->route('founder.settings', array_filter([
                'step' => $nextStep,
                'os_embed' => $embedMode ? 1 : null,
            ]))
            ->with('success', $wizard['is_complete']
                ? 'Company Intelligence is complete and ready to power the rest of Hatchers OS.'
                : 'Saved. Continue to the next Company Intelligence step.');
    }

    private function companyIntelligenceWizardState(Founder $founder, string $requestedStep = ''): array
    {
        $company = $founder->company;
        $intelligence = $company?->intelligence;
        $latestIcpProfile = $company?->icpProfiles()->latest()->first();
        $painPoints = implode(', ', is_array($latestIcpProfile?->pain_points_json) ? $latestIcpProfile->pain_points_json : []);
        $desiredOutcomes = implode(', ', is_array($latestIcpProfile?->desired_outcomes_json) ? $latestIcpProfile->desired_outcomes_json : []);

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
                    'vertical_blueprint' => trim((string) ($company?->verticalBlueprint?->code ?? '')),
                    'industry' => trim((string) ($company?->industry ?? '')),
                    'stage' => trim((string) ($company?->stage ?? '')),
                    'primary_city' => trim((string) ($company?->primary_city ?? '')),
                    'service_radius' => trim((string) ($company?->service_radius ?? '')),
                ],
                'required' => ['full_name', 'company_name', 'company_brief', 'business_model', 'vertical_blueprint', 'industry', 'stage', 'primary_city', 'service_radius'],
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
                    'pain_points' => trim($painPoints),
                ],
                'required' => ['target_audience', 'primary_icp_name', 'ideal_customer_profile', 'problem_solved', 'pain_points'],
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
                    'desired_outcomes' => trim($desiredOutcomes !== '' ? $desiredOutcomes : (string) ($intelligence?->buying_triggers ?? '')),
                ],
                'required' => ['core_offer', 'differentiators', 'objections', 'desired_outcomes'],
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
        $embedMode = request()->boolean('os_embed');

        if ($wizard['is_complete']) {
            app(FounderAiMentorProgramService::class)->ensureFounderProgram(
                $founder->loadMissing('company.intelligence', 'subscription', 'weeklyState', 'actionPlans')
            );
            return null;
        }

        return redirect()
            ->route('founder.settings', array_filter([
                'step' => $wizard['current_step_key'],
                'os_embed' => $embedMode ? 1 : null,
            ]))
            ->with('error', 'Complete Company Intelligence before using the rest of Hatchers OS.');
    }

    private function syncFounderBusinessContextModels(Founder $founder, Company $company): void
    {
        $company->loadMissing('intelligence', 'verticalBlueprint', 'businessBrief', 'icpProfiles');

        if (!$company->vertical_blueprint_id) {
            $fallbackCode = $this->defaultVerticalBlueprintCodeForBusinessModel((string) ($company->business_model ?? 'service'));
            $blueprint = $this->upsertVerticalBlueprint($fallbackCode);
            $company->forceFill([
                'vertical_blueprint_id' => $blueprint->id,
            ])->save();
            $company->setRelation('verticalBlueprint', $blueprint);
        }

        $intelligence = $company->intelligence;
        $blueprint = $company->verticalBlueprint;
        $latestIcpProfile = $company->icpProfiles()->latest()->first();
        $painPoints = is_array($latestIcpProfile?->pain_points_json) ? $latestIcpProfile->pain_points_json : $this->newlineSeparatedValues((string) ($intelligence?->problem_solved ?? ''));
        $desiredOutcomes = is_array($latestIcpProfile?->desired_outcomes_json) && !empty($latestIcpProfile->desired_outcomes_json)
            ? $latestIcpProfile->desired_outcomes_json
            : $this->newlineSeparatedValues((string) ($intelligence?->buying_triggers ?? ''));
        $objections = is_array($latestIcpProfile?->objections_json) && !empty($latestIcpProfile->objections_json)
            ? $latestIcpProfile->objections_json
            : $this->newlineSeparatedValues((string) ($intelligence?->objections ?? ''));

        FounderBusinessBrief::updateOrCreate(
            ['founder_id' => $founder->id, 'company_id' => $company->id],
            [
                'vertical_blueprint_id' => $blueprint?->id,
                'business_name' => (string) ($company->company_name ?? $founder->full_name),
                'business_summary' => (string) ($company->company_brief ?? ''),
                'problem_solved' => (string) ($intelligence?->problem_solved ?? ''),
                'core_offer' => (string) ($intelligence?->core_offer ?? ''),
                'business_type_detail' => (string) ($blueprint?->name ?? ucfirst((string) ($company->business_model ?? 'Business'))),
                'location_city' => (string) ($company->primary_city ?? ''),
                'location_country' => (string) ($company->businessBrief?->location_country ?? $founder->country ?? ''),
                'service_radius' => (string) ($company->service_radius ?? ''),
                'delivery_scope' => (string) ($company->businessBrief?->delivery_scope ?? $company->service_radius ?? ''),
                'proof_points' => (string) ($intelligence?->differentiators ?? ''),
                'founder_story' => (string) ($company->company_brief ?? ''),
                'constraints_json' => is_array($company->businessBrief?->constraints_json) ? $company->businessBrief->constraints_json : [],
                'status' => 'captured',
            ]
        );

        FounderIcpProfile::updateOrCreate(
            ['founder_id' => $founder->id, 'company_id' => $company->id],
            [
                'primary_icp_name' => (string) ($intelligence?->primary_icp_name ?? ''),
                'pain_points_json' => $painPoints,
                'desired_outcomes_json' => $desiredOutcomes,
                'buying_triggers_json' => $desiredOutcomes,
                'objections_json' => $objections,
                'price_sensitivity' => (string) ($latestIcpProfile?->price_sensitivity ?? 'unknown'),
                'primary_channels_json' => is_array($latestIcpProfile?->primary_channels_json) && !empty($latestIcpProfile->primary_channels_json)
                    ? $latestIcpProfile->primary_channels_json
                    : ($blueprint?->default_channels_json ?? []),
                'local_area_focus_json' => array_values(array_filter([(string) ($company->primary_city ?? '')])),
                'language_style' => (string) ($intelligence?->brand_voice ?? ''),
            ]
        );
    }

    private function defaultVerticalBlueprintCodeForBusinessModel(string $businessModel): string
    {
        return match (strtolower(trim($businessModel))) {
            'product' => 'handmade-products',
            'hybrid' => 'tutoring-coaching',
            default => 'dog-walking',
        };
    }

    private function newlineSeparatedValues(string $value): array
    {
        return array_values(array_filter(array_map(
            static fn (string $item): string => trim($item),
            preg_split('/[\r\n,]+/', $value) ?: []
        )));
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

    public function generateWebsiteDraft(Request $request, WebsiteAutopilotService $websiteAutopilotService): RedirectResponse
    {
        /** @var \App\Models\Founder $founder */
        $founder = Auth::user();

        if ($founder->company) {
            $this->syncFounderBusinessContextModels($founder, $founder->company);
        }

        $result = $websiteAutopilotService->generate($founder);
        if (!($result['ok'] ?? false)) {
            return redirect()->route('website', array_filter([
                'os_embed' => $request->boolean('os_embed') ? 1 : null,
            ]))->with('error', (string) ($result['error'] ?? 'Hatchers OS could not generate the website draft yet.'));
        }

        return redirect()->route('website', array_filter([
            'os_embed' => $request->boolean('os_embed') ? 1 : null,
        ]))->with('success', 'Your first website draft is ready. Hatchers OS prefilled the site setup and built the first offer path for review.');
    }

    public function storeWebsiteBuildRequest(
        Request $request,
        WebsiteAutopilotService $websiteAutopilotService,
        FounderNotificationService $founderNotificationService,
        FounderModuleSyncService $founderModuleSyncService
    ): RedirectResponse {
        /** @var \App\Models\Founder $founder */
        $founder = Auth::user();
        if (!$founder->isFounder()) {
            return redirect()->route('dashboard');
        }

        if ($redirect = $this->ensureCompanyIntelligenceComplete($founder)) {
            return $redirect;
        }

        $validated = $request->validate([
            'website_goal' => ['nullable', 'string', 'max:255'],
            'primary_website_focus' => ['nullable', Rule::in(['auto', 'product', 'service'])],
            'primary_cta' => ['nullable', 'string', 'max:255'],
            'founder_story_notes' => ['nullable', 'string', 'max:2500'],
            'services_pricing_notes' => ['nullable', 'string', 'max:3000'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:120'],
            'whatsapp_number' => ['nullable', 'string', 'max:120'],
            'business_address' => ['nullable', 'string', 'max:500'],
            'business_hours' => ['nullable', 'string', 'max:500'],
            'offer_titles' => ['nullable', 'array'],
            'offer_titles.*' => ['nullable', 'string', 'max:180'],
            'offer_prices' => ['nullable', 'array'],
            'offer_prices.*' => ['nullable', 'string', 'max:120'],
            'offer_descriptions' => ['nullable', 'array'],
            'offer_descriptions.*' => ['nullable', 'string', 'max:500'],
            'trust_points' => ['nullable', 'array'],
            'trust_points.*' => ['nullable', 'string', 'max:255'],
            'faq_questions' => ['nullable', 'array'],
            'faq_questions.*' => ['nullable', 'string', 'max:255'],
            'page_sections' => ['nullable', 'array'],
            'page_sections.*' => ['nullable', 'string', 'max:120'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'tiktok_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'image_style' => ['nullable', 'string', 'max:120'],
            'image_mood' => ['nullable', 'string', 'max:120'],
            'image_subjects' => ['nullable', 'array'],
            'image_subjects.*' => ['nullable', 'string', 'max:120'],
            'avoid_visuals' => ['nullable', 'array'],
            'avoid_visuals.*' => ['nullable', 'string', 'max:120'],
            'special_requests' => ['nullable', 'string', 'max:1500'],
            // Legacy compatibility while older sessions/forms may still post these.
            'social_links' => ['nullable', 'string', 'max:1200'],
            'must_include_pages' => ['nullable', 'string', 'max:1200'],
            'offer_items' => ['nullable', 'string', 'max:4000'],
            'faq_points' => ['nullable', 'string', 'max:2500'],
            'proof_points' => ['nullable', 'string', 'max:2500'],
            'image_preferences' => ['nullable', 'string', 'max:1500'],
        ]);

        try {

            $founder->loadMissing('company.intelligence', 'businessBrief', 'icpProfiles', 'actionPlans');
            $company = $founder->company;
            if (!$company) {
                return redirect()->route('founder.settings', array_filter([
                    'step' => 'basics',
                    'os_embed' => $request->boolean('os_embed') ? 1 : null,
                ]))->with('error', 'Complete the company basics before building the website.');
            }

        $this->syncFounderBusinessContextModels($founder, $company);
        $existingBrief = $founder->businessBrief ?: $company->businessBrief;
        $autoWebsiteGoal = trim((string) ($validated['website_goal'] ?? ''));
        if ($autoWebsiteGoal === '') {
            $autoWebsiteGoal = trim((string) ($company->intelligence?->primary_growth_goal ?? ''));
        }
        if ($autoWebsiteGoal === '') {
            $autoWebsiteGoal = 'Get more customers and create the clearest first offer.';
        }
        $founderStoryNotes = trim((string) ($validated['founder_story_notes'] ?? ''));
        $servicesPricingNotes = trim((string) ($validated['services_pricing_notes'] ?? ''));
        $specialRequests = trim((string) ($validated['special_requests'] ?? ''));

        $brief = FounderBusinessBrief::updateOrCreate(
            ['founder_id' => $founder->id, 'company_id' => $company->id],
            [
                'vertical_blueprint_id' => $company->vertical_blueprint_id,
                'business_name' => (string) ($company->company_name ?? $founder->full_name),
                'business_summary' => (string) ($company->company_brief ?? ''),
                'problem_solved' => (string) ($company->intelligence?->problem_solved ?? ''),
                'core_offer' => (string) ($company->intelligence?->core_offer ?? ''),
                'business_type_detail' => (string) ($company->verticalBlueprint?->name ?? ''),
                'location_city' => (string) ($company->primary_city ?? ''),
                'location_country' => (string) ($existingBrief?->location_country ?? ''),
                'service_radius' => (string) ($company->service_radius ?? ''),
                'delivery_scope' => (string) ($existingBrief?->delivery_scope ?? $company->service_radius ?? ''),
                'proof_points' => (string) ($existingBrief?->proof_points ?? ''),
                'founder_story' => $founderStoryNotes !== ''
                    ? $founderStoryNotes
                    : (string) ($existingBrief?->founder_story ?? $company->company_brief ?? ''),
                'status' => 'captured',
            ]
        );

        $constraints = is_array($brief->constraints_json) ? $brief->constraints_json : [];
        $offerCards = $this->normalizeWebsiteBuildOfferCards(
            (array) ($validated['offer_titles'] ?? []),
            (array) ($validated['offer_prices'] ?? []),
            (array) ($validated['offer_descriptions'] ?? [])
        );
        $trustPoints = $this->normalizeWebsiteBuildList((array) ($validated['trust_points'] ?? []));
        $faqQuestions = $this->normalizeWebsiteBuildList((array) ($validated['faq_questions'] ?? []));
        $pageSections = $this->normalizeWebsiteBuildPageSections((array) ($validated['page_sections'] ?? []));
        $socialProfiles = $this->normalizeWebsiteBuildSocialProfiles($validated);
        $imageDirection = $this->normalizeWebsiteBuildImageDirection($validated);

        $constraints['website_build'] = [
            'website_goal' => $autoWebsiteGoal,
            'primary_website_focus' => trim((string) ($validated['primary_website_focus'] ?? 'auto')),
            'primary_cta' => trim((string) ($validated['primary_cta'] ?? '')),
            'founder_story_notes' => $founderStoryNotes,
            'services_pricing_notes' => $servicesPricingNotes,
            'contact_email' => trim((string) ($validated['contact_email'] ?? '')),
            'contact_phone' => trim((string) ($validated['contact_phone'] ?? '')),
            'whatsapp_number' => trim((string) ($validated['whatsapp_number'] ?? '')),
            'business_address' => trim((string) ($validated['business_address'] ?? '')),
            'business_hours' => trim((string) ($validated['business_hours'] ?? '')),
            'offer_cards' => $offerCards,
            'trust_points_list' => $trustPoints,
            'faq_questions_list' => $faqQuestions,
            'page_sections' => $pageSections,
            'social_profiles' => $socialProfiles,
            'image_direction' => $imageDirection,
            // Legacy text summaries kept so existing draft code remains backward-compatible.
            'social_links' => $this->websiteBuildSocialProfilesToText($socialProfiles, trim((string) ($validated['social_links'] ?? ''))),
            'must_include_pages' => $this->websiteBuildPageSectionsToText($pageSections, trim((string) ($validated['must_include_pages'] ?? ''))),
            'offer_items' => $this->websiteBuildOfferCardsToText($offerCards, trim((string) ($validated['offer_items'] ?? ''))),
            'faq_points' => $this->websiteBuildListToText($faqQuestions, trim((string) ($validated['faq_points'] ?? ''))),
            'proof_points' => $this->websiteBuildListToText($trustPoints, trim((string) ($validated['proof_points'] ?? ''))),
            'image_preferences' => $this->websiteBuildImageDirectionToText($imageDirection, trim((string) ($validated['image_preferences'] ?? ''))),
            'special_requests' => $specialRequests,
            'updated_at' => now()->toDateTimeString(),
        ];

        $brief->forceFill([
            'constraints_json' => $constraints,
            'business_summary' => $servicesPricingNotes !== ''
                ? trim((string) ($brief->business_summary ?: $company->company_brief) . "\n\nService and pricing notes:\n" . $servicesPricingNotes)
                : (string) ($brief->business_summary ?? $company->company_brief ?? ''),
            'proof_points' => $this->websiteBuildListToText($trustPoints, (string) ($brief->proof_points ?? '')),
        ])->save();

        $engineLabel = $this->websiteBuildEngineLabel($company, (string) ($validated['primary_website_focus'] ?? 'auto'));
        $isImprovementPass = $company->websiteGenerationRuns()->exists()
            || in_array((string) ($company->website_status ?? ''), ['live', 'in_progress'], true)
            || in_array((string) ($company->website_generation_status ?? ''), ['published', 'ready_for_review', 'in_progress'], true);
        $this->startFounderWebsiteBuildPipeline($founder, $founderNotificationService, $engineLabel, $isImprovementPass);

            return redirect()
                ->route('website', array_filter([
                    'stage' => 'build',
                    'os_embed' => $request->boolean('os_embed') ? 1 : null,
                ]))
                ->with('success', $isImprovementPass
                    ? 'We are reviewing your latest inputs and improving the live website now. We will notify you as soon as the refreshed version is ready.'
                    : 'We are building and publishing your website now. We will notify you as soon as it is live and ready to edit in Servio.');
        } catch (Throwable $e) {
            Log::error('Website build request submission failed before generation started.', [
                'founder_id' => $founder->id,
                'company_id' => $founder->company_id,
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'We could not start the website build just yet. Your notes are safe, and we already tightened this flow so it fails gracefully while we finish the last fixes.');
        }
    }

    private function startFounderWebsiteBuildPipeline(
        Founder $founder,
        FounderNotificationService $founderNotificationService,
        string $engineLabel,
        bool $isImprovementPass
    ): void {
        $company = $founder->company;
        if ($company) {
            $company->forceFill([
                'website_generation_status' => 'in_progress',
                'website_status' => 'in_progress',
            ])->save();
        }

        $founderNotificationService->websiteBuildStarted($founder, $engineLabel, $isImprovementPass);
        $founderId = (int) $founder->id;

        app()->terminating(function () use ($founderId): void {
            $this->executeFounderWebsiteBuildPipeline($founderId);
        });
    }

    private function executeFounderWebsiteBuildPipeline(int $founderId): void
    {
        try {
            /** @var Founder|null $backgroundFounder */
            $backgroundFounder = Founder::query()->find($founderId);
            if (!$backgroundFounder) {
                return;
            }

            /** @var WebsiteAutopilotService $websiteAutopilotService */
            $websiteAutopilotService = app(WebsiteAutopilotService::class);
            /** @var WebsiteProvisioningService $websiteProvisioningService */
            $websiteProvisioningService = app(WebsiteProvisioningService::class);
            /** @var FounderNotificationService $founderNotificationService */
            $founderNotificationService = app(FounderNotificationService::class);
            /** @var FounderModuleSyncService $founderModuleSyncService */
            $founderModuleSyncService = app(FounderModuleSyncService::class);

            $backgroundFounder = $backgroundFounder->fresh([
                'company.verticalBlueprint',
                'company.intelligence',
                'company.websiteGenerationRuns',
                'businessBrief',
                'icpProfiles',
                'actionPlans',
            ]);
            if (!$backgroundFounder) {
                return;
            }

            $engineTarget = strtolower(trim((string) ($backgroundFounder->company?->website_engine ?? '')));
            if (!in_array($engineTarget, ['servio', 'bazaar'], true)) {
                $engineTarget = strtolower(trim((string) ($backgroundFounder->company?->business_model ?? ''))) === 'product'
                    ? 'bazaar'
                    : 'servio';
            }

            $syncResult = $founderModuleSyncService->syncFounder($backgroundFounder, $engineTarget);
            if (empty($syncResult['ok'])) {
                $backgroundFounder->company?->forceFill([
                    'website_generation_status' => 'queued',
                    'website_status' => 'not_started',
                ])->save();

                $founderNotificationService->websiteBuildFailed(
                    $backgroundFounder,
                    (string) ($syncResult['message'] ?? 'We could not provision your founder account in the website engine yet.')
                );

                Log::warning('Website build stopped because founder engine sync failed.', [
                    'founder_id' => $founderId,
                    'engine_target' => $engineTarget,
                    'message' => (string) ($syncResult['message'] ?? 'Founder engine sync failed.'),
                    'results' => $syncResult['results'] ?? [],
                ]);

                return;
            }

            $result = $websiteAutopilotService->generate($backgroundFounder->fresh([
                'company.verticalBlueprint',
                'company.intelligence',
                'company.websiteGenerationRuns',
                'businessBrief',
                'icpProfiles',
                'actionPlans',
            ]));

            $freshFounder = $backgroundFounder->fresh(['company']);
            $freshCompany = $freshFounder?->company;

            if (!($result['ok'] ?? false)) {
                if ($freshCompany) {
                    $freshCompany->forceFill([
                        'website_generation_status' => 'queued',
                        'website_status' => 'not_started',
                    ])->save();
                }

                if ($freshFounder) {
                    $founderNotificationService->websiteBuildFailed(
                        $freshFounder,
                        (string) ($result['error'] ?? 'We could not finish the website build yet. Please try again after the latest fixes.')
                    );
                }

                Log::warning('Website build failed during after-response generation.', [
                    'founder_id' => $founderId,
                    'error' => (string) ($result['error'] ?? 'Unknown website generation error.'),
                ]);

                return;
            }

            $publishResult = $websiteProvisioningService->publishWebsite(
                $freshFounder,
                (string) ($freshCompany?->website_engine ?? 'servio')
            );

            if (!($publishResult['ok'] ?? false) && ($publishResult['bridge_status'] ?? null) !== 'pending') {
                if ($freshCompany) {
                    $freshCompany->forceFill([
                        'website_generation_status' => 'queued',
                        'website_status' => 'not_started',
                    ])->save();
                }

                $founderNotificationService->websiteBuildFailed(
                    $freshFounder,
                    (string) ($publishResult['error'] ?? 'We built the website draft, but could not publish it yet.')
                );

                return;
            }

            if ($freshCompany) {
                $this->syncCompanyWebsitePathFromEnginePublicUrl(
                    $freshCompany,
                    (string) ($publishResult['public_url'] ?? ''),
                    (string) ($freshCompany->website_engine ?? 'servio')
                );
                $freshCompany->forceFill([
                    'website_status' => 'live',
                    'website_generation_status' => 'published',
                    'launch_stage' => 'website_live',
                ])->save();
            }

            $websiteUrl = (string) ($freshCompany?->website_url ?: ('https://app.hatchers.ai/' . ltrim((string) ($freshCompany?->website_path ?? ''), '/')));
            $engineAppKey = strtolower((string) ($freshCompany?->website_engine ?? 'servio')) === 'bazaar' ? 'bazaar-engine' : 'servio-engine';
            $founderNotificationService->websiteReady($freshFounder, $engineAppKey, $websiteUrl);
        } catch (Throwable $e) {
            $crashedFounder = Founder::query()->with('company')->find($founderId);
            if ($crashedFounder?->company) {
                $crashedFounder->company->forceFill([
                    'website_generation_status' => 'queued',
                    'website_status' => 'not_started',
                ])->save();
            }

            if ($crashedFounder) {
                $failureMessage = trim((string) $e->getMessage());
                if ($failureMessage === '') {
                    $failureMessage = 'The website build ran into a technical issue before it could finish.';
                } else {
                    $failureMessage = 'The website build ran into a technical issue: ' . $failureMessage;
                }

                app(FounderNotificationService::class)->websiteBuildFailed(
                    $crashedFounder,
                    $failureMessage
                );
            }

            Log::error('Website build crashed during after-response generation.', [
                'founder_id' => $founderId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'exception' => get_class($e),
                'trace_preview' => collect($e->getTrace())
                    ->take(5)
                    ->map(fn (array $frame): array => [
                        'file' => $frame['file'] ?? null,
                        'line' => $frame['line'] ?? null,
                        'function' => $frame['function'] ?? null,
                        'class' => $frame['class'] ?? null,
                    ])
                    ->values()
                    ->all(),
            ]);
        }
    }

    public function founderApplyLaunchSystem(Request $request, WebsiteAutopilotService $websiteAutopilotService): RedirectResponse
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
            return redirect()->route('website', array_filter([
                'os_embed' => $request->boolean('os_embed') ? 1 : null,
            ]))->with('error', 'Generate a website draft before locking the launch system.');
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

        return redirect()->route('website', array_filter([
            'os_embed' => $request->boolean('os_embed') ? 1 : null,
        ]))->with('success', 'Your website funnel is now locked into the active launch system.');
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
            return redirect()->route('website', array_filter([
                'os_embed' => $request->boolean('os_embed') ? 1 : null,
            ]))->with('error', (string) ($result['error'] ?? 'Hatchers OS could not regenerate that draft block.'));
        }

        return redirect()->route('website', array_filter([
            'os_embed' => $request->boolean('os_embed') ? 1 : null,
        ]))->with('success', ucfirst(str_replace('_', ' ', (string) $validated['block'])) . ' regenerated inside your launch system draft.');
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
            $this->syncCompanyWebsitePathFromEnginePublicUrl($company, (string) ($result['public_url'] ?? ''), $validated['website_engine']);
            $company->save();
        }

        $successMessage = ($result['bridge_status'] ?? null) === 'pending'
            ? 'Website setup saved in Hatchers OS. Your public OS website path is ready now, and engine sync can be completed later.'
            : 'Website setup saved. Hatchers OS updated the underlying website engine for you.';

        return redirect()->route('website', array_filter([
            'os_embed' => $request->boolean('os_embed') ? 1 : null,
        ]))->with('success', $successMessage);
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
            $this->syncCompanyWebsitePathFromEnginePublicUrl($company, (string) ($result['public_url'] ?? ''), $validated['website_engine']);
            $company->save();
        }

        $successMessage = ($result['bridge_status'] ?? null) === 'pending'
            ? 'Website published on Hatchers OS. Your app.hatchers.ai public site is live now, while engine bridge sync remains optional.'
            : 'Website published from Hatchers OS.';

        return redirect()->route('website', array_filter([
            'os_embed' => $request->boolean('os_embed') ? 1 : null,
        ]))->with('success', $successMessage);
    }

    public function publicWebsite(string $websitePath, PublicWebsiteService $publicWebsiteService)
    {
        $company = $this->resolvePublicWebsiteCompany($websitePath);
        if (!$company) {
            $this->logPublicWebsiteResolutionFailure($websitePath, 'page');
            abort(404);
        }

        $site = $publicWebsiteService->build($company);
        $this->logPublicWebsiteResolutionSuccess($websitePath, $company, $site, 'page');

        if (($site['uses_engine_storefront'] ?? false) && (!empty($site['engine_proxy_url']) || !empty($site['engine_proxy_candidates'] ?? []))) {
            $canonicalPath = trim((string) ($site['engine_vendor_slug'] ?? $site['path'] ?? $websitePath), '/');
            $requestedPath = trim($websitePath, '/');

            if ($canonicalPath !== '' && $canonicalPath !== $requestedPath) {
                $canonicalUrl = rtrim((string) config('app.url'), '/') . '/' . $canonicalPath;
                if (request()->getQueryString()) {
                    $canonicalUrl .= '?' . request()->getQueryString();
                }

                return redirect()->away($canonicalUrl);
            }

            return $this->proxyEngineStorefront($company, '', request(), $publicWebsiteService);
        }

        $site['uses_engine_storefront'] = false;
        $site['source_storefront_url'] = '';
        $site['engine_proxy_url'] = '';

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
            $this->logPublicWebsiteResolutionFailure($websiteRoot, 'proxy', $proxyPath);
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

    public function storeOnboarding(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan_code' => ['required', Rule::in(array_keys($this->founderSignupPlans()))],
            'email' => ['required', 'email', 'max:255', 'unique:founders,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'plan_code.required' => 'Please choose a founder plan before signing up.',
        ]);

        $plan = $this->founderSignupPlans()[$validated['plan_code']] ?? null;
        if ($plan === null) {
            return redirect()->route('plans')->with('error', 'Please choose a valid founder plan.');
        }

        $emailLocal = Str::before((string) $validated['email'], '@');
        $username = $this->uniqueFounderUsernameFromSeed($emailLocal);
        $displayName = $this->placeholderFounderNameFromEmail((string) $validated['email']);
        $companyName = $this->placeholderCompanyNameFromEmail((string) $validated['email']);
        $defaultBusinessModel = 'service';
        $defaultBlueprint = $this->upsertVerticalBlueprint($this->defaultVerticalBlueprintCodeForBusinessModel('service'));

        try {
            DB::transaction(function () use ($validated, $plan, $username, $displayName, $companyName, $defaultBlueprint, $defaultBusinessModel) {
                $founder = Founder::create(
                    [
                        'username' => $username,
                        'email' => $validated['email'],
                        'full_name' => $displayName,
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
                        'company_name' => $companyName,
                        'business_model' => $defaultBusinessModel,
                        'vertical_blueprint_id' => $defaultBlueprint->id,
                        'industry' => '',
                        'stage' => 'idea',
                        'primary_city' => '',
                        'service_radius' => '',
                        'primary_goal' => '',
                        'launch_stage' => 'chat_onboarding_pending',
                        'website_generation_status' => 'not_started',
                        'website_status' => 'not_started',
                        'company_brief' => 'Founder account created. Chat onboarding still needs to capture the business context.',
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
                        'business_model' => $defaultBusinessModel,
                        'summary_updated_at' => now(),
                    ]
                );

                FounderWeeklyState::updateOrCreate(
                    ['founder_id' => $founder->id],
                    [
                        'weekly_focus' => 'Complete the founder chat so Hatchers can build your first launch plan.',
                        'state_updated_at' => now(),
                    ]
                );
                FounderActionPlan::firstOrCreate(
                    [
                        'founder_id' => $founder->id,
                        'context' => 'task',
                        'title' => 'Complete your founder chat',
                    ],
                    [
                        'description' => 'Answer the onboarding questions in Hatchers OS so we can generate your launch plan, company intelligence, and first tasks.',
                        'platform' => 'os',
                        'priority' => 100,
                        'status' => 'pending',
                        'cta_label' => 'Open Dashboard',
                        'cta_url' => '/dashboard',
                        'metadata_json' => ['source' => 'minimal_signup'],
                    ]
                );
            });
        } catch (Throwable $exception) {
            $reference = 'SG-' . now()->format('YmdHis');
            Log::error('Founder signup failed unexpectedly.', [
                'reference' => $reference,
                'email' => $validated['email'] ?? '',
                'username' => $username ?? '',
                'message' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['signup' => $this->founderSignupFailureMessage($exception, $reference)]);
        }

        $founder = Founder::query()->with('company')->where('email', $validated['email'])->first();

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
                'Your founder workspace is ready under the ' . $plan['name'] . ' plan. Log in and Hatchers will collect the rest through chat.'
            );
        }

        if ($founder) {
            $this->issueEmailVerification($founder);
            $request->session()->put('verification_email', $founder->email);
        }

        return redirect()->route('verification.email.notice', ['email' => $validated['email']])->with(
            'success',
            'Your founder workspace has been created under the ' . $plan['name'] . ' plan. We sent a verification code to your email before you can log in and continue in chat.'
        );
    }

    public function completeChatOnboarding(
        Request $request,
        AtlasIntelligenceService $atlas,
        FounderModuleSyncService $founderModuleSyncService,
        OsAssistantTimelineService $timeline
    ): JsonResponse
    {
        /** @var \App\Models\Founder $founder */
        $founder = Auth::user();
        if (!$founder->isFounder()) {
            return response()->json([
                'success' => false,
                'error' => 'Only founder accounts can complete onboarding here.',
            ], 403);
        }

        $validated = $request->validate([
            'q1' => ['required', 'string', 'max:1200'],
            'q2' => ['required', 'string', 'max:1200'],
            'q3' => ['required', 'string', 'max:1200'],
            'q4' => ['nullable', 'string', 'max:1200'],
            'budget_strategy' => ['required', Rule::in(['organic', 'paid', 'unsure'])],
            'time_commitment' => ['required', Rule::in(['low', 'mid', 'high'])],
        ]);

        $company = $founder->company;
        if (!$company) {
            return response()->json([
                'success' => false,
                'error' => 'Your founder workspace is missing a company record. Please refresh and try again.',
            ], 422);
        }

        $signupProfile = $this->chatOnboardingValidatedProfile($founder, $validated);
        $deducedProfile = $this->deduceFounderSignupProfile($signupProfile);
        if (trim((string) ($deducedProfile['primary_city'] ?? '')) === '') {
            $deducedProfile['primary_city'] = 'Online / to be confirmed';
        }
        if (trim((string) ($deducedProfile['service_radius'] ?? '')) === '') {
            $deducedProfile['service_radius'] = 'Flexible service area';
        }
        $blueprint = $this->upsertVerticalBlueprint((string) $deducedProfile['vertical_blueprint']);
        $launchPlan = $this->buildFounderLaunchPlan($signupProfile, $deducedProfile, $blueprint);

        DB::transaction(function () use ($founder, $company, $signupProfile, $deducedProfile, $blueprint, $launchPlan) {
            $projectName = trim((string) ($signupProfile['company_name'] ?? ''));

            $founder->forceFill([
                'full_name' => $projectName !== '' && trim((string) $founder->full_name) === $this->placeholderFounderNameFromEmail((string) $founder->email)
                    ? $this->placeholderFounderNameFromEmail((string) $founder->email)
                    : (string) $founder->full_name,
            ])->save();

            $company->forceFill([
                'company_name' => $projectName !== '' ? $projectName : (string) $company->company_name,
                'business_model' => (string) $signupProfile['business_model'],
                'vertical_blueprint_id' => $blueprint->id,
                'industry' => (string) ($deducedProfile['industry'] ?? ''),
                'stage' => (string) $signupProfile['stage'],
                'primary_city' => (string) ($deducedProfile['primary_city'] ?? ''),
                'service_radius' => (string) ($deducedProfile['service_radius'] ?? ''),
                'primary_goal' => (string) $signupProfile['primary_growth_goal'],
                'launch_stage' => 'launch_plan_ready',
                'website_generation_status' => 'queued',
                'website_status' => 'not_started',
                'company_brief' => (string) $signupProfile['company_description'],
            ])->save();

            FounderBusinessBrief::updateOrCreate(
                ['founder_id' => $founder->id, 'company_id' => $company->id],
                [
                    'vertical_blueprint_id' => $blueprint->id,
                    'business_name' => (string) $company->company_name,
                    'business_summary' => (string) $signupProfile['company_description'],
                    'problem_solved' => (string) ($deducedProfile['problem_solved'] ?? ''),
                    'core_offer' => (string) ($deducedProfile['core_offer'] ?? ''),
                    'business_type_detail' => (string) $blueprint->name,
                    'location_city' => (string) ($deducedProfile['primary_city'] ?? ''),
                    'location_country' => (string) ($deducedProfile['location_country'] ?? ''),
                    'service_radius' => (string) ($deducedProfile['service_radius'] ?? ''),
                    'delivery_scope' => (string) ($deducedProfile['service_radius'] ?? ''),
                    'proof_points' => (string) ($deducedProfile['differentiators'] ?? ''),
                    'founder_story' => (string) $signupProfile['company_description'],
                    'constraints_json' => [
                        'known_blockers' => (string) $signupProfile['known_blockers'],
                        'budget_strategy' => (string) $signupProfile['budget_strategy'],
                        'time_commitment' => (string) $signupProfile['time_commitment'],
                        'hours_per_week' => (int) $launchPlan['pace']['hours_per_week'],
                    ],
                    'status' => 'captured',
                ]
            );

            FounderIcpProfile::updateOrCreate(
                ['founder_id' => $founder->id, 'company_id' => $company->id],
                [
                    'primary_icp_name' => (string) ($deducedProfile['primary_icp_name'] ?? 'Ideal customer'),
                    'pain_points_json' => array_values((array) ($deducedProfile['pain_points'] ?? [])),
                    'desired_outcomes_json' => array_values((array) ($deducedProfile['desired_outcomes'] ?? [])),
                    'buying_triggers_json' => array_values((array) ($deducedProfile['desired_outcomes'] ?? [])),
                    'objections_json' => array_values((array) ($deducedProfile['objections'] ?? [])),
                    'price_sensitivity' => 'unknown',
                    'primary_channels_json' => $launchPlan['channels'],
                    'local_area_focus_json' => array_values(array_filter([(string) ($deducedProfile['primary_city'] ?? '')])),
                    'language_style' => (string) ($deducedProfile['brand_voice'] ?? ''),
                ]
            );

            CompanyIntelligence::updateOrCreate(
                ['company_id' => $company->id],
                [
                    'target_audience' => (string) ($deducedProfile['target_audience'] ?? ''),
                    'ideal_customer_profile' => (string) $signupProfile['ideal_customer_profile'],
                    'primary_icp_name' => (string) ($deducedProfile['primary_icp_name'] ?? ''),
                    'problem_solved' => (string) ($deducedProfile['problem_solved'] ?? ''),
                    'brand_voice' => (string) ($deducedProfile['brand_voice'] ?? ''),
                    'differentiators' => (string) ($deducedProfile['differentiators'] ?? ''),
                    'content_goals' => implode(', ', array_values((array) ($launchPlan['north_star_metrics'] ?? []))),
                    'visual_style' => (string) $launchPlan['visual_direction'],
                    'core_offer' => (string) ($deducedProfile['core_offer'] ?? ''),
                    'primary_growth_goal' => (string) $signupProfile['primary_growth_goal'],
                    'known_blockers' => (string) $signupProfile['known_blockers'],
                    'objections' => implode(', ', array_values((array) ($deducedProfile['objections'] ?? []))),
                    'buying_triggers' => implode(', ', array_values((array) ($deducedProfile['desired_outcomes'] ?? []))),
                    'local_market_notes' => $this->localMarketNotesFromProfile($deducedProfile),
                    'last_summary' => (string) $launchPlan['summary'],
                    'intelligence_updated_at' => now(),
                ]
            );

            FounderLaunchSystem::updateOrCreate(
                ['founder_id' => $founder->id, 'company_id' => $company->id],
                [
                    'vertical_blueprint_id' => $blueprint->id,
                    'status' => 'ready',
                    'selected_engine' => (string) $blueprint->engine,
                    'launch_strategy_json' => $launchPlan,
                    'funnel_blocks_json' => $launchPlan['assets'],
                    'offer_stack_json' => $launchPlan['offer_stack'],
                    'acquisition_system_json' => [
                        'channel_strategy' => $launchPlan['channel_strategy'],
                        'budget_strategy' => $signupProfile['budget_strategy'],
                        'channels' => $launchPlan['channels'],
                    ],
                    'applied_at' => now(),
                    'last_reviewed_at' => now(),
                ]
            );

            FounderActionPlan::query()
                ->where('founder_id', $founder->id)
                ->where('context', 'task')
                ->where(function ($query) {
                    $query->where('title', 'Complete your founder chat')
                        ->orWhere('metadata_json->source', 'launch_chat');
                })
                ->delete();

            foreach ($launchPlan['tasks'] as $index => $task) {
                FounderActionPlan::create([
                    'founder_id' => $founder->id,
                    'title' => (string) $task['title'],
                    'description' => (string) $task['description'],
                    'platform' => (string) $task['platform'],
                    'context' => 'task',
                    'priority' => max(45, 95 - ($index * 3)),
                    'status' => 'pending',
                    'cta_label' => (string) $task['cta_label'],
                    'cta_url' => (string) $task['cta_url'],
                    'available_on' => $task['available_on'] ?? now()->toDateString(),
                    'metadata_json' => [
                        'source' => 'launch_chat',
                        'milestone' => (string) $task['milestone'],
                        'north_star_metric' => (string) $task['north_star_metric'],
                    ],
                ]);
            }

            FounderWeeklyState::updateOrCreate(
                ['founder_id' => $founder->id],
                [
                    'weekly_focus' => (string) $launchPlan['weekly_focus'],
                    'open_tasks' => count($launchPlan['tasks']),
                    'weekly_progress_percent' => 0,
                    'state_updated_at' => now(),
                ]
            );

            $this->syncFounderBusinessContextModels($founder, $company);
        });

        $company->refresh();
        $atlasPayload = $this->atlasFounderOnboardingPayload($signupProfile, $deducedProfile, $blueprint);
        $atlas->syncFounderOnboarding($founder->fresh(), $company->fresh(), $atlasPayload);
        $founderModuleSyncService->syncFounder($founder->fresh(['company.intelligence', 'company.businessBrief', 'company.icpProfiles']), 'all');

        $onboardingSummary = implode("\n", array_filter([
            'Business: ' . (string) $signupProfile['company_description'],
            'Ideal customer: ' . (string) $signupProfile['ideal_customer_profile'],
            'Stage: ' . (string) $signupProfile['stage'],
            'Model: ' . (string) $signupProfile['business_model'],
            'Goal: ' . (string) $signupProfile['primary_growth_goal'],
            'Blocker: ' . (string) $signupProfile['known_blockers'],
            'Growth approach: ' . (string) $signupProfile['budget_strategy'],
            'Time commitment: ' . (string) $signupProfile['time_commitment'],
        ]));
        $websitePrompt = $this->assistantWebsiteBuildPromptFromPreferences(
            $this->assistantStoredOperatingPreferences($founder, $company)
        );
        $reply = 'Your launch plan is ready. Hatchers mapped the milestones, tasks, channels, and website path based on your answers. ' . $websitePrompt;
        $timeline->record(
            $founder,
            null,
            'prototype_dashboard',
            $onboardingSummary,
            $reply,
            [[
                'cta' => 'Build My Website',
                'os_workspace_key' => 'website',
                'os_href' => route('website'),
            ]]
        );

        return response()->json([
            'success' => true,
            'reply' => $reply,
            'launch_plan' => $launchPlan,
            'ask_for_website_build' => true,
            'website_prompt' => $websitePrompt,
        ]);
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
        OsAssistantActionService $actionService,
        OsAssistantTimelineService $timeline,
        FounderNotificationService $founderNotificationService
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
                if (($actionResult['action_type'] ?? '') === 'website_build_start') {
                    $actionResult = $this->executeAssistantWebsiteBuildAction(
                        $founder,
                        $founderNotificationService,
                        $actionResult
                    );
                } elseif (($actionResult['action_type'] ?? '') === 'task_batch_create') {
                    $actionResult = $this->executeAssistantTaskBatchAction(
                        $founder,
                        $actionResult
                    );
                } elseif (($actionResult['action_type'] ?? '') === 'launch_plan_refine') {
                    $actionResult = $this->executeAssistantLaunchPlanRefineAction(
                        $founder,
                        $actionResult
                    );
                } elseif (($actionResult['action_type'] ?? '') === 'launch_plan_refine_campaign') {
                    $actionResult = $this->executeAssistantLaunchPlanRefineCampaignAction(
                        $founder,
                        $actionResult,
                        $actionService
                    );
                } elseif (($actionResult['action_type'] ?? '') === 'company_field_website_refresh') {
                    $actionResult = $this->executeAssistantCompanyRefreshWebsiteAction(
                        $founder,
                        $founderNotificationService,
                        $actionResult
                    );
                } elseif (($actionResult['action_type'] ?? '') === 'campaign_format_choose') {
                    $actionResult = $this->executeAssistantCampaignFormatAction(
                        $founder,
                        $actionResult
                    );
                } elseif (($actionResult['action_type'] ?? '') === 'task_breakdown_next') {
                    $actionResult = $this->executeAssistantTaskBreakdownAction(
                        $founder,
                        $actionResult
                    );
                } elseif (($actionResult['action_type'] ?? '') === 'task_tighten_next') {
                    $actionResult = $this->executeAssistantTaskTightenAction(
                        $founder,
                        $actionResult
                    );
                } elseif (
                    ($actionResult['action_type'] ?? '') === 'platform_record_update' &&
                    ($actionResult['platform'] ?? '') === 'lms' &&
                    ($actionResult['category'] ?? '') === 'task' &&
                    ($actionResult['field'] ?? '') === 'status' &&
                    in_array((string) ($actionResult['value'] ?? ''), ['completed', 'complete', 'done'], true)
                ) {
                    $actionResult = $this->executeAssistantTaskCloseGuidanceAction(
                        $founder,
                        $actionResult
                    );
                }

                $founder->refresh();
                $founder->load([
                    'company.intelligence',
                    'subscription',
                    'weeklyState',
                    'commercialSummary',
                    'moduleSnapshots',
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
                'auto_open_action' => !empty($actionResult['executed']) && !empty($mappedActions) ? $mappedActions[0] : null,
                'refresh' => !empty($actionResult['executed']),
                'thread_key' => (string) $thread->thread_key,
            ]);
        }

        $result = $this->localAssistantReply(
            $founder,
            $message,
            $currentPage,
            $threadKey !== '' ? $threadKey : null
        );

        if (!$result['ok']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'The Hatchers assistant could not respond right now.',
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
        $this->persistAssistantStrategicMemory($founder, $thread);

        return response()->json([
            'success' => true,
            'reply' => $result['reply'] ?? '',
            'actions' => $mappedActions,
            'auto_open_action' => null,
            'refresh' => false,
            'thread_key' => (string) $thread->thread_key,
        ]);
    }

    private function localAssistantReply(
        Founder $founder,
        string $message,
        string $currentPage,
        ?string $threadKey = null
    ): array {
        /** @var OpenAiClientService $openAi */
        $openAi = app(OpenAiClientService::class);
        if (!$openAi->hasApiKey()) {
            return [
                'ok' => false,
                'error' => 'The Hatchers assistant is not configured yet.',
            ];
        }

        $founder->loadMissing([
            'company.intelligence',
            'company.businessBrief',
            'company.icpProfiles',
            'weeklyState',
            'commercialSummary',
            'actionPlans',
            'businessBrief',
            'icpProfiles',
            'launchSystems',
            'moduleSnapshots',
        ]);

        $company = $founder->company;
        $intelligence = $company?->intelligence;
        $businessBrief = $founder->businessBrief ?? $company?->businessBrief;
        $icpProfile = $founder->icpProfiles->sortByDesc('updated_at')->first() ?? $company?->icpProfiles?->sortByDesc('updated_at')->first();
        $launchSystem = $founder->launchSystems->sortByDesc(fn (FounderLaunchSystem $system) => $system->last_reviewed_at ?? $system->updated_at)->first();
        $weeklyState = $founder->weeklyState;
        $commercialSummary = $founder->commercialSummary;
        $recentTasks = $founder->actionPlans()
            ->where('context', 'task')
            ->orderBy('available_on')
            ->orderByDesc('priority')
            ->limit(5)
            ->get(['title', 'description', 'status', 'platform'])
            ->map(fn (FounderActionPlan $task): array => [
                'title' => (string) $task->title,
                'description' => (string) $task->description,
                'status' => (string) ($task->status ?? 'pending'),
                'platform' => (string) ($task->platform ?? ''),
            ])
            ->values()
            ->all();

        $thread = FounderConversationThread::query()
            ->where('founder_id', $founder->id)
            ->whereIn('source_channel', ['os_assistant', 'atlas_assistant'])
            ->when(trim((string) $threadKey) !== '', fn ($query) => $query->where('thread_key', (string) $threadKey))
            ->orderByDesc('last_activity_at')
            ->orderByDesc('updated_at')
            ->first();

        $memoryMessages = collect(is_array($thread?->meta_json['messages'] ?? null) ? $thread->meta_json['messages'] : [])
            ->take(-6)
            ->map(fn (array $entry): array => [
                'type' => (string) ($entry['type'] ?? ''),
                'text' => (string) ($entry['text'] ?? ''),
            ])
            ->values()
            ->all();
        $threadMeta = is_array($thread?->meta_json ?? null) ? $thread->meta_json : [];
        $durableAssistantMemory = is_array($businessBrief?->constraints_json['assistant_memory'] ?? null)
            ? $businessBrief->constraints_json['assistant_memory']
            : [];

        $prompt = [
            'task' => 'Act as the local Hatchers founder mentor and operating agent. Coach the founder like a practical growth mentor, ask clarifying questions when needed, and only suggest actions that the OS can actually help with.',
            'mentor_brief' => $this->localAssistantMentorBrief(),
            'instructions' => [
                'Return valid JSON only.',
                'Be conversational, warm, commercially sharp, and founder-friendly.',
                'Blend Alex Hormozi style offer/value clarity with Sabri Suby style direct-response, lead generation, and conversion thinking.',
                'Prioritise organic customer acquisition, clearer offers, stronger proof, better follow-up, and practical execution before fancy tactics.',
                'Use the founder context, company intelligence, launch plan, task state, commercial metrics, and prior conversation memory before giving advice.',
                'When important information is missing, ask one targeted question instead of guessing.',
                'When the founder asks for help using a Hatchers tool, explain how to use the relevant tool and suggest the right next OS action.',
                'When onboarding fields are incomplete, guide the founder to complete the missing business details and refer back to the onboarding answers already captured.',
                'Do not mention APIs, internal system fallbacks, or remote app dependencies.',
                'Keep the answer compact but useful: direct answer first, then concrete next moves.',
                'If appropriate, suggest up to three workspace actions the founder can take next.',
            ],
            'founder' => [
                'name' => (string) $founder->full_name,
                'username' => (string) $founder->username,
                'email' => (string) $founder->email,
            ],
            'company' => [
                'company_name' => (string) ($company?->company_name ?? ''),
                'business_model' => (string) ($company?->business_model ?? ''),
                'industry' => (string) ($company?->industry ?? ''),
                'stage' => (string) ($company?->stage ?? ''),
                'primary_city' => (string) ($company?->primary_city ?? ''),
                'service_radius' => (string) ($company?->service_radius ?? ''),
                'company_brief' => (string) ($company?->company_brief ?? ''),
                'problem_solved' => (string) ($intelligence?->problem_solved ?? ''),
                'target_audience' => (string) ($intelligence?->target_audience ?? ''),
                'ideal_customer_profile' => (string) ($intelligence?->ideal_customer_profile ?? ''),
                'primary_icp_name' => (string) ($intelligence?->primary_icp_name ?? ''),
                'brand_voice' => (string) ($intelligence?->brand_voice ?? ''),
                'core_offer' => (string) ($intelligence?->core_offer ?? ''),
                'differentiators' => (string) ($intelligence?->differentiators ?? ''),
                'primary_growth_goal' => (string) ($intelligence?->primary_growth_goal ?? ''),
                'known_blockers' => (string) ($intelligence?->known_blockers ?? ''),
                'objections' => (string) ($intelligence?->objections ?? ''),
                'buying_triggers' => (string) ($intelligence?->buying_triggers ?? ''),
                'local_market_notes' => (string) ($intelligence?->local_market_notes ?? ''),
                'last_summary' => (string) ($intelligence?->last_summary ?? ''),
            ],
            'onboarding_memory' => [
                'founder_story' => (string) ($businessBrief?->founder_story ?? ''),
                'business_summary' => (string) ($businessBrief?->business_summary ?? ''),
                'problem_solved' => (string) ($businessBrief?->problem_solved ?? ''),
                'proof_points' => (string) ($businessBrief?->proof_points ?? ''),
                'constraints' => is_array($businessBrief?->constraints_json ?? null) ? $businessBrief->constraints_json : [],
                'assistant_memory' => $durableAssistantMemory,
                'icp_name' => (string) ($icpProfile?->primary_icp_name ?? ''),
                'pain_points' => array_values((array) ($icpProfile?->pain_points_json ?? [])),
                'desired_outcomes' => array_values((array) ($icpProfile?->desired_outcomes_json ?? [])),
                'objections' => array_values((array) ($icpProfile?->objections_json ?? [])),
                'primary_channels' => array_values((array) ($icpProfile?->primary_channels_json ?? [])),
            ],
            'launch_plan' => $this->localAssistantLaunchPlanSummary($launchSystem),
            'execution' => [
                'weekly_focus' => (string) ($weeklyState?->weekly_focus ?? ''),
                'open_tasks' => (int) ($weeklyState?->open_tasks ?? 0),
                'completed_tasks' => (int) ($weeklyState?->completed_tasks ?? 0),
                'weekly_progress_percent' => (int) ($weeklyState?->weekly_progress_percent ?? 0),
                'recent_tasks' => $recentTasks,
                'next_pending_task' => $this->assistantNextPendingTaskSummary($founder),
            ],
            'commercial' => [
                'business_model' => (string) ($commercialSummary?->business_model ?? ''),
                'product_count' => (int) ($commercialSummary?->product_count ?? 0),
                'service_count' => (int) ($commercialSummary?->service_count ?? 0),
                'order_count' => (int) ($commercialSummary?->order_count ?? 0),
                'booking_count' => (int) ($commercialSummary?->booking_count ?? 0),
                'customer_count' => (int) ($commercialSummary?->customer_count ?? 0),
                'gross_revenue' => (float) ($commercialSummary?->gross_revenue ?? 0),
                'currency' => (string) ($commercialSummary?->currency ?? 'USD'),
            ],
            'module_state' => $this->localAssistantModuleState($founder),
            'workspace_capabilities' => $this->localAssistantWorkspaceCapabilities(),
            'workspace_playbooks' => $this->localAssistantWorkspacePlaybooks(),
            'operating_guidance' => $this->localAssistantOperatingGuidance($durableAssistantMemory, $threadMeta),
            'current_page' => $currentPage,
            'conversation_memory' => $memoryMessages,
            'recurring_concerns' => $this->localAssistantRecurringConcerns($memoryMessages, $threadMeta, $durableAssistantMemory),
            'strategic_decisions' => $this->localAssistantStrategicDecisions($threadMeta, $durableAssistantMemory),
            'tool_interests' => $this->localAssistantToolInterests($threadMeta, $durableAssistantMemory),
            'user_message' => $message,
            'response_schema' => [
                'reply' => 'string',
                'actions' => [
                    [
                        'title' => 'string',
                        'reason' => 'string',
                        'cta' => 'string',
                        'platform' => 'string',
                    ],
                ],
            ],
        ];

        $response = $openAi->requestJsonObject(
            'You are Hatchers, the founder mentor inside Hatchers AI OS. Reply with clear, founder-friendly advice and optional workspace actions in valid JSON only.',
            json_encode($prompt, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'chat_model',
            'gpt-5.5',
            null,
            40
        );

        $reply = trim((string) ($response['reply'] ?? ''));
        if ($reply === '') {
            Log::warning('Local Hatchers assistant returned no structured reply.', [
                'founder_id' => $founder->id,
                'thread_key' => $threadKey,
                'current_page' => $currentPage,
                'model' => data_get($response, '_meta.model'),
                'status' => data_get($response, '_meta.status'),
                'error' => data_get($response, '_meta.error'),
            ]);

            $fallback = $this->localAssistantSlimFallbackReply($founder, $message, $currentPage);
            if ($fallback['ok']) {
                return $fallback;
            }

            $heuristicFallback = $this->localAssistantHeuristicFallbackReply($founder, $message, $currentPage);
            if ($heuristicFallback['ok']) {
                return $heuristicFallback;
            }

            return [
                'ok' => false,
                'error' => trim((string) data_get($response, '_meta.error', '')) !== ''
                    ? 'The Hatchers assistant hit a model issue while generating a reply. Please try again in a moment.'
                    : 'The Hatchers assistant could not generate a reply right now.',
            ];
        }

        return [
            'ok' => true,
            'reply' => $reply,
            'actions' => array_values(array_filter((array) ($response['actions'] ?? []), fn ($action) => is_array($action))),
        ];
    }

    private function localAssistantHeuristicFallbackReply(Founder $founder, string $message, string $currentPage): array
    {
        $founder->loadMissing([
            'company.intelligence',
            'actionPlans' => fn ($query) => $query->latest()->limit(6),
        ]);

        $company = $founder->company;
        $intelligence = $company?->intelligence;
        $nextTask = $this->assistantNextPendingTaskSummary($founder);
        $normalizedMessage = strtolower(trim($message));
        $companyName = trim((string) ($company?->company_name ?? 'your business'));
        $growthGoal = trim((string) ($intelligence?->primary_growth_goal ?? 'get more customers'));
        $blockers = trim((string) ($intelligence?->known_blockers ?? ''));

        if (in_array($normalizedMessage, ['hi', 'hello', 'hey', 'hello!', 'hey!'], true)) {
            $reply = "Hi {$founder->full_name}, I’m here and I’ve still got the context for {$companyName}. "
                . "Right now our focus is {$growthGoal}."
                . ($nextTask !== '' ? " The next best move is: {$nextTask}." : '')
                . " If you want, I can help you tighten the offer, improve the website, or break the next task into simpler steps.";

            return [
                'ok' => true,
                'reply' => $reply,
                'actions' => array_values(array_filter([
                    $nextTask !== '' ? [
                        'title' => 'Open Tasks',
                        'reason' => 'See the next recommended task.',
                        'cta' => 'Review tasks',
                        'platform' => 'tasks',
                    ] : null,
                    [
                        'title' => 'Improve Website',
                        'reason' => 'Review or rebuild the website draft.',
                        'cta' => 'Open website',
                        'platform' => 'website',
                    ],
                ])),
            ];
        }

        $reply = "I’m still here with your launch context for {$companyName}, but the live model reply failed just now. "
            . "Based on what I know, I’d keep the focus on {$growthGoal}."
            . ($blockers !== '' ? " The main blocker I’m tracking is {$blockers}." : '')
            . ($nextTask !== '' ? " The clearest next step is: {$nextTask}." : '')
            . " If you want, tell me whether you want help with the offer, the website, or the next task and I’ll guide you from there.";

        return [
            'ok' => true,
            'reply' => $reply,
            'actions' => [
                [
                    'title' => 'Open Tasks',
                    'reason' => 'Continue execution from the launch plan.',
                    'cta' => 'Go to tasks',
                    'platform' => 'tasks',
                ],
                [
                    'title' => 'Build My Website',
                    'reason' => 'Review or improve the website workspace.',
                    'cta' => 'Open website',
                    'platform' => 'website',
                ],
            ],
        ];
    }

    private function localAssistantSlimFallbackReply(Founder $founder, string $message, string $currentPage): array
    {
        /** @var OpenAiClientService $openAi */
        $openAi = app(OpenAiClientService::class);

        $founder->loadMissing([
            'company.intelligence',
            'weeklyState',
            'commercialSummary',
            'actionPlans',
        ]);

        $company = $founder->company;
        $intelligence = $company?->intelligence;
        $nextTask = $this->assistantNextPendingTaskSummary($founder);

        $slimPrompt = [
            'founder_name' => (string) $founder->full_name,
            'company_name' => (string) ($company?->company_name ?? ''),
            'business_model' => (string) ($company?->business_model ?? ''),
            'primary_growth_goal' => (string) ($intelligence?->primary_growth_goal ?? ''),
            'known_blockers' => (string) ($intelligence?->known_blockers ?? ''),
            'brand_voice' => (string) ($intelligence?->brand_voice ?? ''),
            'next_pending_task' => $nextTask,
            'current_page' => $currentPage,
            'user_message' => $message,
            'response_schema' => [
                'reply' => 'string',
                'actions' => [
                    [
                        'title' => 'string',
                        'reason' => 'string',
                        'cta' => 'string',
                        'platform' => 'string',
                    ],
                ],
            ],
        ];

        $response = $openAi->requestJsonObject(
            'You are Hatchers, a practical founder mentor. Reply in valid JSON only. Keep it compact, helpful, and grounded in the founder context.',
            json_encode($slimPrompt, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'chat_model',
            'gpt-5.5',
            null,
            30
        );

        $reply = trim((string) ($response['reply'] ?? ''));
        if ($reply === '') {
            Log::warning('Local Hatchers slim fallback also returned no reply.', [
                'founder_id' => $founder->id,
                'current_page' => $currentPage,
                'model' => data_get($response, '_meta.model'),
                'status' => data_get($response, '_meta.status'),
                'error' => data_get($response, '_meta.error'),
            ]);

            return [
                'ok' => false,
                'error' => 'The Hatchers assistant could not generate a reply right now.',
            ];
        }

        return [
            'ok' => true,
            'reply' => $reply,
            'actions' => array_values(array_filter((array) ($response['actions'] ?? []), fn ($action) => is_array($action))),
        ];
    }

    private function localAssistantMentorBrief(): array
    {
        return [
            'mentor_style' => 'Practical founder mentor who combines Alex Hormozi value/offer thinking with Sabri Suby direct-response, lead-generation, and conversion methodology.',
            'principles' => [
                'Make the offer clearer, stronger, and more desirable before adding complexity.',
                'Prefer organic lead generation, outreach, proof, follow-up, and compelling calls to action before paid growth unless the founder asks for paid.',
                'Use simple, commercial language. Focus on customer pain, promised outcome, proof, objections, risk reduction, and urgency.',
                'Always anchor advice to real execution inside Hatchers OS tools, launch plan, and current founder constraints.',
                'Ask good diagnostic questions when the offer, customer, or channel is unclear.',
            ],
        ];
    }

    private function localAssistantLaunchPlanSummary(?FounderLaunchSystem $launchSystem): array
    {
        $strategy = is_array($launchSystem?->launch_strategy_json ?? null) ? $launchSystem->launch_strategy_json : [];

        return [
            'status' => (string) ($launchSystem?->status ?? ''),
            'engine' => (string) ($launchSystem?->selected_engine ?? ''),
            'summary' => (string) ($strategy['summary'] ?? ''),
            'weekly_focus' => (string) ($strategy['weekly_focus'] ?? ''),
            'north_star_metrics' => array_values((array) ($strategy['north_star_metrics'] ?? [])),
            'channels' => array_values((array) ($strategy['channels'] ?? [])),
            'milestones' => collect((array) ($strategy['milestones'] ?? []))
                ->map(fn ($milestone) => is_array($milestone) ? [
                    'title' => (string) ($milestone['title'] ?? ''),
                    'objective' => (string) ($milestone['objective'] ?? ''),
                    'metric' => (string) ($milestone['metric'] ?? ''),
                ] : null)
                ->filter()
                ->values()
                ->all(),
            'offer_stack' => array_values((array) ($strategy['offer_stack'] ?? $launchSystem?->offer_stack_json ?? [])),
            'assets' => collect((array) ($strategy['assets'] ?? []))
                ->map(fn ($asset) => is_array($asset) ? [
                    'type' => (string) ($asset['type'] ?? ''),
                    'name' => (string) ($asset['name'] ?? ''),
                    'purpose' => (string) ($asset['purpose'] ?? ''),
                ] : $asset)
                ->values()
                ->all(),
        ];
    }

    private function localAssistantModuleState(Founder $founder): array
    {
        return $founder->moduleSnapshots
            ->sortByDesc('snapshot_updated_at')
            ->take(5)
            ->map(function ($snapshot): array {
                $payload = is_array($snapshot->payload_json ?? null) ? $snapshot->payload_json : [];
                $summary = is_array($payload['summary'] ?? null) ? $payload['summary'] : [];
                $keyCounts = is_array($payload['key_counts'] ?? null) ? $payload['key_counts'] : [];

                return [
                    'module' => (string) ($snapshot->module ?? ''),
                    'readiness_score' => (int) ($snapshot->readiness_score ?? 0),
                    'updated_at' => optional($snapshot->snapshot_updated_at)->toIso8601String(),
                    'summary' => [
                        'headline' => (string) ($summary['headline'] ?? ''),
                        'status' => (string) ($summary['status'] ?? ''),
                        'gross_revenue' => (float) ($summary['gross_revenue'] ?? 0),
                        'currency' => (string) ($summary['currency'] ?? 'USD'),
                        'order_count' => (int) ($keyCounts['order_count'] ?? 0),
                        'booking_count' => (int) ($keyCounts['booking_count'] ?? 0),
                        'customer_count' => (int) ($keyCounts['customer_count'] ?? 0),
                    ],
                ];
            })
            ->values()
            ->all();
    }

    private function localAssistantWorkspaceCapabilities(): array
    {
        return [
            'dashboard_chat' => 'Guide the founder conversationally, capture onboarding answers, refine plans, and trigger real OS actions.',
            'build_my_website' => 'Build, rebuild, review, and publish the founder website. Useful when the founder wants messaging, offers, FAQs, blogs, and website structure improved.',
            'campaign_studio' => 'Generate campaign briefs and help the founder choose between posts, grids, and content sequences for execution.',
            'servio' => 'Manage service business websites, bookings, offers, and operational service flows.',
            'bazaar' => 'Manage products, storefront operations, catalog structure, orders, and product commerce.',
            'tasks' => 'Create, break down, tighten, complete, and reorder tasks tied to the launch plan.',
            'company_intelligence' => 'Update target audience, ICP, offer, blockers, proof, objections, visual direction, and market notes.',
            'launch_plan' => 'Refine milestones, execution order, weekly focus, and task generation based on founder constraints and goals.',
            'search' => 'Find relevant workspaces or records when the founder is unsure where something lives.',
            'notifications' => 'Review recent OS updates, website build status, and milestone alerts.',
        ];
    }

    private function localAssistantWorkspacePlaybooks(): array
    {
        return [
            'campaign_studio' => [
                'when_to_use' => 'Use when the founder wants to generate demand through organic campaign content, choose a campaign angle, or turn an offer into posts, grids, or content sequences.',
                'how_to_guide' => [
                    'Clarify the offer, audience, and promised outcome first.',
                    'Recommend one campaign angle tied to pain, proof, or transformation.',
                    'Help the founder choose between post, grid, or content sequence depending on depth and urgency.',
                    'If the founder is ready, suggest opening Campaign Studio and creating the brief.',
                ],
            ],
            'build_my_website' => [
                'when_to_use' => 'Use when the founder needs a clearer offer, better messaging, stronger website copy, or wants the system to build/rebuild/publish the website.',
                'how_to_guide' => [
                    'Tighten the offer and ICP before triggering a rebuild when possible.',
                    'Tell the founder which parts of the website are most likely to improve, like hero, offer stack, FAQs, services, or founder story.',
                    'Ask for approval before building or rebuilding the website.',
                ],
            ],
            'company_intelligence' => [
                'when_to_use' => 'Use when the founder is unclear on target audience, ICP, offer, differentiators, blockers, brand voice, or local market.',
                'how_to_guide' => [
                    'Ask direct diagnostic questions.',
                    'Push toward specific customer pain, desired outcome, objections, and proof.',
                    'Encourage concrete language the rest of the OS can reuse.',
                ],
            ],
            'tasks' => [
                'when_to_use' => 'Use when the founder needs the next best move, wants to break down work, or wants to close/reopen tasks.',
                'how_to_guide' => [
                    'Tie each task to a milestone and commercial outcome.',
                    'Break fuzzy tasks into narrow execution steps.',
                    'Suggest the next task after one is completed.',
                ],
            ],
            'servio_bazaar' => [
                'when_to_use' => 'Use when the founder asks about service bookings, product sales, storefronts, orders, or revenue flow.',
                'how_to_guide' => [
                    'Use Servio for service-led businesses and Bazaar for product-led businesses.',
                    'Reference current bookings, orders, and revenue from the commercial summary when advising.',
                    'Prioritise fixing the core offer and conversion path before adding complexity.',
                ],
            ],
        ];
    }

    private function localAssistantRecurringConcerns(array $memoryMessages, array $threadMeta = [], array $durableAssistantMemory = []): array
    {
        $derived = collect($memoryMessages)
            ->filter(fn (array $entry): bool => ($entry['type'] ?? '') === 'user')
            ->pluck('text')
            ->map(function (string $text): string {
                $normalized = trim(preg_replace('/\s+/', ' ', strip_tags($text)));
                if ($normalized === '') {
                    return '';
                }

                return Str::limit($normalized, 140, '...');
            })
            ->filter()
            ->unique()
            ->take(-3)
            ->values();

        return collect(array_values((array) ($durableAssistantMemory['recurring_concerns'] ?? [])))
            ->merge(array_values((array) ($threadMeta['recurring_concerns'] ?? [])))
            ->merge($derived)
            ->filter()
            ->unique()
            ->take(-6)
            ->values()
            ->all();
    }

    private function localAssistantStrategicDecisions(array $threadMeta = [], array $durableAssistantMemory = []): array
    {
        return collect(array_values((array) ($durableAssistantMemory['strategic_decisions'] ?? [])))
            ->merge(array_values((array) ($threadMeta['strategic_decisions'] ?? [])))
            ->filter()
            ->unique()
            ->take(-8)
            ->values()
            ->all();
    }

    private function localAssistantToolInterests(array $threadMeta = [], array $durableAssistantMemory = []): array
    {
        return collect(array_values((array) ($durableAssistantMemory['tool_interests'] ?? [])))
            ->merge(array_values((array) ($threadMeta['tool_interests'] ?? [])))
            ->filter()
            ->unique()
            ->take(-8)
            ->values()
            ->all();
    }

    private function localAssistantOperatingGuidance(array $durableAssistantMemory = [], array $threadMeta = []): array
    {
        $decisions = $this->localAssistantStrategicDecisions($threadMeta, $durableAssistantMemory);
        $concerns = $this->localAssistantRecurringConcerns([], $threadMeta, $durableAssistantMemory);
        $toolInterests = $this->localAssistantToolInterests($threadMeta, $durableAssistantMemory);

        $guidance = [
            'respect_previous_decisions' => $decisions,
            'watch_for_recurring_concerns' => $concerns,
            'lean_into_tool_interests' => $toolInterests,
            'rules' => [],
        ];

        $joinedDecisions = Str::lower(implode(' | ', $decisions));
        $joinedConcerns = Str::lower(implode(' | ', $concerns));

        if (str_contains($joinedDecisions, 'organic')) {
            $guidance['rules'][] = 'Keep the advice focused on organic outreach, direct-response messaging, proof, follow-up, and no paid-ad assumption unless the founder changes direction.';
        }

        if (str_contains($joinedDecisions, 'paid')) {
            $guidance['rules'][] = 'The founder has shown interest in paid growth, but still keep the offer, audience, and conversion path clear before recommending spend.';
        }

        if (str_contains($joinedConcerns, 'offer clarity')) {
            $guidance['rules'][] = 'Offer clarity is a recurring concern. Push toward a simpler, sharper, more decision-ready offer before suggesting more channels.';
        }

        if (str_contains($joinedConcerns, 'website quality')) {
            $guidance['rules'][] = 'Website quality keeps coming up. When relevant, steer toward Build My Website with concrete suggestions for what to improve.';
        }

        if (in_array('campaign_studio', $toolInterests, true)) {
            $guidance['rules'][] = 'The founder keeps circling campaign work. When they ask about getting customers, suggest a campaign angle and explain when Campaign Studio is the right next step.';
        }

        if (in_array('tasks', $toolInterests, true)) {
            $guidance['rules'][] = 'The founder responds well to task-based guidance. Suggest the next best task and offer to break it down.';
        }

        return $guidance;
    }

    private function assistantNextPendingTaskSummary(Founder $founder): array
    {
        $task = $this->assistantNextPendingTask($founder);
        if (!$task) {
            return [];
        }

        return [
            'title' => (string) $task->title,
            'description' => (string) $task->description,
            'platform' => (string) ($task->platform ?? ''),
            'milestone' => (string) ($task->metadata_json['milestone'] ?? ''),
            'north_star_metric' => (string) ($task->metadata_json['north_star_metric'] ?? ''),
            'cta_label' => (string) ($task->cta_label ?? ''),
            'cta_url' => (string) ($task->cta_url ?? ''),
        ];
    }

    private function persistAssistantStrategicMemory(Founder $founder, FounderConversationThread $thread): void
    {
        $meta = is_array($thread->meta_json ?? null) ? $thread->meta_json : [];
        $assistantMemory = [
            'recurring_concerns' => array_values((array) ($meta['recurring_concerns'] ?? [])),
            'strategic_decisions' => array_values((array) ($meta['strategic_decisions'] ?? [])),
            'tool_interests' => array_values((array) ($meta['tool_interests'] ?? [])),
            'updated_at' => now()->toIso8601String(),
        ];

        if (
            empty($assistantMemory['recurring_concerns']) &&
            empty($assistantMemory['strategic_decisions']) &&
            empty($assistantMemory['tool_interests'])
        ) {
            return;
        }

        $businessBrief = $founder->businessBrief;
        $company = $founder->company;
        if (!$businessBrief && $company) {
            $businessBrief = $company->businessBrief;
        }

        if (!$businessBrief) {
            return;
        }

        $constraints = is_array($businessBrief->constraints_json ?? null) ? $businessBrief->constraints_json : [];
        $constraints['assistant_memory'] = $assistantMemory;

        $businessBrief->forceFill([
            'constraints_json' => $constraints,
        ])->save();
    }

    private function executeAssistantWebsiteBuildAction(
        Founder $founder,
        FounderNotificationService $founderNotificationService,
        array $actionResult
    ): array {
        $company = $founder->company;
        if (!$company) {
            return array_merge($actionResult, [
                'success' => false,
                'executed' => false,
                'reply' => 'Your founder workspace is missing a company record, so I could not start the website build yet.',
            ]);
        }

        $preferences = $this->assistantStoredOperatingPreferences($founder, $company);
        $buildFocusSentence = $this->assistantWebsiteBuildFocusSentence($preferences);

        if (in_array((string) ($company->website_generation_status ?? ''), ['in_progress', 'ready_for_review'], true)) {
            return array_merge($actionResult, [
                'reply' => 'We are already working on your website.' . $buildFocusSentence . ' I will keep watching for the publish result and share the live link here as soon as it is ready.',
                'actions' => [[
                    'title' => 'Build My Website',
                    'platform' => 'os',
                    'url' => route('website'),
                ]],
            ]);
        }

        if (in_array((string) ($company->website_status ?? ''), ['live'], true) && trim((string) ($company->website_url ?? '')) !== '') {
            return array_merge($actionResult, [
                'reply' => 'Your website is already live. Here is the public link: ' . (string) $company->website_url,
                'actions' => [[
                    'title' => 'Open Live Website',
                    'platform' => 'website',
                    'url' => (string) $company->website_url,
                ]],
            ]);
        }

        $this->syncFounderBusinessContextModels($founder, $company);
        $isImprovementPass = $company->websiteGenerationRuns()->exists()
            || in_array((string) ($company->website_status ?? ''), ['live', 'in_progress'], true)
            || in_array((string) ($company->website_generation_status ?? ''), ['published', 'ready_for_review', 'in_progress'], true);
        $engineLabel = $this->websiteBuildEngineLabel($company, 'auto');
        $this->startFounderWebsiteBuildPipeline($founder->fresh(['company']), $founderNotificationService, $engineLabel, $isImprovementPass);

        return array_merge($actionResult, [
            'reply' => 'Perfect. I am building and publishing your website now.' . $buildFocusSentence . ' I will reply here with the live link as soon as the website is ready.',
            'actions' => [[
                'title' => 'Build My Website',
                'platform' => 'os',
                'url' => route('website'),
            ]],
            'sync_summary' => 'Atlas triggered the founder website build and publish flow from Hatchers OS.',
        ]);
    }

    public function assistantApproveWebsiteBuild(
        Request $request,
        FounderNotificationService $founderNotificationService,
        OsAssistantTimelineService $timeline
    ): JsonResponse {
        /** @var \App\Models\Founder $founder */
        $founder = Auth::user();
        if (!$founder->isFounder()) {
            return response()->json([
                'success' => false,
                'error' => 'Only founder accounts can start a website build here.',
            ], 403);
        }

        if ($redirect = $this->ensureCompanyIntelligenceComplete($founder)) {
            return response()->json([
                'success' => false,
                'error' => 'Complete company intelligence first so Atlas has enough context to build the website.',
                'redirect' => $redirect->getTargetUrl(),
            ], 422);
        }

        $validated = $request->validate([
            'thread_key' => ['nullable', 'string', 'max:120'],
            'current_page' => ['nullable', 'string', 'max:255'],
        ]);

        $founder->loadMissing('company.intelligence', 'businessBrief', 'icpProfiles', 'actionPlans');
        $company = $founder->company;
        if (!$company) {
            return response()->json([
                'success' => false,
                'error' => 'Your founder workspace is missing a company record. Please refresh and try again.',
            ], 422);
        }

        $preferences = $this->assistantStoredOperatingPreferences($founder, $company);
        $buildFocusSentence = $this->assistantWebsiteBuildFocusSentence($preferences);

        if (in_array((string) ($company->website_generation_status ?? ''), ['in_progress', 'ready_for_review'], true)) {
            $reply = 'We are already working on your website.' . $buildFocusSentence . ' I will keep watching for the publish result and share the live link here as soon as it is ready.';
            $timeline->appendAssistantMessage(
                $founder,
                trim((string) ($validated['thread_key'] ?? '')) ?: null,
                (string) ($validated['current_page'] ?? 'prototype_dashboard'),
                $reply,
                [[
                    'cta' => 'Open Build My Website',
                    'os_workspace_key' => 'website',
                    'os_href' => route('website'),
                ]]
            );

            return response()->json([
                'success' => true,
                'status' => 'already_running',
                'reply' => $reply,
                'poll_status' => true,
            ]);
        }

        if (in_array((string) ($company->website_status ?? ''), ['live'], true) && trim((string) ($company->website_url ?? '')) !== '') {
            $websiteUrl = (string) $company->website_url;
            $reply = 'Your website is already live. Here is the public link: ' . $websiteUrl;

            return response()->json([
                'success' => true,
                'status' => 'already_live',
                'reply' => $reply,
                'website_url' => $websiteUrl,
            ]);
        }

        $this->syncFounderBusinessContextModels($founder, $company);
        $isImprovementPass = $company->websiteGenerationRuns()->exists()
            || in_array((string) ($company->website_status ?? ''), ['live', 'in_progress'], true)
            || in_array((string) ($company->website_generation_status ?? ''), ['published', 'ready_for_review', 'in_progress'], true);
        $engineLabel = $this->websiteBuildEngineLabel($company, 'auto');
        $this->startFounderWebsiteBuildPipeline($founder->fresh(['company']), $founderNotificationService, $engineLabel, $isImprovementPass);

        $reply = 'Perfect. I am building and publishing your website now.' . $buildFocusSentence . ' I will reply here with the live link as soon as the website is ready.';
        $timeline->appendAssistantMessage(
            $founder,
            trim((string) ($validated['thread_key'] ?? '')) ?: null,
            (string) ($validated['current_page'] ?? 'prototype_dashboard'),
            $reply,
            [[
                'cta' => 'Open Build My Website',
                'os_workspace_key' => 'website',
                'os_href' => route('website'),
            ]]
        );

        return response()->json([
            'success' => true,
            'status' => 'started',
            'reply' => $reply,
            'poll_status' => true,
        ]);
    }

    private function executeAssistantTaskBatchAction(Founder $founder, array $actionResult): array
    {
        $title = trim((string) ($actionResult['title'] ?? 'Atlas task list'));
        $steps = collect((array) ($actionResult['steps'] ?? []))
            ->map(fn ($step) => trim((string) $step))
            ->filter()
            ->take(6)
            ->values();

        if ($steps->isEmpty()) {
            return array_merge($actionResult, [
                'success' => false,
                'executed' => false,
                'reply' => 'I could not create tasks because there were no usable steps in that request.',
            ]);
        }

        $created = $steps->map(function (string $step, int $index) use ($founder, $title) {
            return FounderActionPlan::create([
                'founder_id' => $founder->id,
                'title' => $step,
                'description' => 'Created directly by Atlas from chat plan: ' . $title,
                'platform' => 'os_assistant',
                'priority' => max(40, 82 - ($index * 6)),
                'status' => 'pending',
                'cta_label' => 'Open Tasks',
                'cta_url' => route('founder.tasks'),
            ]);
        });

        return array_merge($actionResult, [
            'reply' => 'Done. I turned that plan into ' . $created->count() . ' real OS tasks for you. I would start with the first task today, then tell me where you feel resistance so I can tighten the plan with you.',
            'actions' => [[
                'title' => 'Open Tasks',
                'platform' => 'lms',
                'url' => route('founder.tasks'),
            ]],
            'sync_summary' => 'Atlas created ' . $created->count() . ' founder tasks directly from chat in Hatchers OS.',
        ]);
    }

    private function executeAssistantLaunchPlanRefineAction(Founder $founder, array $actionResult): array
    {
        $company = $founder->company;
        if (!$company) {
            return array_merge($actionResult, [
                'success' => false,
                'executed' => false,
                'reply' => 'Your founder workspace is missing a company record, so I could not refine the launch plan yet.',
            ]);
        }

        $company->loadMissing('intelligence');
        $launchSystem = $founder->launchSystems()->latest('id')->first();
        $blueprint = $company->verticalBlueprint ?: VerticalBlueprint::query()->find($company->vertical_blueprint_id);
        if (!$blueprint) {
            return array_merge($actionResult, [
                'success' => false,
                'executed' => false,
                'reply' => 'I could not refine the launch plan because the business blueprint is missing.',
            ]);
        }

        $focus = trim((string) ($actionResult['focus'] ?? ''));
        $signupProfile = $this->launchPlanSignupProfileFromCurrentState($company, $launchSystem, $focus);
        $deducedProfile = $this->launchPlanDeducedProfileFromCurrentState($company);
        $launchPlan = $this->buildFounderLaunchPlan($signupProfile, $deducedProfile, $blueprint);
        $preferences = $this->assistantStoredOperatingPreferences($founder, $company);
        $launchPlan = $this->applyAssistantOperatingPreferencesToLaunchPlan($launchPlan, $preferences, $signupProfile, $deducedProfile);

        if ($focus !== '') {
            $launchPlan['summary'] = trim($launchPlan['summary'] . ' Atlas refined this version around: ' . $focus . '.');
            $launchPlan['weekly_focus'] = $focus;
        }

        DB::transaction(function () use ($founder, $company, $blueprint, $signupProfile, $launchPlan) {
            FounderLaunchSystem::updateOrCreate(
                ['founder_id' => $founder->id, 'company_id' => $company->id],
                [
                    'vertical_blueprint_id' => $blueprint->id,
                    'status' => 'ready',
                    'selected_engine' => (string) $blueprint->engine,
                    'launch_strategy_json' => $launchPlan,
                    'funnel_blocks_json' => $launchPlan['assets'],
                    'offer_stack_json' => $launchPlan['offer_stack'],
                    'acquisition_system_json' => [
                        'channel_strategy' => $launchPlan['channel_strategy'],
                        'budget_strategy' => $signupProfile['budget_strategy'],
                        'channels' => $launchPlan['channels'],
                    ],
                    'applied_at' => now(),
                    'last_reviewed_at' => now(),
                ]
            );

            FounderActionPlan::query()
                ->where('founder_id', $founder->id)
                ->where('context', 'task')
                ->where('status', 'pending')
                ->where('metadata_json->source', 'launch_chat')
                ->delete();

            foreach ($launchPlan['tasks'] as $index => $task) {
                FounderActionPlan::create([
                    'founder_id' => $founder->id,
                    'title' => (string) $task['title'],
                    'description' => (string) $task['description'],
                    'platform' => (string) $task['platform'],
                    'context' => 'task',
                    'priority' => max(45, 95 - ($index * 3)),
                    'status' => 'pending',
                    'cta_label' => (string) $task['cta_label'],
                    'cta_url' => (string) $task['cta_url'],
                    'available_on' => $task['available_on'] ?? now()->toDateString(),
                    'metadata_json' => [
                        'source' => 'launch_chat',
                        'milestone' => (string) $task['milestone'],
                        'north_star_metric' => (string) $task['north_star_metric'],
                    ],
                ]);
            }

            FounderWeeklyState::updateOrCreate(
                ['founder_id' => $founder->id],
                [
                    'weekly_focus' => (string) $launchPlan['weekly_focus'],
                    'open_tasks' => count($launchPlan['tasks']),
                    'weekly_progress_percent' => 0,
                    'state_updated_at' => now(),
                ]
            );

            CompanyIntelligence::updateOrCreate(
                ['company_id' => $company->id],
                [
                    'content_goals' => implode(', ', array_values((array) ($launchPlan['north_star_metrics'] ?? []))),
                    'visual_style' => (string) $launchPlan['visual_direction'],
                    'last_summary' => (string) $launchPlan['summary'],
                    'intelligence_updated_at' => now(),
                ]
            );
        });

        return array_merge($actionResult, [
            'reply' => $focus !== ''
                ? 'Done. I refined your launch plan around ' . $focus . ' and refreshed the next OS tasks for you. The main thing I would watch now is whether this focus moves you closer to a real offer test, not just more activity. Want me to shape the next campaign angle too?'
                : 'Done. I refined your launch plan and refreshed the next OS tasks for you. The key now is to make sure the next tasks create evidence, not just motion. Want me to turn that into a campaign brief next?',
            'actions' => [[
                'title' => 'Open Tasks',
                'platform' => 'lms',
                'url' => route('founder.tasks'),
            ]],
            'sync_summary' => 'Atlas refined the founder launch plan and refreshed the pending OS task set.',
        ]);
    }

    private function executeAssistantLaunchPlanRefineCampaignAction(
        Founder $founder,
        array $actionResult,
        OsAssistantActionService $actionService
    ): array {
        $refined = $this->executeAssistantLaunchPlanRefineAction($founder, [
            'focus' => $actionResult['focus'] ?? '',
            'reply' => '',
            'actions' => [],
            'sync_summary' => $actionResult['sync_summary'] ?? '',
        ]);

        $founder->refresh();
        $founder->loadMissing('company.intelligence');

        $campaignBrief = $this->assistantCampaignBriefFromState(
            $founder,
            trim((string) ($actionResult['focus'] ?? '')),
            trim((string) ($actionResult['campaign_angle'] ?? 'campaign'))
        );
        $campaignIdeas = $this->assistantCampaignIdeasFromState(
            $founder,
            trim((string) ($actionResult['focus'] ?? '')),
            trim((string) ($actionResult['campaign_angle'] ?? 'campaign'))
        );

        $campaignCreation = $actionService->createCampaignFromOs(
            $founder,
            $campaignBrief['title'],
            $campaignBrief['description'],
            (string) ($actionResult['actor_role'] ?? 'founder')
        );

        $actions = [[
            'title' => 'Open Campaign Studio',
            'platform' => 'atlas',
            'reason' => 'Choose whether to launch a post, grid, or content campaign from this new brief.',
            'url' => route('workspace.launch', ['module' => 'atlas', 'target' => '/campaign-studio']),
        ]];

        if ($campaignCreation['success'] ?? false) {
            $actions[] = [
                'title' => 'Open Tasks',
                'platform' => 'lms',
                'url' => route('founder.tasks'),
            ];
            $formatReason = trim((string) ($campaignBrief['format_reason'] ?? ''));
            $formatReasonText = $formatReason !== '' ? ' I am leaning that way because ' . $formatReason . '.' : '';

            return array_merge($actionResult, [
                'reply' => 'Done. I refined your launch plan, created a campaign brief called "' . $campaignBrief['title'] . '", and I am opening Campaign Studio next so we can choose the best format. I would test this angle first: ' . $campaignBrief['angle'] . $formatReasonText . ' Three strong directions I see are: 1. ' . ($campaignIdeas[0] ?? 'lead with the core problem') . ' 2. ' . ($campaignIdeas[1] ?? 'show the offer outcome clearly') . ' 3. ' . ($campaignIdeas[2] ?? 'use one direct call to action') . ' Which one feels most aligned right now: post, grid, or a broader content sequence?',
                'actions' => $actions,
                'sync_summary' => 'Atlas refined the launch plan, generated a campaign brief, and prepared Campaign Studio in Hatchers OS.',
            ]);
        }

        $formatReason = trim((string) ($campaignBrief['format_reason'] ?? ''));
        $formatReasonText = $formatReason !== '' ? ' I am leaning that way because ' . $formatReason . '.' : '';

        return array_merge($actionResult, [
            'reply' => 'I refined your launch plan and refreshed the next OS tasks. I also drafted the campaign angle conceptually, but I could not save the Atlas campaign brief yet. The strongest next move is still to open Campaign Studio and choose whether this should start as a post, grid, or content sequence.' . $formatReasonText . ' My first three angle ideas would be: 1. ' . ($campaignIdeas[0] ?? 'lead with the core problem') . ' 2. ' . ($campaignIdeas[1] ?? 'show the offer outcome clearly') . ' 3. ' . ($campaignIdeas[2] ?? 'use one direct call to action') . '.',
            'actions' => $actions,
            'sync_summary' => 'Atlas refined the launch plan and prepared Campaign Studio, but campaign brief creation still needs follow-up.',
        ]);
    }

    private function executeAssistantCompanyRefreshWebsiteAction(
        Founder $founder,
        FounderNotificationService $founderNotificationService,
        array $actionResult
    ): array {
        $fieldLabel = str_replace('_', ' ', (string) ($actionResult['field'] ?? 'company intelligence'));
        $websiteAction = $this->executeAssistantWebsiteBuildAction($founder, $founderNotificationService, [
            'reply' => '',
            'actions' => [],
            'sync_summary' => '',
        ]);
        $preferences = $this->assistantStoredOperatingPreferences($founder, $founder->company);
        $areas = $this->assistantWebsiteRefreshAreas((string) ($actionResult['field'] ?? ''), $preferences);
        $areasText = empty($areas)
            ? 'headline, offer, and proof blocks'
            : implode(', ', $areas);
        $preferenceNote = [];
        if (($preferences['offer_clarity_priority'] ?? false) === true) {
            $preferenceNote[] = 'sharpen the offer before expanding the page';
        }
        if (($preferences['website_quality_priority'] ?? false) === true) {
            $preferenceNote[] = 'improve the conversion path and page clarity, not just visual changes';
        }
        $preferenceSentence = empty($preferenceNote)
            ? 'The strongest thing to watch next is whether the positioning feels sharper and easier to say yes to, not just different.'
            : 'Because of your earlier priorities, I am pushing this refresh to ' . implode(' and ', $preferenceNote) . '.';

        return array_merge($actionResult, [
            'reply' => 'Done. I updated "' . $fieldLabel . '" and I am regenerating your website copy now so the live messaging reflects the new direction. I would expect the biggest changes to show up in the ' . $areasText . '. ' . $preferenceSentence . ' I will reopen Build My Website for you so you can review the refreshed version.',
            'actions' => [[
                'title' => 'Build My Website',
                'platform' => 'os',
                'url' => route('website'),
            ]],
            'sync_summary' => $websiteAction['sync_summary'] ?? 'Atlas updated company intelligence and triggered a website copy refresh.',
        ]);
    }

    private function executeAssistantTaskCloseGuidanceAction(Founder $founder, array $actionResult): array
    {
        $preferences = $this->assistantStoredOperatingPreferences($founder, $founder->company);
        $nextTask = $this->assistantNextPendingTask($founder);

        if (!$nextTask) {
            return array_merge($actionResult, [
                'reply' => 'Done. I closed that task for you. Right now you have no pending tasks, which is a good checkpoint. Want me to refine the launch plan or create the next few tasks based on your current goal?',
                'actions' => [[
                    'title' => 'Open Tasks',
                    'platform' => 'lms',
                    'url' => route('founder.tasks'),
                ]],
            ]);
        }

        $milestone = trim((string) data_get($nextTask->metadata_json, 'milestone', ''));
        $whyNext = $this->assistantPreferredTaskReason($nextTask, $preferences);

        return array_merge($actionResult, [
            'reply' => 'Done. I closed that task for you. The next task I would move to is "' . $nextTask->title . '". ' . $whyNext . ' It matters because ' . trim((string) ($nextTask->description ?? 'it is the next leverage point in the plan')) . ($milestone !== '' ? ' It belongs to the milestone "' . $milestone . '".' : '') . ' My advice is to keep this one narrow and finishable in one work block. Want me to tighten that task or break it into smaller execution steps?',
            'actions' => [[
                'title' => 'Open Tasks',
                'platform' => 'lms',
                'url' => route('founder.tasks'),
            ]],
        ]);
    }

    private function executeAssistantTaskBreakdownAction(Founder $founder, array $actionResult): array
    {
        $task = $this->assistantNextPendingTask($founder);
        $preferences = $this->assistantStoredOperatingPreferences($founder, $founder->company);
        if (!$task) {
            return array_merge($actionResult, [
                'reply' => 'I am ready to break the work down, but there is no pending task to expand right now. Want me to create the next tasks from your current goal instead?',
                'actions' => [[
                    'title' => 'Open Tasks',
                    'platform' => 'lms',
                    'url' => route('founder.tasks'),
                ]],
            ]);
        }

        $steps = $this->assistantExecutionStepsForTask($task, $preferences);
        $metadata = is_array($task->metadata_json) ? $task->metadata_json : [];
        $metadata['execution_steps'] = $steps;
        $metadata['assistant_refined_at'] = now()->toIso8601String();
        $metadata['assistant_refine_mode'] = 'breakdown';

        $task->forceFill([
            'description' => trim((string) ($task->description ?? '')) . "\n\nExecution steps:\n" . collect($steps)
                ->values()
                ->map(fn ($step, $index) => ($index + 1) . '. ' . $step)
                ->implode("\n"),
            'metadata_json' => $metadata,
        ])->save();

        return array_merge($actionResult, [
            'reply' => 'Done. I broke "' . $task->title . '" into smaller execution steps for you. ' . $this->assistantPreferredTaskReason($task, $preferences) . ' I would work them in this order: 1. ' . ($steps[0] ?? 'clarify the outcome') . ' 2. ' . ($steps[1] ?? 'build the first visible asset') . ' 3. ' . ($steps[2] ?? 'ship and review'). ' My advice is to finish step one in the next work block before touching the rest.',
            'actions' => [[
                'title' => 'Open Tasks',
                'platform' => 'lms',
                'url' => route('founder.tasks'),
            ]],
            'sync_summary' => 'Atlas broke the next founder task into smaller execution steps in Hatchers OS.',
        ]);
    }

    private function executeAssistantTaskTightenAction(Founder $founder, array $actionResult): array
    {
        $task = $this->assistantNextPendingTask($founder);
        $preferences = $this->assistantStoredOperatingPreferences($founder, $founder->company);
        if (!$task) {
            return array_merge($actionResult, [
                'reply' => 'I can tighten the next task, but there is no pending task to reshape right now. Want me to create the next tasks from your current goal instead?',
                'actions' => [[
                    'title' => 'Open Tasks',
                    'platform' => 'lms',
                    'url' => route('founder.tasks'),
                ]],
            ]);
        }

        $tightened = $this->assistantTightenedTaskCopy($task, $preferences);
        $metadata = is_array($task->metadata_json) ? $task->metadata_json : [];
        $metadata['assistant_refined_at'] = now()->toIso8601String();
        $metadata['assistant_refine_mode'] = 'tighten';

        $task->forceFill([
            'title' => $tightened['title'],
            'description' => $tightened['description'],
            'metadata_json' => $metadata,
        ])->save();

        return array_merge($actionResult, [
            'reply' => 'Done. I tightened that task into a cleaner work block: "' . $tightened['title'] . '". ' . $this->assistantPreferredTaskReason($task, $preferences) . ' The goal now is not to make it bigger, but to make it easier to finish in one sitting. If you want, I can break it into smaller execution steps next.',
            'actions' => [[
                'title' => 'Open Tasks',
                'platform' => 'lms',
                'url' => route('founder.tasks'),
            ]],
            'sync_summary' => 'Atlas tightened the next founder task into a narrower execution block in Hatchers OS.',
        ]);
    }

    private function executeAssistantCampaignFormatAction(Founder $founder, array $actionResult): array
    {
        $campaignAngle = trim((string) ($actionResult['campaign_angle'] ?? 'campaign'));
        $idea = $this->assistantFirstCampaignAssetIdea($founder, $campaignAngle);

        FounderActionPlan::create([
            'founder_id' => $founder->id,
            'title' => $idea['task_title'],
            'description' => $idea['task_description'],
            'platform' => 'atlas',
            'context' => 'task',
            'priority' => 88,
            'status' => 'pending',
            'cta_label' => 'Open Atlas',
            'cta_url' => route('workspace.launch', ['module' => 'atlas', 'target' => '/campaign-studio']),
            'available_on' => now()->toDateString(),
            'metadata_json' => [
                'source' => 'atlas_campaign_follow_up',
                'campaign_angle' => $campaignAngle,
            ],
        ]);

        return array_merge($actionResult, [
            'reply' => 'Perfect. I would start with a ' . $campaignAngle . ' first. My first direction would be: ' . $idea['idea'] . ' I created a matching OS task so you can execute it cleanly, and I am reopening Campaign Studio next so you can build it there.',
            'actions' => [
                [
                    'title' => 'Open Campaign Studio',
                    'platform' => 'atlas',
                    'url' => route('workspace.launch', ['module' => 'atlas', 'target' => '/campaign-studio']),
                ],
                [
                    'title' => 'Open Tasks',
                    'platform' => 'lms',
                    'url' => route('founder.tasks'),
                ],
            ],
            'sync_summary' => 'Atlas prepared the next campaign format, created a matching OS task, and reopened Campaign Studio.',
        ]);
    }

    private function assistantNextPendingTask(Founder $founder): ?FounderActionPlan
    {
        $preferences = $this->assistantStoredOperatingPreferences($founder, $founder->company);

        $tasks = FounderActionPlan::query()
            ->where('founder_id', $founder->id)
            ->where('context', 'task')
            ->whereNotIn('status', ['completed', 'complete', 'done'])
            ->orderByDesc('priority')
            ->orderBy('available_on')
            ->get();

        if ($tasks->isEmpty()) {
            return null;
        }

        return $tasks
            ->sortByDesc(fn (FounderActionPlan $task) => $this->assistantTaskRelevanceScore($task, $preferences))
            ->sortByDesc(fn (FounderActionPlan $task) => (int) $task->priority)
            ->sortBy(fn (FounderActionPlan $task) => (string) ($task->available_on ?? '9999-12-31'))
            ->first();
    }

    private function assistantExecutionStepsForTask(FounderActionPlan $task, array $preferences = []): array
    {
        $title = trim((string) $task->title);
        $description = trim((string) ($task->description ?? ''));
        $shortContext = Str::limit($description !== '' ? $description : $title, 120, '...');

        if (($preferences['offer_clarity_priority'] ?? false) === true && $this->assistantTaskMatchesKeywords($task, [
            'offer', 'headline', 'message', 'cta', 'homepage', 'landing', 'website',
        ])) {
            return [
                'Rewrite the promise for "' . $title . '" into one clear customer-facing sentence with one outcome and one buyer.',
                'Strip away extra claims or choices so the CTA and value proposition are obvious in the first screen or first message.',
                'Publish or test the sharper version and note which wording feels most direct and believable.',
            ];
        }

        if (($preferences['organic_first'] ?? false) === true && $this->assistantTaskMatchesKeywords($task, [
            'campaign', 'post', 'grid', 'content', 'social', 'email', 'outreach', 'lead',
        ])) {
            return [
                'Name the pain point or buyer problem this asset should lead with before creating anything.',
                'Draft one organic-first asset that uses a strong hook, one belief shift, and one proof point tied to ' . Str::lower($shortContext) . '.',
                'Add one clear organic CTA, publish or queue it, then note what response signal you want to track next.',
            ];
        }

        if (($preferences['website_quality_priority'] ?? false) === true && $this->assistantTaskMatchesKeywords($task, [
            'website', 'homepage', 'page', 'copy', 'cta', 'funnel', 'service',
        ])) {
            return [
                'Clarify what part of the conversion path this task should improve before editing the page.',
                'Make one concrete change to the page or copy that removes confusion and strengthens the CTA.',
                'Review the page as a buyer, tighten the weakest section, and confirm the next step is obvious.',
            ];
        }

        return [
            'Clarify the exact output for "' . $title . '" in one sentence before you start.',
            'Create the first concrete asset or draft tied to ' . Str::lower($shortContext) . '.',
            'Review it against the milestone, tighten the weakest part, and ship it.',
        ];
    }

    private function assistantTightenedTaskCopy(FounderActionPlan $task, array $preferences = []): array
    {
        $title = trim((string) $task->title);
        $description = trim((string) ($task->description ?? ''));
        $normalizedTitle = Str::of($title)->replaceMatches('/\s+/', ' ')->trim()->value();

        if (($preferences['offer_clarity_priority'] ?? false) === true && $this->assistantTaskMatchesKeywords($task, [
            'offer', 'headline', 'message', 'cta', 'homepage', 'landing', 'website',
        ])) {
            return [
                'title' => Str::limit('Rewrite the core offer and CTA', 68, ''),
                'description' => 'Tighten this into one conversion-focused work block: rewrite the offer promise, sharpen the CTA, and remove anything that weakens message clarity. Finish with one buyer-facing version you can ship or test today.',
            ];
        }

        if (($preferences['organic_first'] ?? false) === true && $this->assistantTaskMatchesKeywords($task, [
            'campaign', 'post', 'grid', 'content', 'social', 'email', 'outreach', 'lead',
        ])) {
            return [
                'title' => Str::limit('Draft one organic response asset', 68, ''),
                'description' => 'Turn this into one organic-first execution block: create a single asset with one hook, one belief shift, one proof point, and one CTA. Do not expand scope beyond the first shippable post, email, or outreach draft.',
            ];
        }

        return [
            'title' => Str::limit($normalizedTitle, 68, ''),
            'description' => 'Complete one tight work block for this task: ' . ($description !== '' ? Str::limit($description, 220, '...') : 'finish the smallest shippable version of it.') . ' Focus on one output, one CTA, and one finish line.',
        ];
    }

    private function assistantTaskRelevanceScore(FounderActionPlan $task, array $preferences): int
    {
        $score = (int) $task->priority;

        if (($preferences['offer_clarity_priority'] ?? false) === true && $this->assistantTaskMatchesKeywords($task, [
            'offer', 'headline', 'message', 'cta', 'homepage', 'landing', 'website',
        ])) {
            $score += 120;
        }

        if (($preferences['website_quality_priority'] ?? false) === true && $this->assistantTaskMatchesKeywords($task, [
            'website', 'homepage', 'page', 'copy', 'cta', 'funnel', 'service',
        ])) {
            $score += 100;
        }

        if (($preferences['campaign_studio_interest'] ?? false) === true && $this->assistantTaskMatchesKeywords($task, [
            'campaign', 'post', 'grid', 'content', 'social',
        ])) {
            $score += 90;
        }

        if (($preferences['organic_first'] ?? false) === true && $this->assistantTaskMatchesKeywords($task, [
            'outreach', 'lead', 'social', 'email', 'content', 'post', 'prospect',
        ])) {
            $score += 80;
        }

        if (($preferences['task_guidance_preference'] ?? false) === true && (string) $task->platform === 'os') {
            $score += 20;
        }

        return $score;
    }

    private function assistantTaskMatchesKeywords(FounderActionPlan $task, array $keywords): bool
    {
        $haystack = Str::lower(trim((string) $task->title . ' ' . (string) ($task->description ?? '')));
        foreach ($keywords as $keyword) {
            if (str_contains($haystack, Str::lower($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function assistantPreferredTaskReason(FounderActionPlan $task, array $preferences): string
    {
        if (($preferences['offer_clarity_priority'] ?? false) === true && $this->assistantTaskMatchesKeywords($task, [
            'offer', 'headline', 'message', 'cta', 'homepage', 'landing', 'website',
        ])) {
            return 'Because offer clarity keeps surfacing as a founder concern, this is the strongest next leverage point.';
        }

        if (($preferences['website_quality_priority'] ?? false) === true && $this->assistantTaskMatchesKeywords($task, [
            'website', 'homepage', 'page', 'copy', 'cta', 'funnel', 'service',
        ])) {
            return 'Because website quality is a current priority, this task is the fastest way to improve conversion clarity.';
        }

        if (($preferences['campaign_studio_interest'] ?? false) === true && $this->assistantTaskMatchesKeywords($task, [
            'campaign', 'post', 'grid', 'content', 'social',
        ])) {
            return 'Because you have been leaning into campaign execution, this is the best next task to turn strategy into visible output.';
        }

        if (($preferences['organic_first'] ?? false) === true && $this->assistantTaskMatchesKeywords($task, [
            'outreach', 'lead', 'social', 'email', 'content', 'post', 'prospect',
        ])) {
            return 'Because your current operating preference is organic-first growth, this task best supports that path.';
        }

        return 'This is the strongest next leverage point in the current plan.';
    }

    private function assistantWebsiteBuildPromptFromPreferences(array $preferences): string
    {
        if (($preferences['offer_clarity_priority'] ?? false) === true) {
            return 'If you want, I can build and publish your first website next and use it to sharpen the offer, CTA, and buying path.';
        }

        if (($preferences['organic_first'] ?? false) === true) {
            return 'If you want, I can build and publish your first website next and make it support your organic outreach and conversion path.';
        }

        if (($preferences['website_quality_priority'] ?? false) === true) {
            return 'If you want, I can build and publish your first website next with extra focus on page clarity, conversion flow, and CTA strength.';
        }

        return 'If you want, I can build and publish your first website next.';
    }

    private function assistantWebsiteBuildFocusSentence(array $preferences): string
    {
        $areas = [];
        if (($preferences['offer_clarity_priority'] ?? false) === true) {
            $areas[] = 'offer clarity';
            $areas[] = 'CTA strength';
        }
        if (($preferences['organic_first'] ?? false) === true) {
            $areas[] = 'organic conversion flow';
        }
        if (($preferences['website_quality_priority'] ?? false) === true) {
            $areas[] = 'page clarity';
        }

        $areas = array_values(array_unique($areas));
        if ($areas === []) {
            return '';
        }

        return ' I am weighting this build toward ' . implode(', ', $areas) . '.';
    }

    private function assistantFirstCampaignAssetIdea(Founder $founder, string $campaignAngle): array
    {
        $company = $founder->company;
        $intelligence = $company?->intelligence;
        $preferences = $this->assistantStoredOperatingPreferences($founder, $company);
        $icp = trim((string) ($intelligence?->primary_icp_name ?? $intelligence?->ideal_customer_profile ?? 'the right buyer'));
        $offer = trim((string) ($intelligence?->core_offer ?? 'your offer'));
        $problem = trim((string) ($intelligence?->problem_solved ?? $company?->company_brief ?? 'the core problem'));

        return match ($campaignAngle) {
            'grid' => [
                'idea' => (($preferences['organic_first'] ?? false) === true
                    ? 'a 3-card organic trust grid: call out the problem, reframe the belief, then make "' . $offer . '" feel like the obvious next step for ' . $icp . '.'
                    : 'a 3-card grid: call out the problem, reframe the belief, then make "' . $offer . '" feel like the obvious next step for ' . $icp . '.'),
                'task_title' => 'Draft first campaign grid',
                'task_description' => 'Create the first 3-card campaign grid. Card 1 names the problem. Card 2 shifts the belief. Card 3 presents "' . $offer . '" with one CTA.',
            ],
            'content' => [
                'idea' => (($preferences['organic_first'] ?? false) === true
                    ? 'a short content sequence that starts with the pain around ' . Str::lower(Str::limit($problem, 90, '...')) . ', builds trust through proof, then asks for one clear next step.'
                    : 'a short content sequence that starts with the pain around ' . Str::lower(Str::limit($problem, 90, '...')) . ', then earns trust before asking for action.'),
                'task_title' => 'Draft first content sequence',
                'task_description' => 'Write the first short content sequence: pain point, belief shift, proof, then CTA into "' . $offer . '".',
            ],
            default => [
                'idea' => (($preferences['offer_clarity_priority'] ?? false) === true
                    ? 'one direct-response post that simplifies the offer, names the problem clearly, and gives ' . $icp . ' one clean next step.'
                    : 'one direct-response post that names the problem, makes the offer concrete, and gives ' . $icp . ' one clean next step.'),
                'task_title' => 'Draft first campaign post',
                'task_description' => 'Write the first direct-response post. Lead with the problem, frame "' . $offer . '" as the answer, and end with one clean CTA.',
            ],
        };
    }

    private function launchPlanSignupProfileFromCurrentState(Company $company, ?FounderLaunchSystem $launchSystem, string $focus = ''): array
    {
        $intelligence = $company->intelligence;
        $hoursPerWeek = max(1, (int) data_get($launchSystem?->launch_strategy_json, 'pace.hours_per_week', 4));
        $budgetStrategy = $this->inferBudgetStrategyFromRefinementFocus($focus, (string) data_get($launchSystem?->acquisition_system_json, 'budget_strategy', 'organic'));

        return [
            'company_name' => (string) ($company->company_name ?? 'Founder project'),
            'company_description' => (string) ($company->company_brief ?? $intelligence?->problem_solved ?? $intelligence?->last_summary ?? ''),
            'ideal_customer_profile' => (string) ($intelligence?->ideal_customer_profile ?? $intelligence?->primary_icp_name ?? ''),
            'business_model' => (string) ($company->business_model ?? 'service'),
            'stage' => (string) ($company->stage ?? 'operating'),
            'primary_growth_goal' => (string) ($intelligence?->primary_growth_goal ?? 'Get my first customers'),
            'known_blockers' => (string) ($intelligence?->known_blockers ?? 'No clear offer yet'),
            'budget_strategy' => $budgetStrategy,
            'time_commitment' => $hoursPerWeek <= 2 ? 'low' : ($hoursPerWeek >= 7 ? 'high' : 'mid'),
            'hours_per_week' => $hoursPerWeek,
        ];
    }

    private function launchPlanDeducedProfileFromCurrentState(Company $company): array
    {
        $intelligence = $company->intelligence;

        return [
            'primary_icp_name' => (string) ($intelligence?->primary_icp_name ?? 'Ideal customer'),
            'target_audience' => (string) ($intelligence?->target_audience ?? 'Consumers / B2C'),
            'problem_solved' => (string) ($intelligence?->problem_solved ?? $company->company_brief ?? ''),
            'brand_voice' => (string) ($intelligence?->brand_voice ?? 'Professional and credible'),
            'differentiators' => (string) ($intelligence?->differentiators ?? ''),
            'core_offer' => (string) ($intelligence?->core_offer ?? 'Starter offer'),
            'pain_points' => $this->splitIntelligenceCsv((string) ($intelligence?->ideal_customer_profile ?? '')),
            'desired_outcomes' => $this->splitIntelligenceCsv((string) ($intelligence?->buying_triggers ?? '')),
            'objections' => $this->splitIntelligenceCsv((string) ($intelligence?->objections ?? '')),
            'primary_city' => (string) Str::before((string) ($intelligence?->local_market_notes ?? ''), '·'),
        ];
    }

    private function assistantCampaignBriefFromState(Founder $founder, string $focus, string $campaignAngle): array
    {
        $company = $founder->company;
        $intelligence = $company?->intelligence;
        $launchSystem = $founder->launchSystems()->latest('id')->first();
        $weeklyState = $founder->weeklyState;
        $preferences = $this->assistantStoredOperatingPreferences($founder, $company);

        $companyName = trim((string) ($company?->company_name ?? 'Founder project'));
        $icp = trim((string) ($intelligence?->primary_icp_name ?? $intelligence?->ideal_customer_profile ?? 'ideal customers'));
        $offer = trim((string) ($intelligence?->core_offer ?? 'the core offer'));
        $goal = trim((string) ($intelligence?->primary_growth_goal ?? 'generate demand'));
        $weeklyFocus = trim((string) ($weeklyState?->weekly_focus ?? data_get($launchSystem?->launch_strategy_json, 'weekly_focus', '')));
        $northStar = collect((array) data_get($launchSystem?->launch_strategy_json, 'north_star_metrics', []))
            ->filter(fn ($metric) => trim((string) $metric) !== '')
            ->values()
            ->all();
        [$resolvedCampaignAngle, $formatReason] = $this->assistantPreferredCampaignFormat($campaignAngle, $preferences, $focus);

        $angle = match ($resolvedCampaignAngle) {
            'grid' => 'a multi-card grid that educates the right buyer and leads them into the offer',
            'post' => 'a single direct-response post that calls out the problem and pushes to the offer',
            'content' => 'a short content sequence that builds trust before asking for the next step',
            default => 'a direct-response campaign angle that turns attention into action quickly',
        };

        $titleFocus = $focus !== '' ? Str::title(Str::limit($focus, 40, '')) : 'Next Campaign';

        return [
            'title' => $companyName . ' · ' . $titleFocus,
            'angle' => $angle,
            'description' => trim(implode("\n\n", array_filter([
                'Objective: ' . $goal,
                'Audience: ' . $icp,
                'Offer: ' . $offer,
                $weeklyFocus !== '' ? 'Current focus: ' . $weeklyFocus : null,
                !empty($northStar) ? 'North-star metric: ' . $northStar[0] : null,
                $formatReason !== '' ? 'Format rationale: ' . $formatReason : null,
                'Creative direction: Build this around ' . $angle . '. Keep the message practical, direct, and conversion-focused. Lead with the problem, make the offer feel easy to say yes to, and use one clear CTA.',
            ]))),
            'format_reason' => $formatReason,
            'preferred_format' => $resolvedCampaignAngle,
        ];
    }

    private function assistantCampaignIdeasFromState(Founder $founder, string $focus, string $campaignAngle): array
    {
        $company = $founder->company;
        $intelligence = $company?->intelligence;
        $preferences = $this->assistantStoredOperatingPreferences($founder, $company);
        $icp = trim((string) ($intelligence?->primary_icp_name ?? $intelligence?->ideal_customer_profile ?? 'ideal customers'));
        $problem = trim((string) ($intelligence?->problem_solved ?? $company?->company_brief ?? 'a pressing customer problem'));
        $offer = trim((string) ($intelligence?->core_offer ?? 'the core offer'));
        $focusText = $focus !== '' ? $focus : 'the current growth goal';

        $ideas = [
            'Call out how ' . $icp . ' are still dealing with ' . Str::lower(Str::limit($problem, 90, '...')) . ' and frame the post around what they are losing by waiting.',
            'Show how "' . $offer . '" moves people faster toward ' . $focusText . ' with one concrete before-and-after contrast.',
            'Use a simple proof or founder-belief angle that lowers friction and ends with one clean CTA into the offer.',
        ];

        if ($campaignAngle === 'grid') {
            $ideas[0] = 'Open with a problem card, then a belief-shift card, then a CTA card built for ' . $icp . '.';
        } elseif ($campaignAngle === 'post') {
            $ideas[1] = (($preferences['offer_clarity_priority'] ?? false) === true)
                ? 'Write one sharp post that simplifies the offer, reframes the problem, and makes "' . $offer . '" feel easy to say yes to.'
                : 'Write one sharp post that names the problem, reframes it, and makes "' . $offer . '" feel like the obvious next step.';
        } elseif ($campaignAngle === 'content') {
            $ideas[2] = (($preferences['organic_first'] ?? false) === true)
                ? 'Turn this into a short organic trust sequence: pain point, belief shift, proof, then CTA.'
                : 'Turn this into a short sequence: pain point, belief shift, proof, then CTA.';
        }

        return $ideas;
    }

    private function assistantPreferredCampaignFormat(string $campaignAngle, array $preferences, string $focus = ''): array
    {
        $normalized = trim(Str::lower($campaignAngle));
        if (in_array($normalized, ['post', 'grid', 'content'], true)) {
            return [$normalized, 'you already signaled that format explicitly'];
        }

        $focusText = Str::lower(trim($focus));
        if (($preferences['organic_first'] ?? false) === true) {
            if (preg_match('/educate|sequence|nurture|trust|story|content/', $focusText)) {
                return ['content', 'you have been leaning organic-first and this focus benefits from trust-building over multiple touches'];
            }

            if (($preferences['campaign_studio_interest'] ?? false) === true) {
                return ['grid', 'you prefer organic growth and repeated campaign work, so a grid is a strong bridge between trust and execution'];
            }

            return ['post', 'you are leaning organic-first, so a direct-response post is the fastest small test before building a larger content system'];
        }

        if (($preferences['offer_clarity_priority'] ?? false) === true) {
            return ['post', 'offer clarity is still the main issue, so a single sharp post is the fastest way to test whether the message lands'];
        }

        return ['post', 'a single direct-response post is still the simplest first test before expanding into more assets'];
    }

    private function assistantWebsiteRefreshAreas(string $field, array $preferences = []): array
    {
        $areas = match ($field) {
            'company_name' => ['brand label', 'hero headline'],
            'company_brief' => ['hero message', 'founder story', 'about section'],
            'target_audience', 'ideal_customer_profile' => ['hero message', 'problem framing', 'service copy'],
            'brand_voice' => ['headline tone', 'CTA language', 'body copy'],
            'differentiators', 'core_offer' => ['offer stack', 'service descriptions', 'CTA blocks'],
            'primary_growth_goal', 'known_blockers' => ['CTA direction', 'offer framing', 'FAQ emphasis'],
            default => [],
        };

        if (($preferences['offer_clarity_priority'] ?? false) === true) {
            $areas[] = 'offer clarity';
            $areas[] = 'CTA simplicity';
        }

        if (($preferences['website_quality_priority'] ?? false) === true) {
            $areas[] = 'conversion path';
            $areas[] = 'page clarity';
        }

        return array_values(array_unique($areas));
    }

    private function inferBudgetStrategyFromRefinementFocus(string $focus, string $fallback = 'organic'): string
    {
        $focus = Str::lower(trim($focus));
        if ($focus === '') {
            return $fallback !== '' ? $fallback : 'organic';
        }

        if (preg_match('/paid|ads|meta ads|google ads|ad campaign/', $focus)) {
            return 'paid';
        }

        if (preg_match('/organic|content|outreach|direct outreach|social/', $focus)) {
            return 'organic';
        }

        return $fallback !== '' ? $fallback : 'organic';
    }

    private function assistantStoredOperatingPreferences(Founder $founder, ?Company $company = null): array
    {
        $businessBrief = $founder->businessBrief;
        if (!$businessBrief && $company) {
            $businessBrief = $company->businessBrief;
        }

        $assistantMemory = is_array($businessBrief?->constraints_json['assistant_memory'] ?? null)
            ? $businessBrief->constraints_json['assistant_memory']
            : [];

        $joinedDecisions = Str::lower(implode(' | ', array_values((array) ($assistantMemory['strategic_decisions'] ?? []))));
        $joinedConcerns = Str::lower(implode(' | ', array_values((array) ($assistantMemory['recurring_concerns'] ?? []))));
        $toolInterests = array_values((array) ($assistantMemory['tool_interests'] ?? []));

        return [
            'organic_first' => str_contains($joinedDecisions, 'organic'),
            'paid_interest' => str_contains($joinedDecisions, 'paid'),
            'offer_clarity_priority' => str_contains($joinedConcerns, 'offer clarity'),
            'website_quality_priority' => str_contains($joinedConcerns, 'website quality'),
            'campaign_studio_interest' => in_array('campaign_studio', $toolInterests, true),
            'task_guidance_preference' => in_array('tasks', $toolInterests, true),
        ];
    }

    private function applyAssistantOperatingPreferencesToLaunchPlan(
        array $launchPlan,
        array $preferences,
        array $signupProfile,
        array $deducedProfile
    ): array {
        if (($preferences['organic_first'] ?? false) === true) {
            $launchPlan['channels'] = ['website', 'social_content', 'direct_outreach', 'email_capture'];
            $launchPlan['channel_strategy'] = 'Use organic outreach, direct-response content, proof, and follow-up to validate demand before spending on ads.';
            $launchPlan['visual_direction'] = 'Trust-building visuals with clarity, proof, and practical next steps over hype.';
        } elseif (($preferences['paid_interest'] ?? false) === true && ($signupProfile['budget_strategy'] ?? 'organic') !== 'organic') {
            $launchPlan['channel_strategy'] = 'Use paid distribution only after the offer, audience, and conversion path are clear enough to convert efficiently.';
        }

        if (($preferences['offer_clarity_priority'] ?? false) === true) {
            $firstMilestone = $launchPlan['milestones'][0] ?? null;
            if (is_array($firstMilestone)) {
                $launchPlan['milestones'][0]['objective'] = trim((string) ($firstMilestone['objective'] ?? '') . ' Start by sharpening the offer, promise, and why-now angle before expanding execution.');
                $launchPlan['milestones'][0]['north_star_metric'] = 'Offer and message rewritten into one clear customer-facing angle';
            }

            $launchPlan['weekly_focus'] = 'Sharpen the core offer, the customer language, and the simplest path to a buying decision before adding more channels.';
            $launchPlan['summary'] = trim((string) ($launchPlan['summary'] ?? '') . ' This version puts extra weight on offer clarity so the founder improves the first buying decision before scaling activity.');

            array_unshift($launchPlan['tasks'], [
                'title' => 'Rewrite the core offer in one sentence',
                'description' => 'Clarify who the offer is for, what painful problem it solves, what result it creates, and what the clean next step is.',
                'platform' => 'os',
                'cta_label' => 'Open Tasks',
                'cta_url' => route('founder.tasks'),
                'milestone' => (string) ($launchPlan['milestones'][0]['title'] ?? 'Positioning reset'),
                'north_star_metric' => (string) ($launchPlan['milestones'][0]['north_star_metric'] ?? 'Offer and message rewritten into one clear customer-facing angle'),
                'available_on' => now()->toDateString(),
            ]);
        }

        if (($preferences['campaign_studio_interest'] ?? false) === true) {
            $launchPlan['summary'] = trim((string) ($launchPlan['summary'] ?? '') . ' The founder has shown repeated interest in campaign execution, so the next sprint should include a stronger campaign angle handoff.');
        }

        return $launchPlan;
    }

    private function splitIntelligenceCsv(string $value): array
    {
        return collect(preg_split('/[\r\n,;]+/', $value) ?: [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->take(6)
            ->values()
            ->all();
    }

    public function assistantWebsiteBuildStatus(Request $request): JsonResponse
    {
        /** @var \App\Models\Founder $founder */
        $founder = Auth::user();
        $company = $founder->company;

        if (!$founder->isFounder() || !$company) {
            return response()->json([
                'success' => false,
                'error' => 'Your founder workspace is missing company context.',
            ], 422);
        }

        $run = $company->websiteGenerationRuns()->latest('id')->first();
        $websiteUrl = trim((string) ($company->website_url ?? ''));
        if ($websiteUrl === '' && trim((string) ($company->website_path ?? '')) !== '') {
            $websiteUrl = 'https://app.hatchers.ai/' . ltrim((string) $company->website_path, '/');
        }

        if (in_array((string) ($company->website_status ?? ''), ['live'], true) && $websiteUrl !== '') {
            return response()->json([
                'success' => true,
                'state' => 'ready',
                'reply' => 'Your website is ready. Here is the live link: ' . $websiteUrl,
                'website_url' => $websiteUrl,
            ]);
        }

        if (($run?->status ?? '') === 'failed') {
            $message = trim((string) data_get($run?->output_json, 'engine_sync.message', ''));
            if ($message === '') {
                $message = trim((string) data_get($run?->output_json, 'starter_sync.message', ''));
            }
            if ($message === '') {
                $message = trim((string) data_get($run?->output_json, 'blog_sync.message', ''));
            }

            return response()->json([
                'success' => true,
                'state' => 'failed',
                'reply' => $message !== ''
                    ? 'The website build paused before publish: ' . $message
                    : 'The website build paused before publish. Open Build My Website so we can review the last missing piece together.',
            ]);
        }

        return response()->json([
            'success' => true,
            'state' => 'processing',
            'reply' => 'Still working on the website build…',
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
                'platform' => 'os_assistant',
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
                'keywords' => ['campaign studio', 'post', 'grid', 'content sequence', 'campaign brief'],
                'key' => 'campaign-studio',
                'href' => route('workspace.launch', ['module' => 'atlas', 'target' => '/campaign-studio']),
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
    ): RedirectResponse|JsonResponse {
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

        $responsePayload = [
            'ok' => true,
            'status' => $normalizedStatus,
            'completed' => $normalizedStatus === 'completed',
            'message' => $success,
            'task' => [
                'id' => $actionPlan->id,
                'title' => $actionPlan->title,
                'status_label' => $normalizedStatus === 'completed'
                    ? ($isLesson ? 'Reopen lesson' : 'Reopen task')
                    : ($isLesson ? 'Complete lesson' : 'Complete task'),
            ],
        ];

        if (!($bridgeResult['success'] ?? false)) {
            if ($this->expectsJsonTaskResponse(request())) {
                $responsePayload['warning'] = ($bridgeResult['reply'] ?? 'LMS sync is still pending.') . ' The OS state was updated and will keep moving forward.';
                return response()->json($responsePayload);
            }

            return redirect()
                ->route($route)
                ->with('success', $success)
                ->with('error', ($bridgeResult['reply'] ?? 'LMS sync is still pending.') . ' The OS state was updated and will keep moving forward.');
        }

        if ($this->expectsJsonTaskResponse(request())) {
            return response()->json($responsePayload);
        }

        return redirect()
            ->route($route)
            ->with('success', $success);
    }

    private function expectsJsonTaskResponse(Request $request): bool
    {
        return $request->expectsJson()
            || $request->wantsJson()
            || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest';
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
                "
                SUM(CASE WHEN context = 'task' AND (status NOT IN ('completed', 'complete', 'done') AND completed_at IS NULL) THEN 1 ELSE 0 END) as open_task_count,
                SUM(CASE WHEN context = 'task' AND (status IN ('completed', 'complete', 'done') OR completed_at IS NOT NULL) THEN 1 ELSE 0 END) as completed_task_count,
                SUM(CASE WHEN context = 'lesson' AND (status NOT IN ('completed', 'complete', 'done') AND completed_at IS NULL) THEN 1 ELSE 0 END) as open_lesson_count,
                SUM(CASE WHEN context = 'lesson' AND (status IN ('completed', 'complete', 'done') OR completed_at IS NOT NULL) THEN 1 ELSE 0 END) as completed_lesson_count
                "
            )
            ->first();

        $openTaskCount = (int) ($totals?->open_task_count ?? 0);
        $completedTaskCount = (int) ($totals?->completed_task_count ?? 0);
        $openLessonCount = (int) ($totals?->open_lesson_count ?? 0);
        $completedLessonCount = (int) ($totals?->completed_lesson_count ?? 0);
        $totalLessonCount = $openLessonCount + $completedLessonCount;
        $progress = $totalLessonCount > 0 ? (int) round(($completedLessonCount / $totalLessonCount) * 100) : 0;

        FounderWeeklyState::updateOrCreate(
            ['founder_id' => $founder->id],
            [
                'open_tasks' => $openTaskCount,
                'completed_tasks' => $completedTaskCount,
                'open_milestones' => $openLessonCount,
                'completed_milestones' => $completedLessonCount,
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
            ->with(['founder.moduleSnapshots', 'founder.businessBrief', 'businessBrief', 'websiteGenerationRuns'])
            ->get()
            ->first(function (Company $company) use ($normalizedPath): bool {
                if (!$this->companyMatchesPublicWebsitePath($company, $normalizedPath)) {
                    return false;
                }

                return $this->companyIsPublicWebsiteLive($company);
            });

        if (!$company) {
            $company = Company::query()
                ->with(['founder.moduleSnapshots', 'founder.businessBrief', 'businessBrief', 'websiteGenerationRuns'])
                ->get()
                ->first(fn (Company $company): bool => $this->companyMatchesPublicWebsitePath($company, $normalizedPath));
        }

        if ($company && blank($company->website_path)) {
            $company->website_path = $normalizedPath;
            $company->website_url = $this->buildCompanyWebsiteUrl($company, (string) ($company->website_engine ?? ''));
            $company->save();
        }

        if (!$company) {
            $founder = Founder::query()
                ->with(['company', 'moduleSnapshots'])
                ->whereNotNull('id')
                ->get()
                ->first(function (Founder $founder) use ($normalizedPath): bool {
                    $fullNameSlug = trim(strtolower((string) str($founder->full_name ?: '')->slug('-')->value()), '/');
                    if ($fullNameSlug !== '' && $fullNameSlug === $normalizedPath) {
                        return true;
                    }

                    $username = trim(strtolower((string) ($founder->username ?? '')), '/');
                    if ($username !== '' && $username === $normalizedPath) {
                        return true;
                    }

                    foreach (($founder->moduleSnapshots ?? collect()) as $snapshot) {
                        $payload = is_array($snapshot->payload_json ?? null) ? $snapshot->payload_json : [];
                        $snapshotSlug = trim(strtolower((string) ($payload['slug'] ?? $payload['vendor_slug'] ?? '')), '/');
                        if ($snapshotSlug !== '' && $snapshotSlug === $normalizedPath) {
                            return true;
                        }

                        $snapshotUrl = trim((string) ($payload['summary']['website_url'] ?? ''));
                        $snapshotPath = trim(strtolower((string) parse_url($snapshotUrl, PHP_URL_PATH)), '/');
                        if ($snapshotPath !== '') {
                            $segments = array_values(array_filter(explode('/', $snapshotPath)));
                            if ($snapshotPath === $normalizedPath || in_array($normalizedPath, $segments, true)) {
                                return true;
                            }
                        }
                    }

                    return false;
                });

            if ($founder?->company) {
                $company = $founder->company;

                if (blank($company->website_path)) {
                    $company->website_path = $normalizedPath;
                    $company->website_url = $this->buildCompanyWebsiteUrl($company, (string) ($company->website_engine ?? ''));
                    $company->save();
                }
            }
        }

        if (!$company) {
            $generationRun = FounderWebsiteGenerationRun::query()
                ->with(['company.founder.moduleSnapshots', 'founder.company'])
                ->latest('id')
                ->get()
                ->first(function (FounderWebsiteGenerationRun $run) use ($normalizedPath): bool {
                    $output = is_array($run->output_json ?? null) ? $run->output_json : [];
                    $draftPath = trim(strtolower((string) ($output['website_path'] ?? '')), '/');

                    if ($draftPath !== '' && $draftPath === $normalizedPath) {
                        return true;
                    }

                    $draftUrl = trim((string) ($output['website_url'] ?? ''));
                    $draftUrlPath = trim(strtolower((string) parse_url($draftUrl, PHP_URL_PATH)), '/');
                    if ($draftUrlPath !== '') {
                        $segments = array_values(array_filter(explode('/', $draftUrlPath)));
                        if ($draftUrlPath === $normalizedPath || in_array($normalizedPath, $segments, true)) {
                            return true;
                        }
                    }

                    return false;
                });

            if ($generationRun) {
                $company = $generationRun->company ?: $generationRun->founder?->company;

                if ($company) {
                    if (blank($company->website_path)) {
                        $company->website_path = $normalizedPath;
                    }
                    if (blank($company->website_url)) {
                        $company->website_url = $this->buildCompanyWebsiteUrl($company, (string) ($company->website_engine ?? ''));
                    }
                    $company->save();
                }
            }
        }

        return $company;
    }

    private function resolvePublicWebsiteRootCompany(string $websiteRoot): ?Company
    {
        return $this->resolvePublicWebsiteCompany($websiteRoot);
    }

    private function companyMatchesPublicWebsitePath(Company $company, string $normalizedPath): bool
    {
        $path = trim(strtolower((string) ($company->website_path ?? '')), '/');
        if ($path === '') {
            $path = strtolower((string) str($company->company_name ?: 'your-business')->slug('-'));
        }

        if ($path === $normalizedPath) {
            return true;
        }

        $websiteUrlPath = trim((string) parse_url((string) ($company->website_url ?? ''), PHP_URL_PATH), '/');
        if ($websiteUrlPath !== '' && strtolower($websiteUrlPath) === $normalizedPath) {
            return true;
        }

        $engineUrlPath = trim((string) parse_url((string) ($company->engine_public_url ?? ''), PHP_URL_PATH), '/');
        if ($engineUrlPath !== '') {
            $engineSegments = array_values(array_filter(explode('/', strtolower($engineUrlPath))));
            if (in_array($normalizedPath, $engineSegments, true) || strtolower($engineUrlPath) === $normalizedPath) {
                return true;
            }
        }

        $founderUsername = trim(strtolower((string) ($company->founder?->username ?? '')), '/');
        if ($founderUsername !== '' && $founderUsername === $normalizedPath) {
            return true;
        }

        $founderFullNameSlug = trim(strtolower((string) str($company->founder?->full_name ?: '')->slug('-')->value()), '/');
        if ($founderFullNameSlug !== '' && $founderFullNameSlug === $normalizedPath) {
            return true;
        }

        $companyBriefNameSlug = trim(strtolower((string) str($company->businessBrief?->business_name ?: '')->slug('-')->value()), '/');
        if ($companyBriefNameSlug !== '' && $companyBriefNameSlug === $normalizedPath) {
            return true;
        }

        $founderBriefNameSlug = trim(strtolower((string) str($company->founder?->businessBrief?->business_name ?: '')->slug('-')->value()), '/');
        if ($founderBriefNameSlug !== '' && $founderBriefNameSlug === $normalizedPath) {
            return true;
        }

        $moduleSnapshots = $company->founder?->moduleSnapshots ?? collect();
        foreach ($moduleSnapshots as $snapshot) {
            $payload = is_array($snapshot->payload_json ?? null) ? $snapshot->payload_json : [];
            $snapshotSlug = trim(strtolower((string) ($payload['slug'] ?? $payload['vendor_slug'] ?? '')), '/');
            if ($snapshotSlug !== '' && $snapshotSlug === $normalizedPath) {
                return true;
            }

            $summaryWebsiteUrl = trim((string) ($payload['summary']['website_url'] ?? ''));
            $summaryPath = trim(strtolower((string) parse_url($summaryWebsiteUrl, PHP_URL_PATH)), '/');
            if ($summaryPath !== '') {
                $summarySegments = array_values(array_filter(explode('/', $summaryPath)));
                if (strtolower($summaryPath) === $normalizedPath || in_array($normalizedPath, $summarySegments, true)) {
                    return true;
                }
            }
        }

        foreach (($company->websiteGenerationRuns ?? collect()) as $run) {
            $output = is_array($run->output_json ?? null) ? $run->output_json : [];
            $draftPath = trim(strtolower((string) ($output['website_path'] ?? '')), '/');
            if ($draftPath !== '' && $draftPath === $normalizedPath) {
                return true;
            }

            $draftUrl = trim((string) ($output['website_url'] ?? ''));
            $draftUrlPath = trim(strtolower((string) parse_url($draftUrl, PHP_URL_PATH)), '/');
            if ($draftUrlPath !== '') {
                $draftSegments = array_values(array_filter(explode('/', $draftUrlPath)));
                if ($draftUrlPath === $normalizedPath || in_array($normalizedPath, $draftSegments, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function companyIsPublicWebsiteLive(Company $company): bool
    {
        $websiteStatus = strtolower(trim((string) ($company->website_status ?? '')));
        $generationStatus = strtolower(trim((string) ($company->website_generation_status ?? '')));
        $launchStage = strtolower(trim((string) ($company->launch_stage ?? '')));

        return $websiteStatus === 'live'
            || $generationStatus === 'published'
            || $launchStage === 'website_live';
    }

    private function logPublicWebsiteResolutionFailure(string $websitePath, string $context, string $proxyPath = ''): void
    {
        $normalizedPath = trim(strtolower($websitePath), '/');

        $companyMatches = Company::query()
            ->with(['founder', 'websiteGenerationRuns'])
            ->get()
            ->filter(fn (Company $company): bool => $this->companyMatchesPublicWebsitePath($company, $normalizedPath))
            ->take(5)
            ->map(fn (Company $company): array => [
                'company_id' => (int) $company->id,
                'company_name' => (string) ($company->company_name ?? ''),
                'website_path' => (string) ($company->website_path ?? ''),
                'website_url' => (string) ($company->website_url ?? ''),
                'engine_public_url' => (string) ($company->engine_public_url ?? ''),
                'website_status' => (string) ($company->website_status ?? ''),
                'generation_status' => (string) ($company->website_generation_status ?? ''),
                'founder_username' => (string) ($company->founder?->username ?? ''),
                'founder_full_name' => (string) ($company->founder?->full_name ?? ''),
            ])
            ->values()
            ->all();

        $founderMatches = Founder::query()
            ->with('company')
            ->get()
            ->filter(function (Founder $founder) use ($normalizedPath): bool {
                $username = trim(strtolower((string) ($founder->username ?? '')), '/');
                $fullNameSlug = trim(strtolower((string) str($founder->full_name ?: '')->slug('-')->value()), '/');

                return ($username !== '' && $username === $normalizedPath)
                    || ($fullNameSlug !== '' && $fullNameSlug === $normalizedPath);
            })
            ->take(5)
            ->map(fn (Founder $founder): array => [
                'founder_id' => (int) $founder->id,
                'username' => (string) ($founder->username ?? ''),
                'full_name' => (string) ($founder->full_name ?? ''),
                'company_id' => (int) ($founder->company?->id ?? 0),
                'company_website_path' => (string) ($founder->company?->website_path ?? ''),
            ])
            ->values()
            ->all();

        $generationMatches = FounderWebsiteGenerationRun::query()
            ->with(['company', 'founder'])
            ->latest('id')
            ->get()
            ->filter(function (FounderWebsiteGenerationRun $run) use ($normalizedPath): bool {
                $output = is_array($run->output_json ?? null) ? $run->output_json : [];
                $draftPath = trim(strtolower((string) ($output['website_path'] ?? '')), '/');
                $draftUrlPath = trim(strtolower((string) parse_url((string) ($output['website_url'] ?? ''), PHP_URL_PATH)), '/');

                if ($draftPath !== '' && $draftPath === $normalizedPath) {
                    return true;
                }

                if ($draftUrlPath !== '') {
                    $segments = array_values(array_filter(explode('/', $draftUrlPath)));

                    return $draftUrlPath === $normalizedPath || in_array($normalizedPath, $segments, true);
                }

                return false;
            })
            ->take(5)
            ->map(function (FounderWebsiteGenerationRun $run): array {
                $output = is_array($run->output_json ?? null) ? $run->output_json : [];

                return [
                    'run_id' => (int) $run->id,
                    'founder_id' => (int) ($run->founder_id ?? 0),
                    'company_id' => (int) ($run->company_id ?? 0),
                    'website_path' => (string) ($output['website_path'] ?? ''),
                    'website_url' => (string) ($output['website_url'] ?? ''),
                ];
            })
            ->values()
            ->all();

        Log::warning('Public website resolution failed', [
            'context' => $context,
            'requested_path' => $websitePath,
            'normalized_path' => $normalizedPath,
            'proxy_path' => $proxyPath,
            'company_matches' => $companyMatches,
            'founder_matches' => $founderMatches,
            'generation_matches' => $generationMatches,
        ]);
        error_log('[PublicWebsite][failed] ' . json_encode([
            'context' => $context,
            'requested_path' => $websitePath,
            'normalized_path' => $normalizedPath,
            'proxy_path' => $proxyPath,
            'company_matches' => $companyMatches,
            'founder_matches' => $founderMatches,
            'generation_matches' => $generationMatches,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->appendPublicWebsiteDebugLog('failed', [
            'context' => $context,
            'requested_path' => $websitePath,
            'normalized_path' => $normalizedPath,
            'proxy_path' => $proxyPath,
            'company_matches' => $companyMatches,
            'founder_matches' => $founderMatches,
            'generation_matches' => $generationMatches,
        ]);
    }

    private function logPublicWebsiteResolutionSuccess(string $websitePath, Company $company, array $site, string $context): void
    {
        Log::info('Public website resolved', [
            'context' => $context,
            'requested_path' => $websitePath,
            'company_id' => (int) $company->id,
            'company_name' => (string) ($company->company_name ?? ''),
            'website_path' => (string) ($company->website_path ?? ''),
            'website_url' => (string) ($company->website_url ?? ''),
            'engine_public_url' => (string) ($company->engine_public_url ?? ''),
            'engine' => (string) ($site['engine'] ?? ''),
            'uses_engine_storefront' => (bool) ($site['uses_engine_storefront'] ?? false),
            'engine_vendor_slug' => (string) ($site['engine_vendor_slug'] ?? ''),
            'engine_proxy_url' => (string) ($site['engine_proxy_url'] ?? ''),
        ]);
        error_log('[PublicWebsite][resolved] ' . json_encode([
            'context' => $context,
            'requested_path' => $websitePath,
            'company_id' => (int) $company->id,
            'company_name' => (string) ($company->company_name ?? ''),
            'website_path' => (string) ($company->website_path ?? ''),
            'website_url' => (string) ($company->website_url ?? ''),
            'engine_public_url' => (string) ($company->engine_public_url ?? ''),
            'engine' => (string) ($site['engine'] ?? ''),
            'uses_engine_storefront' => (bool) ($site['uses_engine_storefront'] ?? false),
            'engine_vendor_slug' => (string) ($site['engine_vendor_slug'] ?? ''),
            'engine_proxy_url' => (string) ($site['engine_proxy_url'] ?? ''),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->appendPublicWebsiteDebugLog('resolved', [
            'context' => $context,
            'requested_path' => $websitePath,
            'company_id' => (int) $company->id,
            'company_name' => (string) ($company->company_name ?? ''),
            'website_path' => (string) ($company->website_path ?? ''),
            'website_url' => (string) ($company->website_url ?? ''),
            'engine_public_url' => (string) ($company->engine_public_url ?? ''),
            'engine' => (string) ($site['engine'] ?? ''),
            'uses_engine_storefront' => (bool) ($site['uses_engine_storefront'] ?? false),
            'engine_vendor_slug' => (string) ($site['engine_vendor_slug'] ?? ''),
            'engine_proxy_url' => (string) ($site['engine_proxy_url'] ?? ''),
        ]);
    }

    private function appendPublicWebsiteDebugLog(string $stage, array $context): void
    {
        try {
            File::append(
                storage_path('logs/public-website-debug.log'),
                '[' . now()->toDateTimeString() . '] [PublicWebsite][' . $stage . '] ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL
            );
        } catch (Throwable) {
        }
    }

    private function proxyEngineStorefront(Company $company, string $proxyPath, Request $request, PublicWebsiteService $publicWebsiteService)
    {
        $site = $publicWebsiteService->build($company);
        $engineProxyCandidates = collect((array) ($site['engine_proxy_candidates'] ?? []))
            ->map(fn ($url) => trim((string) $url))
            ->filter()
            ->values();
        $engineProxyUrl = trim((string) ($site['engine_proxy_url'] ?? ''));
        if ($engineProxyUrl !== '' && !$engineProxyCandidates->contains($engineProxyUrl)) {
            $engineProxyCandidates->prepend($engineProxyUrl);
        }
        $websiteRoot = trim((string) ($site['path'] ?? $company->website_path ?? ''), '/');
        $this->appendPublicWebsiteDebugLog('proxy-start', [
            'company_id' => (int) $company->id,
            'website_root' => $websiteRoot,
            'proxy_path' => $proxyPath,
            'engine_proxy_url' => $engineProxyUrl,
            'engine_proxy_candidates' => $engineProxyCandidates->all(),
            'uses_engine_storefront' => (bool) ($site['uses_engine_storefront'] ?? false),
        ]);

        if ($engineProxyUrl === '' || $websiteRoot === '') {
            $this->appendPublicWebsiteDebugLog('proxy-skipped', [
                'reason' => 'missing_engine_proxy_or_website_root',
                'company_id' => (int) $company->id,
                'website_root' => $websiteRoot,
                'engine_proxy_url' => $engineProxyUrl,
                'engine_proxy_candidates' => $engineProxyCandidates->all(),
            ]);
            return view('os.public-website', [
                'pageTitle' => (string) ($company->company_name ?: 'Business Website'),
                'site' => $this->disableEngineStorefrontFallback($site),
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
            $this->appendPublicWebsiteDebugLog('proxy-skipped', [
                'reason' => 'recursive_storefront_target',
                'company_id' => (int) $company->id,
                'website_root' => $websiteRoot,
                'target' => $engineProxyUrl,
            ]);

            return view('os.public-website', [
                'pageTitle' => (string) ($company->company_name ?: 'Business Website'),
                'site' => $this->disableEngineStorefrontFallback($site),
                'sourceContext' => [
                    'src' => trim((string) $request->query('src', '')),
                    'promo' => trim((string) $request->query('promo', '')),
                    'offer' => trim((string) $request->query('offer', '')),
                ],
            ]);
        }

        $targetUrl = rtrim($engineProxyUrl, '/');
        $engineOrigin = $this->engineStorefrontOrigin($engineProxyUrl);
        $proxyPath = trim($proxyPath, '/');

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

        $candidateUrls = $engineProxyCandidates->isNotEmpty()
            ? $engineProxyCandidates
            : collect([$engineProxyUrl]);
        $targetUrl = '';
        $engineOrigin = '';
        $upstream = null;

        foreach ($candidateUrls as $candidateUrl) {
            $candidateUrl = rtrim((string) $candidateUrl, '/');
            if ($candidateUrl === '') {
                continue;
            }

            $candidateOrigin = $this->engineStorefrontOrigin($candidateUrl);
            $resolvedTargetUrl = $candidateUrl;
            if ($proxyPath !== '') {
                if ($this->isEngineRootAssetPath($proxyPath) && $candidateOrigin !== '') {
                    $resolvedTargetUrl = rtrim($candidateOrigin, '/') . '/' . $proxyPath;
                } else {
                    $resolvedTargetUrl .= '/' . $proxyPath;
                }
            }

            $candidateResponse = $client->send($method, $resolvedTargetUrl, $options);
            $candidateStatus = $candidateResponse->status();

            $candidateContext = [
                'company_id' => (int) $company->id,
                'website_root' => $websiteRoot,
                'proxy_path' => $proxyPath,
                'candidate_url' => $candidateUrl,
                'target_url' => $resolvedTargetUrl,
                'status' => $candidateStatus,
            ];
            Log::warning('Storefront proxy candidate attempted.', $candidateContext);
            $this->appendPublicWebsiteDebugLog('proxy-candidate', $candidateContext);

            $upstream = $candidateResponse;
            $targetUrl = $resolvedTargetUrl;
            $engineOrigin = $candidateOrigin;

            if ($proxyPath !== '' || $candidateStatus !== 404) {
                if ($candidateUrl !== $engineProxyUrl) {
                    $site['engine_proxy_url'] = $candidateUrl;
                    $site['source_storefront_url'] = $candidateUrl;
                }
                break;
            }
        }

        if ($upstream === null) {
            $this->appendPublicWebsiteDebugLog('proxy-skipped', [
                'reason' => 'no_upstream_response',
                'company_id' => (int) $company->id,
                'website_root' => $websiteRoot,
                'proxy_path' => $proxyPath,
                'engine_proxy_candidates' => $engineProxyCandidates->all(),
            ]);
            return view('os.public-website', [
                'pageTitle' => (string) ($company->company_name ?: 'Business Website'),
                'site' => $this->disableEngineStorefrontFallback($site),
                'sourceContext' => [
                    'src' => trim((string) $request->query('src', '')),
                    'promo' => trim((string) $request->query('promo', '')),
                    'offer' => trim((string) $request->query('offer', '')),
                ],
            ]);
        }

        $status = $upstream->status();
        $contentType = strtolower((string) $upstream->header('Content-Type', 'text/html; charset=UTF-8'));

        if ($status === 404 && $proxyPath === '') {
            Log::warning('Storefront proxy returned 404 for website root; falling back to local public website view.', [
                'company_id' => (int) $company->id,
                'website_root' => $websiteRoot,
                'target_url' => $targetUrl,
                'engine' => (string) ($site['engine'] ?? ''),
            ]);
            $this->appendPublicWebsiteDebugLog('proxy-root-404', [
                'company_id' => (int) $company->id,
                'website_root' => $websiteRoot,
                'target_url' => $targetUrl,
                'engine' => (string) ($site['engine'] ?? ''),
            ]);

            return view('os.public-website', [
                'pageTitle' => (string) ($company->company_name ?: 'Business Website'),
                'site' => $this->disableEngineStorefrontFallback($site),
                'sourceContext' => [
                    'src' => trim((string) $request->query('src', '')),
                    'promo' => trim((string) $request->query('promo', '')),
                    'offer' => trim((string) $request->query('offer', '')),
                ],
            ]);
        }

        if ($status >= 300 && $status < 400 && filled($upstream->header('Location'))) {
            $location = $this->rewriteStorefrontUrlToOsPath((string) $upstream->header('Location'), $engineProxyUrl, $websiteRoot);

            return redirect()->away($location, $status);
        }

        $body = $upstream->body();
        if (str_contains($contentType, 'text/html')) {
            $body = $this->rewriteStorefrontHtmlForOs($body, $engineProxyUrl, $websiteRoot);
        } elseif (str_contains($contentType, 'json')) {
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

    private function disableEngineStorefrontFallback(array $site): array
    {
        $site['uses_engine_storefront'] = false;
        $site['source_storefront_url'] = '';
        $site['engine_proxy_url'] = '';

        return $site;
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
        $engineOrigin = $this->engineStorefrontOrigin($engineProxyUrl);
        $escapedEngineUrl = str_replace('/', '\\/', $normalizedEngineUrl);
        $escapedEngineOrigin = str_replace('/', '\\/', $engineOrigin);
        $escapedOsBaseUrl = str_replace('/', '\\/', $osBaseUrl);

        $rewritten = str_replace(
            array_filter([$normalizedEngineUrl, $escapedEngineUrl, $engineOrigin, $escapedEngineOrigin], static fn ($value) => $value !== ''),
            array_filter([$osBaseUrl, $escapedOsBaseUrl, $osBaseUrl, $escapedOsBaseUrl], static fn ($value) => $value !== ''),
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

    private function engineStorefrontOrigin(string $engineProxyUrl): string
    {
        $scheme = parse_url($engineProxyUrl, PHP_URL_SCHEME);
        $host = parse_url($engineProxyUrl, PHP_URL_HOST);
        $port = parse_url($engineProxyUrl, PHP_URL_PORT);

        if (!$scheme || !$host) {
            return '';
        }

        return $scheme . '://' . $host . ($port ? ':' . $port : '');
    }

    private function isEngineRootAssetPath(string $proxyPath): bool
    {
        $normalized = ltrim(str_replace('\\', '/', $proxyPath), '/');

        foreach (['storage/', 'front/', 'admin-assets/', 'landing/', 'widget_asstes/'] as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                return true;
            }
        }

        return false;
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

    private function founderBusinessModelOptions(): array
    {
        return [
            'product' => 'Product Business',
            'service' => 'Service Business',
            'hybrid' => 'Hybrid Business',
        ];
    }

    private function founderStageOptions(): array
    {
        return [
            'idea' => 'Idea Stage',
            'launching' => 'Launching',
            'operating' => 'Operating',
            'scaling' => 'Scaling',
        ];
    }

    private function founderTargetAudienceOptions(): array
    {
        return [
            'Consumers / B2C',
            'Small businesses / SMB',
            'Corporate / Enterprise',
            'Creators / Personal brands',
            'Local community / Neighborhood market',
        ];
    }

    private function founderBrandVoiceOptions(): array
    {
        return [
            'Warm and supportive',
            'Premium and polished',
            'Bold and energetic',
            'Professional and credible',
            'Friendly and simple',
        ];
    }

    private function founderPrimaryGrowthGoalOptions(): array
    {
        return [
            'Launch my first website',
            'Get my first customers',
            'Increase recurring sales',
            'Build a stronger brand presence',
            'Systemize and scale operations',
        ];
    }

    private function founderKnownBlockerOptions(): array
    {
        return [
            'No clear offer yet',
            'No website or weak funnel',
            'Low traffic or visibility',
            'Low conversions or sales',
            'Limited time or team capacity',
        ];
    }

    private function founderChatOnboardingState(Founder $founder): array
    {
        $wizard = $this->companyIntelligenceWizardState($founder);
        $company = $founder->company;
        $launchSystem = $founder->launchSystems()->latest('id')->first();

        return [
            'is_complete' => (bool) ($wizard['is_complete'] ?? false),
            'needs_onboarding' => !(bool) ($wizard['is_complete'] ?? false),
            'project_name' => trim((string) ($company?->company_name ?? '')),
            'placeholder_founder_name' => $this->placeholderFounderNameFromEmail((string) $founder->email),
            'completion_percent' => (int) ($wizard['completion_percent'] ?? 0),
            'has_launch_plan' => $launchSystem !== null && !empty($launchSystem->launch_strategy_json),
        ];
    }

    private function founderLaunchPlanState(Founder $founder): array
    {
        $launchSystem = $founder->launchSystems()->latest('id')->first();
        $strategy = is_array($launchSystem?->launch_strategy_json) ? $launchSystem->launch_strategy_json : [];
        $tasks = $founder->actionPlans()
            ->where('context', 'task')
            ->orderBy('available_on')
            ->orderByDesc('priority')
            ->limit(12)
            ->get()
            ->map(function (FounderActionPlan $task): array {
                return [
                    'id' => (int) $task->id,
                    'title' => (string) $task->title,
                    'description' => (string) $task->description,
                    'available_on' => optional($task->available_on)->toDateString(),
                    'milestone' => (string) data_get($task->metadata_json, 'milestone', ''),
                    'north_star_metric' => (string) data_get($task->metadata_json, 'north_star_metric', ''),
                    'status' => (string) ($task->status ?? 'pending'),
                    'cta_label' => (string) ($task->cta_label ?? 'Open'),
                    'cta_url' => (string) ($task->cta_url ?? '/tasks'),
                ];
            })
            ->values()
            ->all();

        return [
            'is_ready' => !empty($strategy),
            'title' => (string) ($strategy['title'] ?? 'Your launch plan'),
            'summary' => (string) ($strategy['summary'] ?? ''),
            'weekly_focus' => (string) ($strategy['weekly_focus'] ?? ''),
            'north_star_metrics' => array_values((array) ($strategy['north_star_metrics'] ?? [])),
            'milestones' => array_values((array) ($strategy['milestones'] ?? [])),
            'pace' => is_array($strategy['pace'] ?? null) ? $strategy['pace'] : [],
            'tasks' => $tasks,
        ];
    }

    private function uniqueFounderUsernameFromSeed(string $seed): string
    {
        $base = Str::lower((string) Str::slug($seed !== '' ? $seed : 'founder', '_'));
        $base = preg_replace('/[^a-z0-9_]/', '', $base) ?: 'founder';
        $candidate = Str::limit($base, 32, '');
        $suffix = 2;

        while (Founder::query()->where('username', $candidate)->exists()) {
            $candidate = Str::limit($base, max(1, 32 - strlen((string) $suffix)), '') . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function placeholderFounderNameFromEmail(string $email): string
    {
        $local = trim(Str::before($email, '@'));
        if ($local === '') {
            return 'New Founder';
        }

        return Str::title(str_replace(['.', '_', '-'], ' ', $local));
    }

    private function placeholderCompanyNameFromEmail(string $email): string
    {
        $local = trim(Str::before($email, '@'));
        if ($local === '') {
            return 'New Venture';
        }

        return Str::title(str_replace(['.', '_', '-'], ' ', $local)) . ' Project';
    }

    private function chatOnboardingValidatedProfile(Founder $founder, array $answers): array
    {
        $companyDescription = trim(implode(' ', array_filter([
            (string) ($answers['q1'] ?? ''),
            (string) ($answers['q3'] ?? ''),
            (string) ($answers['q4'] ?? ''),
        ])));
        $projectName = $this->deduceProjectNameFromAnswer((string) ($answers['q1'] ?? ''), (string) $founder->email);
        $businessModel = $this->inferBusinessModelFromChatAnswers($answers);
        $stage = $this->inferStageFromChatAnswers($answers);

        return [
            'company_name' => $projectName,
            'company_description' => $companyDescription !== '' ? $companyDescription : (string) ($answers['q1'] ?? ''),
            'ideal_customer_profile' => (string) ($answers['q2'] ?? ''),
            'business_model' => $businessModel,
            'stage' => $stage,
            'primary_growth_goal' => $this->inferPrimaryGrowthGoalFromChatAnswers($stage, $answers),
            'known_blockers' => $this->inferKnownBlockerFromChatAnswers($answers),
            'budget_strategy' => (string) ($answers['budget_strategy'] ?? 'organic'),
            'time_commitment' => (string) ($answers['time_commitment'] ?? 'mid'),
        ];
    }

    private function deduceProjectNameFromAnswer(string $answer, string $email): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $answer));
        if ($clean !== '') {
            $fragments = preg_split('/[,.!?]/', $clean) ?: [];
            $first = trim((string) ($fragments[0] ?? ''));
            if ($first !== '') {
                $first = trim(Str::headline(Str::limit($first, 36, '')));
                if (mb_strlen($first) >= 4 && str_word_count($first) <= 5) {
                    return $first;
                }
            }
        }

        return $this->placeholderCompanyNameFromEmail($email);
    }

    private function inferBusinessModelFromChatAnswers(array $answers): string
    {
        $combined = Str::lower(implode(' ', array_filter([
            (string) ($answers['q1'] ?? ''),
            (string) ($answers['q3'] ?? ''),
            (string) ($answers['q4'] ?? ''),
        ])));

        if (preg_match('/course|software|app|product|shop|ecommerce|e-commerce|physical product|subscription box/', $combined)) {
            return 'product';
        }

        if (preg_match('/service|studio|agency|coaching|consulting|classes|appointments|bookings|freelance|grooming/', $combined)) {
            return 'service';
        }

        return 'service';
    }

    private function inferStageFromChatAnswers(array $answers): string
    {
        $combined = Str::lower(implode(' ', array_filter([
            (string) ($answers['q1'] ?? ''),
            (string) ($answers['q3'] ?? ''),
        ])));

        if (preg_match('/already have customers|already selling|existing business|operating|running|current clients|current customers/', $combined)) {
            return 'operating';
        }

        if (preg_match('/launch|launching|about to start|mvp|landing page|pre-launch/', $combined)) {
            return 'launching';
        }

        if (preg_match('/idea|thinking about|exploring|validation/', $combined)) {
            return 'idea';
        }

        return 'idea';
    }

    private function inferPrimaryGrowthGoalFromChatAnswers(string $stage, array $answers): string
    {
        if ($stage === 'idea') {
            return 'Get my first customers';
        }

        if ($stage === 'launching') {
            return 'Launch my first website';
        }

        $combined = Str::lower((string) ($answers['q3'] ?? ''));
        if (str_contains($combined, 'repeat') || str_contains($combined, 'return')) {
            return 'Increase recurring sales';
        }

        return 'Build a stronger brand presence';
    }

    private function inferKnownBlockerFromChatAnswers(array $answers): string
    {
        $combined = Str::lower(implode(' ', array_filter([
            (string) ($answers['q1'] ?? ''),
            (string) ($answers['q3'] ?? ''),
            (string) ($answers['q4'] ?? ''),
        ])));

        if (str_contains($combined, 'no website') || str_contains($combined, 'landing page') || str_contains($combined, 'funnel')) {
            return 'No website or weak funnel';
        }
        if (str_contains($combined, 'traffic') || str_contains($combined, 'visibility') || str_contains($combined, 'discover')) {
            return 'Low traffic or visibility';
        }
        if (str_contains($combined, 'convert') || str_contains($combined, 'sales') || str_contains($combined, 'buy')) {
            return 'Low conversions or sales';
        }
        if (str_contains($combined, 'time') || str_contains($combined, 'capacity') || str_contains($combined, 'busy')) {
            return 'Limited time or team capacity';
        }

        return 'No clear offer yet';
    }

    private function buildFounderLaunchPlan(array $signupProfile, array $deducedProfile, VerticalBlueprint $blueprint): array
    {
        $hoursPerWeek = match ((string) ($signupProfile['time_commitment'] ?? 'mid')) {
            'low' => 2,
            'high' => 7,
            default => 4,
        };
        $effectiveHoursPerWeek = max(1, (int) floor($hoursPerWeek * match ((string) ($signupProfile['time_commitment'] ?? 'mid')) {
            'low' => 0.55,
            'high' => 0.8,
            default => 0.7,
        }));
        $budgetStrategy = (string) ($signupProfile['budget_strategy'] ?? 'organic');
        $stage = (string) ($signupProfile['stage'] ?? 'idea');

        $milestones = [];
        if ($stage === 'idea') {
            $milestones[] = [
                'title' => 'Customer discovery',
                'objective' => 'Run discovery interviews to confirm the problem is real and understand exactly what the customer wants.',
                'north_star_metric' => '10 discovery conversations completed',
                'estimated_hours' => 14,
            ];
            $milestones[] = [
                'title' => 'Lean MVP setup',
                'objective' => 'Build a landing page, simple social proof, and a starter offer to test discoverability and intent.',
                'north_star_metric' => 'Landing page published with one clear CTA',
                'estimated_hours' => 18,
            ];
            $milestones[] = [
                'title' => 'Demand validation',
                'objective' => 'Drive organic interest to the MVP and measure whether people engage, ask, book, or buy.',
                'north_star_metric' => 'First 5 qualified leads or 1 paid conversion',
                'estimated_hours' => 20,
            ];
        } else {
            $milestones[] = [
                'title' => 'Positioning reset',
                'objective' => 'Clarify the offer, audience, and message so every growth asset points to one practical outcome.',
                'north_star_metric' => 'Offer stack and message locked',
                'estimated_hours' => 10,
            ];
            $milestones[] = [
                'title' => 'Asset buildout',
                'objective' => 'Build the website, starter content, and primary channel assets needed to generate attention fast.',
                'north_star_metric' => 'Website and acquisition assets published',
                'estimated_hours' => 18,
            ];
            $milestones[] = [
                'title' => 'Customer acquisition sprint',
                'objective' => 'Run the chosen channel playbook and focus on leads, bookings, or first sales instead of vanity activity.',
                'north_star_metric' => '10 qualified leads or 3 booked conversations',
                'estimated_hours' => 16,
            ];
        }

        $totalHours = array_sum(array_column($milestones, 'estimated_hours'));
        $estimatedWeeks = max(2, (int) ceil($totalHours / max(1, $effectiveHoursPerWeek)));
        $channels = $budgetStrategy === 'paid'
            ? ['website', 'meta_ads', 'google_ads', 'email_capture']
            : ['website', 'social_content', 'direct_outreach', 'email_capture'];
        $assetList = $stage === 'idea'
            ? ['Landing page', 'Email capture form', 'Starter offer', 'Social profile', 'Discovery script']
            : ['Website refresh', 'Offer page', 'Service or product pages', 'Social content kit', 'Lead capture flow'];

        $tasks = [];
        $taskDate = now();
        foreach ($milestones as $milestoneIndex => $milestone) {
            $milestoneTasks = $this->milestoneTasksForLaunchPlan($milestone['title'], $signupProfile, $deducedProfile, $channels, $budgetStrategy);
            foreach ($milestoneTasks as $taskIndex => $task) {
                $tasks[] = [
                    'title' => $task['title'],
                    'description' => $task['description'],
                    'platform' => $task['platform'],
                    'cta_label' => $task['cta_label'],
                    'cta_url' => $task['cta_url'],
                    'milestone' => $milestone['title'],
                    'north_star_metric' => $milestone['north_star_metric'],
                    'available_on' => $taskDate->copy()->addDays(($milestoneIndex * 7) + $taskIndex)->toDateString(),
                ];
            }
        }

        return [
            'title' => $stage === 'idea'
                ? 'Idea-to-demand launch plan'
                : 'Growth sprint launch plan',
            'summary' => $stage === 'idea'
                ? 'We start with customer discovery, then a lean MVP, then a demand test so the founder validates real interest before overbuilding.'
                : 'We skip pure idea validation and move straight into offer clarity, asset buildout, and customer acquisition using the fastest practical channel mix.',
            'weekly_focus' => $stage === 'idea'
                ? 'Focus on discovery first, then only build the minimum assets needed to test demand.'
                : 'Focus on one audience, one offer, and one primary acquisition channel before expanding.',
            'north_star_metrics' => array_values(array_map(fn ($milestone) => (string) $milestone['north_star_metric'], $milestones)),
            'milestones' => $milestones,
            'pace' => [
                'hours_per_week' => $hoursPerWeek,
                'effective_hours_per_week' => $effectiveHoursPerWeek,
                'estimated_total_hours' => $totalHours,
                'estimated_weeks' => $estimatedWeeks,
            ],
            'assets' => $assetList,
            'channels' => $channels,
            'channel_strategy' => $budgetStrategy === 'paid'
                ? 'Use paid distribution for speed, but still anchor the launch around a clear offer, a conversion page, and follow-up capture.'
                : 'Use organic outreach, content, and social proof to validate demand without ad spend.',
            'offer_stack' => [
                [
                    'name' => (string) ($deducedProfile['core_offer'] ?? 'Starter offer'),
                    'purpose' => 'Fastest entry point for the best customer profile.',
                ],
            ],
            'tasks' => $tasks,
            'visual_direction' => $budgetStrategy === 'paid'
                ? 'Sharp direct-response visuals with one clear CTA and channel-specific creative assets.'
                : 'Trust-building visuals with credibility, human proof, and practical clarity over hype.',
        ];
    }

    private function milestoneTasksForLaunchPlan(string $milestoneTitle, array $signupProfile, array $deducedProfile, array $channels, string $budgetStrategy): array
    {
        return match ($milestoneTitle) {
            'Customer discovery' => [
                [
                    'title' => 'Write the discovery interview script',
                    'description' => 'Prepare a short script focused on the pain points, current alternatives, and moments that trigger buying intent.',
                    'platform' => 'os',
                    'cta_label' => 'Open Tasks',
                    'cta_url' => '/tasks',
                ],
                [
                    'title' => 'List 10 ideal people to interview',
                    'description' => 'Build a named list of likely buyers who match ' . (string) ($deducedProfile['primary_icp_name'] ?? 'the core ICP') . ' and schedule outreach.',
                    'platform' => 'os',
                    'cta_label' => 'Open First 100',
                    'cta_url' => '/first-100',
                ],
                [
                    'title' => 'Run the first discovery calls',
                    'description' => 'Capture repeated pains, exact language, objections, and proof that the problem is urgent enough to solve.',
                    'platform' => 'os',
                    'cta_label' => 'Open Inbox',
                    'cta_url' => '/inbox',
                ],
            ],
            'Lean MVP setup' => [
                [
                    'title' => 'Build the first landing page',
                    'description' => 'Create a simple page that presents the problem, the promise, the starter offer, and one conversion action.',
                    'platform' => 'servio',
                    'cta_label' => 'Open Website',
                    'cta_url' => '/website',
                ],
                [
                    'title' => 'Set up lead capture and CTA logic',
                    'description' => 'Choose one CTA flow and connect forms, booking, or checkout so demand can be measured cleanly.',
                    'platform' => 'servio',
                    'cta_label' => 'Open Website',
                    'cta_url' => '/website',
                ],
                [
                    'title' => 'Create the first social proof assets',
                    'description' => 'Prepare the minimum social page, intro copy, and credibility blocks needed to make the MVP trustworthy.',
                    'platform' => 'atlas',
                    'cta_label' => 'Open AI Studio',
                    'cta_url' => '/ai-tools',
                ],
            ],
            'Demand validation' => [
                [
                    'title' => 'Launch the first acquisition channel',
                    'description' => $budgetStrategy === 'paid'
                        ? 'Set up and launch the first paid campaign with one offer, one audience, and one landing page.'
                        : 'Start direct outreach and organic content using one clear message and one CTA.',
                    'platform' => 'os',
                    'cta_label' => 'Open Marketing',
                    'cta_url' => '/marketing',
                ],
                [
                    'title' => 'Track every lead response',
                    'description' => 'Measure discoverability through real signals: replies, bookings, opt-ins, and conversions.',
                    'platform' => 'os',
                    'cta_label' => 'Open First 100',
                    'cta_url' => '/first-100',
                ],
                [
                    'title' => 'Refine the page from real objections',
                    'description' => 'Use what prospects actually say to sharpen the headline, CTA, and offer stack before scaling.',
                    'platform' => 'servio',
                    'cta_label' => 'Open Website',
                    'cta_url' => '/website',
                ],
            ],
            'Positioning reset' => [
                [
                    'title' => 'Lock the core offer and ICP',
                    'description' => 'Refine the offer, differentiators, and best customer so every downstream asset stays consistent.',
                    'platform' => 'os',
                    'cta_label' => 'Open Tasks',
                    'cta_url' => '/tasks',
                ],
                [
                    'title' => 'Write the direct-response message',
                    'description' => 'Turn the main pain, desired outcome, and objections into a clear promise and practical CTA.',
                    'platform' => 'atlas',
                    'cta_label' => 'Open AI Studio',
                    'cta_url' => '/ai-tools',
                ],
            ],
            'Asset buildout' => [
                [
                    'title' => 'Build the website assets',
                    'description' => 'Create the website, conversion sections, service or product pages, and supporting content blocks.',
                    'platform' => 'servio',
                    'cta_label' => 'Open Website',
                    'cta_url' => '/website',
                ],
                [
                    'title' => 'Set up the primary channel presence',
                    'description' => 'Publish the core social or outreach assets required for the first acquisition sprint.',
                    'platform' => 'os',
                    'cta_label' => 'Open Marketing',
                    'cta_url' => '/marketing',
                ],
            ],
            default => [
                [
                    'title' => 'Launch the first acquisition sprint',
                    'description' => 'Use ' . implode(', ', $channels) . ' as the focused channel mix and chase the first real lead or booking signals.',
                    'platform' => 'os',
                    'cta_label' => 'Open Marketing',
                    'cta_url' => '/marketing',
                ],
                [
                    'title' => 'Track outcomes weekly',
                    'description' => 'Review the north-star metric weekly and adjust only what affects qualified leads, bookings, or first sales.',
                    'platform' => 'os',
                    'cta_label' => 'Open Tasks',
                    'cta_url' => '/tasks',
                ],
            ],
        };
    }

    private function deduceFounderSignupProfile(array $validated): array
    {
        $fallback = $this->fallbackFounderSignupProfile($validated);
        $aiProfile = $this->requestOpenAiFounderSignupProfile($validated, $fallback);

        return $this->normalizeFounderSignupProfile($validated, array_merge($fallback, $aiProfile));
    }

    private function fallbackFounderSignupProfile(array $validated): array
    {
        $description = trim((string) ($validated['company_description'] ?? ''));
        $icp = trim((string) ($validated['ideal_customer_profile'] ?? ''));
        $combined = Str::lower($description . ' ' . $icp . ' ' . (string) ($validated['company_name'] ?? ''));
        $businessModel = (string) ($validated['business_model'] ?? 'service');

        $verticalBlueprint = match (true) {
            str_contains($combined, 'yoga') || str_contains($combined, 'wellness') || str_contains($combined, 'meditation') => 'yoga-wellness-studio',
            str_contains($combined, 'dog') || str_contains($combined, 'pet') => 'dog-walking',
            str_contains($combined, 'clean') => 'home-cleaning',
            str_contains($combined, 'barber') || str_contains($combined, 'haircut') => 'barber-services',
            str_contains($combined, 'coach') || str_contains($combined, 'tutor') || str_contains($combined, 'training') => 'tutoring-coaching',
            $businessModel === 'product' => 'handmade-products',
            default => 'tutoring-coaching',
        };

        $industry = match ($verticalBlueprint) {
            'yoga-wellness-studio' => 'Health and wellness',
            'dog-walking', 'home-cleaning', 'barber-services' => 'Professional services',
            'tutoring-coaching' => 'Education and training',
            'handmade-products' => 'E-commerce and retail',
            default => 'Professional services',
        };

        $targetAudience = match (true) {
            str_contains($combined, 'business') || str_contains($combined, 'team') || str_contains($combined, 'company') => 'Small businesses / SMB',
            str_contains($combined, 'local') || str_contains($combined, 'neighborhood') => 'Local community / Neighborhood market',
            default => 'Consumers / B2C',
        };

        $brandVoice = match (true) {
            str_contains($combined, 'luxury') || str_contains($combined, 'premium') => 'Premium and polished',
            str_contains($combined, 'bold') || str_contains($combined, 'energetic') => 'Bold and energetic',
            str_contains($combined, 'calm') || str_contains($combined, 'welcoming') || str_contains($combined, 'wellness') => 'Warm and supportive',
            default => 'Professional and credible',
        };

        $coreOffer = match ($businessModel) {
            'product' => 'Physical products',
            'hybrid' => 'Hybrid offer',
            default => str_contains($combined, 'class') || str_contains($combined, 'session') ? 'Group programs' : '1:1 services',
        };

        $primaryIcpName = Str::limit(trim($icp) !== '' ? trim($icp) : 'Ideal customer for ' . (string) $validated['company_name'], 120, '');

        return [
            'vertical_blueprint' => $verticalBlueprint,
            'industry' => $industry,
            'target_audience' => $targetAudience,
            'primary_icp_name' => $primaryIcpName,
            'problem_solved' => Str::limit($description !== '' ? $description : ('We help ' . $primaryIcpName), 500, ''),
            'brand_voice' => $brandVoice,
            'differentiators' => 'Built around the founder perspective, offer, and customer context captured during signup.',
            'core_offer' => $coreOffer,
            'pain_points' => $this->extractFounderSignupBullets($icp, ['overwhelm', 'wasted time', 'unclear options']),
            'desired_outcomes' => $this->extractFounderSignupBullets($description, ['clarity', 'confidence', 'better results']),
            'objections' => ['Need more trust', 'Need clearer pricing', 'Need a simpler first step'],
            'primary_city' => $this->inferPrimaryCity($description . ' ' . $icp),
            'service_radius' => $this->inferServiceRadius($description . ' ' . $icp, $businessModel),
            'location_country' => '',
        ];
    }

    private function requestOpenAiFounderSignupProfile(array $validated, array $fallback): array
    {
        /** @var OpenAiClientService $openAi */
        $openAi = app(OpenAiClientService::class);
        if (!$openAi->hasApiKey()) {
            return [];
        }
        $blueprints = collect($this->verticalBlueprintDefinitions())
            ->map(fn (array $definition): array => [
                'code' => (string) $definition['code'],
                'name' => (string) $definition['name'],
                'business_model' => (string) $definition['business_model'],
                'engine' => (string) $definition['engine'],
                'description' => (string) $definition['description'],
            ])
            ->values()
            ->all();

        $prompt = [
            'task' => 'Infer a high-quality company intelligence profile from a minimal founder signup.',
            'instructions' => [
                'Return JSON only.',
                'Choose the closest vertical_blueprint from the provided blueprint codes.',
                'Infer concise, commercially useful fields for website generation and company intelligence.',
                'Do not invent hyper-specific facts like addresses, prices, certifications, or years of experience.',
                'If location is not explicit, leave primary_city empty and choose a sensible service_radius.',
                'Make copy practical, specific, and founder-trustworthy, not generic startup fluff.',
            ],
            'allowed_values' => [
                'business_model' => array_keys($this->founderBusinessModelOptions()),
                'industry' => $this->founderIndustryOptions(),
                'target_audience' => $this->founderTargetAudienceOptions(),
                'brand_voice' => $this->founderBrandVoiceOptions(),
                'core_offer' => $this->founderCoreOfferOptions(),
                'vertical_blueprint_codes' => array_keys($this->verticalBlueprintDefinitions()),
            ],
            'signup' => [
                'company_name' => $validated['company_name'],
                'company_description' => $validated['company_description'],
                'ideal_customer_profile' => $validated['ideal_customer_profile'],
                'business_model' => $validated['business_model'],
                'stage' => $validated['stage'],
                'primary_growth_goal' => $validated['primary_growth_goal'],
                'known_blockers' => $validated['known_blockers'],
            ],
            'fallback' => $fallback,
            'blueprints' => $blueprints,
            'response_schema' => [
                'vertical_blueprint' => 'string',
                'industry' => 'string',
                'target_audience' => 'string',
                'primary_icp_name' => 'string',
                'problem_solved' => 'string',
                'brand_voice' => 'string',
                'differentiators' => 'string',
                'core_offer' => 'string',
                'pain_points' => ['string'],
                'desired_outcomes' => ['string'],
                'objections' => ['string'],
                'primary_city' => 'string',
                'service_radius' => 'string',
                'location_country' => 'string',
            ],
        ];

        return $openAi->requestJsonObject(
            'You are a founder onboarding intelligence engine. Produce practical, structured business intelligence for website generation.',
            json_encode($prompt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'chat_model',
            'gpt-5.5',
            null,
            40
        );
    }

    private function normalizeFounderSignupProfile(array $validated, array $profile): array
    {
        $fallback = $this->fallbackFounderSignupProfile($validated);
        $blueprintCode = (string) ($profile['vertical_blueprint'] ?? '');
        if (!array_key_exists($blueprintCode, $this->verticalBlueprintDefinitions())) {
            $blueprintCode = $fallback['vertical_blueprint'];
        }

        $industry = (string) ($profile['industry'] ?? '');
        if (!in_array($industry, $this->founderIndustryOptions(), true)) {
            $industry = $fallback['industry'];
        }

        $targetAudience = (string) ($profile['target_audience'] ?? '');
        if (!in_array($targetAudience, $this->founderTargetAudienceOptions(), true)) {
            $targetAudience = $fallback['target_audience'];
        }

        $brandVoice = (string) ($profile['brand_voice'] ?? '');
        if (!in_array($brandVoice, $this->founderBrandVoiceOptions(), true)) {
            $brandVoice = $fallback['brand_voice'];
        }

        $coreOffer = (string) ($profile['core_offer'] ?? '');
        if (!in_array($coreOffer, $this->founderCoreOfferOptions(), true)) {
            $coreOffer = $fallback['core_offer'];
        }

        $painPoints = $this->sanitizeFounderSignupList(is_array($profile['pain_points'] ?? null) ? $profile['pain_points'] : []);
        if ($painPoints === []) {
            $painPoints = array_values((array) ($fallback['pain_points'] ?? []));
        }

        $desiredOutcomes = $this->sanitizeFounderSignupList(is_array($profile['desired_outcomes'] ?? null) ? $profile['desired_outcomes'] : []);
        if ($desiredOutcomes === []) {
            $desiredOutcomes = array_values((array) ($fallback['desired_outcomes'] ?? []));
        }

        $objections = $this->sanitizeFounderSignupList(is_array($profile['objections'] ?? null) ? $profile['objections'] : []);
        if ($objections === []) {
            $objections = array_values((array) ($fallback['objections'] ?? []));
        }

        return [
            'vertical_blueprint' => $blueprintCode,
            'industry' => $industry,
            'target_audience' => $targetAudience,
            'primary_icp_name' => Str::limit(trim((string) ($profile['primary_icp_name'] ?? $fallback['primary_icp_name'] ?? 'Ideal customer')), 255, ''),
            'problem_solved' => Str::limit(trim((string) ($profile['problem_solved'] ?? $validated['company_description'])), 1000, ''),
            'brand_voice' => $brandVoice,
            'differentiators' => Str::limit(trim((string) ($profile['differentiators'] ?? $fallback['differentiators'] ?? '')), 1000, ''),
            'core_offer' => $coreOffer,
            'pain_points' => $painPoints,
            'desired_outcomes' => $desiredOutcomes,
            'objections' => $objections,
            'primary_city' => Str::limit(trim((string) ($profile['primary_city'] ?? $fallback['primary_city'] ?? '')), 191, ''),
            'service_radius' => Str::limit(trim((string) ($profile['service_radius'] ?? $fallback['service_radius'] ?? '')), 191, ''),
            'location_country' => Str::limit(trim((string) ($profile['location_country'] ?? '')), 120, ''),
        ];
    }

    private function atlasFounderOnboardingPayload(array $validated, array $profile, VerticalBlueprint $blueprint): array
    {
        return [
            'company_name' => $validated['company_name'],
            'business_model' => $validated['business_model'],
            'vertical_blueprint' => $profile['vertical_blueprint'],
            'primary_city' => $profile['primary_city'],
            'service_radius' => $profile['service_radius'],
            'industry' => $profile['industry'],
            'stage' => $validated['stage'],
            'target_audience' => $profile['target_audience'],
            'primary_icp_name' => $profile['primary_icp_name'],
            'ideal_customer_profile' => $validated['ideal_customer_profile'],
            'pain_points' => implode(', ', $profile['pain_points']),
            'desired_outcomes' => implode(', ', $profile['desired_outcomes']),
            'objections' => implode(', ', $profile['objections']),
            'brand_voice' => $profile['brand_voice'],
            'differentiators' => $profile['differentiators'],
            'problem_solved' => $profile['problem_solved'],
            'core_offer' => $profile['core_offer'],
            'primary_growth_goal' => $validated['primary_growth_goal'],
            'known_blockers' => $validated['known_blockers'],
            'company_brief' => $validated['company_description'],
            'business_type_detail' => $blueprint->name,
        ];
    }

    private function localMarketNotesFromProfile(array $profile): string
    {
        $parts = array_values(array_filter([
            trim((string) ($profile['primary_city'] ?? '')),
            trim((string) ($profile['service_radius'] ?? '')),
        ]));

        return $parts !== [] ? implode(' · ', $parts) : 'Market inferred from founder signup';
    }

    private function founderSignupFailureMessage(Throwable $exception, string $reference): string
    {
        $message = Str::lower(trim($exception->getMessage()));

        if (Str::contains($message, ['duplicate', 'unique', 'already exists', 'integrity constraint'])) {
            return 'We could not create this founder account because that email or username is already in use. Please try a different email address. Ref: ' . $reference;
        }

        if (Str::contains($message, ['verification', 'mail', 'smtp'])) {
            return 'Your founder account could not finish setup because the verification email step failed. Please try again in a moment. Ref: ' . $reference;
        }

        if (Str::contains($message, ['column', 'field', 'cannot be null', 'undefined array key'])) {
            return 'Your founder account could not finish setup because some required setup data was missing on our side. Please try again now that we have refreshed the signup flow. Ref: ' . $reference;
        }

        if (Str::contains($message, ['timeout', 'connection', 'network', 'http'])) {
            return 'Your founder account could not finish setup because one of our background services did not respond in time. Please try again in a moment. Ref: ' . $reference;
        }

        return 'Hatchers AI could not complete signup right now because a workspace setup step failed on our side. Please try again, and if it happens again share this reference with us: ' . $reference;
    }

    private function sanitizeFounderSignupList(mixed $values): array
    {
        return collect(is_array($values) ? $values : [])
            ->map(fn ($value): string => trim((string) $value))
            ->filter()
            ->unique(fn (string $value): string => Str::lower($value))
            ->take(6)
            ->values()
            ->all();
    }

    private function extractFounderSignupBullets(string $source, array $fallback): array
    {
        $parts = preg_split('/[\r\n,.;]+/', $source) ?: [];
        $values = collect($parts)
            ->map(fn (string $item): string => trim($item))
            ->filter(fn (string $item): bool => Str::length($item) >= 4)
            ->take(5)
            ->values()
            ->all();

        return $values !== [] ? $values : $fallback;
    }

    private function inferPrimaryCity(string $text): string
    {
        if (preg_match('/\b(?:in|based in|serving)\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]+){0,2})/', $text, $matches)) {
            return trim((string) ($matches[1] ?? ''));
        }

        return '';
    }

    private function inferServiceRadius(string $text, string $businessModel): string
    {
        $lower = Str::lower($text);

        if (str_contains($lower, 'nationwide') || str_contains($lower, 'online')) {
            return 'nationwide';
        }

        if (str_contains($lower, 'local') || str_contains($lower, 'studio') || str_contains($lower, 'in-person')) {
            return 'local area';
        }

        return $businessModel === 'product' ? 'nationwide shipping' : 'local area';
    }

    private function verticalBlueprintDefinitions(): array
    {
        return [
            'yoga-wellness-studio' => [
                'code' => 'yoga-wellness-studio',
                'name' => 'Yoga / Wellness Studio',
                'business_model' => 'service',
                'engine' => 'servio',
                'description' => 'A class-based wellness booking system focused on first bookings, memberships, trust, and repeat visits.',
                'default_offer_json' => ['core_offer' => 'Group programs', 'upsells' => ['Starter pack', 'Private session', 'Membership']],
                'default_pricing_json' => ['tier_1' => 'Drop-in class', 'tier_2' => 'Starter pack', 'tier_3' => 'Monthly membership'],
                'default_pages_json' => ['hero', 'class_menu', 'why_choose_us', 'about', 'faq', 'booking_cta'],
                'default_tasks_json' => ['Clarify the starter class offer', 'Publish trust-building visuals', 'Review class flow and pricing'],
                'default_channels_json' => ['Instagram', 'Google Business Profile', 'WhatsApp', 'Local SEO'],
                'default_cta_json' => ['primary' => 'Book your first class', 'secondary' => 'See class plans'],
                'default_image_queries_json' => ['yoga studio natural light', 'wellness stretching class', 'calm yoga indoor'],
                'funnel_framework_json' => ['Problem', 'Promise', 'Offer stack', 'Proof', 'FAQ', 'Booking CTA'],
                'pricing_preset_json' => ['tier_1' => 'Drop-in class', 'tier_2' => 'Starter pack', 'tier_3' => 'Monthly membership'],
                'channel_playbook_json' => ['Instagram', 'Google Business Profile', 'Local SEO'],
                'script_library_json' => ['First class invitation', 'Beginner reassurance follow-up', 'Membership close'],
            ],
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
            '/ai-images-hub',
            '/ai-images',
            '/ai-images/campaign',
            '/ai-images/campaign-detail',
            '/ai-images/grid',
            '/campaign-studio',
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

    private function syncCompanyWebsitePathFromEnginePublicUrl(Company $company, string $publicUrl, string $engine): void
    {
        $publicUrl = trim($publicUrl);
        if ($publicUrl === '') {
            return;
        }

        $canonicalPath = $this->extractCanonicalWebsitePathFromEnginePublicUrl($publicUrl, $engine);
        if ($canonicalPath === '') {
            return;
        }

        $company->website_path = $canonicalPath;
        $company->website_url = $this->buildCompanyWebsiteUrl($company, $engine);
    }

    private function extractCanonicalWebsitePathFromEnginePublicUrl(string $publicUrl, string $engine): string
    {
        $publicUrl = trim($publicUrl);
        if ($publicUrl === '') {
            return '';
        }

        $path = trim((string) parse_url($publicUrl, PHP_URL_PATH), '/');
        if ($path !== '') {
            $segments = array_values(array_filter(explode('/', strtolower($path))));
            if (!empty($segments)) {
                return trim((string) $segments[0], '/');
            }
        }

        $publicHost = strtolower((string) parse_url($publicUrl, PHP_URL_HOST));
        $engineBaseUrl = rtrim((string) config('modules.' . strtolower(trim($engine)) . '.base_url', ''), '/');
        $engineHost = strtolower((string) parse_url($engineBaseUrl, PHP_URL_HOST));
        if ($publicHost !== '' && $engineHost !== '' && $publicHost !== $engineHost) {
            $suffix = '.' . $engineHost;
            if (str_ends_with($publicHost, $suffix)) {
                return trim((string) substr($publicHost, 0, -strlen($suffix)), '/');
            }
        }

        return '';
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

    private function websiteBuildEngineLabel(Company $company, string $focus): string
    {
        $focus = strtolower(trim($focus));
        $businessModel = strtolower(trim((string) ($company->business_model ?? 'hybrid')));

        if ($focus === 'product') {
            return 'bazaar';
        }

        if ($focus === 'service') {
            return 'servio';
        }

        return $businessModel === 'product' ? 'bazaar' : 'servio';
    }

    private function normalizeWebsiteBuildOfferCards(array $titles, array $prices, array $descriptions): array
    {
        $rows = max(count($titles), count($prices), count($descriptions));
        $cards = [];

        for ($index = 0; $index < $rows; $index++) {
            $title = trim((string) ($titles[$index] ?? ''));
            $price = trim((string) ($prices[$index] ?? ''));
            $description = trim((string) ($descriptions[$index] ?? ''));

            if ($title === '' && $price === '' && $description === '') {
                continue;
            }

            if ($title === '') {
                continue;
            }

            $cards[] = [
                'title' => Str::limit(preg_replace('/\s+/', ' ', $title) ?: $title, 180, ''),
                'price' => Str::limit(preg_replace('/\s+/', ' ', $price) ?: $price, 120, ''),
                'description' => Str::limit(preg_replace('/\s+/', ' ', $description) ?: $description, 500, ''),
            ];
        }

        return array_slice($cards, 0, 6);
    }

    private function normalizeWebsiteBuildList(array $items): array
    {
        return collect($items)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->map(function (string $item): string {
                $normalized = preg_replace('/\s+/', ' ', $item) ?: $item;
                return Str::limit($normalized, 255, '');
            })
            ->unique()
            ->values()
            ->take(8)
            ->all();
    }

    private function normalizeWebsiteBuildPageSections(array $items): array
    {
        $allowed = [
            'hero', 'about', 'offers', 'services', 'products', 'pricing',
            'results', 'testimonials', 'faq', 'contact', 'gallery',
        ];

        return collect($items)
            ->map(fn ($item) => strtolower(trim((string) $item)))
            ->filter(fn (string $item) => in_array($item, $allowed, true))
            ->unique()
            ->values()
            ->take(8)
            ->all();
    }

    private function normalizeWebsiteBuildSocialProfiles(array $validated): array
    {
        $map = [
            'instagram_url' => 'Instagram',
            'facebook_url' => 'Facebook',
            'tiktok_url' => 'TikTok',
            'linkedin_url' => 'LinkedIn',
            'youtube_url' => 'YouTube',
            'website_url' => 'Website',
        ];

        $profiles = [];

        foreach ($map as $field => $network) {
            $url = trim((string) ($validated[$field] ?? ''));
            if ($url === '') {
                continue;
            }

            $profiles[] = [
                'network' => $network,
                'url' => Str::limit($url, 255, ''),
            ];
        }

        return $profiles;
    }

    private function normalizeWebsiteBuildImageDirection(array $validated): array
    {
        $subjects = collect((array) ($validated['image_subjects'] ?? []))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->take(8)
            ->all();

        $avoid = collect((array) ($validated['avoid_visuals'] ?? []))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->take(8)
            ->all();

        return array_filter([
            'style' => trim((string) ($validated['image_style'] ?? '')),
            'mood' => trim((string) ($validated['image_mood'] ?? '')),
            'subjects' => $subjects,
            'avoid' => $avoid,
        ], function ($value): bool {
            if (is_array($value)) {
                return $value !== [];
            }

            return trim((string) $value) !== '';
        });
    }

    private function websiteBuildOfferCardsToText(array $cards, string $fallback = ''): string
    {
        $lines = collect($cards)
            ->filter(fn ($card): bool => is_array($card) && trim((string) ($card['title'] ?? '')) !== '')
            ->map(function (array $card): string {
                return implode(' | ', array_values(array_filter([
                    trim((string) ($card['title'] ?? '')),
                    trim((string) ($card['price'] ?? '')),
                    trim((string) ($card['description'] ?? '')),
                ], fn ($value): bool => $value !== '')));
            })
            ->filter()
            ->values()
            ->all();

        return $lines !== [] ? implode("\n", $lines) : trim($fallback);
    }

    private function websiteBuildListToText(array $items, string $fallback = ''): string
    {
        $items = collect($items)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();

        return $items !== [] ? implode("\n", $items) : trim($fallback);
    }

    private function websiteBuildPageSectionsToText(array $sections, string $fallback = ''): string
    {
        $labels = [
            'hero' => 'Hero',
            'about' => 'About',
            'offers' => 'Offers',
            'services' => 'Services',
            'products' => 'Products',
            'pricing' => 'Pricing',
            'results' => 'Results',
            'testimonials' => 'Testimonials',
            'faq' => 'FAQ',
            'contact' => 'Contact',
            'gallery' => 'Gallery',
        ];

        $items = collect($sections)
            ->map(fn ($item) => $labels[strtolower(trim((string) $item))] ?? trim((string) $item))
            ->filter()
            ->values()
            ->all();

        return $items !== [] ? implode("\n", $items) : trim($fallback);
    }

    private function websiteBuildSocialProfilesToText(array $profiles, string $fallback = ''): string
    {
        $lines = collect($profiles)
            ->filter(fn ($profile): bool => is_array($profile) && trim((string) ($profile['url'] ?? '')) !== '')
            ->map(function (array $profile): string {
                $network = trim((string) ($profile['network'] ?? ''));
                $url = trim((string) ($profile['url'] ?? ''));
                return $network !== '' ? $network . ': ' . $url : $url;
            })
            ->filter()
            ->values()
            ->all();

        return $lines !== [] ? implode("\n", $lines) : trim($fallback);
    }

    private function websiteBuildImageDirectionToText(array $imageDirection, string $fallback = ''): string
    {
        $lines = [];
        $style = trim((string) ($imageDirection['style'] ?? ''));
        $mood = trim((string) ($imageDirection['mood'] ?? ''));
        $subjects = array_values(array_filter(array_map('trim', (array) ($imageDirection['subjects'] ?? []))));
        $avoid = array_values(array_filter(array_map('trim', (array) ($imageDirection['avoid'] ?? []))));

        if ($style !== '') {
            $lines[] = 'Style: ' . $style;
        }
        if ($mood !== '') {
            $lines[] = 'Mood: ' . $mood;
        }
        if ($subjects !== []) {
            $lines[] = 'Show: ' . implode(', ', $subjects);
        }
        if ($avoid !== []) {
            $lines[] = 'Avoid: ' . implode(', ', $avoid);
        }

        return $lines !== [] ? implode("\n", $lines) : trim($fallback);
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
