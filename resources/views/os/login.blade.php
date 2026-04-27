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

        .auth-scene {
            min-height: 100vh;
            padding: 28px;
            background:
                radial-gradient(circle at 82% 14%, rgba(234, 187, 199, 0.28), transparent 0 18%),
                linear-gradient(165deg, #ddd2c8 0%, #c8b8b0 100%);
        }

        .auth-scene::before {
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

        .auth-frame {
            position: relative;
            z-index: 1;
            min-height: calc(100vh - 56px);
            display: grid;
            grid-template-columns: minmax(320px, 0.94fr) minmax(420px, 0.88fr);
            gap: 22px;
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

        .auth-panel,
        .auth-form-panel {
            border-radius: 26px;
            border: 1px solid rgba(223, 211, 198, 0.9);
            background: rgba(255, 252, 248, 0.72);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.82);
            backdrop-filter: blur(16px);
        }

        .auth-panel {
            display: grid;
            align-content: space-between;
            gap: 24px;
            padding: 26px;
        }

        .auth-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #2c241f;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 20px;
        }

        .auth-brand-mark {
            width: 22px;
            height: 22px;
            border-radius: 7px;
            background: linear-gradient(135deg, #e11d74, #ef4444);
            box-shadow: 0 10px 26px rgba(225, 29, 116, 0.28);
        }

        .auth-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            font-size: 0.72rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: rgba(116, 97, 86, 0.72);
        }

        .auth-eyebrow::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: rgba(225, 29, 116, 0.7);
        }

        .auth-panel h1,
        .auth-form-panel h1 {
            font-family: "Inter Tight", "Inter", "Avenir Next", sans-serif;
            font-size: clamp(2.6rem, 4vw, 4.3rem);
            line-height: 0.96;
            letter-spacing: -0.06em;
            margin: 0 0 16px;
            color: #171310;
        }

        .auth-copy {
            color: rgba(92, 76, 67, 0.84);
            font-size: 1rem;
            line-height: 1.65;
            max-width: 46ch;
        }

        .auth-metric-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .auth-metric {
            padding: 16px;
            border-radius: 20px;
            border: 1px solid rgba(225, 212, 198, 0.9);
            background: rgba(255,255,255,0.62);
        }

        .auth-metric-label {
            display: block;
            font-size: 0.72rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(117, 100, 89, 0.7);
            margin-bottom: 8px;
        }

        .auth-metric-value {
            display: block;
            font-family: "Inter Tight", "Inter", sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: #191411;
            line-height: 1.3;
        }

        .auth-method {
            padding: 18px;
            border-radius: 22px;
            border: 1px solid rgba(225, 212, 198, 0.9);
            background: rgba(250, 244, 236, 0.76);
            color: rgba(88, 72, 63, 0.88);
        }

        .auth-method strong {
            display: block;
            margin-bottom: 8px;
            color: #211a16;
        }

        .auth-form-panel {
            display: grid;
            align-content: center;
            padding: 34px;
        }

        .auth-form-shell {
            max-width: 520px;
            width: 100%;
            margin: 0 auto;
        }

        .auth-alert {
            margin-bottom: 14px;
            border-radius: 16px;
            padding: 12px 14px;
            border: 1px solid rgba(44, 122, 87, 0.24);
            background: rgba(44, 122, 87, 0.08);
            color: var(--success);
        }

        .auth-field-grid {
            display: grid;
            gap: 14px;
        }

        .auth-field-label {
            display: block;
            margin-bottom: 7px;
            font-size: 0.9rem;
            color: rgba(96, 81, 73, 0.86);
        }

        .auth-input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid rgba(220, 207, 191, 0.94);
            background: rgba(255, 255, 255, 0.9);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.78);
            color: #191411;
            font: inherit;
        }

        .auth-error {
            margin-top: 6px;
            color: #b32253;
            font-size: 0.9rem;
        }

        .auth-submit {
            width: 100%;
            margin-top: 6px;
            padding: 15px 18px;
            border: 0;
            border-radius: 999px;
            background: linear-gradient(180deg, #181310, #2b221d);
            color: #fff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 14px 28px rgba(33, 25, 20, 0.15);
        }

        .auth-footnote {
            margin-top: 18px;
            color: rgba(105, 89, 79, 0.78);
            font-size: 0.96rem;
        }

        .auth-footnote a {
            color: #171310;
            font-weight: 700;
            text-decoration: none;
        }

        @media (max-width: 980px) {
            .auth-frame {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .auth-panel {
                order: 2;
            }

            .auth-form-panel {
                order: 1;
            }
        }
    </style>
@endsection

@section('content')
    <div class="auth-scene">
        <section class="auth-frame">
            <aside class="auth-panel">
                <div>
                    <a class="auth-brand" href="/">
                        <span class="auth-brand-mark"></span>
                        <span>Hatchers OS</span>
                    </a>
                    <div class="auth-eyebrow">Founder Login</div>
                    <h1>Welcome back to your operating system.</h1>
                    <p class="auth-copy">
                        Step back into the same founder workspace where your strategy, tasks, website, offers, bookings, orders, and mentor guidance all stay connected.
                    </p>
                </div>

                <div class="auth-metric-grid">
                    <div class="auth-metric">
                        <span class="auth-metric-label">Workspace</span>
                        <span class="auth-metric-value">One calm desktop for the whole business</span>
                    </div>
                    <div class="auth-metric">
                        <span class="auth-metric-label">Atlas Mentor</span>
                        <span class="auth-metric-value">Founder guidance from your live OS data</span>
                    </div>
                    <div class="auth-metric">
                        <span class="auth-metric-label">Commerce</span>
                        <span class="auth-metric-value">Orders, bookings, and money in one surface</span>
                    </div>
                    <div class="auth-metric">
                        <span class="auth-metric-label">Execution</span>
                        <span class="auth-metric-value">Learning, tasks, and growth without app-switching</span>
                    </div>
                </div>

                <div class="auth-method">
                    <strong>Built for focused founders</strong>
                    The OS is designed to keep attention on the next move that drives revenue, clarity, and momentum.
                </div>
            </aside>

            <section class="auth-form-panel">
                <div class="auth-form-shell">
                    <div class="auth-eyebrow">Sign In</div>
                    <h1>Enter Hatchers OS</h1>
                    <p class="auth-copy" style="margin-bottom: 18px;">Log in to continue building inside your founder operating system.</p>

                    @if (session('success'))
                        <div class="auth-alert">{{ session('success') }}</div>
                    @endif

                    <form method="POST" action="/login" class="auth-field-grid">
                        @csrf
                        <label>
                            <span class="auth-field-label">Email or Username</span>
                            <input class="auth-input" type="text" name="login" value="{{ old('login') }}">
                            @error('login')
                                <div class="auth-error">{{ $message }}</div>
                            @enderror
                        </label>
                        <label>
                            <span class="auth-field-label">Password</span>
                            <input class="auth-input" type="password" name="password">
                            @error('password')
                                <div class="auth-error">{{ $message }}</div>
                            @enderror
                        </label>
                        <button class="auth-submit" type="submit">Login</button>
                    </form>

                    <p class="auth-footnote">
                        Don’t have an account? <a href="/plans">Choose a founder plan and sign up</a>
                    </p>
                </div>
            </section>
        </section>
    </div>
@endsection
