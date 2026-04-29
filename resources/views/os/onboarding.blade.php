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

        .signup-scene {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 28px;
            background:
                radial-gradient(circle at 82% 14%, rgba(234, 187, 199, 0.22), transparent 0 18%),
                linear-gradient(165deg, #ddd2c8 0%, #c8b8b0 100%);
        }

        .signup-scene::before {
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

        .signup-wrap {
            position: relative;
            z-index: 1;
            width: min(100%, 920px);
            display: grid;
            gap: 18px;
            justify-items: center;
            text-align: center;
            opacity: 1;
            transition: opacity 320ms ease, transform 420ms ease, filter 420ms ease;
        }

        .signup-scene.is-launch-arrival .signup-wrap {
            opacity: 0;
            transform: translateY(20px) scale(0.986);
            filter: blur(8px);
        }

        .signup-scene.is-launch-arrival.is-launch-ready .signup-wrap {
            opacity: 1;
            transform: translateY(0) scale(1);
            filter: blur(0);
        }

        .signup-mark {
            width: 82px;
            height: 82px;
            border-radius: 24px;
            background: linear-gradient(145deg, #ec2d70, #f24c44);
            box-shadow:
                0 22px 48px rgba(225, 29, 116, 0.2),
                0 0 0 12px rgba(255,255,255,0.14);
        }

        .signup-brand {
            margin-top: -6px;
            font-size: 1.55rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: rgba(131, 111, 100, 0.72);
        }

        .signup-copy {
            color: rgba(116, 96, 86, 0.82);
            font-size: 0.92rem;
            line-height: 1.5;
        }

        .signup-card {
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

        .signup-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            font-size: 0.66rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(116, 97, 86, 0.72);
        }

        .signup-kicker::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: rgba(225, 29, 116, 0.7);
        }

        .signup-header {
            display: grid;
            gap: 12px;
            margin-bottom: 20px;
        }

        .signup-title {
            margin: 0;
            font-family: "Inter Tight", "Inter", sans-serif;
            font-size: clamp(1.95rem, 4vw, 2.8rem);
            line-height: 0.98;
            letter-spacing: -0.05em;
            color: #171310;
        }

        .signup-subcopy {
            margin: 0;
            color: rgba(92, 76, 67, 0.84);
            line-height: 1.5;
            font-size: 0.92rem;
            max-width: 720px;
        }

        .signup-topline {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }

        .signup-plan-pill,
        .signup-arrival-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            padding: 9px 13px;
            border-radius: 999px;
            border: 1px solid rgba(223, 210, 197, 0.92);
            background: rgba(255,255,255,0.74);
            color: rgba(90, 72, 62, 0.88);
            font-size: 0.78rem;
            font-weight: 600;
        }

        .signup-plan-pill::before,
        .signup-arrival-badge::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: linear-gradient(135deg, #e11d74, #ef4444);
        }

        .signup-entry-banner {
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 16px;
            border-radius: 18px;
            border: 1px solid rgba(223, 210, 197, 0.92);
            background: rgba(255,255,255,0.66);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.8);
        }

        .signup-entry-banner.is-visible {
            display: flex;
        }

        .signup-entry-banner strong {
            display: block;
            color: #171310;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }

        .signup-entry-banner span {
            color: rgba(96, 81, 73, 0.84);
            font-size: 0.88rem;
        }

        .signup-alert {
            margin-bottom: 16px;
            border-radius: 18px;
            padding: 14px 16px;
        }

        .signup-alert.error {
            border: 1px solid rgba(179, 34, 83, 0.2);
            background: rgba(179, 34, 83, 0.06);
            color: var(--rose);
        }

        .signup-form-grid {
            display: grid;
            gap: 14px;
        }

        .signup-grid-2,
        .signup-grid-3 {
            display: grid;
            gap: 16px;
        }

        .signup-grid-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .signup-grid-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .signup-label {
            display: block;
            margin-bottom: 7px;
            font-size: 0.84rem;
            color: rgba(96, 81, 73, 0.86);
        }

        .signup-input,
        .signup-select,
        .signup-textarea {
            width: 100%;
            padding: 13px 15px;
            border-radius: 14px;
            border: 1px solid rgba(220, 207, 191, 0.94);
            background: rgba(255, 255, 255, 0.9);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.78);
            color: #191411;
            font: inherit;
            font-size: 0.9rem;
        }

        .signup-textarea {
            resize: vertical;
            min-height: 118px;
        }

        .signup-error {
            margin-top: 6px;
            color: #b32253;
            font-size: 0.9rem;
        }

        .signup-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 6px;
        }

        .signup-submit,
        .signup-secondary {
            text-decoration: none;
            border-radius: 999px;
            padding: 12px 16px;
            font-weight: 700;
            font: inherit;
            font-size: 0.88rem;
        }

        .signup-submit {
            border: 0;
            background: linear-gradient(180deg, #181310, #2b221d);
            color: #fff;
            cursor: pointer;
            box-shadow: 0 14px 28px rgba(33, 25, 20, 0.15);
        }

        .signup-secondary {
            border: 1px solid rgba(220, 207, 191, 0.94);
            background: rgba(255,255,255,0.72);
            color: #1c1714;
        }

        .signup-footnote {
            margin: 0;
            color: rgba(104, 88, 79, 0.78);
            font-size: 0.84rem;
        }

        @media (max-width: 840px) {
            .signup-grid-2,
            .signup-grid-3 {
                grid-template-columns: 1fr;
            }

            .signup-card {
                padding: 24px;
            }

            .signup-entry-banner {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
@endsection

@section('content')
    <div class="signup-scene" data-signup-scene>
        <section class="signup-wrap">
            <div class="signup-mark"></div>
            <div class="signup-brand">Hatchers OS</div>
            <div class="signup-copy">Create your workspace.</div>

            <div class="signup-card">
                <div class="signup-header">
                    <div class="signup-topline">
                        <div class="signup-kicker">Founder signup</div>
                        <div class="signup-plan-pill">{{ $selectedPlan['label'] }}</div>
                    </div>

                    <h1 class="signup-title">Tell Hatchers how your business works.</h1>
                    <p class="signup-subcopy">
                        Finish this setup once so we can tailor your website, learning, and workspace to your business.
                    </p>

                    <div class="signup-entry-banner" data-signup-entry-banner>
                        <div>
                            <strong data-signup-entry-title>Founder mode selected</strong>
                            <span data-signup-entry-copy>We’re preparing your workspace and keeping your onboarding context ready.</span>
                        </div>
                        <div class="signup-arrival-badge" data-signup-entry-badge>{{ $selectedPlan['label'] }}</div>
                    </div>
                </div>

                @if (session('error'))
                    <div class="signup-alert error">
                        {{ session('error') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="signup-alert error">
                        <strong style="display: block; margin-bottom: 8px;">Please complete every required field.</strong>
                        <ul style="margin: 0 0 0 18px; padding: 0;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="/onboarding" class="signup-form-grid">
                    @csrf
                    <input type="hidden" name="plan_code" value="{{ $selectedPlan['code'] }}">
                    <div class="signup-grid-2">
                        <label>
                            <div class="signup-label">Founder Name</div>
                            <input class="signup-input" required type="text" name="full_name" value="{{ old('full_name') }}">
                            @error('full_name')<div class="signup-error">{{ $message }}</div>@enderror
                        </label>
                        <label>
                            <div class="signup-label">Email</div>
                            <input class="signup-input" required type="email" name="email" value="{{ old('email') }}">
                            @error('email')<div class="signup-error">{{ $message }}</div>@enderror
                        </label>
                    </div>
                    <div class="signup-grid-2">
                        <label>
                            <div class="signup-label">Username</div>
                            <input class="signup-input" required type="text" name="username" value="{{ old('username') }}">
                            @error('username')<div class="signup-error">{{ $message }}</div>@enderror
                        </label>
                        <label>
                            <div class="signup-label">Company Name</div>
                            <input class="signup-input" required type="text" name="company_name" value="{{ old('company_name') }}">
                            @error('company_name')<div class="signup-error">{{ $message }}</div>@enderror
                        </label>
                    </div>
                    <div class="signup-grid-2">
                        <label>
                            <div class="signup-label">Password</div>
                            <input class="signup-input" required type="password" name="password">
                            @error('password')<div class="signup-error">{{ $message }}</div>@enderror
                        </label>
                        <label>
                            <div class="signup-label">Confirm Password</div>
                            <input class="signup-input" required type="password" name="password_confirmation">
                        </label>
                    </div>
                    <div class="signup-grid-3">
                        <label>
                            <div class="signup-label">Business Blueprint</div>
                            <select class="signup-select" required name="vertical_blueprint">
                                <option value="">Select the closest blueprint</option>
                                @foreach ($verticalBlueprintOptions as $verticalBlueprint)
                                    <option value="{{ $verticalBlueprint['code'] }}" @selected(old('vertical_blueprint') === $verticalBlueprint['code'])>{{ $verticalBlueprint['name'] }} · {{ ucfirst($verticalBlueprint['business_model']) }}</option>
                                @endforeach
                            </select>
                            @error('vertical_blueprint')<div class="signup-error">{{ $message }}</div>@enderror
                        </label>
                        <label>
                            <div class="signup-label">Business Model</div>
                            <select class="signup-select" required name="business_model">
                                <option value="">Select a business model</option>
                                <option value="product" @selected(old('business_model') === 'product')>Product Business</option>
                                <option value="service" @selected(old('business_model') === 'service')>Service Business</option>
                                <option value="hybrid" @selected(old('business_model') === 'hybrid')>Hybrid Business</option>
                            </select>
                            @error('business_model')<div class="signup-error">{{ $message }}</div>@enderror
                        </label>
                        <label>
                            <div class="signup-label">Stage</div>
                            <select class="signup-select" required name="stage">
                                <option value="">Select your stage</option>
                                <option value="idea" @selected(old('stage') === 'idea')>Idea Stage</option>
                                <option value="launching" @selected(old('stage') === 'launching')>Launching</option>
                                <option value="operating" @selected(old('stage') === 'operating')>Operating</option>
                                <option value="scaling" @selected(old('stage') === 'scaling')>Scaling</option>
                            </select>
                            @error('stage')<div class="signup-error">{{ $message }}</div>@enderror
                        </label>
                    </div>
                    <div class="signup-grid-2">
                        <label>
                            <div class="signup-label">Primary City / Market</div>
                            <input class="signup-input" required type="text" name="primary_city" value="{{ old('primary_city') }}" placeholder="Austin, Cairo, London...">
                            @error('primary_city')<div class="signup-error">{{ $message }}</div>@enderror
                        </label>
                        <label>
                            <div class="signup-label">Service Radius / Delivery Scope</div>
                            <input class="signup-input" required type="text" name="service_radius" value="{{ old('service_radius') }}" placeholder="10 miles, nationwide shipping, city center...">
                            @error('service_radius')<div class="signup-error">{{ $message }}</div>@enderror
                        </label>
                    </div>
                    <label>
                        <div class="signup-label">Industry</div>
                        <select class="signup-select" required name="industry">
                            <option value="">Select your industry</option>
                            @foreach ($industryOptions as $industryOption)
                                <option value="{{ $industryOption }}" @selected(old('industry') === $industryOption)>{{ $industryOption }}</option>
                            @endforeach
                        </select>
                        @error('industry')<div class="signup-error">{{ $message }}</div>@enderror
                    </label>
                    <label>
                        <div class="signup-label">Company Brief</div>
                        <textarea class="signup-textarea" required name="company_brief" rows="4">{{ old('company_brief') }}</textarea>
                        @error('company_brief')<div class="signup-error">{{ $message }}</div>@enderror
                    </label>
                    <div class="signup-grid-2">
                        <label>
                            <div class="signup-label">Problem You Solve</div>
                            <textarea class="signup-textarea" required name="problem_solved" rows="3">{{ old('problem_solved') }}</textarea>
                            @error('problem_solved')<div class="signup-error">{{ $message }}</div>@enderror
                        </label>
                        <label>
                            <div class="signup-label">What Makes You Different?</div>
                            <textarea class="signup-textarea" required name="differentiators" rows="3">{{ old('differentiators') }}</textarea>
                            @error('differentiators')<div class="signup-error">{{ $message }}</div>@enderror
                        </label>
                    </div>
                    <div class="signup-grid-2">
                        <label>
                            <div class="signup-label">Target Audience</div>
                            <select class="signup-select" required name="target_audience">
                                <option value="">Select your audience</option>
                                <option value="Consumers / B2C" @selected(old('target_audience') === 'Consumers / B2C')>Consumers / B2C</option>
                                <option value="Small businesses / SMB" @selected(old('target_audience') === 'Small businesses / SMB')>Small businesses / SMB</option>
                                <option value="Corporate / Enterprise" @selected(old('target_audience') === 'Corporate / Enterprise')>Corporate / Enterprise</option>
                                <option value="Creators / Personal brands" @selected(old('target_audience') === 'Creators / Personal brands')>Creators / Personal brands</option>
                                <option value="Local community / Neighborhood market" @selected(old('target_audience') === 'Local community / Neighborhood market')>Local community / Neighborhood market</option>
                            </select>
                            @error('target_audience')<div class="signup-error">{{ $message }}</div>@enderror
                        </label>
                        <label>
                            <div class="signup-label">Primary ICP Name</div>
                            <input class="signup-input" required type="text" name="primary_icp_name" value="{{ old('primary_icp_name') }}" placeholder="Busy dog owners, first-time homebuyers...">
                            @error('primary_icp_name')<div class="signup-error">{{ $message }}</div>@enderror
                        </label>
                    </div>
                    <div class="signup-grid-2">
                        <label>
                            <div class="signup-label">Ideal Customer Profile</div>
                            <textarea class="signup-textarea" required name="ideal_customer_profile" rows="3">{{ old('ideal_customer_profile') }}</textarea>
                            @error('ideal_customer_profile')<div class="signup-error">{{ $message }}</div>@enderror
                        </label>
                        <label>
                            <div class="signup-label">Top Pain Points</div>
                            <textarea class="signup-textarea" required name="pain_points" rows="3" placeholder="Separate with commas">{{ old('pain_points') }}</textarea>
                            @error('pain_points')<div class="signup-error">{{ $message }}</div>@enderror
                        </label>
                    </div>
                    <div class="signup-grid-2">
                        <label>
                            <div class="signup-label">Desired Outcomes</div>
                            <textarea class="signup-textarea" required name="desired_outcomes" rows="3" placeholder="Separate with commas">{{ old('desired_outcomes') }}</textarea>
                            @error('desired_outcomes')<div class="signup-error">{{ $message }}</div>@enderror
                        </label>
                        <label>
                            <div class="signup-label">Common Objections</div>
                            <textarea class="signup-textarea" required name="objections" rows="3" placeholder="Separate with commas">{{ old('objections') }}</textarea>
                            @error('objections')<div class="signup-error">{{ $message }}</div>@enderror
                        </label>
                    </div>
                    <div class="signup-grid-2">
                        <label>
                            <div class="signup-label">Brand Voice</div>
                            <select class="signup-select" required name="brand_voice">
                                <option value="">Select a brand voice</option>
                                <option value="Warm and supportive" @selected(old('brand_voice') === 'Warm and supportive')>Warm and supportive</option>
                                <option value="Premium and polished" @selected(old('brand_voice') === 'Premium and polished')>Premium and polished</option>
                                <option value="Bold and energetic" @selected(old('brand_voice') === 'Bold and energetic')>Bold and energetic</option>
                                <option value="Professional and credible" @selected(old('brand_voice') === 'Professional and credible')>Professional and credible</option>
                                <option value="Friendly and simple" @selected(old('brand_voice') === 'Friendly and simple')>Friendly and simple</option>
                            </select>
                            @error('brand_voice')<div class="signup-error">{{ $message }}</div>@enderror
                        </label>
                        <label>
                            <div class="signup-label">Core Offer</div>
                            <select class="signup-select" required name="core_offer">
                                <option value="">Select your core offer</option>
                                @foreach ($coreOfferOptions as $coreOfferOption)
                                    <option value="{{ $coreOfferOption }}" @selected(old('core_offer') === $coreOfferOption)>{{ $coreOfferOption }}</option>
                                @endforeach
                            </select>
                            @error('core_offer')<div class="signup-error">{{ $message }}</div>@enderror
                        </label>
                    </div>
                    <div class="signup-grid-2">
                        <label>
                            <div class="signup-label">Primary Growth Goal</div>
                            <select class="signup-select" required name="primary_growth_goal">
                                <option value="">Select your goal</option>
                                <option value="Launch my first website" @selected(old('primary_growth_goal') === 'Launch my first website')>Launch my first website</option>
                                <option value="Get my first customers" @selected(old('primary_growth_goal') === 'Get my first customers')>Get my first customers</option>
                                <option value="Increase recurring sales" @selected(old('primary_growth_goal') === 'Increase recurring sales')>Increase recurring sales</option>
                                <option value="Build a stronger brand presence" @selected(old('primary_growth_goal') === 'Build a stronger brand presence')>Build a stronger brand presence</option>
                                <option value="Systemize and scale operations" @selected(old('primary_growth_goal') === 'Systemize and scale operations')>Systemize and scale operations</option>
                            </select>
                            @error('primary_growth_goal')<div class="signup-error">{{ $message }}</div>@enderror
                        </label>
                        <label>
                            <div class="signup-label">Current Biggest Blocker</div>
                            <select class="signup-select" required name="known_blockers">
                                <option value="">Select a blocker</option>
                                <option value="No clear offer yet" @selected(old('known_blockers') === 'No clear offer yet')>No clear offer yet</option>
                                <option value="No website or weak funnel" @selected(old('known_blockers') === 'No website or weak funnel')>No website or weak funnel</option>
                                <option value="Low traffic or visibility" @selected(old('known_blockers') === 'Low traffic or visibility')>Low traffic or visibility</option>
                                <option value="Low conversions or sales" @selected(old('known_blockers') === 'Low conversions or sales')>Low conversions or sales</option>
                                <option value="Limited time or team capacity" @selected(old('known_blockers') === 'Limited time or team capacity')>Limited time or team capacity</option>
                            </select>
                            @error('known_blockers')<div class="signup-error">{{ $message }}</div>@enderror
                        </label>
                    </div>
                    <div class="signup-actions">
                        <button class="signup-submit" type="submit">Complete founder signup</button>
                        <a class="signup-secondary" href="{{ route('plans') }}">Back to plans</a>
                    </div>
                    <p class="signup-footnote">Every field is still required so Hatchers OS can personalize the workspace from day one.</p>
                </form>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const scene = document.querySelector('[data-signup-scene]');
            const banner = document.querySelector('[data-signup-entry-banner]');
            const bannerTitle = document.querySelector('[data-signup-entry-title]');
            const bannerCopy = document.querySelector('[data-signup-entry-copy]');
            const bannerBadge = document.querySelector('[data-signup-entry-badge]');

            if (!scene) {
                return;
            }

            let transitionState = null;

            try {
                transitionState = JSON.parse(sessionStorage.getItem('hatchersPlanLaunchTransition') || 'null');
                if (transitionState) {
                    sessionStorage.removeItem('hatchersPlanLaunchTransition');
                }
            } catch (error) {
                transitionState = null;
            }

            if (!transitionState || !transitionState.planName) {
                return;
            }

            scene.classList.add('is-launch-arrival');

            if (banner && bannerTitle && bannerCopy && bannerBadge) {
                banner.classList.add('is-visible');
                bannerTitle.textContent = `${transitionState.planName} selected`;
                bannerCopy.textContent = `Now shaping your onboarding around ${String(transitionState.planBestFor || 'your business').toLowerCase()}.`;
                if (transitionState.planLabel) {
                    bannerBadge.textContent = transitionState.planLabel;
                }
            }

            window.requestAnimationFrame(() => {
                window.requestAnimationFrame(() => {
                    scene.classList.add('is-launch-ready');
                });
            });
        });
    </script>
@endsection
