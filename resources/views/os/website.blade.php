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
        .website-stage { width:100%; max-width:1240px; margin:0 auto; }
        .website-window { width:min(1120px, calc(100% - 80px)); margin:36px auto 0; background:var(--surface); border:0.5px solid var(--border); border-radius:18px; box-shadow:0 8px 24px rgba(30,24,16,0.08), 0 1px 2px rgba(30,24,16,0.06); overflow:hidden; }
        .website-window-header { display:flex; align-items:center; justify-content:center; position:relative; padding:14px 20px; border-bottom:0.5px solid var(--hairline); background:var(--surface); }
        .website-window-title { font-size:11px; font-weight:600; letter-spacing:.10em; text-transform:uppercase; color:var(--text-muted); }
        .website-window-body { padding:28px 28px 30px; }
        .website-hello { margin:0 0 8px; font-size:44px; line-height:1; letter-spacing:-0.04em; font-weight:650; }
        .website-sub { margin:0 0 18px; font-size:14px; color:var(--text-muted); max-width:640px; }
        .stage-tabs { display:flex; gap:10px; flex-wrap:wrap; margin:0 0 16px; }
        .stage-tab { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:999px; text-decoration:none; color:var(--text); background:#fff; border:0.5px solid var(--border); font-weight:600; font-size:13px; }
        .stage-tab.active { background:#111110; color:#fff; border-color:#111110; }
        .stage-help { margin-bottom:18px; padding:14px 16px; border-radius:16px; background:#fff; border:0.5px solid var(--border); color:var(--text-muted); font-size:13px; line-height:1.6; }
        .stage-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:16px; }
        .stage-card, .stack-card { background:#fff; border:0.5px solid var(--border); border-radius:16px; box-shadow:var(--shadow-sm); }
        .stage-card { padding:22px; }
        .stage-card h2 { margin:0 0 12px; font-size:18px; font-weight:600; letter-spacing:-0.01em; }
        .muted { color:var(--text-muted); font-size:13px; line-height:1.55; }
        .stack { display:grid; gap:12px; }
        .stack-card { padding:14px 16px; }
        .stack-card strong { display:block; margin-bottom:6px; }
        .input, .textarea { width:100%; border:0.5px solid var(--border); border-radius:14px; background:#fff; padding:12px 14px; font:inherit; color:var(--text); font-size:14px; }
        .textarea { min-height:120px; resize:vertical; }
        .btn-row { display:flex; gap:12px; flex-wrap:wrap; margin-top:18px; }
        .btn-primary, .btn-secondary { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:12px 18px; border-radius:999px; text-decoration:none; font-size:13px; font-weight:600; cursor:pointer; }
        .btn-primary { background:#111110; color:#fff; border:0; }
        .btn-secondary { background:#fff; color:var(--text); border:0.5px solid var(--border); }
        .build-loading { min-height:340px; display:grid; place-items:center; text-align:center; padding:30px; }
        .build-loading-mark { width:104px; height:104px; border-radius:28px; margin:0 auto 18px; background:linear-gradient(180deg, #ff4f7a 0%, #ff6a5c 100%); box-shadow:0 18px 40px rgba(255,95,108,0.28); position:relative; }
        .build-loading-mark::after { content:""; position:absolute; inset:-10px; border-radius:34px; border:1px solid rgba(255,120,132,0.28); animation:websiteBuildPulse 1.8s ease-out infinite; }
        @keyframes websiteBuildPulse { 0% { transform: scale(0.92); opacity: 0.75; } 70% { transform: scale(1.18); opacity: 0; } 100% { transform: scale(1.18); opacity: 0; } }
        .notice-card { margin-bottom:16px; }
        @media (max-width: 980px) {
            .stage-grid { grid-template-columns:1fr; }
            .website-hello { font-size:36px; }
            .website-window { width:calc(100% - 20px); }
        }
    </style>
@endsection

@section('content')
    <x-os.prototype-shell :founder="$founder" :workspace="$workspace" active-tile="ai-tools">
        <div class="workspace">
            <div class="website-stage">
                <div class="website-window">
                    <div class="website-window-header">
                        <span class="traffic">
                            <span class="red"></span>
                            <span class="yellow"></span>
                            <span class="green"></span>
                        </span>
                        <span class="website-window-title">WEBSITE</span>
                    </div>

                    <div class="website-window-body">
                        <h1 class="website-hello">Website</h1>
                        <p class="website-sub">Build, review, set up, and publish the first version of {{ $companyName }} without leaving the OS.</p>

                        <div class="stage-tabs">
                            <a class="stage-tab {{ $websiteStage === 'build' ? 'active' : '' }}" href="{{ route('website', array_filter(['stage' => 'build', 'os_embed' => $osEmbedMode ? 1 : null])) }}">1. Build My Website</a>
                            <a class="stage-tab {{ $websiteStage === 'overview' ? 'active' : '' }}" href="{{ route('website', array_filter(['stage' => 'overview', 'os_embed' => $osEmbedMode ? 1 : null])) }}">2. Review Draft</a>
                            <a class="stage-tab {{ $websiteStage === 'setup' ? 'active' : '' }}" href="{{ route('website', array_filter(['stage' => 'setup', 'os_embed' => $osEmbedMode ? 1 : null])) }}">3. Finish Setup</a>
                            <a class="stage-tab {{ $websiteStage === 'publish' ? 'active' : '' }}" href="{{ route('website', array_filter(['stage' => 'publish', 'os_embed' => $osEmbedMode ? 1 : null])) }}">4. Publish</a>
                        </div>

                        <div class="stage-help">{{ $stageHelp[$websiteStage] }}</div>

                        @if (session('success'))
                            <section class="stage-card notice-card"><strong>Success</strong><div class="muted" style="margin-top:8px;">{{ session('success') }}</div></section>
                        @endif
                        @if (session('error'))
                            <section class="stage-card notice-card"><strong>Could not complete that step</strong><div class="muted" style="margin-top:8px;">{{ session('error') }}</div></section>
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
    </x-os.prototype-shell>
@endsection
