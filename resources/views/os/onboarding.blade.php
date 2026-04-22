@extends('os.layout')

@section('content')
    <section class="hero">
        <div class="eyebrow">Founder Onboarding</div>
        <h1>Tell Hatchers OS about the business once.</h1>
        <p class="muted">This onboarding replaces fragmented setup across Atlas, LMS, Bazaar, and Servio. It captures the founder context up front so the entire OS can personalize around the company from day one.</p>
    </section>

    <section class="grid-2">
        <div class="card">
            <h2>Onboarding Flow</h2>
            <div class="stack" style="margin-top: 14px;">
                <div class="stack-item"><strong>Step 1</strong><br>Company identity and founder goal</div>
                <div class="stack-item"><strong>Step 2</strong><br>Business model: product, service, or hybrid</div>
                <div class="stack-item"><strong>Step 3</strong><br>Audience, offer, and brand direction</div>
                <div class="stack-item"><strong>Step 4</strong><br>Traction stage and current blockers</div>
                <div class="stack-item"><strong>Step 5</strong><br>Route the founder into the right weekly plan and website path</div>
            </div>
        </div>

        <div class="card">
            <h2>Sample Intake Fields</h2>
            <div class="stack" style="margin-top: 14px;">
                <div class="stack-item">What is your company called?</div>
                <div class="stack-item">What are you selling: products, services, or both?</div>
                <div class="stack-item">Who is your ideal customer?</div>
                <div class="stack-item">What style should the brand feel like?</div>
                <div class="stack-item">What is the biggest challenge blocking growth right now?</div>
            </div>
        </div>
    </section>

    <section class="card" style="margin-top: 22px;">
        <h2>Prototype Form</h2>
        <form method="POST" action="/onboarding" style="display: grid; gap: 14px; margin-top: 16px;">
            @csrf
            <div class="grid-2">
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Founder Name</div>
                    <input type="text" name="full_name" value="Mina Elhamy" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                </label>
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Email</div>
                    <input type="email" name="email" value="{{ old('email', 'founder@example.com') }}" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                </label>
            </div>
            <div class="grid-2">
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Username</div>
                    <input type="text" name="username" value="{{ old('username', 'founder') }}" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                </label>
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Company Name</div>
                    <input type="text" name="company_name" value="{{ old('company_name', 'Brightpath Wellness') }}" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                </label>
            </div>
            <div class="grid-2">
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Password</div>
                    <input type="password" name="password" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                </label>
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Confirm Password</div>
                    <input type="password" name="password_confirmation" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                </label>
            </div>
            <div class="grid-2">
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Business Model</div>
                    <select name="business_model" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                        <option value="product">Product</option>
                        <option value="service" selected>Service</option>
                        <option value="hybrid">Hybrid</option>
                    </select>
                </label>
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Stage</div>
                    <select name="stage" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                        <option value="idea">Idea</option>
                        <option value="launching" selected>Launching</option>
                        <option value="operating">Operating</option>
                        <option value="scaling">Scaling</option>
                    </select>
                </label>
            </div>
            <label>
                <div class="muted" style="margin-bottom: 6px;">Industry</div>
                <input type="text" name="industry" value="Wellness" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
            </label>
            <label>
                <div class="muted" style="margin-bottom: 6px;">Company Brief</div>
                <textarea name="company_brief" rows="4" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">We help busy professionals book premium wellness support with a modern digital experience.</textarea>
            </label>
            <div class="grid-2">
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Target Audience</div>
                    <textarea name="target_audience" rows="3" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">Busy professionals in urban areas looking for convenient premium wellness services.</textarea>
                </label>
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Ideal Customer Profile</div>
                    <textarea name="ideal_customer_profile" rows="3" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">A time-constrained, digitally comfortable customer who values trust, convenience, and premium care.</textarea>
                </label>
            </div>
            <div class="grid-2">
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Brand Voice</div>
                    <input type="text" name="brand_voice" value="Warm, premium, reassuring, modern" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                </label>
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Core Offer</div>
                    <input type="text" name="core_offer" value="Book premium wellness services with ease" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                </label>
            </div>
            <div class="grid-2">
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Primary Growth Goal</div>
                    <input type="text" name="primary_growth_goal" value="Generate the first 50 recurring customers" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                </label>
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Known Blockers</div>
                    <input type="text" name="known_blockers" value="No strong website funnel yet and limited content engine" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                </label>
            </div>
            <div class="cta-row">
                <button class="btn primary" type="submit" style="cursor: pointer;">Create founder workspace</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>Why this matters</h2>
        <p class="muted">Onboarding should write directly into the central company intelligence layer so Atlas, the dashboard, mentor workflows, and future tools all start from the same business memory.</p>
    </section>
@endsection
