<?php

namespace App\Http\Controllers;

use App\Models\CommercialSummary;
use App\Models\Company;
use App\Models\CompanyIntelligence;
use App\Models\Founder;
use App\Models\FounderActionPlan;
use App\Models\FounderWeeklyState;
use App\Models\OsAutomationRule;
use App\Models\OsOperationException;
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
use App\Services\OsOperationsLogService;
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

        return view('os.activity', [
            'pageTitle' => 'Activity',
            'dashboard' => $founderDashboardService->build($user),
        ]);
    }

    public function founderLearningPlan(FounderDashboardService $founderDashboardService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
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

        return view('os.tasks', [
            'pageTitle' => 'Tasks',
            'dashboard' => $founderDashboardService->build($user),
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

    public function adminSupport(AdminDashboardService $adminDashboardService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        $this->ensureAdminPermission($user, 'exception_resolution');

        return view('os.admin-support', [
            'pageTitle' => 'Support Center',
            'workspace' => $adminDashboardService->buildSupportWorkspace($user),
        ]);
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
        WorkspaceLaunchService $workspaceLaunchService
    ) {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        return view('os.ai-tools', [
            'pageTitle' => 'AI Tools',
            'dashboard' => $founderDashboardService->build($user),
            'launchCards' => $workspaceLaunchService->launchCards($user),
        ]);
    }

    public function founderSearch(Request $request, FounderDashboardService $founderDashboardService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
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
        WebsiteWorkspaceService $websiteWorkspaceService
    ) {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $dashboard = $founderDashboardService->build($user);

        return view('os.media-library', [
            'pageTitle' => 'Media Library',
            'dashboard' => $dashboard,
            'website' => $websiteWorkspaceService->build($user),
            'assets' => $this->buildFounderMediaAssets($user, $dashboard),
        ]);
    }

    public function founderAnalytics(FounderDashboardService $founderDashboardService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
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

        return view('os.automations', [
            'pageTitle' => 'Automations',
            'dashboard' => $founderDashboardService->build($user),
            'automations' => $user->automationRules()->latest()->get(),
            'triggerOptions' => $this->automationTriggerOptions(),
            'scopeOptions' => $this->automationScopeOptions(),
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
        WorkspaceLaunchService $workspaceLaunchService
    ) {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        return view('os.commerce', [
            'pageTitle' => 'Launch Plan',
            'dashboard' => $founderDashboardService->build($user),
            'website' => $websiteWorkspaceService->build($user),
            'launchCards' => $workspaceLaunchService->launchCards($user),
            'catalogOffers' => $this->commerceOffers($user),
        ]);
    }

    public function founderOrders(
        FounderDashboardService $founderDashboardService,
        WebsiteWorkspaceService $websiteWorkspaceService,
        WorkspaceLaunchService $workspaceLaunchService
    ) {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        return view('os.orders', [
            'pageTitle' => 'Orders',
            'dashboard' => $founderDashboardService->build($user),
            'website' => $websiteWorkspaceService->build($user),
            'launchCards' => $workspaceLaunchService->launchCards($user),
            'orderWorkspace' => $this->commerceOperationsWorkspace($user, 'bazaar'),
        ]);
    }

    public function founderBookings(
        FounderDashboardService $founderDashboardService,
        WebsiteWorkspaceService $websiteWorkspaceService,
        WorkspaceLaunchService $workspaceLaunchService
    ) {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        return view('os.bookings', [
            'pageTitle' => 'Bookings',
            'dashboard' => $founderDashboardService->build($user),
            'website' => $websiteWorkspaceService->build($user),
            'launchCards' => $workspaceLaunchService->launchCards($user),
            'bookingWorkspace' => $this->commerceOperationsWorkspace($user, 'servio'),
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
        ]);

        $offer = $this->parseCommerceOffer($actionPlan);
        $originalTitle = $actionPlan->title;
        $warnings = [];

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

        $actionPlan->forceFill([
            'title' => (string) $validated['title'],
            'description' => $this->serializeCommerceOffer([
                'type' => $offer['type'],
                'description' => (string) ($validated['description'] ?? ''),
                'price' => $newPrice,
                'engine' => $offer['engine'],
            ]),
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

    public function founderSettings(FounderDashboardService $founderDashboardService)
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        return view('os.settings', [
            'pageTitle' => 'Settings',
            'dashboard' => $founderDashboardService->build($user),
        ]);
    }

    public function founderUpdateSettings(Request $request): RedirectResponse
    {
        /** @var \App\Models\Founder $user */
        $user = Auth::user();
        if (!$user->isFounder()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company_name' => ['required', 'string', 'max:255'],
            'company_brief' => ['nullable', 'string', 'max:2000'],
            'business_model' => ['required', Rule::in(['product', 'service', 'hybrid'])],
        ]);

        $user->forceFill([
            'full_name' => (string) $validated['full_name'],
            'phone' => (string) ($validated['phone'] ?? ''),
        ])->save();

        $company = $user->company ?: Company::create([
            'founder_id' => $user->id,
            'company_name' => (string) $validated['company_name'],
            'business_model' => (string) $validated['business_model'],
            'stage' => 'idea',
            'website_status' => 'not_started',
        ]);

        $company->forceFill([
            'company_name' => (string) $validated['company_name'],
            'company_brief' => (string) ($validated['company_brief'] ?? ''),
            'business_model' => (string) $validated['business_model'],
        ])->save();

        return redirect()->route('founder.settings')->with('success', 'Founder settings updated from Hatchers Ai Business OS.');
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
            'permissions.*' => ['string', Rule::in(['subscriber_reporting', 'founder_operations', 'mentor_management', 'module_monitoring', 'exception_resolution', 'system_access'])],
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
            'target' => ['required', Rule::in(['atlas', 'bazaar', 'servio', 'all'])],
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
            'target' => ['required', Rule::in(['atlas', 'bazaar', 'servio', 'all'])],
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

        $result = $websiteProvisioningService->applyWebsiteSetup($founder, $validated);
        if (!$result['ok']) {
            return back()->with('error', $result['error'] ?? 'Hatchers OS could not save the website setup.');
        }

        if (!empty($company)) {
            $company->business_model = $validated['website_mode'];
            $company->website_status = 'in_progress';
            $company->website_path = trim(strtolower((string) $validated['website_path']), '/');
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
            $company->website_url = (string) ($result['data']['public_url'] ?? $this->buildCompanyWebsiteUrl($company, $validated['website_engine']));
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
            $company->website_url = $this->buildCompanyWebsiteUrl($company, $validated['website_engine']);
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
                        'auth_source' => 'os',
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
                    'status' => $actionPlan->status,
                    'updated_at' => optional($actionPlan->updated_at)?->diffForHumans(),
                ];
            })
            ->all();
    }

    private function commerceOperationsWorkspace(Founder $founder, string $module): array
    {
        $snapshot = $founder->moduleSnapshots()->where('module', $module)->latest('snapshot_updated_at')->first();
        $payload = $snapshot?->payload_json ?? [];
        $summary = $payload['summary'] ?? [];
        $counts = $payload['key_counts'] ?? [];
        $activity = collect($payload['recent_activity'] ?? [])
            ->filter(fn ($item) => is_string($item) && trim($item) !== '')
            ->values()
            ->all();

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
            'activity' => $activity,
        ];
    }

    private function parseCommerceOffer(FounderActionPlan $actionPlan): array
    {
        $description = (string) ($actionPlan->description ?? '');
        $lines = preg_split("/\r\n|\n|\r/", $description) ?: [];
        $price = '0.00';
        $body = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, 'Price:')) {
                $price = trim((string) substr($line, strlen('Price:')));
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
        ];
    }

    private function serializeCommerceOffer(array $payload): string
    {
        return trim(implode("\n", array_filter([
            'Type: ' . trim((string) ($payload['type'] ?? 'offer')),
            'Engine: ' . trim((string) ($payload['engine'] ?? '')),
            'Price: ' . trim((string) ($payload['price'] ?? '0.00')),
            '',
            trim((string) ($payload['description'] ?? '')),
        ], static fn ($value) => $value !== '')));
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
        $fallbackHost = preg_replace('#^https?://#', '', trim((string) $company->website_url)) ?? '';
        if ($fallbackHost === '') {
            $fallbackHost = ($company->company_name ? str($company->company_name)->slug('-')->value() : 'your-business') . '.hatchers.site';
        }

        $host = trim((string) $company->custom_domain) !== ''
            ? trim((string) $company->custom_domain)
            : $fallbackHost;
        $host = trim($host, '/');
        $path = trim((string) ($company->website_path ?? ''), '/');

        if ($host === '') {
            $host = 'your-business.hatchers.site';
        }

        if (!str_contains($host, '.')) {
            $host = $host . '.hatchers.site';
        }

        return 'https://' . $host . ($path !== '' ? '/' . $path : '');
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
                'description' => (string) ($campaign['description'] ?? 'Atlas campaign asset'),
                'source' => 'Atlas',
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
            'task_blocked' => 'When a task stays blocked',
            'new_order' => 'When a new order arrives',
            'new_booking' => 'When a new booking arrives',
            'campaign_published' => 'When campaign content is published',
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
}
