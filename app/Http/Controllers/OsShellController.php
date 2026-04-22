<?php

namespace App\Http\Controllers;

use App\Models\CommercialSummary;
use App\Models\Company;
use App\Models\CompanyIntelligence;
use App\Models\Founder;
use App\Models\FounderActionPlan;
use App\Models\FounderWeeklyState;
use App\Models\Subscription;
use App\Services\AdminDashboardService;
use App\Services\AdminOperationsService;
use App\Services\AtlasIntelligenceService;
use App\Services\FounderModuleSyncService;
use App\Services\FounderDashboardService;
use App\Services\IdentitySyncService;
use App\Services\LmsIdentityBridgeService;
use App\Services\MentorDashboardService;
use App\Services\OsAssistantActionService;
use App\Services\WebsiteProvisioningService;
use App\Services\WebsiteWorkspaceService;
use App\Services\WorkspaceLaunchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
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

    public function launchWorkspace(string $module, WorkspaceLaunchService $workspaceLaunchService): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();

        $url = $workspaceLaunchService->buildLaunchUrl($user, $module);
        if ($url === null) {
            return redirect()->route('dashboard')->with('error', 'Hatchers OS could not prepare that workspace launch yet.');
        }

        return redirect()->away($url);
    }

    public function website(WebsiteWorkspaceService $websiteWorkspaceService)
    {
        /** @var \App\Models\Founder $founder */
        $founder = Auth::user();

        return view('os.website', [
            'pageTitle' => 'Website Workspace',
            'website' => $websiteWorkspaceService->build($founder),
        ]);
    }

    public function adminControl(AdminOperationsService $adminOperationsService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403);
        }

        return view('os.admin-control', [
            'pageTitle' => 'Admin Control',
            'workspace' => $adminOperationsService->build($user),
        ]);
    }

    public function adminAssignMentor(Request $request, AdminOperationsService $adminOperationsService): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'founder_id' => ['required', 'integer', 'exists:founders,id'],
            'mentor_id' => ['nullable', 'integer', 'exists:founders,id'],
        ]);

        $adminOperationsService->assignMentor(
            (int) $validated['founder_id'],
            !empty($validated['mentor_id']) ? (int) $validated['mentor_id'] : null
        );

        return redirect()->route('admin.control')->with('success', 'Mentor assignment updated from Hatchers OS.');
    }

    public function adminUpdateSubscription(
        Request $request,
        AdminOperationsService $adminOperationsService
    ): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'founder_id' => ['required', 'integer', 'exists:founders,id'],
            'plan_code' => ['required', 'string', 'max:120'],
            'plan_name' => ['required', 'string', 'max:255'],
            'billing_status' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $adminOperationsService->updateSubscription((int) $validated['founder_id'], $validated);

        return redirect()->route('admin.control')->with('success', 'Subscription state updated from Hatchers OS.');
    }

    public function adminUpdateFounder(Request $request, AdminOperationsService $adminOperationsService): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403);
        }

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

        return redirect()->route('admin.control')->with('success', 'Founder profile updated from Hatchers OS.');
    }

    public function adminSyncFounder(
        Request $request,
        FounderModuleSyncService $founderModuleSyncService
    ): RedirectResponse {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'founder_id' => ['required', 'integer', 'exists:founders,id'],
            'target' => ['required', Rule::in(['atlas', 'bazaar', 'servio', 'all'])],
        ]);

        $founder = Founder::query()->with('company')->findOrFail((int) $validated['founder_id']);
        $result = $founderModuleSyncService->syncFounder($founder, $validated['target']);

        return redirect()->route('admin.control')->with(
            empty($result['ok']) ? 'error' : 'success',
            $result['message'] ?? 'Founder sync completed.'
        );
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
            'theme_template' => ['required', 'string', 'max:20'],
        ]);

        if ($validated['website_mode'] === 'product') {
            $validated['website_engine'] = 'bazaar';
        } elseif ($validated['website_mode'] === 'service') {
            $validated['website_engine'] = 'servio';
        }

        $result = $websiteProvisioningService->applyWebsiteSetup($founder, $validated);
        if (!$result['ok']) {
            return back()->with('error', $result['error'] ?? 'Hatchers OS could not save the website setup.');
        }

        if (!empty($company)) {
            $company->business_model = $validated['website_mode'];
            $company->website_status = 'in_progress';
            if (!empty($result['public_url'])) {
                $company->website_url = $result['public_url'];
            }
            $company->save();
        }

        return redirect()->route('website')->with('success', 'Website setup saved. Hatchers OS updated the underlying website engine for you.');
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

        $result = $websiteProvisioningService->publishWebsite($founder, $validated['website_engine']);
        if (!$result['ok']) {
            return back()->with('error', $result['error'] ?? 'Hatchers OS could not publish the website.');
        }

        if (!empty($company)) {
            $company->website_status = 'live';
            $company->website_url = (string) ($result['data']['public_url'] ?? $company->website_url);
            $company->save();
        }

        return redirect()->route('website')->with('success', 'Website published from Hatchers OS.');
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

        return redirect()->route('website')->with('success', 'Starter ' . $validated['starter_mode'] . ' created from Hatchers OS.');
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

        $result = $websiteProvisioningService->connectCustomDomain($founder, $validated);
        if (!$result['ok']) {
            return back()->with('error', $result['error'] ?? 'Hatchers OS could not save the custom domain request.');
        }

        if (!empty($company)) {
            $company->website_engine = $validated['website_engine'];
            $company->custom_domain = (string) ($result['domain'] ?? $validated['custom_domain']);
            $company->custom_domain_status = 'pending_dns';
            $company->save();
        }

        return redirect()->route('website')->with(
            'success',
            'Custom domain saved in Hatchers OS. Point it to ' . ($result['dns_target'] ?? 'the platform host') . ' to complete connection.'
        );
    }

    public function storeOnboarding(Request $request, AtlasIntelligenceService $atlas): RedirectResponse
    {
        $validated = $request->validate([
            'plan_code' => ['required', Rule::in(array_keys($this->founderSignupPlans()))],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:founders,email'],
            'username' => ['required', 'string', 'max:255', 'unique:founders,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'company_name' => ['required', 'string', 'max:255'],
            'business_model' => ['required', 'in:product,service,hybrid'],
            'industry' => ['required', Rule::in($this->founderIndustryOptions())],
            'stage' => ['required', 'in:idea,launching,operating,scaling'],
            'target_audience' => ['required', 'string', 'max:255'],
            'ideal_customer_profile' => ['required', 'string', 'max:1000'],
            'brand_voice' => ['required', 'string', 'max:255'],
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

        try {
            DB::transaction(function () use ($validated, $atlas, $plan) {
                $founder = Founder::create(
                    [
                        'username' => $validated['username'],
                        'email' => $validated['email'],
                        'full_name' => $validated['full_name'],
                        'password' => Hash::make($validated['password']),
                        'status' => 'active',
                        'role' => 'founder',
                        'timezone' => 'Africa/Cairo',
                        'mentor_entitled_until' => !empty($plan['mentor_months']) ? now()->addMonths((int) $plan['mentor_months']) : null,
                    ]
                );

                $company = Company::updateOrCreate(
                    ['founder_id' => $founder->id],
                    [
                        'company_name' => $validated['company_name'],
                        'business_model' => $validated['business_model'],
                        'industry' => $validated['industry'],
                        'stage' => $validated['stage'],
                        'website_status' => 'not_started',
                        'company_brief' => $validated['company_brief'],
                    ]
                );

                CompanyIntelligence::updateOrCreate(
                    ['company_id' => $company->id],
                    [
                        'target_audience' => $validated['target_audience'],
                        'ideal_customer_profile' => $validated['ideal_customer_profile'],
                        'brand_voice' => $validated['brand_voice'],
                        'core_offer' => $validated['core_offer'],
                        'primary_growth_goal' => $validated['primary_growth_goal'],
                        'known_blockers' => $validated['known_blockers'],
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
                        'description' => 'Refine your audience, offer, and positioning so Atlas can guide the rest of the OS accurately.',
                        'platform' => 'atlas',
                        'priority' => 95,
                        'cta_label' => 'Open Atlas',
                        'cta_url' => '/dashboard',
                    ],
                    [
                        'title' => 'Choose your website path',
                        'description' => 'Start with the right website engine based on your business model.',
                        'platform' => $validated['business_model'] === 'service' ? 'servio' : 'bazaar',
                        'priority' => 88,
                        'cta_label' => 'Open Website Workspace',
                        'cta_url' => '/website',
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

        return redirect()->route('login')->with(
            'success',
            'Your founder workspace has been created under the ' . $plan['name'] . ' plan. Please log in to continue.'
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
        } catch (Throwable $exception) {
            Log::error('OS login failed unexpectedly.', [
                'login' => $credentials['login'],
                'message' => $exception->getMessage(),
            ]);

            return back()
                ->withErrors(['login' => 'Hatchers OS could not complete login right now. Please try again in a moment.'])
                ->onlyInput('login');
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
        OsAssistantActionService $actionService
    ): JsonResponse
    {
        /** @var \App\Models\Founder $founder */
        $founder = Auth::user();

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'current_page' => ['nullable', 'string', 'max:255'],
        ]);

        $message = trim((string) $validated['message']);
        $currentPage = trim((string) ($validated['current_page'] ?? 'os_dashboard'));

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

            return response()->json([
                'success' => true,
                'reply' => $actionResult['reply'] ?? '',
                'actions' => $actionResult['actions'] ?? [],
                'refresh' => !empty($actionResult['executed']),
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

        return response()->json([
            'success' => true,
            'reply' => $result['reply'] ?? '',
            'actions' => $result['actions'] ?? [],
            'refresh' => false,
        ]);
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

    private function resolveFounderSignupPlan(string $planCode): ?array
    {
        $plans = $this->founderSignupPlans();

        return $plans[$planCode] ?? null;
    }
}
