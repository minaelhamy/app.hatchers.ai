@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page { padding: 0; }
        .commerce-shell { min-height: 100vh; display:grid; grid-template-columns:220px minmax(0,1fr) 220px; background:#f8f5ee; }
        .commerce-sidebar, .commerce-rightbar { background: rgba(255,252,247,0.8); border-color: var(--line); border-style: solid; border-width:0 1px 0 0; min-height:100vh; display:flex; flex-direction:column; }
        .commerce-rightbar { border-width:0 0 0 1px; background: rgba(255,251,246,0.9); }
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
        .commerce-main-inner { max-width:780px; margin:0 auto; }
        .commerce-main h1 { font-size: clamp(2rem, 3vw, 3rem); letter-spacing:-0.02em; margin-bottom:6px; }
        .commerce-main p { color:var(--muted); margin-bottom:24px; }
        .commerce-banner { border-radius:16px; padding:14px 16px; border:1px solid rgba(220,207,191,0.8); background: rgba(255,255,255,0.9); margin-bottom:14px; }
        .commerce-banner.success { border-color: rgba(44,122,87,0.26); background: rgba(226,245,236,0.9); }
        .commerce-banner.error { border-color: rgba(179,34,83,0.22); background: rgba(255,241,246,0.92); }
        .commerce-metrics { display:grid; grid-template-columns:repeat(5, minmax(0,1fr)); gap:12px; margin-bottom:22px; }
        .commerce-metric { background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:18px; padding:16px 18px; box-shadow:0 10px 28px rgba(52,41,26,0.04); }
        .commerce-metric strong { display:block; font-size:1.55rem; margin-top:6px; }
        .commerce-section { margin-bottom:22px; }
        .commerce-section h2 { font-size:1.08rem; margin-bottom:12px; }
        .commerce-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:12px; }
        .commerce-card { background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:18px; padding:18px 18px 16px; box-shadow:0 10px 28px rgba(52,41,26,0.04); }
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
        .rail-item, .mini-note { background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:14px; padding:12px 14px; }
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
        $bazaar = $engines->firstWhere('key', 'bazaar');
        $servio = $engines->firstWhere('key', 'servio');
        $nextSteps = $website['next_steps'];
        $catalogOffers = $catalogOffers ?? [];
    @endphp

    <div class="commerce-shell">
        <aside class="commerce-sidebar">
            <div class="commerce-sidebar-inner">
                <a class="commerce-brand" href="/dashboard/founder"><img src="/brand/hatchers-ai-logo.png" alt="Hatchers AI"></a>
                <nav class="commerce-nav">
                    <a class="commerce-nav-item" href="/dashboard/founder"><span class="commerce-nav-icon">⌂</span><span>Home</span></a>
                    <a class="commerce-nav-item active" href="{{ route('founder.commerce') }}"><span class="commerce-nav-icon">⌁</span><span>Launch Plan</span></a>
                    <a class="commerce-nav-item" href="{{ route('founder.ai-tools') }}"><span class="commerce-nav-icon">✦</span><span>AI Tools</span></a>
                    <a class="commerce-nav-item" href="{{ route('founder.learning-plan') }}"><span class="commerce-nav-icon">▣</span><span>Learning Plan</span></a>
                    <a class="commerce-nav-item" href="{{ route('founder.tasks') }}"><span class="commerce-nav-icon">◌</span><span>Tasks</span></a>
                    <a class="commerce-nav-item" href="{{ route('founder.settings') }}"><span class="commerce-nav-icon">⚙</span><span>Settings</span></a>
                </nav>
            </div>
            <div class="commerce-sidebar-footer">
                <div class="commerce-user">
                    <div class="commerce-avatar">{{ strtoupper(substr($founder->full_name, 0, 1)) }}</div>
                    <div>{{ $founder->full_name }}</div>
                </div>
                <form method="POST" action="/logout" style="margin:0;">@csrf<button class="commerce-nav-icon" type="submit" style="border:0;background:transparent;cursor:pointer;">↘</button></form>
            </div>
        </aside>

        <main class="commerce-main">
            <div class="commerce-main-inner">
                <h1>Launch Plan</h1>
                <p>Manage your products, services, storefront readiness, orders, and bookings from one OS workspace powered by Bazaar and Servio behind the scenes.</p>

                @if (session('success'))
                    <div class="commerce-banner success">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="commerce-banner error">{{ session('error') }}</div>
                @endif

                <section class="commerce-metrics">
                    <div class="commerce-metric">
                        <div class="commerce-card-copy">Products</div>
                        <strong>{{ $growth['product_count'] }}</strong>
                    </div>
                    <div class="commerce-metric">
                        <div class="commerce-card-copy">Services</div>
                        <strong>{{ $growth['service_count'] }}</strong>
                    </div>
                    <div class="commerce-metric">
                        <div class="commerce-card-copy">Orders</div>
                        <strong>{{ $growth['order_count'] }}</strong>
                    </div>
                    <div class="commerce-metric">
                        <div class="commerce-card-copy">Bookings</div>
                        <strong>{{ $growth['booking_count'] }}</strong>
                    </div>
                    <div class="commerce-metric">
                        <div class="commerce-card-copy">Revenue</div>
                        <strong>{{ $growth['gross_revenue_formatted'] }}</strong>
                    </div>
                </section>

                <section class="commerce-section">
                    <h2>Storefront Performance</h2>
                    <div class="commerce-grid">
                        <div class="commerce-card">
                            <div class="commerce-card-meta">Commercial Health</div>
                            <div class="commerce-card-title">How your storefront is performing</div>
                            <div class="commerce-card-copy">
                                <div style="margin-bottom:10px;"><strong>Customers tracked</strong><br>{{ $growth['customer_count'] }} customers across product and service workflows.</div>
                                <div style="margin-bottom:10px;"><strong>Revenue tracked</strong><br>{{ $growth['gross_revenue_formatted'] }}</div>
                                <div><strong>Conversion flow</strong><br>{{ $growth['order_count'] }} orders and {{ $growth['booking_count'] }} bookings are already visible from the OS.</div>
                            </div>
                        </div>
                        <div class="commerce-card">
                            <div class="commerce-card-meta">Storefront Coverage</div>
                            <div class="commerce-card-title">Where the business is live</div>
                            <div class="commerce-card-copy">
                                <div style="margin-bottom:10px;"><strong>Bazaar storefront</strong><br>{{ $growth['bazaar_title'] ?: 'Not named yet' }}</div>
                                <div style="margin-bottom:10px;"><strong>Servio storefront</strong><br>{{ $growth['servio_title'] ?: 'Not named yet' }}</div>
                                <div><strong>Offer mix</strong><br>{{ $growth['product_count'] }} products and {{ $growth['service_count'] }} services currently tracked.</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="commerce-section">
                    <h2>Storefront Engines</h2>
                    <div class="commerce-grid">
                        @foreach ($engines as $engine)
                            <div class="commerce-card">
                                <div class="commerce-card-meta">{{ $engine['label'] }}</div>
                                <div class="commerce-card-title">{{ $engine['website_title'] }}</div>
                                <div class="commerce-card-copy">{{ $engine['summary'] }}</div>
                                <div class="commerce-chip">Theme: {{ $engine['theme'] }}</div>
                                <div class="commerce-chip">Readiness: {{ $engine['readiness_score'] }}%</div>
                                <div class="commerce-actions">
                                    <a class="commerce-cta" href="{{ $engine['website_url'] }}" target="_blank" rel="noreferrer">Preview site</a>
                                    <a class="commerce-secondary" href="{{ route('founder.legacy-tools') }}">Legacy access</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="commerce-section">
                    <h2>OS-Native Offer Setup</h2>
                    <div class="commerce-grid">
                        <div class="commerce-card">
                            <div class="commerce-card-meta">Product Manager</div>
                            <div class="commerce-card-title">Add a product from the OS</div>
                            <div class="commerce-card-copy">This creates the first product record in Bazaar without pushing the founder into Bazaar first.</div>
                            <form method="POST" action="/website/starter" class="commerce-field">
                                @csrf
                                <input type="hidden" name="website_engine" value="bazaar">
                                <input type="hidden" name="starter_mode" value="product">
                                <div class="commerce-field">
                                    <label for="product-title">Product title</label>
                                    <input id="product-title" name="starter_title" type="text" placeholder="Core product name" required>
                                </div>
                                <div class="commerce-field">
                                    <label for="product-description">Product description</label>
                                    <textarea id="product-description" name="starter_description" placeholder="What does the product do and who is it for?"></textarea>
                                </div>
                                <div class="commerce-field">
                                    <label for="product-price">Price</label>
                                    <input id="product-price" name="starter_price" type="number" step="0.01" min="0" placeholder="99">
                                </div>
                                <div class="commerce-actions">
                                    <button class="commerce-cta" type="submit">Create product</button>
                                </div>
                            </form>
                        </div>

                        <div class="commerce-card">
                            <div class="commerce-card-meta">Services Manager</div>
                            <div class="commerce-card-title">Add a service from the OS</div>
                            <div class="commerce-card-copy">This creates the first service record in Servio so the founder can keep building from the OS.</div>
                            <form method="POST" action="/website/starter" class="commerce-field">
                                @csrf
                                <input type="hidden" name="website_engine" value="servio">
                                <input type="hidden" name="starter_mode" value="service">
                                <div class="commerce-field">
                                    <label for="service-title">Service title</label>
                                    <input id="service-title" name="starter_title" type="text" placeholder="Core service name" required>
                                </div>
                                <div class="commerce-field">
                                    <label for="service-description">Service description</label>
                                    <textarea id="service-description" name="starter_description" placeholder="What outcome does this service deliver?"></textarea>
                                </div>
                                <div class="commerce-field">
                                    <label for="service-price">Price</label>
                                    <input id="service-price" name="starter_price" type="number" step="0.01" min="0" placeholder="250">
                                </div>
                                <div class="commerce-actions">
                                    <button class="commerce-cta" type="submit">Create service</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>

                <section class="commerce-section">
                    <h2>Offer Manager</h2>
                    <div class="commerce-grid">
                        @forelse ($catalogOffers as $offer)
                            <div class="commerce-card">
                                <div class="commerce-card-meta">{{ strtoupper($offer['type']) }} · {{ strtoupper($offer['engine']) }}</div>
                                <div class="commerce-card-title">{{ $offer['title'] }}</div>
                                <div class="commerce-card-copy">Last updated {{ $offer['updated_at'] ?? 'recently' }}.</div>
                                <form method="POST" action="{{ route('founder.commerce.offer.update', $offer['id']) }}" class="commerce-field" style="margin-top:14px;">
                                    @csrf
                                    <div class="commerce-field">
                                        <label for="offer-title-{{ $offer['id'] }}">Title</label>
                                        <input id="offer-title-{{ $offer['id'] }}" name="title" type="text" value="{{ $offer['title'] }}" required>
                                    </div>
                                    <div class="commerce-field">
                                        <label for="offer-description-{{ $offer['id'] }}">Description</label>
                                        <textarea id="offer-description-{{ $offer['id'] }}" name="description">{{ $offer['description'] }}</textarea>
                                    </div>
                                    <div class="commerce-field">
                                        <label for="offer-price-{{ $offer['id'] }}">Price</label>
                                        <input id="offer-price-{{ $offer['id'] }}" name="price" type="number" step="0.01" min="0" value="{{ $offer['price'] }}">
                                    </div>
                                    <div class="commerce-actions">
                                        <button class="commerce-cta" type="submit">Save in OS</button>
                                        <a class="commerce-secondary" href="{{ route('founder.legacy-tools') }}">Legacy access</a>
                                    </div>
                                </form>
                            </div>
                        @empty
                            <div class="commerce-card">
                                <div class="commerce-card-meta">Offer Manager</div>
                                <div class="commerce-card-title">No offers in the OS yet</div>
                                <div class="commerce-card-copy">Create your first product or service above and it will stay visible here for ongoing editing from Hatchers Ai Business OS.</div>
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="commerce-section">
                    <h2>Orders And Bookings</h2>
                    <div class="commerce-grid">
                        <div class="commerce-card">
                            <div class="commerce-card-meta">Orders</div>
                            <div class="commerce-card-title">{{ $growth['order_count'] }} orders tracked</div>
                            <div class="commerce-card-copy">Bazaar snapshots feed the OS with store health, order volume, and storefront readiness so the founder sees commerce state from one place.</div>
                            <div class="commerce-actions">
                                <a class="commerce-cta" href="{{ route('founder.commerce.orders') }}">Open orders view</a>
                                <a class="commerce-secondary" href="{{ route('founder.legacy-tools') }}">Legacy access</a>
                            </div>
                        </div>
                        <div class="commerce-card">
                            <div class="commerce-card-meta">Bookings</div>
                            <div class="commerce-card-title">{{ $growth['booking_count'] }} bookings tracked</div>
                            <div class="commerce-card-copy">Servio snapshots feed the OS with booking and service delivery signals while the OS becomes the founder-facing workspace.</div>
                            <div class="commerce-actions">
                                <a class="commerce-cta" href="{{ route('founder.commerce.bookings') }}">Open bookings view</a>
                                <a class="commerce-secondary" href="{{ route('founder.legacy-tools') }}">Legacy access</a>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="commerce-section">
                    <h2>Website And Domain Control</h2>
                    <div class="commerce-grid">
                        <div class="commerce-card">
                            <div class="commerce-card-meta">Website Workspace</div>
                            <div class="commerce-card-title">Configure your public site from the OS</div>
                            <div class="commerce-card-copy">Theme selection, publishing, and domain connection already route through Hatchers Ai Business OS.</div>
                            <div class="commerce-actions">
                                <a class="commerce-cta" href="{{ route('website') }}">Open website workspace</a>
                            </div>
                        </div>
                        <div class="commerce-card">
                            <div class="commerce-card-meta">Next Steps</div>
                            <div class="commerce-card-title">What to finish next</div>
                            <div class="commerce-card-copy">
                                @foreach ($nextSteps as $step)
                                    <div style="margin-bottom:10px;">
                                        <strong>{{ $step['title'] }}</strong><br>
                                        {{ $step['description'] }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>
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

                <h3 style="margin-top:22px;">Connected Tools</h3>
                <div class="rail-list">
                    @foreach ($launchCards as $launch)
                        <div class="rail-item">
                            <div style="font-weight:600;">{{ $launch['label'] }}</div>
                            <div style="margin-top:4px;color:var(--muted);">{{ $launch['description'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>
    </div>
@endsection
