@extends('os.layout')

@section('content')
    @php
        $admin = $workspace['admin'];
        $filters = $workspace['filters'];
        $filterOptions = $workspace['filter_options'];
        $metrics = $workspace['metrics'];
        $founders = $workspace['founders'];
        $catalog = $workspace['catalog'];
        $reliabilityQueue = $workspace['reliability_queue'];
        $recentOperations = $workspace['recent_operations'];
    @endphp

    <div class="sidebar-layout">
        <aside class="sidebar-card">
            <div class="pill">System Admin</div>
            <div class="nav-group" style="margin-top: 18px;">
                <div class="nav-group-title">Control Center</div>
                <a class="nav-item" href="/dashboard/admin">Overview</a>
                <a class="nav-item" href="{{ route('admin.subscribers') }}">Subscribers</a>
                <a class="nav-item" href="/admin/control">Founder Operations</a>
                <a class="nav-item" href="{{ route('admin.system-access') }}">System Access</a>
                <a class="nav-item" href="{{ route('admin.identity') }}">Identity</a>
                <a class="nav-item active" href="{{ route('admin.commerce') }}">Commerce Control</a>
                <a class="nav-item" href="{{ route('admin.finance') }}">Finance Control</a>
                <a class="nav-item" href="{{ route('admin.modules') }}">Module Monitoring</a>
                <a class="nav-item" href="{{ route('admin.support') }}">Support Center</a>
                <a class="nav-item" href="/dashboard">OS Home</a>
            </div>
        </aside>

        <div>
            <section class="hero">
                <div class="eyebrow">Commerce Control</div>
                <h1>Monitor Bazaar and Servio from one OS workspace.</h1>
                <p class="muted">Welcome back, {{ $admin->full_name }}. This view shows which founders are on Bazaar or Servio, what their live catalog looks like, and where commerce operations still need attention.</p>
            </section>

            @if (session('success'))
                <section class="card" style="border-color: rgba(44, 122, 87, 0.25); background: rgba(44, 122, 87, 0.06); margin-bottom: 18px;">
                    <h3 style="color: var(--success);">Action completed</h3>
                    <p class="muted" style="margin-top: 8px;">{{ session('success') }}</p>
                </section>
            @endif

            @if (session('error'))
                <section class="card" style="border-color: rgba(179, 34, 83, 0.25); background: rgba(179, 34, 83, 0.06); margin-bottom: 18px;">
                    <h3 style="color: var(--rose);">Something needs attention</h3>
                    <p class="muted" style="margin-top: 8px;">{{ session('error') }}</p>
                </section>
            @endif

            @if ($errors->any())
                <section class="card" style="border-color: rgba(154, 107, 27, 0.28); background: rgba(154, 107, 27, 0.08); margin-bottom: 18px;">
                    <h3 style="color: var(--warning);">A few fields still need adjustment</h3>
                    <div class="stack" style="margin-top: 12px;">
                        @foreach ($errors->all() as $error)
                            <div class="stack-item">{{ $error }}</div>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="metrics" style="margin-bottom: 22px;">
                <div class="card metric"><div class="muted">Founders in view</div><strong>{{ $metrics['founders'] }}</strong></div>
                <div class="card metric"><div class="muted">Product founders</div><strong>{{ $metrics['product_founders'] }}</strong></div>
                <div class="card metric"><div class="muted">Service founders</div><strong>{{ $metrics['service_founders'] }}</strong></div>
                <div class="card metric"><div class="muted">Hybrid founders</div><strong>{{ $metrics['hybrid_founders'] }}</strong></div>
                <div class="card metric"><div class="muted">Live websites</div><strong>{{ $metrics['live_websites'] }}</strong></div>
                <div class="card metric"><div class="muted">Bazaar orders</div><strong>{{ $metrics['bazaar_orders'] }}</strong></div>
                <div class="card metric"><div class="muted">Servio bookings</div><strong>{{ $metrics['servio_bookings'] }}</strong></div>
                <div class="card metric"><div class="muted">Open queue items</div><strong>{{ $metrics['open_queue_items'] }}</strong></div>
            </section>

            <section class="grid-2">
                <div class="card">
                    <h2>Filter Commerce Workspace</h2>
                    <form method="GET" action="{{ route('admin.commerce') }}" class="stack" style="margin-top: 14px;">
                        <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search founder, email, or company" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                        <div class="grid-2">
                            <select name="business_model" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <option value="">All business models</option>
                                @foreach ($filterOptions['business_models'] as $option)
                                    <option value="{{ $option }}" @selected($filters['business_model'] === $option)>{{ ucfirst($option) }}</option>
                                @endforeach
                            </select>
                            <select name="engine" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <option value="">All engines</option>
                                @foreach ($filterOptions['engines'] as $option)
                                    <option value="{{ $option }}" @selected($filters['engine'] === $option)>{{ strtoupper($option) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="cta-row">
                            <button class="btn primary" type="submit">Apply Filters</button>
                            <a class="btn" href="{{ route('admin.commerce') }}">Reset</a>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <h2>Commerce Reliability Queue</h2>
                    <p class="muted">These are the founder-level commerce issues the OS can already see without opening Bazaar or Servio directly.</p>
                    <div class="stack" style="margin-top: 14px;">
                        @forelse ($reliabilityQueue as $item)
                            <div class="stack-item">
                                <strong>{{ $item['company_name'] }}</strong><br>
                                {{ $item['founder_name'] }} · {{ $item['engine'] }}
                                <div class="muted" style="margin-top: 6px;">
                                    {{ $item['issue'] }}
                                    @if ($item['last_synced_at'])
                                        · Last synced {{ $item['last_synced_at'] }}
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No open commerce reliability issues</strong><br>
                                Bazaar and Servio look healthy for the founders currently in this filtered view.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Bazaar Admin Create</h2>
                    <p class="muted">Create founder-specific Bazaar categories and taxes from the OS.</p>
                    <form method="POST" action="{{ route('admin.commerce.catalog.store') }}" class="stack" style="margin-top: 14px;">
                        @csrf
                        <input type="hidden" name="platform" value="bazaar">
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                        <input type="hidden" name="business_model" value="{{ $filters['business_model'] }}">
                        <input type="hidden" name="engine" value="{{ $filters['engine'] }}">
                        <select name="founder_id" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                            <option value="">Choose founder</option>
                            @foreach ($founders as $founder)
                                @if (in_array('bazaar', $founder['engines'], true))
                                    <option value="{{ $founder['id'] }}">{{ $founder['company_name'] }} · {{ $founder['name'] }}</option>
                                @endif
                            @endforeach
                        </select>
                        <select name="resource" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                            <option value="category">Category</option>
                            <option value="tax">Tax</option>
                        </select>
                        <input type="text" name="title" placeholder="Name" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                        <div class="grid-2">
                            <input type="number" step="0.01" name="value" placeholder="Tax value (tax only)" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                            <select name="tax_type" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <option value="percent">Percent</option>
                                <option value="fixed">Fixed</option>
                            </select>
                        </div>
                        <div class="cta-row">
                            <button class="btn primary" type="submit">Create In Bazaar</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <h2>Servio Admin Create</h2>
                    <p class="muted">Create founder-specific Servio categories, taxes, and staff from the OS.</p>
                    <form method="POST" action="{{ route('admin.commerce.catalog.store') }}" class="stack" style="margin-top: 14px;">
                        @csrf
                        <input type="hidden" name="platform" value="servio">
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                        <input type="hidden" name="business_model" value="{{ $filters['business_model'] }}">
                        <input type="hidden" name="engine" value="{{ $filters['engine'] }}">
                        <select name="founder_id" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                            <option value="">Choose founder</option>
                            @foreach ($founders as $founder)
                                @if (in_array('servio', $founder['engines'], true))
                                    <option value="{{ $founder['id'] }}">{{ $founder['company_name'] }} · {{ $founder['name'] }}</option>
                                @endif
                            @endforeach
                        </select>
                        <select name="resource" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                            <option value="category">Category</option>
                            <option value="tax">Tax</option>
                            <option value="staff">Staff</option>
                        </select>
                        <input type="text" name="title" placeholder="Name" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                        <div class="grid-2">
                            <input type="number" step="0.01" name="value" placeholder="Tax value (tax only)" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                            <select name="tax_type" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <option value="percent">Percent</option>
                                <option value="fixed">Fixed</option>
                            </select>
                        </div>
                        <div class="grid-2">
                            <input type="email" name="email" placeholder="Staff email (staff only)" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                            <input type="text" name="mobile" placeholder="Staff mobile (staff only)" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                        </div>
                        <div class="cta-row">
                            <button class="btn primary" type="submit">Create In Servio</button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Bazaar Admin Update</h2>
                    <p class="muted">Update existing Bazaar categories and taxes by founder from the OS.</p>
                    <form method="POST" action="{{ route('admin.commerce.catalog.update') }}" class="stack" style="margin-top: 14px;">
                        @csrf
                        <input type="hidden" name="platform" value="bazaar">
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                        <input type="hidden" name="business_model" value="{{ $filters['business_model'] }}">
                        <input type="hidden" name="engine" value="{{ $filters['engine'] }}">
                        <select name="founder_id" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                            <option value="">Choose founder</option>
                            @foreach ($founders as $founder)
                                @if (in_array('bazaar', $founder['engines'], true))
                                    <option value="{{ $founder['id'] }}">{{ $founder['company_name'] }} · {{ $founder['name'] }}</option>
                                @endif
                            @endforeach
                        </select>
                        <div class="grid-3">
                            <select name="resource" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <option value="category">Category</option>
                                <option value="tax">Tax</option>
                            </select>
                            <input type="text" name="target_name" placeholder="Current name" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                            <select name="field" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <option value="status">Status</option>
                                <option value="name">Name</option>
                                <option value="value">Value</option>
                                <option value="type">Type</option>
                            </select>
                        </div>
                        <input type="text" name="value" placeholder="New value, name, active/inactive, percent/fixed" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                        <div class="cta-row">
                            <button class="btn primary" type="submit">Update In Bazaar</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <h2>Servio Admin Update</h2>
                    <p class="muted">Update existing Servio categories, taxes, and staff by founder from the OS.</p>
                    <form method="POST" action="{{ route('admin.commerce.catalog.update') }}" class="stack" style="margin-top: 14px;">
                        @csrf
                        <input type="hidden" name="platform" value="servio">
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                        <input type="hidden" name="business_model" value="{{ $filters['business_model'] }}">
                        <input type="hidden" name="engine" value="{{ $filters['engine'] }}">
                        <select name="founder_id" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                            <option value="">Choose founder</option>
                            @foreach ($founders as $founder)
                                @if (in_array('servio', $founder['engines'], true))
                                    <option value="{{ $founder['id'] }}">{{ $founder['company_name'] }} · {{ $founder['name'] }}</option>
                                @endif
                            @endforeach
                        </select>
                        <div class="grid-3">
                            <select name="resource" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <option value="category">Category</option>
                                <option value="tax">Tax</option>
                                <option value="staff">Staff</option>
                            </select>
                            <input type="text" name="target_name" placeholder="Current name or email" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                            <select name="field" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <option value="status">Status</option>
                                <option value="name">Name</option>
                                <option value="value">Value</option>
                                <option value="type">Type</option>
                                <option value="email">Email</option>
                                <option value="mobile">Mobile</option>
                            </select>
                        </div>
                        <input type="text" name="value" placeholder="New value, name, active/inactive, percent/fixed, email, or mobile" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                        <div class="cta-row">
                            <button class="btn primary" type="submit">Update In Servio</button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Bazaar Offer Intervention</h2>
                    <p class="muted">Update product variants, extras, or availability directly from the OS.</p>
                    <form method="POST" action="{{ route('admin.commerce.offer.update') }}" class="stack" style="margin-top: 14px;">
                        @csrf
                        <input type="hidden" name="platform" value="bazaar">
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                        <input type="hidden" name="business_model" value="{{ $filters['business_model'] }}">
                        <input type="hidden" name="engine" value="{{ $filters['engine'] }}">
                        <select name="founder_id" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                            <option value="">Choose founder</option>
                            @foreach ($founders as $founder)
                                @if (in_array('bazaar', $founder['engines'], true))
                                    <option value="{{ $founder['id'] }}">{{ $founder['company_name'] }} · {{ $founder['name'] }}</option>
                                @endif
                            @endforeach
                        </select>
                        <input type="text" name="target_name" placeholder="Exact product name" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                        <select name="field" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                            <option value="variants">Variants</option>
                            <option value="extras">Extras</option>
                            <option value="status">Status</option>
                        </select>
                        <textarea name="value" rows="4" placeholder="Variants: Small | 19.99 | 12 | 3&#10;Extras: Gift wrap | 5&#10;Status: active or inactive" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;"></textarea>
                        <div class="cta-row">
                            <button class="btn primary" type="submit">Update Bazaar Offer</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <h2>Servio Offer Intervention</h2>
                    <p class="muted">Update service add-ons, staff assignment, availability days, opening hours, or availability state.</p>
                    <form method="POST" action="{{ route('admin.commerce.offer.update') }}" class="stack" style="margin-top: 14px;">
                        @csrf
                        <input type="hidden" name="platform" value="servio">
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                        <input type="hidden" name="business_model" value="{{ $filters['business_model'] }}">
                        <input type="hidden" name="engine" value="{{ $filters['engine'] }}">
                        <select name="founder_id" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                            <option value="">Choose founder</option>
                            @foreach ($founders as $founder)
                                @if (in_array('servio', $founder['engines'], true))
                                    <option value="{{ $founder['id'] }}">{{ $founder['company_name'] }} · {{ $founder['name'] }}</option>
                                @endif
                            @endforeach
                        </select>
                        <input type="text" name="target_name" placeholder="Exact service name" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                        <select name="field" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                            <option value="additional_services">Additional services</option>
                            <option value="staff_ids">Staff IDs</option>
                            <option value="availability_days">Availability days</option>
                            <option value="open_time">Open time</option>
                            <option value="close_time">Close time</option>
                            <option value="status">Status</option>
                        </select>
                        <textarea name="value" rows="4" placeholder="Add-ons: Nail trim | 10&#10;Staff IDs: 12|18|24&#10;Availability: Monday|Tuesday|Friday&#10;Open/close: 09:00 or 17:00&#10;Status: active or inactive" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;"></textarea>
                        <div class="cta-row">
                            <button class="btn primary" type="submit">Update Servio Offer</button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Bazaar Shipping Intervention</h2>
                    <p class="muted">Adjust delivery zones and fees from the OS when a founder’s shipping setup needs admin help.</p>
                    <form method="POST" action="{{ route('admin.commerce.operation.update') }}" class="stack" style="margin-top: 14px;">
                        @csrf
                        <input type="hidden" name="platform" value="bazaar">
                        <input type="hidden" name="category" value="shipping">
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                        <input type="hidden" name="business_model" value="{{ $filters['business_model'] }}">
                        <input type="hidden" name="engine" value="{{ $filters['engine'] }}">
                        <select name="founder_id" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                            <option value="">Choose founder</option>
                            @foreach ($founders as $founder)
                                @if (in_array('bazaar', $founder['engines'], true))
                                    <option value="{{ $founder['id'] }}">{{ $founder['company_name'] }} · {{ $founder['name'] }}</option>
                                @endif
                            @endforeach
                        </select>
                        <input type="text" name="target_name" placeholder="Exact shipping area name" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                        <div class="grid-2">
                            <select name="field" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <option value="area_name">Area name</option>
                                <option value="delivery_charge">Delivery charge</option>
                                <option value="status">Status</option>
                            </select>
                            <input type="text" name="value" placeholder="New area, fee, or active/inactive" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                        </div>
                        <div class="cta-row">
                            <button class="btn primary" type="submit">Update Shipping Zone</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <h2>Servio Availability Intervention</h2>
                    <p class="muted">Use this when a service business needs fast admin correction for availability windows without editing the full service payload manually.</p>
                    <form method="POST" action="{{ route('admin.commerce.offer.update') }}" class="stack" style="margin-top: 14px;">
                        @csrf
                        <input type="hidden" name="platform" value="servio">
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                        <input type="hidden" name="business_model" value="{{ $filters['business_model'] }}">
                        <input type="hidden" name="engine" value="{{ $filters['engine'] }}">
                        <select name="founder_id" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                            <option value="">Choose founder</option>
                            @foreach ($founders as $founder)
                                @if (in_array('servio', $founder['engines'], true))
                                    <option value="{{ $founder['id'] }}">{{ $founder['company_name'] }} · {{ $founder['name'] }}</option>
                                @endif
                            @endforeach
                        </select>
                        <input type="text" name="target_name" placeholder="Exact service name" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                        <div class="grid-2">
                            <select name="field" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <option value="availability_days">Availability days</option>
                                <option value="open_time">Open time</option>
                                <option value="close_time">Close time</option>
                                <option value="status">Status</option>
                            </select>
                            <input type="text" name="value" placeholder="Monday|Tuesday|Friday or 09:00 or active" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                        </div>
                        <div class="cta-row">
                            <button class="btn primary" type="submit">Update Availability</button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Bazaar Extras Governance</h2>
                    <p class="muted">Use synced extras below to target the right product, then rewrite the full extras list from the OS when pricing or packaging needs correction.</p>
                    <div class="stack" style="margin-top: 14px;">
                        <div class="stack-item">
                            <strong>Synced reusable extras</strong><br>
                            <span class="muted">{{ implode(' · ', $catalog['bazaar']['extras']) ?: 'No Bazaar extras synced yet.' }}</span>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.commerce.offer.update') }}" class="stack" style="margin-top: 14px;">
                        @csrf
                        <input type="hidden" name="platform" value="bazaar">
                        <input type="hidden" name="field" value="extras">
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                        <input type="hidden" name="business_model" value="{{ $filters['business_model'] }}">
                        <input type="hidden" name="engine" value="{{ $filters['engine'] }}">
                        <select name="founder_id" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                            <option value="">Choose founder</option>
                            @foreach ($founders as $founder)
                                @if (in_array('bazaar', $founder['engines'], true))
                                    <option value="{{ $founder['id'] }}">{{ $founder['company_name'] }} · {{ $founder['name'] }}</option>
                                @endif
                            @endforeach
                        </select>
                        <input type="text" name="target_name" placeholder="Exact product name" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                        <textarea name="value" rows="4" placeholder="Gift wrap | 5&#10;Premium packaging | 10" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;"></textarea>
                        <div class="cta-row">
                            <button class="btn primary" type="submit">Rewrite Bazaar Extras</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <h2>Servio Additional Services Governance</h2>
                    <p class="muted">Use the synced add-on list below to standardize service upsells and keep founder add-on menus clean across Servio-backed businesses.</p>
                    <div class="stack" style="margin-top: 14px;">
                        <div class="stack-item">
                            <strong>Synced additional services</strong><br>
                            <span class="muted">{{ implode(' · ', $catalog['servio']['additional_services']) ?: 'No Servio additional services synced yet.' }}</span>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.commerce.offer.update') }}" class="stack" style="margin-top: 14px;">
                        @csrf
                        <input type="hidden" name="platform" value="servio">
                        <input type="hidden" name="field" value="additional_services">
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                        <input type="hidden" name="business_model" value="{{ $filters['business_model'] }}">
                        <input type="hidden" name="engine" value="{{ $filters['engine'] }}">
                        <select name="founder_id" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                            <option value="">Choose founder</option>
                            @foreach ($founders as $founder)
                                @if (in_array('servio', $founder['engines'], true))
                                    <option value="{{ $founder['id'] }}">{{ $founder['company_name'] }} · {{ $founder['name'] }}</option>
                                @endif
                            @endforeach
                        </select>
                        <input type="text" name="target_name" placeholder="Exact service name" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                        <textarea name="value" rows="4" placeholder="Nail trim | 10&#10;Priority arrival | 15" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;"></textarea>
                        <div class="cta-row">
                            <button class="btn primary" type="submit">Rewrite Servio Add-ons</button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Bazaar Order Intervention</h2>
                    <p class="muted">Update order status, payment, fulfillment notes, delivery, or customer follow-up from the OS.</p>
                    <form method="POST" action="{{ route('admin.commerce.operation.update') }}" class="stack" style="margin-top: 14px;">
                        @csrf
                        <input type="hidden" name="platform" value="bazaar">
                        <input type="hidden" name="category" value="order">
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                        <input type="hidden" name="business_model" value="{{ $filters['business_model'] }}">
                        <input type="hidden" name="engine" value="{{ $filters['engine'] }}">
                        <select name="founder_id" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                            <option value="">Choose founder</option>
                            @foreach ($founders as $founder)
                                @if (in_array('bazaar', $founder['engines'], true))
                                    <option value="{{ $founder['id'] }}">{{ $founder['company_name'] }} · {{ $founder['name'] }}</option>
                                @endif
                            @endforeach
                        </select>
                        <input type="text" name="target_name" placeholder="Exact order number" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                        <div class="grid-2">
                            <select name="field" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <option value="status">Status</option>
                                <option value="payment_status">Payment status</option>
                                <option value="vendor_note">Vendor note</option>
                                <option value="delivery_date">Delivery date</option>
                                <option value="delivery_time">Delivery time</option>
                                <option value="customer_message">Customer message</option>
                            </select>
                            <select name="message_channel" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <option value="manual">Manual</option>
                                <option value="email">Email</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="sms">SMS</option>
                            </select>
                        </div>
                        <textarea name="value" rows="4" placeholder="Examples: processing, paid, 2026-05-01, 14:30, or your customer update message" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;"></textarea>
                        <div class="cta-row">
                            <button class="btn primary" type="submit">Update Bazaar Order</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <h2>Servio Booking Intervention</h2>
                    <p class="muted">Update booking status, payment, staff, timing, notes, or customer follow-up from the OS.</p>
                    <form method="POST" action="{{ route('admin.commerce.operation.update') }}" class="stack" style="margin-top: 14px;">
                        @csrf
                        <input type="hidden" name="platform" value="servio">
                        <input type="hidden" name="category" value="booking">
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                        <input type="hidden" name="business_model" value="{{ $filters['business_model'] }}">
                        <input type="hidden" name="engine" value="{{ $filters['engine'] }}">
                        <select name="founder_id" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                            <option value="">Choose founder</option>
                            @foreach ($founders as $founder)
                                @if (in_array('servio', $founder['engines'], true))
                                    <option value="{{ $founder['id'] }}">{{ $founder['company_name'] }} · {{ $founder['name'] }}</option>
                                @endif
                            @endforeach
                        </select>
                        <input type="text" name="target_name" placeholder="Exact booking number" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                        <div class="grid-2">
                            <select name="field" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <option value="status">Status</option>
                                <option value="payment_status">Payment status</option>
                                <option value="staff_id">Staff ID</option>
                                <option value="booking_date">Booking date</option>
                                <option value="booking_time">Start time</option>
                                <option value="booking_endtime">End time</option>
                                <option value="booking_notes">Booking notes</option>
                                <option value="customer_message">Customer message</option>
                            </select>
                            <select name="message_channel" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;">
                                <option value="manual">Manual</option>
                                <option value="email">Email</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="sms">SMS</option>
                            </select>
                        </div>
                        <textarea name="value" rows="4" placeholder="Examples: processing, paid, 12, 2026-05-01, 09:00, 10:00, or your customer update message" style="width:100%;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;"></textarea>
                        <div class="cta-row">
                            <button class="btn primary" type="submit">Update Servio Booking</button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="card" style="margin-top: 22px;">
                <h2>Founder Commerce Coverage</h2>
                <div class="stack" style="margin-top: 14px;">
                    @forelse ($founders as $founder)
                        <div class="stack-item">
                            <div style="display:flex;justify-content:space-between;gap:12px;align-items:start;flex-wrap:wrap;">
                                <div>
                                    <strong>{{ $founder['company_name'] }}</strong><br>
                                    {{ $founder['name'] }} · {{ $founder['email'] }} · {{ ucfirst($founder['business_model']) }} · {{ $founder['plan_name'] }}
                                </div>
                                <div class="pill">{{ str_replace('_', ' ', ucfirst($founder['website_status'])) }}</div>
                            </div>
                            @if ($founder['website_path'])
                                <div class="muted" style="margin-top: 6px;">Public path: /{{ $founder['website_path'] }}</div>
                            @endif

                            <div class="grid-2" style="margin-top: 12px;">
                                @foreach (['bazaar' => 'Bazaar', 'servio' => 'Servio'] as $engineKey => $engineLabel)
                                    <div class="stack-item" style="background: rgba(240, 231, 218, 0.35);">
                                        <div style="display:flex;justify-content:space-between;gap:12px;align-items:start;flex-wrap:wrap;">
                                            <strong>{{ $engineLabel }}</strong>
                                            <span class="pill" style="
                                                @if ($founder[$engineKey]['status_tone'] === 'success')
                                                    background: rgba(44,122,87,0.1); color: var(--success); border-color: rgba(44,122,87,0.18);
                                                @elseif ($founder[$engineKey]['status_tone'] === 'warning')
                                                    background: rgba(180,83,9,0.08); color: #b45309; border-color: rgba(180,83,9,0.18);
                                                @else
                                                    background: rgba(179,34,83,0.08); color: var(--rose); border-color: rgba(179,34,83,0.18);
                                                @endif
                                            ">{{ $founder[$engineKey]['status'] }}</span>
                                        </div>
                                        <div class="muted" style="margin-top: 6px;">
                                            @if (in_array($engineKey, $founder['engines'], true))
                                                {{ $founder[$engineKey]['status_reason'] }}
                                            @else
                                                Not applicable for this founder’s business model.
                                            @endif
                                        </div>
                                        @if (in_array($engineKey, $founder['engines'], true))
                                            <div class="muted" style="margin-top: 6px;">
                                                Readiness {{ $founder[$engineKey]['readiness_score'] }}%
                                                @if ($founder[$engineKey]['last_synced_at'])
                                                    · {{ $founder[$engineKey]['last_synced_at'] }}
                                                @endif
                                            </div>
                                            <div class="muted" style="margin-top: 6px;">
                                                @if ($engineKey === 'bazaar')
                                                    Products {{ $founder[$engineKey]['summary']['product_count'] ?? 0 }} · Orders {{ $founder[$engineKey]['summary']['order_count'] ?? 0 }}
                                                @else
                                                    Services {{ $founder[$engineKey]['summary']['service_count'] ?? 0 }} · Bookings {{ $founder[$engineKey]['summary']['booking_count'] ?? 0 }}
                                                @endif
                                            </div>
                                            @if (!empty($founder[$engineKey]['attention_items']))
                                                <div class="stack" style="margin-top: 10px;">
                                                    @foreach ($founder[$engineKey]['attention_items'] as $attention)
                                                        <div class="stack-item" style="background:#fff;">{{ $attention }}</div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="stack-item">
                            <strong>No founders match this commerce filter</strong><br>
                            Try widening the search or clearing one of the business model or engine filters.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Bazaar Live Catalog</h2>
                    <div class="stack" style="margin-top: 14px;">
                        <div class="stack-item">
                            <strong>Categories</strong><br>
                            <span class="muted">{{ implode(' · ', $catalog['bazaar']['categories']) ?: 'No Bazaar categories synced yet.' }}</span>
                        </div>
                        <div class="stack-item">
                            <strong>Tax Rules</strong><br>
                            <span class="muted">{{ implode(' · ', $catalog['bazaar']['taxes']) ?: 'No Bazaar taxes synced yet.' }}</span>
                        </div>
                        @forelse ($catalog['bazaar']['products'] as $product)
                            <div class="stack-item">
                                <strong>{{ $product['name'] ?? 'Product' }}</strong><br>
                                <span class="muted">
                                    {{ $product['category_name'] ?? 'No category' }}
                                    · {{ $product['price'] ?? '0' }}
                                    @if (!empty($product['variants']))
                                        · {{ count($product['variants']) }} variants
                                    @endif
                                    @if (!empty($product['extras']))
                                        · {{ count($product['extras']) }} extras
                                    @endif
                                </span>
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No Bazaar products synced yet</strong><br>
                                Product founders will appear here once Bazaar snapshots flow back into the OS.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <h2>Servio Live Catalog</h2>
                    <div class="stack" style="margin-top: 14px;">
                        <div class="stack-item">
                            <strong>Categories</strong><br>
                            <span class="muted">{{ implode(' · ', $catalog['servio']['categories']) ?: 'No Servio categories synced yet.' }}</span>
                        </div>
                        <div class="stack-item">
                            <strong>Tax Rules</strong><br>
                            <span class="muted">{{ implode(' · ', $catalog['servio']['taxes']) ?: 'No Servio taxes synced yet.' }}</span>
                        </div>
                        <div class="stack-item">
                            <strong>Staff Roster</strong><br>
                            <span class="muted">
                                {{ collect($catalog['servio']['staff'])->pluck('name')->filter()->implode(' · ') ?: 'No staff synced yet.' }}
                            </span>
                        </div>
                        <div class="stack-item">
                            <strong>Additional Services</strong><br>
                            <span class="muted">{{ implode(' · ', $catalog['servio']['additional_services']) ?: 'No additional services synced yet.' }}</span>
                        </div>
                        @forelse ($catalog['servio']['services'] as $service)
                            <div class="stack-item">
                                <strong>{{ $service['name'] ?? 'Service' }}</strong><br>
                                <span class="muted">
                                    {{ $service['category_name'] ?? 'No category' }}
                                    · {{ $service['price'] ?? '0' }}
                                    @if (!empty($service['staff_ids']))
                                        · {{ count($service['staff_ids']) }} staff linked
                                    @endif
                                    @if (!empty($service['additional_services']))
                                        · {{ count($service['additional_services']) }} add-ons
                                    @endif
                                </span>
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No Servio services synced yet</strong><br>
                                Service founders will appear here once Servio snapshots flow back into the OS.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Recent Bazaar Products</h2>
                    <p class="muted">Use these synced product names directly in Bazaar offer interventions when variants or extras need cleanup.</p>
                    <div class="stack" style="margin-top: 14px;">
                        @forelse ($recentOperations['bazaar_products'] as $product)
                            <div class="stack-item">
                                <strong>{{ $product['name'] }}</strong><br>
                                {{ $product['company_name'] }} · {{ $product['founder_name'] }}
                                <div class="muted" style="margin-top: 6px;">
                                    {{ $product['category_name'] }} · {{ $product['price'] }} · {{ ucfirst($product['status']) }}
                                    @if ($product['variants_count'] > 0)
                                        · {{ $product['variants_count'] }} variants
                                    @endif
                                    @if ($product['extras_count'] > 0)
                                        · {{ $product['extras_count'] }} extras
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No Bazaar products synced yet</strong><br>
                                Synced product names will appear here as Bazaar founder snapshots refresh into the OS.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <h2>Recent Servio Services</h2>
                    <p class="muted">Use these synced service names directly in Servio offer interventions when staff assignment, availability, or add-ons need admin help.</p>
                    <div class="stack" style="margin-top: 14px;">
                        @forelse ($recentOperations['servio_services'] as $service)
                            <div class="stack-item">
                                <strong>{{ $service['name'] }}</strong><br>
                                {{ $service['company_name'] }} · {{ $service['founder_name'] }}
                                <div class="muted" style="margin-top: 6px;">
                                    {{ $service['category_name'] }} · {{ $service['price'] }} · {{ ucfirst($service['status']) }}
                                    @if ($service['staff_count'] > 0)
                                        · {{ $service['staff_count'] }} staff linked
                                    @endif
                                    @if ($service['additional_services_count'] > 0)
                                        · {{ $service['additional_services_count'] }} add-ons
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No Servio services synced yet</strong><br>
                                Synced service names will appear here as Servio founder snapshots refresh into the OS.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Recent Bazaar Orders</h2>
                    <p class="muted">Admins can use these synced order numbers directly in the intervention form above instead of guessing IDs.</p>
                    <div class="stack" style="margin-top: 14px;">
                        @forelse ($recentOperations['bazaar_orders'] as $order)
                            <div class="stack-item">
                                <strong>{{ $order['number'] }}</strong><br>
                                {{ $order['company_name'] }} · {{ $order['founder_name'] }}
                                <div class="muted" style="margin-top: 6px;">
                                    {{ $order['customer_name'] }} · {{ ucfirst($order['status']) }} · {{ ucfirst($order['payment_status']) }} · {{ $order['amount'] }}
                                    @if ($order['delivery_date'] || $order['delivery_time'])
                                        · {{ trim($order['delivery_date'] . ' ' . $order['delivery_time']) }}
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No Bazaar orders synced yet</strong><br>
                                Recent order numbers will appear here as Bazaar founder snapshots keep flowing into the OS.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <h2>Recent Servio Bookings</h2>
                    <p class="muted">Admins can use these synced booking numbers directly in the intervention form above instead of relying on raw database lookups.</p>
                    <div class="stack" style="margin-top: 14px;">
                        @forelse ($recentOperations['servio_bookings'] as $booking)
                            <div class="stack-item">
                                <strong>{{ $booking['number'] }}</strong><br>
                                {{ $booking['company_name'] }} · {{ $booking['founder_name'] }}
                                <div class="muted" style="margin-top: 6px;">
                                    {{ $booking['customer_name'] }} · {{ $booking['service_name'] }} · {{ ucfirst($booking['status']) }} · {{ ucfirst($booking['payment_status']) }}
                                    @if ($booking['booking_date'] || $booking['booking_time'])
                                        · {{ trim($booking['booking_date'] . ' ' . $booking['booking_time']) }}
                                        @if ($booking['booking_endtime'])
                                            - {{ $booking['booking_endtime'] }}
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No Servio bookings synced yet</strong><br>
                                Recent booking numbers will appear here once service founder snapshots are refreshed into the OS.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
