@extends('os.layout')

@section('content')
    <section class="hero">
        <div class="eyebrow">Plans</div>
        <h1>Choose how you want to build with Hatchers OS.</h1>
        <p class="muted">The platform is structured around one self-serve operating system plan and one mentor-guided plan.</p>
    </section>

    <section class="grid-2">
        <div class="plan-card">
            <div class="pill">Self-serve</div>
            <h2 style="margin-top: 14px;">Hatchers OS</h2>
            <div class="price">$99<span style="font-size: 1rem; font-weight: 500;">/month</span></div>
            <p class="muted">For founders who want the full OS, unified AI, website tools, content generation, and business workflows without a mentor.</p>
            <div class="stack" style="margin-top: 18px;">
                <div class="stack-item">Unified founder dashboard</div>
                <div class="stack-item">Atlas assistant across all workflows</div>
                <div class="stack-item">Website building for product or service businesses</div>
                <div class="stack-item">Marketing and content studio</div>
            </div>
        </div>

        <div class="plan-card">
            <div class="pill">Guided growth</div>
            <h2 style="margin-top: 14px;">Hatchers OS + Mentor</h2>
            <div class="price">$600<span style="font-size: 1rem; font-weight: 500;">/month</span></div>
            <p class="muted">Mentor-guided support for the first 6 months, then transitions to the standard OS subscription at $99/month.</p>
            <div class="stack" style="margin-top: 18px;">
                <div class="stack-item">Everything in Hatchers OS</div>
                <div class="stack-item">Assigned mentor and weekly execution rhythm</div>
                <div class="stack-item">Tasks, milestones, and meeting guidance</div>
                <div class="stack-item">Atlas aware of mentor context and founder progress</div>
            </div>
        </div>
    </section>
@endsection
