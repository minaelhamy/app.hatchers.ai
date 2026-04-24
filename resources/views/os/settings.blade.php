@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page { padding: 0; }
        .settings-shell { min-height: 100vh; display:grid; grid-template-columns:220px minmax(0,1fr) 220px; background:#f8f5ee; }
        .settings-sidebar, .settings-rightbar { background: rgba(255,252,247,0.8); border-color: var(--line); border-style: solid; border-width:0 1px 0 0; min-height:100vh; display:flex; flex-direction:column; }
        .settings-rightbar { border-width:0 0 0 1px; background: rgba(255,251,246,0.9); }
        .settings-sidebar-inner, .settings-rightbar-inner { padding:22px 18px; }
        .settings-brand { display:inline-block; margin-bottom:24px; }
        .settings-brand img { width:168px; height:auto; display:block; }
        .settings-nav { display:grid; gap:6px; }
        .settings-nav-item { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:14px; text-decoration:none; color:var(--ink); font-size:0.98rem; }
        .settings-nav-item.active { background:#ece6db; }
        .settings-nav-icon { width:18px; text-align:center; color:var(--muted); }
        .settings-sidebar-footer { margin-top:auto; padding:18px; border-top:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .settings-user { display:flex; align-items:center; gap:10px; }
        .settings-avatar { width:30px; height:30px; border-radius:999px; background:#b0a999; color:#fff; display:grid; place-items:center; font-weight:700; font-size:0.92rem; flex-shrink:0; }
        .settings-main { padding:26px 28px 24px; }
        .settings-main-inner { max-width:760px; margin:0 auto; }
        .settings-main h1 { font-size: clamp(2rem, 3vw, 3rem); letter-spacing:-0.02em; margin-bottom:6px; }
        .settings-main p { color:var(--muted); margin-bottom:24px; }
        .settings-banner { border-radius:16px; padding:14px 16px; border:1px solid rgba(220,207,191,0.8); background: rgba(255,255,255,0.9); margin-bottom:14px; }
        .settings-banner.success { border-color: rgba(44,122,87,0.26); background: rgba(226,245,236,0.9); }
        .settings-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:12px; }
        .settings-card { background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:18px; padding:18px; box-shadow:0 10px 28px rgba(52,41,26,0.04); }
        .settings-card-title { font-size:1rem; font-weight:700; margin-bottom:6px; }
        .settings-card-copy { color:var(--muted); font-size:0.95rem; line-height:1.45; }
        .settings-field { display:grid; gap:8px; margin-top:14px; }
        .settings-field label { font-size:0.92rem; font-weight:600; }
        .settings-field input, .settings-field textarea, .settings-field select { width:100%; border:1px solid rgba(220,207,191,0.9); background:#fff; border-radius:12px; padding:12px 14px; font:inherit; color:var(--ink); }
        .settings-field textarea { min-height:110px; resize:vertical; }
        .settings-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:16px; }
        .settings-button { border:0; cursor:pointer; font:inherit; padding:10px 14px; border-radius:10px; font-weight:600; }
        .settings-button.primary { background:linear-gradient(90deg,#8e1c74,#ff2c35); color:#fff; }
        .settings-chip { display:inline-block; margin-top:12px; padding:8px 14px; border-radius:10px; background:#f0ece4; color:#7a7267; font-size:0.92rem; }
        .settings-rightbar h3 { font-size:0.83rem; letter-spacing:0.06em; text-transform:uppercase; color:var(--muted); margin-bottom:12px; }
        .rail-list { display:grid; gap:10px; margin-top:14px; }
        .rail-item, .mini-note { background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:14px; padding:12px 14px; }
        .field-error { color:var(--rose); font-size:0.85rem; }
        @media (max-width:1240px) { .settings-shell { grid-template-columns:220px 1fr; } .settings-rightbar { display:none; } }
        @media (max-width:900px) { .settings-shell { grid-template-columns:1fr; } .settings-sidebar { min-height:auto; border-right:0; border-bottom:1px solid var(--line); } .settings-sidebar-footer { display:none; } .settings-main { padding:20px 16px 24px; } .settings-grid { grid-template-columns:1fr; } }
    </style>
@endsection

@section('content')
    @php
        $founder = $dashboard['founder'];
        $company = $dashboard['company'];
        $subscription = $dashboard['subscription'];
    @endphp

    <div class="settings-shell">
        <aside class="settings-sidebar">
            <div class="settings-sidebar-inner">
                <a class="settings-brand" href="/dashboard/founder"><img src="/brand/hatchers-ai-logo.png" alt="Hatchers AI"></a>
                <nav class="settings-nav">
                    <a class="settings-nav-item" href="/dashboard/founder"><span class="settings-nav-icon">⌂</span><span>Home</span></a>
                    <a class="settings-nav-item" href="{{ route('founder.commerce') }}"><span class="settings-nav-icon">⌁</span><span>Commerce</span></a>
                    <a class="settings-nav-item" href="{{ route('founder.ai-tools') }}"><span class="settings-nav-icon">✦</span><span>AI Tools</span></a>
                    <a class="settings-nav-item" href="{{ route('founder.learning-plan') }}"><span class="settings-nav-icon">▣</span><span>Learning Plan</span></a>
                    <a class="settings-nav-item active" href="{{ route('founder.settings') }}"><span class="settings-nav-icon">⚙</span><span>Settings</span></a>
                </nav>
            </div>
            <div class="settings-sidebar-footer">
                <div class="settings-user">
                    <div class="settings-avatar">{{ strtoupper(substr($founder->full_name, 0, 1)) }}</div>
                    <div>{{ $founder->full_name }}</div>
                </div>
                <form method="POST" action="/logout" style="margin:0;">@csrf<button class="settings-nav-icon" type="submit" style="border:0;background:transparent;cursor:pointer;">↘</button></form>
            </div>
        </aside>

        <main class="settings-main">
            <div class="settings-main-inner">
                <h1>Settings</h1>
                <p>Update your founder identity, company profile, and plan visibility from Hatchers Ai Business OS.</p>

                @if (session('success'))
                    <div class="settings-banner success">{{ session('success') }}</div>
                @endif

                <div class="settings-grid">
                    <div class="settings-card">
                        <div class="settings-card-title">Founder And Company Profile</div>
                        <div class="settings-card-copy">Keep your core company identity current so every connected workflow in the OS has the right context.</div>

                        <form method="POST" action="{{ route('founder.settings.update') }}">
                            @csrf
                            <div class="settings-field">
                                <label for="full-name">Founder name</label>
                                <input id="full-name" name="full_name" type="text" value="{{ old('full_name', $founder->full_name) }}" required>
                                @error('full_name')<div class="field-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="settings-field">
                                <label for="phone">Phone</label>
                                <input id="phone" name="phone" type="text" value="{{ old('phone', $founder->phone) }}">
                                @error('phone')<div class="field-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="settings-field">
                                <label for="company-name">Company name</label>
                                <input id="company-name" name="company_name" type="text" value="{{ old('company_name', $company?->company_name) }}" required>
                                @error('company_name')<div class="field-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="settings-field">
                                <label for="business-model">Business model</label>
                                <select id="business-model" name="business_model" required>
                                    @foreach (['product' => 'Product business', 'service' => 'Service business', 'hybrid' => 'Hybrid business'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('business_model', $company?->business_model) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('business_model')<div class="field-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="settings-field">
                                <label for="company-brief">Company brief</label>
                                <textarea id="company-brief" name="company_brief">{{ old('company_brief', $company?->company_brief) }}</textarea>
                                @error('company_brief')<div class="field-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="settings-actions">
                                <button class="settings-button primary" type="submit">Save settings</button>
                            </div>
                        </form>
                    </div>

                    <div class="settings-card">
                        <div class="settings-card-title">Plan And Account Visibility</div>
                        <div class="settings-card-copy">The OS should show founders exactly what plan they are on and what state their subscription is in.</div>
                        <div class="settings-chip">Plan: {{ $subscription?->plan_name ?: 'No active plan yet' }}</div>
                        <div class="settings-chip">Billing state: {{ $subscription?->billing_status ?: 'Unknown' }}</div>
                        <div class="settings-chip">Account role: {{ ucfirst($founder->role) }}</div>
                        <div class="settings-chip">Username: {{ $founder->username }}</div>
                        <div class="settings-chip">Email: {{ $founder->email }}</div>
                    </div>
                </div>
            </div>
        </main>

        <aside class="settings-rightbar">
            <div class="settings-rightbar-inner">
                <h3>Profile Signals</h3>
                <div class="rail-list">
                    <div class="rail-item">
                        <div style="font-weight:600;">Company</div>
                        <div style="margin-top:4px;color:var(--muted);">{{ $company?->company_name ?: 'Not set yet' }}</div>
                    </div>
                    <div class="rail-item">
                        <div style="font-weight:600;">Business Model</div>
                        <div style="margin-top:4px;color:var(--muted);">{{ ucfirst($company?->business_model ?: 'not set') }}</div>
                    </div>
                    <div class="rail-item">
                        <div style="font-weight:600;">Current Plan</div>
                        <div style="margin-top:4px;color:var(--muted);">{{ $subscription?->plan_name ?: 'No active plan yet' }}</div>
                    </div>
                </div>

                <h3 style="margin-top:22px;">OS Direction</h3>
                <div class="mini-note">Founder identity, company profile, and subscription visibility now live in the OS. The next step is deeper billing and entitlement management without leaving Hatchers Ai Business OS.</div>
            </div>
        </aside>
    </div>
@endsection
