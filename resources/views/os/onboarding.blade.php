@extends('os.layout')

@section('content')
    <div class="public-shell narrow">
        <section class="hero">
            <div class="eyebrow">Founder Signup</div>
            <h1>Create your founder workspace.</h1>
            <p class="muted">You’re signing up on the <strong>{{ $selectedPlan['name'] }}</strong> plan. Finish signup, then return to login and enter your founder dashboard.</p>
        </section>

        <section class="card">
            <div class="pill">{{ $selectedPlan['label'] }}</div>
            <h2 style="margin-top: 14px;">Founder Signup Form</h2>
            <p class="muted">{{ $selectedPlan['description'] }}</p>
            <form method="POST" action="/onboarding" style="display: grid; gap: 14px; margin-top: 16px;">
            @csrf
            <input type="hidden" name="plan_code" value="{{ $selectedPlan['code'] }}">
            <div class="grid-2">
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Founder Name</div>
                    <input type="text" name="full_name" value="{{ old('full_name') }}" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                </label>
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Email</div>
                    <input type="email" name="email" value="{{ old('email') }}" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                </label>
            </div>
            <div class="grid-2">
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Username</div>
                    <input type="text" name="username" value="{{ old('username') }}" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                </label>
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Company Name</div>
                    <input type="text" name="company_name" value="{{ old('company_name') }}" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
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
                <input type="text" name="industry" value="{{ old('industry') }}" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
            </label>
            <label>
                <div class="muted" style="margin-bottom: 6px;">Company Brief</div>
                <textarea name="company_brief" rows="4" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">{{ old('company_brief') }}</textarea>
            </label>
            <div class="grid-2">
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Target Audience</div>
                    <textarea name="target_audience" rows="3" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">{{ old('target_audience') }}</textarea>
                </label>
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Ideal Customer Profile</div>
                    <textarea name="ideal_customer_profile" rows="3" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">{{ old('ideal_customer_profile') }}</textarea>
                </label>
            </div>
            <div class="grid-2">
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Brand Voice</div>
                    <input type="text" name="brand_voice" value="{{ old('brand_voice') }}" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                </label>
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Core Offer</div>
                    <input type="text" name="core_offer" value="{{ old('core_offer') }}" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                </label>
            </div>
            <div class="grid-2">
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Primary Growth Goal</div>
                    <input type="text" name="primary_growth_goal" value="{{ old('primary_growth_goal') }}" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                </label>
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Known Blockers</div>
                    <input type="text" name="known_blockers" value="{{ old('known_blockers') }}" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                </label>
            </div>
            <div class="cta-row">
                <button class="btn primary" type="submit" style="cursor: pointer;">Complete founder signup</button>
                <a class="btn" href="{{ route('plans') }}">Back to plans</a>
            </div>
        </form>
        </section>
    </div>
@endsection
