@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page { padding: 0; }
        .tracker-shell { min-height:100vh; display:grid; grid-template-columns:220px minmax(0,1fr) 250px; background:#f8f5ee; }
        .tracker-sidebar, .tracker-rightbar { background:rgba(255,252,247,0.8); border-color:var(--line); border-style:solid; border-width:0 1px 0 0; min-height:100vh; display:flex; flex-direction:column; }
        .tracker-rightbar { border-width:0 0 0 1px; background:rgba(255,251,246,0.9); }
        .tracker-sidebar-inner, .tracker-rightbar-inner { padding:22px 18px; }
        .tracker-brand { display:inline-block; margin-bottom:24px; }
        .tracker-brand img { width:168px; height:auto; display:block; }
        .tracker-nav { display:grid; gap:6px; }
        .tracker-nav-item { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:14px; text-decoration:none; color:var(--ink); font-size:0.98rem; }
        .tracker-nav-item.active { background:#ece6db; }
        .tracker-nav-icon { width:18px; text-align:center; color:var(--muted); }
        .tracker-sidebar-footer { margin-top:auto; padding:18px; border-top:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .tracker-user { display:flex; align-items:center; gap:10px; }
        .tracker-avatar { width:30px; height:30px; border-radius:999px; background:#b0a999; color:#fff; display:grid; place-items:center; font-weight:700; font-size:0.92rem; flex-shrink:0; }
        .tracker-main { padding:26px 28px 24px; }
        .tracker-main-inner { max-width:930px; margin:0 auto; }
        .tracker-main h1 { font-size:clamp(2rem, 3vw, 3rem); letter-spacing:-0.02em; margin-bottom:6px; }
        .tracker-main p { color:var(--muted); margin-bottom:24px; }
        .tracker-banner { border-radius:16px; padding:14px 16px; border:1px solid rgba(220,207,191,0.8); background:rgba(255,255,255,0.9); margin-bottom:14px; }
        .tracker-banner.success { border-color:rgba(44,122,87,0.26); background:rgba(226,245,236,0.9); }
        .tracker-banner.error { border-color:rgba(179,34,83,0.22); background:rgba(255,241,246,0.92); }
        .tracker-metrics { display:grid; grid-template-columns:repeat(5, minmax(0,1fr)); gap:12px; margin-bottom:18px; }
        .tracker-card, .tracker-metric, .tracker-rail-card { background:rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:18px; padding:18px 20px; box-shadow:0 10px 28px rgba(52,41,26,0.04); }
        .tracker-metric strong { display:block; font-size:1.5rem; margin-top:6px; }
        .tracker-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:12px; }
        .tracker-stack { display:grid; gap:10px; }
        .tracker-field label { display:block; font-size:0.92rem; font-weight:600; color:var(--ink); }
        .tracker-field input, .tracker-field select, .tracker-field textarea { width:100%; margin-top:6px; border:1px solid rgba(220,207,191,0.9); background:#fff; border-radius:12px; padding:10px 12px; font:inherit; color:var(--ink); }
        .tracker-field textarea { min-height:92px; resize:vertical; }
        .tracker-form-grid { display:grid; gap:10px; grid-template-columns:repeat(2, minmax(0,1fr)); }
        .tracker-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .tracker-btn, .tracker-pill { display:inline-block; padding:10px 14px; border-radius:10px; text-decoration:none; font-weight:600; border:0; cursor:pointer; font:inherit; }
        .tracker-btn { background:linear-gradient(90deg,#8e1c74,#ff2c35); color:#fff; }
        .tracker-pill { background:#f0ece4; color:#5d554a; }
        .tracker-row-header { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; flex-wrap:wrap; }
        .tracker-muted { color:var(--muted); }
        .tracker-lead-card { border:1px solid rgba(220,207,191,0.72); border-radius:16px; padding:16px; background:#fffdf9; }
        .tracker-script-box { border:1px solid rgba(220,207,191,0.72); border-radius:14px; padding:12px 14px; background:#fff; }
        .tracker-section-title { font-size:1.08rem; margin-bottom:12px; }
        .tracker-progress { height:10px; border-radius:999px; background:#ebe4d9; overflow:hidden; margin-top:8px; }
        .tracker-progress span { display:block; height:100%; background:linear-gradient(90deg,#ff824d,#ff2c35); }
        .tracker-milestone { display:flex; justify-content:space-between; gap:10px; align-items:center; padding:12px 0; border-top:1px solid rgba(220,207,191,0.5); }
        .tracker-milestone:first-child { border-top:0; padding-top:0; }
        .tracker-badge { display:inline-flex; align-items:center; justify-content:center; padding:7px 11px; border-radius:999px; background:#ece6db; font-size:0.84rem; }
        .tracker-badge.success { background:rgba(44,122,87,0.12); color:#21643a; }
        .tracker-badge.warning { background:rgba(154,107,27,0.12); color:#9a6b1b; }
        .tracker-mode-nav { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
        .tracker-mode-tab { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:999px; text-decoration:none; color:var(--ink); background:rgba(255,255,255,0.88); border:1px solid rgba(220,207,191,0.8); font-weight:600; }
        .tracker-mode-tab.active { background:#ece6db; }
        .tracker-helper { margin-bottom:18px; padding:14px 16px; border-radius:16px; background:rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.7); color:var(--muted); }
        @media (max-width:1240px) { .tracker-shell { grid-template-columns:220px 1fr; } .tracker-rightbar { display:none; } }
        @media (max-width:900px) { .tracker-shell { grid-template-columns:1fr; } .tracker-sidebar { min-height:auto; border-right:0; border-bottom:1px solid var(--line); } .tracker-sidebar-footer { display:none; } .tracker-main { padding:20px 16px 24px; } .tracker-grid, .tracker-metrics, .tracker-form-grid { grid-template-columns:1fr; } }
    </style>
@endsection

@section('content')
    @php
        $founder = $dashboard['founder'];
        $summary = $tracker['summary'];
        $metrics = $summary['metrics'];
        $dailyPlan = $summary['daily_plan'];
        $channelPerformance = $summary['channel_performance'];
        $milestones = $summary['milestones'];
        $bestOffer = $summary['best_offer'];
        $acquisitionEngine = $summary['acquisition_engine'];
        $conversationEngine = $tracker['conversation_engine'];
        $followUpEngine = $tracker['follow_up_engine'];
        $offlineBridge = $tracker['offline_bridge'];
        $filters = $tracker['filters'];
        $leads = $tracker['leads'];
        $trackerMode = request()->query('mode', 'pipeline');
        if (!in_array($trackerMode, ['pipeline', 'scripts', 'offline'], true)) {
            $trackerMode = 'pipeline';
        }
        $trackerModeHelp = [
            'pipeline' => 'Use this mode to track leads, update stages, and manage follow-up records clearly.',
            'scripts' => 'Use this mode when you are actively messaging leads and need scripts, objections, and follow-up rules.',
            'offline' => 'Use this mode when you are running flyer, QR, referral, or in-person campaigns and want source attribution.',
        ];
    @endphp

    <div class="tracker-shell">
        <aside class="tracker-sidebar">
            @include('os.partials.founder-sidebar', [
                'founder' => $founder,
                'businessModel' => $founder->company->business_model ?? 'hybrid',
                'activeKey' => 'first-100',
                'navClass' => 'tracker-nav',
                'itemClass' => 'tracker-nav-item',
                'iconClass' => 'tracker-nav-icon',
                'innerClass' => 'tracker-sidebar-inner',
                'brandClass' => 'tracker-brand',
                'footerClass' => 'tracker-sidebar-footer',
                'userClass' => 'tracker-user',
                'avatarClass' => 'tracker-avatar',
            ])
        </aside>

        <main class="tracker-main">
            <div class="tracker-main-inner">
                <h1>Lead Tracker</h1>
                <p>Track named leads, follow-up, stages, and outreach history here. Use Tasks to decide what to do next.</p>
                <div class="tracker-mode-nav">
                    <a class="tracker-mode-tab {{ $trackerMode === 'pipeline' ? 'active' : '' }}" href="{{ route('founder.first-100', array_filter(['mode' => 'pipeline'])) }}">Leads</a>
                    <a class="tracker-mode-tab {{ $trackerMode === 'scripts' ? 'active' : '' }}" href="{{ route('founder.first-100', array_filter(['mode' => 'scripts'])) }}">Scripts</a>
                    <a class="tracker-mode-tab {{ $trackerMode === 'offline' ? 'active' : '' }}" href="{{ route('founder.first-100', array_filter(['mode' => 'offline'])) }}">Offline</a>
                </div>
                <div class="tracker-helper">{{ $trackerModeHelp[$trackerMode] }}</div>

                @if (session('success'))
                    <div class="tracker-banner success">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="tracker-banner error">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="tracker-banner error">{{ $errors->first() }}</div>
                @endif

                <section class="tracker-metrics">
                    <div class="tracker-metric"><div class="tracker-muted">Identified leads</div><strong>{{ $metrics['identified_leads'] }}</strong></div>
                    <div class="tracker-metric"><div class="tracker-muted">Active conversations</div><strong>{{ $metrics['active_conversations'] }}</strong></div>
                    <div class="tracker-metric"><div class="tracker-muted">Customers won</div><strong>{{ $metrics['customers_won'] }}</strong></div>
                    <div class="tracker-metric"><div class="tracker-muted">Follow-up due</div><strong>{{ $metrics['follow_up_due'] }}</strong></div>
                    <div class="tracker-metric"><div class="tracker-muted">Pipeline value</div><strong>USD {{ number_format((float) $metrics['estimated_pipeline_value'], 2) }}</strong></div>
                </section>

                @if ($trackerMode === 'scripts')
                <section class="tracker-card" style="margin-bottom:12px;">
                    <h2 class="tracker-section-title">{{ $conversationEngine['headline'] }}</h2>
                    <div class="tracker-muted">{{ $conversationEngine['focus'] }}</div>
                    @if ($conversationEngine['priority_lead_name'] !== '')
                        <div class="tracker-muted" style="margin-top:8px;"><strong>Priority conversation:</strong> {{ $conversationEngine['priority_lead_name'] }} · {{ $conversationEngine['priority_lead_stage'] }}</div>
                    @endif
                    <div class="tracker-grid" style="margin-top:14px;">
                        @foreach ($conversationEngine['objection_prompts'] as $prompt)
                            <div class="tracker-lead-card">
                                <strong>{{ $prompt['objection'] }}</strong>
                                <div class="tracker-muted" style="margin-top:6px;">{{ $prompt['response'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="tracker-card" style="margin-bottom:12px;">
                    <h2 class="tracker-section-title">{{ $followUpEngine['headline'] }}</h2>
                    <div class="tracker-muted">{{ $followUpEngine['focus'] }}</div>
                    <div class="tracker-grid" style="margin-top:14px;">
                        @foreach ($followUpEngine['templates'] as $template)
                            <div class="tracker-lead-card">
                                <div class="tracker-row-header">
                                    <div>
                                        <strong>{{ $template['name'] }}</strong><br>
                                        <span class="tracker-muted">{{ $template['description'] }}</span>
                                    </div>
                                    <div class="tracker-badge {{ $template['active'] ? 'success' : 'warning' }}">
                                        {{ $template['active'] ? 'Active' : 'Recommended' }}
                                    </div>
                                </div>
                                <div class="tracker-muted" style="margin-top:8px;">
                                    {{ $template['queue_count'] }} {{ $template['queue_label'] }} right now
                                </div>
                                <div class="tracker-actions" style="margin-top:12px;">
                                    @if (!$template['active'])
                                        <form method="POST" action="{{ route('founder.automations.templates.store') }}" style="margin:0;">
                                            @csrf
                                            <input type="hidden" name="template_key" value="{{ $template['template_key'] }}">
                                            <button class="tracker-btn" type="submit">Save follow-up rule</button>
                                        </form>
                                    @endif
                                    <a class="tracker-pill" href="{{ $template['href'] }}">Open queue</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if (!empty($followUpEngine['queue']))
                        <div class="tracker-stack" style="margin-top:12px;">
                            @foreach ($followUpEngine['queue'] as $queueLead)
                                <div class="tracker-script-box">
                                    <strong>{{ $queueLead['lead_name'] }}</strong>
                                    <div class="tracker-muted" style="margin-top:6px;">
                                        {{ $queueLead['channel_label'] }} · {{ $queueLead['stage_label'] }}
                                        @if ($queueLead['is_public_intro'])
                                            · Public intro
                                        @endif
                                        @if ($queueLead['promo_code'] !== '')
                                            · Promo {{ $queueLead['promo_code'] }}
                                        @endif
                                    </div>
                                    <div class="tracker-muted" style="margin-top:6px;">
                                        {{ $queueLead['next_follow_up_at'] ?: 'No follow-up scheduled yet' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
                @endif

                @if ($trackerMode === 'offline')
                <section class="tracker-card" style="margin-bottom:12px;">
                    <h2 class="tracker-section-title">{{ $offlineBridge['headline'] }}</h2>
                    <div class="tracker-muted">{{ $offlineBridge['focus'] }}</div>
                    <div class="tracker-grid" style="margin-top:14px;">
                        <div class="tracker-lead-card">
                            <strong>Create a promo link</strong>
                            <div class="tracker-muted" style="margin-top:6px;">Create one QR-ready or flyer-ready URL per source so the OS can attribute real offline traffic.</div>
                            <form method="POST" action="{{ route('founder.first-100.promo-links.store') }}" class="tracker-form-grid" style="margin-top:12px;">
                                @csrf
                                <div class="tracker-field">
                                    <label for="promo-title">Campaign label</label>
                                    <input id="promo-title" name="title" type="text" placeholder="Maadi flyer campaign">
                                </div>
                                <div class="tracker-field">
                                    <label for="promo-source">Source channel</label>
                                    <select id="promo-source" name="source_channel">
                                        @foreach (['flyer', 'qr_poster', 'referral_card', 'window_sign', 'event_booth', 'business_card', 'neighborhood_board'] as $source)
                                            <option value="{{ $source }}">{{ ucwords(str_replace('_', ' ', $source)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="tracker-field">
                                    <label for="promo-code">Promo code</label>
                                    <input id="promo-code" name="promo_code" type="text" placeholder="MAADI10">
                                </div>
                                <div class="tracker-field">
                                    <label for="promo-offer">Offer title</label>
                                    <input id="promo-offer" name="offer_title" type="text" placeholder="Free intro consult">
                                </div>
                                <div class="tracker-field" style="grid-column:1 / -1;">
                                    <label for="promo-cta">CTA label</label>
                                    <input id="promo-cta" name="cta_label" type="text" placeholder="Scan to get started">
                                </div>
                                <div class="tracker-actions" style="grid-column:1 / -1;">
                                    <button class="tracker-btn" type="submit">Save promo link</button>
                                </div>
                            </form>
                        </div>
                        <div class="tracker-lead-card">
                            <strong>Starter flyer copy</strong>
                            <div class="tracker-muted" style="margin-top:8px;"><strong>Headline:</strong> {{ $offlineBridge['starter_copy']['headline'] }}</div>
                            <div class="tracker-muted" style="margin-top:8px;"><strong>Body:</strong> {{ $offlineBridge['starter_copy']['body'] }}</div>
                            <div class="tracker-muted" style="margin-top:8px;"><strong>CTA:</strong> {{ $offlineBridge['starter_copy']['cta'] }}</div>
                            @foreach ($offlineBridge['quick_ideas'] as $idea)
                                <div class="tracker-muted" style="margin-top:8px;">{{ $idea }}</div>
                            @endforeach
                        </div>
                    </div>
                    @if (!empty($offlineBridge['promo_links']))
                        <div class="tracker-stack" style="margin-top:12px;">
                            @foreach ($offlineBridge['promo_links'] as $link)
                                <div class="tracker-script-box">
                                    <strong>{{ $link['title'] }}</strong>
                                    <div class="tracker-muted" style="margin-top:6px;">{{ $link['source_channel_label'] }} · Promo {{ $link['promo_code'] }}{{ $link['offer_title'] !== '' ? ' · ' . $link['offer_title'] : '' }}</div>
                                    <div class="tracker-muted" style="margin-top:6px;">{{ $link['stats']['captured_leads'] ?? 0 }} leads captured · {{ $link['stats']['won_leads'] ?? 0 }} won · {{ $link['stats']['follow_up_due'] ?? 0 }} follow-up due</div>
                                    <div class="tracker-muted" style="margin-top:6px; word-break:break-all;">{{ $link['url'] }}</div>
                                    <div class="tracker-actions" style="margin-top:10px;">
                                        <a class="tracker-pill" href="{{ route('founder.first-100.promo-links.kit', $link['id']) }}">Open campaign kit</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif
                    </section>
                @endif

                @if ($trackerMode === 'pipeline')
                <section class="tracker-grid">
                    <div class="tracker-card">
                        <h2 class="tracker-section-title">Add a lead</h2>
                        <form method="POST" action="{{ route('founder.first-100.leads.store') }}" class="tracker-form-grid">
                            @csrf
                            <div class="tracker-field">
                                <label for="lead_name">Lead name</label>
                                <input id="lead_name" name="lead_name" type="text" value="{{ old('lead_name') }}" placeholder="Sarah from Downtown Dog Owners">
                            </div>
                            <div class="tracker-field">
                                <label for="lead_channel">Channel</label>
                                <select id="lead_channel" name="lead_channel">
                                    @foreach (array_filter($tracker['channel_options'], fn ($option) => $option !== 'all') as $option)
                                        <option value="{{ $option }}" @selected(old('lead_channel') === $option)>{{ ucwords(str_replace('_', ' ', $option)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="tracker-field">
                                <label for="lead_stage">Stage</label>
                                <select id="lead_stage" name="lead_stage">
                                    @foreach (array_filter($tracker['stage_options'], fn ($option) => $option !== 'all') as $option)
                                        <option value="{{ $option }}" @selected(old('lead_stage', 'identified') === $option)>{{ ucwords(str_replace('_', ' ', $option)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="tracker-field">
                                <label for="offer_name">Offer</label>
                                <input id="offer_name" name="offer_name" type="text" value="{{ old('offer_name') }}" placeholder="Intro dog walking package">
                            </div>
                            <div class="tracker-field">
                                <label for="contact_handle">Contact handle</label>
                                <input id="contact_handle" name="contact_handle" type="text" value="{{ old('contact_handle') }}" placeholder="@sarah / 010... / email">
                            </div>
                            <div class="tracker-field">
                                <label for="city">City / area</label>
                                <input id="city" name="city" type="text" value="{{ old('city') }}" placeholder="Maadi">
                            </div>
                            <div class="tracker-field">
                                <label for="estimated_value">Estimated value</label>
                                <input id="estimated_value" name="estimated_value" type="number" min="0" step="0.01" value="{{ old('estimated_value') }}" placeholder="120">
                            </div>
                            <div class="tracker-field">
                                <label for="next_follow_up_at">Next follow-up</label>
                                <input id="next_follow_up_at" name="next_follow_up_at" type="datetime-local" value="{{ old('next_follow_up_at') }}">
                            </div>
                            <div class="tracker-field" style="grid-column:1 / -1;">
                                <label for="source_notes">Source notes</label>
                                <textarea id="source_notes" name="source_notes" placeholder="Where did this lead come from and why are they a fit?">{{ old('source_notes') }}</textarea>
                            </div>
                            <div class="tracker-actions" style="grid-column:1 / -1;">
                                <button class="tracker-btn" type="submit">Save lead</button>
                            </div>
                        </form>
                    </div>

                    <div class="tracker-card">
                        <h2 class="tracker-section-title">Filter the pipeline</h2>
                        <form method="GET" action="{{ route('founder.first-100') }}" class="tracker-form-grid">
                            <div class="tracker-field">
                                <label for="stage">Stage</label>
                                <select id="stage" name="stage">
                                    @foreach ($tracker['stage_options'] as $option)
                                        <option value="{{ $option }}" @selected($filters['stage'] === $option)>{{ $option === 'all' ? 'All stages' : ucwords(str_replace('_', ' ', $option)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="tracker-field">
                                <label for="channel">Channel</label>
                                <select id="channel" name="channel">
                                    @foreach ($tracker['channel_options'] as $option)
                                        <option value="{{ $option }}" @selected($filters['channel'] === $option)>{{ $option === 'all' ? 'All channels' : ucwords(str_replace('_', ' ', $option)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="tracker-field" style="grid-column:1 / -1;">
                                <label for="q">Search</label>
                                <input id="q" name="q" type="text" value="{{ $filters['q'] }}" placeholder="Lead name, offer, source, or handle">
                            </div>
                            <div class="tracker-actions" style="grid-column:1 / -1;">
                                <button class="tracker-btn" type="submit">Apply filters</button>
                                <a class="tracker-pill" href="{{ route('founder.first-100') }}">Clear</a>
                            </div>
                        </form>

                        <div class="tracker-stack" style="margin-top:18px;">
                            <div class="tracker-lead-card">
                                <strong>Best channel right now</strong>
                                <div class="tracker-muted" style="margin-top:6px;">{{ $summary['best_channel']['channel_label'] ?? 'No winning channel yet' }}</div>
                            </div>
                            <div class="tracker-lead-card">
                                <strong>Best offer signal</strong>
                                <div class="tracker-muted" style="margin-top:6px;">
                                    @if ($bestOffer)
                                        {{ $bestOffer['offer_name'] }} · {{ $bestOffer['won'] }} won / {{ $bestOffer['active'] }} active
                                    @else
                                        No offer pattern yet. Start logging the offer attached to each lead.
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="tracker-card" style="margin-top:12px;">
                    <h2 class="tracker-section-title">Lead tracker</h2>
                    <div class="tracker-stack">
                        @forelse ($leads as $lead)
                            @php
                                $leadFollowUpValue = $lead['next_follow_up_at']
                                    ? \Illuminate\Support\Carbon::parse($lead['next_follow_up_at'])->format('Y-m-d\TH:i')
                                    : '';
                            @endphp
                            <div class="tracker-lead-card">
                                <div class="tracker-row-header">
                                    <div>
                                        <strong>{{ $lead['lead_name'] }}</strong><br>
                                        <span class="tracker-muted">{{ $lead['lead_channel_label'] }} · {{ $lead['offer_name'] !== '' ? $lead['offer_name'] : 'No offer linked yet' }}</span>
                                    </div>
                                    <div class="tracker-badge {{ $lead['is_follow_up_due'] ? 'warning' : ($lead['lead_stage'] === 'won' ? 'success' : '') }}">{{ $lead['lead_stage_label'] }}</div>
                                </div>
                                <div class="tracker-muted" style="margin-top:8px;">
                                    {{ $lead['contact_handle'] !== '' ? $lead['contact_handle'] . ' · ' : '' }}{{ $lead['city'] !== '' ? $lead['city'] . ' · ' : '' }}Estimated value: USD {{ $lead['estimated_value_display'] }}
                                </div>
                                @if ($lead['source_notes'] !== '')
                                    <div class="tracker-muted" style="margin-top:8px;">{{ $lead['source_notes'] }}</div>
                                @endif
                                <div class="tracker-muted" style="margin-top:8px;">
                                    @if ($lead['next_follow_up_at'])
                                        Next follow-up: {{ $lead['next_follow_up_at'] }}
                                    @elseif ($lead['converted_at'])
                                        Converted: {{ $lead['converted_at'] }}
                                    @elseif ($lead['lost_at'])
                                        Marked lost: {{ $lead['lost_at'] }}
                                    @else
                                        No follow-up scheduled yet
                                    @endif
                                </div>

                                <form method="POST" action="{{ route('founder.first-100.leads.update', $lead['id']) }}" class="tracker-form-grid" style="margin-top:12px;">
                                    @csrf
                                    <div class="tracker-field">
                                        <label for="stage-{{ $lead['id'] }}">Stage</label>
                                        <select id="stage-{{ $lead['id'] }}" name="lead_stage">
                                            @foreach (array_filter($tracker['stage_options'], fn ($option) => $option !== 'all') as $option)
                                                <option value="{{ $option }}" @selected($lead['lead_stage'] === $option)>{{ ucwords(str_replace('_', ' ', $option)) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="tracker-field">
                                        <label for="follow-up-{{ $lead['id'] }}">Next follow-up</label>
                                        <input id="follow-up-{{ $lead['id'] }}" name="next_follow_up_at" type="datetime-local" value="{{ $leadFollowUpValue }}">
                                    </div>
                                    <div class="tracker-field" style="grid-column:1 / -1;">
                                        <label for="notes-{{ $lead['id'] }}">Stage notes</label>
                                        <textarea id="notes-{{ $lead['id'] }}" name="stage_notes" placeholder="What happened last, what is the next move?">{{ $lead['stage_notes'] }}</textarea>
                                    </div>
                                    <div class="tracker-actions" style="grid-column:1 / -1;">
                                        <button class="tracker-btn" type="submit">Update lead</button>
                                    </div>
                                </form>

                                <div class="tracker-grid" style="margin-top:12px;">
                                    <div class="tracker-script-box">
                                        <strong>{{ $lead['conversation_pack']['recommended_message_type'] }}</strong>
                                        <div class="tracker-muted" style="margin-top:6px; white-space:pre-line;">{{ $lead['conversation_pack']['primary_script'] }}</div>
                                    </div>
                                    <div class="tracker-script-box">
                                        <strong>Follow-up script</strong>
                                        <div class="tracker-muted" style="margin-top:6px; white-space:pre-line;">{{ $lead['conversation_pack']['follow_up_script'] }}</div>
                                    </div>
                                </div>

                                <div class="tracker-script-box" style="margin-top:12px;">
                                    <strong>Closing / next-step script</strong>
                                    <div class="tracker-muted" style="margin-top:6px; white-space:pre-line;">{{ $lead['conversation_pack']['closing_script'] }}</div>
                                </div>

                                <div class="tracker-grid" style="margin-top:12px;">
                                    @foreach ($lead['conversation_pack']['objection_replies'] as $objection)
                                        <div class="tracker-script-box">
                                            <strong>{{ $objection['objection'] }}</strong>
                                            <div class="tracker-muted" style="margin-top:6px; white-space:pre-line;">{{ $objection['reply'] }}</div>
                                        </div>
                                    @endforeach
                                </div>

                                <form method="POST" action="{{ route('founder.first-100.leads.touch', $lead['id']) }}" class="tracker-form-grid" style="margin-top:12px;">
                                    @csrf
                                    <div class="tracker-field">
                                        <label for="touch-type-{{ $lead['id'] }}">Log touch</label>
                                        <select id="touch-type-{{ $lead['id'] }}" name="touch_type">
                                            <option value="outreach_sent">Outreach sent</option>
                                            <option value="follow_up_sent">Follow-up sent</option>
                                            <option value="reply_received">Reply received</option>
                                            <option value="proposal_sent">Proposal sent</option>
                                            <option value="won">Won</option>
                                            <option value="lost">Lost</option>
                                        </select>
                                    </div>
                                    <div class="tracker-field">
                                        <label for="message-channel-{{ $lead['id'] }}">Message channel</label>
                                        <select id="message-channel-{{ $lead['id'] }}" name="message_channel">
                                            <option value="manual">Manual</option>
                                            <option value="whatsapp">WhatsApp</option>
                                            <option value="email">Email</option>
                                            <option value="sms">SMS</option>
                                            <option value="instagram">Instagram</option>
                                            <option value="facebook_groups">Facebook groups</option>
                                            <option value="nextdoor">Nextdoor</option>
                                            <option value="referral">Referral</option>
                                        </select>
                                    </div>
                                    <div class="tracker-field">
                                        <label for="touch-follow-up-{{ $lead['id'] }}">Next follow-up</label>
                                        <input id="touch-follow-up-{{ $lead['id'] }}" name="next_follow_up_at" type="datetime-local">
                                    </div>
                                    <div class="tracker-field" style="grid-column:1 / -1;">
                                        <label for="touch-note-{{ $lead['id'] }}">Touch note</label>
                                        <textarea id="touch-note-{{ $lead['id'] }}" name="touch_note" placeholder="What was sent, what they said, or what happens next?"></textarea>
                                    </div>
                                    <div class="tracker-actions" style="grid-column:1 / -1;">
                                        <button class="tracker-btn" type="submit">{{ $lead['conversation_pack']['next_step_label'] }}</button>
                                    </div>
                                </form>

                                @if (!empty($lead['conversation_pack']['touch_timeline']))
                                    <div class="tracker-stack" style="margin-top:12px;">
                                        @foreach ($lead['conversation_pack']['touch_timeline'] as $touch)
                                            <div class="tracker-script-box">
                                                <strong>{{ $touch['type'] }}</strong>
                                                <div class="tracker-muted" style="margin-top:6px;">{{ $touch['channel'] }} · {{ $touch['logged_at'] }}</div>
                                                @if ($touch['note'] !== '')
                                                    <div class="tracker-muted" style="margin-top:6px;">{{ $touch['note'] }}</div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="tracker-lead-card">
                                <strong>No leads in the tracker yet</strong>
                                <div class="tracker-muted" style="margin-top:8px;">Start with named people, not anonymous traffic. Your first 10 prospects are the foundation for the whole daily revenue OS.</div>
                            </div>
                        @endforelse
                    </div>
                </section>
                @endif
            </div>
        </main>

        <aside class="tracker-rightbar">
            <div class="tracker-rightbar-inner">
                <div class="tracker-rail-card">
                    <h3 style="font-size:0.83rem;letter-spacing:0.06em;text-transform:uppercase;color:var(--muted);margin-bottom:12px;">First 100 Progress</h3>
                    <strong>{{ $metrics['customers_won'] }} / 100 customers won</strong>
                    <div class="tracker-progress"><span style="width: {{ max(4, $metrics['first_hundred_progress_percent']) }}%;"></span></div>
                    <div class="tracker-muted" style="margin-top:8px;">The goal is not just traffic. The goal is repeatable customer acquisition and conversion.</div>
                </div>

                <div class="tracker-rail-card" style="margin-top:12px;">
                    <h3 style="font-size:0.83rem;letter-spacing:0.06em;text-transform:uppercase;color:var(--muted);margin-bottom:12px;">Follow-Up Rules</h3>
                    <strong>{{ $followUpEngine['active_rules_count'] }} active rule{{ $followUpEngine['active_rules_count'] === 1 ? '' : 's' }}</strong>
                    <div class="tracker-muted" style="margin-top:8px;">Use saved follow-up rules to keep fresh promo leads and due conversations from going cold.</div>
                    <div class="tracker-actions" style="margin-top:10px;">
                        <a class="tracker-pill" href="{{ route('founder.automations') }}">Open automations</a>
                    </div>
                </div>

                <div class="tracker-rail-card" style="margin-top:12px;">
                    <h3 style="font-size:0.83rem;letter-spacing:0.06em;text-transform:uppercase;color:var(--muted);margin-bottom:12px;">Milestones</h3>
                    @foreach ($milestones as $milestone)
                        <div class="tracker-milestone">
                            <div>
                                <strong>{{ $milestone['label'] }}</strong><br>
                                <span class="tracker-muted">
                                    @if ($milestone['completed'])
                                        Completed
                                    @else
                                        {{ $milestone['remaining'] }} remaining
                                    @endif
                                </span>
                            </div>
                            <div class="tracker-badge {{ $milestone['completed'] ? 'success' : '' }}">{{ $milestone['target'] }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="tracker-rail-card" style="margin-top:12px;">
                    <h3 style="font-size:0.83rem;letter-spacing:0.06em;text-transform:uppercase;color:var(--muted);margin-bottom:12px;">Channel Performance</h3>
                    <div class="tracker-stack">
                        @forelse (array_slice($channelPerformance, 0, 4) as $channel)
                            <div class="tracker-lead-card">
                                <strong>{{ $channel['channel_label'] }}</strong>
                                <div class="tracker-muted" style="margin-top:6px;">{{ $channel['leads'] }} leads · {{ $channel['active'] }} active · {{ $channel['won'] }} won</div>
                            </div>
                        @empty
                            <div class="tracker-lead-card">
                                <strong>No channel signal yet</strong>
                                <div class="tracker-muted" style="margin-top:6px;">Once you log leads, Hatchers will start showing which channel is actually moving customers.</div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="tracker-rail-card" style="margin-top:12px;">
                    <h3 style="font-size:0.83rem;letter-spacing:0.06em;text-transform:uppercase;color:var(--muted);margin-bottom:12px;">Channel Targets</h3>
                    <div class="tracker-stack">
                        @foreach (array_slice($acquisitionEngine['playbooks'], 0, 3) as $playbook)
                            <div class="tracker-lead-card">
                                <strong>{{ $playbook['channel_label'] }}</strong>
                                <div class="tracker-muted" style="margin-top:6px;">Target {{ $playbook['today_target'] }} new conversation{{ $playbook['today_target'] === 1 ? '' : 's' }} today.</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </aside>
    </div>
@endsection
