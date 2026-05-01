@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page { padding: 0; }
        .marketing-shell { min-height: 100vh; display:grid; grid-template-columns:220px minmax(0,1fr) 220px; background:#f8f5ee; }
        .marketing-sidebar, .marketing-rightbar { background: rgba(255,252,247,0.8); border-color: var(--line); border-style: solid; border-width:0 1px 0 0; min-height:100vh; display:flex; flex-direction:column; }
        .marketing-rightbar { border-width:0 0 0 1px; background: rgba(255,251,246,0.9); }
        .marketing-sidebar-inner, .marketing-rightbar-inner { padding:22px 18px; }
        .marketing-brand { display:inline-block; margin-bottom:24px; }
        .marketing-brand img { width:168px; height:auto; display:block; }
        .marketing-nav { display:grid; gap:6px; }
        .marketing-nav-item { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:14px; text-decoration:none; color:var(--ink); font-size:0.98rem; }
        .marketing-nav-item.active { background:#ece6db; }
        .marketing-nav-icon { width:18px; text-align:center; color:var(--muted); }
        .marketing-sidebar-footer { margin-top:auto; padding:18px; border-top:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .marketing-user { display:flex; align-items:center; gap:10px; }
        .marketing-avatar { width:30px; height:30px; border-radius:999px; background:#b0a999; color:#fff; display:grid; place-items:center; font-weight:700; font-size:0.92rem; flex-shrink:0; }
        .marketing-main { padding:26px 28px 24px; }
        .marketing-main-inner { max-width:760px; margin:0 auto; }
        .marketing-main h1 { font-size: clamp(2rem, 3vw, 3rem); letter-spacing:-0.02em; margin-bottom:6px; }
        .marketing-main p { color:var(--muted); margin-bottom:24px; }
        .marketing-metrics { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:12px; margin-bottom:22px; }
        .marketing-metric { background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:18px; padding:16px 18px; box-shadow:0 10px 28px rgba(52,41,26,0.04); }
        .marketing-metric strong { display:block; font-size:1.55rem; margin-top:6px; }
        .marketing-section { margin-bottom:22px; }
        .marketing-section h2 { font-size:1.08rem; margin-bottom:12px; }
        .marketing-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:12px; }
        .marketing-card { background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:18px; padding:18px 18px 16px; box-shadow:0 10px 28px rgba(52,41,26,0.04); }
        .marketing-banner { border-radius:16px; padding:14px 16px; border:1px solid rgba(220,207,191,0.8); background: rgba(255,255,255,0.9); margin-bottom:14px; }
        .marketing-banner.success { border-color: rgba(44,122,87,0.26); background: rgba(226,245,236,0.9); }
        .marketing-banner.error { border-color: rgba(179,34,83,0.22); background: rgba(255,241,246,0.92); }
        .marketing-card-title { font-size:1rem; font-weight:700; margin-bottom:6px; }
        .marketing-card-copy { color:var(--muted); font-size:0.95rem; line-height:1.45; }
        .marketing-card-meta { font-size:0.82rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--rose); margin-bottom:8px; }
        .marketing-chip { display:inline-block; margin-top:12px; padding:8px 14px; border-radius:10px; background:#f0ece4; color:#7a7267; font-size:0.92rem; }
        .marketing-cta { display:inline-block; margin-top:14px; padding:10px 14px; border-radius:10px; text-decoration:none; background:linear-gradient(90deg,#8e1c74,#ff2c35); color:white; font-weight:600; }
        .marketing-secondary { display:inline-block; margin-top:14px; padding:10px 14px; border-radius:10px; text-decoration:none; background:#f0ece4; color:#5d554a; font-weight:600; }
        .marketing-inline-form { display:grid; gap:12px; }
        .marketing-field { display:grid; gap:8px; }
        .marketing-field label { font-size:0.92rem; font-weight:600; }
        .marketing-field input, .marketing-field textarea, .marketing-select { width:100%; border:1px solid rgba(220,207,191,0.9); background:#fff; border-radius:12px; padding:12px 14px; font:inherit; color:var(--ink); }
        .marketing-field textarea { min-height:120px; resize:vertical; }
        .marketing-field .field-error { color:var(--rose); font-size:0.85rem; }
        .marketing-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:14px; }
        .marketing-button { border:0; cursor:pointer; font:inherit; }
        .marketing-button.secondary { background:#f0ece4; color:#5d554a; }
        .marketing-note { color:var(--muted); font-size:0.88rem; line-height:1.45; }
        .marketing-pipeline { display:grid; gap:12px; }
        .marketing-pipeline-item { background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:16px; padding:16px; box-shadow:0 10px 28px rgba(52,41,26,0.04); }
        .marketing-pipeline-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:10px; }
        .marketing-status { display:inline-block; padding:7px 10px; border-radius:999px; font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; }
        .marketing-status.draft { background:#f0ece4; color:#7a7267; }
        .marketing-status.pending { background:#efe5f8; color:#8e1c74; }
        .marketing-status.approved { background:#e7eefc; color:#355fb1; }
        .marketing-status.completed { background:#e3f5eb; color:#2c7a57; }
        .marketing-status.published { background:#e7f8f2; color:#1d7a55; }
        .marketing-meta-list { display:grid; gap:6px; color:var(--muted); font-size:0.9rem; margin-top:10px; }
        .marketing-preview { margin-top:12px; padding:14px 15px; border-radius:14px; background:#fff; border:1px solid rgba(220,207,191,0.75); }
        .marketing-preview-headline { font-weight:700; margin-bottom:8px; }
        .marketing-preview-body { white-space:pre-wrap; color:var(--muted); font-size:0.92rem; line-height:1.5; }
        .marketing-preview-meta { margin-top:10px; color:#8a826f; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.05em; }
        .marketing-rightbar h3 { font-size:0.83rem; letter-spacing:0.06em; text-transform:uppercase; color:var(--muted); margin-bottom:12px; }
        .rail-list { display:grid; gap:10px; margin-top:14px; }
        .rail-item, .mini-note { background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:14px; padding:12px 14px; }
        @media (max-width:1240px) { .marketing-shell { grid-template-columns:220px 1fr; } .marketing-rightbar { display:none; } }
        @media (max-width:900px) { .marketing-shell { grid-template-columns:1fr; } .marketing-sidebar { min-height:auto; border-right:0; border-bottom:1px solid var(--line); } .marketing-sidebar-footer { display:none; } .marketing-main { padding:20px 16px 24px; } .marketing-grid, .marketing-metrics { grid-template-columns:1fr; } }
    </style>
@endsection

@section('content')
    @php
        $founder = $dashboard['founder'];
        $atlas = $dashboard['atlas'] ?? [];
        $moduleCards = collect($dashboard['module_cards'] ?? []);
        $atlasCard = $moduleCards->firstWhere('key', 'atlas');
        $launchCards = $launchCards ?? [];
        $contentRequests = $contentRequests ?? collect();
        $channelAnalytics = $channelAnalytics ?? [];
        $publishTargets = $publishTargets ?? [];
        $atlasHistory = $atlasHistory ?? [];
    @endphp

    <div class="marketing-shell">
        <aside class="marketing-sidebar">
            @include('os.partials.founder-sidebar', [
                'founder' => $founder,
                'businessModel' => $founder->company->business_model ?? 'hybrid',
                'activeKey' => 'marketing',
                'navClass' => 'marketing-nav',
                'itemClass' => 'marketing-nav-item',
                'iconClass' => 'marketing-nav-icon',
                'innerClass' => 'marketing-sidebar-inner',
                'brandClass' => 'marketing-brand',
                'footerClass' => 'marketing-sidebar-footer',
                'userClass' => 'marketing-user',
                'avatarClass' => 'marketing-avatar',
            ])
        </aside>

        <main class="marketing-main">
            <div class="marketing-main-inner">
                <h1>Marketing</h1>
                <p>Manage campaigns, content generation, and publishing from one OS workspace.</p>

                @if (session('success'))
                    <div class="marketing-banner success">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="marketing-banner error">{{ session('error') }}</div>
                @endif

                @if ($errors->any())
                    <div class="marketing-banner error">
                        <strong>Please fix the highlighted fields.</strong>
                        <div class="marketing-note" style="margin-top:6px;">We kept your draft in place so you can finish creating the campaign from Hatchers AI OS.</div>
                    </div>
                @endif

                <section class="marketing-metrics">
                    <div class="marketing-metric">
                        <div class="marketing-card-copy">Generated posts</div>
                        <strong>{{ $atlas['generated_posts_count'] ?? 0 }}</strong>
                    </div>
                    <div class="marketing-metric">
                        <div class="marketing-card-copy">Campaigns</div>
                        <strong>{{ $atlas['generated_campaigns_count'] ?? 0 }}</strong>
                    </div>
                    <div class="marketing-metric">
                        <div class="marketing-card-copy">Images</div>
                        <strong>{{ $atlas['generated_images_count'] ?? 0 }}</strong>
                    </div>
                    <div class="marketing-metric">
                        <div class="marketing-card-copy">Campaign readiness</div>
                        <strong>{{ $atlasCard['readiness_score'] ?? 0 }}%</strong>
                    </div>
                </section>

                <section class="marketing-section">
                    <h2>OS Marketing Actions</h2>
                    <div class="marketing-grid">
                        <div class="marketing-card">
                            <div class="marketing-card-meta">Create Campaign</div>
                            <div class="marketing-card-title">Draft a new campaign in the OS</div>
                            <div class="marketing-card-copy">Create the campaign here, keep the draft here, and manage the work here.</div>
                            <form class="marketing-inline-form" method="POST" action="{{ route('founder.marketing.campaign.create') }}">
                                @csrf
                                <div class="marketing-field">
                                    <label for="campaign-title">Campaign title</label>
                                    <input id="campaign-title" name="title" type="text" value="{{ old('title') }}" placeholder="Spring launch, ICP discovery sprint, weekly nurture..." required>
                                    @error('title')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="marketing-field">
                                    <label for="campaign-description">Campaign brief</label>
                                    <textarea id="campaign-description" name="description" placeholder="What are we launching, who is it for, and what should the OS create?" required>{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="marketing-actions">
                                    <button class="marketing-cta marketing-button" type="submit">Create campaign</button>
                                </div>
                            </form>
                        </div>
                        <div class="marketing-card">
                            <div class="marketing-card-meta">Campaigns</div>
                            <div class="marketing-card-title">Open campaign workspace</div>
                            <div class="marketing-card-copy">Review active campaigns, archived campaigns, linked posts, and the current growth goal for your business.</div>
                            <a class="marketing-cta" href="{{ route('founder.ai-tools') }}">Back to AI Tools</a>
                        </div>
                    </div>
                </section>

                <section class="marketing-section">
                    <h2>Content Pipeline</h2>
                    <div class="marketing-grid">
                        <div class="marketing-card">
                            <div class="marketing-card-meta">Plan Content</div>
                            <div class="marketing-card-title">Create a content request in the OS</div>
                            <div class="marketing-card-copy">Plan the next post, email, blog, or landing page brief here. Hatchers AI OS keeps the queue visible and ready for generation.</div>
                            <form class="marketing-inline-form" method="POST" action="{{ route('founder.marketing.content-request.create') }}">
                                @csrf
                                <div class="marketing-field">
                                    <label for="content-title">Content title</label>
                                    <input id="content-title" name="content_title" type="text" value="{{ old('content_title') }}" placeholder="Founder story email, product teaser, social proof post..." required>
                                    @error('content_title')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="marketing-field">
                                    <label for="content-channel">Channel</label>
                                    <select id="content-channel" class="marketing-select" name="content_channel" required>
                                        <option value="">Select a channel</option>
                                        @foreach (['linkedin' => 'LinkedIn', 'instagram' => 'Instagram', 'x' => 'X / Twitter', 'email' => 'Email', 'blog' => 'Blog', 'landing-page' => 'Landing Page'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('content_channel') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('content_channel')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="marketing-field">
                                    <label for="content-goal">Goal</label>
                                    <input id="content-goal" name="content_goal" type="text" value="{{ old('content_goal') }}" placeholder="Drive replies, book calls, validate ICP, push a launch..." required>
                                    @error('content_goal')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="marketing-field">
                                    <label for="content-publish-target">Publish target</label>
                                    <select id="content-publish-target" class="marketing-select" name="content_publish_target" required>
                                        <option value="">Choose where this should go next</option>
                                        @foreach ($publishTargets as $value => $label)
                                            <option value="{{ $value }}" @selected(old('content_publish_target') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('content_publish_target')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="marketing-field">
                                    <label for="content-brief">Brief</label>
                                    <textarea id="content-brief" name="content_brief" placeholder="What should this content say, what angle should it take, and what action should the audience take next?" required>{{ old('content_brief') }}</textarea>
                                    @error('content_brief')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="marketing-actions">
                                    <button class="marketing-cta marketing-button" type="submit">Add to content queue</button>
                                </div>
                            </form>
                        </div>

                        <div class="marketing-card">
                            <div class="marketing-card-meta">Queue</div>
                            <div class="marketing-card-title">Current content requests</div>
                            <div class="marketing-card-copy">Use the queue to move content from draft to generation-ready, then mark it complete once the work is done.</div>
                            <div class="marketing-pipeline" style="margin-top:14px;">
                                @forelse ($contentRequests as $requestItem)
                                    <div class="marketing-pipeline-item">
                                        <div class="marketing-pipeline-head">
                                            <div>
                                                <div class="marketing-card-title">{{ $requestItem['title'] ?? 'Content request' }}</div>
                                                <div class="marketing-note">{{ $requestItem['brief'] }}</div>
                                            </div>
                                            <span class="marketing-status {{ $requestItem['status'] }}">{{ ucfirst($requestItem['status']) }}</span>
                                        </div>
                                        <div class="marketing-meta-list">
                                            <div>Channel: {{ $requestItem['channel'] ?: 'Not set' }}</div>
                                            <div>Goal: {{ $requestItem['goal'] ?: 'Not set' }}</div>
                                            <div>Publish target: {{ $requestItem['publish_target'] ?: 'Not set' }}</div>
                                            <div>Updated: {{ $requestItem['updated_at'] ?: 'Recently' }}</div>
                                        </div>
                                        <div class="marketing-preview">
                                            <div class="marketing-preview-headline">{{ $requestItem['preview']['headline'] }}</div>
                                            <div class="marketing-preview-body">{{ $requestItem['preview']['body'] }}</div>
                                            <div class="marketing-preview-meta">{{ $requestItem['preview']['meta'] }}</div>
                                        </div>
                                        @if ($requestItem['has_draft'])
                                            <div class="marketing-field" style="margin-top:12px;">
                                                <label for="draft-body-{{ $requestItem['id'] }}">Generated starter draft</label>
                                                <form method="POST" action="{{ route('founder.marketing.content-request.save-draft') }}">
                                                    @csrf
                                                    <input type="hidden" name="content_request_id" value="{{ $requestItem['id'] }}">
                                                    <textarea id="draft-body-{{ $requestItem['id'] }}" name="draft_body" placeholder="Edit your OS-generated draft here..." required>{{ old('content_request_id') == $requestItem['id'] ? old('draft_body') : $requestItem['draft'] }}</textarea>
                                                    @if (old('content_request_id') == $requestItem['id'])
                                                        @error('draft_body')
                                                            <div class="field-error">{{ $message }}</div>
                                                        @enderror
                                                    @endif
                                                    <div class="marketing-actions">
                                                        <button class="marketing-cta marketing-button" type="submit">Save draft</button>
                                                    </div>
                                                </form>
                                            </div>
                                        @endif
                                        <div class="marketing-actions">
                                            @if (!$requestItem['has_draft'])
                                                <form method="POST" action="{{ route('founder.marketing.content-request.generate') }}">
                                                    @csrf
                                                    <input type="hidden" name="content_request_id" value="{{ $requestItem['id'] }}">
                                                    <button class="marketing-cta marketing-button" type="submit">Generate starter draft</button>
                                                </form>
                                            @endif
                                            @if ($requestItem['has_draft'] && !in_array($requestItem['status'], ['approved', 'published'], true))
                                                <form method="POST" action="{{ route('founder.marketing.content-request.status') }}">
                                                    @csrf
                                                    <input type="hidden" name="content_request_id" value="{{ $requestItem['id'] }}">
                                                    <input type="hidden" name="status" value="approved">
                                                    <button class="marketing-cta marketing-button" type="submit">Approve draft</button>
                                                </form>
                                            @endif
                                            @if ($requestItem['status'] !== 'pending')
                                                <form method="POST" action="{{ route('founder.marketing.content-request.status') }}">
                                                    @csrf
                                                    <input type="hidden" name="content_request_id" value="{{ $requestItem['id'] }}">
                                                    <input type="hidden" name="status" value="pending">
                                                    <button class="marketing-cta marketing-button" type="submit">Queue for generation</button>
                                                </form>
                                            @endif
                                            @if ($requestItem['status'] === 'approved')
                                                <form method="POST" action="{{ route('founder.marketing.content-request.publish') }}">
                                                    @csrf
                                                    <input type="hidden" name="content_request_id" value="{{ $requestItem['id'] }}">
                                                    <button class="marketing-cta marketing-button" type="submit">Publish handoff</button>
                                                </form>
                                            @endif
                                            @if ($requestItem['status'] === 'published' && !empty($requestItem['cta_url']))
                                                <a class="marketing-secondary" href="{{ $requestItem['cta_url'] }}">Open publish workspace</a>
                                            @endif
                                            @if ($requestItem['status'] !== 'completed')
                                                <form method="POST" action="{{ route('founder.marketing.content-request.status') }}">
                                                    @csrf
                                                    <input type="hidden" name="content_request_id" value="{{ $requestItem['id'] }}">
                                                    <input type="hidden" name="status" value="completed">
                                                    <button class="marketing-secondary marketing-button secondary" type="submit">Mark complete</button>
                                                </form>
                                            @endif
                                            @if ($requestItem['status'] !== 'draft')
                                                <form method="POST" action="{{ route('founder.marketing.content-request.status') }}">
                                                    @csrf
                                                    <input type="hidden" name="content_request_id" value="{{ $requestItem['id'] }}">
                                                    <input type="hidden" name="status" value="draft">
                                                    <button class="marketing-secondary marketing-button secondary" type="submit">Move to draft</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="marketing-pipeline-item">
                                        <div class="marketing-card-title">No content requests yet</div>
                                        <div class="marketing-note">Start planning your next pieces here so Hatchers AI OS becomes the place where campaigns and content both begin.</div>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </section>

                <section class="marketing-section">
                    <h2>Active Campaigns</h2>
                    <div class="marketing-grid">
                        @forelse ($atlas['recent_campaigns'] as $campaign)
                            <div class="marketing-card">
                                <div class="marketing-card-title">{{ $campaign['title'] ?? 'Campaign' }}</div>
                                <div class="marketing-card-copy">{{ $campaign['description'] ?? 'Saved in Campaign Studio.' }}</div>
                                <div class="marketing-chip">{{ (int) ($campaign['generated_posts_count'] ?? 0) }} linked posts</div>
                                <div class="marketing-actions">
                                    @if (!empty($campaign['url']))
                                        <a class="marketing-cta" href="{{ $campaign['url'] }}" target="_blank" rel="noreferrer">Open Campaign</a>
                                    @endif
                                    <form method="POST" action="{{ route('founder.marketing.campaign.duplicate') }}">
                                        @csrf
                                        <input type="hidden" name="campaign_title" value="{{ $campaign['title'] ?? '' }}">
                                        <button class="marketing-secondary marketing-button secondary" type="submit">Duplicate</button>
                                    </form>
                                    <form method="POST" action="{{ route('founder.marketing.campaign.archive') }}">
                                        @csrf
                                        <input type="hidden" name="campaign_title" value="{{ $campaign['title'] ?? '' }}">
                                        <button class="marketing-secondary marketing-button secondary" type="submit">Archive</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="marketing-card">
                                <div class="marketing-card-title">No active campaigns yet</div>
                                <div class="marketing-card-copy">As campaigns are created in the OS, they will appear here as first-class founder items.</div>
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="marketing-section">
                    <h2>Campaign Activity</h2>
                    <div class="marketing-grid">
                        <div class="marketing-card">
                            <div class="marketing-card-meta">Chats, Agents, Content</div>
                            <div class="marketing-card-title">Recent AI and campaign activity</div>
                            <div class="marketing-card-copy">This keeps recent campaign generation, AI work, and content activity visible without sending the founder anywhere else.</div>
                            <div class="rail-list" style="margin-top: 14px;">
                                @forelse ($atlasHistory as $item)
                                    <div class="rail-item">
                                        <strong>{{ ucfirst($item['kind']) }}</strong><br>
                                        {{ $item['message'] }}
                                        <div class="marketing-note" style="margin-top: 6px;">{{ $item['updated_at'] }}</div>
                                    </div>
                                @empty
                                    <div class="rail-item">
                                        <strong>No campaign activity yet</strong><br>
                                        As you create campaigns and drafts, the recent activity timeline will appear here.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="marketing-card">
                            <div class="marketing-card-meta">OS Direction</div>
                            <div class="marketing-card-title">Keep marketing inside the OS</div>
                            <div class="marketing-card-copy">
                                The goal is to let founders create campaigns, manage content, review history, and publish toward the right target from one OS-native workflow.
                            </div>
                            <div class="marketing-actions">
                                <a class="marketing-cta" href="{{ route('founder.activity') }}">Open Activity Center</a>
                                <a class="marketing-secondary" href="{{ route('founder.ai-tools') }}">Back to AI Tools</a>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="marketing-section">
                    <h2>Archived Campaigns</h2>
                    <div class="marketing-grid">
                        @forelse (($atlas['archived_campaigns'] ?? []) as $campaign)
                            <div class="marketing-card">
                                <div class="marketing-card-title">{{ $campaign['title'] ?? 'Archived campaign' }}</div>
                                <div class="marketing-card-copy">{{ $campaign['description'] ?? 'Archived in Campaign Studio.' }}</div>
                                <div class="marketing-chip">{{ (int) ($campaign['generated_posts_count'] ?? 0) }} linked posts</div>
                                <div class="marketing-actions">
                                    @if (!empty($campaign['url']))
                                        <a class="marketing-secondary" href="{{ $campaign['url'] }}" target="_blank" rel="noreferrer">Open Archive</a>
                                    @endif
                                    <form method="POST" action="{{ route('founder.marketing.campaign.restore') }}">
                                        @csrf
                                        <input type="hidden" name="campaign_title" value="{{ $campaign['title'] ?? '' }}">
                                        <button class="marketing-cta marketing-button" type="submit">Restore</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="marketing-card">
                                <div class="marketing-card-title">No archived campaigns</div>
                                <div class="marketing-card-copy">Archived items will appear here as the OS takes over more of the founder marketing workflow.</div>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </main>

        <aside class="marketing-rightbar">
            <div class="marketing-rightbar-inner">
                <h3>Current Goal</h3>
                <div class="mini-note">{{ $atlas['primary_growth_goal'] ?? 'No primary growth goal has been set yet.' }}</div>

                <h3 style="margin-top:22px;">Connected Tools</h3>
                <div class="rail-list">
                    @foreach ($launchCards as $launch)
                        <div class="rail-item">
                            <div style="font-weight:600;">{{ $launch['label'] }}</div>
                            <div style="margin-top:4px;color:var(--muted);">{{ $launch['description'] }}</div>
                        </div>
                    @endforeach
                </div>

                <h3 style="margin-top:22px;">Channel Analytics</h3>
                <div class="rail-list">
                    @forelse ($channelAnalytics as $channel)
                        <div class="rail-item">
                            <div style="font-weight:600;">{{ $channel['channel'] }}</div>
                            <div style="margin-top:4px;color:var(--muted);">{{ $channel['total'] }} requests · {{ $channel['approved'] }} approved · {{ $channel['published'] }} published</div>
                        </div>
                    @empty
                        <div class="mini-note">Once content requests start flowing through the OS, channel analytics will appear here.</div>
                    @endforelse
                </div>

                <h3 style="margin-top:22px;">OS Direction</h3>
                <div class="mini-note">Campaign creation, content planning, review, publish targets, and publish handoff now live in Hatchers Ai Business OS. The next strongest step is direct publishing and post-performance analytics from this same workspace.</div>
            </div>
        </aside>
    </div>
@endsection
