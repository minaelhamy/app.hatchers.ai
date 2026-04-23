@extends('os.layout')

@section('content')
    <div class="public-shell">
        <section class="hero">
            <div class="eyebrow">Analytics</div>
            <h1>Unified performance across execution, growth, and marketing.</h1>
            <p class="muted">This workspace turns your OS data into one reporting surface instead of splitting insight across modules.</p>
        </section>

        <section class="metrics" style="margin-bottom:22px;">
            @foreach ($analytics['headline_metrics'] as $metric)
                <div class="card metric">
                    <div class="muted">{{ $metric['label'] }}</div>
                    <strong>{{ $metric['value'] }}</strong>
                </div>
            @endforeach
        </section>

        <section class="grid-3">
            <div class="card">
                <h2>Execution</h2>
                <div class="stack" style="margin-top:14px;">
                    @foreach ($analytics['execution'] as $item)
                        <div class="stack-item"><strong>{{ $item['value'] }}</strong><br><span class="muted">{{ $item['label'] }}</span></div>
                    @endforeach
                </div>
            </div>
            <div class="card">
                <h2>Growth</h2>
                <div class="stack" style="margin-top:14px;">
                    @foreach ($analytics['growth'] as $item)
                        <div class="stack-item"><strong>{{ $item['value'] }}</strong><br><span class="muted">{{ $item['label'] }}</span></div>
                    @endforeach
                </div>
            </div>
            <div class="card">
                <h2>Marketing</h2>
                <div class="stack" style="margin-top:14px;">
                    @foreach ($analytics['marketing'] as $item)
                        <div class="stack-item"><strong>{{ $item['value'] }}</strong><br><span class="muted">{{ $item['label'] }}</span></div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
@endsection
