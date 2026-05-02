<?php

namespace App\Services;

use App\Models\Company;

class PublicWebsiteService
{
    public function build(Company $company): array
    {
        $founder = $company->founder;
        $company->loadMissing('websiteGenerationRuns');
        $websitePath = trim((string) ($company->website_path ?? ''), '/');
        if ($websitePath === '') {
            $websitePath = strtolower((string) str($company->company_name ?: ($founder?->full_name ?? 'your-business'))->slug('-'));
        }
        if ($websitePath === '') {
            $websitePath = 'your-business';
        }
        $snapshots = $founder?->moduleSnapshots?->keyBy('module') ?? collect();
        $latestDraft = $company->websiteGenerationRuns()->latest('id')->first();
        $draftOutput = is_array($latestDraft?->output_json) ? $latestDraft->output_json : [];
        $businessModel = $this->normalizeBusinessModel((string) ($company->business_model ?? 'hybrid'));
        $engine = $this->resolveEngine($company, $businessModel);
        $snapshot = $snapshots->get($engine);
        $payload = $snapshot?->payload_json ?? [];
        $summary = is_array($payload['summary'] ?? null) ? $payload['summary'] : [];
        $counts = is_array($payload['key_counts'] ?? null) ? $payload['key_counts'] : [];
        $offerPreferences = $this->offerPaymentPreferences($founder);

        $websiteTitle = trim((string) ($summary['website_title'] ?? ''));
        if ($websiteTitle === '') {
            $websiteTitle = trim((string) ($company->company_name ?? ''));
        }
        if ($websiteTitle === '') {
            $websiteTitle = trim((string) ($founder?->full_name ?? 'Hatchers Business'));
        }

        $currency = strtoupper(trim((string) ($summary['currency'] ?? 'USD')));
        if ($currency === '') {
            $currency = 'USD';
        }

        $storefrontUrl = trim((string) ($summary['website_url'] ?? ''));
        if ($storefrontUrl === '') {
            $storefrontUrl = trim((string) ($company->engine_public_url ?? ''));
        }
        $engineBaseUrl = rtrim((string) config('modules.' . $engine . '.base_url', ''), '/');
        $engineVendorSlug = $this->resolveEngineVendorSlug($storefrontUrl, $engineBaseUrl, $payload, $founder?->username, $websitePath);
        $osBaseUrl = rtrim((string) config('app.url'), '/');
        $engineProxyUrl = '';

        if ($engineBaseUrl !== '' && $engineVendorSlug !== '') {
            $candidateProxyUrl = $engineBaseUrl . '/' . ltrim($engineVendorSlug, '/');
            if (!$this->isRecursiveOsStorefrontUrl($candidateProxyUrl, $osBaseUrl, $websitePath)) {
                $engineProxyUrl = $candidateProxyUrl;
            }
        } elseif ($storefrontUrl !== '' && $this->shouldUseDirectStorefrontUrl($storefrontUrl, $engineBaseUrl) && !$this->isRecursiveOsStorefrontUrl($storefrontUrl, $osBaseUrl, $websitePath)) {
            $engineProxyUrl = $storefrontUrl;
        } elseif ($storefrontUrl !== '' && !$this->isRecursiveOsStorefrontUrl($storefrontUrl, $osBaseUrl, $websitePath)) {
            $engineProxyUrl = $storefrontUrl;
        }

        $usesEngineStorefront = $engineProxyUrl !== '';

        return [
            'title' => $websiteTitle,
            'business_model' => $businessModel,
            'engine' => $engine,
            'path' => $websitePath,
            'logo_url' => !empty($company->company_logo_path) ? asset('storage/' . ltrim((string) $company->company_logo_path, '/')) : null,
            'source_storefront_url' => $storefrontUrl,
            'engine_proxy_url' => $engineProxyUrl,
            'engine_vendor_slug' => $engineVendorSlug,
            'uses_engine_storefront' => $usesEngineStorefront,
            'theme' => (string) ($summary['theme_template'] ?? ''),
            'hero_image_url' => $this->resolveHeroImageUrl($draftOutput),
            'hero' => $this->buildHero($company, $founder?->full_name ?? '', $businessModel, $counts, $currency, $draftOutput),
            'metrics' => $this->buildMetrics($businessModel, $counts, (float) ($summary['gross_revenue'] ?? 0), $currency),
            'offers' => $this->buildOffers($payload, $businessModel, $currency, $offerPreferences),
            'proof' => $this->buildProof($businessModel, $counts, $summary, $currency, $draftOutput),
            'operations' => $this->buildOperations($payload, $businessModel, $currency),
            'generated_sections' => $this->generatedSections($draftOutput),
            'image_queries' => collect((array) ($draftOutput['image_queries'] ?? []))
                ->map(fn ($query) => trim((string) $query))
                ->filter()
                ->values()
                ->all(),
            'asset_slots' => collect((array) ($draftOutput['atlas_handoff']['asset_slots'] ?? []))
                ->filter(fn ($asset): bool => is_array($asset))
                ->values()
                ->all(),
            'checkout_context' => [
                'shipping_zones' => collect((array) ($payload['shipping_zones'] ?? []))
                    ->filter(fn ($zone): bool => is_array($zone))
                    ->map(fn (array $zone): array => [
                        'area_name' => (string) ($zone['area_name'] ?? ''),
                        'delivery_charge' => (float) ($zone['delivery_charge'] ?? 0),
                    ])->values()->all(),
            ],
            'contact' => [
                'founder_name' => (string) ($founder?->full_name ?? ''),
                'email' => (string) ($founder?->email ?? ''),
                'company' => (string) ($company->company_name ?? $websiteTitle),
            ],
            'updated_at' => $snapshot?->snapshot_updated_at?->toDateTimeString(),
        ];
    }

    private function generatedSections(array $draftOutput): array
    {
        return collect((array) ($draftOutput['sections'] ?? []))
            ->filter(fn ($section): bool => is_array($section))
            ->values()
            ->map(function (array $section): array {
                $asset = is_array($section['asset'] ?? null) ? $section['asset'] : [];

                return [
                    'title' => $this->cleanTitle((string) ($section['title'] ?? '')),
                    'body' => $this->cleanMarketingCopy((string) ($section['body'] ?? '')),
                    'bullets' => collect((array) ($section['bullets'] ?? []))
                        ->map(fn ($bullet) => $this->cleanListItem((string) $bullet))
                        ->filter()
                        ->values()
                        ->all(),
                    'asset' => $asset,
                ];
            })
            ->all();
    }

    private function resolveEngineVendorSlug(string $storefrontUrl, string $engineBaseUrl, array $payload, ?string $founderUsername, string $websitePath = ''): string
    {
        $path = trim((string) parse_url($storefrontUrl, PHP_URL_PATH), '/');
        if ($path !== '') {
            $segments = array_values(array_filter(explode('/', $path)));
            if (!empty($segments)) {
                return trim((string) $segments[0]);
            }
        }

        $storefrontHost = strtolower((string) parse_url($storefrontUrl, PHP_URL_HOST));
        $engineHost = strtolower((string) parse_url($engineBaseUrl, PHP_URL_HOST));
        if ($storefrontHost !== '' && $engineHost !== '' && $storefrontHost !== $engineHost) {
            $suffix = '.' . $engineHost;
            if (str_ends_with($storefrontHost, $suffix)) {
                $subdomain = substr($storefrontHost, 0, -strlen($suffix));
                $subdomain = trim((string) $subdomain);
                if ($subdomain !== '') {
                    return $subdomain;
                }
            }
        }

        $payloadSlug = trim((string) ($payload['slug'] ?? $payload['vendor_slug'] ?? ''));
        if ($payloadSlug !== '') {
            return $payloadSlug;
        }

        $founderUsername = trim((string) $founderUsername);
        if ($founderUsername !== '') {
            return $founderUsername;
        }

        return trim((string) $websitePath);
    }

    private function shouldUseDirectStorefrontUrl(string $storefrontUrl, string $engineBaseUrl): bool
    {
        $storefrontHost = strtolower((string) parse_url($storefrontUrl, PHP_URL_HOST));
        $engineHost = strtolower((string) parse_url($engineBaseUrl, PHP_URL_HOST));
        $storefrontPath = trim((string) parse_url($storefrontUrl, PHP_URL_PATH), '/');

        if ($storefrontHost === '') {
            return false;
        }

        if ($engineHost === '') {
            return true;
        }

        if ($storefrontHost !== $engineHost && !str_ends_with($storefrontHost, '.' . $engineHost)) {
            return true;
        }

        return $storefrontPath === '';
    }

    private function isRecursiveOsStorefrontUrl(string $candidateUrl, string $osBaseUrl, string $websitePath): bool
    {
        $candidateUrl = rtrim(trim($candidateUrl), '/');
        $osBaseUrl = rtrim(trim($osBaseUrl), '/');
        $websitePath = trim($websitePath, '/');

        if ($candidateUrl === '' || $osBaseUrl === '') {
            return false;
        }

        if ($candidateUrl === $osBaseUrl) {
            return true;
        }

        return $websitePath !== '' && $candidateUrl === $osBaseUrl . '/' . $websitePath;
    }

    private function buildHero(Company $company, string $founderName, string $businessModel, array $counts, string $currency, array $draftOutput = []): array
    {
        $companyName = trim((string) ($company->company_name ?? ''));
        $brief = trim((string) ($company->company_brief ?? ''));

        if ($brief === '') {
            $brief = match ($businessModel) {
                'product' => 'A focused ecommerce business running through Hatchers Ai Business OS.',
                'service' => 'A service business running bookings and delivery through Hatchers Ai Business OS.',
                default => 'A hybrid business running products and services through Hatchers Ai Business OS.',
            };
        }

        $headline = trim((string) ($draftOutput['hero']['headline'] ?? ''));
        if ($headline === '') {
            $headline = $companyName !== '' ? $companyName : ($founderName !== '' ? $founderName : 'Hatchers Business');
        }
        $headline = $this->cleanHeadline($headline);
        if ($this->headlineFeelsTooLong($headline)) {
            $headline = $companyName !== '' ? $companyName : ($founderName !== '' ? $founderName : 'Hatchers Business');
        }
        $headline = $this->cleanHeadline($headline);

        $subhead = trim((string) ($draftOutput['hero']['subhead'] ?? ''));
        if ($subhead === '') {
            $subhead = match ($businessModel) {
                'product' => 'Browse the featured offers, choose the right fit, and buy directly from this storefront.',
                'service' => 'Discover the best-fit classes or services, book with confidence, and take the next step today.',
                default => 'Explore the best-fit offers, services, and next steps from one polished storefront.',
            };
        }
        $subhead = $this->cleanMarketingCopy($subhead);

        $heroBrief = trim((string) ($draftOutput['hero']['brief'] ?? ''));
        if ($heroBrief !== '') {
            $brief = $heroBrief;
        }
        $brief = $this->cleanMarketingCopy($brief);

        return [
            'headline' => $headline,
            'subhead' => $subhead,
            'brief' => $brief,
            'primary_cta' => trim((string) ($draftOutput['hero']['primary_cta'] ?? '')) ?: ($businessModel === 'service' ? 'Book now' : 'Explore offers'),
            'secondary_cta' => trim((string) ($draftOutput['hero']['secondary_cta'] ?? '')) ?: ($businessModel === 'service' ? 'See services' : 'See what is available'),
            'eyebrow' => trim((string) ($draftOutput['hero']['eyebrow'] ?? '')) ?: strtoupper($businessModel === 'service' ? 'SERVICES WEBSITE' : ($businessModel === 'product' ? 'STOREFRONT' : 'BUSINESS WEBSITE')),
        ];
    }

    private function buildMetrics(string $businessModel, array $counts, float $grossRevenue, string $currency): array
    {
        $metrics = [];

        if ($businessModel !== 'service') {
            $metrics[] = ['label' => 'Products', 'value' => (string) ((int) ($counts['product_count'] ?? 0))];
            $metrics[] = ['label' => 'Orders', 'value' => (string) ((int) ($counts['order_count'] ?? 0))];
        }

        if ($businessModel !== 'product') {
            $metrics[] = ['label' => 'Services', 'value' => (string) ((int) ($counts['service_count'] ?? 0))];
            $metrics[] = ['label' => 'Bookings', 'value' => (string) ((int) ($counts['booking_count'] ?? 0))];
        }

        $metrics[] = ['label' => 'Revenue tracked', 'value' => $currency . ' ' . number_format($grossRevenue, 0)];

        $visibleMetrics = array_values(array_filter($metrics, function (array $metric): bool {
            return !preg_match('/\b0(?:\.00)?\b/', (string) $metric['value']);
        }));

        return array_slice($visibleMetrics, 0, 4);
    }

    private function buildOffers(array $payload, string $businessModel, string $currency, array $offerPreferences = []): array
    {
        $items = [];

        if ($businessModel !== 'service') {
            foreach ((array) ($payload['recent_products'] ?? []) as $product) {
                if (!is_array($product)) {
                    continue;
                }

                $paymentCollection = $offerPreferences['bazaar:' . strtolower((string) ($product['title'] ?? ''))] ?? 'both';
                $items[] = [
                    'type' => 'product',
                    'title' => $this->cleanTitle((string) ($product['title'] ?? 'Product')),
                    'meta' => $this->cleanMarketingCopy(trim('SKU ' . (string) ($product['sku'] ?? '') . (($product['qty'] ?? null) !== null ? ' · Stock ' . (int) $product['qty'] : ''))),
                    'price' => $currency . ' ' . number_format((float) ($product['price'] ?? 0), 2),
                    'base_price' => (float) ($product['price'] ?? 0),
                    'currency' => $currency,
                    'status' => ucfirst((string) ($product['status'] ?? 'active')),
                    'request_options' => [
                        'payment_collection' => $paymentCollection,
                        'accepted_payment_methods' => $this->acceptedPaymentMethods($paymentCollection),
                        'variants' => collect((array) ($product['variants'] ?? []))
                            ->filter(fn ($variant): bool => is_array($variant))
                            ->map(fn (array $variant): array => [
                                'name' => (string) ($variant['name'] ?? ''),
                                'price' => (float) ($variant['price'] ?? 0),
                                'qty' => (int) ($variant['qty'] ?? 0),
                            ])->filter(fn (array $variant): bool => $variant['name'] !== '')
                            ->values()
                            ->all(),
                        'extras' => collect((array) ($product['extras'] ?? []))
                            ->filter(fn ($extra): bool => is_array($extra))
                            ->map(fn (array $extra): array => [
                                'name' => (string) ($extra['name'] ?? ''),
                                'price' => (float) ($extra['price'] ?? 0),
                            ])->filter(fn (array $extra): bool => $extra['name'] !== '')
                            ->values()
                            ->all(),
                    ],
                    'details' => array_values(array_filter(array_merge(
                        collect((array) ($product['variants'] ?? []))
                            ->map(fn ($variant) => is_array($variant) ? $this->cleanListItem((string) (($variant['name'] ?? '') . ' · ' . ($variant['qty'] ?? 0) . ' in stock')) : '')
                            ->all(),
                        collect((array) ($product['extras'] ?? []))
                            ->map(fn ($extra) => is_array($extra) ? $this->cleanListItem((string) (($extra['name'] ?? '') . ' · +' . $currency . ' ' . number_format((float) ($extra['price'] ?? 0), 2))) : '')
                            ->all()
                    ))),
                ];
            }
        }

        if ($businessModel !== 'product') {
            foreach ((array) ($payload['recent_services'] ?? []) as $service) {
                if (!is_array($service)) {
                    continue;
                }

                $duration = (int) ($service['duration'] ?? 0);
                $durationUnit = (string) ($service['duration_unit'] ?? 'minutes');
                $paymentCollection = $offerPreferences['servio:' . strtolower((string) ($service['title'] ?? ''))] ?? 'both';
                $items[] = [
                    'type' => 'service',
                    'title' => $this->cleanTitle((string) ($service['title'] ?? 'Service')),
                    'meta' => $this->cleanMarketingCopy(trim(($duration > 0 ? $duration . ' ' . $durationUnit : '') . ((int) ($service['capacity'] ?? 0) > 0 ? ' · Capacity ' . (int) $service['capacity'] : ''))),
                    'price' => $currency . ' ' . number_format((float) ($service['price'] ?? 0), 2),
                    'base_price' => (float) ($service['price'] ?? 0),
                    'currency' => $currency,
                    'status' => ucfirst((string) ($service['status'] ?? 'active')),
                    'request_options' => [
                        'payment_collection' => $paymentCollection,
                        'accepted_payment_methods' => $this->acceptedPaymentMethods($paymentCollection),
                        'additional_services' => collect((array) ($service['additional_services'] ?? []))
                            ->filter(fn ($extra): bool => is_array($extra))
                            ->map(fn (array $extra): array => [
                                'name' => (string) ($extra['name'] ?? ''),
                                'price' => (float) ($extra['price'] ?? 0),
                            ])->filter(fn (array $extra): bool => $extra['name'] !== '')
                            ->values()
                            ->all(),
                        'duration' => $duration,
                        'duration_unit' => $durationUnit,
                        'availability_days' => collect((array) ($service['availability_days'] ?? []))
                            ->map(fn ($day) => trim((string) $day))
                            ->filter()
                            ->values()
                            ->all(),
                        'open_time' => (string) ($service['open_time'] ?? ''),
                        'close_time' => (string) ($service['close_time'] ?? ''),
                    ],
                    'details' => array_values(array_filter(array_merge(
                        collect((array) ($service['additional_services'] ?? []))
                            ->map(fn ($extra) => is_array($extra) ? $this->cleanListItem((string) (($extra['name'] ?? '') . ' · +' . $currency . ' ' . number_format((float) ($extra['price'] ?? 0), 2))) : '')
                            ->all(),
                        collect((array) ($service['staff_ids'] ?? []))
                            ->map(fn ($staffId) => $this->cleanListItem((string) ('Staff ID ' . $staffId)))
                            ->all()
                    ))),
                ];
            }
        }

        if (empty($items)) {
            $fallbackLabel = match ($businessModel) {
                'product' => 'Products will appear here once the founder finishes the first storefront setup.',
                'service' => 'Services will appear here once the founder finishes the first booking setup.',
                default => 'Products and services will appear here once the founder finishes setup.',
            };

            return [[
                'type' => 'placeholder',
                'title' => 'Launching soon',
                'meta' => $fallbackLabel,
                'price' => '',
                'status' => 'Setup in progress',
            ]];
        }

        return array_slice($items, 0, 6);
    }

    private function buildProof(string $businessModel, array $counts, array $summary, string $currency, array $draftOutput = []): array
    {
        $proof = [];
        $grossRevenue = (float) ($summary['gross_revenue'] ?? 0);

        if ($businessModel !== 'service') {
            $proof[] = [
                'title' => 'Orders are already flowing',
                'description' => $this->cleanMarketingCopy((int) ($counts['order_count'] ?? 0) . ' orders tracked and ' . $currency . ' ' . number_format($grossRevenue, 0) . ' in revenue signals.'),
            ];
        }

        if ($businessModel !== 'product') {
            $proof[] = [
                'title' => 'Designed to turn interest into bookings',
                'description' => $this->cleanMarketingCopy((int) ($counts['booking_count'] ?? 0) . ' bookings tracked with service operations flowing through Servio.'),
            ];
        }

        foreach (array_slice((array) ($draftOutput['sections'] ?? []), 0, 2) as $section) {
            if (!is_array($section)) {
                continue;
            }

            $proof[] = [
                'title' => $this->cleanTitle((string) ($section['title'] ?? 'Launch section')),
                'description' => $this->cleanMarketingCopy(trim(implode(' ', array_filter(array_merge(
                    [(string) ($section['body'] ?? '')],
                    array_slice(array_values(array_filter(array_map('strval', (array) ($section['bullets'] ?? [])))), 0, 2)
                ))))),
            ];
        }

        return array_values(array_filter($proof, fn (array $item): bool => trim((string) ($item['description'] ?? '')) !== ''));
    }

    private function acceptedPaymentMethods(string $paymentCollection): array
    {
        return match ($paymentCollection) {
            'online_only' => ['online'],
            'cash_only' => ['cash'],
            default => ['online', 'cash'],
        };
    }

    private function offerPaymentPreferences($founder): array
    {
        if (!$founder) {
            return [];
        }

        return $founder->actionPlans()
            ->whereIn('platform', ['bazaar', 'servio'])
            ->where('description', 'not like', 'Config:%')
            ->get()
            ->mapWithKeys(function ($actionPlan): array {
                $description = (string) ($actionPlan->description ?? '');
                preg_match('/^PaymentCollection:\s*(.+)$/mi', $description, $matches);
                $paymentCollection = trim((string) ($matches[1] ?? 'both'));
                if (!in_array($paymentCollection, ['online_only', 'cash_only', 'both'], true)) {
                    $paymentCollection = 'both';
                }

                return [
                    (string) $actionPlan->platform . ':' . strtolower((string) $actionPlan->title) => $paymentCollection,
                ];
            })
            ->all();
    }

    private function buildOperations(array $payload, string $businessModel, string $currency): array
    {
        $operations = [];

        if ($businessModel !== 'service') {
            $shipping = collect((array) ($payload['shipping_zones'] ?? []))
                ->filter(fn ($zone): bool => is_array($zone))
                ->map(function (array $zone) use ($currency): array {
                    return [
                        'title' => (string) ($zone['area_name'] ?? 'Delivery zone'),
                        'meta' => trim('Delivery fee · ' . $currency . ' ' . number_format((float) ($zone['delivery_charge'] ?? 0), 2)),
                        'detail' => !empty($zone['is_available']) ? 'Available for delivery' : 'Currently inactive',
                    ];
                })
                ->take(4)
                ->values()
                ->all();

            if ($shipping !== []) {
                $operations[] = [
                    'title' => 'Delivery coverage',
                    'items' => $shipping,
                ];
            }
        }

        if ($businessModel !== 'product') {
            $availability = collect((array) ($payload['recent_services'] ?? []))
                ->filter(fn ($service): bool => is_array($service))
                ->map(function (array $service): array {
                    $days = collect((array) ($service['availability_days'] ?? []))
                        ->filter(fn ($day): bool => trim((string) $day) !== '')
                        ->values()
                        ->all();

                    return [
                        'title' => (string) ($service['title'] ?? $service['name'] ?? 'Service'),
                        'meta' => $days !== [] ? implode(' · ', $days) : 'Availability is being configured',
                        'detail' => trim(((string) ($service['open_time'] ?? '')) . (((string) ($service['close_time'] ?? '')) !== '' ? ' - ' . (string) $service['close_time'] : '')),
                    ];
                })
                ->take(4)
                ->values()
                ->all();

            if ($availability !== []) {
                $operations[] = [
                    'title' => 'Booking availability',
                    'items' => $availability,
                ];
            }
        }

        return $operations;
    }

    private function resolveEngine(Company $company, string $businessModel): string
    {
        $engine = strtolower(trim((string) ($company->website_engine ?? '')));

        if ($businessModel === 'service') {
            return 'servio';
        }

        if ($businessModel === 'product') {
            return 'bazaar';
        }

        return in_array($engine, ['bazaar', 'servio'], true) ? $engine : 'bazaar';
    }

    private function normalizeBusinessModel(string $businessModel): string
    {
        $businessModel = strtolower(trim($businessModel));

        return in_array($businessModel, ['product', 'service', 'hybrid'], true) ? $businessModel : 'hybrid';
    }

    private function resolveHeroImageUrl(array $draftOutput): ?string
    {
        foreach ((array) ($draftOutput['atlas_handoff']['asset_slots'] ?? []) as $slot) {
            if (!is_array($slot)) {
                continue;
            }

            $previewUrl = trim((string) ($slot['preview_url'] ?? ''));
            if ($previewUrl !== '') {
                return $previewUrl;
            }
        }

        foreach ((array) ($draftOutput['sections'] ?? []) as $section) {
            if (!is_array($section)) {
                continue;
            }

            $previewUrl = trim((string) (($section['asset']['preview_url'] ?? '')));
            if ($previewUrl !== '') {
                return $previewUrl;
            }
        }

        return null;
    }

    private function cleanHeadline(string $value): string
    {
        $value = $this->dedupeRepeatedWords($value);
        $value = preg_replace('/\b(helps\s+you\s+we\s+help)\b/i', 'helps', $value) ?? $value;
        $value = preg_replace('/\b(many\s+busy\s+professionals\s+want\s+the\s+benefits\s+of)\b/i', 'busy professionals get', $value) ?? $value;
        $value = preg_replace('/\s{2,}/', ' ', trim($value)) ?? trim($value);

        return $value;
    }

    private function cleanTitle(string $value): string
    {
        $value = $this->dedupeRepeatedWords($value);
        $value = preg_replace('/\b(starter)(?:\s+\1)+\b/i', 'Starter', $value) ?? $value;
        $value = preg_replace('/\s{2,}/', ' ', trim($value)) ?? trim($value);

        return $value;
    }

    private function cleanMarketingCopy(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = $this->dedupeRepeatedWords($value);
        $value = preg_replace('/\b(starter)(?:\s+\1)+\b/i', 'starter', $value) ?? $value;
        $value = preg_replace('/\b(helps\s+you\s+we\s+help)\b/i', 'helps', $value) ?? $value;
        $value = preg_replace('/\bmanaged entirely from Hatchers OS\b/i', 'available online and ready for the next customer', $value) ?? $value;
        $value = preg_replace('/\s{2,}/', ' ', $value) ?? $value;

        return trim($value);
    }

    private function cleanListItem(string $value): string
    {
        return $this->cleanMarketingCopy($value);
    }

    private function dedupeRepeatedWords(string $value): string
    {
        $previous = null;
        $cleaned = trim($value);

        while ($cleaned !== $previous) {
            $previous = $cleaned;
            $cleaned = preg_replace('/\b([A-Za-z][A-Za-z\'-]*)\s+\1\b/i', '$1', $cleaned) ?? $cleaned;
        }

        return trim($cleaned);
    }

    private function headlineFeelsTooLong(string $value): bool
    {
        $words = preg_split('/\s+/', trim($value)) ?: [];

        return count($words) > 11 || mb_strlen($value) > 88;
    }
}
