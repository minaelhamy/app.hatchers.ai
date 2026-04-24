<?php

namespace App\Services;

use App\Models\Company;

class PublicWebsiteService
{
    public function build(Company $company): array
    {
        $founder = $company->founder;
        $snapshots = $founder?->moduleSnapshots?->keyBy('module') ?? collect();
        $businessModel = $this->normalizeBusinessModel((string) ($company->business_model ?? 'hybrid'));
        $engine = $this->resolveEngine($company, $businessModel);
        $snapshot = $snapshots->get($engine);
        $payload = $snapshot?->payload_json ?? [];
        $summary = is_array($payload['summary'] ?? null) ? $payload['summary'] : [];
        $counts = is_array($payload['key_counts'] ?? null) ? $payload['key_counts'] : [];

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

        return [
            'title' => $websiteTitle,
            'business_model' => $businessModel,
            'engine' => $engine,
            'path' => trim((string) ($company->website_path ?? ''), '/'),
            'theme' => (string) ($summary['theme_template'] ?? ''),
            'hero' => $this->buildHero($company, $founder?->full_name ?? '', $businessModel, $counts, $currency),
            'metrics' => $this->buildMetrics($businessModel, $counts, (float) ($summary['gross_revenue'] ?? 0), $currency),
            'offers' => $this->buildOffers($payload, $businessModel, $currency),
            'proof' => $this->buildProof($businessModel, $counts, $summary, $currency),
            'operations' => $this->buildOperations($payload, $businessModel, $currency),
            'contact' => [
                'founder_name' => (string) ($founder?->full_name ?? ''),
                'email' => (string) ($founder?->email ?? ''),
                'company' => (string) ($company->company_name ?? $websiteTitle),
            ],
            'updated_at' => $snapshot?->snapshot_updated_at?->toDateTimeString(),
        ];
    }

    private function buildHero(Company $company, string $founderName, string $businessModel, array $counts, string $currency): array
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

        $headline = $companyName !== '' ? $companyName : ($founderName !== '' ? $founderName : 'Hatchers Business');
        $subhead = match ($businessModel) {
            'product' => 'Browse products, place orders, and buy directly from one operating system.',
            'service' => 'Book services, confirm time slots, and work with a business that runs from one operating system.',
            default => 'Explore products and services from one unified business operating system.',
        };

        return [
            'headline' => $headline,
            'subhead' => $subhead,
            'brief' => $brief,
            'primary_cta' => $businessModel === 'service' ? 'Book now' : 'Explore offers',
            'secondary_cta' => $businessModel === 'service' ? 'See services' : 'See what is available',
            'eyebrow' => strtoupper($businessModel === 'service' ? 'SERVICES WEBSITE' : ($businessModel === 'product' ? 'STOREFRONT' : 'BUSINESS WEBSITE')),
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

        return array_slice($metrics, 0, 4);
    }

    private function buildOffers(array $payload, string $businessModel, string $currency): array
    {
        $items = [];

        if ($businessModel !== 'service') {
            foreach ((array) ($payload['recent_products'] ?? []) as $product) {
                if (!is_array($product)) {
                    continue;
                }

                $items[] = [
                    'type' => 'product',
                    'title' => (string) ($product['title'] ?? 'Product'),
                    'meta' => trim('SKU ' . (string) ($product['sku'] ?? '') . (($product['qty'] ?? null) !== null ? ' · Stock ' . (int) $product['qty'] : '')),
                    'price' => $currency . ' ' . number_format((float) ($product['price'] ?? 0), 2),
                    'status' => ucfirst((string) ($product['status'] ?? 'active')),
                    'request_options' => [
                        'variants' => collect((array) ($product['variants'] ?? []))
                            ->filter('is_array')
                            ->map(fn (array $variant): array => [
                                'name' => (string) ($variant['name'] ?? ''),
                                'price' => (float) ($variant['price'] ?? 0),
                                'qty' => (int) ($variant['qty'] ?? 0),
                            ])->filter(fn (array $variant): bool => $variant['name'] !== '')
                            ->values()
                            ->all(),
                        'extras' => collect((array) ($product['extras'] ?? []))
                            ->filter('is_array')
                            ->map(fn (array $extra): array => [
                                'name' => (string) ($extra['name'] ?? ''),
                                'price' => (float) ($extra['price'] ?? 0),
                            ])->filter(fn (array $extra): bool => $extra['name'] !== '')
                            ->values()
                            ->all(),
                    ],
                    'details' => array_values(array_filter(array_merge(
                        collect((array) ($product['variants'] ?? []))
                            ->map(fn ($variant) => is_array($variant) ? trim((string) (($variant['name'] ?? '') . ' · ' . ($variant['qty'] ?? 0) . ' in stock')) : '')
                            ->all(),
                        collect((array) ($product['extras'] ?? []))
                            ->map(fn ($extra) => is_array($extra) ? trim((string) (($extra['name'] ?? '') . ' · +' . $currency . ' ' . number_format((float) ($extra['price'] ?? 0), 2))) : '')
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
                $items[] = [
                    'type' => 'service',
                    'title' => (string) ($service['title'] ?? 'Service'),
                    'meta' => trim(($duration > 0 ? $duration . ' ' . $durationUnit : '') . ((int) ($service['capacity'] ?? 0) > 0 ? ' · Capacity ' . (int) $service['capacity'] : '')),
                    'price' => $currency . ' ' . number_format((float) ($service['price'] ?? 0), 2),
                    'status' => ucfirst((string) ($service['status'] ?? 'active')),
                    'request_options' => [
                        'additional_services' => collect((array) ($service['additional_services'] ?? []))
                            ->filter('is_array')
                            ->map(fn (array $extra): array => [
                                'name' => (string) ($extra['name'] ?? ''),
                                'price' => (float) ($extra['price'] ?? 0),
                            ])->filter(fn (array $extra): bool => $extra['name'] !== '')
                            ->values()
                            ->all(),
                    ],
                    'details' => array_values(array_filter(array_merge(
                        collect((array) ($service['additional_services'] ?? []))
                            ->map(fn ($extra) => is_array($extra) ? trim((string) (($extra['name'] ?? '') . ' · +' . $currency . ' ' . number_format((float) ($extra['price'] ?? 0), 2))) : '')
                            ->all(),
                        collect((array) ($service['staff_ids'] ?? []))
                            ->map(fn ($staffId) => trim((string) ('Staff ID ' . $staffId)))
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

    private function buildProof(string $businessModel, array $counts, array $summary, string $currency): array
    {
        $proof = [];
        $grossRevenue = (float) ($summary['gross_revenue'] ?? 0);

        if ($businessModel !== 'service') {
            $proof[] = [
                'title' => 'Commerce running in OS',
                'description' => (int) ($counts['order_count'] ?? 0) . ' orders tracked and ' . $currency . ' ' . number_format($grossRevenue, 0) . ' in revenue signals.',
            ];
        }

        if ($businessModel !== 'product') {
            $proof[] = [
                'title' => 'Bookings running in OS',
                'description' => (int) ($counts['booking_count'] ?? 0) . ' bookings tracked with service operations flowing through Servio.',
            ];
        }

        $proof[] = [
            'title' => 'Managed from Hatchers Ai Business OS',
            'description' => 'This public site is published from app.hatchers.ai while Bazaar and Servio keep powering the backend.',
        ];

        return $proof;
    }

    private function buildOperations(array $payload, string $businessModel, string $currency): array
    {
        $operations = [];

        if ($businessModel !== 'service') {
            $shipping = collect((array) ($payload['shipping_zones'] ?? []))
                ->filter('is_array')
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
                ->filter('is_array')
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
}
