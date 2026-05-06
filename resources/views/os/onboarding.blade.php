@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'minimal-signup-page')

@section('head')
    <style>
        .page.minimal-signup-page {
            min-height: 100vh;
            padding: 0;
            font-family: "Inter", "Avenir Next", "Segoe UI", sans-serif;
        }

        .ms-scene {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 28px;
            background:
                radial-gradient(circle at 82% 16%, rgba(241, 121, 145, 0.18), transparent 0 18%),
                linear-gradient(180deg, #f4efe8 0%, #e8dfd4 100%);
        }

        .ms-card {
            width: min(100%, 560px);
            border-radius: 34px;
            border: 1px solid rgba(118, 101, 90, 0.12);
            background: rgba(255, 251, 247, 0.92);
            box-shadow: 0 32px 70px rgba(33, 23, 18, 0.14);
            padding: 30px;
        }

        .ms-mark {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            background: linear-gradient(135deg, #f13b74, #f26444);
            box-shadow: 0 18px 34px rgba(241, 59, 116, 0.24);
            margin-bottom: 16px;
        }

        .ms-kicker {
            margin: 0 0 10px;
            font-size: 0.78rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(105, 88, 77, 0.68);
        }

        .ms-title {
            margin: 0;
            font-family: "Inter Tight", "Inter", sans-serif;
            font-size: clamp(2rem, 4vw, 3rem);
            line-height: 0.94;
            letter-spacing: -0.05em;
            color: #171310;
        }

        .ms-copy {
            margin: 14px 0 0;
            font-size: 0.98rem;
            line-height: 1.6;
            color: rgba(88, 73, 63, 0.82);
        }

        .ms-pill {
            margin-top: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(245, 238, 231, 0.96);
            border: 1px solid rgba(118, 101, 90, 0.12);
            color: rgba(91, 74, 64, 0.84);
            font-size: 0.84rem;
            font-weight: 600;
        }

        .ms-pill::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: linear-gradient(135deg, #f13b74, #f26444);
        }

        .ms-form {
            margin-top: 24px;
            display: grid;
            gap: 16px;
        }

        .ms-field label {
            display: block;
            margin-bottom: 7px;
            font-size: 0.86rem;
            color: rgba(88, 73, 63, 0.82);
        }

        .ms-field input {
            width: 100%;
            min-height: 56px;
            border-radius: 18px;
            border: 1px solid rgba(118, 101, 90, 0.14);
            background: rgba(255,255,255,0.95);
            padding: 0 16px;
            font: inherit;
            color: #181210;
        }

        .ms-btn {
            min-height: 58px;
            border-radius: 20px;
            border: 0;
            background: #111;
            color: #fff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .ms-note {
            margin-top: 18px;
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(245, 238, 231, 0.94);
            border: 1px solid rgba(118, 101, 90, 0.12);
            color: rgba(91, 74, 64, 0.84);
            line-height: 1.5;
            font-size: 0.9rem;
        }

        .ms-error {
            color: #b32253;
            font-size: 0.84rem;
            margin-top: 6px;
        }

        .ms-alert {
            margin-top: 16px;
            padding: 14px 16px;
            border-radius: 18px;
            border: 1px solid rgba(179, 34, 83, 0.18);
            background: rgba(179, 34, 83, 0.06);
            color: #b32253;
        }
    </style>
@endsection

@section('content')
    <div class="ms-scene">
        <div class="ms-card">
            <div class="ms-mark"></div>
            <p class="ms-kicker">Hatchers OS signup</p>
            <h1 class="ms-title">Create the account first. We’ll shape the business inside the chat.</h1>
            <p class="ms-copy">
                Choose your plan, create the founder login, then Hatchers will collect the business context conversationally and turn it into your launch plan, tasks, and website direction.
            </p>

            <div class="ms-pill">{{ $selectedPlan['name'] ?? 'Selected plan' }}</div>

            @if($errors->has('signup'))
                <div class="ms-alert">{{ $errors->first('signup') }}</div>
            @endif

            <form method="POST" action="{{ route('onboarding.store') }}" class="ms-form">
                @csrf
                <input type="hidden" name="plan_code" value="{{ $selectedPlan['code'] }}">

                <div class="ms-field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">
                    @error('email')<div class="ms-error">{{ $message }}</div>@enderror
                </div>

                <div class="ms-field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password">
                    @error('password')<div class="ms-error">{{ $message }}</div>@enderror
                </div>

                <div class="ms-field">
                    <label for="password_confirmation">Confirm Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                </div>

                <button type="submit" class="ms-btn">Create founder account</button>
            </form>

            <div class="ms-note">
                After this, Hatchers will ask the onboarding questions inside the OS chat and build your launch plan from there instead of making you fill a long form up front.
            </div>
        </div>
    </div>
@endsection
