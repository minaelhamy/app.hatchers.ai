@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page { padding: 0; }
        .intelligence-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 220px minmax(0, 1fr);
            background:
                radial-gradient(circle at 18% 16%, rgba(255, 255, 255, 0.95), rgba(255, 248, 240, 0.82) 34%, rgba(245, 235, 224, 0.62) 74%),
                linear-gradient(140deg, #ddd1c4 0%, #eadfd4 54%, #f2e9df 100%);
        }
        .intelligence-sidebar {
            border-right: 1px solid rgba(208, 193, 175, 0.7);
            background: rgba(255, 252, 248, 0.62);
            backdrop-filter: blur(18px);
        }
        .intelligence-main {
            padding: 28px;
        }
        .intelligence-wrap {
            max-width: 980px;
            margin: 0 auto;
            display: grid;
            gap: 18px;
        }
        .intelligence-hero,
        .intelligence-card,
        .step-card,
        .checkpoint-card {
            border: 1px solid rgba(216, 202, 186, 0.78);
            background: rgba(255, 252, 248, 0.9);
            box-shadow: 0 24px 60px rgba(96, 78, 59, 0.08);
            backdrop-filter: blur(16px);
        }
        .intelligence-hero {
            border-radius: 28px;
            padding: 24px 24px 20px;
        }
        .intelligence-kicker {
            font-size: 0.79rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #9a8878;
            margin-bottom: 10px;
        }
        .intelligence-hero h1 {
            margin: 0 0 10px;
            font-size: clamp(2rem, 4vw, 3.35rem);
            line-height: 0.98;
            letter-spacing: -0.045em;
        }
        .intelligence-copy {
            max-width: 760px;
            font-size: 1rem;
            line-height: 1.7;
            color: #746657;
            margin: 0;
        }
        .intelligence-progress {
            margin-top: 18px;
            display: grid;
            gap: 10px;
        }
        .intelligence-progress-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            color: #6f6255;
            font-size: 0.94rem;
        }
        .intelligence-bar {
            height: 12px;
            border-radius: 999px;
            background: rgba(218, 204, 189, 0.58);
            overflow: hidden;
        }
        .intelligence-bar-fill {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #ff5c7d 0%, #ff7f50 45%, #f8b55c 100%);
        }
        .intelligence-layout {
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            gap: 18px;
            align-items: start;
        }
        .intelligence-card,
        .step-card,
        .checkpoint-card {
            border-radius: 26px;
        }
        .intelligence-card {
            padding: 20px;
            display: grid;
            gap: 14px;
            position: sticky;
            top: 24px;
        }
        .step-nav {
            display: grid;
            gap: 10px;
        }
        .step-link {
            text-decoration: none;
            color: inherit;
            display: grid;
            gap: 4px;
            padding: 14px 15px;
            border-radius: 18px;
            border: 1px solid rgba(219, 206, 190, 0.72);
            background: rgba(255, 255, 255, 0.62);
            transition: transform 140ms ease, border-color 140ms ease, background 140ms ease;
        }
        .step-link:hover {
            transform: translateY(-1px);
            border-color: rgba(186, 164, 139, 0.9);
        }
        .step-link.active {
            background: linear-gradient(135deg, rgba(255, 247, 240, 0.98), rgba(249, 238, 228, 0.88));
            border-color: rgba(180, 158, 135, 0.94);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.55);
        }
        .step-link.complete {
            background: rgba(245, 255, 248, 0.85);
        }
        .step-link-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #9b8b7a;
        }
        .step-link-title {
            font-size: 1rem;
            line-height: 1.35;
            font-weight: 700;
            color: #261d15;
        }
        .step-link-copy {
            font-size: 0.88rem;
            line-height: 1.5;
            color: #756859;
        }
        .step-link-status {
            width: 22px;
            height: 22px;
            border-radius: 999px;
            border: 1px solid rgba(205, 191, 174, 0.9);
            display: grid;
            place-items: center;
            font-size: 0.74rem;
            background: rgba(255,255,255,0.82);
        }
        .step-link.complete .step-link-status {
            background: #eaf7ee;
            color: #1f7a43;
            border-color: rgba(91, 160, 113, 0.35);
        }
        .step-card {
            padding: 24px;
        }
        .step-card-top {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 18px;
        }
        .step-card-kicker {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: #9a8877;
            margin-bottom: 8px;
        }
        .step-card h2 {
            margin: 0 0 8px;
            font-size: clamp(1.6rem, 3vw, 2.4rem);
            line-height: 1.02;
            letter-spacing: -0.04em;
        }
        .step-card p {
            margin: 0;
            color: #736555;
            line-height: 1.7;
            max-width: 680px;
        }
        .checkpoint-card {
            min-width: 180px;
            padding: 14px 16px;
            display: grid;
            gap: 8px;
        }
        .checkpoint-label {
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: #9a8877;
        }
        .checkpoint-value {
            font-size: 1.5rem;
            letter-spacing: -0.04em;
            font-weight: 800;
            color: #231b14;
        }
        .checkpoint-note {
            font-size: 0.9rem;
            color: #786b5c;
            line-height: 1.45;
        }
        .wizard-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .wizard-field {
            display: grid;
            gap: 8px;
        }
        .wizard-field.full {
            grid-column: 1 / -1;
        }
        .wizard-field label {
            font-size: 0.9rem;
            font-weight: 700;
            color: #31261d;
        }
        .wizard-field-note {
            font-size: 0.84rem;
            color: #8b7b6c;
            margin-top: -2px;
        }
        .wizard-field input,
        .wizard-field select,
        .wizard-field textarea {
            width: 100%;
            border: 1px solid rgba(216, 201, 184, 0.96);
            background: rgba(255, 255, 255, 0.96);
            color: #241a13;
            font: inherit;
            border-radius: 16px;
            padding: 13px 15px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.7);
        }
        .wizard-field textarea {
            min-height: 132px;
            resize: vertical;
        }
        .wizard-field input[type="file"] {
            padding: 12px;
        }
        .wizard-file-preview {
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #756655;
            font-size: 0.9rem;
        }
        .wizard-file-preview img {
            width: 66px;
            height: 66px;
            object-fit: cover;
            border-radius: 18px;
            border: 1px solid rgba(216, 201, 184, 0.92);
            background: #fff;
        }
        .wizard-actions {
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .wizard-actions-left,
        .wizard-actions-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .wizard-button,
        .wizard-link {
            border-radius: 999px;
            padding: 12px 18px;
            font: inherit;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .wizard-button {
            border: 0;
            color: #fff;
            background: linear-gradient(135deg, #14100d, #34261a);
            min-width: 170px;
            box-shadow: 0 18px 34px rgba(44, 29, 18, 0.18);
        }
        .wizard-link {
            border: 1px solid rgba(216, 201, 184, 0.95);
            color: #4d4033;
            background: rgba(255, 255, 255, 0.74);
        }
        .wizard-edit-note {
            font-size: 0.9rem;
            color: #7d6f60;
        }
        .intelligence-banner {
            border-radius: 18px;
            padding: 14px 16px;
            border: 1px solid rgba(219, 205, 188, 0.82);
            background: rgba(255,255,255,0.84);
            color: #56483b;
        }
        .intelligence-banner.success {
            background: rgba(236, 249, 240, 0.94);
            border-color: rgba(101, 165, 116, 0.34);
            color: #245032;
        }
        .field-error {
            color: #be3b58;
            font-size: 0.84rem;
        }
        @media (max-width: 1080px) {
            .intelligence-layout {
                grid-template-columns: 1fr;
            }
            .intelligence-card {
                position: static;
            }
        }
        @media (max-width: 920px) {
            .intelligence-shell {
                grid-template-columns: 1fr;
            }
            .intelligence-sidebar {
                border-right: 0;
                border-bottom: 1px solid rgba(208, 193, 175, 0.7);
            }
            .intelligence-main {
                padding: 18px 14px 24px;
            }
            .wizard-grid {
                grid-template-columns: 1fr;
            }
            .step-card-top {
                flex-direction: column;
            }
        }
    </style>
@endsection

@section('content')
    @php
        $founder = $dashboard['founder'];
        $company = $dashboard['company'];
        $subscription = $dashboard['subscription'];
        $intelligence = $intelligence ?? $company?->intelligence;
        $latestIcpProfile = $latestIcpProfile ?? $company?->icpProfiles()->latest()->first();
        $wizard = $wizard ?? [];
        $steps = collect($wizard['steps'] ?? []);
        $currentStep = $wizard['current_step'] ?? null;
        $currentStepKey = $wizard['current_step_key'] ?? 'basics';
        $logoUrl = !empty($company?->company_logo_path) ? asset('storage/' . ltrim((string) $company->company_logo_path, '/')) : null;
        $osEmbedMode = request()->boolean('os_embed');
    @endphp

    <div class="intelligence-shell">
        <aside class="intelligence-sidebar">
            @include('os.partials.founder-sidebar', [
                'founder' => $founder,
                'businessModel' => $founder->company->business_model ?? 'hybrid',
                'activeKey' => 'settings',
                'navClass' => 'settings-nav',
                'itemClass' => 'settings-nav-item',
                'iconClass' => 'settings-nav-icon',
                'innerClass' => 'settings-sidebar-inner',
                'brandClass' => 'settings-brand',
                'footerClass' => 'settings-sidebar-footer',
                'userClass' => 'settings-user',
                'avatarClass' => 'settings-avatar',
            ])
        </aside>

        <main class="intelligence-main">
            <div class="intelligence-wrap">
                @include('os.partials.guidebook-workspace-topbar', [
                    'founder' => $founder,
                    'company' => $company,
                    'workspace' => $dashboard['workspace'] ?? [],
                    'projectName' => $company?->company_name ?? 'Founder workspace',
                    'sectionLabel' => 'Brand Studio',
                    'searchPlaceholder' => 'Sharpen positioning, review intelligence, and guide what Hatchers builds next...',
                ])
                <section class="intelligence-hero">
                    <div class="intelligence-kicker">Company Intelligence</div>
                    <h1>Build the business core once, then let the whole OS use it.</h1>
                    <p class="intelligence-copy">This is the first task for every founder. Complete it before using the rest of Hatchers AI OS, then come back and refine it anytime as your business becomes clearer and stronger.</p>
                    <div class="intelligence-progress">
                        <div class="intelligence-progress-top">
                            <span>{{ $wizard['completed_steps'] ?? 0 }} of {{ $wizard['total_steps'] ?? 4 }} steps complete</span>
                            <strong>{{ $wizard['completion_percent'] ?? 0 }}%</strong>
                        </div>
                        <div class="intelligence-bar">
                            <div class="intelligence-bar-fill" style="width: {{ $wizard['completion_percent'] ?? 0 }}%;"></div>
                        </div>
                    </div>
                </section>

                @if (session('success'))
                    <div class="intelligence-banner success">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="intelligence-banner">{{ session('error') }}</div>
                @endif

                <section class="intelligence-layout">
                    <aside class="intelligence-card">
                        <div class="intelligence-kicker" style="margin-bottom:2px;">Step Guide</div>
                        <div style="font-size:1rem;font-weight:700;color:#271d15;">Complete each step in order.</div>
                        <div style="font-size:0.92rem;line-height:1.6;color:#766859;">Everything here stays editable later, but this foundation needs to be complete before the founder can use the rest of the system.</div>

                        <div class="step-nav">
                            @foreach ($steps as $step)
                                @php
                                    $isActive = ($step['key'] ?? '') === $currentStepKey;
                                @endphp
                                <a
                                    class="step-link{{ !empty($step['is_complete']) ? ' complete' : '' }}{{ $isActive ? ' active' : '' }}"
                                    href="{{ route('founder.settings', array_filter(['step' => $step['key'], 'os_embed' => $osEmbedMode ? 1 : null])) }}"
                                >
                                    <div class="step-link-top">
                                        <span>Step {{ $loop->iteration }}</span>
                                        <span class="step-link-status">{{ !empty($step['is_complete']) ? '✓' : $loop->iteration }}</span>
                                    </div>
                                    <div class="step-link-title">{{ $step['label'] }}</div>
                                    <div class="step-link-copy">{{ $step['copy'] }}</div>
                                </a>
                            @endforeach
                        </div>

                        <div class="intelligence-banner" style="margin-top:2px;">
                            <strong style="display:block;margin-bottom:4px;">Plan</strong>
                            <span style="font-size:0.94rem;color:#77695a;">{{ $subscription?->plan_name ?: 'No active plan yet' }}</span>
                        </div>
                    </aside>

                    <section class="step-card">
                        <div class="step-card-top">
                            <div>
                                <div class="step-card-kicker">Current step</div>
                                <h2>{{ $currentStep['headline'] ?? 'Complete Company Intelligence' }}</h2>
                                <p>{{ $currentStep['copy'] ?? 'Fill this out once, then keep improving it over time as you learn more about your business.' }}</p>
                            </div>

                            <div class="checkpoint-card">
                                <div class="checkpoint-label">Checkpoint</div>
                                <div class="checkpoint-value">{{ $wizard['completion_percent'] ?? 0 }}%</div>
                                <div class="checkpoint-note">
                                    @if (!empty($wizard['is_complete']))
                                        Company Intelligence is complete and ready to power the OS.
                                    @else
                                        Finish this step, then we’ll move you to the next incomplete part automatically.
                                    @endif
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('founder.settings.update') }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="current_step" value="{{ $currentStepKey }}">
                            @if ($osEmbedMode)
                                <input type="hidden" name="os_embed" value="1">
                            @endif

                            @if ($currentStepKey === 'basics')
                                <div class="wizard-grid">
                                    <div class="wizard-field">
                                        <label for="full-name">Founder name</label>
                                        <input id="full-name" name="full_name" type="text" value="{{ old('full_name', $founder->full_name) }}" required>
                                        @error('full_name')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="wizard-field">
                                        <label for="phone">Phone</label>
                                        <input id="phone" name="phone" type="text" value="{{ old('phone', $founder->phone) }}">
                                        @error('phone')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="wizard-field">
                                        <label for="company-name">Company name</label>
                                        <input id="company-name" name="company_name" type="text" value="{{ old('company_name', $company?->company_name) }}" required>
                                        @error('company_name')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="wizard-field">
                                        <label for="business-model">Business model</label>
                                        <select id="business-model" name="business_model" required>
                                            @foreach ($businessModelOptions as $value => $label)
                                                <option value="{{ $value }}" @selected(old('business_model', $company?->business_model) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('business_model')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="wizard-field">
                                        <label for="vertical-blueprint">Business blueprint</label>
                                        <select id="vertical-blueprint" name="vertical_blueprint" required>
                                            @foreach ($verticalBlueprintOptions as $verticalBlueprint)
                                                <option value="{{ $verticalBlueprint['code'] }}" @selected(old('vertical_blueprint', $company?->verticalBlueprint?->code) === $verticalBlueprint['code'])>{{ $verticalBlueprint['name'] }} · {{ ucfirst($verticalBlueprint['business_model']) }}</option>
                                            @endforeach
                                        </select>
                                        @error('vertical_blueprint')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="wizard-field">
                                        <label for="industry">Industry</label>
                                        <select id="industry" name="industry" required>
                                            @foreach ($industryOptions as $option)
                                                <option value="{{ $option }}" @selected(old('industry', $company?->industry) === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        @error('industry')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="wizard-field">
                                        <label for="stage">Stage</label>
                                        <select id="stage" name="stage" required>
                                            @foreach ($stageOptions as $value => $label)
                                                <option value="{{ $value }}" @selected(old('stage', $company?->stage) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('stage')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="wizard-field">
                                        <label for="primary-city">Primary city / market</label>
                                        <input id="primary-city" name="primary_city" type="text" value="{{ old('primary_city', $company?->primary_city) }}" required>
                                        @error('primary_city')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="wizard-field">
                                        <label for="service-radius">Service radius / delivery scope</label>
                                        <input id="service-radius" name="service_radius" type="text" value="{{ old('service_radius', $company?->service_radius) }}" required>
                                        @error('service_radius')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="wizard-field full">
                                        <label for="company-brief">What does the business do?</label>
                                        <div class="wizard-field-note">Keep it simple and clear. This becomes the seed for the rest of the system.</div>
                                        <textarea id="company-brief" name="company_brief" required>{{ old('company_brief', $company?->company_brief) }}</textarea>
                                        @error('company_brief')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="wizard-field full">
                                        <label for="company-logo">Logo</label>
                                        <input id="company-logo" name="company_logo" type="file" accept="image/*">
                                        @error('company_logo')<div class="field-error">{{ $message }}</div>@enderror
                                        @if ($logoUrl)
                                            <div class="wizard-file-preview">
                                                <img src="{{ $logoUrl }}" alt="{{ $company?->company_name ?: 'Company logo' }}">
                                                <span>Current company logo</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @elseif ($currentStepKey === 'audience')
                                <div class="wizard-grid">
                                    <div class="wizard-field full">
                                        <label for="target-audience">Target audience</label>
                                        <select id="target-audience" name="target_audience" required>
                                            @foreach ($targetAudienceOptions as $option)
                                                <option value="{{ $option }}" @selected(old('target_audience', $intelligence?->target_audience) === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        @error('target_audience')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="wizard-field full">
                                        <label for="primary-icp-name">Primary ideal customer</label>
                                        <input id="primary-icp-name" name="primary_icp_name" type="text" value="{{ old('primary_icp_name', $intelligence?->primary_icp_name) }}" required>
                                        @error('primary_icp_name')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="wizard-field full">
                                        <label for="ideal-customer-profile">Ideal customer profile</label>
                                        <div class="wizard-field-note">Who are they, what stage are they at, and what makes them a strong fit?</div>
                                        <textarea id="ideal-customer-profile" name="ideal_customer_profile" required>{{ old('ideal_customer_profile', $intelligence?->ideal_customer_profile) }}</textarea>
                                        @error('ideal_customer_profile')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="wizard-field full">
                                        <label for="problem-solved">Problem solved</label>
                                        <div class="wizard-field-note">What pain, friction, or goal is the business helping this customer solve?</div>
                                        <textarea id="problem-solved" name="problem_solved" required>{{ old('problem_solved', $intelligence?->problem_solved) }}</textarea>
                                        @error('problem_solved')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="wizard-field full">
                                        <label for="pain-points">Top pain points</label>
                                        <div class="wizard-field-note">Keep the same comma-separated list from founder signup so your ICP stays consistent everywhere.</div>
                                        <textarea id="pain-points" name="pain_points" required>{{ old('pain_points', implode(', ', is_array($latestIcpProfile?->pain_points_json) ? $latestIcpProfile->pain_points_json : [])) }}</textarea>
                                        @error('pain_points')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            @elseif ($currentStepKey === 'offer')
                                <div class="wizard-grid">
                                    <div class="wizard-field full">
                                        <label for="core-offer">Core offer</label>
                                        <select id="core-offer" name="core_offer" required>
                                            @foreach ($coreOfferOptions as $option)
                                                <option value="{{ $option }}" @selected(old('core_offer', $intelligence?->core_offer) === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        @error('core_offer')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="wizard-field full">
                                        <label for="differentiators">Why people choose you</label>
                                        <textarea id="differentiators" name="differentiators" required>{{ old('differentiators', $intelligence?->differentiators) }}</textarea>
                                        @error('differentiators')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="wizard-field full">
                                        <label for="desired-outcomes">Desired outcomes</label>
                                        <textarea id="desired-outcomes" name="desired_outcomes" required>{{ old('desired_outcomes', implode(', ', is_array($latestIcpProfile?->desired_outcomes_json) ? $latestIcpProfile->desired_outcomes_json : [])) }}</textarea>
                                        @error('desired_outcomes')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="wizard-field full">
                                        <label for="objections">Common objections</label>
                                        <textarea id="objections" name="objections" required>{{ old('objections', $intelligence?->objections) }}</textarea>
                                        @error('objections')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            @elseif ($currentStepKey === 'brand')
                                <div class="wizard-grid">
                                    <div class="wizard-field">
                                        <label for="brand-voice">Brand voice</label>
                                        <select id="brand-voice" name="brand_voice" required>
                                            @foreach ($brandVoiceOptions as $option)
                                                <option value="{{ $option }}" @selected(old('brand_voice', $intelligence?->brand_voice) === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        @error('brand_voice')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="wizard-field">
                                        <label for="visual-style">Visual style</label>
                                        <input id="visual-style" name="visual_style" type="text" value="{{ old('visual_style', $intelligence?->visual_style) }}" required>
                                        @error('visual_style')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="wizard-field full">
                                        <label for="primary-growth-goal">Primary growth goal</label>
                                        <select id="primary-growth-goal" name="primary_growth_goal" required>
                                            @foreach ($growthGoalOptions as $option)
                                                <option value="{{ $option }}" @selected(old('primary_growth_goal', $intelligence?->primary_growth_goal) === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        @error('primary_growth_goal')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="wizard-field full">
                                        <label for="known-blockers">Known blockers</label>
                                        <select id="known-blockers" name="known_blockers" required>
                                            @foreach ($knownBlockerOptions as $option)
                                                <option value="{{ $option }}" @selected(old('known_blockers', $intelligence?->known_blockers) === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        @error('known_blockers')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="wizard-field full">
                                        <label for="local-market-notes">Local market notes</label>
                                        <div class="wizard-field-note">Optional. Add any local context, channel realities, or market notes worth remembering.</div>
                                        <textarea id="local-market-notes" name="local_market_notes">{{ old('local_market_notes', $intelligence?->local_market_notes) }}</textarea>
                                        @error('local_market_notes')<div class="field-error">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            @endif

                            <div class="wizard-actions">
                                <div class="wizard-actions-left">
                                    @php
                                        $stepKeys = $steps->pluck('key')->values();
                                        $currentIndex = $stepKeys->search($currentStepKey);
                                        $previousKey = $currentIndex !== false && $currentIndex > 0 ? $stepKeys[$currentIndex - 1] : null;
                                    @endphp
                                    @if ($previousKey)
                                        <a class="wizard-link" href="{{ route('founder.settings', array_filter(['step' => $previousKey, 'os_embed' => $osEmbedMode ? 1 : null])) }}">Back</a>
                                    @endif
                                    <span class="wizard-edit-note">You can return and edit this at any time as the business grows.</span>
                                </div>
                                <div class="wizard-actions-right">
                                    <button class="wizard-button" type="submit">
                                        @if (!empty($wizard['is_complete']) && $currentStepKey === 'brand')
                                            Save Company Intelligence
                                        @else
                                            Save and continue
                                        @endif
                                    </button>
                                </div>
                            </div>
                        </form>
                    </section>
                </section>
            </div>
        </main>
    </div>
@endsection
