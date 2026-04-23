@extends('os.layout')

@section('content')
    @php
        $workspace = $dashboard['workspace'];
        $groups = $workspace['notification_groups'] ?? ['new' => [], 'earlier' => []];
        $actions = $workspace['next_best_actions'] ?? [];
    @endphp

    <div class="public-shell narrow">
        <section class="hero">
            <div class="eyebrow">Inbox</div>
            <h1>Your OS inbox.</h1>
            <p class="muted">One place for founder alerts, weekly execution signals, and the next actions the OS thinks matter most.</p>
        </section>

        <section class="grid-2">
            <div class="card">
                <h2>New</h2>
                <div class="stack" style="margin-top:14px;">
                    @forelse ($groups['new'] as $item)
                        <div class="stack-item">
                            <strong>{{ $item['title'] }}</strong><br>
                            <span class="muted">{{ $item['meta'] }} · {{ $item['age_label'] }}</span>
                        </div>
                    @empty
                        <div class="stack-item">
                            <strong>No new inbox items</strong><br>
                            <span class="muted">You are currently caught up across the OS.</span>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <h2>Next Best Actions</h2>
                <div class="stack" style="margin-top:14px;">
                    @forelse ($actions as $action)
                        <a href="{{ $action['href'] }}" class="stack-item" style="text-decoration:none;color:inherit;">
                            <strong>{{ $action['title'] }}</strong><br>
                            <span class="muted">{{ $action['description'] }}</span>
                            <div style="margin-top:10px;"><span class="pill">{{ $action['label'] }}</span></div>
                        </a>
                    @empty
                        <div class="stack-item">
                            <strong>No action suggestions right now</strong><br>
                            <span class="muted">The OS will surface guidance here as your tasks, campaigns, and business signals change.</span>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="card" style="margin-top:22px;">
            <h2>Earlier</h2>
            <div class="stack" style="margin-top:14px;">
                @forelse ($groups['earlier'] as $item)
                    <div class="stack-item">
                        <strong>{{ $item['title'] }}</strong><br>
                        <span class="muted">{{ $item['meta'] }} · {{ $item['age_label'] }}</span>
                    </div>
                @empty
                    <div class="stack-item">
                        <strong>No earlier inbox items</strong><br>
                        <span class="muted">As more cross-tool activity lands in the OS, the inbox timeline will grow here.</span>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
