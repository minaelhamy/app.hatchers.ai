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

        .verify-scene {
            min-height: 100vh;
            padding: 28px;
            background:
                radial-gradient(circle at 82% 14%, rgba(234, 187, 199, 0.26), transparent 0 18%),
                linear-gradient(165deg, #ddd2c8 0%, #c8b8b0 100%);
        }

        .verify-scene::before {
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

        .verify-frame {
            position: relative;
            z-index: 1;
            min-height: calc(100vh - 56px);
            display: grid;
            place-items: center;
        }

        .verify-card {
            width: min(620px, 100%);
            padding: 34px;
            border-radius: 30px;
            border: 1px solid rgba(214, 201, 184, 0.84);
            background:
                linear-gradient(180deg, rgba(249, 244, 238, 0.94), rgba(240, 230, 220, 0.96));
            box-shadow:
                0 18px 54px rgba(71, 52, 31, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.72);
            text-align: center;
        }

        .verify-mark {
            width: 66px;
            height: 66px;
            margin: 0 auto 18px;
            border-radius: 18px;
            background: linear-gradient(135deg, #e11d74, #ef4444);
            box-shadow: 0 18px 34px rgba(225, 29, 116, 0.2);
        }

        .verify-eyebrow {
            font-size: 0.72rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: rgba(116, 97, 86, 0.72);
            margin-bottom: 12px;
        }

        .verify-card h1 {
            font-family: "Inter Tight", "Inter", sans-serif;
            font-size: clamp(2.4rem, 4vw, 3.4rem);
            line-height: 0.96;
            letter-spacing: -0.06em;
            margin: 0 0 14px;
            color: #171310;
        }

        .verify-copy {
            color: rgba(92, 76, 67, 0.84);
            line-height: 1.65;
            margin-bottom: 18px;
        }

        .verify-alert {
            margin-bottom: 14px;
            border-radius: 16px;
            padding: 12px 14px;
            border: 1px solid rgba(44, 122, 87, 0.24);
            background: rgba(44, 122, 87, 0.08);
            color: var(--success);
            text-align: left;
        }

        .verify-form {
            display: grid;
            gap: 14px;
            text-align: left;
        }

        .verify-label {
            display: block;
            margin-bottom: 7px;
            font-size: 0.9rem;
            color: rgba(96, 81, 73, 0.86);
        }

        .verify-input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid rgba(220, 207, 191, 0.94);
            background: rgba(255, 255, 255, 0.9);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.78);
            color: #191411;
            font: inherit;
        }

        .verify-code {
            letter-spacing: 0.32em;
            text-align: center;
        }

        .verify-error {
            margin-top: 6px;
            color: #b32253;
            font-size: 0.9rem;
        }

        .verify-submit,
        .verify-secondary {
            width: 100%;
            border-radius: 999px;
            padding: 14px 18px;
            font: inherit;
            font-weight: 700;
        }

        .verify-submit {
            border: 0;
            background: linear-gradient(180deg, #181310, #2b221d);
            color: #fff;
            cursor: pointer;
        }

        .verify-secondary {
            margin-top: 14px;
            border: 1px solid rgba(220, 207, 191, 0.94);
            background: rgba(255,255,255,0.72);
            color: #1c1714;
            cursor: pointer;
        }

        .verify-footnote {
            margin-top: 18px;
            color: rgba(105, 89, 79, 0.78);
            font-size: 0.96rem;
        }

        .verify-footnote a {
            color: #171310;
            font-weight: 700;
            text-decoration: none;
        }
    </style>
@endsection

@section('content')
    <div class="verify-scene">
        <div class="verify-frame">
            <section class="verify-card">
                <div class="verify-mark"></div>
                <div class="verify-eyebrow">Founder Verification</div>
                <h1>Verify your email</h1>
                <p class="verify-copy">Enter the 6-digit code we sent to your founder email to activate your account.</p>

                @if (session('success'))
                    <div class="verify-alert">{{ session('success') }}</div>
                @endif

                <form method="POST" action="{{ route('verification.email.verify') }}" class="verify-form">
                    @csrf
                    <label>
                        <div class="verify-label">Founder Email</div>
                        <input class="verify-input" type="email" name="email" value="{{ old('email', $email ?? '') }}">
                        @error('email')
                            <div class="verify-error">{{ $message }}</div>
                        @enderror
                    </label>
                    <label>
                        <div class="verify-label">Verification Code</div>
                        <input class="verify-input verify-code" type="text" name="code" inputmode="numeric" maxlength="6" value="{{ old('code') }}">
                        @error('code')
                            <div class="verify-error">{{ $message }}</div>
                        @enderror
                    </label>
                    <button class="verify-submit" type="submit">Verify email</button>
                </form>

                <form method="POST" action="{{ route('verification.email.resend') }}" style="margin-top: 14px; text-align: left;">
                    @csrf
                    <input type="hidden" name="email" value="{{ old('email', $email ?? '') }}">
                    <button class="verify-secondary" type="submit">Resend verification code</button>
                </form>

                <p class="verify-footnote">
                    Already verified? <a href="{{ route('login') }}">Back to login</a>
                </p>
            </section>
        </div>
    </div>
@endsection
