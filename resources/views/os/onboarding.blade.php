@extends('os.layout')

@section('content')
    <div class="public-shell narrow">
        <section class="hero">
            <div class="eyebrow">Founder Signup</div>
            <h1>Create your founder workspace.</h1>
            <p class="muted">You’re signing up on the <strong>{{ $selectedPlan['name'] }}</strong> plan. Finish signup, then return to login and enter your founder dashboard.</p>
            <p class="muted" style="margin-top: 10px;"><strong>All fields are mandatory</strong> and must be completed before you can continue.</p>
        </section>

        <section class="card">
            <div class="pill">{{ $selectedPlan['label'] }}</div>
            <h2 style="margin-top: 14px;">Founder Signup Form</h2>
            <p class="muted">{{ $selectedPlan['description'] }}</p>
            @if (session('error'))
                <div style="margin-top: 16px; border: 1px solid rgba(179, 34, 83, 0.2); background: rgba(179, 34, 83, 0.06); border-radius: 16px; padding: 14px 16px; color: var(--rose);">
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div style="margin-top: 16px; border: 1px solid rgba(179, 34, 83, 0.2); background: rgba(179, 34, 83, 0.06); border-radius: 16px; padding: 14px 16px;">
                    <strong style="display: block; color: var(--rose);">Please complete every required field.</strong>
                    <ul style="margin: 10px 0 0 18px; padding: 0; color: var(--muted);">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="/onboarding" style="display: grid; gap: 14px; margin-top: 16px;">
                @csrf
                <input type="hidden" name="plan_code" value="{{ $selectedPlan['code'] }}">
                <div class="grid-2">
                    <label>
                        <div class="muted" style="margin-bottom: 6px;">Founder Name</div>
                        <input required type="text" name="full_name" value="{{ old('full_name') }}" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                        @error('full_name')<div style="color: var(--rose); margin-top: 6px;">{{ $message }}</div>@enderror
                    </label>
                    <label>
                        <div class="muted" style="margin-bottom: 6px;">Email</div>
                        <input required type="email" name="email" value="{{ old('email') }}" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                        @error('email')<div style="color: var(--rose); margin-top: 6px;">{{ $message }}</div>@enderror
                    </label>
                </div>
                <div class="grid-2">
                    <label>
                        <div class="muted" style="margin-bottom: 6px;">Username</div>
                        <input required type="text" name="username" value="{{ old('username') }}" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                        @error('username')<div style="color: var(--rose); margin-top: 6px;">{{ $message }}</div>@enderror
                    </label>
                    <label>
                        <div class="muted" style="margin-bottom: 6px;">Company Name</div>
                        <input required type="text" name="company_name" value="{{ old('company_name') }}" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                        @error('company_name')<div style="color: var(--rose); margin-top: 6px;">{{ $message }}</div>@enderror
                    </label>
                </div>
                <div class="grid-2">
                    <label>
                        <div class="muted" style="margin-bottom: 6px;">Password</div>
                        <input required type="password" name="password" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                        @error('password')<div style="color: var(--rose); margin-top: 6px;">{{ $message }}</div>@enderror
                    </label>
                    <label>
                        <div class="muted" style="margin-bottom: 6px;">Confirm Password</div>
                        <input required type="password" name="password_confirmation" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                    </label>
                </div>
                <div class="grid-2">
                    <label>
                        <div class="muted" style="margin-bottom: 6px;">Business Model</div>
                        <select required name="business_model" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                            <option value="">Select a business model</option>
                            <option value="product" @selected(old('business_model') === 'product')>Product Business</option>
                            <option value="service" @selected(old('business_model') === 'service')>Service Business</option>
                            <option value="hybrid" @selected(old('business_model') === 'hybrid')>Hybrid Business</option>
                        </select>
                        @error('business_model')<div style="color: var(--rose); margin-top: 6px;">{{ $message }}</div>@enderror
                    </label>
                    <label>
                        <div class="muted" style="margin-bottom: 6px;">Stage</div>
                        <select required name="stage" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                            <option value="">Select your stage</option>
                            <option value="idea" @selected(old('stage') === 'idea')>Idea Stage</option>
                            <option value="launching" @selected(old('stage') === 'launching')>Launching</option>
                            <option value="operating" @selected(old('stage') === 'operating')>Operating</option>
                            <option value="scaling" @selected(old('stage') === 'scaling')>Scaling</option>
                        </select>
                        @error('stage')<div style="color: var(--rose); margin-top: 6px;">{{ $message }}</div>@enderror
                    </label>
                </div>
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Industry</div>
                    <select required name="industry" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                        <option value="">Select your industry</option>
                        @foreach ($industryOptions as $industryOption)
                            <option value="{{ $industryOption }}" @selected(old('industry') === $industryOption)>{{ $industryOption }}</option>
                        @endforeach
                    </select>
                    @error('industry')<div style="color: var(--rose); margin-top: 6px;">{{ $message }}</div>@enderror
                </label>
                <label>
                    <div class="muted" style="margin-bottom: 6px;">Company Brief</div>
                    <textarea required name="company_brief" rows="4" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">{{ old('company_brief') }}</textarea>
                    @error('company_brief')<div style="color: var(--rose); margin-top: 6px;">{{ $message }}</div>@enderror
                </label>
                <div class="grid-2">
                    <label>
                        <div class="muted" style="margin-bottom: 6px;">Target Audience</div>
                        <select required name="target_audience" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                            <option value="">Select your audience</option>
                            <option value="Consumers / B2C" @selected(old('target_audience') === 'Consumers / B2C')>Consumers / B2C</option>
                            <option value="Small businesses / SMB" @selected(old('target_audience') === 'Small businesses / SMB')>Small businesses / SMB</option>
                            <option value="Corporate / Enterprise" @selected(old('target_audience') === 'Corporate / Enterprise')>Corporate / Enterprise</option>
                            <option value="Creators / Personal brands" @selected(old('target_audience') === 'Creators / Personal brands')>Creators / Personal brands</option>
                            <option value="Local community / Neighborhood market" @selected(old('target_audience') === 'Local community / Neighborhood market')>Local community / Neighborhood market</option>
                        </select>
                        @error('target_audience')<div style="color: var(--rose); margin-top: 6px;">{{ $message }}</div>@enderror
                    </label>
                    <label>
                        <div class="muted" style="margin-bottom: 6px;">Ideal Customer Profile</div>
                        <textarea required name="ideal_customer_profile" rows="3" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">{{ old('ideal_customer_profile') }}</textarea>
                        @error('ideal_customer_profile')<div style="color: var(--rose); margin-top: 6px;">{{ $message }}</div>@enderror
                    </label>
                </div>
                <div class="grid-2">
                    <label>
                        <div class="muted" style="margin-bottom: 6px;">Brand Voice</div>
                        <select required name="brand_voice" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                            <option value="">Select a brand voice</option>
                            <option value="Warm and supportive" @selected(old('brand_voice') === 'Warm and supportive')>Warm and supportive</option>
                            <option value="Premium and polished" @selected(old('brand_voice') === 'Premium and polished')>Premium and polished</option>
                            <option value="Bold and energetic" @selected(old('brand_voice') === 'Bold and energetic')>Bold and energetic</option>
                            <option value="Professional and credible" @selected(old('brand_voice') === 'Professional and credible')>Professional and credible</option>
                            <option value="Friendly and simple" @selected(old('brand_voice') === 'Friendly and simple')>Friendly and simple</option>
                        </select>
                        @error('brand_voice')<div style="color: var(--rose); margin-top: 6px;">{{ $message }}</div>@enderror
                    </label>
                    <label>
                        <div class="muted" style="margin-bottom: 6px;">Core Offer</div>
                        <select required name="core_offer" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                            <option value="">Select your core offer</option>
                            @foreach ($coreOfferOptions as $coreOfferOption)
                                <option value="{{ $coreOfferOption }}" @selected(old('core_offer') === $coreOfferOption)>{{ $coreOfferOption }}</option>
                            @endforeach
                        </select>
                        @error('core_offer')<div style="color: var(--rose); margin-top: 6px;">{{ $message }}</div>@enderror
                    </label>
                </div>
                <div class="grid-2">
                    <label>
                        <div class="muted" style="margin-bottom: 6px;">Primary Growth Goal</div>
                        <select required name="primary_growth_goal" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                            <option value="">Select your goal</option>
                            <option value="Launch my first website" @selected(old('primary_growth_goal') === 'Launch my first website')>Launch my first website</option>
                            <option value="Get my first customers" @selected(old('primary_growth_goal') === 'Get my first customers')>Get my first customers</option>
                            <option value="Increase recurring sales" @selected(old('primary_growth_goal') === 'Increase recurring sales')>Increase recurring sales</option>
                            <option value="Build a stronger brand presence" @selected(old('primary_growth_goal') === 'Build a stronger brand presence')>Build a stronger brand presence</option>
                            <option value="Systemize and scale operations" @selected(old('primary_growth_goal') === 'Systemize and scale operations')>Systemize and scale operations</option>
                        </select>
                        @error('primary_growth_goal')<div style="color: var(--rose); margin-top: 6px;">{{ $message }}</div>@enderror
                    </label>
                    <label>
                        <div class="muted" style="margin-bottom: 6px;">Current Biggest Blocker</div>
                        <select required name="known_blockers" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--line); background: #fff;">
                            <option value="">Select a blocker</option>
                            <option value="No clear offer yet" @selected(old('known_blockers') === 'No clear offer yet')>No clear offer yet</option>
                            <option value="No website or weak funnel" @selected(old('known_blockers') === 'No website or weak funnel')>No website or weak funnel</option>
                            <option value="Low traffic or visibility" @selected(old('known_blockers') === 'Low traffic or visibility')>Low traffic or visibility</option>
                            <option value="Low conversions or sales" @selected(old('known_blockers') === 'Low conversions or sales')>Low conversions or sales</option>
                            <option value="Limited time or team capacity" @selected(old('known_blockers') === 'Limited time or team capacity')>Limited time or team capacity</option>
                        </select>
                        @error('known_blockers')<div style="color: var(--rose); margin-top: 6px;">{{ $message }}</div>@enderror
                    </label>
                </div>
                <div class="cta-row">
                    <button class="btn primary" type="submit" style="cursor: pointer;">Complete founder signup</button>
                    <a class="btn" href="{{ route('plans') }}">Back to plans</a>
                </div>
                <p class="muted" style="margin: -2px 0 0; font-size: 0.92rem;">Every field above is mandatory. If anything is missing, Hatchers AI Business OS will keep your current inputs and show you exactly what to fix.</p>
            </form>
        </section>
    </div>
@endsection
