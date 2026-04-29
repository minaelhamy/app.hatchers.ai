<?php

namespace App\Services;

use App\Models\Founder;
use App\Models\ModuleSnapshot;

class WebsiteWorkspaceService
{
    public function __construct(
        private WebsiteProvisioningService $websiteProvisioningService,
        private WebsiteAutopilotService $websiteAutopilotService
    )
    {
    }

    public function build(Founder $founder): array
    {
        $company = $founder->company;
        $autopilotDraft = $this->websiteAutopilotService->latestDraft($company);
        $latestLaunchSystem = $company?->launchSystems()->latest('id')->first();
        $latestIcp = $company ? $company->icpProfiles()->latest()->first() : null;
        $intelligence = $company?->intelligence;
        $businessBrief = $founder->businessBrief;
        $websiteBuild = $this->websiteBuildPayload($businessBrief?->constraints_json ?? []);
        $businessModel = $this->normalizeBusinessModel((string) ($company?->business_model ?? 'hybrid'));
        $companyName = (string) ($company?->company_name ?? $founder->full_name);
        $slug = $this->slugify($companyName);
        $snapshots = $founder->moduleSnapshots->keyBy('module');
        $bazaar = $snapshots->get('bazaar');
        $servio = $snapshots->get('servio');
        $supportedEngines = $this->supportedEngines($businessModel);

        $recommendedEngine = (string) ($autopilotDraft['engine'] ?? $company?->website_engine ?: $this->determineRecommendedEngine($businessModel));
        if (!in_array($recommendedEngine, $supportedEngines, true)) {
            $recommendedEngine = $supportedEngines[0] ?? 'servio';
        }
        $websitePath = $this->normalizeWebsitePath((string) ($autopilotDraft['website_path'] ?? $company?->website_path ?? ''));
        if ($websitePath === '') {
            $websitePath = $slug;
        }
        $recommendedSubdomain = $this->buildRecommendedSubdomain($slug, $recommendedEngine, $websitePath);
        $currentWebsiteUrl = (string) ($company?->website_url ?? '');
        if ($currentWebsiteUrl === '') {
            $currentWebsiteUrl = $this->buildPublicWebsiteUrl($slug, $websitePath, '');
        }

        $engines = [];
        foreach ($supportedEngines as $engine) {
            $engines[] = $this->buildEngineCard(
                $engine,
                $engine === 'bazaar' ? $bazaar : $servio,
                $slug,
                $websitePath,
                ''
            );
        }

        $themeOptions = [];
        foreach ($supportedEngines as $engine) {
            $themeOptions[$engine] = $this->websiteProvisioningService->availableThemes($engine);
        }

        return [
            'company_name' => $companyName,
            'business_model' => $businessModel,
            'website_status' => (string) ($company?->website_status ?? 'not_started'),
            'website_generation_status' => (string) ($company?->website_generation_status ?? 'not_started'),
            'recommended_engine' => $recommendedEngine,
            'website_path' => $websitePath,
            'current_website_url' => $currentWebsiteUrl,
            'recommended_subdomain' => $recommendedSubdomain,
            'custom_domain_example' => '',
            'custom_domain' => '',
            'custom_domain_status' => 'disabled',
            'engines' => $engines,
            'theme_options' => $themeOptions,
            'supported_engines' => $supportedEngines,
            'autopilot' => [
                'blueprint_name' => (string) ($company?->verticalBlueprint?->name ?? ''),
                'primary_city' => (string) ($company?->primary_city ?? ''),
                'problem_solved' => (string) ($company?->businessBrief?->problem_solved ?? ''),
                'primary_icp_name' => (string) ($latestIcp?->primary_icp_name ?? ''),
                'draft' => $autopilotDraft,
                'launch_system' => $latestLaunchSystem ? [
                    'id' => $latestLaunchSystem->id,
                    'status' => (string) $latestLaunchSystem->status,
                    'selected_engine' => (string) ($latestLaunchSystem->selected_engine ?? ''),
                    'applied_at' => optional($latestLaunchSystem->applied_at)->toDateTimeString(),
                ] : null,
            ],
            'build_brief' => [
                'selected_engine' => $recommendedEngine,
                'selected_engine_label' => strtoupper($recommendedEngine),
                'company_intelligence_summary' => array_values(array_filter([
                    ['label' => 'Business', 'value' => (string) ($company?->company_name ?? $founder->full_name)],
                    ['label' => 'Business model', 'value' => ucfirst($businessModel)],
                    ['label' => 'Audience', 'value' => (string) ($intelligence?->target_audience ?? '')],
                    ['label' => 'Ideal customer', 'value' => (string) ($intelligence?->primary_icp_name ?? '')],
                    ['label' => 'Problem solved', 'value' => (string) ($intelligence?->problem_solved ?? '')],
                    ['label' => 'Core offer', 'value' => (string) ($intelligence?->core_offer ?? '')],
                    ['label' => 'Brand voice', 'value' => (string) ($intelligence?->brand_voice ?? '')],
                    ['label' => 'Visual style', 'value' => (string) ($intelligence?->visual_style ?? '')],
                ])),
                'intake' => $websiteBuild,
                'missing_items' => $this->buildMissingWebsiteInputs($businessModel, $websiteBuild),
            ],
            'domain_model' => [
                [
                    'title' => 'Founder workspace',
                    'value' => 'app.hatchers.ai',
                    'description' => 'The founder logs in once, sees one dashboard, and manages the business from one operating system.',
                ],
                [
                    'title' => 'Default published site',
                    'value' => preg_replace('#^https?://#', '', $recommendedSubdomain) ?? $recommendedSubdomain,
                    'description' => 'By default the public business site lives inside Hatchers Ai Business OS under app.hatchers.ai/{company}.',
                ],
            ],
            'next_steps' => $this->buildNextSteps($businessModel, $recommendedEngine, $bazaar, $servio),
            'dns_targets' => [
                'bazaar' => $this->dnsTargetForEngine('bazaar'),
                'servio' => $this->dnsTargetForEngine('servio'),
            ],
        ];
    }

    private function buildEngineCard(string $engine, ?ModuleSnapshot $snapshot, string $slug, string $websitePath, string $customDomain): array
    {
        $payload = $snapshot?->payload_json ?? [];
        $summary = $payload['summary'] ?? [];
        $keyCounts = $payload['key_counts'] ?? [];
        $engineLabel = strtoupper($engine);
        $defaultUrl = $this->buildPublicWebsiteUrl($slug, $websitePath, $customDomain);

        if ($engine === 'bazaar') {
            $title = (string) ($summary['website_title'] ?? 'Product storefront');
            $summaryLine = sprintf(
                '%d products · %d orders · %s',
                (int) ($keyCounts['product_count'] ?? 0),
                (int) ($keyCounts['order_count'] ?? 0),
                $this->formatMoney((float) ($summary['gross_revenue'] ?? 0), (string) ($summary['currency'] ?? 'USD'))
            );
            $adminUrl = config('modules.bazaar.base_url') . '/admin/dashboard';
        } else {
            $title = (string) ($summary['website_title'] ?? 'Service website');
            $summaryLine = sprintf(
                '%d services · %d bookings · %s',
                (int) ($keyCounts['service_count'] ?? 0),
                (int) ($keyCounts['booking_count'] ?? 0),
                $this->formatMoney((float) ($summary['gross_revenue'] ?? 0), (string) ($summary['currency'] ?? 'USD'))
            );
            $adminUrl = config('modules.servio.base_url') . '/admin/dashboard';
        }

        return [
            'key' => $engine,
            'label' => $engineLabel,
            'role' => (string) (config('modules.' . $engine . '.role') ?? ''),
            'title' => $title,
            'summary' => $summaryLine,
            'readiness_score' => (int) ($snapshot?->readiness_score ?? 0),
            'theme' => (string) ($summary['theme_template'] ?? 'No theme selected yet'),
            'website_title' => $title,
            'website_url' => (string) ($summary['website_url'] ?? $defaultUrl),
            'updated_at' => $snapshot?->snapshot_updated_at?->toDateTimeString(),
            'admin_url' => $adminUrl,
            'website_mode' => $engine === 'bazaar' ? 'product' : 'service',
        ];
    }

    private function buildNextSteps(
        string $businessModel,
        string $recommendedEngine,
        ?ModuleSnapshot $bazaar,
        ?ModuleSnapshot $servio
    ): array {
        $steps = [
            [
                'title' => 'Start from one OS workflow',
                'description' => 'Founders should begin website building from Hatchers OS, then the OS can route the work into the right engine behind the scenes.',
            ],
            [
                'title' => 'Use the recommended engine first',
                'description' => 'For this business model, the primary website path is ' . strtoupper($recommendedEngine) . '.',
            ],
        ];

        if ($businessModel === 'hybrid') {
            $steps[] = [
                'title' => 'Support both business motions',
                'description' => 'Hybrid founders can publish a commerce storefront and a services site, while still managing both from one founder workspace.',
            ];
        }

        if (($bazaar?->readiness_score ?? 0) < 50) {
            $steps[] = [
                'title' => 'Complete your product setup',
                'description' => 'Choose a theme, add the first products, and make the public store ready to publish inside Hatchers OS.',
            ];
        }

        if (($servio?->readiness_score ?? 0) < 50) {
            $steps[] = [
                'title' => 'Complete your service setup',
                'description' => 'Choose a theme, add the first services, and configure booking availability so the public site is ready to publish inside Hatchers OS.',
            ];
        }

        return $steps;
    }

    private function determineRecommendedEngine(string $businessModel): string
    {
        return match ($businessModel) {
            'product' => 'bazaar',
            'service' => 'servio',
            default => 'bazaar',
        };
    }

    private function buildRecommendedSubdomain(string $slug, string $engine, string $path = ''): string
    {
        return $this->buildPublicWebsiteUrl($slug, $path, '');
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value !== '' ? $value : 'your-business';
    }

    private function formatMoney(float $amount, string $currency): string
    {
        $code = strtoupper(trim($currency));
        if ($code === '') {
            $code = 'USD';
        }

        return $code . ' ' . number_format($amount, 0);
    }

    private function dnsTargetForEngine(string $engine): string
    {
        $baseUrl = rtrim((string) config('modules.' . $engine . '.base_url'), '/');
        $host = parse_url($baseUrl, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : $baseUrl;
    }

    private function buildPublicWebsiteUrl(string $slug, string $path, string $customDomain): string
    {
        $host = 'app.hatchers.ai';
        $normalizedPath = $this->normalizeWebsitePath($path);
        if ($normalizedPath === '') {
            $normalizedPath = $slug;
        }

        return 'https://' . $host . ($normalizedPath !== '' ? '/' . $normalizedPath : '');
    }

    private function supportedEngines(string $businessModel): array
    {
        return match ($businessModel) {
            'product' => ['bazaar'],
            'service' => ['servio'],
            default => ['bazaar', 'servio'],
        };
    }

    private function normalizeBusinessModel(string $businessModel): string
    {
        $businessModel = strtolower(trim($businessModel));

        return in_array($businessModel, ['product', 'service', 'hybrid'], true) ? $businessModel : 'hybrid';
    }

    private function normalizeWebsitePath(string $path): string
    {
        $path = strtolower(trim($path));
        $path = preg_replace('/[^a-z0-9\-\/]+/', '-', $path) ?? '';
        $path = preg_replace('/\/+/', '/', $path) ?? '';
        $path = trim($path, '/-');

        return $path;
    }

    private function normalizeDomainHost(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain) ?? '';

        return trim($domain, '/');
    }

    private function websiteBuildPayload(array $constraints): array
    {
        $payload = is_array($constraints['website_build'] ?? null) ? $constraints['website_build'] : [];

        return [
            'website_goal' => trim((string) ($payload['website_goal'] ?? '')),
            'primary_website_focus' => trim((string) ($payload['primary_website_focus'] ?? 'auto')),
            'primary_cta' => trim((string) ($payload['primary_cta'] ?? '')),
            'contact_email' => trim((string) ($payload['contact_email'] ?? '')),
            'contact_phone' => trim((string) ($payload['contact_phone'] ?? '')),
            'whatsapp_number' => trim((string) ($payload['whatsapp_number'] ?? '')),
            'business_address' => trim((string) ($payload['business_address'] ?? '')),
            'business_hours' => trim((string) ($payload['business_hours'] ?? '')),
            'social_links' => trim((string) ($payload['social_links'] ?? '')),
            'must_include_pages' => trim((string) ($payload['must_include_pages'] ?? '')),
            'offer_items' => trim((string) ($payload['offer_items'] ?? '')),
            'faq_points' => trim((string) ($payload['faq_points'] ?? '')),
            'proof_points' => trim((string) ($payload['proof_points'] ?? '')),
            'image_preferences' => trim((string) ($payload['image_preferences'] ?? '')),
            'special_requests' => trim((string) ($payload['special_requests'] ?? '')),
        ];
    }

    private function buildMissingWebsiteInputs(string $businessModel, array $websiteBuild): array
    {
        $required = [
            'website_goal' => 'What should the website do first: drive bookings, product orders, leads, or consultations?',
            'offer_items' => $businessModel === 'product'
                ? 'Which products should we feature first, and what prices should we show?'
                : 'Which services should we feature first, and what prices should we show?',
            'primary_cta' => 'What primary action should the visitor take first?',
            'proof_points' => 'What trust signals, credentials, results, or guarantees should appear on the site?',
            'faq_points' => 'What common questions should we answer in the FAQ?',
            'image_preferences' => 'What should the photography style feel like?',
        ];

        $missing = [];
        foreach ($required as $field => $prompt) {
            if (trim((string) ($websiteBuild[$field] ?? '')) === '') {
                $missing[] = $prompt;
            }
        }

        return $missing;
    }
}
