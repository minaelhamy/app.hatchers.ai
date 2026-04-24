@extends('os.layout')

@section('content')
    <section class="card" style="max-width: 560px; margin: 24px auto 0; padding: 34px; border-radius: 32px; text-align: center;">
        <img
            src="/brand/hatchers-ai-logo.png"
            alt="Hatchers AI"
            style="width: 240px; max-width: 78%; height: auto; display: block; margin: 0 auto 18px;"
        >
        <div class="eyebrow">Founder Verification</div>
        <h1 style="font-size: clamp(2rem, 4vw, 3rem);">Verify your email</h1>
        <p class="muted" style="margin-bottom: 18px;">Enter the 6-digit code we sent to your founder email to activate your account.</p>

        @if (session('success'))
            <div style="margin-bottom: 14px; border: 1px solid rgba(44, 122, 87, 0.24); background: rgba(44, 122, 87, 0.08); color: var(--success); border-radius: 16px; padding: 12px 14px; text-align: left;">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('verification.email.verify') }}" style="display: grid; gap: 14px; text-align: left;">
            @csrf
            <label>
                <div class="muted" style="margin-bottom: 6px;">Founder Email</div>
                <input type="email" name="email" value="{{ old('email', $email ?? '') }}" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                @error('email')
                    <div style="color: #b32253; margin-top: 6px;">{{ $message }}</div>
                @enderror
            </label>
            <label>
                <div class="muted" style="margin-bottom: 6px;">Verification Code</div>
                <input type="text" name="code" inputmode="numeric" maxlength="6" value="{{ old('code') }}" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff; letter-spacing: 0.3em;">
                @error('code')
                    <div style="color: #b32253; margin-top: 6px;">{{ $message }}</div>
                @enderror
            </label>
            <div class="cta-row" style="margin-top: 0;">
                <button class="btn primary" type="submit" style="cursor: pointer; width: 100%;">Verify email</button>
            </div>
        </form>

        <form method="POST" action="{{ route('verification.email.resend') }}" style="margin-top: 14px; text-align: left;">
            @csrf
            <input type="hidden" name="email" value="{{ old('email', $email ?? '') }}">
            <button class="btn" type="submit" style="cursor: pointer; width: 100%;">Resend verification code</button>
        </form>

        <p class="muted" style="margin-top: 18px; font-size: 0.96rem;">
            Already verified? <a href="{{ route('login') }}" style="color: var(--ink); font-weight: 700; text-decoration: none;">Back to login</a>
        </p>
    </section>
@endsection
