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
            padding: 28px;
            background:
                radial-gradient(circle at 84% 14%, rgba(234, 187, 199, 0.24), transparent 0 18%),
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

        .signup-frame {
            position: relative;
            z-index: 1;
            min-height: calc(100vh - 56px);
            display: grid;
            grid-template-columns: minmax(300px, 0.72fr) minmax(0, 1.28fr);
            gap: 22px;
            opacity: 1;
            transition: opacity 320ms ease, transform 420ms ease, filter 420ms ease;
        }

        .signup-rail,
        .signup-form-panel {
            border-radius: 28px;
            border: 1px solid rgba(214, 201, 184, 0.84);
            background:
                linear-gradient(180deg, rgba(249, 244, 238, 0.92), rgba(240, 230, 220, 0.94));
            box-shadow:
                0 18px 54px rgba(71, 52, 31, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.72);
            backdrop-filter: blur(14px);
        }

        .signup-scene.is-launch-arrival .signup-frame {
            opacity: 0;
            transform: translateY(20px) scale(0.986);
            filter: blur(8px);
        }

        .signup-scene.is-launch-arrival.is-launch-ready .signup-frame {
            opacity: 1;
            transform: translateY(0) scale(1);
            filter: blur(0);
        }

        .signup-rail {
            padding: 24px;
            display: grid;
            gap: 18px;
            align-content: start;
            position: sticky;
            top: 28px;
            height: calc(100vh - 56px);
        }

        .signup-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #2c241f;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .signup-brand-mark {
            width: 22px;
            height: 22px;
            border-radius: 7px;
            background: linear-gradient(135deg, #e11d74, #ef4444);
            box-shadow: 0 10px 26px rgba(225, 29, 116, 0.28);
        }

        .signup-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.72rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: rgba(116, 97, 86, 0.72);
        }

        .signup-eyebrow::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: rgba(225, 29, 116, 0.7);
        }

        .signup-rail h1,
        .signup-form-panel h1 {
            font-family: "Inter Tight", "Inter", "Avenir Next", sans-serif;
            font-size: clamp(2.4rem, 4vw, 3.8rem);
            line-height: 0.96;
            letter-spacing: -0.06em;
            margin: 0;
            color: #171310;
        }

        .signup-copy {
            color: rgba(92, 76, 67, 0.84);
            line-height: 1.65;
            font-size: 0.98rem;
        }

        .signup-plan,
        .signup-note,
        .signup-pillars {
            border-radius: 22px;
            border: 1px solid rgba(225, 212, 198, 0.9);
            background: rgba(255,255,255,0.62);
            padding: 18px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.8);
        }

        .signup-plan-label,
        .signup-note-title,
        .signup-pillars-title {
            display: block;
            margin-bottom: 10px;
            font-size: 0.72rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(117, 100, 89, 0.7);
        }

        .signup-plan-name {
            font-family: "Inter Tight", "Inter", sans-serif;
            font-size: 1.28rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: #171310;
            margin-bottom: 6px;
        }

        .signup-pillar-list {
            display: grid;
            gap: 10px;
            margin: 0;
            padding: 0;
            list-style: none;
            color: rgba(85, 70, 61, 0.88);
        }

        .signup-pillar-list li {
            position: relative;
            padding-left: 18px;
        }

        .signup-pillar-list li::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0.55em;
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: rgba(225, 29, 116, 0.68);
        }

        .signup-form-panel {
            padding: 28px;
        }

        .signup-rail,
        .signup-form-panel {
            transition: transform 460ms cubic-bezier(.22,1,.36,1), opacity 360ms ease;
        }

        .signup-scene.is-launch-arrival .signup-rail {
            opacity: 0;
            transform: translateX(-28px);
        }

        .signup-scene.is-launch-arrival .signup-form-panel {
            opacity: 0;
            transform: translateX(28px);
        }

        .signup-scene.is-launch-arrival.is-launch-ready .signup-rail,
        .signup-scene.is-launch-arrival.is-launch-ready .signup-form-panel {
            opacity: 1;
            transform: translateX(0);
        }

        .signup-alert {
            margin-bottom: 16px;
            border-radius: 16px;
            padding: 14px 16px;
        }

        .signup-alert.error {
            border: 1px solid rgba(179, 34, 83, 0.2);
            background: rgba(179, 34, 83, 0.06);
            color: var(--rose);
        }

        .signup-form-grid {
            display: grid;
            gap: 16px;
        }

        .signup-grid-2,
        .signup-grid-3 {
            display: grid;
            gap: 16px;
        }

        .signup-grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .signup-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }

        .signup-label {
            display: block;
            margin-bottom: 7px;
            font-size: 0.9rem;
            color: rgba(96, 81, 73, 0.86);
        }

        .signup-input,
        .signup-select,
        .signup-textarea {
            width: 100%;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid rgba(220, 207, 191, 0.94);
            background: rgba(255, 255, 255, 0.9);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.78);
            color: #191411;
            font: inherit;
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
            padding: 14px 18px;
            font-weight: 700;
            font: inherit;
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
            font-size: 0.92rem;
        }

        .signup-entry-banner {
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 16px;
            margin-bottom: 18px;
            border-radius: 18px;
            border: 1px solid rgba(223, 210, 197, 0.92);
            background: rgba(255,255,255,0.68);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.8);
        }

        .signup-entry-banner.is-visible {
            display: flex;
        }

        .signup-entry-banner strong {
            display: block;
            color: #171310;
            letter-spacing: -0.02em;
        }

        .signup-entry-banner span {
            color: rgba(96, 81, 73, 0.84);
            font-size: 0.94rem;
        }

        .signup-entry-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            padding: 9px 12px;
            border-radius: 999px;
            background: rgba(244, 238, 229, 0.92);
            border: 1px solid rgba(223, 210, 197, 0.92);
            color: rgba(90, 72, 62, 0.88);
            font-size: 0.8rem;
            font-weight: 700;
        }

        .signup-entry-badge::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: linear-gradient(135deg, #e11d74, #ef4444);
        }

        @media (max-width: 1080px) {
            .signup-frame {
                grid-template-columns: 1fr;
            }

            .signup-rail {
                position: static;
                height: auto;
            }
        }

        @media (max-width: 840px) {
            .signup-grid-2,
            .signup-grid-3 {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    <div class="signup-scene" data-signup-scene>
        <div class="signup-frame">
            <aside class="signup-rail">
                <a class="signup-brand" href="/">
                    <span class="signup-brand-mark"></span>
                    <span>Hatchers OS</span>
                </a>
                <div class="signup-eyebrow">Founder Signup</div>
                <h1>Create your founder workspace.</h1>
                <p class="signup-copy">
                    You’re signing up on the <strong>{{ $selectedPlan['name'] }}</strong> plan. Finish this setup once, and the OS can personalize your website, commerce, learning, and execution surfaces around your business.
                </p>
                <div class="signup-plan">
                    <span class="signup-plan-label">Selected plan</span>
                    <div class="signup-plan-name">{{ $selectedPlan['label'] }}</div>
                    <div class="signup-copy">{{ $selectedPlan['description'] }}</div>
                </div>
                <div class="signup-pillars">
                    <span class="signup-pillars-title">What this setup unlocks</span>
                    <ul class="signup-pillar-list">
                        <li>Personalized founder desktop and Atlas mentoring</li>
                        <li>Website and commerce flow matched to your business model</li>
                        <li>Tasks, learning, and first-customer execution built around your ICP</li>
                    </ul>
                </div>
                <div class="signup-note">
                    <span class="signup-note-title">Important</span>
                    <div class="signup-copy">All fields are required so the OS can give founders better guidance from day one.</div>
                </div>
            </aside>

            <section class="signup-form-panel">
                <div class="signup-eyebrow">Onboarding Form</div>
                <h1>Tell Hatchers how your business works.</h1>
                <p class="signup-copy" style="margin: 12px 0 18px;">This gives the OS the context it needs to build the right founder journey.</p>

                <div class="signup-entry-banner" data-signup-entry-banner>
                    <div>
                        <strong data-signup-entry-title>Founder mode selected</strong>
                        <span data-signup-entry-copy>We’re preparing your workspace and keeping your onboarding context ready.</span>
                    </div>
                    <div class="signup-entry-badge" data-signup-entry-badge>{{ $selectedPlan['label'] }}</div>
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
                <p class="signup-footnote">Every field above is mandatory. If anything is missing, Hatchers OS will keep your current inputs and show you exactly what to fix.</p>
            </form>
            </section>
        </div>
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
                bannerCopy.textContent = `Now shaping your onboarding around ${String(transitionState.planBestFor || 'your founder workflow').toLowerCase()}.`;
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
