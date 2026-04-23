@extends('os.layout')

@section('content')
    <div class="public-shell narrow" style="padding-top: 8px;">
        <section class="hero">
            <div class="eyebrow">Unified Search</div>
            <h1>Search across your OS work.</h1>
            <p class="muted">Find tasks, lessons, campaigns, offers, and activity without jumping between tools.</p>
            <form method="GET" action="{{ route('founder.search') }}" class="cta-row" style="margin-top:14px;">
                <input type="text" name="q" value="{{ $searchQuery }}" placeholder="Search your founder workspace" style="flex:1;min-width:240px;padding:12px 14px;border-radius:14px;border:1px solid var(--line);background:#fff;">
                <button class="btn primary" type="submit">Search</button>
            </form>
        </section>

        <section class="card">
            <h2>Results</h2>
            <div class="stack" style="margin-top:14px;">
                @forelse ($results as $result)
                    <a href="{{ $result['href'] }}" class="stack-item" style="text-decoration:none;color:inherit;">
                        <div class="pill">{{ $result['type'] }}</div>
                        <strong style="display:block;margin-top:10px;">{{ $result['title'] }}</strong>
                        <div class="muted" style="margin-top:6px;">{{ $result['description'] }}</div>
                    </a>
                @empty
                    <div class="stack-item">
                        <strong>{{ $searchQuery !== '' ? 'No matches yet' : 'Start with a search' }}</strong><br>
                        <span class="muted">{{ $searchQuery !== '' ? 'Try a task title, campaign name, offer, or a phrase from your recent activity.' : 'Search is unified across the founder OS state that already lives inside Hatchers Ai Business OS.' }}</span>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
