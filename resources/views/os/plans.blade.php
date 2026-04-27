@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'auth-entry-page')

@section('head')
    <style>
        .page.auth-entry-page {
            min-height: 100vh;
            padding: 0;
            font-family: "Inter", "Avenir Next", "Segoe UI", sans-serif;
        }

        .plans-scene {
            min-height: 100vh;
            padding: 28px;
            background:
                radial-gradient(circle at 82% 14%, rgba(234, 187, 199, 0.26), transparent 0 18%),
                linear-gradient(165deg, #ddd2c8 0%, #c8b8b0 100%);
        }

        .plans-scene::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.11) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.11) 1px, transparent 1px);
            background-size: 88px 88px;
            opacity: 0.28;
        }

        .plans-frame {
            position: relative;
            z-index: 1;
            min-height: calc(100vh - 56px);
            padding: 22px;
            border-radius: 28px;
            border: 1px solid rgba(214, 201, 184, 0.84);
            background:
                radial-gradient(circle at 78% 18%, rgba(234, 197, 201, 0.2), transparent 0 18%),
                linear-gradient(180deg, rgba(246, 239, 232, 0.94), rgba(237, 227, 218, 0.96));
            box-shadow:
                0 18px 54px rgba(71, 52, 31, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.72);
        }

        .plans-frame.is-transitioning {
            overflow: hidden;
        }

        .plans-header {
            display: grid;
            grid-template-columns: minmax(320px, 0.95fr) minmax(280px, 0.7fr);
            gap: 22px;
            margin-bottom: 22px;
        }

        .plans-hero,
        .plans-sidecard,
        .plans-card,
        .plans-notice {
            border-radius: 26px;
            border: 1px solid rgba(223, 211, 198, 0.9);
            background: rgba(255, 252, 248, 0.72);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.82);
            backdrop-filter: blur(16px);
        }

        .plans-hero,
        .plans-sidecard,
        .plans-notice {
            padding: 24px;
        }

        .plans-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #2c241f;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 18px;
        }

        .plans-brand-mark {
            width: 22px;
            height: 22px;
            border-radius: 7px;
            background: linear-gradient(135deg, #e11d74, #ef4444);
            box-shadow: 0 10px 26px rgba(225, 29, 116, 0.28);
        }

        .plans-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            font-size: 0.72rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: rgba(116, 97, 86, 0.72);
        }

        .plans-eyebrow::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: rgba(225, 29, 116, 0.7);
        }

        .plans-hero h1 {
            margin: 0 0 16px;
            font-family: "Inter Tight", "Inter", sans-serif;
            font-size: clamp(2.6rem, 4vw, 4.2rem);
            line-height: 0.96;
            letter-spacing: -0.06em;
            color: #171310;
        }

        .plans-copy,
        .plans-sidecopy,
        .plans-feature {
            color: rgba(92, 76, 67, 0.84);
            line-height: 1.62;
        }

        .plans-test-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 16px;
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid rgba(223, 210, 197, 0.92);
            background: rgba(255,255,255,0.74);
            color: rgba(90, 72, 62, 0.88);
            font-size: 0.84rem;
            font-weight: 600;
        }

        .plans-test-pill::before {
            content: "";
            width: 9px;
            height: 9px;
            border-radius: 999px;
            background: #68c06a;
        }

        .plans-sidecard {
            display: grid;
            gap: 16px;
            align-content: start;
        }

        .plans-sidekicker {
            font-size: 0.72rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(117, 100, 89, 0.7);
        }

        .plans-sidecard strong {
            display: block;
            font-family: "Inter Tight", "Inter", sans-serif;
            font-size: 1.18rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: #171310;
        }

        .plans-sidepoints {
            display: grid;
            gap: 10px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .plans-sidepoints li {
            position: relative;
            padding-left: 18px;
            color: rgba(85, 70, 61, 0.88);
        }

        .plans-sidepoints li::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0.55em;
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: rgba(225, 29, 116, 0.68);
        }

        .plans-notice {
            margin-bottom: 22px;
        }

        .plans-notice.error {
            border-color: rgba(179, 34, 83, 0.25);
            background: rgba(179, 34, 83, 0.08);
        }

        .plans-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.75fr) minmax(300px, 0.95fr);
            gap: 18px;
        }

        .plans-card-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .plans-card {
            display: grid;
            gap: 16px;
            padding: 22px;
            position: relative;
            overflow: hidden;
            transition:
                transform 180ms ease,
                border-color 180ms ease,
                box-shadow 180ms ease,
                background 180ms ease;
            cursor: pointer;
        }

        .plans-card::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(145deg, rgba(255,255,255,0.15), transparent 48%);
            pointer-events: none;
        }

        .plans-card:hover,
        .plans-card:focus-within,
        .plans-card.is-active {
            transform: translateY(-4px);
            border-color: rgba(200, 180, 162, 0.95);
            background: rgba(255, 252, 248, 0.84);
            box-shadow:
                0 20px 36px rgba(86, 64, 45, 0.08),
                inset 0 1px 0 rgba(255,255,255,0.86);
        }

        .plans-frame.is-transitioning .plans-card:not(.is-active),
        .plans-frame.is-transitioning .plans-hero,
        .plans-frame.is-transitioning .plans-sidecard:not(.plans-inspector),
        .plans-frame.is-transitioning .plans-notice {
            opacity: 0.18;
            transform: scale(0.985);
            transition: opacity 260ms ease, transform 260ms ease;
        }

        .plans-pill {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid rgba(221, 208, 194, 0.94);
            background: rgba(244, 238, 229, 0.9);
            font-size: 0.82rem;
            color: rgba(98, 83, 73, 0.86);
        }

        .plans-card-topline {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .plans-status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: rgba(110, 92, 81, 0.8);
            font-size: 0.78rem;
            font-weight: 600;
        }

        .plans-status::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: rgba(106, 192, 108, 0.84);
        }

        .plans-card-copy {
            display: grid;
            gap: 8px;
        }

        .plans-card h2 {
            margin: 0;
            font-family: "Inter Tight", "Inter", sans-serif;
            font-size: 1.5rem;
            line-height: 1.02;
            letter-spacing: -0.04em;
            color: #171310;
        }

        .plans-price {
            font-family: "Inter Tight", "Inter", sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: -0.06em;
            color: #171310;
        }

        .plans-price span {
            font-size: 0.98rem;
            font-weight: 500;
            letter-spacing: -0.01em;
            color: rgba(103, 87, 77, 0.9);
        }

        .plans-microgrid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .plans-microcard {
            padding: 12px 13px;
            border-radius: 18px;
            border: 1px solid rgba(224, 212, 198, 0.88);
            background: rgba(250, 246, 240, 0.72);
        }

        .plans-microcard span {
            display: block;
            font-size: 0.72rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgba(116, 98, 87, 0.64);
            margin-bottom: 6px;
        }

        .plans-microcard strong {
            display: block;
            color: #1e1714;
            font-size: 0.96rem;
            letter-spacing: -0.02em;
        }

        .plans-feature-list {
            display: grid;
            gap: 10px;
        }

        .plans-feature {
            padding: 13px 14px;
            border-radius: 18px;
            border: 1px solid rgba(224, 212, 198, 0.88);
            background: rgba(255,255,255,0.66);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.78);
        }

        .plans-actions {
            margin-top: auto;
        }

        .plans-cta {
            display: inline-block;
            width: 100%;
            text-align: center;
            text-decoration: none;
            padding: 14px 18px;
            border-radius: 999px;
            background: linear-gradient(180deg, #181310, #2b221d);
            color: #fff;
            font-weight: 700;
            box-shadow: 0 14px 28px rgba(33, 25, 20, 0.15);
            transition: transform 180ms ease, box-shadow 180ms ease, background 180ms ease;
        }

        .plans-cta:hover,
        .plans-cta:focus-visible {
            transform: translateY(-1px);
            box-shadow: 0 18px 32px rgba(33, 25, 20, 0.18);
            background: linear-gradient(180deg, #201915, #342922);
        }

        .plans-inspector {
            position: sticky;
            top: 22px;
            display: grid;
            gap: 16px;
            align-self: start;
            padding: 22px;
        }

        .plans-inspector h3 {
            margin: 0;
            font-family: "Inter Tight", "Inter", sans-serif;
            font-size: 1.7rem;
            letter-spacing: -0.04em;
            color: #171310;
        }

        .plans-inspector-price {
            font-family: "Inter Tight", "Inter", sans-serif;
            font-size: 2rem;
            letter-spacing: -0.05em;
            color: #171310;
        }

        .plans-inspector-price span {
            font-size: 0.92rem;
            color: rgba(103, 87, 77, 0.88);
        }

        .plans-inspector-grid {
            display: grid;
            gap: 12px;
        }

        .plans-inspector-section {
            padding: 15px 16px;
            border-radius: 20px;
            border: 1px solid rgba(224, 212, 198, 0.88);
            background: rgba(255,255,255,0.62);
        }

        .plans-inspector-label {
            display: block;
            margin-bottom: 7px;
            font-size: 0.72rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: rgba(116, 98, 87, 0.64);
        }

        .plans-inspector-value {
            color: #201814;
            font-weight: 600;
            line-height: 1.5;
        }

        .plans-inspector-list {
            display: grid;
            gap: 10px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .plans-inspector-list li {
            position: relative;
            padding-left: 18px;
            color: rgba(85, 70, 61, 0.88);
            line-height: 1.52;
        }

        .plans-inspector-list li::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0.58em;
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: rgba(225, 29, 116, 0.62);
        }

        .plans-launch-overlay {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: grid;
            place-items: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 260ms ease;
            background:
                radial-gradient(circle at 50% 42%, rgba(244, 229, 220, 0.84), rgba(214, 195, 183, 0.94) 58%, rgba(202, 182, 171, 0.98) 100%);
            backdrop-filter: blur(16px);
        }

        .plans-launch-overlay.is-visible {
            opacity: 1;
        }

        .plans-launch-core {
            display: grid;
            gap: 16px;
            justify-items: center;
            text-align: center;
            transform: translateY(14px) scale(0.96);
            transition: transform 320ms ease;
        }

        .plans-launch-overlay.is-visible .plans-launch-core {
            transform: translateY(0) scale(1);
        }

        .plans-launch-mark {
            width: 94px;
            height: 94px;
            border-radius: 28px;
            background: linear-gradient(145deg, #ec2d70, #f24c44);
            box-shadow:
                0 22px 48px rgba(225, 29, 116, 0.22),
                0 0 0 12px rgba(255,255,255,0.16);
        }

        .plans-launch-title {
            font-family: "Inter Tight", "Inter", sans-serif;
            font-size: 2rem;
            letter-spacing: -0.05em;
            color: #211915;
        }

        .plans-launch-copy {
            color: rgba(92, 76, 67, 0.82);
            font-size: 1rem;
            line-height: 1.55;
        }

        .plans-launch-line {
            width: 180px;
            height: 6px;
            border-radius: 999px;
            background: rgba(255,255,255,0.52);
            overflow: hidden;
        }

        .plans-launch-line::after {
            content: "";
            display: block;
            width: 42%;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #ef2c66, #ff8b5e);
            animation: plans-launch-sweep 920ms ease-in-out infinite;
        }

        @keyframes plans-launch-sweep {
            0% { transform: translateX(-110%); }
            100% { transform: translateX(260%); }
        }

        @media (max-width: 1080px) {
            .plans-header,
            .plans-grid,
            .plans-card-grid {
                grid-template-columns: 1fr;
            }

            .plans-inspector {
                position: static;
            }
        }
    </style>
@endsection

@section('content')
    @php
        $planMeta = [
            'hatchers-os-trial' => [
                'status' => 'Fastest way to try the OS',
                'best_for' => 'Founders validating fit before committing',
                'support' => 'Founder-only exploration without mentor guidance',
                'pace' => '7-day focused trial',
                'workspace_focus' => 'Desktop, AI mentor, website setup, and early workflow testing',
                'decision_note' => 'Best if you want to feel the OS in motion first, then upgrade once it clicks.',
            ],
            'hatchers-os' => [
                'status' => 'Most balanced starting point',
                'best_for' => 'Self-directed founders ready to run inside the OS now',
                'support' => 'Full operating system with AI support but no assigned mentor',
                'pace' => 'Month-to-month self-serve execution',
                'workspace_focus' => 'Commerce, website, marketing, AI Studio, and operating rhythm',
                'decision_note' => 'Best if you already know you want the full OS and prefer to drive your own pace.',
            ],
            'hatchers-os-mentor' => [
                'status' => 'Highest-support guided path',
                'best_for' => 'Founders who want accountability, structure, and weekly momentum',
                'support' => 'Mentor-guided execution layered on top of the full OS',
                'pace' => 'Guided growth for the first 6 months, then standard OS rhythm',
                'workspace_focus' => 'Everything in the OS, plus mentor-led priorities and execution checkpoints',
                'decision_note' => 'Best if you want help turning insight into action without losing momentum.',
            ],
        ];
        $defaultPlan = $plans[1] ?? $plans[0] ?? null;
        $defaultMeta = $defaultPlan ? ($planMeta[$defaultPlan['code']] ?? []) : [];
    @endphp
    <div class="plans-scene">
        <section class="plans-frame">
            <div class="plans-header">
                <div class="plans-hero">
                    <a class="plans-brand" href="/">
                        <span class="plans-brand-mark"></span>
                        <span>Hatchers OS</span>
                    </a>
                    <div class="plans-eyebrow">Plans</div>
                    <h1>Choose how you want to build with Hatchers OS.</h1>
                    <p class="plans-copy">
                        Founders can start with a free trial, choose the self-serve operating system, or build with mentor guidance, all inside the same OS experience.
                    </p>
                    @if (config('app.disable_auth_verification'))
                        <div class="plans-test-pill">Test mode active · signup and login verification are disabled</div>
                    @endif
                </div>

                <aside class="plans-sidecard">
                    <span class="plans-sidekicker">What changes after signup</span>
                    <strong>Your founder desktop, AI mentor, website, commerce, and execution flow all start from the same profile.</strong>
                    <p class="plans-sidecopy">Pick the plan that matches how much support you want around the OS, then continue into onboarding once.</p>
                    <ul class="plans-sidepoints">
                        <li>OS-native founder desktop</li>
                        <li>Atlas mentor with live founder context</li>
                        <li>Website, tasks, learning, marketing, and commerce in one place</li>
                    </ul>
                </aside>
            </div>

            @if (session('error'))
                <section class="plans-notice error">
                    <h3 style="margin: 0; color: var(--rose);">Please choose a founder plan first</h3>
                    <p class="plans-copy" style="margin-top: 8px;">{{ session('error') }}</p>
                </section>
            @endif

            <section class="plans-grid">
                <div class="plans-card-grid">
                    @foreach ($plans as $index => $plan)
                        @php
                            $meta = $planMeta[$plan['code']] ?? [];
                            $isDefault = $defaultPlan && $defaultPlan['code'] === $plan['code'];
                        @endphp
                        <article
                            class="plans-card {{ $isDefault ? 'is-active' : '' }}"
                            tabindex="0"
                            data-plan-card
                            data-plan-name="{{ $plan['name'] }}"
                            data-plan-price="{{ $plan['price_display'] }}"
                            data-plan-period="{{ $plan['period_display'] }}"
                            data-plan-description="{{ $plan['description'] }}"
                            data-plan-best-for="{{ $meta['best_for'] ?? '' }}"
                            data-plan-support="{{ $meta['support'] ?? '' }}"
                            data-plan-pace="{{ $meta['pace'] ?? '' }}"
                            data-plan-workspace-focus="{{ $meta['workspace_focus'] ?? '' }}"
                            data-plan-decision-note="{{ $meta['decision_note'] ?? '' }}"
                            data-plan-cta="{{ $plan['cta'] }}"
                            data-plan-href="{{ route('onboarding', ['plan' => $plan['code']]) }}"
                            data-plan-features='@json(array_values($plan["features"]))'
                        >
                            <div class="plans-card-topline">
                                <div class="plans-pill">{{ $plan['label'] }}</div>
                                <div class="plans-status">{{ $meta['status'] ?? 'Founder OS access' }}</div>
                            </div>
                            <div class="plans-card-copy">
                                <h2>{{ $plan['name'] }}</h2>
                                <div class="plans-price">
                                    {{ $plan['price_display'] }}<span>{{ $plan['period_display'] }}</span>
                                </div>
                                <p class="plans-copy">{{ $plan['description'] }}</p>
                            </div>
                            <div class="plans-microgrid">
                                <div class="plans-microcard">
                                    <span>Best for</span>
                                    <strong>{{ $meta['best_for'] ?? 'Founder OS access' }}</strong>
                                </div>
                                <div class="plans-microcard">
                                    <span>Pace</span>
                                    <strong>{{ $meta['pace'] ?? 'Flexible monthly pace' }}</strong>
                                </div>
                            </div>
                            <div class="plans-feature-list">
                                @foreach ($plan['features'] as $feature)
                                    <div class="plans-feature">{{ $feature }}</div>
                                @endforeach
                            </div>
                            <div class="plans-actions">
                                <a class="plans-cta" href="{{ route('onboarding', ['plan' => $plan['code']]) }}">{{ $plan['cta'] }}</a>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($defaultPlan)
                    <aside class="plans-sidecard plans-inspector" data-plan-inspector>
                        <div class="plans-eyebrow">Plan comparison</div>
                        <h3 data-plan-output="name">{{ $defaultPlan['name'] }}</h3>
                        <div class="plans-inspector-price">
                            <span data-plan-output="price">{{ $defaultPlan['price_display'] }}</span><span data-plan-output="period">{{ $defaultPlan['period_display'] }}</span>
                        </div>
                        <p class="plans-copy" data-plan-output="description">{{ $defaultPlan['description'] }}</p>

                        <div class="plans-inspector-grid">
                            <section class="plans-inspector-section">
                                <span class="plans-inspector-label">Best for</span>
                                <div class="plans-inspector-value" data-plan-output="best-for">{{ $defaultMeta['best_for'] ?? '' }}</div>
                            </section>

                            <section class="plans-inspector-section">
                                <span class="plans-inspector-label">Support style</span>
                                <div class="plans-inspector-value" data-plan-output="support">{{ $defaultMeta['support'] ?? '' }}</div>
                            </section>

                            <section class="plans-inspector-section">
                                <span class="plans-inspector-label">Workspace focus</span>
                                <div class="plans-inspector-value" data-plan-output="workspace-focus">{{ $defaultMeta['workspace_focus'] ?? '' }}</div>
                            </section>

                            <section class="plans-inspector-section">
                                <span class="plans-inspector-label">Included in this mode</span>
                                <ul class="plans-inspector-list" data-plan-output="features">
                                    @foreach ($defaultPlan['features'] as $feature)
                                        <li>{{ $feature }}</li>
                                    @endforeach
                                </ul>
                            </section>

                            <section class="plans-inspector-section">
                                <span class="plans-inspector-label">Decision note</span>
                                <div class="plans-inspector-value" data-plan-output="decision-note">{{ $defaultMeta['decision_note'] ?? '' }}</div>
                            </section>
                        </div>

                        <div class="plans-actions">
                            <a class="plans-cta" href="{{ route('onboarding', ['plan' => $defaultPlan['code']]) }}" data-plan-output="cta-link">{{ $defaultPlan['cta'] }}</a>
                        </div>
                    </aside>
                @endif
            </section>
        </section>
    </div>

    <div class="plans-launch-overlay" data-plan-launch-overlay aria-hidden="true">
        <div class="plans-launch-core">
            <div class="plans-launch-mark"></div>
            <div class="plans-launch-title" data-plan-launch-title>Entering Hatchers OS</div>
            <div class="plans-launch-copy" data-plan-launch-copy>Preparing your founder workspace…</div>
            <div class="plans-launch-line"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const cards = Array.from(document.querySelectorAll('[data-plan-card]'));
            const inspector = document.querySelector('[data-plan-inspector]');
            const frame = document.querySelector('.plans-frame');
            const overlay = document.querySelector('[data-plan-launch-overlay]');
            const overlayTitle = document.querySelector('[data-plan-launch-title]');
            const overlayCopy = document.querySelector('[data-plan-launch-copy]');
            let transitionLocked = false;

            if (!cards.length || !inspector) {
                return;
            }

            const outputs = {
                name: inspector.querySelector('[data-plan-output="name"]'),
                price: inspector.querySelector('[data-plan-output="price"]'),
                period: inspector.querySelector('[data-plan-output="period"]'),
                description: inspector.querySelector('[data-plan-output="description"]'),
                'best-for': inspector.querySelector('[data-plan-output="best-for"]'),
                support: inspector.querySelector('[data-plan-output="support"]'),
                'workspace-focus': inspector.querySelector('[data-plan-output="workspace-focus"]'),
                'decision-note': inspector.querySelector('[data-plan-output="decision-note"]'),
                features: inspector.querySelector('[data-plan-output="features"]'),
                ctaLink: inspector.querySelector('[data-plan-output="cta-link"]'),
            };

            const beginTransition = (card, href) => {
                if (!href || transitionLocked) {
                    return;
                }

                transitionLocked = true;
                activateCard(card);

                if (frame) {
                    frame.classList.add('is-transitioning');
                }

                const planName = card.dataset.planName || 'Hatchers OS';
                const bestFor = card.dataset.planBestFor || 'founder execution';

                if (overlay) {
                    overlay.setAttribute('aria-hidden', 'false');
                    overlay.classList.add('is-visible');
                }

                if (overlayTitle) {
                    overlayTitle.textContent = `Entering ${planName}`;
                }

                if (overlayCopy) {
                    overlayCopy.textContent = `Setting up your founder workspace for ${bestFor.toLowerCase()}.`;
                }

                try {
                    sessionStorage.setItem('hatchersPlanLaunchTransition', JSON.stringify({
                        planName,
                        planLabel: card.querySelector('.plans-pill')?.textContent?.trim() || '',
                        planBestFor: bestFor,
                        at: Date.now()
                    }));
                } catch (error) {
                    // Ignore storage issues and continue navigation.
                }

                window.setTimeout(() => {
                    window.location.href = href;
                }, 420);
            };

            const activateCard = (card) => {
                cards.forEach((item) => item.classList.toggle('is-active', item === card));

                outputs.name.textContent = card.dataset.planName || '';
                outputs.price.textContent = card.dataset.planPrice || '';
                outputs.period.textContent = card.dataset.planPeriod || '';
                outputs.description.textContent = card.dataset.planDescription || '';
                outputs['best-for'].textContent = card.dataset.planBestFor || '';
                outputs.support.textContent = card.dataset.planSupport || '';
                outputs['workspace-focus'].textContent = card.dataset.planWorkspaceFocus || '';
                outputs['decision-note'].textContent = card.dataset.planDecisionNote || '';

                if (outputs.ctaLink) {
                    outputs.ctaLink.textContent = card.dataset.planCta || 'Continue';
                    outputs.ctaLink.href = card.dataset.planHref || '#';
                }

                if (outputs.features) {
                    const features = JSON.parse(card.dataset.planFeatures || '[]');
                    outputs.features.innerHTML = features.map((feature) => `<li>${feature}</li>`).join('');
                }
            };

            cards.forEach((card) => {
                card.addEventListener('mouseenter', () => activateCard(card));
                card.addEventListener('focus', () => activateCard(card));
                card.addEventListener('click', (event) => {
                    const anchor = event.target.closest('a');
                    if (anchor) {
                        event.preventDefault();
                        beginTransition(card, anchor.href);
                        return;
                    }

                    activateCard(card);
                });
                card.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        beginTransition(card, card.dataset.planHref);
                    }
                });
            });

            if (outputs.ctaLink) {
                outputs.ctaLink.addEventListener('click', (event) => {
                    const activeCard = document.querySelector('[data-plan-card].is-active');
                    if (!activeCard) {
                        return;
                    }

                    event.preventDefault();
                    beginTransition(activeCard, outputs.ctaLink.href);
                });
            }
        });
    </script>
@endsection
