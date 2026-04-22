@extends('os.layout')

@section('content')
    @php
        $founder = $dashboard['founder'];
        $company = $dashboard['company'];
        $intelligence = $dashboard['intelligence'];
        $subscription = $dashboard['subscription'];
        $weeklyState = $dashboard['weekly_state'];
        $commercial = $dashboard['commercial_summary'];
        $actions = $dashboard['actions'];
        $metrics = $dashboard['metrics'];
        $moduleCards = $dashboard['module_cards'];
        $activityFeed = $dashboard['activity_feed'];
        $execution = $dashboard['execution'];
        $growth = $dashboard['growth'];
        $atlas = $dashboard['atlas'];
    @endphp
    <div class="sidebar-layout">
        <aside class="sidebar-card">
            <div class="pill">{{ $subscription?->plan_name ?? 'Hatchers OS' }}</div>
            <div class="nav-group" style="margin-top: 18px;">
                <div class="nav-group-title">Workspace</div>
                <a class="nav-item active" href="/dashboard">Home</a>
                <a class="nav-item" href="#">Weekly Plan</a>
                <a class="nav-item" href="#">Atlas</a>
            </div>
            <div class="nav-group">
                <div class="nav-group-title">Business</div>
                <a class="nav-item" href="/website">Website</a>
                <a class="nav-item" href="#">Products</a>
                <a class="nav-item" href="#">Services</a>
                <a class="nav-item" href="#">Orders & Bookings</a>
            </div>
            <div class="nav-group">
                <div class="nav-group-title">Growth</div>
                <a class="nav-item" href="#">Marketing</a>
                <a class="nav-item" href="#">Content Studio</a>
                <a class="nav-item" href="#">Customers</a>
                <a class="nav-item" href="#">Analytics</a>
            </div>
            <div class="nav-group">
                <div class="nav-group-title">Support</div>
                <a class="nav-item" href="#">Mentor</a>
                <a class="nav-item" href="#">Settings</a>
            </div>
        </aside>

        <div>
            <section class="hero">
                <div class="eyebrow">Founder Dashboard</div>
                <h1>{{ $company?->company_name ?? $founder->full_name }} runs from one operating system.</h1>
                <p class="muted">Welcome back, {{ $founder->full_name }}. This dashboard combines execution, company intelligence, website readiness, revenue signals, and growth momentum into one founder view.</p>
                <div class="cta-row">
                    <a class="btn primary" href="/website">Open Website Workspace</a>
                    <span class="pill">Business model: {{ ucfirst($company?->business_model ?? 'hybrid') }}</span>
                    <span class="pill">Stage: {{ ucfirst($company?->stage ?? 'launching') }}</span>
                    <span class="pill">Plan: {{ $subscription?->plan_name ?? 'Hatchers OS' }}</span>
                </div>
            </section>

            <section class="metrics">
                <div class="card metric">
                    <div class="muted">Weekly Progress</div>
                    <strong>{{ $metrics['weekly_progress_percent'] }}%</strong>
                </div>
                <div class="card metric">
                    <div class="muted">Open Tasks</div>
                    <strong>{{ $metrics['open_tasks'] }}</strong>
                </div>
                <div class="card metric">
                    <div class="muted">Orders / Bookings</div>
                    <strong>{{ $metrics['orders_bookings'] }}</strong>
                </div>
                <div class="card metric">
                    <div class="muted">Revenue</div>
                    <strong>{{ $metrics['currency'] }} {{ number_format($metrics['gross_revenue'], 0) }}</strong>
                </div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>This Week</h2>
                    <div class="stack" style="margin-top: 14px;">
                        @forelse ($actions as $action)
                            <div class="stack-item">
                                <strong>{{ $action->title }}</strong><br>
                                {{ $action->description }}
                            </div>
                        @empty
                            <div class="stack-item"><strong>Complete company intelligence</strong><br>Atlas will recommend the next sprint once your company context is more complete.</div>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <h2>Module Status</h2>
                    <div class="stack" style="margin-top: 14px;">
                        @foreach ($moduleCards as $moduleCard)
                            <div class="stack-item">
                                <strong>{{ $moduleCard['module'] }} · readiness {{ $moduleCard['readiness_score'] }}%</strong><br>
                                {{ $moduleCard['description'] }}
                                @if (!empty($moduleCard['highlights']))
                                    <div class="muted" style="margin-top: 8px;">{{ implode(' · ', $moduleCard['highlights']) }}</div>
                                @endif
                                @if (!empty($moduleCard['updated_at']))
                                    <div class="muted" style="margin-top: 6px;">Updated {{ $moduleCard['updated_at'] }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Execution Pulse</h2>
                    <div class="stack" style="margin-top: 14px;">
                        <div class="stack-item">
                            <strong>Weekly focus</strong><br>
                            {{ $execution['weekly_focus'] ?: 'Atlas and LMS will surface the next sprint once more mentoring data is synced.' }}
                        </div>
                        <div class="stack-item">
                            <strong>Mentor</strong><br>
                            {{ $execution['mentor_name'] ?: 'No mentor assigned yet' }}
                        </div>
                        <div class="stack-item">
                            <strong>Tasks</strong><br>
                            {{ $execution['completed_tasks'] }} completed · {{ $execution['open_tasks'] }} open
                        </div>
                        <div class="stack-item">
                            <strong>Milestones</strong><br>
                            {{ $execution['completed_milestones'] }} completed · {{ $execution['open_milestones'] }} open
                        </div>
                        <div class="stack-item">
                            <strong>Next meeting</strong><br>
                            {{ $execution['next_meeting_at'] ?: 'No meeting synced yet' }}
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h2>Growth Pulse</h2>
                    <div class="stack" style="margin-top: 14px;">
                        <div class="stack-item">
                            <strong>Business model</strong><br>
                            {{ ucfirst($growth['business_model']) }}
                        </div>
                        <div class="stack-item">
                            <strong>Commerce</strong><br>
                            {{ $growth['product_count'] }} products · {{ $growth['order_count'] }} orders
                        </div>
                        <div class="stack-item">
                            <strong>Services</strong><br>
                            {{ $growth['service_count'] }} services · {{ $growth['booking_count'] }} bookings
                        </div>
                        <div class="stack-item">
                            <strong>Customers</strong><br>
                            {{ $growth['customer_count'] }}
                        </div>
                        <div class="stack-item">
                            <strong>Revenue</strong><br>
                            {{ $growth['gross_revenue_formatted'] }}
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                <div class="card">
                    <h2>Atlas Intelligence</h2>
                    <div class="stack" style="margin-top: 14px;">
                        <div class="stack-item">
                            <strong>Company</strong><br>
                            {{ $atlas['company_name'] ?: ($company?->company_name ?? 'Company profile still being formed') }}
                        </div>
                        <div class="stack-item">
                            <strong>Primary growth goal</strong><br>
                            {{ $atlas['primary_growth_goal'] ?: 'No explicit growth goal synced yet' }}
                        </div>
                        <div class="stack-item">
                            <strong>Brand voice</strong><br>
                            {{ $atlas['brand_voice'] ?: 'Brand voice not defined yet' }}
                        </div>
                        <div class="stack-item">
                            <strong>Generated assets</strong><br>
                            {{ $atlas['generated_posts_count'] }} posts · {{ $atlas['generated_campaigns_count'] }} campaigns · {{ $atlas['generated_images_count'] }} images
                        </div>
                        <div class="stack-item">
                            <strong>Recommended actions</strong><br>
                            {{ $atlas['recommended_actions_count'] }}
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h2>Atlas Campaigns</h2>
                    <div class="stack" style="margin-top: 14px;">
                        @forelse ($atlas['recent_campaigns'] as $campaign)
                            <div class="stack-item">
                                <strong>{{ $campaign['title'] ?? 'Campaign brief' }}</strong><br>
                                {{ $campaign['description'] ?? 'Saved in Atlas.' }}
                                @if (!empty($campaign['generated_posts_count']) || !empty($campaign['last_generated_at']))
                                    <div class="muted" style="margin-top: 6px;">
                                        {{ (int) ($campaign['generated_posts_count'] ?? 0) }} linked posts
                                        @if (!empty($campaign['last_generated_at']))
                                            · Last generated {{ $campaign['last_generated_at'] }}
                                        @endif
                                    </div>
                                @endif
                                @if (!empty($campaign['updated_at']))
                                    <div class="muted" style="margin-top: 6px;">{{ $campaign['updated_at'] }}</div>
                                @endif
                                @if (!empty($campaign['url']))
                                    <div style="margin-top: 10px;">
                                        <a class="pill" href="{{ $campaign['url'] }}" target="_blank" rel="noreferrer">Open In Atlas</a>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No saved campaigns yet</strong><br>
                                Active Atlas campaigns will appear here as founders create and refine them.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <h2>Archived Atlas Campaigns</h2>
                    <div class="stack" style="margin-top: 14px;">
                        @forelse ($atlas['archived_campaigns'] as $campaign)
                            <div class="stack-item">
                                <strong>{{ $campaign['title'] ?? 'Archived campaign' }}</strong><br>
                                {{ $campaign['description'] ?? 'Archived in Atlas.' }}
                                @if (!empty($campaign['generated_posts_count']) || !empty($campaign['last_generated_at']))
                                    <div class="muted" style="margin-top: 6px;">
                                        {{ (int) ($campaign['generated_posts_count'] ?? 0) }} linked posts
                                        @if (!empty($campaign['last_generated_at']))
                                            · Last generated {{ $campaign['last_generated_at'] }}
                                        @endif
                                    </div>
                                @endif
                                @if (!empty($campaign['updated_at']))
                                    <div class="muted" style="margin-top: 6px;">{{ $campaign['updated_at'] }}</div>
                                @endif
                                @if (!empty($campaign['url']))
                                    <div style="margin-top: 10px;">
                                        <a class="pill" href="{{ $campaign['url'] }}" target="_blank" rel="noreferrer">Open In Atlas</a>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No archived campaigns</strong><br>
                                Archived Atlas campaigns will appear here without cluttering the active OS view.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <h2>Live Activity</h2>
                    <div class="stack" style="margin-top: 14px;">
                        @forelse ($activityFeed as $event)
                            <div class="stack-item">
                                <strong>{{ $event['module'] }}</strong><br>
                                {{ $event['message'] }}
                                @if (!empty($event['updated_at']))
                                    <div class="muted" style="margin-top: 6px;">{{ $event['updated_at'] }}</div>
                                @endif
                            </div>
                        @empty
                            <div class="stack-item">
                                <strong>No activity yet</strong><br>
                                Once LMS, Atlas, Bazaar, and Servio sync activity, the OS will show a live founder timeline here.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection

@section('assistant')
    <div class="assistant" data-os-assistant>
        <div class="assistant-header">
            <div>
                <h3 style="margin: 0 0 6px;">Atlas</h3>
                <p>I know {{ $company?->company_name ?? 'your company' }}, your plan, your weekly priorities, and your latest OS state.</p>
            </div>
            <button class="assistant-toggle" type="button" data-assistant-toggle>+</button>
        </div>
        <div class="assistant-body">
            <div class="assistant-feed" data-assistant-feed>
                <div class="assistant-bubble atlas">
                    Ask Atlas what to do next, where to focus this week, or how to use Bazaar, Servio, LMS, and Atlas together.
                </div>
            </div>
            <form class="assistant-form" data-assistant-form>
                <textarea class="assistant-textarea" data-assistant-input placeholder="Ask Atlas anything about your business, execution plan, website, sales, or content..."></textarea>
                <div class="assistant-row">
                    <div class="assistant-status" data-assistant-status>Atlas is synced with your OS context.</div>
                    <button class="assistant-send" data-assistant-send type="submit">Send</button>
                </div>
            </form>
        </div>
    </div>
@endsection
