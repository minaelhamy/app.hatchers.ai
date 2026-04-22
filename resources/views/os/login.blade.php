@extends('os.layout')

@section('content')
    <section class="card" style="max-width: 560px; margin: 24px auto 0; padding: 34px; border-radius: 32px; text-align: center;">
        <img
            src="/brand/hatchers-ai-logo.png"
            alt="Hatchers AI"
            style="width: 240px; max-width: 78%; height: auto; display: block; margin: 0 auto 18px;"
        >
        <div class="eyebrow">Founder Login</div>
        <h1 style="font-size: clamp(2rem, 4vw, 3rem);">Welcome back to Hatchers Ai Business OS</h1>
        <p class="muted" style="margin-bottom: 18px;">Log in to continue building inside your founder operating system.</p>

        @if (session('success'))
            <div style="margin-bottom: 14px; border: 1px solid rgba(44, 122, 87, 0.24); background: rgba(44, 122, 87, 0.08); color: var(--success); border-radius: 16px; padding: 12px 14px; text-align: left;">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="/login" style="display: grid; gap: 14px; text-align: left;">
            @csrf
            <label>
                <div class="muted" style="margin-bottom: 6px;">Email or Username</div>
                <input type="text" name="login" value="{{ old('login') }}" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                @error('login')
                    <div style="color: #b32253; margin-top: 6px;">{{ $message }}</div>
                @enderror
            </label>
            <label>
                <div class="muted" style="margin-bottom: 6px;">Password</div>
                <input type="password" name="password" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                @error('password')
                    <div style="color: #b32253; margin-top: 6px;">{{ $message }}</div>
                @enderror
            </label>
            <div class="cta-row">
                <button class="btn primary" type="submit" style="cursor: pointer; width: 100%;">Login</button>
            </div>
        </form>
        <p class="muted" style="margin-top: 18px; font-size: 0.96rem;">
            Don't have an account <a href="/plans" style="color: var(--ink); font-weight: 700; text-decoration: none;">signup here</a>
        </p>
    </section>
@endsection
