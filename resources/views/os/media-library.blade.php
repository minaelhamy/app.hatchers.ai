@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page { padding: 0; }
        .media-shell { min-height: 100vh; display:grid; grid-template-columns:220px minmax(0,1fr) 220px; background:#f8f5ee; }
        .media-sidebar, .media-rightbar { background: rgba(255,252,247,0.8); border-color: var(--line); border-style: solid; border-width:0 1px 0 0; min-height:100vh; display:flex; flex-direction:column; }
        .media-rightbar { border-width:0 0 0 1px; background: rgba(255,251,246,0.9); }
        .media-sidebar-inner, .media-rightbar-inner { padding:22px 18px; }
        .media-brand { display:inline-block; margin-bottom:24px; }
        .media-brand img { width:168px; height:auto; display:block; }
        .media-nav { display:grid; gap:6px; }
        .media-nav-item { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:14px; text-decoration:none; color:var(--ink); font-size:0.98rem; }
        .media-nav-item.active { background:#ece6db; }
        .media-nav-icon { width:18px; text-align:center; color:var(--muted); }
        .media-sidebar-footer { margin-top:auto; padding:18px; border-top:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .media-user { display:flex; align-items:center; gap:10px; }
        .media-avatar { width:30px; height:30px; border-radius:999px; background:#b0a999; color:#fff; display:grid; place-items:center; font-weight:700; font-size:0.92rem; flex-shrink:0; }
        .media-main { padding:26px 28px 24px; }
        .media-main-inner { max-width:780px; margin:0 auto; }
        .media-main h1 { font-size: clamp(2rem, 3vw, 3rem); letter-spacing:-0.02em; margin-bottom:6px; }
        .media-main p { color:var(--muted); margin-bottom:24px; }
        .media-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
        .media-card, .rail-item, .mini-note { background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:18px; padding:18px; box-shadow:0 10px 28px rgba(52,41,26,0.04); }
        .media-rightbar h3 { font-size:0.83rem; letter-spacing:0.06em; text-transform:uppercase; color:var(--muted); margin-bottom:12px; }
        .rail-list { display:grid; gap:10px; margin-top:14px; }
        @media (max-width:1240px) { .media-shell { grid-template-columns:220px 1fr; } .media-rightbar { display:none; } }
        @media (max-width:900px) { .media-shell { grid-template-columns:1fr; } .media-sidebar { min-height:auto; border-right:0; border-bottom:1px solid var(--line); } .media-sidebar-footer { display:none; } .media-main { padding:20px 16px 24px; } .media-grid { grid-template-columns:1fr; } }
    </style>
@endsection

@section('content')
    @php
        $founder = $dashboard['founder'];
        $allThemes = collect($website['theme_options'] ?? [])->flatten(1)->values()->all();
        $atlasWorkspace = $atlasWorkspace ?? [];
        $atlasMediaOutputs = $atlasWorkspace['media_outputs'] ?? [];
        $atlasDocuments = $atlasWorkspace['documents'] ?? [];
    @endphp

    <div class="media-shell">
        <aside class="media-sidebar">
            @include('os.partials.founder-sidebar', [
                'founder' => $founder,
                'businessModel' => $founder->company->business_model ?? 'hybrid',
                'activeKey' => 'media-library',
                'navClass' => 'media-nav',
                'itemClass' => 'media-nav-item',
                'iconClass' => 'media-nav-icon',
                'innerClass' => 'media-sidebar-inner',
                'brandClass' => 'media-brand',
                'footerClass' => 'media-sidebar-footer',
                'userClass' => 'media-user',
                'avatarClass' => 'media-avatar',
            ])
        </aside>

        <main class="media-main">
            <div class="media-main-inner">
                @include('os.partials.guidebook-workspace-topbar', [
                    'founder' => $founder,
                    'company' => $founder->company,
                    'workspace' => $dashboard['workspace'] ?? [],
                    'projectName' => $founder->company->company_name ?? 'Founder workspace',
                    'sectionLabel' => 'Media Library',
                    'searchPlaceholder' => 'Find images, campaign outputs, documents, and website visuals in one place...',
                ])
                <h1>Media Library</h1>
                <p>Review Atlas-generated images, campaign outputs, documents, and website visuals from one OS-native library.</p>

                <section class="media-grid">
                    <div class="media-card">
                        <h2>Atlas Media Outputs</h2>
                        <div class="stack" style="margin-top:14px;">
                            @forelse ($atlasMediaOutputs as $asset)
                                <div class="stack-item">
                                    <div class="pill">{{ ucfirst($asset['post_type'] ?? 'post') }}</div>
                                    <strong style="display:block;margin-top:10px;">{{ $asset['title'] ?? 'Media output' }}</strong>
                                    @if (!empty($asset['preview_image']))
                                        <div style="margin-top:10px;">
                                            <img src="{{ $asset['preview_image'] }}" alt="{{ $asset['title'] ?? 'Media output' }}" style="width:100%;max-height:180px;object-fit:cover;border-radius:14px;border:1px solid var(--line);display:block;">
                                        </div>
                                    @endif
                                    <div class="muted" style="margin-top:6px;">{{ !empty($asset['campaign_title']) ? 'Campaign: ' . $asset['campaign_title'] : 'Saved from Atlas media generation.' }}</div>
                                    <a href="{{ route('founder.ai-tools.open', ['target' => $asset['target_path'] ?? '/all-images', 'title' => 'Atlas Media']) }}" style="display:inline-block;margin-top:10px;">Open in Atlas</a>
                                </div>
                            @empty
                                <div class="stack-item">
                                    <strong>No Atlas media yet</strong><br>
                                    <span class="muted">As you generate images and campaign outputs in Atlas, they will appear here inside the OS.</span>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="media-card">
                        <h2>Atlas Documents</h2>
                        <div class="stack" style="margin-top:14px;">
                            @forelse ($atlasDocuments as $document)
                                <div class="stack-item">
                                    <strong>{{ $document['title'] ?? 'Document' }}</strong><br>
                                    <span class="muted">{{ $document['content'] ?? 'Saved Atlas document.' }}</span>
                                    <div class="muted" style="margin-top:6px;">Template: {{ $document['template'] ?? 'Atlas' }}</div>
                                    <a href="{{ route('founder.ai-tools.open', ['target' => $document['target_path'] ?? '/all-documents', 'title' => 'Atlas Documents']) }}" style="display:inline-block;margin-top:10px;">Open document</a>
                                </div>
                            @empty
                                <div class="stack-item">
                                    <strong>No Atlas documents yet</strong><br>
                                    <span class="muted">Atlas documents, drafts, and generated writing outputs will appear here as you create them.</span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section class="media-grid" style="margin-top:12px;">
                    <div class="media-card">
                        <h2>OS Asset Feed</h2>
                        <div class="stack" style="margin-top:14px;">
                            @forelse ($assets as $asset)
                                <div class="stack-item">
                                    <div class="pill">{{ $asset['type'] }}</div>
                                    <strong style="display:block;margin-top:10px;">{{ $asset['title'] }}</strong>
                                    <div class="muted" style="margin-top:6px;">{{ $asset['description'] }}</div>
                                    <div class="muted" style="margin-top:6px;">Source: {{ $asset['source'] }}</div>
                                </div>
                            @empty
                                <div class="stack-item">
                                    <strong>No OS assets yet</strong><br>
                                    <span class="muted">As you generate campaigns, drafts, and offers in OS, they will also appear here.</span>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="media-card">
                        <h2>Website Visuals</h2>
                        <div class="stack" style="margin-top:14px;">
                            @forelse ($allThemes as $theme)
                                <div class="stack-item">
                                    <strong>{{ $theme['label'] ?? ('Theme ' . ($theme['id'] ?? '')) }}</strong><br>
                                    <span class="muted">{{ !empty($theme['preview_url']) ? 'Preview image available for this website style.' : 'This website style is available inside the website workspace.' }}</span>
                                </div>
                            @empty
                                <div class="stack-item">
                                    <strong>No website visuals yet</strong><br>
                                    <span class="muted">Website visuals will appear here as your site draft and campaign media keep growing inside the OS.</span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </section>
            </div>
        </main>

        <aside class="media-rightbar">
            <div class="media-rightbar-inner">
                <h3>OS Direction</h3>
                <div class="rail-list">
                    <div class="mini-note">This library now mixes real Atlas outputs with OS-managed assets so founders can review generated images, campaign files, documents, and website visuals in one place.</div>
                </div>
            </div>
        </aside>
    </div>
@endsection
