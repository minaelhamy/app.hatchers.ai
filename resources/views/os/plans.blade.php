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
            display: grid;
            place-items: center;
            padding: 28px;
            background:
                radial-gradient(circle at 82% 14%, rgba(234, 187, 199, 0.22), transparent 0 18%),
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

        .plans-wrap {
            position: relative;
            z-index: 1;
            width: min(100%, 1040px);
            display: grid;
            gap: 24px;
            justify-items: center;
            text-align: center;
        }

        .plans-mark {
            width: 94px;
            height: 94px;
            border-radius: 28px;
            background: linear-gradient(145deg, #ec2d70, #f24c44);
            box-shadow:
                0 22px 48px rgba(225, 29, 116, 0.2),
                0 0 0 12px rgba(255,255,255,0.14);
        }

        .plans-brand {
            font-size: 2rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: rgba(131, 111, 100, 0.72);
        }

        .plans-card {
            width: 100%;
            padding: 30px;
            border-radius: 30px;
            border: 1px solid rgba(214, 201, 184, 0.84);
            background:
                radial-gradient(circle at 78% 18%, rgba(234, 197, 201, 0.14), transparent 0 18%),
                linear-gradient(180deg, rgba(249, 244, 238, 0.88), rgba(240, 230, 220, 0.92));
            box-shadow:
                0 18px 54px rgba(71, 52, 31, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.72);
            backdrop-filter: blur(14px);
            text-align: left;
        }

        .plans-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            font-size: 0.72rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: rgba(116, 97, 86, 0.72);
        }

        .plans-kicker::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: rgba(225, 29, 116, 0.7);
        }

        .plans-title {
            margin: 0 0 12px;
            font-family: "Inter Tight", "Inter", sans-serif;
            font-size: clamp(2.4rem, 5vw, 4.1rem);
            line-height: 0.96;
            letter-spacing: -0.06em;
            color: #171310;
        }

        .plans-copy {
            margin: 0;
            color: rgba(92, 76, 67, 0.84);
            line-height: 1.6;
            max-width: 760px;
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

        .plans-notice {
            width: 100%;
            border-radius: 18px;
            padding: 14px 16px;
            border: 1px solid rgba(179, 34, 83, 0.25);
            background: rgba(179, 34, 83, 0.08);
            color: var(--rose);
            text-align: left;
        }

        .plans-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-top: 24px;
        }

        .plan-card {
            display: grid;
            gap: 16px;
            padding: 22px;
            border-radius: 24px;
            border: 1px solid rgba(223, 211, 198, 0.9);
            background: rgba(255, 252, 248, 0.72);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.82);
            backdrop-filter: blur(16px);
            transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
        }

        .plan-card:hover,
        .plan-card.is-active {
            transform: translateY(-3px);
            box-shadow: 0 18px 34px rgba(71, 52, 31, 0.08), inset 0 1px 0 rgba(255,255,255,0.82);
            border-color: rgba(204, 189, 173, 0.98);
        }

        .plan-pill {
            display: inline-flex;
            width: fit-content;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid rgba(221, 208, 194, 0.94);
            background: rgba(244, 238, 229, 0.9);
            font-size: 0.82rem;
            color: rgba(98, 83, 73, 0.86);
        }

        .plan-card h2 {
            margin: 0;
            font-family: "Inter Tight", "Inter", sans-serif;
            font-size: 1.55rem;
            line-height: 1.02;
            letter-spacing: -0.04em;
            color: #171310;
        }

        .plan-price {
            font-family: "Inter Tight", "Inter", sans-serif;
            font-size: 2.45rem;
            font-weight: 700;
            letter-spacing: -0.06em;
            color: #171310;
        }

        .plan-price span {
            margin-left: 4px;
            font-size: 0.98rem;
            font-weight: 500;
            color: rgba(103, 87, 77, 0.9);
        }

        .plan-description {
            color: rgba(92, 76, 67, 0.84);
            line-height: 1.58;
        }

        .plan-features {
            display: grid;
            gap: 10px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .plan-features li {
            position: relative;
            padding-left: 18px;
            color: rgba(85, 70, 61, 0.88);
            line-height: 1.5;
        }

        .plan-features li::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0.55em;
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: rgba(225, 29, 116, 0.62);
        }

        .plan-cta {
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

        @media (max-width: 980px) {
            .plans-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    <div class="plans-scene">
        <section class="plans-wrap">
            <div class="plans-mark"></div>
            <div class="plans-brand">Hatchers OS</div>

            <div class="plans-card">
                <div class="plans-kicker">Plans</div>
                <h1 class="plans-title">Choose your mode.</h1>
                <p class="plans-copy">Pick the plan that fits how you want to build, then continue into your workspace setup.</p>

                @if (config('app.disable_auth_verification'))
                    <div class="plans-test-pill">Test mode active · signup and login verification are disabled</div>
                @endif

                @if (session('error'))
                    <div class="plans-notice" style="margin-top: 16px;">{{ session('error') }}</div>
                @endif

                <div class="plans-grid">
                    @foreach ($plans as $plan)
                        <article
                            class="plan-card"
                            data-plan-card
                            data-plan-name="{{ $plan['name'] }}"
                            data-plan-href="{{ route('onboarding', ['plan' => $plan['code']]) }}"
                        >
                            <div class="plan-pill">{{ $plan['label'] }}</div>
                            <div>
                                <h2>{{ $plan['name'] }}</h2>
                                <div class="plan-price">{{ $plan['price_display'] }}<span>{{ $plan['period_display'] }}</span></div>
                            </div>
                            <div class="plan-description">{{ $plan['description'] }}</div>
                            <ul class="plan-features">
                                @foreach ($plan['features'] as $feature)
                                    <li>{{ $feature }}</li>
                                @endforeach
                            </ul>
                            <a class="plan-cta" href="{{ route('onboarding', ['plan' => $plan['code']]) }}">{{ $plan['cta'] }}</a>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    </div>

    <div class="plans-launch-overlay" data-plan-launch-overlay aria-hidden="true">
        <div class="plans-launch-core">
            <div class="plans-mark"></div>
            <div class="plans-launch-title" data-plan-launch-title>Entering Hatchers OS</div>
            <div class="plans-launch-copy" data-plan-launch-copy>Preparing your founder workspace…</div>
            <div class="plans-launch-line"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const cards = Array.from(document.querySelectorAll('[data-plan-card]'));
            const overlay = document.querySelector('[data-plan-launch-overlay]');
            const overlayTitle = document.querySelector('[data-plan-launch-title]');
            let transitionLocked = false;

            const beginTransition = (card, href) => {
                if (transitionLocked || !overlay || !href) return;
                transitionLocked = true;
                cards.forEach((item) => item.classList.toggle('is-active', item === card));
                overlay.classList.add('is-visible');

                const planName = card.dataset.planName || 'Hatchers OS';
                overlayTitle.textContent = `Entering ${planName}`;

                try {
                    window.sessionStorage.setItem('hatchersPlanLaunchTransition', JSON.stringify({
                        planName,
                        planLabel: card.querySelector('.plan-pill')?.textContent?.trim() || '',
                        planBestFor: planName,
                    }));
                } catch (error) {
                    // Ignore storage issues.
                }

                window.setTimeout(() => {
                    window.location.href = href;
                }, 520);
            };

            cards.forEach((card) => {
                const href = card.dataset.planHref || card.querySelector('.plan-cta')?.getAttribute('href') || '';
                card.addEventListener('click', (event) => {
                    const target = event.target;
                    if (target instanceof HTMLElement && target.closest('.plan-cta')) {
                        event.preventDefault();
                    }
                    beginTransition(card, href);
                });
            });
        });
    </script>
@endsection
