@extends('os.layout')

@section('content')
    <div class="public-shell">
        <section class="hero">
            <div class="eyebrow">Media Library</div>
            <h1>Shared assets from across your OS workflows.</h1>
            <p class="muted">This library brings together campaign drafts, content assets, and commerce offer copy so the OS starts acting like one product layer.</p>
        </section>

        <section class="grid-2">
            <div class="card">
                <h2>Asset Feed</h2>
                <div class="stack" style="margin-top:14px;">
                    @forelse ($assets as $asset)
                        <div class="stack-item">
                            <div class="pill">{{ $asset['type'] }}</div>
                            <strong style="display:block;margin-top:10px;">{{ $asset['title'] }}</strong>
                            <div class="muted" style="margin-top:6px;">{{ $asset['description'] }}</div>
                            <div class="muted" style="margin-top:6px;">Source: {{ $asset['source'] }}</div>
                        </div>
                    @empty
                        <div class="stack-item">
                            <strong>No assets yet</strong><br>
                            <span class="muted">As you generate campaigns, content drafts, and offers in the OS, they will start appearing here.</span>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <h2>Website Themes</h2>
                <div class="stack" style="margin-top:14px;">
                    @foreach (($website['themes'] ?? []) as $theme)
                        <div class="stack-item">
                            <strong>{{ $theme['name'] }}</strong><br>
                            <span class="muted">{{ $theme['description'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
@endsection
