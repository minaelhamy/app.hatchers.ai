@extends('os.layout')

@section('content')
    <section class="hero">
        <div class="eyebrow">The Founder Operating System</div>
        <h1>One platform to build, run, and scale any founder-led business.</h1>
        <p class="muted">Hatchers OS combines mentoring, AI intelligence, website creation, commerce, service operations, and growth execution into one founder workspace at <strong>app.hatchers.ai</strong>.</p>
        <div class="cta-row">
            <a class="btn primary" href="/plans">Choose a plan</a>
            <a class="btn" href="/onboarding">Preview onboarding</a>
            <a class="btn" href="/dashboard">Preview dashboard</a>
        </div>
    </section>

    <section class="grid-3">
        <div class="card">
            <h2>One identity</h2>
            <p class="muted">One founder account, one subscription, and one workspace across mentoring, AI, ecommerce, and service operations.</p>
        </div>
        <div class="card">
            <h2>One intelligence layer</h2>
            <p class="muted">Atlas becomes the shared business memory for company context, goals, generated content, mentor guidance, and growth recommendations.</p>
        </div>
        <div class="card">
            <h2>One operating flow</h2>
            <p class="muted">Founders onboard once, get a weekly plan, build the right website, launch revenue, and keep growing from the same system.</p>
        </div>
    </section>
@endsection
