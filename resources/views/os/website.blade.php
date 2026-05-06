@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'prototype-dashboard-page')

@php
    $founder = auth()->user();
    $osEmbedMode = request()->boolean('os_embed');
    $workspace = $dashboard['workspace'] ?? [];
    $companyName = $website['company_name'];
    $businessModel = $website['business_model'];
    $websiteStatus = $website['website_status'];
    $recommendedEngine = $website['recommended_engine'];
    $currentWebsiteUrl = $website['current_website_url'];
    $recommendedSubdomain = $website['recommended_subdomain'];
    $websitePath = $website['website_path'];
    $customDomain = $website['custom_domain'];
    $customDomainStatus = $website['custom_domain_status'];
    $generationStatus = $website['website_generation_status'];
    $buildBrief = $website['build_brief'] ?? [];
    $buildSummary = $buildBrief['company_intelligence_summary'] ?? [];
    $buildIntake = $buildBrief['intake'] ?? [];
    $buildMissingItems = $buildBrief['missing_items'] ?? [];
    $autopilot = $website['autopilot'] ?? [];
    $autopilotDraft = $autopilot['draft'] ?? null;
    $websiteStage = request()->query('stage', 'build');
    if (!in_array($websiteStage, ['build', 'overview', 'setup', 'publish'], true)) {
        $websiteStage = 'build';
    }
    $stageHelp = [
        'build' => 'Start here after company intelligence. Hatchers will collect the missing website direction and build the first draft for you.',
        'overview' => 'Review the first website draft, understand what Hatchers built, and regenerate if the message still needs work.',
        'setup' => 'Set the public title, path, and domain details before launch.',
        'publish' => 'Use this stage when the draft and setup are ready and you want the public site to go live.',
    ];
@endphp

@section('head')
    <style>
        .page.prototype-dashboard-page {
            --bg: #F9F8F6; --surface: #FBFAF7; --surface-2: #F4F1EC; --border: rgba(30, 24, 16, 0.10); --hairline: rgba(30, 24, 16, 0.08); --text: #1B1A17; --text-muted: #6B6660; --text-subtle: #A39E96; --accent-pink: #F2546B; --tile-purple: #C8B8D6; --tile-purple-2: #A99BBC; --tile-grey: #B8B0A6; --tile-grey-2: #8E867C; --shadow-sm: 0 1px 0 rgba(30,24,16,0.04); --shadow-md: 0 1px 2px rgba(30,24,16,0.06), 0 0 0 0.5px rgba(30,24,16,0.06); --shadow-lg: 0 8px 24px rgba(30,24,16,0.08), 0 1px 2px rgba(30,24,16,0.06);
            min-height: 100vh; padding: 0; background: var(--bg); font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: var(--text);
        }
        .page.prototype-dashboard-page * { box-sizing: border-box; }
        .prototype-app { background: var(--bg); display: grid; grid-template-columns: auto 1fr; min-height: 100vh; }
        .rail { width:56px; border-right:0.5px solid var(--hairline); padding:14px 0; display:flex; flex-direction:column; align-items:center; justify-content:space-between; background:var(--bg); }
        .rail-top,.rail-bottom { display:flex; flex-direction:column; align-items:center; gap:16px; }
        .rail-icon { width:28px; height:28px; display:inline-flex; align-items:center; justify-content:center; color:#6B6660; border-radius:6px; text-decoration:none; background:transparent; font-size:16px; }
        .rail-icon:hover { color:var(--text); background:var(--surface-2); }
        .rail-add { background:#ECE6FA; color:#5B45C9; border:0.5px solid #C9BCF0; position:relative; }
        .rail-tooltip { position:absolute; left:calc(100% + 10px); top:50%; transform:translateY(-50%); background:#fff; border:0.5px solid var(--border); border-radius:8px; padding:5px 10px; font-size:12px; color:var(--text); white-space:nowrap; box-shadow:var(--shadow-md); opacity:0; pointer-events:none; }
        .rail-add:hover .rail-tooltip { opacity:1; }
        .rail-avatar { width:28px; height:28px; border-radius:8px; background:linear-gradient(160deg, #7C5BE0, #5B3FC9); color:#fff; font-size:12px; font-weight:600; display:inline-flex; align-items:center; justify-content:center; }
        .main { display:flex; flex-direction:column; min-width:0; }
        .topbar { display:grid; grid-template-columns:auto 1fr auto; align-items:center; gap:16px; padding:14px 20px; border-bottom:0.5px solid var(--hairline); background:var(--bg); }
        .brand { display:inline-flex; align-items:center; gap:10px; padding:6px 12px 6px 8px; background:var(--surface); border:0.5px solid var(--border); border-radius:999px; box-shadow:var(--shadow-sm); font-weight:600; font-size:13px; color:var(--text); text-decoration:none; }
        .brand-mark { width:18px; height:18px; border-radius:5px; background:var(--accent-pink); }
        .search { display:flex; align-items:center; gap:10px; height:36px; padding:0 14px; background:var(--surface); border:0.5px solid var(--border); border-radius:999px; box-shadow:var(--shadow-sm); max-width:560px; width:100%; justify-self:start; margin-left:4px; }
        .search-dot { width:6px; height:6px; border-radius:50%; background:#1B1A17; }
        .search input { flex:1; border:0; outline:0; background:transparent; font:inherit; color:var(--text); font-size:13px; }
        .search input::placeholder { color:var(--text-subtle); }
        .search-kbd { font-size:11px; color:var(--text-subtle); border:0.5px solid var(--border); border-radius:6px; padding:2px 7px; line-height:1; }
        .status-pill { display:inline-flex; align-items:center; gap:10px; padding:6px 14px 6px 10px; background:var(--surface); border:0.5px solid var(--border); border-radius:999px; box-shadow:var(--shadow-sm); font-size:12.5px; color:var(--text); text-decoration:none; }
        .bell-wrap { position:relative; width:22px; height:22px; display:inline-flex; align-items:center; justify-content:center; }
        .bell-badge { position:absolute; top:-2px; right:-2px; min-width:14px; height:14px; padding:0 3px; border-radius:999px; background:var(--accent-pink); color:#fff; font-size:9px; font-weight:600; display:inline-flex; align-items:center; justify-content:center; line-height:1; border:1.5px solid var(--surface); }
        .content { flex:1; display:grid; grid-template-columns:140px 1fr; min-height:0; }
        .tile-rail { padding:24px 16px; display:flex; flex-direction:column; gap:24px; align-items:center; }
        .tile { width:92px; display:flex; flex-direction:column; align-items:center; gap:8px; text-decoration:none; color:inherit; }
        .tile-art { width:88px; height:88px; border-radius:18px; display:flex; align-items:center; justify-content:center; color:#fff; box-shadow:inset 0 1px 0 rgba(255,255,255,0.35), inset 0 -10px 24px rgba(0,0,0,0.12), 0 1px 2px rgba(30,24,16,0.08); position:relative; overflow:hidden; font-size:28px; }
        .tile-art::after { content:""; position:absolute; inset:0; background:linear-gradient(160deg, rgba(255,255,255,0.18) 0%, rgba(255,255,255,0) 45%, rgba(0,0,0,0.10) 100%); }
        .tile-art.purple { background:linear-gradient(160deg, var(--tile-purple) 0%, var(--tile-purple-2) 100%); }
        .tile-art.grey { background:linear-gradient(160deg, var(--tile-grey) 0%, var(--tile-grey-2) 100%); }
        .tile-label { font-size:12px; color:var(--text); font-weight:500; text-align:center; }
        .workspace { padding:28px 40px 60px; }
        .website-panel { width:min(1080px, calc(100% - 40px)); margin:0 auto; }
        .hero-card, .stage-card, .stack-card { background:var(--surface); border:0.5px solid var(--border); border-radius:18px; box-shadow:var(--shadow-md); }
        .hero-card { padding:28px 28px 24px; margin-bottom:18px; }
        .hero-eyebrow { display:inline-flex; padding:10px 16px; border-radius:999px; border:0.5px solid var(--border); background:var(--surface-2); font-size:13px; font-weight:600; color:var(--text-muted); margin-bottom:18px; }
        .hero-card h1 { margin:0 0 10px; font-size: clamp(42px, 5vw, 72px); letter-spacing:-0.06em; line-height:0.98; font-weight:650; }
        .hero-card p { margin:0; color:var(--text-muted); font-size:14px; line-height:1.65; max-width:760px; }
        .stage-tabs { display:flex; gap:10px; flex-wrap:wrap; margin:18px 0 0; }
        .stage-tab { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:999px; text-decoration:none; color:var(--text); background:#fff; border:0.5px solid var(--border); font-weight:600; font-size:13px; }
        .stage-tab.active { background:var(--surface-2); }
        .stage-help { margin-top:14px; padding:14px 16px; border-radius:16px; background:#fff; border:0.5px solid var(--border); color:var(--text-muted); font-size:13px; line-height:1.6; }
        .stage-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:16px; margin-top:18px; }
        .stage-card { padding:22px; }
        .stage-card h2 { margin:0 0 12px; font-size:18px; font-weight:600; letter-spacing:-0.01em; }
        .muted { color:var(--text-muted); font-size:13px; line-height:1.55; }
        .stack { display:grid; gap:12px; }
        .stack-card { padding:14px 16px; background:#fff; }
        .stack-card strong { display:block; margin-bottom:6px; }
        .input, .textarea, .select { width:100%; border:0.5px solid var(--border); border-radius:14px; background:#fff; padding:12px 14px; font:inherit; color:var(--text); font-size:14px; }
        .textarea { min-height:120px; resize:vertical; }
        .btn-row { display:flex; gap:12px; flex-wrap:wrap; margin-top:18px; }
        .btn-primary, .btn-secondary { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:12px 18px; border-radius:999px; text-decoration:none; font-size:13px; font-weight:600; cursor:pointer; }
        .btn-primary { background:#111110; color:#fff; border:0; }
        .btn-secondary { background:#fff; color:var(--text); border:0.5px solid var(--border); }
        .build-loading { min-height:340px; display:grid; place-items:center; text-align:center; padding:30px; }
        .build-loading-mark { width:104px; height:104px; border-radius:28px; margin:0 auto 18px; background:linear-gradient(180deg, #ff4f7a 0%, #ff6a5c 100%); box-shadow:0 18px 40px rgba(255,95,108,0.28); position:relative; }
        .build-loading-mark::after { content:""; position:absolute; inset:-10px; border-radius:34px; border:1px solid rgba(255,120,132,0.28); animation:websiteBuildPulse 1.8s ease-out infinite; }
        @keyframes websiteBuildPulse { 0% { transform: scale(0.92); opacity: 0.75; } 70% { transform: scale(1.18); opacity: 0; } 100% { transform: scale(1.18); opacity: 0; } }
        @media (max-width: 980px) { .content { grid-template-columns:1fr; } .tile-rail { flex-direction:row; justify-content:center; padding:20px; } .workspace { padding:20px; } .stage-grid { grid-template-columns:1fr; } .hero-card h1 { font-size:42px; } }
    </style>
@endsection

@section('content')
    <div class="prototype-app">
        <aside class="rail">
            <div class="rail-top">
                <a href="{{ route('dashboard') }}" class="rail-icon" aria-label="Dashboard">▥</a>
                <a href="{{ route('founder.settings') }}" class="rail-icon" aria-label="Settings">⚙</a>
                <a href="{{ route('founder.ai-tools') }}" class="rail-icon rail-add" aria-label="New Agent">＋<span class="rail-tooltip">New Agent</span></a>
            </div>
            <div class="rail-bottom">
                <a href="{{ route('founder.inbox') }}" class="rail-icon" aria-label="Inbox">✉</a>
                <span class="rail-avatar">{{ strtoupper(substr((string) ($founder->full_name ?? 'J'), 0, 1)) }}</span>
            </div>
        </aside>
        <div class="main">
            <div class="topbar">
                <a href="{{ route('dashboard') }}" class="brand"><span class="brand-mark"></span><span>Hatchers AI OS</span></a>
                <div class="search"><span class="search-dot"></span><input type="text" placeholder="What would you like to do?"><span class="search-kbd">⌘K</span></div>
                <a href="{{ route('founder.notifications') }}" class="status-pill"><span class="bell-wrap">🔔 @if(!empty($workspace['unread_notification_count']))<span class="bell-badge">{{ $workspace['unread_notification_count'] }}</span>@endif</span><span>{{ now()->format('D, M j g:i A') }}</span></a>
            </div>
            <div class="content">
                <div class="tile-rail">
                    <a class="tile" href="{{ route('founder.tasks') }}"><div class="tile-art purple">☷</div><div class="tile-label">Tasks</div></a>
                    <a class="tile" href="{{ route('founder.inbox') }}"><div class="tile-art grey">⌂</div><div class="tile-label">Inbox</div></a>
                    <a class="tile" href="{{ route('founder.ai-tools') }}"><div class="tile-art grey">✦</div><div class="tile-label">AI Tools</div></a>
                </div>
                <div class="workspace">
                    <div class="website-panel">
                        <section class="hero-card">
                            <div class="hero-eyebrow">Launch workspace</div>
                            <h1>Your website</h1>
                            <p>Build, review, set up, and publish the first version of {{ $companyName }} without leaving the OS.</p>
                            <div class="stage-tabs">
                                <a class="stage-tab {{ $websiteStage === 'build' ? 'active' : '' }}" href="{{ route('website', array_filter(['stage' => 'build', 'os_embed' => $osEmbedMode ? 1 : null])) }}">1. Build My Website</a>
                                <a class="stage-tab {{ $websiteStage === 'overview' ? 'active' : '' }}" href="{{ route('website', array_filter(['stage' => 'overview', 'os_embed' => $osEmbedMode ? 1 : null])) }}">2. Review Draft</a>
                                <a class="stage-tab {{ $websiteStage === 'setup' ? 'active' : '' }}" href="{{ route('website', array_filter(['stage' => 'setup', 'os_embed' => $osEmbedMode ? 1 : null])) }}">3. Finish Setup</a>
                                <a class="stage-tab {{ $websiteStage === 'publish' ? 'active' : '' }}" href="{{ route('website', array_filter(['stage' => 'publish', 'os_embed' => $osEmbedMode ? 1 : null])) }}">4. Publish</a>
                            </div>
                            <div class="stage-help">{{ $stageHelp[$websiteStage] }}</div>
                        </section>

                        @if (session('success'))
                            <section class="stage-card" style="margin-bottom:16px;"><strong>Success</strong><div class="muted" style="margin-top:8px;">{{ session('success') }}</div></section>
                        @endif
                        @if (session('error'))
                            <section class="stage-card" style="margin-bottom:16px;"><strong>Could not complete that step</strong><div class="muted" style="margin-top:8px;">{{ session('error') }}</div></section>
                        @endif

                        @if ($websiteStage === 'build' && $generationStatus === 'in_progress')
                            <section class="stage-card build-loading">
                                <div>
                                    <div class="build-loading-mark"></div>
                                    <h2 style="margin:0 0 10px;font-size:36px;letter-spacing:-0.04em;">Working on your website</h2>
                                    <p class="muted" style="max-width:560px;">We are preparing your first website draft now. You can close this window and we will notify you as soon as it is ready.</p>
                                </div>
                            </section>
                        @elseif ($websiteStage === 'build')
                            <section class="stage-grid">
                                <div class="stage-card">
                                    <h2>Company intelligence brief</h2>
                                    <div class="stack">
                                        @foreach ($buildSummary as $row)
                                            @if (!empty($row['value']))
                                                <div class="stack-card">
                                                    <strong>{{ $row['label'] }}</strong>
                                                    <div class="muted">{{ $row['value'] }}</div>
                                                </div>
                                            @endif
                                        @endforeach
                                        @if (!empty($buildMissingItems))
                                            <div class="stack-card">
                                                <strong>Helpful details still missing</strong>
                                                @foreach ($buildMissingItems as $missing)
                                                    <div class="muted">• {{ $missing }}</div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="stage-card">
                                    <h2>Build my website</h2>
                                    <form method="POST" action="{{ route('website.build.store') }}" class="stack">
                                        @csrf
                                        @if($osEmbedMode)<input type="hidden" name="os_embed" value="1">@endif
                                        <div>
                                            <strong>What should this website do first?</strong>
                                            <input class="input" type="text" name="website_goal" value="{{ old('website_goal', $buildIntake['website_goal'] ?? '') }}" placeholder="Get customers, book discovery calls, sell a starter offer, collect leads..." style="margin-top:10px;">
                                        </div>
                                        <div>
                                            <strong>Write more about yourself</strong>
                                            <div class="muted" style="margin:6px 0 10px;">Tell us the personal story, credibility, background, or philosophy we should use to make the site feel real and trustworthy.</div>
                                            <textarea class="textarea" name="founder_story_notes">{{ old('founder_story_notes', $buildIntake['founder_story_notes'] ?? '') }}</textarea>
                                        </div>
                                        <div>
                                            <strong>Write more about your services and pricing</strong>
                                            <div class="muted" style="margin:6px 0 10px;">If you know your offers, put them here in any format.</div>
                                            <textarea class="textarea" name="services_pricing_notes">{{ old('services_pricing_notes', $buildIntake['services_pricing_notes'] ?? '') }}</textarea>
                                        </div>
                                        <div>
                                            <strong>Anything we should absolutely include or avoid?</strong>
                                            <div class="muted" style="margin:6px 0 10px;">Use this for brand direction, image mood, must-mention stories, or anything sensitive.</div>
                                            <textarea class="textarea" name="special_requests">{{ old('special_requests', $buildIntake['special_requests'] ?? '') }}</textarea>
                                        </div>
                                        <div class="btn-row">
                                            <button class="btn-primary" type="submit">Build And Publish My Website</button>
                                        </div>
                                    </form>
                                </div>
                            </section>
                        @elseif ($websiteStage === 'overview')
                            <section class="stage-grid">
                                <div class="stage-card">
                                    <h2>Draft overview</h2>
                                    <div class="stack">
                                        <div class="stack-card"><strong>Title</strong><div class="muted">{{ $autopilotDraft['title'] ?? $companyName }}</div></div>
                                        <div class="stack-card"><strong>Status</strong><div class="muted">{{ ucfirst(str_replace('_', ' ', $websiteStatus)) }}</div></div>
                                        <div class="stack-card"><strong>Engine</strong><div class="muted">{{ strtoupper($recommendedEngine) }}</div></div>
                                    </div>
                                </div>
                                <div class="stage-card">
                                    <h2>Regenerate draft</h2>
                                    <div class="muted">If you want Hatchers to create a stronger website draft using the latest company intelligence, rebuild the draft here.</div>
                                    <form method="POST" action="{{ route('website.generate') }}" style="margin-top:18px;">
                                        @csrf
                                        <div class="btn-row"><button class="btn-primary" type="submit">Generate Website Draft</button></div>
                                    </form>
                                </div>
                            </section>
                        @elseif ($websiteStage === 'setup')
                            <section class="stage-grid">
                                <div class="stage-card">
                                    <h2>Public website setup</h2>
                                    <form method="POST" action="{{ route('website.setup') }}" class="stack">
                                        @csrf
                                        <div>
                                            <strong>Website title</strong>
                                            <input class="input" type="text" name="website_title" value="{{ old('website_title', $autopilotDraft['title'] ?? $companyName) }}" style="margin-top:10px;">
                                        </div>
                                        <div>
                                            <strong>Website path</strong>
                                            <input class="input" type="text" name="website_path" value="{{ old('website_path', $websitePath) }}" style="margin-top:10px;">
                                        </div>
                                        <div>
                                            <strong>Custom domain</strong>
                                            <input class="input" type="text" name="custom_domain" value="{{ old('custom_domain', $customDomain) }}" placeholder="yourdomain.com" style="margin-top:10px;">
                                        </div>
                                        <div class="btn-row">
                                            <button class="btn-primary" type="submit">Save Website Setup</button>
                                        </div>
                                    </form>
                                </div>
                                <div class="stage-card">
                                    <h2>Setup status</h2>
                                    <div class="stack">
                                        <div class="stack-card"><strong>Suggested subdomain</strong><div class="muted">{{ $recommendedSubdomain }}</div></div>
                                        <div class="stack-card"><strong>Current path</strong><div class="muted">{{ $websitePath }}</div></div>
                                        <div class="stack-card"><strong>Custom domain status</strong><div class="muted">{{ ucfirst(str_replace('_', ' ', $customDomainStatus ?: 'not connected')) }}</div></div>
                                    </div>
                                </div>
                            </section>
                        @elseif ($websiteStage === 'publish')
                            <section class="stage-grid">
                                <div class="stage-card">
                                    <h2>Ready to publish</h2>
                                    <div class="stack">
                                        <div class="stack-card"><strong>Business model</strong><div class="muted">{{ ucfirst($businessModel) }}</div></div>
                                        <div class="stack-card"><strong>Current URL</strong><div class="muted">{{ $currentWebsiteUrl ?: 'Not published yet' }}</div></div>
                                        <div class="stack-card"><strong>Recommended engine</strong><div class="muted">{{ strtoupper($recommendedEngine) }}</div></div>
                                    </div>
                                </div>
                                <div class="stage-card">
                                    <h2>Publish website</h2>
                                    <div class="muted">When the draft looks right and setup is complete, publish the public site here.</div>
                                    <form method="POST" action="{{ route('website.publish') }}" style="margin-top:18px;">
                                        @csrf
                                        <div class="btn-row">
                                            <button class="btn-primary" type="submit">Publish Website</button>
                                            @if($currentWebsiteUrl)
                                                <a class="btn-secondary" href="{{ $currentWebsiteUrl }}" target="_blank" rel="noopener">Open Current Website</a>
                                            @endif
                                        </div>
                                    </form>
                                </div>
                            </section>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
