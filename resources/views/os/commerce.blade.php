@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page { padding: 0; }
        .commerce-shell { min-height:100vh; display:grid; grid-template-columns:220px minmax(0,1fr) 220px; background:#f8f5ee; }
        .commerce-sidebar, .commerce-rightbar { background:rgba(255,252,247,0.8); border-color:var(--line); border-style:solid; border-width:0 1px 0 0; min-height:100vh; display:flex; flex-direction:column; }
        .commerce-rightbar { border-width:0 0 0 1px; background:rgba(255,251,246,0.9); }
        .commerce-sidebar-inner, .commerce-rightbar-inner { padding:22px 18px; }
        .commerce-brand { display:inline-block; margin-bottom:24px; }
        .commerce-brand img { width:168px; height:auto; display:block; }
        .commerce-nav { display:grid; gap:6px; }
        .commerce-nav-item { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:14px; text-decoration:none; color:var(--ink); font-size:0.98rem; }
        .commerce-nav-item.active { background:#ece6db; }
        .commerce-nav-icon { width:18px; text-align:center; color:var(--muted); }
        .commerce-sidebar-footer { margin-top:auto; padding:18px; border-top:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .commerce-user { display:flex; align-items:center; gap:10px; }
        .commerce-avatar { width:30px; height:30px; border-radius:999px; background:#b0a999; color:#fff; display:grid; place-items:center; font-weight:700; font-size:0.92rem; flex-shrink:0; }
        .commerce-main { padding:26px 28px 24px; }
        .commerce-main-inner { max-width:820px; margin:0 auto; }
        .commerce-main h1 { font-size: clamp(2rem, 3vw, 3rem); letter-spacing:-0.02em; margin-bottom:6px; }
        .commerce-main p { color:var(--muted); margin-bottom:24px; }
        .commerce-banner { border-radius:16px; padding:14px 16px; border:1px solid rgba(220,207,191,0.8); background:rgba(255,255,255,0.9); margin-bottom:14px; }
        .commerce-banner.success { border-color:rgba(44,122,87,0.26); background:rgba(226,245,236,0.9); }
        .commerce-banner.error { border-color:rgba(179,34,83,0.22); background:rgba(255,241,246,0.92); }
        .commerce-metrics { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:12px; margin-bottom:22px; }
        .commerce-metric { background:rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:18px; padding:16px 18px; box-shadow:0 10px 28px rgba(52,41,26,0.04); }
        .commerce-metric strong { display:block; font-size:1.55rem; margin-top:6px; }
        .commerce-section { margin-bottom:22px; }
        .commerce-section h2 { font-size:1.08rem; margin-bottom:12px; }
        .commerce-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:12px; }
        .commerce-card, .rail-item, .mini-note { background:rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:18px; padding:18px 18px 16px; box-shadow:0 10px 28px rgba(52,41,26,0.04); }
        .commerce-card-title { font-size:1rem; font-weight:700; margin-bottom:6px; }
        .commerce-card-copy { color:var(--muted); font-size:0.95rem; line-height:1.45; }
        .commerce-card-meta { font-size:0.82rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--rose); margin-bottom:8px; }
        .commerce-chip { display:inline-block; margin-top:12px; padding:8px 14px; border-radius:10px; background:#f0ece4; color:#7a7267; font-size:0.92rem; }
        .commerce-cta, .commerce-secondary { display:inline-block; margin-top:14px; padding:10px 14px; border-radius:10px; text-decoration:none; font-weight:600; border:0; cursor:pointer; font:inherit; }
        .commerce-cta { background:linear-gradient(90deg,#8e1c74,#ff2c35); color:white; }
        .commerce-secondary { background:#f0ece4; color:#5d554a; }
        .commerce-field { display:grid; gap:8px; }
        .commerce-field label { font-size:0.92rem; font-weight:600; }
        .commerce-field input, .commerce-field textarea, .commerce-field select { width:100%; border:1px solid rgba(220,207,191,0.9); background:#fff; border-radius:12px; padding:12px 14px; font:inherit; color:var(--ink); }
        .commerce-field textarea { min-height:100px; resize:vertical; }
        .commerce-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:14px; }
        .commerce-rightbar h3 { font-size:0.83rem; letter-spacing:0.06em; text-transform:uppercase; color:var(--muted); margin-bottom:12px; }
        .rail-list { display:grid; gap:10px; margin-top:14px; }
        .commerce-view-nav { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
        .commerce-view-tab { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:999px; text-decoration:none; color:var(--ink); background:rgba(255,255,255,0.88); border:1px solid rgba(220,207,191,0.8); font-weight:600; }
        .commerce-view-tab.active { background:#ece6db; }
        .commerce-helper { margin-bottom:18px; padding:14px 16px; border-radius:16px; background:rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.7); color:var(--muted); }
        @media (max-width:1240px) { .commerce-shell { grid-template-columns:220px 1fr; } .commerce-rightbar { display:none; } }
        @media (max-width:900px) { .commerce-shell { grid-template-columns:1fr; } .commerce-sidebar { min-height:auto; border-right:0; border-bottom:1px solid var(--line); } .commerce-sidebar-footer { display:none; } .commerce-main { padding:20px 16px 24px; } .commerce-grid, .commerce-metrics { grid-template-columns:1fr; } }
    </style>
@endsection

@section('content')
    @php
        $founder = $dashboard['founder'];
        $growth = $dashboard['growth'];
        $businessModel = $website['business_model'];
        $engines = collect($website['engines']);
        $catalogOffers = $catalogOffers ?? [];
        $commerceConfigs = $commerceConfigs ?? ['coupon' => [], 'shipping' => [], 'booking_policy' => []];
        $commerceCatalogs = $commerceCatalogs ?? ['bazaar' => ['categories' => [], 'taxes' => []], 'servio' => ['categories' => [], 'taxes' => [], 'additional_services' => []]];
        $pricingOptimizer = $pricingOptimizer ?? ['headline' => 'Offer & Pricing Optimizer', 'currency' => 'USD', 'price_story' => '', 'best_offer_name' => '', 'best_channel_label' => '', 'bundle_logic' => '', 'recommendations' => [], 'upsells' => [], 'conversion_notes' => []];
        $walletSummary = $walletSummary ?? ['available_balance' => 0, 'pending_balance' => 0, 'reserved_balance' => 0, 'minimum_payout_amount' => 50, 'currency' => 'USD', 'recent_entries' => []];
        $payoutAccount = $payoutAccount ?? null;
        $recentPayoutRequests = $recentPayoutRequests ?? collect();
        $supportsProducts = in_array($businessModel, ['product', 'hybrid'], true);
        $supportsServices = in_array($businessModel, ['service', 'hybrid'], true);
        $automationSummary = $dashboard['automation_summary'] ?? ['active_count' => 0, 'items' => [], 'has_unpaid_order_rule' => false, 'has_unscheduled_booking_rule' => false, 'has_provider_assignment_rule' => false];
        $commerceHeading = match ($businessModel) {
            'service' => 'Manage your services, booking rules, and delivery flow from one OS workspace.',
            'product' => 'Manage your products, discounts, shipping plans, and orders from one OS workspace.',
            default => 'Manage your products, services, storefront readiness, orders, and bookings from one OS workspace.',
        };
        $commerceView = request()->query('view', 'overview');
        if (!in_array($commerceView, ['overview', 'offers', 'operations', 'money'], true)) {
            $commerceView = 'overview';
        }
        $commerceViewHelp = [
            'overview' => 'Start here to understand where the business stands today before changing offers or payout settings.',
            'offers' => 'Use this mode when you are shaping what you sell, how it is priced, and how it appears in the storefront.',
            'operations' => 'Use this mode when you need to manage orders, bookings, coupons, shipping, or service policies.',
            'money' => 'Use this mode for wallet balance, payout setup, withdrawals, and payment operations.',
        ];
    @endphp

    <div class="commerce-shell">
        <aside class="commerce-sidebar">
            @include('os.partials.founder-sidebar', [
                'founder' => $founder,
                'businessModel' => $businessModel,
                'activeKey' => 'commerce',
                'navClass' => 'commerce-nav',
                'itemClass' => 'commerce-nav-item',
                'iconClass' => 'commerce-nav-icon',
                'innerClass' => 'commerce-sidebar-inner',
                'brandClass' => 'commerce-brand',
                'footerClass' => 'commerce-sidebar-footer',
                'userClass' => 'commerce-user',
                'avatarClass' => 'commerce-avatar',
            ])
        </aside>

        <main class="commerce-main">
            <div class="commerce-main-inner">
                <h1>Commerce</h1>
                <p>{{ $commerceHeading }}</p>
                <div class="commerce-view-nav">
                    <a class="commerce-view-tab {{ $commerceView === 'overview' ? 'active' : '' }}" href="{{ route('founder.commerce', ['view' => 'overview']) }}">Overview</a>
                    <a class="commerce-view-tab {{ $commerceView === 'offers' ? 'active' : '' }}" href="{{ route('founder.commerce', ['view' => 'offers']) }}">Offers</a>
                    <a class="commerce-view-tab {{ $commerceView === 'operations' ? 'active' : '' }}" href="{{ route('founder.commerce', ['view' => 'operations']) }}">Operations</a>
                    <a class="commerce-view-tab {{ $commerceView === 'money' ? 'active' : '' }}" href="{{ route('founder.commerce', ['view' => 'money']) }}">Money</a>
                </div>
                <div class="commerce-helper">{{ $commerceViewHelp[$commerceView] }}</div>

                @if (session('success'))
                    <div class="commerce-banner success">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="commerce-banner error">{{ session('error') }}</div>
                @endif

                <section class="commerce-metrics">
                    @if ($supportsProducts)
                        <div class="commerce-metric"><div class="commerce-card-copy">Products</div><strong>{{ $growth['product_count'] }}</strong></div>
                        <div class="commerce-metric"><div class="commerce-card-copy">Orders</div><strong>{{ $growth['order_count'] }}</strong></div>
                    @endif
                    @if ($supportsServices)
                        <div class="commerce-metric"><div class="commerce-card-copy">Services</div><strong>{{ $growth['service_count'] }}</strong></div>
                        <div class="commerce-metric"><div class="commerce-card-copy">Bookings</div><strong>{{ $growth['booking_count'] }}</strong></div>
                    @endif
                    <div class="commerce-metric"><div class="commerce-card-copy">Customers</div><strong>{{ $growth['customer_count'] }}</strong></div>
                    <div class="commerce-metric"><div class="commerce-card-copy">Revenue</div><strong>{{ $growth['gross_revenue_formatted'] }}</strong></div>
                </section>

                @if ($commerceView === 'overview')
                <section class="commerce-section">
                    <h2>Connected Engine{{ $engines->count() > 1 ? 's' : '' }}</h2>
                    <div class="commerce-grid">
                        @foreach ($engines as $engine)
                            <div class="commerce-card">
                                <div class="commerce-card-meta">{{ $engine['label'] }}</div>
                                <div class="commerce-card-title">{{ $engine['website_title'] }}</div>
                                <div class="commerce-card-copy">{{ $engine['summary'] }}</div>
                                <div class="commerce-chip">Theme: {{ $engine['theme'] }}</div>
                                <div class="commerce-chip">Readiness: {{ $engine['readiness_score'] }}%</div>
                                <div class="commerce-actions">
                                    <a class="commerce-cta" href="{{ route('website') }}">Open website workspace</a>
                                    @if ($engine['key'] === 'bazaar' && $supportsProducts)
                                        <a class="commerce-secondary" href="{{ route('founder.commerce.orders') }}">Orders</a>
                                    @endif
                                    @if ($engine['key'] === 'servio' && $supportsServices)
                                        <a class="commerce-secondary" href="{{ route('founder.commerce.bookings') }}">Bookings</a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
                @endif

                @if ($commerceView === 'offers')
                <section class="commerce-section">
                    <h2>Live Catalog Sync</h2>
                    <div class="commerce-grid">
                        @if ($supportsProducts)
                            <div class="commerce-card">
                                <div class="commerce-card-meta">Product Categories & Taxes</div>
                                <div class="commerce-card-title">Live product catalog definitions</div>
                                <div class="commerce-card-copy">These are the current product categories and tax rules visible to the OS after sync.</div>
                                @forelse ($commerceCatalogs['bazaar']['categories'] as $category)
                                    <div class="commerce-chip">{{ $category['title'] }} · {{ ucfirst($category['status']) }}</div>
                                @empty
                                    <div class="commerce-chip">No product categories synced yet</div>
                                @endforelse
                                @forelse ($commerceCatalogs['bazaar']['taxes'] as $tax)
                                    <div class="commerce-chip">{{ $tax['title'] }} · {{ $tax['value'] }} {{ $tax['type'] }} · {{ ucfirst($tax['status']) }}</div>
                                @empty
                                    <div class="commerce-chip">No product taxes synced yet</div>
                                @endforelse
                                @foreach (($commerceCatalogs['bazaar']['products'] ?? []) as $product)
                                    @foreach (($product['variants'] ?? []) as $variant)
                                        <div class="commerce-chip">{{ $product['title'] }} → {{ $variant['name'] }} · {{ $variant['qty'] }} in stock</div>
                                    @endforeach
                                    @foreach (($product['extras'] ?? []) as $extra)
                                        <div class="commerce-chip">{{ $product['title'] }} + {{ $extra['name'] }} · {{ number_format((float) ($extra['price'] ?? 0), 2) }}</div>
                                    @endforeach
                                @endforeach
                            </div>
                        @endif
                        @if ($supportsServices)
                            <div class="commerce-card">
                                <div class="commerce-card-meta">Service Categories, Taxes, And Add-ons</div>
                                <div class="commerce-card-title">Live service catalog definitions</div>
                                <div class="commerce-card-copy">These are the current service categories, tax rules, and service add-ons visible to the OS after sync.</div>
                                @forelse ($commerceCatalogs['servio']['categories'] as $category)
                                    <div class="commerce-chip">{{ $category['title'] }} · {{ ucfirst($category['status']) }}</div>
                                @empty
                                    <div class="commerce-chip">No service categories synced yet</div>
                                @endforelse
                                @forelse ($commerceCatalogs['servio']['taxes'] as $tax)
                                    <div class="commerce-chip">{{ $tax['title'] }} · {{ $tax['value'] }} {{ $tax['type'] }} · {{ ucfirst($tax['status']) }}</div>
                                @empty
                                    <div class="commerce-chip">No service taxes synced yet</div>
                                @endforelse
                                @forelse ($commerceCatalogs['servio']['additional_services'] as $service)
                                    <div class="commerce-chip">{{ $service['title'] }} · {{ number_format((float) ($service['price'] ?? 0), 2) }}</div>
                                @empty
                                    <div class="commerce-chip">No service add-ons synced yet</div>
                                @endforelse
                                @foreach (($commerceCatalogs['servio']['staff'] ?? []) as $staff)
                                    <div class="commerce-chip">{{ $staff['title'] }} · ID {{ $staff['id'] }}</div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>

                <section class="commerce-section">
                    <h2>{{ $pricingOptimizer['headline'] }}</h2>
                    <div class="commerce-grid">
                        <div class="commerce-card">
                            <div class="commerce-card-meta">Price Story</div>
                            <div class="commerce-card-title">How the OS thinks your offer should sell</div>
                            <div class="commerce-card-copy">{{ $pricingOptimizer['price_story'] }}</div>
                            @if ($pricingOptimizer['best_offer_name'] !== '')
                                <div class="commerce-chip">Best offer signal: {{ $pricingOptimizer['best_offer_name'] }}</div>
                            @endif
                            @if ($pricingOptimizer['best_channel_label'] !== '')
                                <div class="commerce-chip">Best channel: {{ $pricingOptimizer['best_channel_label'] }}</div>
                            @endif
                            <div class="commerce-card-copy" style="margin-top:12px;">{{ $pricingOptimizer['bundle_logic'] }}</div>
                        </div>
                        <div class="commerce-card">
                            <div class="commerce-card-meta">Conversion Notes</div>
                            <div class="commerce-card-title">What to tighten first</div>
                            @foreach ($pricingOptimizer['conversion_notes'] as $note)
                                <div class="commerce-chip">{{ $note }}</div>
                            @endforeach
                        </div>
                    </div>
                    <div class="commerce-grid" style="margin-top:12px;">
                        @foreach ($pricingOptimizer['recommendations'] as $recommendation)
                            <div class="commerce-card">
                                <div class="commerce-card-meta">{{ strtoupper($recommendation['positioning']) }}</div>
                                <div class="commerce-card-title">{{ $recommendation['title'] }}</div>
                                <div class="commerce-card-copy">{{ $recommendation['description'] }}</div>
                                <div class="commerce-chip">{{ $pricingOptimizer['currency'] }} {{ number_format((float) $recommendation['price'], 2) }}</div>
                                <div class="commerce-chip">Status: {{ ucfirst(str_replace('_', ' ', $recommendation['status'] ?? 'generated')) }}</div>
                                <form method="POST" action="{{ route('founder.commerce.pricing.apply', $recommendation['id']) }}" style="margin-top:12px;">
                                    @csrf
                                    @if (!empty($catalogOffers))
                                        <div class="commerce-field">
                                            <label for="pricing-target-{{ $recommendation['id'] }}">Apply to offer</label>
                                            <select id="pricing-target-{{ $recommendation['id'] }}" name="target_action_plan_id">
                                                @foreach ($catalogOffers as $offer)
                                                    <option value="{{ $offer['id'] }}" @selected((int) ($recommendation['target_action_plan_id'] ?? 0) === (int) $offer['id'])>
                                                        {{ $offer['title'] }} · {{ strtoupper($offer['engine']) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                    <div class="commerce-actions">
                                        <button class="commerce-cta" type="submit">
                                            {{ ($recommendation['status'] ?? 'generated') === 'applied' ? 'Reapply Recommendation' : 'Apply Recommendation' }}
                                        </button>
                                    </div>
                                </form>
                                @if (($recommendation['status'] ?? 'generated') !== 'applied')
                                    <form method="POST" action="{{ route('founder.commerce.pricing.status', $recommendation['id']) }}" style="margin-top:10px;">
                                        @csrf
                                        <input type="hidden" name="status" value="rejected">
                                        <button class="commerce-secondary" type="submit">Reject Recommendation</button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                        @if (!empty($pricingOptimizer['upsells']))
                            <div class="commerce-card">
                                <div class="commerce-card-meta">Upsells</div>
                                <div class="commerce-card-title">Attach simple add-ons after the yes</div>
                                @foreach ($pricingOptimizer['upsells'] as $upsell)
                                    <div class="commerce-chip">{{ $upsell['title'] }} · {{ $pricingOptimizer['currency'] }} {{ number_format((float) $upsell['price'], 2) }}</div>
                                    <div class="commerce-card-copy" style="margin-top:8px;">{{ $upsell['why'] }}</div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>

                <section class="commerce-section">
                    <h2>OS-Native Offer Setup</h2>
                    <div class="commerce-grid">
                        @if ($supportsProducts)
                            <div class="commerce-card">
                                <div class="commerce-card-meta">Product Manager</div>
                                <div class="commerce-card-title">Add a product from the OS</div>
                                <div class="commerce-card-copy">Create the first product here.</div>
                                <form method="POST" action="/website/starter" class="commerce-field">
                                    @csrf
                                    <input type="hidden" name="website_engine" value="bazaar">
                                    <input type="hidden" name="starter_mode" value="product">
                                    <label for="product-title">Product title</label>
                                    <input id="product-title" name="starter_title" type="text" placeholder="Core product name" required>
                                    <label for="product-description">Product description</label>
                                    <textarea id="product-description" name="starter_description" placeholder="What does the product do and who is it for?"></textarea>
                                    <label for="product-price">Price</label>
                                    <input id="product-price" name="starter_price" type="number" step="0.01" min="0" placeholder="99">
                                    <div class="commerce-actions"><button class="commerce-cta" type="submit">Create product</button></div>
                                </form>
                            </div>
                        @endif

                        @if ($supportsServices)
                            <div class="commerce-card">
                                <div class="commerce-card-meta">Services Manager</div>
                                <div class="commerce-card-title">Add a service from the OS</div>
                                <div class="commerce-card-copy">Create the first service here.</div>
                                <form method="POST" action="/website/starter" class="commerce-field">
                                    @csrf
                                    <input type="hidden" name="website_engine" value="servio">
                                    <input type="hidden" name="starter_mode" value="service">
                                    <label for="service-title">Service title</label>
                                    <input id="service-title" name="starter_title" type="text" placeholder="Core service name" required>
                                    <label for="service-description">Service description</label>
                                    <textarea id="service-description" name="starter_description" placeholder="What outcome does this service deliver?"></textarea>
                                    <label for="service-price">Price</label>
                                    <input id="service-price" name="starter_price" type="number" step="0.01" min="0" placeholder="250">
                                    <div class="commerce-actions"><button class="commerce-cta" type="submit">Create service</button></div>
                                </form>
                            </div>
                        @endif
                    </div>
                </section>

                <section class="commerce-section">
                    <h2>Offer Manager</h2>
                    <div class="commerce-grid">
                        @php
                            $visibleOffers = collect($catalogOffers)->filter(function ($offer) use ($supportsProducts, $supportsServices) {
                                return ($supportsProducts && $offer['engine'] === 'bazaar') || ($supportsServices && $offer['engine'] === 'servio');
                            })->values();
                        @endphp
                        @forelse ($visibleOffers as $offer)
                            <div class="commerce-card">
                                <div class="commerce-card-meta">{{ strtoupper($offer['type']) }} · {{ strtoupper($offer['engine']) }}</div>
                                <div class="commerce-card-title">{{ $offer['title'] }}</div>
                                <div class="commerce-card-copy">Last updated {{ $offer['updated_at'] ?? 'recently' }}.</div>
                                <form method="POST" action="{{ route('founder.commerce.offer.update', $offer['id']) }}" class="commerce-field" style="margin-top:14px;">
                                    @csrf
                                    <label for="offer-title-{{ $offer['id'] }}">Title</label>
                                    <input id="offer-title-{{ $offer['id'] }}" name="title" type="text" value="{{ $offer['title'] }}" required>
                                    <label for="offer-description-{{ $offer['id'] }}">Description</label>
                                    <textarea id="offer-description-{{ $offer['id'] }}" name="description">{{ $offer['description'] }}</textarea>
                                    <label for="offer-price-{{ $offer['id'] }}">Price</label>
                                    <input id="offer-price-{{ $offer['id'] }}" name="price" type="number" step="0.01" min="0" value="{{ $offer['price'] }}">
                                    <label for="offer-category-{{ $offer['id'] }}">Category</label>
                                    <input id="offer-category-{{ $offer['id'] }}" name="category_name" type="text" value="{{ $offer['category_name'] ?? '' }}" placeholder="{{ $offer['engine'] === 'bazaar' ? 'Treats, Accessories, Grooming' : 'Walking, Training, Home visits' }}">
                                    <label for="offer-tax-rules-{{ $offer['id'] }}">Tax rules</label>
                                    <textarea id="offer-tax-rules-{{ $offer['id'] }}" name="tax_rules_text" placeholder="VAT | 15 | percent&#10;Service fee | 5 | fixed">{{ $offer['tax_rules_text'] ?? '' }}</textarea>
                                    <label for="offer-payment-collection-{{ $offer['id'] }}">Payment options</label>
                                    <select id="offer-payment-collection-{{ $offer['id'] }}" name="payment_collection">
                                        <option value="both" @selected(($offer['payment_collection'] ?? 'both') === 'both')>Pay online or cash</option>
                                        <option value="online_only" @selected(($offer['payment_collection'] ?? 'both') === 'online_only')>Pay online only</option>
                                        <option value="cash_only" @selected(($offer['payment_collection'] ?? 'both') === 'cash_only')>{{ $offer['engine'] === 'bazaar' ? 'Cash on delivery only' : 'Cash on booking only' }}</option>
                                    </select>
                                    @if ($offer['engine'] === 'bazaar')
                                        <label for="offer-sku-{{ $offer['id'] }}">SKU</label>
                                        <input id="offer-sku-{{ $offer['id'] }}" name="sku" type="text" value="{{ $offer['sku'] }}">
                                        <label for="offer-stock-{{ $offer['id'] }}">Stock quantity</label>
                                        <input id="offer-stock-{{ $offer['id'] }}" name="stock" type="number" min="0" step="1" value="{{ $offer['stock'] }}">
                                        <label for="offer-low-stock-{{ $offer['id'] }}">Low stock alert</label>
                                        <input id="offer-low-stock-{{ $offer['id'] }}" name="low_stock" type="number" min="0" step="1" value="{{ $offer['low_stock'] }}">
                                        <label for="offer-adjustment-mode-{{ $offer['id'] }}">Quick stock movement</label>
                                        <select id="offer-adjustment-mode-{{ $offer['id'] }}" name="adjustment_mode">
                                            <option value="">No stock movement</option>
                                            <option value="increase">Increase stock</option>
                                            <option value="decrease">Decrease stock</option>
                                            <option value="set">Set stock to exact quantity</option>
                                        </select>
                                        <label for="offer-adjustment-amount-{{ $offer['id'] }}">Adjustment quantity</label>
                                        <input id="offer-adjustment-amount-{{ $offer['id'] }}" name="adjustment_amount" type="number" min="1" step="1" placeholder="10">
                                        <label for="offer-variants-{{ $offer['id'] }}">Variants</label>
                                        <textarea id="offer-variants-{{ $offer['id'] }}" name="variants_text" placeholder="Small | 19.99 | 12 | 3&#10;Large | 29.99 | 8 | 2">{{ $offer['variants_text'] ?? '' }}</textarea>
                                        <label for="offer-extras-{{ $offer['id'] }}">Extras</label>
                                        <textarea id="offer-extras-{{ $offer['id'] }}" name="extras_text" placeholder="Gift wrap | 5&#10;Rush packaging | 10">{{ $offer['extras_text'] ?? '' }}</textarea>
                                    @else
                                        <label for="offer-duration-{{ $offer['id'] }}">Duration</label>
                                        <input id="offer-duration-{{ $offer['id'] }}" name="duration" type="number" min="1" step="1" value="{{ $offer['duration'] }}">
                                        <label for="offer-duration-unit-{{ $offer['id'] }}">Duration unit</label>
                                        <select id="offer-duration-unit-{{ $offer['id'] }}" name="duration_unit">
                                            <option value="minutes" @selected(($offer['duration_unit'] ?? 'minutes') === 'minutes')>Minutes</option>
                                            <option value="hours" @selected(($offer['duration_unit'] ?? 'minutes') === 'hours')>Hours</option>
                                        </select>
                                        <label for="offer-capacity-{{ $offer['id'] }}">Bookings per slot</label>
                                        <input id="offer-capacity-{{ $offer['id'] }}" name="capacity" type="number" min="1" step="1" value="{{ $offer['capacity'] }}">
                                        <label for="offer-staff-mode-{{ $offer['id'] }}">Staff assignment</label>
                                        <select id="offer-staff-mode-{{ $offer['id'] }}" name="staff_mode">
                                            <option value="auto" @selected(($offer['staff_mode'] ?? 'auto') === 'auto')>Any available staff</option>
                                            <option value="specific" @selected(($offer['staff_mode'] ?? 'auto') === 'specific')>Specific staff member</option>
                                        </select>
                                        <label for="offer-staff-id-{{ $offer['id'] }}">Staff member id</label>
                                        <input id="offer-staff-id-{{ $offer['id'] }}" name="staff_id" type="text" value="{{ $offer['staff_id'] }}" placeholder="Optional specific staff id">
                                        <label for="offer-staff-ids-{{ $offer['id'] }}">Assignable staff ids</label>
                                        <input id="offer-staff-ids-{{ $offer['id'] }}" name="staff_ids_text" type="text" value="{{ $offer['staff_ids_text'] ?? '' }}" placeholder="12, 18, 24">
                                        <label><span>Available days</span>
                                            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;">
                                                @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                                    <label style="display:inline-flex;align-items:center;gap:6px;font-weight:500;">
                                                        <input type="checkbox" name="availability_days[]" value="{{ $day }}" @checked(in_array($day, $offer['availability_days'] ?? [], true))>
                                                        <span>{{ substr($day, 0, 3) }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </label>
                                        <label for="offer-open-time-{{ $offer['id'] }}">Open time</label>
                                        <input id="offer-open-time-{{ $offer['id'] }}" name="open_time" type="time" value="{{ $offer['open_time'] ?? '09:00' }}">
                                        <label for="offer-close-time-{{ $offer['id'] }}">Close time</label>
                                        <input id="offer-close-time-{{ $offer['id'] }}" name="close_time" type="time" value="{{ $offer['close_time'] ?? '17:00' }}">
                                        <label for="offer-additional-services-{{ $offer['id'] }}">Additional services</label>
                                        <textarea id="offer-additional-services-{{ $offer['id'] }}" name="additional_services_text" placeholder="Nail trim | 10&#10;Dog pickup | 15">{{ $offer['additional_services_text'] ?? '' }}</textarea>
                                    @endif
                                    <label for="offer-status-{{ $offer['id'] }}">Availability</label>
                                    <select id="offer-status-{{ $offer['id'] }}" name="availability">
                                        <option value="active" @selected(($offer['status'] ?? 'active') === 'active')>Active</option>
                                        <option value="inactive" @selected(($offer['status'] ?? 'active') === 'inactive')>Inactive</option>
                                    </select>
                                    <div class="commerce-actions">
                                        <button class="commerce-cta" type="submit">Save in OS</button>
                                        <a class="commerce-secondary" href="{{ route('website') }}">Website Workspace</a>
                                    </div>
                                </form>
                            </div>
                        @empty
                            <div class="commerce-card">
                                <div class="commerce-card-meta">Offer Manager</div>
                                <div class="commerce-card-title">No offers in the OS yet</div>
                                <div class="commerce-card-copy">Create your first relevant product or service above and it will stay visible here for ongoing editing from Hatchers Ai Business OS.</div>
                            </div>
                        @endforelse
                    </div>
                </section>
                @endif

                @if ($commerceView === 'operations' && $supportsProducts)
                    <section class="commerce-section">
                        <h2>Discounts And Shipping</h2>
                        <div class="commerce-grid">
                            <div class="commerce-card">
                                <div class="commerce-card-meta">Coupons</div>
                                <div class="commerce-card-title">Add coupon codes from the OS</div>
                                <div class="commerce-card-copy">Coupons belong here in the OS and should be managed from this workspace.</div>
                                <form method="POST" action="{{ route('founder.commerce.settings.store') }}" class="commerce-field">
                                    @csrf
                                    <input type="hidden" name="setting_type" value="coupon">
                                    <input type="hidden" name="setting_platform" value="bazaar">
                                    <label for="coupon-title">Coupon name</label>
                                    <input id="coupon-title" name="title" type="text" placeholder="Spring launch offer" required>
                                    <label for="coupon-code">Coupon code</label>
                                    <input id="coupon-code" name="field_one" type="text" placeholder="SPRING20">
                                    <label for="coupon-type">Discount type</label>
                                    <select id="coupon-type" name="field_two">
                                        <option value="percent">Percentage discount</option>
                                        <option value="fixed">Fixed amount discount</option>
                                    </select>
                                    <label for="coupon-value">Discount value</label>
                                    <input id="coupon-value" name="field_three" type="text" placeholder="20">
                                    <label for="coupon-applies-to">Applies to</label>
                                    <input id="coupon-applies-to" name="field_four" type="text" placeholder="All products or selected collection">
                                    <div class="commerce-actions"><button class="commerce-cta" type="submit">Save coupon rule</button></div>
                                </form>
                                @foreach (collect($commerceConfigs['coupon'])->where('engine', 'bazaar') as $config)
                                    <div style="margin-top:10px;">
                                        <div class="commerce-chip">{{ $config['title'] }} · {{ $config['field_one'] }} · {{ $config['field_two'] }} {{ $config['field_three'] }} · {{ $config['status'] === 'paused' ? 'Inactive' : 'Active' }}</div>
                                        <form method="POST" action="{{ route('founder.commerce.settings.toggle') }}" style="margin-top:8px;">
                                            @csrf
                                            <input type="hidden" name="platform" value="bazaar">
                                            <input type="hidden" name="config_type" value="coupon">
                                            <input type="hidden" name="title" value="{{ $config['title'] }}">
                                            <input type="hidden" name="status" value="{{ $config['status'] === 'paused' ? 'active' : 'inactive' }}">
                                            <button class="commerce-secondary" type="submit">{{ $config['status'] === 'paused' ? 'Activate' : 'Deactivate' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('founder.commerce.config.update', $config['id']) }}" class="commerce-field" style="margin-top:8px;">
                                            @csrf
                                            <label>Coupon name<input name="title" type="text" value="{{ $config['title'] }}"></label>
                                            <label>Coupon code<input name="field_one" type="text" value="{{ $config['field_one'] }}"></label>
                                            <label>Discount type
                                                <select name="field_two">
                                                    <option value="percent" @selected(($config['field_two'] ?? '') === 'percent')>Percentage discount</option>
                                                    <option value="fixed" @selected(($config['field_two'] ?? '') === 'fixed')>Fixed amount discount</option>
                                                </select>
                                            </label>
                                            <label>Discount value<input name="field_three" type="text" value="{{ $config['field_three'] }}"></label>
                                            <label>Coupon note<input name="field_four" type="text" value="{{ $config['field_four'] }}"></label>
                                            <button class="commerce-secondary" type="submit">Update coupon</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>

                            <div class="commerce-card">
                                <div class="commerce-card-meta">Shipping</div>
                                <div class="commerce-card-title">Set shipping plans from the OS</div>
                                <div class="commerce-card-copy">Shipping plans should be configured here, not in a separate storefront admin.</div>
                                <form method="POST" action="{{ route('founder.commerce.settings.store') }}" class="commerce-field">
                                    @csrf
                                    <input type="hidden" name="setting_type" value="shipping">
                                    <input type="hidden" name="setting_platform" value="bazaar">
                                    <label for="shipping-title">Shipping plan name</label>
                                    <input id="shipping-title" name="title" type="text" placeholder="Standard shipping" required>
                                    <label for="shipping-region">Region</label>
                                    <input id="shipping-region" name="field_one" type="text" placeholder="Egypt, GCC, Worldwide">
                                    <label for="shipping-fee">Fee</label>
                                    <input id="shipping-fee" name="field_two" type="text" placeholder="30 USD or free over 100 USD">
                                    <label for="shipping-window">Delivery window</label>
                                    <input id="shipping-window" name="field_three" type="text" placeholder="2-4 business days">
                                    <label for="shipping-note">Fulfillment note</label>
                                    <input id="shipping-note" name="field_four" type="text" placeholder="Courier, pickup, same-day">
                                    <div class="commerce-actions"><button class="commerce-cta" type="submit">Save shipping plan</button></div>
                                </form>
                                @foreach (collect($commerceConfigs['shipping'])->where('engine', 'bazaar') as $config)
                                    <div style="margin-top:10px;">
                                        <div class="commerce-chip">{{ $config['title'] }} · {{ $config['field_one'] }} · {{ $config['field_two'] }} · {{ $config['status'] === 'paused' ? 'Inactive' : 'Active' }}</div>
                                        <form method="POST" action="{{ route('founder.commerce.settings.toggle') }}" style="margin-top:8px;">
                                            @csrf
                                            <input type="hidden" name="platform" value="bazaar">
                                            <input type="hidden" name="config_type" value="shipping">
                                            <input type="hidden" name="title" value="{{ $config['title'] }}">
                                            <input type="hidden" name="status" value="{{ $config['status'] === 'paused' ? 'active' : 'inactive' }}">
                                            <button class="commerce-secondary" type="submit">{{ $config['status'] === 'paused' ? 'Activate' : 'Deactivate' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('founder.commerce.config.update', $config['id']) }}" class="commerce-field" style="margin-top:8px;">
                                            @csrf
                                            <label>Plan name<input name="title" type="text" value="{{ $config['title'] }}"></label>
                                            <label>Region<input name="field_one" type="text" value="{{ $config['field_one'] }}"></label>
                                            <label>Fee<input name="field_two" type="text" value="{{ $config['field_two'] }}"></label>
                                            <label>Delivery window<input name="field_three" type="text" value="{{ $config['field_three'] }}"></label>
                                            <label>Fulfillment note<input name="field_four" type="text" value="{{ $config['field_four'] }}"></label>
                                            <button class="commerce-secondary" type="submit">Update shipping plan</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endif

                @if ($commerceView === 'operations' && $supportsServices)
                    <section class="commerce-section">
                        <h2>Booking Policies</h2>
                        <div class="commerce-grid">
                            <div class="commerce-card">
                                <div class="commerce-card-meta">Coupons</div>
                                <div class="commerce-card-title">Add service coupons from the OS</div>
                                <div class="commerce-card-copy">Service discounts should be managed in the OS too.</div>
                                <form method="POST" action="{{ route('founder.commerce.settings.store') }}" class="commerce-field" style="margin-top:14px;">
                                    @csrf
                                    <input type="hidden" name="setting_type" value="coupon">
                                    <input type="hidden" name="setting_platform" value="servio">
                                    <label for="service-coupon-title">Coupon name</label>
                                    <input id="service-coupon-title" name="title" type="text" placeholder="New client booking offer" required>
                                    <label for="service-coupon-code">Coupon code</label>
                                    <input id="service-coupon-code" name="field_one" type="text" placeholder="BOOK15">
                                    <label for="service-coupon-type">Discount type</label>
                                    <select id="service-coupon-type" name="field_two">
                                        <option value="percent">Percentage discount</option>
                                        <option value="fixed">Fixed amount discount</option>
                                    </select>
                                    <label for="service-coupon-value">Discount value</label>
                                    <input id="service-coupon-value" name="field_three" type="text" placeholder="15">
                                    <label for="service-coupon-note">Coupon note</label>
                                    <input id="service-coupon-note" name="field_four" type="text" placeholder="Applies to first booking only">
                                    <div class="commerce-actions"><button class="commerce-cta" type="submit">Save service coupon</button></div>
                                </form>
                                @foreach (collect($commerceConfigs['coupon'])->where('engine', 'servio') as $config)
                                    <div style="margin-top:10px;">
                                        <div class="commerce-chip">{{ $config['title'] }} · {{ $config['field_one'] }} · {{ $config['field_two'] }} {{ $config['field_three'] }} · {{ $config['status'] === 'paused' ? 'Inactive' : 'Active' }}</div>
                                        <form method="POST" action="{{ route('founder.commerce.settings.toggle') }}" style="margin-top:8px;">
                                            @csrf
                                            <input type="hidden" name="platform" value="servio">
                                            <input type="hidden" name="config_type" value="coupon">
                                            <input type="hidden" name="title" value="{{ $config['title'] }}">
                                            <input type="hidden" name="status" value="{{ $config['status'] === 'paused' ? 'active' : 'inactive' }}">
                                            <button class="commerce-secondary" type="submit">{{ $config['status'] === 'paused' ? 'Activate' : 'Deactivate' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('founder.commerce.config.update', $config['id']) }}" class="commerce-field" style="margin-top:8px;">
                                            @csrf
                                            <label>Coupon name<input name="title" type="text" value="{{ $config['title'] }}"></label>
                                            <label>Coupon code<input name="field_one" type="text" value="{{ $config['field_one'] }}"></label>
                                            <label>Discount type
                                                <select name="field_two">
                                                    <option value="percent" @selected(($config['field_two'] ?? '') === 'percent')>Percentage discount</option>
                                                    <option value="fixed" @selected(($config['field_two'] ?? '') === 'fixed')>Fixed amount discount</option>
                                                </select>
                                            </label>
                                            <label>Discount value<input name="field_three" type="text" value="{{ $config['field_three'] }}"></label>
                                            <label>Coupon note<input name="field_four" type="text" value="{{ $config['field_four'] }}"></label>
                                            <button class="commerce-secondary" type="submit">Update service coupon</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>

                            <div class="commerce-card">
                                <div class="commerce-card-meta">Service Operations</div>
                                <div class="commerce-card-title">Configure booking rules from the OS</div>
                                <div class="commerce-card-copy">Set availability, session length, buffer time, and cancellation policy here.</div>
                                <form method="POST" action="{{ route('founder.commerce.settings.store') }}" class="commerce-field">
                                    @csrf
                                    <input type="hidden" name="setting_type" value="booking_policy">
                                    <input type="hidden" name="setting_platform" value="servio">
                                    <label for="booking-title">Policy name</label>
                                    <input id="booking-title" name="title" type="text" placeholder="Standard sessions" required>
                                    <label for="booking-length">Session length</label>
                                    <input id="booking-length" name="field_one" type="text" placeholder="60 minutes">
                                    <label for="booking-buffer">Buffer time</label>
                                    <input id="booking-buffer" name="field_two" type="text" placeholder="15 minutes">
                                    <label for="booking-lead">Minimum lead time</label>
                                    <input id="booking-lead" name="field_three" type="text" placeholder="12 hours">
                                    <label for="booking-cancel">Cancellation window</label>
                                    <input id="booking-cancel" name="field_four" type="text" placeholder="24 hours before start">
                                    <div class="commerce-actions"><button class="commerce-cta" type="submit">Save booking policy</button></div>
                                </form>
                                @foreach (collect($commerceConfigs['booking_policy'])->where('engine', 'servio') as $config)
                                    <div class="commerce-chip">{{ $config['title'] }} · {{ $config['field_one'] }} · {{ $config['field_two'] }}</div>
                                @endforeach
                            </div>
                            <div class="commerce-card">
                                <div class="commerce-card-meta">Bookings</div>
                                <div class="commerce-card-title">{{ $growth['booking_count'] }} bookings tracked</div>
                                <div class="commerce-card-copy">See bookings and service activity in one place.</div>
                                <div class="commerce-actions"><a class="commerce-cta" href="{{ route('founder.commerce.bookings') }}">Open bookings view</a></div>
                            </div>
                        </div>
                    </section>
                @endif

                @if (in_array($commerceView, ['overview', 'operations'], true) && $supportsProducts)
                    <section class="commerce-section">
                        <h2>Orders</h2>
                        <div class="commerce-grid">
                            <div class="commerce-card">
                                <div class="commerce-card-meta">Orders</div>
                                <div class="commerce-card-title">{{ $growth['order_count'] }} orders tracked</div>
                                <div class="commerce-card-copy">See orders, sales activity, and storefront status in one place.</div>
                                <div class="commerce-actions"><a class="commerce-cta" href="{{ route('founder.commerce.orders') }}">Open orders view</a></div>
                            </div>
                            <div class="commerce-card">
                                <div class="commerce-card-meta">Website</div>
                                <div class="commerce-card-title">Public storefront and checkout</div>
                                <div class="commerce-card-copy">Open the public storefront and checkout flow.</div>
                                <div class="commerce-actions"><a class="commerce-cta" href="{{ route('website') }}">Open website workspace</a></div>
                            </div>
                        </div>
                    </section>
                @endif

                @if (in_array($commerceView, ['overview', 'operations'], true) && $supportsServices)
                    <section class="commerce-section">
                        <h2>Bookings</h2>
                        <div class="commerce-grid">
                            <div class="commerce-card">
                                <div class="commerce-card-meta">Bookings</div>
                                <div class="commerce-card-title">{{ $growth['booking_count'] }} bookings tracked</div>
                                <div class="commerce-card-copy">See bookings and service activity in one place.</div>
                                <div class="commerce-actions"><a class="commerce-cta" href="{{ route('founder.commerce.bookings') }}">Open bookings view</a></div>
                            </div>
                            <div class="commerce-card">
                                <div class="commerce-card-meta">Website</div>
                                <div class="commerce-card-title">Public service site and booking flow</div>
                                <div class="commerce-card-copy">Open the public service website and booking flow.</div>
                                <div class="commerce-actions"><a class="commerce-cta" href="{{ route('website') }}">Open website workspace</a></div>
                            </div>
                        </div>
                    </section>
                @endif

                @if ($commerceView === 'money')
                    <section class="commerce-section">
                        <h2>Founder Wallet</h2>
                        <div class="commerce-grid">
                            <div class="commerce-card">
                                <div class="commerce-card-meta">Balance</div>
                                <div class="commerce-card-title">{{ $walletSummary['currency'] }} {{ number_format((float) $walletSummary['available_balance'], 2) }}</div>
                                <div class="commerce-card-copy">Available balance ready for future payout requests after platform fees, refunds, and reserves.</div>
                                <div class="commerce-chip">Pending: {{ $walletSummary['currency'] }} {{ number_format((float) $walletSummary['pending_balance'], 2) }}</div>
                                <div class="commerce-chip">Reserved: {{ $walletSummary['currency'] }} {{ number_format((float) $walletSummary['reserved_balance'], 2) }}</div>
                                <div class="commerce-chip">Gross sales: {{ $walletSummary['currency'] }} {{ number_format((float) ($walletSummary['gross_sales_total'] ?? 0), 2) }}</div>
                                <div class="commerce-chip">Net earnings: {{ $walletSummary['currency'] }} {{ number_format((float) ($walletSummary['net_earnings_total'] ?? 0), 2) }}</div>
                                <div class="commerce-actions">
                                    <a class="commerce-secondary" href="{{ route('founder.commerce.wallet') }}" style="text-decoration:none;">Open wallet history</a>
                                </div>
                            </div>
                            <div class="commerce-card">
                                <div class="commerce-card-meta">Payout setup</div>
                                <div class="commerce-card-title">
                                    @if ($payoutAccount)
                                        {{ $payoutAccount->bank_name ?? 'Saved payout account' }}
                                    @else
                                        Connect where payouts should go
                                    @endif
                                </div>
                                <div class="commerce-card-copy">
                                    @if ($payoutAccount && $payoutAccount->stripe_payouts_enabled)
                                        Stripe Connect is enabled, so payout requests can attempt automatic transfer.
                                    @elseif ($payoutAccount && $payoutAccount->stripe_account_id)
                                        Finish Stripe onboarding to enable automatic payouts.
                                    @else
                                        Save bank details and connect Stripe if you want automated payout handling.
                                    @endif
                                </div>
                                <div class="commerce-actions">
                                    <a class="commerce-cta" href="{{ route('founder.commerce.payout-account.connect') }}">
                                        @if ($payoutAccount && $payoutAccount->stripe_account_id)
                                            Continue Stripe onboarding
                                        @else
                                            Connect Stripe payouts
                                        @endif
                                    </a>
                                </div>
                                <form method="POST" action="{{ route('founder.commerce.payout-account.store') }}" class="commerce-field" style="margin-top:12px;">
                                    @csrf
                                    <input name="account_holder_name" type="text" placeholder="Account holder" value="{{ old('account_holder_name', $payoutAccount->account_holder_name ?? $founder->full_name) }}">
                                    <input name="bank_name" type="text" placeholder="Bank name" value="{{ old('bank_name', $payoutAccount->bank_name ?? '') }}">
                                    <input name="account_number" type="text" placeholder="Account number" value="{{ old('account_number', $payoutAccount->account_number ?? '') }}">
                                    <input name="iban" type="text" placeholder="IBAN" value="{{ old('iban', $payoutAccount->iban ?? '') }}">
                                    <input name="swift_code" type="text" placeholder="SWIFT code" value="{{ old('swift_code', $payoutAccount->swift_code ?? '') }}">
                                    <input name="routing_number" type="text" placeholder="Routing number" value="{{ old('routing_number', $payoutAccount->routing_number ?? '') }}">
                                    <input name="bank_country" type="text" placeholder="Bank country" value="{{ old('bank_country', $payoutAccount->bank_country ?? '') }}">
                                    <input name="bank_currency" type="text" placeholder="Currency" value="{{ old('bank_currency', $payoutAccount->bank_currency ?? $walletSummary['currency']) }}">
                                    <button class="commerce-secondary" type="submit">Save payout account</button>
                                </form>
                            </div>
                            <div class="commerce-card">
                                <div class="commerce-card-meta">Withdraw to bank</div>
                                <div class="commerce-card-title">Minimum payout: USD {{ number_format((float) $walletSummary['minimum_payout_amount'], 2) }}</div>
                                <div class="commerce-card-copy">Request a withdrawal once the available balance is high enough and your payout destination is ready.</div>
                                <form method="POST" action="{{ route('founder.commerce.payout-request.store') }}" class="commerce-field" style="margin-top:12px;">
                                    @csrf
                                    <input name="amount" type="number" step="0.01" min="50" placeholder="50.00">
                                    <textarea name="notes" placeholder="Optional payout note"></textarea>
                                    <button class="commerce-cta" type="submit">Request withdrawal</button>
                                </form>
                            </div>
                            <div class="commerce-card">
                                <div class="commerce-card-meta">Recent payout requests</div>
                                <div class="commerce-card-title">Latest money movement</div>
                                @forelse ($recentPayoutRequests as $payoutRequest)
                                    <div class="commerce-chip">{{ strtoupper($payoutRequest->currency) }} {{ number_format((float) $payoutRequest->amount, 2) }} · {{ ucfirst($payoutRequest->status) }} · {{ optional($payoutRequest->requested_at)->toDateString() }}</div>
                                @empty
                                    <div class="commerce-card-copy">No payout requests yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </section>
                @endif
            </div>
        </main>

        <aside class="commerce-rightbar">
            <div class="commerce-rightbar-inner">
                <h3>Business Model</h3>
                <div class="mini-note">{{ ucfirst($businessModel) }} business</div>

                <h3 style="margin-top:22px;">Engine Health</h3>
                <div class="rail-list">
                    @foreach ($engines as $engine)
                        <div class="rail-item">
                            <div style="font-weight:600;">{{ $engine['label'] }}</div>
                            <div style="margin-top:4px;color:var(--muted);">{{ $engine['summary'] }}</div>
                            <div style="margin-top:8px;color:var(--muted);">Updated: {{ $engine['updated_at'] ?: 'Not synced yet' }}</div>
                        </div>
                    @endforeach
                </div>

                <h3 style="margin-top:22px;">Where To Manage Things</h3>
                <div class="rail-list">
                    @if ($supportsProducts)
                        <div class="rail-item"><strong>Coupons</strong><br><span class="muted">Commerce → Discounts And Shipping</span></div>
                        <div class="rail-item"><strong>Shipping plans</strong><br><span class="muted">Commerce → Discounts And Shipping</span></div>
                        <div class="rail-item"><strong>Orders</strong><br><span class="muted">Commerce → Orders</span></div>
                    @endif
                    @if ($supportsServices)
                        <div class="rail-item"><strong>Booking rules</strong><br><span class="muted">Commerce → Booking Policies</span></div>
                        <div class="rail-item"><strong>Bookings</strong><br><span class="muted">Commerce → Bookings</span></div>
                    @endif
                    <div class="rail-item"><strong>Theme + public URL</strong><br><span class="muted">Website workspace</span></div>
                </div>

                <h3 style="margin-top:22px;">Pricing Focus</h3>
                <div class="rail-list">
                    @foreach (array_slice($pricingOptimizer['recommendations'], 0, 3) as $recommendation)
                        <div class="rail-item">
                            <strong>{{ $recommendation['title'] }}</strong><br>
                            <span class="muted">{{ $recommendation['positioning'] }} · {{ $pricingOptimizer['currency'] }} {{ number_format((float) $recommendation['price'], 2) }}</span>
                        </div>
                    @endforeach
                    @if ($pricingOptimizer['best_channel_label'] !== '')
                        <div class="rail-item">
                            <strong>Best channel</strong><br>
                            <span class="muted">{{ $pricingOptimizer['best_channel_label'] }}</span>
                        </div>
                    @endif
                </div>

                <h3 style="margin-top:22px;">Founder Wallet</h3>
                <div class="rail-list">
                    <div class="rail-item">
                        <strong>{{ $walletSummary['currency'] }} {{ number_format((float) $walletSummary['available_balance'], 2) }}</strong><br>
                        <span class="muted">Available balance</span>
                        <div style="margin-top:8px;color:var(--muted);">Pending: {{ $walletSummary['currency'] }} {{ number_format((float) $walletSummary['pending_balance'], 2) }}</div>
                        <div style="color:var(--muted);">Reserved: {{ $walletSummary['currency'] }} {{ number_format((float) $walletSummary['reserved_balance'], 2) }}</div>
                        <div style="margin-top:8px;color:var(--muted);">Gross sales: {{ $walletSummary['currency'] }} {{ number_format((float) ($walletSummary['gross_sales_total'] ?? 0), 2) }}</div>
                        <div style="color:var(--muted);">Refunded sales: {{ $walletSummary['currency'] }} {{ number_format((float) ($walletSummary['refunded_sales_total'] ?? 0), 2) }}</div>
                        <div style="color:var(--muted);">Platform fees: {{ $walletSummary['currency'] }} {{ number_format((float) ($walletSummary['platform_fees_total'] ?? 0), 2) }}</div>
                        <div style="color:var(--muted);">Net earnings: {{ $walletSummary['currency'] }} {{ number_format((float) ($walletSummary['net_earnings_total'] ?? 0), 2) }}</div>
                        <div class="commerce-actions"><a class="commerce-secondary" href="{{ route('founder.commerce.wallet') }}" style="text-decoration:none;">Open wallet history</a></div>
                    </div>
                    <div class="rail-item">
                        <strong>Payout account</strong><br>
                        <span class="muted">{{ $payoutAccount ? (($payoutAccount->bank_name ?? 'Bank') . ' · ' . ($payoutAccount->iban ?: $payoutAccount->account_number ?: 'Saved')) : 'No bank account saved yet' }}</span>
                        <div style="margin-top:8px;color:var(--muted);">
                            Stripe Connect:
                            @if ($payoutAccount && $payoutAccount->stripe_payouts_enabled)
                                Connected
                            @elseif ($payoutAccount && $payoutAccount->stripe_account_id)
                                {{ ucfirst((string) ($payoutAccount->stripe_onboarding_status ?: 'pending')) }}
                            @else
                                Not connected
                            @endif
                        </div>
                        <div class="commerce-actions" style="margin-top:12px;">
                            <a class="commerce-cta" href="{{ route('founder.commerce.payout-account.connect') }}">
                                @if ($payoutAccount && $payoutAccount->stripe_account_id)
                                    Continue Stripe onboarding
                                @else
                                    Connect Stripe payouts
                                @endif
                            </a>
                        </div>
                        <form method="POST" action="{{ route('founder.commerce.payout-account.store') }}" class="commerce-field" style="margin-top:12px;">
                            @csrf
                            <input name="account_holder_name" type="text" placeholder="Account holder" value="{{ old('account_holder_name', $payoutAccount->account_holder_name ?? $founder->full_name) }}">
                            <input name="bank_name" type="text" placeholder="Bank name" value="{{ old('bank_name', $payoutAccount->bank_name ?? '') }}">
                            <input name="account_number" type="text" placeholder="Account number" value="{{ old('account_number', $payoutAccount->account_number ?? '') }}">
                            <input name="iban" type="text" placeholder="IBAN" value="{{ old('iban', $payoutAccount->iban ?? '') }}">
                            <input name="swift_code" type="text" placeholder="SWIFT code" value="{{ old('swift_code', $payoutAccount->swift_code ?? '') }}">
                            <input name="routing_number" type="text" placeholder="Routing number" value="{{ old('routing_number', $payoutAccount->routing_number ?? '') }}">
                            <input name="bank_country" type="text" placeholder="Bank country" value="{{ old('bank_country', $payoutAccount->bank_country ?? '') }}">
                            <input name="bank_currency" type="text" placeholder="Currency" value="{{ old('bank_currency', $payoutAccount->bank_currency ?? $walletSummary['currency']) }}">
                            <button class="commerce-secondary" type="submit">Save payout account</button>
                        </form>
                    </div>
                    <div class="rail-item">
                        <strong>Withdraw to bank</strong><br>
                        <span class="muted">Minimum payout: USD {{ number_format((float) $walletSummary['minimum_payout_amount'], 2) }}</span>
                        @if ($payoutAccount && $payoutAccount->stripe_payouts_enabled)
                            <div style="margin-top:8px;color:var(--muted);">Stripe Express is connected, so the OS will attempt automatic Stripe transfer when you request a payout.</div>
                        @else
                            <div style="margin-top:8px;color:var(--muted);">Without Stripe Connect, payout requests stay in the OS queue for manual bank transfer processing.</div>
                        @endif
                        <form method="POST" action="{{ route('founder.commerce.payout-request.store') }}" class="commerce-field" style="margin-top:12px;">
                            @csrf
                            <input name="amount" type="number" step="0.01" min="50" placeholder="50.00">
                            <textarea name="notes" placeholder="Optional payout note"></textarea>
                            <button class="commerce-cta" type="submit">Request withdrawal</button>
                        </form>
                    </div>
                    @foreach ($recentPayoutRequests as $payoutRequest)
                        <div class="rail-item">
                            <strong>{{ strtoupper($payoutRequest->currency) }} {{ number_format((float) $payoutRequest->amount, 2) }}</strong><br>
                            <span class="muted">{{ ucfirst($payoutRequest->status) }} · {{ optional($payoutRequest->requested_at)->toDateString() }}</span>
                        </div>
                    @endforeach
                </div>

                <h3 style="margin-top:22px;">Reminder Rules</h3>
                <div class="rail-list">
                    @if ($supportsProducts)
                        <a class="rail-item" href="{{ $automationSummary['has_unpaid_order_rule'] ? route('founder.commerce.orders', ['status' => 'all', 'queue' => 'unpaid']) : route('founder.automations') }}" style="text-decoration:none;color:inherit;display:block;">
                            <strong>Unpaid orders</strong><br>
                            <span class="muted">
                                @if ($automationSummary['has_unpaid_order_rule'])
                                    Active in OS automations
                                @else
                                    Save the unpaid order reminder template in Automations
                                @endif
                            </span>
                        </a>
                    @endif
                    @if ($supportsServices)
                        <a class="rail-item" href="{{ $automationSummary['has_unscheduled_booking_rule'] ? route('founder.commerce.bookings', ['status' => 'all', 'queue' => 'unscheduled']) : route('founder.automations') }}" style="text-decoration:none;color:inherit;display:block;">
                            <strong>Unscheduled bookings</strong><br>
                            <span class="muted">
                                @if ($automationSummary['has_unscheduled_booking_rule'])
                                    Active in OS automations
                                @else
                                    Save the unscheduled booking reminder template in Automations
                                @endif
                            </span>
                        </a>
                        <a class="rail-item" href="{{ $automationSummary['has_provider_assignment_rule'] ? route('founder.commerce.bookings', ['status' => 'all', 'queue' => 'needs_staff']) : route('founder.automations') }}" style="text-decoration:none;color:inherit;display:block;">
                            <strong>Provider assignment</strong><br>
                            <span class="muted">
                                @if ($automationSummary['has_provider_assignment_rule'])
                                    Active in OS automations
                                @else
                                    Save the provider assignment template in Automations
                                @endif
                            </span>
                        </a>
                    @endif
                    <div class="rail-item">
                        <a class="commerce-secondary" href="{{ route('founder.automations') }}" style="text-decoration:none;">Open automations</a>
                    </div>
                </div>
            </div>
        </aside>
    </div>
@endsection
