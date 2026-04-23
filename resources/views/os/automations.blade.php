@extends('os.layout')

@section('content')
    <div class="public-shell">
        <section class="hero">
            <div class="eyebrow">Automations</div>
            <h1>Saved cross-tool rules for your OS.</h1>
            <p class="muted">This is the first OS-native automation layer: define a trigger, decide which module scope it watches, and record the action the OS should take.</p>
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
@endsection
