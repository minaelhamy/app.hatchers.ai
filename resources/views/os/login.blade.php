@extends('os.layout')

@section('content')
    <section class="hero" style="max-width: 680px; margin-inline: auto;">
        <div class="eyebrow">Founder Login</div>
        <h1>Return to your Hatchers OS workspace.</h1>
        <p class="muted">Log in with your founder email or username to access your company dashboard, action plan, and Atlas assistant.</p>
    </section>

    <section class="card" style="max-width: 680px; margin-inline: auto;">
        <form method="POST" action="/login" style="display: grid; gap: 14px;">
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
                <button class="btn primary" type="submit" style="cursor: pointer;">Login</button>
                <a class="btn" href="/onboarding">Create a founder workspace</a>
            </div>
        </form>
    </section>
@endsection
