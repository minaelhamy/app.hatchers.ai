@extends('os.layout')

@section('content')
    @php
        $founder = $dashboard['founder'] ?? null;
    @endphp

    <div class="sidebar-layout">
        <aside class="sidebar-card">
            <div class="pill">Fallback Access</div>
            <div class="nav-group" style="margin-top: 18px;">
                <div class="nav-group-title">OS Navigation</div>
                <a class="nav-item" href="{{ $homeRoute }}">Workspace Home</a>
                @if ($founder && $founder->isFounder())
                    <a class="nav-item" href="{{ route('founder.commerce') }}">Launch Plan</a>
                    <a class="nav-item" href="{{ route('founder.ai-tools') }}">AI Tools</a>
                    <a class="nav-item" href="{{ route('founder.tasks') }}">Tasks</a>
                    <a class="nav-item" href="{{ route('founder.settings') }}">Settings</a>
                @endif
                @if ($founder && $founder->isMentor())
                    <a class="nav-item" href="{{ route('dashboard.mentor') }}">Mentor Overview</a>
                @endif
                <a class="nav-item active" href="#">Legacy Access</a>
            </div>
        </aside>

        <div>
            <section class="hero">
                <div class="eyebrow">Legacy Access</div>
                <h1>{{ $workspaceTitle }}</h1>
                <p class="muted">{{ $workspaceIntro }}</p>
            </section>

            <section class="card">
                <h2>Use The OS First</h2>
                <div class="stack" style="margin-top: 14px;">
                    <div class="stack-item">
                        <strong>Normal founder and mentor work should stay here</strong><br>
                        Tasks, learning, campaigns, content planning, website setup, offers, orders, bookings, notes, and portfolio review now have OS-native workspaces.
                    </div>
                    <div class="stack-item">
                        <strong>Legacy access is now a deliberate exception path</strong><br>
                        We keep these engine launches available for rare troubleshooting, backend verification, or temporary capability gaps, not as the normal product flow.
                    </div>
                </div>
            </section>

            <section class="grid-2" style="margin-top: 22px;">
                @foreach ($legacyModules as $module)
                    <div class="card">
                        <div class="pill">{{ $module['module'] }}</div>
                        <h2 style="margin-top: 12px;">{{ $module['label'] }}</h2>
                        <p class="muted">{{ $module['description'] }}</p>
                        <div class="stack" style="margin-top: 14px;">
                            <div class="stack-item">
                                <strong>Current OS status</strong><br>
                                {{ $module['status'] }}
                            </div>
                            <div class="stack-item">
                                <strong>Why fallback is still here</strong><br>
                                {{ $module['status_reason'] }}
                            </div>
                        </div>
                        <div class="cta-row" style="margin-top: 14px;">
                            <a class="btn" href="{{ $module['url'] }}">Open legacy engine</a>
                        </div>
                    </div>
                @endforeach
            </section>
        </div>
    </div>
@endsection
