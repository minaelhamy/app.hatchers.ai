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

        .entry-scene {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 28px;
            background:
                radial-gradient(circle at 82% 14%, rgba(234, 187, 199, 0.22), transparent 0 18%),
                linear-gradient(165deg, #ddd2c8 0%, #c8b8b0 100%);
        }

        .entry-scene::before {
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

        .entry-wrap {
            position: relative;
            z-index: 1;
            width: min(100%, 460px);
            display: grid;
            gap: 18px;
            justify-items: center;
            text-align: center;
        }

        .entry-mark {
            width: 82px;
            height: 82px;
            border-radius: 24px;
            background: linear-gradient(145deg, #ec2d70, #f24c44);
            box-shadow:
                0 22px 48px rgba(225, 29, 116, 0.2),
                0 0 0 12px rgba(255,255,255,0.14);
        }

        .entry-brand {
            margin-top: -6px;
            font-size: 1.55rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: rgba(131, 111, 100, 0.72);
        }

        .entry-copy {
            color: rgba(116, 96, 86, 0.82);
            font-size: 0.92rem;
            line-height: 1.5;
        }

        .entry-card {
            width: 100%;
            padding: 24px;
            border-radius: 24px;
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

        .entry-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            font-size: 0.66rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(116, 97, 86, 0.72);
        }

        .entry-kicker::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: rgba(225, 29, 116, 0.7);
        }

        .entry-title {
            margin: 0 0 10px;
            font-family: "Inter Tight", "Inter", sans-serif;
            font-size: clamp(1.85rem, 4vw, 2.5rem);
            line-height: 0.98;
            letter-spacing: -0.05em;
            color: #171310;
        }

        .entry-subcopy {
            margin: 0 0 16px;
            color: rgba(92, 76, 67, 0.84);
            line-height: 1.5;
            font-size: 0.92rem;
        }

        .entry-alert {
            margin-bottom: 14px;
            border-radius: 16px;
            padding: 12px 14px;
            border: 1px solid rgba(44, 122, 87, 0.24);
            background: rgba(44, 122, 87, 0.08);
            color: var(--success);
        }

        .entry-field-grid {
            display: grid;
            gap: 12px;
        }

        .entry-field-label {
            display: block;
            margin-bottom: 7px;
            font-size: 0.84rem;
            color: rgba(96, 81, 73, 0.86);
        }

        .entry-input {
            width: 100%;
            padding: 13px 15px;
            border-radius: 14px;
            border: 1px solid rgba(220, 207, 191, 0.94);
            background: rgba(255, 255, 255, 0.9);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.78);
            color: #191411;
            font: inherit;
        }

        .entry-error {
            margin-top: 6px;
            color: #b32253;
            font-size: 0.9rem;
        }

        .entry-submit {
            width: 100%;
            margin-top: 6px;
            padding: 13px 18px;
            border: 0;
            border-radius: 999px;
            background: linear-gradient(180deg, #181310, #2b221d);
            color: #fff;
            font: inherit;
            font-size: 0.92rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 14px 28px rgba(33, 25, 20, 0.15);
        }

        .entry-footnote {
            margin-top: 16px;
            color: rgba(105, 89, 79, 0.78);
            font-size: 0.88rem;
            text-align: center;
        }

        .entry-footnote a {
            color: #171310;
            font-weight: 700;
            text-decoration: none;
        }
    </style>
@endsection

@section('content')
    <div class="entry-scene">
        <section class="entry-wrap">
            <div class="entry-mark"></div>
            <div class="entry-brand">Hatchers AI OS</div>
            <div class="entry-copy">Enter your workspace.</div>

            <div class="entry-card">
                <div class="entry-kicker">Founder login</div>
                <h1 class="entry-title">Sign in</h1>
                <p class="entry-subcopy">Continue where you left off.</p>

                @if (session('success'))
                    <div class="entry-alert">{{ session('success') }}</div>
                @endif

                <form method="POST" action="/login" class="entry-field-grid">
                    @csrf
                    <label>
                        <span class="entry-field-label">Email or Username</span>
                        <input class="entry-input" type="text" name="login" value="{{ old('login') }}">
                        @error('login')
                            <div class="entry-error">{{ $message }}</div>
                        @enderror
                    </label>
                    <label>
                        <span class="entry-field-label">Password</span>
                        <input class="entry-input" type="password" name="password">
                        @error('password')
                            <div class="entry-error">{{ $message }}</div>
                        @enderror
                    </label>
                    <button class="entry-submit" type="submit">Login</button>
                </form>

                <p class="entry-footnote">
                    Don’t have an account? <a href="/plans">Choose a founder plan and sign up</a>
                </p>
            </div>
        </section>
    </div>
@endsection
