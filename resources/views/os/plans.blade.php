@extends('os.layout')

@section('content')
    <section class="hero">
        <div class="eyebrow">Plans</div>
        <h1>Choose how you want to build with Hatchers OS.</h1>
        <p class="muted">Founders can start with a 7-day free trial, choose the self-serve operating system, or build with mentor guidance. Admins and mentors are not created from this signup flow.</p>
    </section>

    @if (session('error'))
        <section class="card" style="border-color: rgba(179, 34, 83, 0.25); background: rgba(179, 34, 83, 0.06);">
            <h3 style="color: var(--rose);">Please choose a founder plan first</h3>
            <p class="muted" style="margin-top: 8px;">{{ session('error') }}</p>
        </section>
    @endif

    <section class="grid-3">
        @foreach ($plans as $plan)
            <div class="plan-card">
                <div class="pill">{{ $plan['label'] }}</div>
                <h2 style="margin-top: 14px;">{{ $plan['name'] }}</h2>
                <div class="price">
                    {{ $plan['price_display'] }}<span style="font-size: 1rem; font-weight: 500;">{{ $plan['period_display'] }}</span>
                </div>
                <p class="muted">{{ $plan['description'] }}</p>
                <div class="stack" style="margin-top: 18px;">
                    @foreach ($plan['features'] as $feature)
                        <div class="stack-item">{{ $feature }}</div>
                    @endforeach
                </div>
                <div class="cta-row">
                    <a class="btn primary" href="{{ route('onboarding', ['plan' => $plan['code']]) }}">{{ $plan['cta'] }}</a>
                </div>
            </div>
        @endforeach
    </section>
@endsection
