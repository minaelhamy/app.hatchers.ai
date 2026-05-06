@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'guidebook-dashboard-page')

@php
    $workspace = $dashboard['workspace'] ?? [];
    $notifications = $workspace['notifications'] ?? [];
    $taskEntries = $launchPlanState['tasks'] ?? [];
    $launchMilestones = $launchPlanState['milestones'] ?? [];
    $quickPrompt = $workspace['quick_prompt'] ?? 'Ask Hatchers what to build next...';
    $founder = $dashboard['founder'] ?? auth()->user();
    $company = $dashboard['company'] ?? null;
    $chatNeedsOnboarding = (bool) ($chatOnboardingState['needs_onboarding'] ?? false);
    $projectName = trim((string) ($chatOnboardingState['project_name'] ?? ($company?->company_name ?? 'New project')));
    $topActions = [
        ['label' => 'Tasks', 'href' => route('founder.tasks'), 'description' => 'Step-by-step execution'],
        ['label' => 'Inbox', 'href' => route('founder.inbox'), 'description' => 'Messages and responses'],
        ['label' => 'Website', 'href' => route('website'), 'description' => 'Build and publish'],
        ['label' => 'Marketing', 'href' => route('founder.marketing'), 'description' => 'Campaigns and content'],
        ['label' => 'Commerce', 'href' => route('founder.commerce'), 'description' => 'Orders, bookings, wallet'],
        ['label' => 'AI Studio', 'href' => route('founder.ai-tools'), 'description' => 'Atlas and AI tools'],
        ['label' => 'Analytics', 'href' => route('founder.analytics'), 'description' => 'See what is working'],
        ['label' => 'Settings', 'href' => route('founder.settings'), 'description' => 'Business intelligence'],
    ];
@endphp

@section('head')
    <style>
        .page.guidebook-dashboard-page {
            min-height: 100vh;
            padding: 0;
            font-family: "Inter", "Avenir Next", "Segoe UI", sans-serif;
            background: #ece7e0;
        }

        .gb-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 86px minmax(0, 1fr);
            background:
                radial-gradient(circle at 12% 18%, rgba(255,255,255,0.52), transparent 0 18%),
                linear-gradient(180deg, #f4efe8 0%, #e7ded4 100%);
        }

        .gb-rail {
            padding: 18px 12px;
            border-right: 1px solid rgba(96, 82, 72, 0.12);
            background: rgba(28, 24, 21, 0.96);
            display: grid;
            align-content: start;
            gap: 14px;
        }

        .gb-rail-mark {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: linear-gradient(135deg, #f13b74, #f26444);
            box-shadow: 0 18px 34px rgba(241, 59, 116, 0.26);
            justify-self: center;
        }

        .gb-rail-link,
        .gb-rail-chat {
            width: 56px;
            height: 56px;
            border-radius: 18px;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.04);
            color: rgba(255,255,255,0.82);
            display: grid;
            place-items: center;
            text-decoration: none;
            transition: transform 0.18s ease, background 0.18s ease, border-color 0.18s ease;
            justify-self: center;
            cursor: pointer;
        }

        .gb-rail-link:hover,
        .gb-rail-chat:hover,
        .gb-rail-link.is-active {
            transform: translateY(-1px);
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.16);
        }

        .gb-main {
            display: grid;
            grid-template-rows: auto 1fr;
            min-width: 0;
        }

        .gb-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 20px 28px;
            border-bottom: 1px solid rgba(106, 88, 74, 0.1);
            background: rgba(255, 251, 247, 0.8);
            backdrop-filter: blur(14px);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .gb-search {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 820px;
            min-height: 54px;
            padding: 0 18px;
            border-radius: 999px;
            border: 1px solid rgba(118, 101, 90, 0.12);
            background: rgba(255,255,255,0.85);
            color: rgba(89, 74, 63, 0.64);
            box-shadow: 0 14px 40px rgba(60, 46, 36, 0.06);
        }

        .gb-search-kbd {
            padding: 6px 10px;
            border-radius: 12px;
            border: 1px solid rgba(118, 101, 90, 0.12);
            background: rgba(245, 238, 231, 0.85);
            font-size: 0.76rem;
            color: rgba(101, 83, 70, 0.7);
        }

        .gb-topbar-right {
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }

        .gb-bell,
        .gb-user {
            position: relative;
            min-height: 54px;
            border-radius: 999px;
            border: 1px solid rgba(118, 101, 90, 0.12);
            background: rgba(255,255,255,0.85);
            box-shadow: 0 14px 40px rgba(60, 46, 36, 0.06);
        }

        .gb-bell {
            width: 54px;
            display: grid;
            place-items: center;
            text-decoration: none;
            color: #40362f;
        }

        .gb-bell-badge {
            position: absolute;
            top: 6px;
            right: 6px;
            min-width: 18px;
            height: 18px;
            padding: 0 4px;
            border-radius: 999px;
            background: linear-gradient(135deg, #f26444, #f13b74);
            color: #fff;
            font-size: 0.68rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .gb-user {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 0 18px 0 10px;
            color: #201915;
        }

        .gb-user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            background: linear-gradient(135deg, #f2dcc4, #d2b28b);
        }

        .gb-body {
            padding: 28px;
            display: grid;
            gap: 24px;
            align-content: start;
        }

        .gb-hero {
            border-radius: 36px;
            padding: 32px;
            background: rgba(255, 251, 247, 0.84);
            border: 1px solid rgba(118, 101, 90, 0.12);
            box-shadow: 0 28px 64px rgba(67, 51, 40, 0.08);
        }

        .gb-hero-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .gb-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(245, 239, 231, 0.95);
            border: 1px solid rgba(118, 101, 90, 0.12);
            font-size: 0.82rem;
            color: rgba(96, 79, 68, 0.84);
        }

        .gb-chip::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: linear-gradient(135deg, #f13b74, #f26444);
        }

        .gb-heading {
            margin: 0;
            font-family: "Inter Tight", "Inter", sans-serif;
            font-size: clamp(2.4rem, 4vw, 4.8rem);
            line-height: 0.92;
            letter-spacing: -0.06em;
            color: #171310;
            max-width: 880px;
        }

        .gb-subcopy {
            margin: 16px 0 0;
            max-width: 760px;
            font-size: 1.06rem;
            line-height: 1.6;
            color: rgba(79, 65, 56, 0.82);
        }

        .gb-hero-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .gb-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 52px;
            padding: 0 18px;
            border-radius: 18px;
            border: 1px solid rgba(118, 101, 90, 0.14);
            background: rgba(255,255,255,0.92);
            color: #201915;
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
        }

        .gb-btn.primary {
            background: #111;
            color: #fff;
            border-color: #111;
        }

        .gb-grid {
            display: grid;
            grid-template-columns: 1.3fr 1fr 1fr;
            gap: 20px;
        }

        .gb-card {
            border-radius: 28px;
            border: 1px solid rgba(118, 101, 90, 0.12);
            background: rgba(255, 251, 247, 0.86);
            box-shadow: 0 22px 48px rgba(67, 51, 40, 0.07);
            padding: 22px;
            min-width: 0;
        }

        .gb-card h3 {
            margin: 0 0 12px;
            font-size: 1.1rem;
            letter-spacing: -0.02em;
        }

        .gb-task-list,
        .gb-plan-list,
        .gb-tool-list,
        .gb-notification-list {
            display: grid;
            gap: 12px;
        }

        .gb-task-item,
        .gb-plan-item,
        .gb-tool-item,
        .gb-notification-item {
            border-radius: 20px;
            border: 1px solid rgba(118, 101, 90, 0.12);
            background: rgba(255,255,255,0.9);
            padding: 14px 15px;
        }

        .gb-task-item strong,
        .gb-plan-item strong,
        .gb-tool-item strong,
        .gb-notification-item strong {
            display: block;
            margin-bottom: 4px;
            color: #18120f;
            letter-spacing: -0.02em;
        }

        .gb-muted {
            color: rgba(90, 75, 65, 0.74);
            line-height: 1.45;
            font-size: 0.92rem;
        }

        .gb-inline-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            font-weight: 700;
            text-decoration: none;
            color: #151110;
        }

        .gb-launch-wrap {
            display: grid;
            grid-template-columns: 1.15fr .85fr;
            gap: 20px;
        }

        .gb-launch-metric {
            font-size: 0.85rem;
            color: rgba(90, 75, 65, 0.74);
            margin-top: 10px;
        }

        .gb-chat-fab {
            position: fixed;
            right: 32px;
            bottom: 28px;
            width: 64px;
            height: 64px;
            border-radius: 22px;
            border: 1px solid rgba(17,17,17,0.12);
            background: linear-gradient(135deg, #1a1715, #2f2620);
            color: #fff;
            display: grid;
            place-items: center;
            box-shadow: 0 28px 64px rgba(33, 23, 18, 0.28);
            cursor: pointer;
            z-index: 30;
        }

        .gb-chat-card,
        .gb-chat-panel,
        .gb-tools-panel {
            position: fixed;
            right: 32px;
            bottom: 104px;
            z-index: 29;
            opacity: 0;
            pointer-events: none;
            transform: translateY(14px) scale(0.98);
            transition: opacity 0.22s ease, transform 0.22s ease;
        }

        .gb-chat-card.is-open,
        .gb-chat-panel.is-open,
        .gb-tools-panel.is-open {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0) scale(1);
        }

        .gb-chat-card {
            width: 320px;
            border-radius: 26px;
            border: 1px solid rgba(118, 101, 90, 0.12);
            background: rgba(255, 251, 247, 0.96);
            box-shadow: 0 28px 64px rgba(33, 23, 18, 0.18);
            padding: 18px;
        }

        .gb-chat-panel {
            width: min(480px, calc(100vw - 130px));
            height: min(720px, calc(100vh - 130px));
            border-radius: 30px;
            border: 1px solid rgba(118, 101, 90, 0.12);
            background: rgba(255, 251, 247, 0.98);
            box-shadow: 0 32px 70px rgba(33, 23, 18, 0.24);
            overflow: hidden;
            display: grid;
            grid-template-rows: auto 1fr auto;
        }

        .gb-tools-panel {
            width: min(420px, calc(100vw - 130px));
            max-height: min(680px, calc(100vh - 120px));
            overflow: auto;
            border-radius: 28px;
            border: 1px solid rgba(118, 101, 90, 0.12);
            background: rgba(255, 251, 247, 0.98);
            box-shadow: 0 32px 70px rgba(33, 23, 18, 0.24);
            padding: 18px;
        }

        .gb-chat-head,
        .gb-tools-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 18px 16px;
            border-bottom: 1px solid rgba(118, 101, 90, 0.1);
        }

        .gb-chat-stream {
            overflow: auto;
            padding: 18px;
            display: grid;
            gap: 12px;
            align-content: start;
            background: linear-gradient(180deg, rgba(251,247,243,0.96), rgba(244,236,227,0.9));
        }

        .gb-msg-ai,
        .gb-msg-user {
            display: grid;
            gap: 8px;
        }

        .gb-msg-user {
            justify-items: end;
        }

        .gb-bubble-ai,
        .gb-bubble-user {
            max-width: 88%;
            border-radius: 20px;
            padding: 14px 16px;
            line-height: 1.55;
            font-size: 0.94rem;
        }

        .gb-bubble-ai {
            background: rgba(255,255,255,0.9);
            border: 1px solid rgba(118, 101, 90, 0.1);
            color: #211915;
        }

        .gb-bubble-user {
            background: #111;
            color: #fff;
        }

        .gb-chat-actions,
        .gb-choice-list {
            display: grid;
            gap: 10px;
            margin-top: 10px;
        }

        .gb-choice {
            width: 100%;
            text-align: left;
            padding: 13px 14px;
            border-radius: 18px;
            border: 1px solid rgba(118, 101, 90, 0.12);
            background: rgba(255,255,255,0.94);
            color: #181210;
            cursor: pointer;
            font: inherit;
        }

        .gb-chat-input-wrap {
            padding: 14px 18px 18px;
            border-top: 1px solid rgba(118, 101, 90, 0.1);
            background: rgba(255, 251, 247, 0.98);
        }

        .gb-chat-input {
            display: flex;
            gap: 10px;
            align-items: center;
            min-height: 62px;
            border-radius: 22px;
            border: 1px solid rgba(118, 101, 90, 0.12);
            background: rgba(255,255,255,0.95);
            padding: 10px 12px 10px 16px;
        }

        .gb-chat-input textarea {
            flex: 1;
            min-height: 22px;
            max-height: 140px;
            resize: none;
            border: 0;
            outline: none;
            background: transparent;
            font: inherit;
            color: #181210;
        }

        .gb-send {
            min-width: 46px;
            height: 46px;
            border-radius: 14px;
            border: 0;
            background: #111;
            color: #fff;
            cursor: pointer;
        }

        .gb-hidden {
            display: none !important;
        }

        .gb-status {
            padding: 12px 14px;
            border-radius: 16px;
            background: rgba(245, 238, 231, 0.96);
            border: 1px solid rgba(118, 101, 90, 0.12);
            color: rgba(90, 75, 65, 0.84);
            font-size: 0.9rem;
        }

        @media (max-width: 1180px) {
            .gb-grid,
            .gb-launch-wrap {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 860px) {
            .gb-shell {
                grid-template-columns: 1fr;
            }

            .gb-rail {
                display: none;
            }

            .gb-topbar,
            .gb-body {
                padding-left: 18px;
                padding-right: 18px;
            }

            .gb-chat-panel,
            .gb-tools-panel {
                right: 16px;
                bottom: 90px;
                width: calc(100vw - 32px);
            }

            .gb-chat-fab {
                right: 16px;
                bottom: 16px;
            }
        }
    </style>
@endsection

@section('content')
    <div class="gb-shell"
         id="guidebookShell"
         data-onboarding-needed="{{ $chatNeedsOnboarding ? '1' : '0' }}"
         data-onboarding-endpoint="{{ route('assistant.chat.onboarding-complete') }}"
         data-reset-endpoint="{{ route('assistant.chat.reset') }}"
         data-assistant-endpoint="{{ route('assistant.chat') }}">
        <aside class="gb-rail">
            <div class="gb-rail-mark"></div>
            <a href="{{ route('dashboard') }}" class="gb-rail-link is-active" title="Home">⌂</a>
            <a href="{{ route('founder.tasks') }}" class="gb-rail-link" title="Tasks">✓</a>
            <a href="{{ route('founder.inbox') }}" class="gb-rail-link" title="Inbox">✉</a>
            <button type="button" class="gb-rail-chat" id="railAiToolsBtn" title="AI Tools">✦</button>
        </aside>

        <div class="gb-main">
            <div class="gb-topbar">
                <div class="gb-search">
                    <span>What would you like to do?</span>
                    <span class="gb-search-kbd">⌘K</span>
                </div>
                <div class="gb-topbar-right">
                    <a href="{{ route('founder.notifications') }}" class="gb-bell" aria-label="Notifications">
                        <span>🔔</span>
                        @if(!empty($workspace['unread_notification_count']))
                            <span class="gb-bell-badge">{{ $workspace['unread_notification_count'] }}</span>
                        @endif
                    </a>
                    <div class="gb-user">
                        <span class="gb-user-avatar"></span>
                        <div>
                            <strong style="display:block;">{{ $founder->full_name }}</strong>
                            <span class="gb-muted">{{ now()->format('D, M j g:i A') }}</span>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="gb-btn">Logout</button>
                    </form>
                </div>
            </div>

            <main class="gb-body">
                <section class="gb-hero">
                    <div class="gb-hero-meta">
                        <span class="gb-chip">{{ $chatNeedsOnboarding ? 'Setup in progress' : 'Launch workspace' }}</span>
                        <span class="gb-chip">{{ $projectName !== '' ? $projectName : 'New project' }}</span>
                    </div>
                    <h1 class="gb-heading">
                        @if($chatNeedsOnboarding)
                            Let’s shape this business through chat and turn it into a real launch plan.
                        @else
                            {{ $launchPlanState['title'] ?? 'Your Hatchers launch workspace is ready.' }}
                        @endif
                    </h1>
                    <p class="gb-subcopy">
                        @if($chatNeedsOnboarding)
                            Hatchers will collect the core business context, infer the rest, then build the milestones, tasks, and website direction using the same playbook structure as the guidebook prototype.
                        @else
                            {{ $launchPlanState['summary'] ?? 'Your company intelligence, launch plan, and first tasks are now aligned inside Hatchers OS.' }}
                        @endif
                    </p>
                    <div class="gb-hero-actions">
                        <button type="button" class="gb-btn primary" id="newProjectBtn">
                            {{ $chatNeedsOnboarding ? 'Start founder chat' : 'Refine launch plan' }}
                        </button>
                        <a href="{{ route('website') }}" class="gb-btn">Build My Website</a>
                        <button type="button" class="gb-btn" id="openToolsBtn">AI Tools</button>
                    </div>
                </section>

                <section class="gb-grid">
                    <article class="gb-card">
                        <h3>Tasks</h3>
                        <div class="gb-task-list">
                            @forelse(array_slice($taskEntries, 0, 4) as $task)
                                <div class="gb-task-item">
                                    <strong>{{ $task['title'] }}</strong>
                                    <div class="gb-muted">{{ $task['description'] }}</div>
                                    @if(!empty($task['milestone']))
                                        <div class="gb-launch-metric">{{ $task['milestone'] }} · {{ $task['north_star_metric'] }}</div>
                                    @endif
                                    <a href="{{ $task['cta_url'] ?: route('founder.tasks') }}" class="gb-inline-link">{{ $task['cta_label'] ?: 'Open' }} →</a>
                                </div>
                            @empty
                                <div class="gb-status">Once the onboarding chat is complete, Hatchers will write your first detailed execution tasks here.</div>
                            @endforelse
                        </div>
                    </article>

                    <article class="gb-card">
                        <h3>Inbox</h3>
                        <div class="gb-notification-list">
                            @forelse(array_slice($notifications, 0, 4) as $item)
                                <div class="gb-notification-item">
                                    <strong>{{ $item['title'] ?? 'Update from Hatchers' }}</strong>
                                    <div class="gb-muted">{{ $item['body'] ?? $item['description'] ?? 'Your OS activity will appear here.' }}</div>
                                </div>
                            @empty
                                <div class="gb-status">No inbox updates yet. Hatchers will surface execution notes, system updates, and guidance here.</div>
                            @endforelse
                        </div>
                    </article>

                    <article class="gb-card">
                        <h3>AI Tools</h3>
                        <div class="gb-tool-list">
                            @foreach(array_slice($topActions, 0, 4) as $tool)
                                <div class="gb-tool-item">
                                    <strong>{{ $tool['label'] }}</strong>
                                    <div class="gb-muted">{{ $tool['description'] }}</div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="gb-btn" style="margin-top:16px;" id="openToolsBtnSecondary">Open all AI tools</button>
                    </article>
                </section>

                <section class="gb-launch-wrap">
                    <article class="gb-card">
                        <h3>Launch plan</h3>
                        <div class="gb-plan-list">
                            @forelse($launchMilestones as $milestone)
                                <div class="gb-plan-item">
                                    <strong>{{ $milestone['title'] ?? 'Milestone' }}</strong>
                                    <div class="gb-muted">{{ $milestone['objective'] ?? '' }}</div>
                                    <div class="gb-launch-metric">{{ $milestone['north_star_metric'] ?? '' }} · {{ $milestone['estimated_hours'] ?? 0 }} hrs</div>
                                </div>
                            @empty
                                <div class="gb-status">No launch plan yet. Start the founder chat and Hatchers will build a milestone-based plan with realistic pacing.</div>
                            @endforelse
                        </div>
                    </article>

                    <article class="gb-card">
                        <h3>Plan pacing</h3>
                        @if(!empty($launchPlanState['pace']))
                            <div class="gb-status">Hours per week: {{ $launchPlanState['pace']['hours_per_week'] ?? 0 }}</div>
                            <div class="gb-status" style="margin-top:10px;">Effective weekly capacity: {{ $launchPlanState['pace']['effective_hours_per_week'] ?? 0 }} hrs</div>
                            <div class="gb-status" style="margin-top:10px;">Estimated total effort: {{ $launchPlanState['pace']['estimated_total_hours'] ?? 0 }} hrs</div>
                            <div class="gb-status" style="margin-top:10px;">Estimated duration: {{ $launchPlanState['pace']['estimated_weeks'] ?? 0 }} weeks</div>
                        @else
                            <div class="gb-status">Hatchers will estimate workload and stretch the plan over time based on the founder’s realistic weekly capacity.</div>
                        @endif

                        @if(!empty($launchPlanState['north_star_metrics']))
                            <h3 style="margin-top:18px;">North-star metrics</h3>
                            <div class="gb-plan-list">
                                @foreach($launchPlanState['north_star_metrics'] as $metric)
                                    <div class="gb-plan-item">
                                        <strong>{{ $metric }}</strong>
                                        <div class="gb-muted">Each milestone is anchored to a real outcome, not a vanity metric.</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </article>
                </section>
            </main>
        </div>

        <button type="button" class="gb-chat-fab" id="chatFab" aria-label="Open chat">✦</button>

        <div class="gb-chat-card" id="chatCard">
            <strong style="display:block; margin-bottom:6px;">Hatchers AI</strong>
            <div class="gb-muted">Open the chat to shape your launch plan, ask for guidance, or build the next piece of the business.</div>
        </div>

        <section class="gb-chat-panel" id="chatPanel" aria-live="polite">
            <div class="gb-chat-head">
                <div>
                    <strong style="display:block;">Hatchers AI</strong>
                    <span class="gb-muted" id="chatPanelLabel">{{ $chatNeedsOnboarding ? 'Founder onboarding' : 'Launch copilot' }}</span>
                </div>
                <button type="button" class="gb-btn" id="closeChatBtn">Close</button>
            </div>
            <div class="gb-chat-stream" id="chatStream"></div>
            <div class="gb-chat-input-wrap">
                <div class="gb-chat-input">
                    <textarea id="chatInput" placeholder="{{ $quickPrompt }}"></textarea>
                    <button type="button" class="gb-send" id="chatSendBtn">→</button>
                </div>
            </div>
        </section>

        <aside class="gb-tools-panel" id="toolsPanel">
            <div class="gb-tools-head">
                <div>
                    <strong style="display:block;">AI Tools</strong>
                    <span class="gb-muted">Everything Hatchers can launch, automate, or help you build.</span>
                </div>
                <button type="button" class="gb-btn" id="closeToolsBtn">Close</button>
            </div>

            <div class="gb-tool-list">
                @foreach($topActions as $tool)
                    <a href="{{ $tool['href'] }}" class="gb-tool-item" style="text-decoration:none;">
                        <strong>{{ $tool['label'] }}</strong>
                        <div class="gb-muted">{{ $tool['description'] }}</div>
                    </a>
                @endforeach

                @foreach($launchCards as $card)
                    <a href="{{ $card['url'] }}" class="gb-tool-item" style="text-decoration:none;" target="_blank" rel="noopener">
                        <strong>{{ $card['label'] }}</strong>
                        <div class="gb-muted">{{ $card['description'] }}</div>
                    </a>
                @endforeach
            </div>
        </aside>
    </div>
@endsection

@section('scripts')
    <script>
        (() => {
            const shell = document.getElementById('guidebookShell');
            if (!shell) return;

            const onboardingNeeded = shell.dataset.onboardingNeeded === '1';
            const onboardingEndpoint = shell.dataset.onboardingEndpoint;
            const assistantEndpoint = shell.dataset.assistantEndpoint;
            const chatFab = document.getElementById('chatFab');
            const chatCard = document.getElementById('chatCard');
            const chatPanel = document.getElementById('chatPanel');
            const closeChatBtn = document.getElementById('closeChatBtn');
            const chatStream = document.getElementById('chatStream');
            const chatInput = document.getElementById('chatInput');
            const chatSendBtn = document.getElementById('chatSendBtn');
            const openToolsBtn = document.getElementById('openToolsBtn');
            const openToolsBtnSecondary = document.getElementById('openToolsBtnSecondary');
            const railAiToolsBtn = document.getElementById('railAiToolsBtn');
            const toolsPanel = document.getElementById('toolsPanel');
            const closeToolsBtn = document.getElementById('closeToolsBtn');
            const newProjectBtn = document.getElementById('newProjectBtn');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            let chatState = 'closed';
            let onboarding = {
                answers: {},
                currentStep: onboardingNeeded ? 'q1' : 'freeform',
                processing: false,
            };

            const steps = {
                q1: {
                    prompt: 'What do you do, and who do you help?',
                    help: 'Say it simply, like you would explain it to a friend.',
                    next: 'q2',
                },
                q2: {
                    prompt: 'Who is the one person most likely to pay you right now?',
                    help: 'Describe the best customer to target first, not everyone eventually.',
                    next: 'q3',
                },
                q3: {
                    prompt: 'What problem do you solve for them, and what happens if they do not fix it?',
                    help: 'Focus on the real commercial pain, not just the topic.',
                    next: 'q4',
                },
                q4: {
                    prompt: 'What might they use instead of you, even if it is not a direct competitor?',
                    help: 'A platform, a freelancer, doing nothing, or another workaround all count.',
                    next: 'budget',
                    optional: true,
                },
                budget: {
                    prompt: 'Do you want to grow organically, or are you open to using a budget for paid acquisition?',
                    choices: [
                        { value: 'organic', label: 'Organic only' },
                        { value: 'paid', label: 'Open to paid' },
                        { value: 'unsure', label: 'Not sure yet' },
                    ],
                    next: 'time',
                },
                time: {
                    prompt: 'How much time can you realistically put into this each week?',
                    choices: [
                        { value: 'low', label: 'Less than 2 hours' },
                        { value: 'mid', label: '3 to 5 hours' },
                        { value: 'high', label: '5+ hours' },
                    ],
                    next: 'complete',
                },
            };

            function setChatState(state) {
                chatState = state;
                chatCard.classList.toggle('is-open', state === 'card');
                chatPanel.classList.toggle('is-open', state === 'panel');
            }

            function setToolsOpen(open) {
                toolsPanel.classList.toggle('is-open', open);
            }

            function appendBubble(role, html) {
                const wrap = document.createElement('div');
                wrap.className = role === 'user' ? 'gb-msg-user' : 'gb-msg-ai';
                const bubble = document.createElement('div');
                bubble.className = role === 'user' ? 'gb-bubble-user' : 'gb-bubble-ai';
                bubble.innerHTML = html;
                wrap.appendChild(bubble);
                chatStream.appendChild(wrap);
                chatStream.scrollTop = chatStream.scrollHeight;
                return bubble;
            }

            function showChoiceStep(stepKey) {
                const step = steps[stepKey];
                appendBubble('ai', `<strong>${step.prompt}</strong>`);
                const wrap = document.createElement('div');
                wrap.className = 'gb-msg-ai';
                const bubble = document.createElement('div');
                bubble.className = 'gb-bubble-ai';
                const list = document.createElement('div');
                list.className = 'gb-choice-list';

                step.choices.forEach((choice) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'gb-choice';
                    button.textContent = choice.label;
                    button.addEventListener('click', () => {
                        if (onboarding.processing) return;
                        onboarding.answers[stepKey === 'budget' ? 'budget_strategy' : 'time_commitment'] = choice.value;
                        appendBubble('user', choice.label);
                        bubble.remove();
                        moveToStep(step.next);
                    });
                    list.appendChild(button);
                });

                bubble.appendChild(list);
                wrap.appendChild(bubble);
                chatStream.appendChild(wrap);
                chatStream.scrollTop = chatStream.scrollHeight;
            }

            function askFreeformStep(stepKey) {
                const step = steps[stepKey];
                const help = step.help ? `<div class="gb-muted" style="margin-top:6px;">${step.help}</div>` : '';
                const bubble = appendBubble('ai', `<strong>${step.prompt}</strong>${help}`);
                if (step.optional) {
                    const actions = document.createElement('div');
                    actions.className = 'gb-chat-actions';
                    const skipButton = document.createElement('button');
                    skipButton.type = 'button';
                    skipButton.className = 'gb-choice';
                    skipButton.textContent = 'Skip this question';
                    skipButton.addEventListener('click', () => {
                        onboarding.answers[stepKey] = '';
                        appendBubble('user', '(Skipped)');
                        actions.remove();
                        moveToStep(step.next);
                    });
                    actions.appendChild(skipButton);
                    bubble.appendChild(actions);
                }
                chatInput.placeholder = step.optional ? 'You can answer or skip this one…' : 'Type your answer…';
                chatInput.focus();
                onboarding.currentStep = stepKey;
            }

            function moveToStep(stepKey) {
                if (stepKey === 'complete') {
                    submitOnboarding();
                    return;
                }

                if (steps[stepKey]?.choices) {
                    onboarding.currentStep = stepKey;
                    showChoiceStep(stepKey);
                    return;
                }

                askFreeformStep(stepKey);
            }

            async function submitOnboarding() {
                onboarding.processing = true;
                appendBubble('ai', '<strong>Building your launch plan…</strong><div class="gb-muted" style="margin-top:6px;">Hatchers is deducing your company intelligence, choosing the launch path, and writing your first milestones and tasks.</div>');

                try {
                    const response = await fetch(onboardingEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify(onboarding.answers),
                    });

                    const payload = await response.json();
                    if (!response.ok || !payload.success) {
                        throw new Error(payload.error || 'Hatchers could not finish the onboarding flow.');
                    }

                    appendBubble('ai', `<strong>Launch plan ready.</strong><div class="gb-muted" style="margin-top:6px;">${payload.reply}</div>`);
                    setTimeout(() => window.location.reload(), 1200);
                } catch (error) {
                    onboarding.processing = false;
                    appendBubble('ai', `<strong>We hit a setup problem.</strong><div class="gb-muted" style="margin-top:6px;">${error.message}</div>`);
                }
            }

            async function sendFreeformChat() {
                if (onboarding.processing) return;
                const value = chatInput.value.trim();
                if (!value) return;
                chatInput.value = '';

                if (onboardingNeeded && steps[onboarding.currentStep]) {
                    onboarding.answers[onboarding.currentStep] = value;
                    appendBubble('user', value);
                    moveToStep(steps[onboarding.currentStep].next);
                    return;
                }

                appendBubble('user', value);
                try {
                    const response = await fetch(assistantEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({
                            message: value,
                            current_page: 'guidebook_dashboard',
                        }),
                    });
                    const payload = await response.json();
                    if (!response.ok || !payload.success) {
                        throw new Error(payload.error || 'Hatchers could not respond right now.');
                    }
                    appendBubble('ai', payload.reply);
                } catch (error) {
                    appendBubble('ai', `<strong>We could not answer that right now.</strong><div class="gb-muted" style="margin-top:6px;">${error.message}</div>`);
                }
            }

            function startOnboardingFlow() {
                setChatState('panel');
                chatStream.innerHTML = '';
                onboarding.answers = {};
                onboarding.currentStep = 'q1';
                onboarding.processing = false;
                appendBubble('ai', '<strong>Let’s build your launch plan.</strong><div class="gb-muted" style="margin-top:6px;">I will ask a few focused questions, infer the rest, and turn that into milestones, tasks, and the right website path.</div>');
                askFreeformStep('q1');
            }

            chatFab.addEventListener('click', () => {
                if (chatState === 'closed') setChatState('card');
                else if (chatState === 'card') setChatState('panel');
                else setChatState('closed');
            });

            chatCard.addEventListener('click', () => setChatState('panel'));
            closeChatBtn.addEventListener('click', () => setChatState('closed'));
            openToolsBtn?.addEventListener('click', () => setToolsOpen(true));
            openToolsBtnSecondary?.addEventListener('click', () => setToolsOpen(true));
            railAiToolsBtn?.addEventListener('click', () => setToolsOpen(true));
            closeToolsBtn?.addEventListener('click', () => setToolsOpen(false));
            newProjectBtn?.addEventListener('click', startOnboardingFlow);
            chatSendBtn.addEventListener('click', sendFreeformChat);
            chatInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    sendFreeformChat();
                }
            });

            if (onboardingNeeded) {
                setTimeout(() => {
                    setChatState('card');
                    startOnboardingFlow();
                }, 280);
            } else {
                chatStream.innerHTML = '';
                appendBubble('ai', '<strong>Your launch workspace is live.</strong><div class="gb-muted" style="margin-top:6px;">Ask Hatchers to refine the offer, improve the website, or break the next milestone into clearer tasks.</div>');
            }
        })();
    </script>
@endsection
