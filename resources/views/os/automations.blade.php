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
        $founder = $dashboard['founder'];
    @endphp

    <div class="workspace-shell">
        <aside class="workspace-sidebar">
            @include('os.partials.founder-sidebar', [
                'founder' => $founder,
                'businessModel' => $founder->company->business_model ?? 'hybrid',
                'activeKey' => 'automations',
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
                @include('os.partials.guidebook-workspace-topbar', [
                    'founder' => $founder,
                    'company' => $founder->company,
                    'workspace' => $dashboard['workspace'] ?? [],
                    'projectName' => $founder->company->company_name ?? 'Founder workspace',
                    'sectionLabel' => 'Automations',
                    'searchPlaceholder' => 'Search rules, repeated work, and the next processes worth automating...',
                ])
        <section class="hero">
            <div class="eyebrow">Automations</div>
            <h1>Saved cross-tool rules for your OS.</h1>
            <p class="muted">This is the first OS-native automation layer: define a trigger, decide which module scope it watches, and record the action the OS should take.</p>
        </section>

        <section class="card" style="margin-bottom: 18px;">
            <h2>When to use this</h2>
            <p class="muted" style="margin-top: 8px;">Come here after your website, First 100, and commerce flows are already moving. Automations are best for repeated follow-up and queue cleanup, not for deciding what your offer or daily plan should be.</p>
        </section>

        @if (session('success'))
            <section class="card" style="border-color: rgba(44, 122, 87, 0.25); background: rgba(44, 122, 87, 0.06); margin-bottom: 18px;">
                <h3 style="color: var(--success);">Action completed</h3>
                <p class="muted" style="margin-top: 8px;">{{ session('success') }}</p>
            </section>
        @endif

        @if ($errors->any())
            <section class="card" style="border-color: rgba(154, 107, 27, 0.28); background: rgba(154, 107, 27, 0.08); margin-bottom: 18px;">
                <h3 style="color: var(--warning);">A few fields still need adjustment</h3>
                <div class="stack" style="margin-top: 12px;">
                    @foreach ($errors->all() as $error)
                        <div class="stack-item">{{ $error }}</div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="grid-2">
            <div class="card">
                <h2>Recommended Reminder Sequences</h2>
                <p class="muted">These presets turn the new order and booking follow-up flows into reusable OS rules instead of one-off manual actions.</p>
                <div class="stack" style="margin-top:14px;">
                    @foreach ($recommendedTemplates as $key => $template)
                        <div class="stack-item">
                            <div class="pill">{{ $scopeOptions[$template['module_scope']] ?? ucfirst($template['module_scope']) }}</div>
                            <strong style="display:block;margin-top:10px;">{{ $template['name'] }}</strong>
                            <div class="muted" style="margin-top:6px;">{{ $triggerOptions[$template['trigger_type']] ?? $template['trigger_type'] }}</div>
                            <div class="muted" style="margin-top:6px;">If {{ $template['condition_summary'] }}</div>
                            <div class="muted" style="margin-top:6px;">Then {{ $template['action_summary'] }}</div>
                            <form method="POST" action="{{ route('founder.automations.templates.store') }}" style="margin-top:12px;">
                                @csrf
                                <input type="hidden" name="template_key" value="{{ $key }}">
                                <button class="btn" type="submit">Save Template Rule</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>

            <form method="POST" action="{{ route('founder.automations.store') }}" class="card">
                @csrf
                <h2>Create Automation</h2>
                <div class="stack" style="margin-top:14px;">
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Automation name" style="width:100%;padding:12px 14px;border-radius:14px;border:1px solid var(--line);background:#fff;">
                    <select name="trigger_type" style="width:100%;padding:12px 14px;border-radius:14px;border:1px solid var(--line);background:#fff;">
                        <option value="">Choose trigger</option>
                        @foreach ($triggerOptions as $key => $label)
                            <option value="{{ $key }}" @selected(old('trigger_type') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="module_scope" style="width:100%;padding:12px 14px;border-radius:14px;border:1px solid var(--line);background:#fff;">
                        <option value="">Choose scope</option>
                        @foreach ($scopeOptions as $key => $label)
                            <option value="{{ $key }}" @selected(old('module_scope') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <textarea name="condition_summary" rows="4" placeholder="Condition summary" style="width:100%;padding:12px 14px;border-radius:14px;border:1px solid var(--line);background:#fff;resize:vertical;">{{ old('condition_summary') }}</textarea>
                    <textarea name="action_summary" rows="4" placeholder="Action summary" style="width:100%;padding:12px 14px;border-radius:14px;border:1px solid var(--line);background:#fff;resize:vertical;">{{ old('action_summary') }}</textarea>
                    <select name="status" style="width:100%;padding:12px 14px;border-radius:14px;border:1px solid var(--line);background:#fff;">
                        <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                        <option value="paused" @selected(old('status') === 'paused')>Paused</option>
                    </select>
                    <div class="cta-row">
                        <button class="btn primary" type="submit">Save Automation</button>
                    </div>
                </div>
            </form>

            <div class="card">
                <h2>Saved Rules</h2>
                <div class="stack" style="margin-top:14px;">
                    @forelse ($automations as $automation)
                        <div class="stack-item">
                            <div class="pill">{{ ucfirst($automation->status) }}</div>
                            <strong style="display:block;margin-top:10px;">{{ $automation->name }}</strong>
                            <div class="muted" style="margin-top:6px;">{{ $triggerOptions[$automation->trigger_type] ?? $automation->trigger_type }} · {{ $scopeOptions[$automation->module_scope] ?? $automation->module_scope }}</div>
                            <div class="muted" style="margin-top:6px;">If {{ $automation->condition_summary }}</div>
                            <div class="muted" style="margin-top:6px;">Then {{ $automation->action_summary }}</div>
                        </div>
                    @empty
                        <div class="stack-item">
                            <strong>No automation rules yet</strong><br>
                            <span class="muted">Saved automation rules will appear here as you create them inside Hatchers Ai Business OS.</span>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
            </div>
        </main>

        <aside class="workspace-rightbar">
            <div class="workspace-rightbar-inner">
                <h3>Automation Snapshot</h3>
                <div class="workspace-rail-list">
                    <div class="workspace-rail-item"><strong>Saved rules</strong><br><span class="muted">{{ $automations->count() }}</span></div>
                    <div class="workspace-rail-item"><strong>Recommended templates</strong><br><span class="muted">{{ count($recommendedTemplates) }}</span></div>
                    <div class="workspace-rail-item"><strong>Purpose</strong><br><span class="muted">Use automations to reduce manual follow-up across commerce and lead workflows.</span></div>
                </div>
                <h3 style="margin-top:22px;">What To Automate</h3>
                <div class="workspace-rail-list">
                    <div class="workspace-rail-item"><strong>Unpaid orders</strong><br><span class="muted">Remind customers before cash flow stalls.</span></div>
                    <div class="workspace-rail-item"><strong>Unscheduled bookings</strong><br><span class="muted">Keep service inquiries from going cold.</span></div>
                    <div class="workspace-rail-item"><strong>Lead follow-up</strong><br><span class="muted">Connect this page back to your First 100 rhythm.</span></div>
                </div>
            </div>
        </aside>
    </div>
@endsection
