@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page { padding: 0; }
        .workspace-shell { min-height:100vh; display:grid; grid-template-columns:220px minmax(0,1fr) 240px; background:#f8f5ee; }
        .workspace-sidebar, .workspace-rightbar { background:rgba(255,252,247,0.82); border-color:var(--line); border-style:solid; border-width:0 1px 0 0; min-height:100vh; display:flex; flex-direction:column; }
        .workspace-rightbar { border-width:0 0 0 1px; background:rgba(255,251,246,0.92); }
        .workspace-sidebar-inner, .workspace-rightbar-inner { padding:22px 18px; }
        .workspace-brand { display:inline-block; margin-bottom:24px; }
        .workspace-brand img { width:168px; height:auto; display:block; }
        .workspace-nav { display:grid; gap:6px; }
        .workspace-nav-item { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:14px; text-decoration:none; color:var(--ink); font-size:0.98rem; }
        .workspace-nav-item.active { background:#ece6db; }
        .workspace-nav-icon { width:18px; text-align:center; color:var(--muted); }
        .workspace-sidebar-footer { margin-top:auto; padding:18px; border-top:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .workspace-user { display:flex; align-items:center; gap:10px; }
        .workspace-avatar { width:30px; height:30px; border-radius:999px; background:#b0a999; color:#fff; display:grid; place-items:center; font-weight:700; font-size:0.92rem; flex-shrink:0; }
        .workspace-main { padding:26px 28px 28px; }
        .workspace-main-inner { max-width:900px; margin:0 auto; }
        .workspace-rightbar h3 { font-size:0.83rem; letter-spacing:0.06em; text-transform:uppercase; color:var(--muted); margin-bottom:12px; }
        .workspace-rail-list { display:grid; gap:10px; }
        .workspace-rail-item { background:rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:14px; padding:12px 14px; }
        @media (max-width:1240px) { .workspace-shell { grid-template-columns:220px 1fr; } .workspace-rightbar { display:none; } }
        @media (max-width:900px) { .workspace-shell { grid-template-columns:1fr; } .workspace-sidebar { min-height:auto; border-right:0; border-bottom:1px solid var(--line); } .workspace-sidebar-footer { display:none; } .workspace-main { padding:20px 16px 24px; } }
    </style>
@endsection

@section('content')
    @php
        $founder = auth()->user();
        $pods = $workspace['pods'];
        $metrics = $workspace['metrics'];
    @endphp

    <div class="workspace-shell">
        <aside class="workspace-sidebar">
            @include('os.partials.founder-sidebar', [
                'founder' => $founder,
                'businessModel' => $founder?->company?->business_model ?? 'hybrid',
                'activeKey' => 'pods',
                'navClass' => 'workspace-nav',
                'itemClass' => 'workspace-nav-item',
                'iconClass' => 'workspace-nav-icon',
                'innerClass' => 'workspace-sidebar-inner',
                'brandClass' => 'workspace-brand',
                'footerClass' => 'workspace-sidebar-footer',
                'userClass' => 'workspace-user',
                'avatarClass' => 'workspace-avatar',
            ])
        </aside>

        <main class="workspace-main">
            <div class="workspace-main-inner">
            <section class="hero">
                <div class="eyebrow">Micro-Community Pods</div>
                <h1>Founders in the same vertical should not learn alone.</h1>
                <p class="muted">Pods group founders by blueprint and stage so they can share what worked, what is blocked, and what to try next without leaving the OS.</p>
            </section>

            <section class="card" style="margin-top:22px;">
                <h2>When to use this</h2>
                <p class="muted" style="margin-top:8px;">Pods become most useful after you already have a website draft, an active First 100 pipeline, and real outreach in motion. Use them to sharpen what is already happening, not to replace your daily execution plan.</p>
            </section>

            @if (session('success'))
                <section class="card" style="margin-top:22px;border-color: rgba(44, 122, 87, 0.25); background: rgba(44, 122, 87, 0.06);">
                    <p class="muted">{{ session('success') }}</p>
                </section>
            @endif

            @if (session('error'))
                <section class="card" style="margin-top:22px;border-color: rgba(179, 34, 83, 0.25); background: rgba(179, 34, 83, 0.06);">
                    <p class="muted">{{ session('error') }}</p>
                </section>
            @endif

            <section class="metrics" style="margin-top:22px;">
                <div class="card metric"><div class="muted">Available pods</div><strong>{{ $metrics['available_pods'] }}</strong></div>
                <div class="card metric"><div class="muted">Joined pods</div><strong>{{ $metrics['joined_pods'] }}</strong></div>
                <div class="card metric"><div class="muted">Shared wins</div><strong>{{ $metrics['shared_wins'] }}</strong></div>
                <div class="card metric"><div class="muted">Shared blockers</div><strong>{{ $metrics['shared_blockers'] }}</strong></div>
            </section>

            <section class="stack" style="margin-top:22px;">
                @forelse ($pods as $pod)
                    <section class="card">
                        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
                            <div>
                                <h2>{{ $pod['name'] }}</h2>
                                <p class="muted">{{ $pod['description'] }}</p>
                            </div>
                            <div class="pill">{{ $pod['joined'] ? 'Joined' : 'Recommended' }}</div>
                        </div>
                        <div class="muted" style="margin-top:8px;">Stage {{ $pod['stage'] ?: 'any' }} · {{ $pod['member_count'] }} members · {{ $pod['wins_count'] }} wins · {{ $pod['blockers_count'] }} blockers</div>
                        @if (!empty($pod['benchmark']))
                            <div class="muted" style="margin-top:8px;">Benchmarks: {{ collect($pod['benchmark'])->map(fn ($value, $key) => str_replace('_', ' ', $key) . ' ' . $value)->implode(' · ') }}</div>
                        @endif

                        <div class="stack" style="margin-top:14px;">
                            <div class="stack-item">
                                <strong>Members</strong><br>
                                {{ collect($pod['members'])->map(fn ($member) => $member['name'] . ($member['company_name'] ? ' · ' . $member['company_name'] : ''))->implode(' | ') ?: 'No members yet' }}
                            </div>
                            <div class="stack-item">
                                <strong>Recent posts</strong>
                                @forelse ($pod['posts'] as $post)
                                    <div class="muted" style="margin-top:8px;">{{ ucfirst($post['type']) }} · {{ $post['title'] }} · {{ $post['founder_name'] }} · {{ $post['created_at'] }}</div>
                                    <div class="muted">{{ $post['body'] }}</div>
                                @empty
                                    <div class="muted" style="margin-top:8px;">No one has posted in this pod yet.</div>
                                @endforelse
                            </div>
                        </div>

                        <div class="cta-row" style="margin-top:14px;flex-wrap:wrap;">
                            @if (!$pod['joined'])
                                <form method="POST" action="{{ route('founder.pods.join', $pod['id']) }}">
                                    @csrf
                                    <button class="btn primary" type="submit">Join Pod</button>
                                </form>
                            @endif
                        </div>

                        @if ($pod['joined'])
                            <form method="POST" action="{{ route('founder.pods.posts.store', $pod['id']) }}" class="grid-2" style="margin-top:14px;">
                                @csrf
                                <label>
                                    <span class="muted">Post type</span>
                                    <select name="post_type" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;">
                                        <option value="win">Win</option>
                                        <option value="blocker">Blocker</option>
                                        <option value="prompt">Prompt</option>
                                    </select>
                                </label>
                                <label>
                                    <span class="muted">Title</span>
                                    <input type="text" name="title" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;" placeholder="What happened?">
                                </label>
                                <label style="grid-column:1 / -1;">
                                    <span class="muted">Share the details</span>
                                    <textarea name="body" rows="4" style="width:100%;margin-top:6px;border:1px solid var(--line);background:#fff;border-radius:12px;padding:10px 12px;"></textarea>
                                </label>
                                <div style="grid-column:1 / -1;">
                                    <button class="btn" type="submit">Post To Pod</button>
                                </div>
                            </form>
                        @endif
                    </section>
                @empty
                    <section class="card">
                        <h2>No pods yet</h2>
                        <p class="muted">Pods appear once a blueprint has an active peer group.</p>
                    </section>
                @endforelse
            </section>
            </div>
        </main>

        <aside class="workspace-rightbar">
            <div class="workspace-rightbar-inner">
                <h3>Pod Snapshot</h3>
                <div class="workspace-rail-list">
                    <div class="workspace-rail-item"><strong>Available pods</strong><br><span class="muted">{{ $metrics['available_pods'] }}</span></div>
                    <div class="workspace-rail-item"><strong>Joined pods</strong><br><span class="muted">{{ $metrics['joined_pods'] }}</span></div>
                    <div class="workspace-rail-item"><strong>Shared wins</strong><br><span class="muted">{{ $metrics['shared_wins'] }}</span></div>
                    <div class="workspace-rail-item"><strong>Shared blockers</strong><br><span class="muted">{{ $metrics['shared_blockers'] }}</span></div>
                </div>
                <h3 style="margin-top:22px;">How To Use Pods</h3>
                <div class="workspace-rail-list">
                    <div class="workspace-rail-item"><strong>Join your vertical</strong><br><span class="muted">Stay around founders selling to the same kind of customer.</span></div>
                    <div class="workspace-rail-item"><strong>Post wins and blockers</strong><br><span class="muted">Use pods as a lightweight execution loop, not a passive feed.</span></div>
                    <div class="workspace-rail-item"><strong>Bring lessons back</strong><br><span class="muted">Use what works in pods to improve First 100, Website, and Commerce.</span></div>
                </div>
            </div>
        </aside>
    </div>
@endsection
