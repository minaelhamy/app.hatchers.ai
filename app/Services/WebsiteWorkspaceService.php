<?php

namespace App\Services;

use App\Models\Founder;
use App\Models\ModuleSnapshot;

class WebsiteWorkspaceService
{
    public function __construct(private WebsiteProvisioningService $websiteProvisioningService)
    {
    }

    public function build(Founder $founder): array
    {
        $company = $founder->company;
        $businessModel = (string) ($company?->business_model ?? 'hybrid');
        $companyName = (string) ($company?->company_name ?? $founder->full_name);
        $slug = $this->slugify($companyName);
        $snapshots = $founder->moduleSnapshots->keyBy('module');
        $bazaar = $snapshots->get('bazaar');
        $servio = $snapshots->get('servio');

        $recommendedEngine = (string) ($company?->website_engine ?: $this->determineRecommendedEngine($businessModel));
        $recommendedSubdomain = $this->buildRecommendedSubdomain($slug, $recommendedEngine);
        $currentWebsiteUrl = (string) ($company?->website_url ?? '');
        if ($currentWebsiteUrl === '') {
            $currentWebsiteUrl = $recommendedSubdomain;
        }

        return [
            'company_name' => $companyName,
            'business_model' => $businessModel,
            'website_status' => (string) ($company?->website_status ?? 'not_started'),
            'recommended_engine' => $recommendedEngine,
            'current_website_url' => $currentWebsiteUrl,
            'recommended_subdomain' => $recommendedSubdomain,
            'custom_domain_example' => 'www.' . $slug . '.com',
            'custom_domain' => (string) ($company?->custom_domain ?? ''),
            'custom_domain_status' => (string) ($company?->custom_domain_status ?? 'not_connected'),
            'engines' => [
                $this->buildEngineCard('bazaar', $bazaar, $slug),
                $this->buildEngineCard('servio', $servio, $slug),
            ],
            'theme_options' => [
                'bazaar' => $this->websiteProvisioningService->availableThemes('bazaar'),
                'servio' => $this->websiteProvisioningService->availableThemes('servio'),
            ],
            'domain_model' => [
                [
                    'title' => 'Founder workspace',
                    'value' => 'app.hatchers.ai',
                    'description' => 'The founder logs in once, sees one dashboard, and manages the business from one operating system.',
                ],
                [
                    'title' => 'Default published site',
                    'value' => $recommendedSubdomain,
                    'description' => 'Hatchers can publish a ready-to-use business website without waiting on a custom domain.',
                ],
                [
                    'title' => 'Custom domain',
                    'value' => 'www.' . $slug . '.com',
                    'description' => 'When the founder is ready, the public website can point to their own branded domain.',
                ],
            ],
            'next_steps' => $this->buildNextSteps($businessModel, $recommendedEngine, $bazaar, $servio),
            'dns_targets' => [
                'bazaar' => $this->dnsTargetForEngine('bazaar'),
                'servio' => $this->dnsTargetForEngine('servio'),
            ],
        ];
    }

    private function buildEngineCard(string $engine, ?ModuleSnapshot $snapshot, string $slug): array
    {
        $payload = $snapshot?->payload_json ?? [];
        $summary = $payload['summary'] ?? [];
        $keyCounts = $payload['key_counts'] ?? [];
        $engineLabel = strtoupper($engine);
        $defaultUrl = $this->buildRecommendedSubdomain($slug, $engine);

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
                'title' => 'Complete your product website setup',
                'description' => 'Choose a theme, add the first products, and finalize store branding so Bazaar is ready to publish.',
            ];
        }

        if (($servio?->readiness_score ?? 0) < 50) {
            $steps[] = [
                'title' => 'Complete your service website setup',
                'description' => 'Choose a theme, add the first services, and configure booking availability so Servio is ready to publish.',
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

    private function buildRecommendedSubdomain(string $slug, string $engine): string
    {
        return match ($engine) {
            'servio' => 'https://' . $slug . '.hatchers.site',
            'bazaar' => 'https://' . $slug . '.hatchers.site',
            default => 'https://' . $slug . '.hatchers.site',
        };
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
}
