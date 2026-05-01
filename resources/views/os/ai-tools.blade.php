@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page { padding: 0; }
        .tools-shell { min-height: 100vh; display:grid; grid-template-columns:220px minmax(0,1fr) 220px; background:#f8f5ee; }
        .tools-sidebar, .tools-rightbar { background: rgba(255,252,247,0.8); border-color: var(--line); border-style: solid; border-width:0 1px 0 0; min-height:100vh; display:flex; flex-direction:column; }
        .tools-rightbar { border-width:0 0 0 1px; background: rgba(255,251,246,0.9); }
        .tools-sidebar-inner, .tools-rightbar-inner { padding:22px 18px; }
        .tools-brand { display:inline-block; margin-bottom:24px; }
        .tools-brand img { width:168px; height:auto; display:block; }
        .tools-nav { display:grid; gap:6px; }
        .tools-nav-item { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:14px; text-decoration:none; color:var(--ink); font-size:0.98rem; }
        .tools-nav-item.active { background:#ece6db; }
        .tools-nav-icon { width:18px; text-align:center; color:var(--muted); }
        .tools-sidebar-footer { margin-top:auto; padding:18px; border-top:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .tools-user { display:flex; align-items:center; gap:10px; }
        .tools-avatar { width:30px; height:30px; border-radius:999px; background:#b0a999; color:#fff; display:grid; place-items:center; font-weight:700; font-size:0.92rem; flex-shrink:0; }
        .tools-main { padding:26px 28px 24px; }
        .tools-main-inner { max-width:760px; margin:0 auto; }
        .tools-main h1 { font-size: clamp(2rem, 3vw, 3rem); letter-spacing:-0.02em; margin-bottom:6px; }
        .tools-main p { color:var(--muted); margin-bottom:24px; }
        .tools-section { margin-bottom:22px; }
        .tools-section h2 { font-size:1.08rem; margin-bottom:12px; }
        .tools-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:12px; }
        .tool-card { background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:18px; padding:18px 18px 16px; box-shadow:0 10px 28px rgba(52,41,26,0.04); }
        .tool-card-title { font-size:1rem; font-weight:700; margin-bottom:6px; }
        .tool-card-copy { color:var(--muted); font-size:0.95rem; line-height:1.45; }
        .tool-card-row { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
        .tool-card-icon { width:34px; height:34px; border-radius:10px; border:1px solid rgba(222,60,109,0.24); color:#e02961; display:grid; place-items:center; flex-shrink:0; }
        .tool-card-cta { display:inline-block; margin-top:14px; padding:10px 14px; border-radius:10px; text-decoration:none; background:linear-gradient(90deg,#8e1c74,#ff2c35); color:white; font-weight:600; }
        .tool-card-secondary { display:inline-block; margin-top:14px; padding:10px 14px; border-radius:10px; text-decoration:none; background:#f0ece4; color:#5d554a; font-weight:600; }
        .tools-highlight { background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:18px; padding:18px 20px; box-shadow:0 10px 28px rgba(52,41,26,0.04); }
        .highlight-label { font-size:0.82rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--rose); margin-bottom:8px; }
        .highlight-copy { color:var(--muted); line-height:1.5; margin-top:4px; }
        .highlight-badge { display:inline-block; margin-top:12px; padding:8px 14px; border-radius:10px; background:#f0ece4; color:#7a7267; font-size:0.95rem; }
        .tools-rightbar h3 { font-size:0.83rem; letter-spacing:0.06em; text-transform:uppercase; color:var(--muted); margin-bottom:12px; }
        .mini-note, .rail-item { background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:14px; padding:12px 14px; }
        .rail-list { display:grid; gap:10px; margin-top:14px; }
        @media (max-width:1240px) { .tools-shell { grid-template-columns:220px 1fr; } .tools-rightbar { display:none; } }
        @media (max-width:900px) { .tools-shell { grid-template-columns:1fr; } .tools-sidebar { min-height:auto; border-right:0; border-bottom:1px solid var(--line); } .tools-sidebar-footer { display:none; } .tools-main { padding:20px 16px 24px; } .tools-grid { grid-template-columns:1fr; } }
    </style>
@endsection

@section('content')
    @php
        $founder = $dashboard['founder'];
        $workspace = $dashboard['workspace'] ?? [];
        $launchCards = $launchCards ?? [];
        $aiTools = $workspace['ai_tools'] ?? [];
        $company = $dashboard['company'] ?? null;
        $primaryGoal = $dashboard['atlas']['primary_growth_goal'] ?? '';
        $recentCampaigns = $dashboard['atlas']['recent_campaigns'] ?? [];
        $atlasWorkspace = $atlasWorkspace ?? [];
        $atlasConversations = $atlasWorkspace['conversations'] ?? [];
        $atlasRecentCampaigns = $atlasWorkspace['recent_campaigns'] ?? $recentCampaigns;
        $atlasArchivedCampaigns = $atlasWorkspace['archived_campaigns'] ?? [];
        $atlasMediaOutputs = $atlasWorkspace['media_outputs'] ?? [];
        $moduleCards = $dashboard['module_cards'] ?? [];
        $logoUrl = !empty($company?->company_logo_path) ? asset('storage/' . ltrim((string) $company->company_logo_path, '/')) : null;
    @endphp

    <div class="tools-shell">
        <aside class="tools-sidebar">
            @include('os.partials.founder-sidebar', [
                'founder' => $founder,
                'businessModel' => $founder->company->business_model ?? 'hybrid',
                'activeKey' => 'ai-tools',
                'navClass' => 'tools-nav',
                'itemClass' => 'tools-nav-item',
                'iconClass' => 'tools-nav-icon',
                'innerClass' => 'tools-sidebar-inner',
                'brandClass' => 'tools-brand',
                'footerClass' => 'tools-sidebar-footer',
                'userClass' => 'tools-user',
                'avatarClass' => 'tools-avatar',
            ])
        </aside>

        <main class="tools-main">
            <div class="tools-main-inner">
                <h1>AI Studio</h1>
                <p>Use one OS workspace for brand direction, campaign work, and AI help without switching products.</p>

                <section class="tools-section">
                    <div class="tools-highlight">
                        <div class="highlight-label">Recommended next move</div>
                        <div style="font-size:1.2rem;font-weight:700;">{{ $primaryGoal !== '' ? $primaryGoal : 'Use AI to move your next founder sprint forward.' }}</div>
                        <div class="highlight-copy">Start here when you want to shape your company story, launch a campaign, review generated work, or ask the OS for help with your next move.</div>
                        <div class="highlight-badge">Ask AI anything about your business...</div>
                    </div>
                </section>

                <section class="tools-section">
                    <h2>Core Studios</h2>
                    <div class="tools-grid">
                        <div class="tool-card">
                            <div class="tool-card-row">
                                <div>
                                    <div class="tool-card-title">Brand Studio</div>
                                    <div class="tool-card-copy">Upload your logo, sharpen your ICP, define your brand voice, and keep your company intelligence in one place.</div>
                                </div>
                                <div class="tool-card-icon">◌</div>
                            </div>
                            @if ($logoUrl)
                                <div style="margin-top:12px;">
                                    <img src="{{ $logoUrl }}" alt="{{ $company?->company_name ?: 'Company logo' }}" style="width:88px;height:auto;border-radius:14px;border:1px solid var(--line);display:block;">
                                </div>
                            @endif
                            <a class="tool-card-cta" href="{{ route('founder.settings') }}">Open Brand Studio</a>
                        </div>
                        <div class="tool-card">
                            <div class="tool-card-row">
                                <div>
                                    <div class="tool-card-title">Campaign Studio</div>
                                    <div class="tool-card-copy">Browse the real Atlas campaign list, open campaign detail, and continue campaign work without leaving the OS shell.</div>
                                </div>
                                <div class="tool-card-icon">✦</div>
                            </div>
                            <a class="tool-card-cta" href="{{ route('founder.marketing') }}">Open Campaign Studio</a>
                        </div>
                        <div class="tool-card">
                            <div class="tool-card-row">
                                <div>
                                    <div class="tool-card-title">AI Agents</div>
                                    <div class="tool-card-copy">See real Atlas conversation threads, reopen any thread, and continue agent work inside Hatchers AI OS.</div>
                                </div>
                                <div class="tool-card-icon">◇</div>
                            </div>
                            <a class="tool-card-secondary" href="{{ route('founder.ai-tools.open', ['target' => '/ai-chat', 'title' => 'Atlas Agents']) }}">Open Atlas Agents</a>
                        </div>
                        <div class="tool-card">
                            <div class="tool-card-row">
                                <div>
                                    <div class="tool-card-title">Media Library</div>
                                    <div class="tool-card-copy">Review generated Atlas media outputs and documents alongside OS-managed assets.</div>
                                </div>
                                <div class="tool-card-icon">▥</div>
                            </div>
                            <a class="tool-card-secondary" href="{{ route('founder.media-library') }}">Open Media Library</a>
                        </div>
                    </div>
                </section>

                <section class="tools-section">
                    <h2>Atlas Conversations</h2>
                    <div class="tools-grid">
                        @forelse (array_slice($atlasConversations, 0, 6) as $conversation)
                            @php
                                $conversationId = (string) ($conversation['id'] ?? '');
                                $chatTarget = '/ai-chat';
                                if ($conversationId !== '' && $conversationId !== 'default') {
                                    $chatTarget = '/ai-chat';
                                }
                            @endphp
                            <div class="tool-card">
                                <div class="tool-card-title">{{ $conversation['title'] ?? 'Conversation' }}</div>
                                <div class="tool-card-copy">{{ $conversation['last_message'] ?? 'Continue this Atlas thread from inside the OS.' }}</div>
                                <div class="highlight-badge">{{ !empty($conversation['updated_at']) ? \Illuminate\Support\Carbon::parse($conversation['updated_at'])->diffForHumans() : 'Updated recently' }}</div>
                                <a class="tool-card-cta" href="{{ route('founder.ai-tools.open', ['target' => $chatTarget, 'title' => 'Atlas Agents']) }}">Continue conversation</a>
                            </div>
                        @empty
                            <div class="tool-card">
                                <div class="tool-card-title">No Atlas conversations yet</div>
                                <div class="tool-card-copy">Start your first agent thread and it will appear here for quick continuation inside the OS.</div>
                                <a class="tool-card-cta" href="{{ route('founder.ai-tools.open', ['target' => '/ai-chat', 'title' => 'Atlas Agents']) }}">Start Atlas chat</a>
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="tools-section">
                    <h2>Atlas Campaigns</h2>
                    <div class="tools-grid">
                        @forelse (array_slice($atlasRecentCampaigns, 0, 6) as $campaign)
                            @php
                                $targetPath = (string) ($campaign['target_path'] ?? '/ai-images/campaign');
                            @endphp
                            <div class="tool-card">
                                <div class="tool-card-title">{{ $campaign['title'] ?? 'Campaign' }}</div>
                                <div class="tool-card-copy">{{ $campaign['description'] ?? 'Open this campaign in Atlas Campaign Studio.' }}</div>
                                <div class="highlight-badge">{{ (int) ($campaign['generated_posts_count'] ?? 0) }} linked posts</div>
                                <a class="tool-card-cta" href="{{ route('founder.ai-tools.open', ['target' => $targetPath, 'title' => 'Campaign Studio']) }}">Open campaign</a>
                            </div>
                        @empty
                            <div class="tool-card">
                                <div class="tool-card-title">No campaigns yet</div>
                                <div class="tool-card-copy">Create your first Atlas campaign and it will show up here with a direct OS launch back into the real workspace.</div>
                                <a class="tool-card-cta" href="{{ route('founder.ai-tools.open', ['target' => '/ai-images/campaign', 'title' => 'Campaign Studio']) }}">Open Campaign Studio</a>
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="tools-section">
                    <h2>Atlas Media Outputs</h2>
                    <div class="tools-grid">
                        @forelse (array_slice($atlasMediaOutputs, 0, 4) as $asset)
                            <div class="tool-card">
                                <div class="tool-card-row">
                                    <div>
                                        <div class="tool-card-title">{{ $asset['title'] ?? 'Media output' }}</div>
                                        <div class="tool-card-copy">{{ ucfirst($asset['post_type'] ?? 'post') }}{{ !empty($asset['campaign_title']) ? ' for ' . $asset['campaign_title'] : '' }}</div>
                                    </div>
                                    <div class="tool-card-icon">▥</div>
                                </div>
                                @if (!empty($asset['preview_image']))
                                    <div style="margin-top:12px;">
                                        <img src="{{ $asset['preview_image'] }}" alt="{{ $asset['title'] ?? 'Media output' }}" style="width:100%;max-height:180px;object-fit:cover;border-radius:14px;border:1px solid var(--line);display:block;">
                                    </div>
                                @endif
                                <a class="tool-card-secondary" href="{{ route('founder.media-library') }}">Review in Media Library</a>
                            </div>
                        @empty
                            <div class="tool-card">
                                <div class="tool-card-title">No generated media yet</div>
                                <div class="tool-card-copy">Atlas image and campaign outputs will appear here as soon as you generate them.</div>
                                <a class="tool-card-secondary" href="{{ route('founder.ai-tools.open', ['target' => '/all-images', 'title' => 'Atlas Media']) }}">Open Atlas media</a>
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="tools-section">
                    <h2>Platform Workspaces</h2>
                    <div class="tools-grid">
                        <div class="tool-card">
                            <div class="tool-card-title">Unified Search</div>
                            <div class="tool-card-copy">Search tasks, lessons, campaigns, offers, and activity from one OS surface.</div>
                            <a class="tool-card-cta" href="{{ route('founder.search') }}">Open Search</a>
                        </div>
                        <div class="tool-card">
                            <div class="tool-card-title">Analytics</div>
                            <div class="tool-card-copy">Review execution, growth, and marketing performance in one reporting workspace.</div>
                            <a class="tool-card-cta" href="{{ route('founder.analytics') }}">Open Analytics</a>
                        </div>
                        <div class="tool-card">
                            <div class="tool-card-title">Media Library</div>
                            <div class="tool-card-copy">Keep campaign drafts, content assets, and offer copy together inside the OS.</div>
                            <a class="tool-card-cta" href="{{ route('founder.media-library') }}">Open Media Library</a>
                        </div>
                        <div class="tool-card">
                            <div class="tool-card-title">Automations</div>
                            <div class="tool-card-copy">Define saved cross-tool rules so the OS can become the long-term workflow layer.</div>
                            <a class="tool-card-cta" href="{{ route('founder.automations') }}">Open Automations</a>
                        </div>
                    </div>
                </section>

                <section class="tools-section">
                    <h2>OS Modules</h2>
                    <div class="tools-grid">
                        @foreach ($launchCards as $launch)
                            <div class="tool-card">
                                <div class="tool-card-row">
                                    <div>
                                        <div class="tool-card-title">{{ $launch['label'] }}</div>
                                        <div class="tool-card-copy">{{ $launch['description'] }}</div>
                                    </div>
                                    <div class="tool-card-icon">{{ strtoupper(substr($launch['module'], 0, 1)) }}</div>
                                </div>
                                <a class="tool-card-cta" href="/dashboard/founder">Open in OS</a>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="tools-section">
                    <h2>Readiness Snapshot</h2>
                    <div class="tools-grid">
                        @foreach (array_slice($moduleCards, 0, 4) as $module)
                            <div class="tool-card">
                                <div class="tool-card-title">{{ $module['module'] }}</div>
                                <div class="tool-card-copy">{{ $module['description'] }}</div>
                                <div class="highlight-badge">{{ $module['readiness_score'] }}% readiness</div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </main>

        <aside class="tools-rightbar">
            <div class="tools-rightbar-inner">
                <h3>Recent Campaigns</h3>
                <div class="rail-list">
                    @forelse (array_slice($atlasRecentCampaigns, 0, 3) as $campaign)
                        <div class="rail-item">
                            <div style="font-weight:600;">{{ $campaign['title'] ?? 'Campaign' }}</div>
                            <div style="margin-top:4px;color:var(--muted);">{{ $campaign['description'] ?? 'Saved in Campaign Studio.' }}</div>
                        </div>
                    @empty
                        <div class="rail-item">
                            <div style="font-weight:600;">No campaigns yet</div>
                            <div style="margin-top:4px;color:var(--muted);">Your campaign work will appear here as you start using Campaign Studio.</div>
                        </div>
                    @endforelse
                </div>

                <h3 style="margin-top:22px;">Archived Campaigns</h3>
                <div class="rail-list">
                    @forelse (array_slice($atlasArchivedCampaigns, 0, 2) as $campaign)
                        <div class="rail-item">
                            <div style="font-weight:600;">{{ $campaign['title'] ?? 'Campaign' }}</div>
                            <div style="margin-top:4px;color:var(--muted);">{{ (int) ($campaign['generated_posts_count'] ?? 0) }} linked posts</div>
                        </div>
                    @empty
                        <div class="rail-item">
                            <div style="font-weight:600;">No archived campaigns</div>
                            <div style="margin-top:4px;color:var(--muted);">Archived Atlas campaigns will surface here when you start cycling through campaigns.</div>
                        </div>
                    @endforelse
                </div>

                <h3 style="margin-top:22px;">Use This Page For</h3>
                <div class="mini-note">Start here when you need brand context, Atlas campaign history, chat continuation, or generated media. This page is now the OS-native launch surface for real Atlas work.</div>
            </div>
        </aside>
    </div>
@endsection
