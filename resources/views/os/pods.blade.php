@extends('os.layout')

@section('content')
    @php
        $pods = $workspace['pods'];
        $metrics = $workspace['metrics'];
    @endphp

    <div class="sidebar-layout">
        <aside class="sidebar-card">
            <div class="pill">Founder Workspace</div>
            <div class="nav-group" style="margin-top: 18px;">
                <div class="nav-group-title">Core</div>
                <a class="nav-item" href="/dashboard/founder">Home</a>
                <a class="nav-item" href="{{ route('founder.tasks') }}">Tasks</a>
                <a class="nav-item" href="{{ route('founder.first-100') }}">First 100</a>
                <a class="nav-item" href="{{ route('founder.marketing') }}">Marketing</a>
            </div>
            <div class="nav-group">
                <div class="nav-group-title">Build</div>
                <a class="nav-item" href="{{ route('website') }}">Website</a>
                <a class="nav-item" href="{{ route('founder.commerce') }}">Commerce</a>
                <a class="nav-item" href="{{ route('founder.commerce.wallet') }}">Wallet</a>
            </div>
            <div class="nav-group">
                <div class="nav-group-title">More</div>
                <a class="nav-item" href="{{ route('founder.activity') }}">Activity</a>
                <a class="nav-item" href="{{ route('founder.ai-tools') }}">AI Tools</a>
                <a class="nav-item active" href="{{ route('founder.pods') }}">Pods</a>
                <a class="nav-item" href="{{ route('founder.learning-plan') }}">Learning Plan</a>
                <a class="nav-item" href="{{ route('founder.settings') }}">Settings</a>
            </div>
        </aside>

        <div>
            <section class="hero">
                <div class="eyebrow">Micro-Community Pods</div>
                <h1>Founders in the same vertical should not learn alone.</h1>
                <p class="muted">Pods group founders by blueprint and stage so they can share what worked, what is blocked, and what to try next without leaving the OS.</p>
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
    </div>
@endsection
