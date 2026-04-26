@extends('os.layout')

@section('content')
    @php
        $admin = $workspace['admin'];
        $metrics = $workspace['metrics'];
        $blueprints = $workspace['blueprints'];
        $founderReviews = $workspace['founder_reviews'];
        $pods = $workspace['pods'];
        $filters = $workspace['filters'];
        $filterOptions = $workspace['filter_options'];
    @endphp

    <div class="sidebar-layout">
        <aside class="sidebar-card">
            <div class="pill">Blueprint Control</div>
            <div class="nav-group" style="margin-top: 18px;">
                <div class="nav-group-title">Control Center</div>
                <a class="nav-item" href="/dashboard/admin">Overview</a>
                <a class="nav-item" href="{{ route('admin.subscribers') }}">Subscribers</a>
                <a class="nav-item" href="/admin/control">Founder Operations</a>
                <a class="nav-item" href="{{ route('admin.system-access') }}">System Access</a>
                <a class="nav-item" href="{{ route('admin.identity') }}">Identity</a>
                <a class="nav-item active" href="{{ route('admin.blueprints') }}">Blueprint Control</a>
                <a class="nav-item" href="{{ route('admin.commerce') }}">Commerce Control</a>
                <a class="nav-item" href="{{ route('admin.finance') }}">Finance Control</a>
                <a class="nav-item" href="{{ route('admin.modules') }}">Module Monitoring</a>
                <a class="nav-item" href="{{ route('admin.support') }}">Support Center</a>
                <a class="nav-item" href="/dashboard">OS Home</a>
            </div>
        </aside>

        <div>
            <section class="hero">
                <div class="eyebrow">Blueprint Control</div>
                <h1>Manage the vertical launch systems that Hatchers uses to build businesses for founders.</h1>
                <p class="muted">Welcome back, {{ $admin->full_name }}. This workspace is where we define business-in-a-box blueprints and review founder brief quality before automated website generation starts.</p>
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

            <section class="metrics">
                <div class="card metric"><div class="muted">Blueprints</div><strong>{{ $metrics['blueprint_count'] }}</strong></div>
                <div class="card metric"><div class="muted">Active blueprints</div><strong>{{ $metrics['active_blueprints'] }}</strong></div>
                <div class="card metric"><div class="muted">Founder briefs</div><strong>{{ $metrics['founder_briefs'] }}</strong></div>
                <div class="card metric"><div class="muted">Launch-ready founders</div><strong>{{ $metrics['launch_ready_founders'] }}</strong></div>
                <div class="card metric"><div class="muted">Applied launch systems</div><strong>{{ $metrics['applied_launch_systems'] }}</strong></div>
                <div class="card metric"><div class="muted">Applied pricing recs</div><strong>{{ $metrics['applied_pricing_recommendations'] }}</strong></div>
                <div class="card metric"><div class="muted">Adopted channels</div><strong>{{ $metrics['lead_channel_adoptions'] }}</strong></div>
                <div class="card metric"><div class="muted">Active pods</div><strong>{{ $metrics['active_pods'] }}</strong></div>
            </section>

            <section class="card" style="margin-top:22px;">
                <h2>Blueprint Filters</h2>
                <form method="GET" action="{{ route('admin.blueprints') }}" class="grid-2" style="margin-top: 14px;">
                    <label>
                        <span class="muted">Search</span>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Blueprint or founder" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;">
                    </label>
                    <label>
                        <span class="muted">Blueprint status</span>
                        <select name="status" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;">
                            <option value="">All statuses</option>
                            @foreach ($filterOptions['statuses'] as $option)
                                <option value="{{ $option }}" @selected(($filters['status'] ?? '') === $option)>{{ ucfirst($option) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="muted">Business model</span>
                        <select name="business_model" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;">
                            <option value="">All models</option>
                            @foreach ($filterOptions['business_models'] as $option)
                                <option value="{{ $option }}" @selected(($filters['business_model'] ?? '') === $option)>{{ ucfirst($option) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div style="grid-column:1 / -1;display:flex;gap:10px;flex-wrap:wrap;">
                        <button class="btn primary" type="submit">Apply filters</button>
                        <a class="btn" href="{{ route('admin.blueprints') }}">Clear</a>
                    </div>
                </form>
            </section>

            <section class="card" style="margin-top:22px;">
                <h2>Create Or Update Blueprint</h2>
                <p class="muted">Use one OS form to define the vertical launch system, the engine it should use, and the default assets that later generation layers will inherit.</p>
                <form method="POST" action="{{ route('admin.blueprints.store') }}" class="grid-2" style="margin-top:14px;">
                    @csrf
                    <input type="hidden" name="blueprint_id" value="">
                    <label><span class="muted">Code</span><input type="text" name="code" placeholder="dog-walking" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                    <label><span class="muted">Name</span><input type="text" name="name" placeholder="Dog Walking" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                    <label>
                        <span class="muted">Business model</span>
                        <select name="business_model" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;">
                            @foreach ($filterOptions['business_models'] as $option)
                                <option value="{{ $option }}">{{ ucfirst($option) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="muted">Engine</span>
                        <select name="engine" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;">
                            @foreach ($filterOptions['engines'] as $option)
                                <option value="{{ $option }}">{{ ucfirst($option) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="muted">Status</span>
                        <select name="status" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;">
                            @foreach ($filterOptions['statuses'] as $option)
                                <option value="{{ $option }}">{{ ucfirst($option) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label style="grid-column:1 / -1;"><span class="muted">Description</span><textarea name="description" rows="3" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;"></textarea></label>
                    <label><span class="muted">Default offer items</span><textarea name="default_offer" rows="3" placeholder="Single walk, Weekly plan, Feeding add-on" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;"></textarea></label>
                    <label><span class="muted">Default pricing tiers</span><textarea name="default_pricing" rows="3" placeholder="Single walk, 3-walk plan, 5-walk plan" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;"></textarea></label>
                    <label><span class="muted">Default pages</span><textarea name="default_pages" rows="3" placeholder="hero, how_it_works, services" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;"></textarea></label>
                    <label><span class="muted">Default tasks</span><textarea name="default_tasks" rows="3" placeholder="Join Facebook groups, Follow up with leads" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;"></textarea></label>
                    <label><span class="muted">Default channels</span><textarea name="default_channels" rows="3" placeholder="Facebook groups, Nextdoor, Local SEO" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;"></textarea></label>
                    <label><span class="muted">Default CTA</span><textarea name="default_cta" rows="3" placeholder="Book now, Request quote" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;"></textarea></label>
                    <label><span class="muted">Default image queries</span><textarea name="default_image_queries" rows="3" placeholder="dog walking, happy dog on leash" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;"></textarea></label>
                    <label><span class="muted">Funnel framework</span><textarea name="funnel_framework" rows="3" placeholder="Lead magnet, Problem, Proof, Offer stack, Guarantee, Urgency, FAQ" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;"></textarea></label>
                    <label><span class="muted">Pricing presets</span><textarea name="pricing_presets" rows="3" placeholder="Entry offer, Core offer, Premium upgrade" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;"></textarea></label>
                    <label><span class="muted">Channel playbooks</span><textarea name="channel_playbooks" rows="3" placeholder="Facebook groups, Nextdoor, Google Business Profile" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;"></textarea></label>
                    <label><span class="muted">Script library</span><textarea name="script_library" rows="3" placeholder="First outreach script, Follow-up script, Offer close script" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;"></textarea></label>
                    <label style="grid-column:1 / -1;"><span class="muted">Change summary</span><textarea name="change_summary" rows="2" placeholder="What changed in this blueprint version?" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;"></textarea></label>
                    <div style="grid-column:1 / -1;">
                        <button class="btn primary" type="submit">Save Blueprint</button>
                    </div>
                </form>
            </section>

            <section class="card" style="margin-top:22px;">
                <h2>Current Blueprints</h2>
                <div class="stack" style="margin-top:14px;">
                    @forelse ($blueprints as $blueprint)
                        <div class="stack-item">
                            <strong>{{ $blueprint['name'] }}</strong><br>
                            {{ $blueprint['code'] }} · {{ ucfirst($blueprint['business_model']) }} · {{ ucfirst($blueprint['engine']) }} · {{ ucfirst($blueprint['status']) }}
                            <div class="muted" style="margin-top:6px;">{{ $blueprint['description'] }}</div>
                            <div class="muted" style="margin-top:6px;">Offer: {{ $blueprint['default_offer'] ?: 'n/a' }}</div>
                            <div class="muted" style="margin-top:6px;">Pricing: {{ $blueprint['default_pricing'] ?: 'n/a' }}</div>
                            <div class="muted" style="margin-top:6px;">Pages: {{ $blueprint['default_pages'] ?: 'n/a' }}</div>
                            <div class="muted" style="margin-top:6px;">Tasks: {{ $blueprint['default_tasks'] ?: 'n/a' }}</div>
                            <div class="muted" style="margin-top:6px;">Channels: {{ $blueprint['default_channels'] ?: 'n/a' }}</div>
                            <div class="muted" style="margin-top:6px;">CTA: {{ $blueprint['default_cta'] ?: 'n/a' }}</div>
                            <div class="muted" style="margin-top:6px;">Images: {{ $blueprint['default_image_queries'] ?: 'n/a' }}</div>
                            <div class="muted" style="margin-top:6px;">Funnel: {{ $blueprint['funnel_framework'] ?: 'n/a' }}</div>
                            <div class="muted" style="margin-top:6px;">Pricing presets: {{ $blueprint['pricing_presets'] ?: 'n/a' }}</div>
                            <div class="muted" style="margin-top:6px;">Playbooks: {{ $blueprint['channel_playbooks'] ?: 'n/a' }}</div>
                            <div class="muted" style="margin-top:6px;">Scripts: {{ $blueprint['script_library'] ?: 'n/a' }}</div>
                            <div class="muted" style="margin-top:6px;">Version: v{{ $blueprint['version_number'] }} · Launch systems {{ $blueprint['applied_launch_systems'] }} · Pricing applied {{ $blueprint['pricing_applied_count'] }} · Pricing rejected {{ $blueprint['pricing_rejected_count'] }}</div>
                            @if (!empty($blueprint['versions']))
                                <div class="muted" style="margin-top:8px;"><strong>Recent versions</strong></div>
                                @foreach ($blueprint['versions'] as $version)
                                    <div class="muted" style="margin-top:4px;">{{ $version['label'] }} · {{ $version['summary'] ?: 'No summary' }} · {{ $version['created_at'] }}</div>
                                @endforeach
                            @endif
                        </div>
                    @empty
                        <div class="stack-item">
                            <strong>No blueprints yet</strong><br>
                            Once saved, Hatchers business-in-a-box systems will appear here.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="card" style="margin-top:22px;">
                <h2>Founder Brief Review</h2>
                <p class="muted">This is the queue where operations can judge whether a founder is ready for auto-generated website and launch output.</p>
                <div class="stack" style="margin-top:14px;">
                    @forelse ($founderReviews as $review)
                        <div class="stack-item">
                            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
                                <div>
                                    <strong>{{ $review['company_name'] }}</strong><br>
                                    {{ $review['founder_name'] }} · {{ $review['email'] }} · {{ ucfirst($review['business_model']) }} · {{ $review['vertical_name'] }}
                                </div>
                                <div class="pill">{{ str_replace('_', ' ', ucfirst($review['launch_stage'])) }}</div>
                            </div>
                            <div class="muted" style="margin-top:8px;">Market {{ $review['primary_city'] ?: 'n/a' }} · Radius {{ $review['service_radius'] ?: 'n/a' }} · Website generation {{ str_replace('_', ' ', ucfirst($review['website_generation_status'])) }}</div>
                            <div class="muted" style="margin-top:8px;">Brief: {{ $review['company_brief'] }}</div>
                            <div class="muted" style="margin-top:8px;">Problem solved: {{ $review['problem_solved'] ?: 'n/a' }}</div>
                            <div class="muted" style="margin-top:8px;">Differentiators: {{ $review['differentiators'] ?: 'n/a' }}</div>
                            <div class="muted" style="margin-top:8px;">ICP: {{ $review['primary_icp_name'] ?: 'n/a' }}</div>
                            <div class="muted" style="margin-top:8px;">Pain points: {{ $review['pain_points'] ?: 'n/a' }}</div>
                            <div class="muted" style="margin-top:8px;">Desired outcomes: {{ $review['desired_outcomes'] ?: 'n/a' }}</div>
                            <div class="muted" style="margin-top:8px;">Objections: {{ $review['objections'] ?: 'n/a' }}</div>
                            <div class="muted" style="margin-top:8px;">Launch system: {{ ucfirst(str_replace('_', ' ', $review['launch_system_status'])) }} · Pricing applied {{ $review['pricing_applied_count'] }} · Pricing rejected {{ $review['pricing_rejected_count'] }}</div>
                            <form method="POST" action="{{ route('admin.blueprints.founder-review') }}" class="grid-2" style="margin-top:12px;">
                                @csrf
                                <input type="hidden" name="founder_id" value="{{ $review['founder_id'] }}">
                                <label>
                                    <span class="muted">Launch stage</span>
                                    <select name="launch_stage" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;">
                                        @foreach (['brief_pending', 'brief_captured', 'generation_ready', 'website_review', 'live'] as $option)
                                            <option value="{{ $option }}" @selected($review['launch_stage'] === $option)>{{ str_replace('_', ' ', ucfirst($option)) }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>
                                    <span class="muted">Website generation status</span>
                                    <select name="website_generation_status" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;">
                                        @foreach (['not_started', 'queued', 'in_progress', 'ready_for_review', 'published'] as $option)
                                            <option value="{{ $option }}" @selected($review['website_generation_status'] === $option)>{{ str_replace('_', ' ', ucfirst($option)) }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label style="grid-column:1 / -1;">
                                    <span class="muted">Assign blueprint</span>
                                    <select name="vertical_blueprint_id" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;">
                                        <option value="">Keep current blueprint</option>
                                        @foreach ($blueprints as $blueprint)
                                            <option value="{{ $blueprint['id'] }}" @selected($review['vertical_code'] === $blueprint['code'])>{{ $blueprint['name'] }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <div style="grid-column:1 / -1;">
                                    <button class="btn primary" type="submit">Save Founder Review</button>
                                </div>
                            </form>
                        </div>
                    @empty
                        <div class="stack-item">
                            <strong>No founder briefs yet</strong><br>
                            Once founders complete the new intake, their business briefs and ICPs will appear here.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="card" style="margin-top:22px;">
                <h2>Pod Community Layer</h2>
                <p class="muted">These are the vertical peer groups now attached to the blueprint system for wins, blockers, and shared benchmarks.</p>
                <div class="stack" style="margin-top:14px;">
                    @forelse ($pods as $pod)
                        <div class="stack-item">
                            <strong>{{ $pod['name'] }}</strong><br>
                            {{ $pod['stage'] ?: 'Any stage' }} · {{ $pod['member_count'] }} members · {{ $pod['post_count'] }} posts
                        </div>
                    @empty
                        <div class="stack-item">
                            <strong>No pods yet</strong><br>
                            Pods will appear here as blueprints create their default peer groups.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
