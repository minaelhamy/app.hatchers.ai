<?php

namespace App\Services;

use App\Models\Founder;
use App\Models\MentorAssignment;
use App\Models\ModuleSnapshot;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Builder;

class AdminDashboardService
{
    public function build(Founder $admin): array
    {
        $founderCount = Founder::query()->where('role', 'founder')->count();
        $mentorCount = Founder::query()->where('role', 'mentor')->count();
        $adminCount = Founder::query()->where('role', 'admin')->count();
        $subscriberCount = Subscription::query()->count();
        $activeSubscriberCount = Subscription::query()
            ->whereIn('billing_status', ['active', 'draft', 'trialing'])
            ->count();
        $mentorAssignmentCount = MentorAssignment::query()->where('status', 'active')->count();
        $liveWebsiteCount = \App\Models\Company::query()->where('website_status', 'live')->count();
        $grossRevenue = (float) \App\Models\CommercialSummary::query()->sum('gross_revenue');

        $moduleHealth = collect(['lms', 'atlas', 'bazaar', 'servio'])->map(function (string $module) use ($founderCount): array {
            $query = ModuleSnapshot::query()->where('module', $module);
            $latestSnapshot = (clone $query)->latest('snapshot_updated_at')->first();
            $syncedFounders = (int) (clone $query)->distinct('founder_id')->count('founder_id');
            $coveragePercent = $founderCount > 0
                ? (int) round(($syncedFounders / $founderCount) * 100)
                : 0;
            $lastSyncedAt = $latestSnapshot?->snapshot_updated_at;
            $hoursSinceSync = $lastSyncedAt?->diffInHours(now());
            $status = $this->resolveModuleStatus($syncedFounders, $hoursSinceSync);

            return [
                'module' => strtoupper($module),
                'status' => $status['label'],
                'status_tone' => $status['tone'],
                'status_reason' => $status['reason'],
                'synced_founders' => $syncedFounders,
                'missing_founders' => max($founderCount - $syncedFounders, 0),
                'coverage_percent' => $coveragePercent,
                'avg_readiness' => (int) round((float) ((clone $query)->avg('readiness_score') ?? 0)),
                'last_synced_at' => optional($lastSyncedAt)->toDateTimeString(),
                'hours_since_sync' => $hoursSinceSync,
            ];
        })->all();

        $healthyModules = collect($moduleHealth)->where('status', 'Healthy')->count();
        $staleModules = collect($moduleHealth)->where('status', 'Stale')->count();
        $offlineModules = collect($moduleHealth)->where('status', 'Offline')->count();

        $recentSubscribers = Founder::query()
            ->where('role', 'founder')
            ->with(['company', 'subscription', 'weeklyState', 'commercialSummary'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(function (Founder $founder): array {
                return [
                    'name' => $founder->full_name,
                    'email' => $founder->email,
                    'company_name' => $founder->company?->company_name ?: 'Company not set yet',
                    'business_model' => ucfirst((string) ($founder->company?->business_model ?? 'hybrid')),
                    'plan_name' => $founder->subscription?->plan_name ?: 'No plan yet',
                    'billing_status' => ucfirst((string) ($founder->subscription?->billing_status ?? 'unknown')),
                    'weekly_progress_percent' => (int) ($founder->weeklyState?->weekly_progress_percent ?? 0),
                    'gross_revenue' => (float) ($founder->commercialSummary?->gross_revenue ?? 0),
                    'created_at' => optional($founder->created_at)->toDateTimeString(),
                ];
            })
            ->all();

        $roleLaunches = [
            [
                'label' => 'Open LMS Admin',
                'url' => rtrim((string) config('modules.lms.base_url'), '/') . '/admin',
            ],
            [
                'label' => 'Open Atlas Admin',
                'url' => rtrim((string) config('modules.atlas.base_url'), '/') . '/admin',
            ],
            [
                'label' => 'Open Bazaar Admin',
                'url' => rtrim((string) config('modules.bazaar.base_url'), '/') . '/admin/dashboard',
            ],
            [
                'label' => 'Open Servio Admin',
                'url' => rtrim((string) config('modules.servio.base_url'), '/') . '/admin/dashboard',
            ],
        ];

        return [
            'admin' => $admin,
            'metrics' => [
                'founders' => $founderCount,
                'mentors' => $mentorCount,
                'admins' => $adminCount,
                'subscribers' => $subscriberCount,
                'active_subscribers' => $activeSubscriberCount,
                'active_mentor_assignments' => $mentorAssignmentCount,
                'live_websites' => $liveWebsiteCount,
                'gross_revenue' => $grossRevenue,
                'healthy_modules' => $healthyModules,
                'stale_modules' => $staleModules,
                'offline_modules' => $offlineModules,
            ],
            'module_health' => $moduleHealth,
            'recent_subscribers' => $recentSubscribers,
            'role_launches' => $roleLaunches,
        ];
    }

    public function buildSubscriberReport(Founder $admin, array $filters = []): array
    {
        $baseQuery = Founder::query()
            ->where('role', 'founder')
            ->with([
                'company',
                'subscription',
                'weeklyState',
                'commercialSummary',
                'assignedFounderLinks.mentor',
            ]);

        $filteredQuery = (clone $baseQuery);
        $this->applySubscriberFilters($filteredQuery, $filters);
        $founders = $filteredQuery->orderBy('full_name')->get();

        $subscribers = $founders->map(function (Founder $founder): array {
            $assignment = $founder->assignedFounderLinks
                ->where('status', 'active')
                ->sortByDesc('assigned_at')
                ->first();

            return [
                'id' => $founder->id,
                'name' => $founder->full_name,
                'email' => $founder->email,
                'status' => (string) ($founder->status ?? 'active'),
                'company_name' => $founder->company?->company_name ?: 'Company not set yet',
                'business_model' => (string) ($founder->company?->business_model ?? 'hybrid'),
                'plan_code' => (string) ($founder->subscription?->plan_code ?? ''),
                'plan_name' => (string) ($founder->subscription?->plan_name ?? 'Hatchers OS'),
                'billing_status' => (string) ($founder->subscription?->billing_status ?? 'draft'),
                'weekly_progress_percent' => (int) ($founder->weeklyState?->weekly_progress_percent ?? 0),
                'open_tasks' => (int) ($founder->weeklyState?->open_tasks ?? 0),
                'gross_revenue' => (float) ($founder->commercialSummary?->gross_revenue ?? 0),
                'orders' => (int) ($founder->commercialSummary?->order_count ?? 0),
                'bookings' => (int) ($founder->commercialSummary?->booking_count ?? 0),
                'website_status' => (string) ($founder->company?->website_status ?? 'not_started'),
                'mentor_name' => $assignment?->mentor?->full_name ?: 'Unassigned',
                'created_at' => optional($founder->created_at)->toDateTimeString(),
            ];
        });

        $allFounders = $baseQuery->get();
        $newLast7Days = $allFounders->filter(fn (Founder $founder) => optional($founder->created_at)?->gte(now()->subDays(7)))->count();
        $newLast30Days = $allFounders->filter(fn (Founder $founder) => optional($founder->created_at)?->gte(now()->subDays(30)))->count();

        return [
            'admin' => $admin,
            'filters' => [
                'search' => trim((string) ($filters['search'] ?? '')),
                'plan_code' => (string) ($filters['plan_code'] ?? ''),
                'billing_status' => (string) ($filters['billing_status'] ?? ''),
                'status' => (string) ($filters['status'] ?? ''),
                'business_model' => (string) ($filters['business_model'] ?? ''),
            ],
            'filter_options' => [
                'plan_codes' => Subscription::query()
                    ->whereNotNull('plan_code')
                    ->where('plan_code', '!=', '')
                    ->distinct()
                    ->orderBy('plan_code')
                    ->pluck('plan_code')
                    ->all(),
                'billing_statuses' => Subscription::query()
                    ->whereNotNull('billing_status')
                    ->where('billing_status', '!=', '')
                    ->distinct()
                    ->orderBy('billing_status')
                    ->pluck('billing_status')
                    ->all(),
                'statuses' => ['active', 'paused', 'blocked'],
                'business_models' => ['product', 'service', 'hybrid'],
            ],
            'metrics' => [
                'filtered_subscribers' => $subscribers->count(),
                'active_subscribers' => $subscribers->where('billing_status', 'active')->count(),
                'trialing_subscribers' => $subscribers->where('billing_status', 'trialing')->count(),
                'blocked_founders' => $subscribers->where('status', 'blocked')->count(),
                'live_websites' => $subscribers->where('website_status', 'live')->count(),
                'mentor_coverage' => $subscribers->filter(fn (array $row) => $row['mentor_name'] !== 'Unassigned')->count(),
                'avg_weekly_progress' => (int) round((float) ($subscribers->avg('weekly_progress_percent') ?? 0)),
                'gross_revenue' => (float) $subscribers->sum('gross_revenue'),
                'new_last_7_days' => $newLast7Days,
                'new_last_30_days' => $newLast30Days,
            ],
            'health' => [
                'on_track' => $subscribers->filter(fn (array $row) => $row['weekly_progress_percent'] >= 60 && $row['status'] === 'active')->count(),
                'watchlist' => $subscribers->filter(fn (array $row) => $row['weekly_progress_percent'] >= 25 && $row['weekly_progress_percent'] < 60)->count(),
                'at_risk' => $subscribers->filter(fn (array $row) => $row['weekly_progress_percent'] < 25 || $row['status'] !== 'active')->count(),
            ],
            'subscribers' => $subscribers->values()->all(),
        ];
    }

    public function buildModuleMonitoring(Founder $admin): array
    {
        $overview = $this->build($admin);

        return [
            'admin' => $admin,
            'module_health' => $overview['module_health'],
            'metrics' => $overview['metrics'],
            'recent_subscribers' => $overview['recent_subscribers'],
            'exceptions' => \App\Models\OsOperationException::query()
                ->with('founder')
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn (\App\Models\OsOperationException $exception): array => [
                    'id' => $exception->id,
                    'module' => strtoupper((string) $exception->module),
                    'operation' => $exception->operation,
                    'status' => $exception->status,
                    'message' => $exception->message,
                    'founder_name' => $exception->founder?->full_name ?: '',
                    'created_at' => optional($exception->created_at)->toDayDateTimeString(),
                ])
                ->all(),
            'recent_audits' => \App\Models\OsAuditLog::query()
                ->with('actor')
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn (\App\Models\OsAuditLog $log): array => [
                    'actor_name' => $log->actor?->full_name ?: 'System',
                    'summary' => $log->summary,
                    'created_at' => optional($log->created_at)->toDayDateTimeString(),
                ])
                ->all(),
        ];
    }

    public function buildSupportWorkspace(Founder $admin): array
    {
        $subscriberReport = $this->buildSubscriberReport($admin);
        $moduleMonitoring = $this->buildModuleMonitoring($admin);

        $urgentSubscribers = collect($subscriberReport['subscribers'])
            ->filter(fn (array $subscriber): bool => $subscriber['status'] !== 'active'
                || $subscriber['weekly_progress_percent'] < 25
                || $subscriber['billing_status'] === 'past_due')
            ->sortBy([
                fn (array $subscriber) => $subscriber['status'] === 'blocked' ? 0 : 1,
                fn (array $subscriber) => $subscriber['weekly_progress_percent'],
            ])
            ->take(12)
            ->values()
            ->all();

        $staleModules = collect($moduleMonitoring['module_health'])
            ->filter(fn (array $module): bool => in_array($module['status'], ['Stale', 'Offline'], true))
            ->values()
            ->all();

        $openExceptions = collect($moduleMonitoring['exceptions'])
            ->filter(fn (array $exception): bool => $exception['status'] !== 'resolved')
            ->values()
            ->all();

        return [
            'admin' => $admin,
            'metrics' => [
                'urgent_founders' => count($urgentSubscribers),
                'open_exceptions' => count($openExceptions),
                'stale_modules' => count($staleModules),
                'watchlist_founders' => $subscriberReport['health']['watchlist'],
            ],
            'urgent_subscribers' => $urgentSubscribers,
            'stale_modules' => $staleModules,
            'exceptions' => $openExceptions,
            'recent_audits' => $moduleMonitoring['recent_audits'],
        ];
    }

    public function buildCommerceWorkspace(Founder $admin, array $filters = []): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $businessModel = trim((string) ($filters['business_model'] ?? ''));
        $engineFilter = trim((string) ($filters['engine'] ?? ''));

        $founders = Founder::query()
            ->where('role', 'founder')
            ->with(['company', 'subscription', 'moduleSnapshots'])
            ->orderBy('full_name')
            ->get()
            ->filter(function (Founder $founder) use ($search, $businessModel, $engineFilter): bool {
                $company = $founder->company;
                $model = (string) ($company?->business_model ?? 'hybrid');
                $supportedEngines = $this->commerceEnginesForModel($model);

                if ($search !== '') {
                    $haystacks = [
                        strtolower((string) $founder->full_name),
                        strtolower((string) $founder->email),
                        strtolower((string) ($company?->company_name ?? '')),
                    ];

                    $matched = collect($haystacks)->contains(fn (string $value): bool => str_contains($value, strtolower($search)));
                    if (!$matched) {
                        return false;
                    }
                }

                if ($businessModel !== '' && $model !== $businessModel) {
                    return false;
                }

                if ($engineFilter !== '' && !in_array($engineFilter, $supportedEngines, true)) {
                    return false;
                }

                return true;
            })
            ->values();

        $founderRows = $founders->map(function (Founder $founder): array {
            $company = $founder->company;
            $model = (string) ($company?->business_model ?? 'hybrid');
            $engines = $this->commerceEnginesForModel($model);
            $bazaarSnapshot = $founder->moduleSnapshots->where('module', 'bazaar')->sortByDesc('snapshot_updated_at')->first();
            $servioSnapshot = $founder->moduleSnapshots->where('module', 'servio')->sortByDesc('snapshot_updated_at')->first();
            $bazaarPayload = is_array($bazaarSnapshot?->payload_json) ? $bazaarSnapshot->payload_json : [];
            $servioPayload = is_array($servioSnapshot?->payload_json) ? $servioSnapshot->payload_json : [];

            return [
                'id' => $founder->id,
                'name' => $founder->full_name,
                'email' => $founder->email,
                'company_name' => (string) ($company?->company_name ?: 'Company not set yet'),
                'business_model' => $model,
                'plan_name' => (string) ($founder->subscription?->plan_name ?? 'Hatchers OS'),
                'website_status' => (string) ($company?->website_status ?? 'not_started'),
                'website_path' => (string) ($company?->website_path ?? ''),
                'engines' => $engines,
                'bazaar' => $this->buildCommerceFounderModuleRow($bazaarSnapshot, $bazaarPayload, 'bazaar'),
                'servio' => $this->buildCommerceFounderModuleRow($servioSnapshot, $servioPayload, 'servio'),
            ];
        })->all();

        $catalog = [
            'bazaar' => $this->buildCatalogIndex($founders, 'bazaar'),
            'servio' => $this->buildCatalogIndex($founders, 'servio'),
        ];

        $recentOperations = [
            'bazaar_products' => $founders
                ->flatMap(function (Founder $founder): array {
                    $snapshot = $founder->moduleSnapshots->where('module', 'bazaar')->sortByDesc('snapshot_updated_at')->first();
                    $payload = is_array($snapshot?->payload_json) ? $snapshot->payload_json : [];

                    return collect($payload['recent_products'] ?? [])
                        ->filter('is_array')
                        ->map(function (array $product) use ($founder): array {
                            return [
                                'founder_id' => $founder->id,
                                'founder_name' => $founder->full_name,
                                'company_name' => (string) ($founder->company?->company_name ?: 'Company not set yet'),
                                'name' => (string) ($product['name'] ?? 'Product'),
                                'category_name' => (string) ($product['category_name'] ?? 'No category'),
                                'price' => (string) ($product['price'] ?? '0'),
                                'status' => (string) ($product['status'] ?? 'inactive'),
                                'variants_count' => count(array_filter($product['variants'] ?? [], 'is_array')),
                                'extras_count' => count(array_filter($product['extras'] ?? [], 'is_array')),
                            ];
                        })
                        ->all();
                })
                ->take(16)
                ->values()
                ->all(),
            'servio_services' => $founders
                ->flatMap(function (Founder $founder): array {
                    $snapshot = $founder->moduleSnapshots->where('module', 'servio')->sortByDesc('snapshot_updated_at')->first();
                    $payload = is_array($snapshot?->payload_json) ? $snapshot->payload_json : [];

                    return collect($payload['recent_services'] ?? [])
                        ->filter('is_array')
                        ->map(function (array $service) use ($founder): array {
                            return [
                                'founder_id' => $founder->id,
                                'founder_name' => $founder->full_name,
                                'company_name' => (string) ($founder->company?->company_name ?: 'Company not set yet'),
                                'name' => (string) ($service['name'] ?? 'Service'),
                                'category_name' => (string) ($service['category_name'] ?? 'No category'),
                                'price' => (string) ($service['price'] ?? '0'),
                                'status' => (string) ($service['status'] ?? 'inactive'),
                                'staff_count' => count(array_filter($service['staff_ids'] ?? [], fn ($value): bool => $value !== null && $value !== '')),
                                'additional_services_count' => count(array_filter($service['additional_services'] ?? [], 'is_array')),
                            ];
                        })
                        ->all();
                })
                ->take(16)
                ->values()
                ->all(),
            'bazaar_orders' => $founders
                ->flatMap(function (Founder $founder): array {
                    $snapshot = $founder->moduleSnapshots->where('module', 'bazaar')->sortByDesc('snapshot_updated_at')->first();
                    $payload = is_array($snapshot?->payload_json) ? $snapshot->payload_json : [];

                    return collect($payload['recent_orders'] ?? [])
                        ->filter('is_array')
                        ->map(function (array $order) use ($founder): array {
                            return [
                                'founder_id' => $founder->id,
                                'founder_name' => $founder->full_name,
                                'company_name' => (string) ($founder->company?->company_name ?: 'Company not set yet'),
                                'number' => (string) ($order['order_number'] ?? $order['id'] ?? 'Order'),
                                'customer_name' => (string) ($order['customer_name'] ?? 'Customer not set'),
                                'status' => (string) ($order['status'] ?? 'pending'),
                                'payment_status' => (string) ($order['payment_status'] ?? 'unpaid'),
                                'delivery_date' => (string) ($order['delivery_date'] ?? ''),
                                'delivery_time' => (string) ($order['delivery_time'] ?? ''),
                                'amount' => (string) ($order['grand_total'] ?? $order['sub_total'] ?? '0'),
                            ];
                        })
                        ->all();
                })
                ->take(16)
                ->values()
                ->all(),
            'servio_bookings' => $founders
                ->flatMap(function (Founder $founder): array {
                    $snapshot = $founder->moduleSnapshots->where('module', 'servio')->sortByDesc('snapshot_updated_at')->first();
                    $payload = is_array($snapshot?->payload_json) ? $snapshot->payload_json : [];

                    return collect($payload['recent_bookings'] ?? [])
                        ->filter('is_array')
                        ->map(function (array $booking) use ($founder): array {
                            return [
                                'founder_id' => $founder->id,
                                'founder_name' => $founder->full_name,
                                'company_name' => (string) ($founder->company?->company_name ?: 'Company not set yet'),
                                'number' => (string) ($booking['booking_number'] ?? $booking['id'] ?? 'Booking'),
                                'customer_name' => (string) ($booking['customer_name'] ?? 'Customer not set'),
                                'service_name' => (string) ($booking['service_name'] ?? 'Service'),
                                'status' => (string) ($booking['status'] ?? 'pending'),
                                'payment_status' => (string) ($booking['payment_status'] ?? 'unpaid'),
                                'booking_date' => (string) ($booking['booking_date'] ?? ''),
                                'booking_time' => (string) ($booking['booking_time'] ?? ''),
                                'booking_endtime' => (string) ($booking['booking_endtime'] ?? ''),
                            ];
                        })
                        ->all();
                })
                ->take(16)
                ->values()
                ->all(),
        ];

        $metrics = [
            'founders' => count($founderRows),
            'product_founders' => collect($founderRows)->where('business_model', 'product')->count(),
            'service_founders' => collect($founderRows)->where('business_model', 'service')->count(),
            'hybrid_founders' => collect($founderRows)->where('business_model', 'hybrid')->count(),
            'live_websites' => collect($founderRows)->where('website_status', 'live')->count(),
            'bazaar_orders' => (int) collect($founderRows)->sum(fn (array $row): int => (int) ($row['bazaar']['summary']['order_count'] ?? 0)),
            'servio_bookings' => (int) collect($founderRows)->sum(fn (array $row): int => (int) ($row['servio']['summary']['booking_count'] ?? 0)),
            'open_queue_items' => (int) collect($founderRows)->sum(fn (array $row): int => count($row['bazaar']['attention_items']) + count($row['servio']['attention_items'])),
            'catalog_terms' => count($catalog['bazaar']['categories']) + count($catalog['bazaar']['taxes']) + count($catalog['servio']['categories']) + count($catalog['servio']['taxes']),
        ];

        $reliabilityQueue = collect($founderRows)
            ->flatMap(function (array $row): array {
                $items = [];
                foreach (['bazaar', 'servio'] as $engine) {
                    $module = $row[$engine];
                    if (in_array($engine, $row['engines'], true)) {
                        if ($module['status'] !== 'Healthy') {
                            $items[] = [
                                'founder_name' => $row['name'],
                                'company_name' => $row['company_name'],
                                'engine' => strtoupper($engine),
                                'issue' => $module['status_reason'],
                                'last_synced_at' => $module['last_synced_at'],
                            ];
                        }

                        foreach ($module['attention_items'] as $attention) {
                            $items[] = [
                                'founder_name' => $row['name'],
                                'company_name' => $row['company_name'],
                                'engine' => strtoupper($engine),
                                'issue' => $attention,
                                'last_synced_at' => $module['last_synced_at'],
                            ];
                        }
                    }
                }

                return $items;
            })
            ->take(20)
            ->values()
            ->all();

        return [
            'admin' => $admin,
            'filters' => [
                'search' => $search,
                'business_model' => $businessModel,
                'engine' => $engineFilter,
            ],
            'filter_options' => [
                'business_models' => ['product', 'service', 'hybrid'],
                'engines' => ['bazaar', 'servio'],
            ],
            'metrics' => $metrics,
            'founders' => $founderRows,
            'catalog' => $catalog,
            'reliability_queue' => $reliabilityQueue,
            'recent_operations' => $recentOperations,
        ];
    }

    private function resolveModuleStatus(int $syncedFounders, ?int $hoursSinceSync): array
    {
        if ($syncedFounders === 0 || $hoursSinceSync === null) {
            return [
                'label' => 'Offline',
                'tone' => 'danger',
                'reason' => 'No module snapshots have reached Hatchers Ai OS yet.',
            ];
        }

        if ($hoursSinceSync <= 24) {
            return [
                'label' => 'Healthy',
                'tone' => 'success',
                'reason' => 'Recent module snapshots are flowing into the OS.',
            ];
        }

        if ($hoursSinceSync <= 72) {
            return [
                'label' => 'Stale',
                'tone' => 'warning',
                'reason' => 'Module snapshots exist, but they are aging and need refresh attention.',
            ];
        }

        return [
            'label' => 'Offline',
            'tone' => 'danger',
            'reason' => 'This module has not synced recently enough to be trusted operationally.',
        ];
    }

    private function applySubscriberFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('full_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhereHas('company', fn (Builder $company) => $company->where('company_name', 'like', '%' . $search . '%'));
            });
        }

        if (!empty($filters['plan_code'])) {
            $query->whereHas('subscription', fn (Builder $subscription) => $subscription->where('plan_code', $filters['plan_code']));
        }

        if (!empty($filters['billing_status'])) {
            $query->whereHas('subscription', fn (Builder $subscription) => $subscription->where('billing_status', $filters['billing_status']));
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['business_model'])) {
            $query->whereHas('company', fn (Builder $company) => $company->where('business_model', $filters['business_model']));
        }
    }

    private function commerceEnginesForModel(string $businessModel): array
    {
        return match ($businessModel) {
            'product' => ['bazaar'],
            'service' => ['servio'],
            default => ['bazaar', 'servio'],
        };
    }

    private function buildCommerceFounderModuleRow(?ModuleSnapshot $snapshot, array $payload, string $module): array
    {
        $lastSyncedAt = $snapshot?->snapshot_updated_at;
        $hoursSinceSync = $lastSyncedAt?->diffInHours(now());
        $status = $this->resolveModuleStatus($snapshot ? 1 : 0, $hoursSinceSync);
        $summary = is_array($payload['summary'] ?? null) ? $payload['summary'] : [];

        $attentionItems = [];
        if ($module === 'bazaar') {
            $orderCount = (int) ($summary['order_count'] ?? count($payload['recent_orders'] ?? []));
            $productCount = (int) ($summary['product_count'] ?? count($payload['recent_products'] ?? []));
            if ($productCount === 0) {
                $attentionItems[] = 'No synced products yet.';
            }
            if ($orderCount > 0 && collect($payload['recent_orders'] ?? [])->contains(fn ($order) => (($order['payment_status'] ?? '') !== 'paid'))) {
                $attentionItems[] = 'There are unpaid Bazaar orders needing follow-up.';
            }
        } else {
            $serviceCount = (int) ($summary['service_count'] ?? count($payload['recent_services'] ?? []));
            $bookingCount = (int) ($summary['booking_count'] ?? count($payload['recent_bookings'] ?? []));
            if ($serviceCount === 0) {
                $attentionItems[] = 'No synced services yet.';
            }
            if ($bookingCount > 0 && collect($payload['recent_bookings'] ?? [])->contains(function ($booking): bool {
                return empty($booking['booking_date']) || empty($booking['booking_time']) || empty($booking['booking_endtime']);
            })) {
                $attentionItems[] = 'There are bookings without complete scheduling details.';
            }
        }

        return [
            'status' => $status['label'],
            'status_tone' => $status['tone'],
            'status_reason' => $status['reason'],
            'last_synced_at' => optional($lastSyncedAt)->toDateTimeString(),
            'readiness_score' => (int) ($snapshot?->readiness_score ?? 0),
            'summary' => $summary,
            'counts' => is_array($payload['key_counts'] ?? null) ? $payload['key_counts'] : [],
            'attention_items' => $attentionItems,
        ];
    }

    private function buildCatalogIndex($founders, string $module): array
    {
        $payloads = $founders->map(function (Founder $founder) use ($module): array {
            $snapshot = $founder->moduleSnapshots->where('module', $module)->sortByDesc('snapshot_updated_at')->first();
            return is_array($snapshot?->payload_json) ? $snapshot->payload_json : [];
        });

        $categories = $payloads
            ->flatMap(fn (array $payload): array => array_values(array_filter($payload['recent_categories'] ?? [], 'is_array')))
            ->pluck('name')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $taxes = $payloads
            ->flatMap(fn (array $payload): array => array_values(array_filter($payload['recent_taxes'] ?? [], 'is_array')))
            ->map(function (array $tax): string {
                $name = (string) ($tax['name'] ?? 'Tax');
                $value = (string) ($tax['value'] ?? '');
                $type = (string) ($tax['type'] ?? '');
                return trim($name . ($value !== '' ? ' · ' . $value : '') . ($type !== '' ? ' · ' . $type : ''));
            })
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($module === 'bazaar') {
            return [
                'categories' => $categories,
                'taxes' => $taxes,
                'extras' => $payloads
                    ->flatMap(function (array $payload): array {
                        return collect($payload['recent_products'] ?? [])
                            ->filter('is_array')
                            ->flatMap(function (array $product): array {
                                return array_values(array_filter($product['extras'] ?? [], 'is_array'));
                            })
                            ->map(function (array $extra): string {
                                $name = (string) ($extra['name'] ?? 'Extra');
                                $price = (string) ($extra['price'] ?? '');
                                return trim($name . ($price !== '' ? ' · ' . $price : ''));
                            })
                            ->all();
                    })
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->all(),
                'products' => $payloads
                    ->flatMap(fn (array $payload): array => array_values(array_filter($payload['recent_products'] ?? [], 'is_array')))
                    ->take(12)
                    ->values()
                    ->all(),
            ];
        }

        return [
            'categories' => $categories,
            'taxes' => $taxes,
            'staff' => $payloads
                ->flatMap(fn (array $payload): array => array_values(array_filter($payload['recent_staff'] ?? [], 'is_array')))
                ->unique(fn (array $staff): string => (string) ($staff['id'] ?? $staff['name'] ?? ''))
                ->take(20)
                ->values()
                ->all(),
            'services' => $payloads
                ->flatMap(fn (array $payload): array => array_values(array_filter($payload['recent_services'] ?? [], 'is_array')))
                ->take(12)
                ->values()
                ->all(),
            'additional_services' => $payloads
                ->flatMap(fn (array $payload): array => array_values(array_filter($payload['recent_additional_services'] ?? [], 'is_array')))
                ->map(function (array $item): string {
                    $name = (string) ($item['name'] ?? 'Add-on');
                    $price = (string) ($item['price'] ?? '');
                    return trim($name . ($price !== '' ? ' · ' . $price : ''));
                })
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all(),
        ];
    }
}
