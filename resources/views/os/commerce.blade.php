@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'prototype-dashboard-page')

@php
    $founder = $dashboard['founder'];
    $growth = $dashboard['growth'];
    $businessModel = $website['business_model'];
    $engines = collect($website['engines']);
    $supportsProducts = in_array($businessModel, ['product', 'hybrid'], true);
    $supportsServices = in_array($businessModel, ['service', 'hybrid'], true);
    $walletSummary = $walletSummary ?? ['available_balance' => 0, 'pending_balance' => 0, 'reserved_balance' => 0, 'minimum_payout_amount' => 50, 'currency' => 'USD', 'recent_entries' => []];
    $commerceView = request()->query('view', 'overview');
    if (!in_array($commerceView, ['overview', 'offers', 'operations', 'money'], true)) {
        $commerceView = 'overview';
    }
    $commerceHeading = match ($businessModel) {
        'service' => 'Manage your services, booking rules, and delivery flow from one OS workspace.',
        'product' => 'Manage your products, discounts, shipping plans, and orders from one OS workspace.',
        default => 'Manage your products, services, storefront readiness, orders, and bookings from one OS workspace.',
    };
@endphp

@section('head')
    <style>
        .page.prototype-dashboard-page { --bg:#F9F8F6; --surface:#FBFAF7; --surface-2:#F4F1EC; --border:rgba(30,24,16,.10); --hairline:rgba(30,24,16,.08); --text:#1B1A17; --text-muted:#6B6660; --text-subtle:#A39E96; --accent-pink:#F2546B; --tile-purple:#C8B8D6; --tile-purple-2:#A99BBC; --tile-grey:#B8B0A6; --tile-grey-2:#8E867C; --shadow-sm:0 1px 0 rgba(30,24,16,.04); --shadow-md:0 1px 2px rgba(30,24,16,.06),0 0 0 .5px rgba(30,24,16,.06); min-height:100vh; padding:0; background:var(--bg); font-family:'Inter',-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; color:var(--text); }
        .page.prototype-dashboard-page *{box-sizing:border-box}.prototype-app{background:var(--bg);display:grid;grid-template-columns:auto 1fr;min-height:100vh}.rail{width:56px;border-right:.5px solid var(--hairline);padding:14px 0;display:flex;flex-direction:column;align-items:center;justify-content:space-between;background:var(--bg)}.rail-top,.rail-bottom{display:flex;flex-direction:column;align-items:center;gap:16px}.rail-icon{width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;color:#6B6660;border-radius:6px;text-decoration:none;background:transparent;font-size:16px}.rail-icon:hover{color:var(--text);background:var(--surface-2)}.rail-add{background:#ECE6FA;color:#5B45C9;border:.5px solid #C9BCF0;position:relative}.rail-tooltip{position:absolute;left:calc(100% + 10px);top:50%;transform:translateY(-50%);background:#fff;border:.5px solid var(--border);border-radius:8px;padding:5px 10px;font-size:12px;color:var(--text);white-space:nowrap;box-shadow:var(--shadow-md);opacity:0;pointer-events:none}.rail-add:hover .rail-tooltip{opacity:1}.rail-avatar{width:28px;height:28px;border-radius:8px;background:linear-gradient(160deg,#7C5BE0,#5B3FC9);color:#fff;font-size:12px;font-weight:600;display:inline-flex;align-items:center;justify-content:center}.main{display:flex;flex-direction:column;min-width:0}.topbar{display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:16px;padding:14px 20px;border-bottom:.5px solid var(--hairline);background:var(--bg)}.brand{display:inline-flex;align-items:center;gap:10px;padding:6px 12px 6px 8px;background:var(--surface);border:.5px solid var(--border);border-radius:999px;box-shadow:var(--shadow-sm);font-weight:600;font-size:13px;color:var(--text);text-decoration:none}.brand-mark{width:18px;height:18px;border-radius:5px;background:var(--accent-pink)}.search{display:flex;align-items:center;gap:10px;height:36px;padding:0 14px;background:var(--surface);border:.5px solid var(--border);border-radius:999px;box-shadow:var(--shadow-sm);max-width:560px;width:100%;justify-self:start;margin-left:4px}.search-dot{width:6px;height:6px;border-radius:50%;background:#1B1A17}.search input{flex:1;border:0;outline:0;background:transparent;font:inherit;color:var(--text);font-size:13px}.search input::placeholder{color:var(--text-subtle)}.search-kbd{font-size:11px;color:var(--text-subtle);border:.5px solid var(--border);border-radius:6px;padding:2px 7px;line-height:1}.status-pill{display:inline-flex;align-items:center;gap:10px;padding:6px 14px 6px 10px;background:var(--surface);border:.5px solid var(--border);border-radius:999px;box-shadow:var(--shadow-sm);font-size:12.5px;color:var(--text);text-decoration:none}.content{flex:1;display:grid;grid-template-columns:140px 1fr;min-height:0}.tile-rail{padding:24px 16px;display:flex;flex-direction:column;gap:24px;align-items:center}.tile{width:92px;display:flex;flex-direction:column;align-items:center;gap:8px;text-decoration:none;color:inherit}.tile-art{width:88px;height:88px;border-radius:18px;display:flex;align-items:center;justify-content:center;color:#fff;box-shadow:inset 0 1px 0 rgba(255,255,255,.35),inset 0 -10px 24px rgba(0,0,0,.12),0 1px 2px rgba(30,24,16,.08);position:relative;overflow:hidden;font-size:28px}.tile-art::after{content:"";position:absolute;inset:0;background:linear-gradient(160deg,rgba(255,255,255,.18) 0%,rgba(255,255,255,0) 45%,rgba(0,0,0,.10) 100%)}.tile-art.purple{background:linear-gradient(160deg,var(--tile-purple) 0%,var(--tile-purple-2) 100%)}.tile-art.grey{background:linear-gradient(160deg,var(--tile-grey) 0%,var(--tile-grey-2) 100%)}.tile-label{font-size:12px;color:var(--text);font-weight:500;text-align:center}.workspace{padding:28px 40px 60px}.panel{width:min(1080px,calc(100% - 40px));margin:0 auto;background:var(--surface);border:.5px solid var(--border);border-radius:18px;box-shadow:var(--shadow-md);overflow:hidden}.panel-header{display:flex;align-items:center;justify-content:center;position:relative;padding:14px 20px;border-bottom:.5px solid var(--hairline)}.traffic{position:absolute;left:18px;display:inline-flex;gap:7px;align-items:center}.traffic span{width:12px;height:12px;border-radius:50%;display:inline-block;box-shadow:inset 0 0 0 .5px rgba(0,0,0,.10)}.traffic .red{background:#ED6A5E}.traffic .yellow{background:#F4BF4F}.traffic .green{background:#62C554}.panel-title{font-size:11px;font-weight:600;letter-spacing:.10em;text-transform:uppercase;color:var(--text-muted)}.panel-body{padding:24px}.hero{margin-bottom:22px}.hero h1{margin:0 0 10px;font-size:42px;letter-spacing:-.05em;line-height:1;font-weight:650}.hero p{margin:0;color:var(--text-muted);font-size:14px;line-height:1.6;max-width:760px}.metric-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:20px}.metric-card,.info-card{background:#fff;border:.5px solid var(--border);border-radius:16px;padding:16px 18px;box-shadow:var(--shadow-sm)}.metric-card strong{display:block;font-size:26px;margin-top:8px}.tab-row{display:flex;gap:10px;flex-wrap:wrap;margin:18px 0}.tab{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:999px;text-decoration:none;color:var(--text);background:#fff;border:.5px solid var(--border);font-weight:600;font-size:13px}.tab.active{background:var(--surface-2)}.muted{color:var(--text-muted);font-size:13px;line-height:1.55}.info-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.stack{display:grid;gap:10px;margin-top:14px}.stack-item{background:var(--surface-2);border-radius:14px;padding:12px 14px}.stack-item strong{display:block}
        @media (max-width:980px){.content{grid-template-columns:1fr}.tile-rail{flex-direction:row;justify-content:center;padding:20px}.workspace{padding:20px}.metric-grid,.info-grid{grid-template-columns:1fr}}
    </style>
@endsection

@section('content')
    <x-os.prototype-shell :founder="$founder" active-tile="ai-tools">
        <div class="workspace">
                    <div class="panel">
                        <div class="panel-header"><span class="traffic"><span class="red"></span><span class="yellow"></span><span class="green"></span></span><span class="panel-title">COMMERCE</span></div>
                        <div class="panel-body">
                            <div class="hero">
                                <h1>Commerce</h1>
                                <p>{{ $commerceHeading }}</p>
                            </div>
                            <div class="tab-row">
                                <a class="tab {{ $commerceView === 'overview' ? 'active' : '' }}" href="{{ route('founder.commerce', ['view' => 'overview']) }}">Overview</a>
                                <a class="tab {{ $commerceView === 'offers' ? 'active' : '' }}" href="{{ route('founder.commerce', ['view' => 'offers']) }}">Offers</a>
                                <a class="tab {{ $commerceView === 'operations' ? 'active' : '' }}" href="{{ route('founder.commerce', ['view' => 'operations']) }}">Operations</a>
                                <a class="tab {{ $commerceView === 'money' ? 'active' : '' }}" href="{{ route('founder.commerce', ['view' => 'money']) }}">Money</a>
                            </div>
                            <section class="metric-grid">
                                @if ($supportsProducts)
                                    <div class="metric-card"><div class="muted">Products</div><strong>{{ $growth['product_count'] }}</strong></div>
                                    <div class="metric-card"><div class="muted">Orders</div><strong>{{ $growth['order_count'] }}</strong></div>
                                @endif
                                @if ($supportsServices)
                                    <div class="metric-card"><div class="muted">Services</div><strong>{{ $growth['service_count'] }}</strong></div>
                                    <div class="metric-card"><div class="muted">Bookings</div><strong>{{ $growth['booking_count'] }}</strong></div>
                                @endif
                                <div class="metric-card"><div class="muted">Customers</div><strong>{{ $growth['customer_count'] }}</strong></div>
                                <div class="metric-card"><div class="muted">Revenue</div><strong>{{ $growth['gross_revenue_formatted'] }}</strong></div>
                            </section>
                            @if ($commerceView === 'overview')
                                <section class="info-grid">
                                    <div class="info-card">
                                        <strong>Connected engine{{ $engines->count() > 1 ? 's' : '' }}</strong>
                                        <div class="stack">
                                            @foreach ($engines as $engine)
                                                <div class="stack-item">
                                                    <strong>{{ $engine['label'] }} · {{ $engine['website_title'] }}</strong>
                                                    <div class="muted">{{ $engine['summary'] }}</div>
                                                    <div class="muted" style="margin-top:6px;">Theme {{ $engine['theme'] }} · {{ $engine['readiness_score'] }}% readiness</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="info-card">
                                        <strong>Next workspace actions</strong>
                                        <div class="stack">
                                            <div class="stack-item"><strong>Website</strong><div class="muted">Refine the storefront and publish path.</div></div>
                                            @if ($supportsProducts)<a href="{{ route('founder.commerce.orders') }}" class="stack-item" style="text-decoration:none;color:inherit;"><strong>Orders</strong><div class="muted">Review product-side order activity.</div></a>@endif
                                            @if ($supportsServices)<a href="{{ route('founder.commerce.bookings') }}" class="stack-item" style="text-decoration:none;color:inherit;"><strong>Bookings</strong><div class="muted">Review service-side scheduling and booking flow.</div></a>@endif
                                        </div>
                                    </div>
                                </section>
                            @elseif ($commerceView === 'offers')
                                <section class="info-grid">
                                    <div class="info-card"><strong>Offer system</strong><p class="muted" style="margin-top:8px;">Use this area to shape what you sell, how it is priced, and how it appears in the storefront.</p><a href="{{ route('website') }}" class="tab" style="margin-top:12px;">Open website workspace</a></div>
                                    <div class="info-card"><strong>Catalog mode</strong><div class="stack"><div class="stack-item"><strong>Business model</strong><div class="muted">{{ ucfirst($businessModel) }}</div></div><div class="stack-item"><strong>Product support</strong><div class="muted">{{ $supportsProducts ? 'Enabled' : 'Not primary' }}</div></div><div class="stack-item"><strong>Service support</strong><div class="muted">{{ $supportsServices ? 'Enabled' : 'Not primary' }}</div></div></div></div>
                                </section>
                            @elseif ($commerceView === 'operations')
                                <section class="info-grid">
                                    <div class="info-card"><strong>Operations focus</strong><p class="muted" style="margin-top:8px;">Use operations mode when you need to handle orders, bookings, delivery flow, or storefront execution.</p></div>
                                    <div class="info-card"><strong>Direct workspaces</strong><div class="stack">@if ($supportsProducts)<a href="{{ route('founder.commerce.orders') }}" class="stack-item" style="text-decoration:none;color:inherit;"><strong>Orders</strong><div class="muted">Review and process order flow.</div></a>@endif @if ($supportsServices)<a href="{{ route('founder.commerce.bookings') }}" class="stack-item" style="text-decoration:none;color:inherit;"><strong>Bookings</strong><div class="muted">Review service scheduling and booking flow.</div></a>@endif</div></div>
                                </section>
                            @else
                                <section class="info-grid">
                                    <div class="info-card"><strong>Wallet</strong><div class="stack"><div class="stack-item"><strong>Available balance</strong><div class="muted">{{ number_format((float) ($walletSummary['available_balance'] ?? 0), 2) }} {{ $walletSummary['currency'] ?? 'USD' }}</div></div><div class="stack-item"><strong>Pending balance</strong><div class="muted">{{ number_format((float) ($walletSummary['pending_balance'] ?? 0), 2) }} {{ $walletSummary['currency'] ?? 'USD' }}</div></div><div class="stack-item"><strong>Reserved balance</strong><div class="muted">{{ number_format((float) ($walletSummary['reserved_balance'] ?? 0), 2) }} {{ $walletSummary['currency'] ?? 'USD' }}</div></div></div></div>
                                    <div class="info-card"><strong>Payout guidance</strong><p class="muted" style="margin-top:8px;">Minimum payout amount: {{ number_format((float) ($walletSummary['minimum_payout_amount'] ?? 0), 2) }} {{ $walletSummary['currency'] ?? 'USD' }}.</p></div>
                                </section>
                            @endif
                        </div>
                    </div>
        </div>
    </x-os.prototype-shell>
@endsection
