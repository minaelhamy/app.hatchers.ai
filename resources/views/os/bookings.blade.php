@extends('os.layout')

@section('hide_topbar', '1')
@section('page_class', 'founder-home-page')

@section('head')
    <style>
        .page.founder-home-page { padding: 0; }
        .ops-shell { min-height: 100vh; display:grid; grid-template-columns:220px minmax(0,1fr) 220px; background:#f8f5ee; }
        .ops-sidebar, .ops-rightbar { background: rgba(255,252,247,0.8); border-color: var(--line); border-style: solid; border-width:0 1px 0 0; min-height:100vh; display:flex; flex-direction:column; }
        .ops-rightbar { border-width:0 0 0 1px; background: rgba(255,251,246,0.9); }
        .ops-sidebar-inner, .ops-rightbar-inner { padding:22px 18px; }
        .ops-brand { display:inline-block; margin-bottom:24px; }
        .ops-brand img { width:168px; height:auto; display:block; }
        .ops-nav { display:grid; gap:6px; }
        .ops-nav-item { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:14px; text-decoration:none; color:var(--ink); font-size:0.98rem; }
        .ops-nav-item.active { background:#ece6db; }
        .ops-nav-icon { width:18px; text-align:center; color:var(--muted); }
        .ops-sidebar-footer { margin-top:auto; padding:18px; border-top:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .ops-user { display:flex; align-items:center; gap:10px; }
        .ops-avatar { width:30px; height:30px; border-radius:999px; background:#b0a999; color:#fff; display:grid; place-items:center; font-weight:700; font-size:0.92rem; flex-shrink:0; }
        .ops-main { padding:26px 28px 24px; }
        .ops-main-inner { max-width:780px; margin:0 auto; }
        .ops-main h1 { font-size: clamp(2rem, 3vw, 3rem); letter-spacing:-0.02em; margin-bottom:6px; }
        .ops-main p { color:var(--muted); margin-bottom:24px; }
        .ops-metrics { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:12px; margin-bottom:22px; }
        .ops-metric, .ops-card, .rail-item, .mini-note { background: rgba(255,255,255,0.92); border:1px solid rgba(220,207,191,0.65); border-radius:18px; padding:16px 18px; box-shadow:0 10px 28px rgba(52,41,26,0.04); }
        .ops-metric strong { display:block; font-size:1.55rem; margin-top:6px; }
        .ops-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:12px; }
        .ops-stack { display:grid; gap:10px; }
        @media (max-width:1240px) { .ops-shell { grid-template-columns:220px 1fr; } .ops-rightbar { display:none; } }
        @media (max-width:900px) { .ops-shell { grid-template-columns:1fr; } .ops-sidebar { min-height:auto; border-right:0; border-bottom:1px solid var(--line); } .ops-sidebar-footer { display:none; } .ops-main { padding:20px 16px 24px; } .ops-grid, .ops-metrics { grid-template-columns:1fr; } }
    </style>
@endsection

@section('content')
    @php
        $founder = $dashboard['founder'];
        $ops = $bookingWorkspace;
        $recentBookings = $ops['recent_bookings'] ?? [];
        $recentCoupons = $ops['recent_coupons'] ?? [];
        $filters = $bookingFilters ?? ['status' => 'all', 'queue' => 'all', 'q' => ''];
        $queueLabels = ['all' => 'All booking work', 'pending' => 'Pending action', 'unscheduled' => 'Need scheduling', 'needs_staff' => 'Need staff assignment'];
        $activeQueueLabel = $queueLabels[$filters['queue'] ?? 'all'] ?? 'All booking work';
        $pendingBookings = collect($recentBookings)->whereIn('status', ['pending', 'processing'])->count();
        $unscheduledBookings = collect($recentBookings)->filter(fn ($booking) => trim((string) ($booking['booking_date'] ?? '')) === '' || trim((string) ($booking['booking_time'] ?? '')) === '')->count();
        $needsStaffAssignment = collect($recentBookings)->filter(fn ($booking) => in_array((string) ($booking['status'] ?? ''), ['pending', 'processing'], true) && trim((string) ($booking['staff_id'] ?? '')) === '')->count();
        $awaitingPaymentBookings = collect($recentBookings)->where('payment_status', 'unpaid')->count();
    @endphp
    <div class="ops-shell">
        <aside class="ops-sidebar">
            <div class="ops-sidebar-inner">
                <a class="ops-brand" href="/dashboard/founder"><img src="/brand/hatchers-ai-logo.png" alt="Hatchers AI"></a>
                <nav class="ops-nav">
                    <a class="ops-nav-item" href="/dashboard/founder"><span class="ops-nav-icon">⌂</span><span>Home</span></a>
                    <a class="ops-nav-item active" href="{{ route('founder.commerce') }}"><span class="ops-nav-icon">⌁</span><span>Commerce</span></a>
                    <a class="ops-nav-item" href="{{ route('founder.ai-tools') }}"><span class="ops-nav-icon">✦</span><span>AI Tools</span></a>
                    <a class="ops-nav-item" href="{{ route('founder.learning-plan') }}"><span class="ops-nav-icon">▣</span><span>Learning Plan</span></a>
                    <a class="ops-nav-item" href="{{ route('founder.tasks') }}"><span class="ops-nav-icon">◌</span><span>Tasks</span></a>
                    <a class="ops-nav-item" href="{{ route('founder.settings') }}"><span class="ops-nav-icon">⚙</span><span>Settings</span></a>
                </nav>
            </div>
            <div class="ops-sidebar-footer">
                <div class="ops-user">
                    <div class="ops-avatar">{{ strtoupper(substr($founder->full_name, 0, 1)) }}</div>
                    <div>{{ $founder->full_name }}</div>
                </div>
                <form method="POST" action="/logout" style="margin:0;">@csrf<button class="ops-nav-icon" type="submit" style="border:0;background:transparent;cursor:pointer;">↘</button></form>
            </div>
        </aside>

        <main class="ops-main">
            <div class="ops-main-inner">
                <h1>Bookings</h1>
                <p>Track Servio-driven bookings and service operations from Hatchers Ai Business OS while the service engine keeps running underneath.</p>

                <section class="ops-metrics">
                    <div class="ops-metric"><div class="muted">Bookings</div><strong>{{ $ops['counts']['bookings'] }}</strong></div>
                    <div class="ops-metric"><div class="muted">Services</div><strong>{{ $ops['counts']['services'] }}</strong></div>
                    <div class="ops-metric"><div class="muted">Customers</div><strong>{{ $ops['counts']['customers'] }}</strong></div>
                    <div class="ops-metric"><div class="muted">Revenue</div><strong>{{ $ops['currency'] }} {{ number_format($ops['gross_revenue'], 0) }}</strong></div>
                </section>

                <section class="ops-grid">
                    <div class="ops-card">
                        <h2 style="margin-bottom:12px;">Services Overview</h2>
                        <div class="ops-stack">
                            <div><strong>{{ $ops['website_title'] }}</strong></div>
                            <div class="muted">Readiness {{ $ops['readiness_score'] }}% · Last synced {{ $ops['updated_at'] ?: 'Not synced yet' }}</div>
                            <div class="muted">This page is the OS-native operations view for Servio booking data.</div>
                            <div style="margin-top:8px;">
                                <a class="pill" href="{{ route('founder.commerce') }}">Back to Commerce</a>
                            </div>
                        </div>
                    </div>

                    <div class="ops-card">
                        <h2 style="margin-bottom:12px;">Booking Activity</h2>
                        <div class="ops-stack">
                            @forelse ($ops['activity'] as $item)
                                <div class="rail-item">{{ $item }}</div>
                            @empty
                                <div class="rail-item">No Servio booking activity has synced yet.</div>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section class="ops-metrics" style="margin-top:12px;">
                    <div class="ops-metric"><div class="muted">Pending action</div><strong>{{ $pendingBookings }}</strong></div>
                    <div class="ops-metric"><div class="muted">Awaiting payment</div><strong>{{ $awaitingPaymentBookings }}</strong></div>
                    <div class="ops-metric"><div class="muted">Unscheduled</div><strong>{{ $unscheduledBookings }}</strong></div>
                    <div class="ops-metric"><div class="muted">Need staff</div><strong>{{ $needsStaffAssignment }}</strong></div>
                </section>

                <section class="ops-card" style="margin-top:12px;">
                    <h2 style="margin-bottom:12px;">Filter Bookings</h2>
                    <form method="GET" action="{{ route('founder.commerce.bookings') }}" style="display:grid;gap:10px;grid-template-columns:repeat(2,minmax(0,1fr));">
                        <label>
                            <span class="muted">Queue</span>
                            <select name="queue" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;">
                                @foreach ($queueLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(($filters['queue'] ?? 'all') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span class="muted">Status</span>
                            <select name="status" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;">
                                @foreach (['all' => 'All statuses', 'pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                                    <option value="{{ $value }}" @selected(($filters['status'] ?? 'all') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span class="muted">Search</span>
                            <input name="q" type="text" value="{{ $filters['q'] ?? '' }}" placeholder="Booking number, customer, service" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;">
                        </label>
                        <div style="grid-column:1 / -1;display:flex;gap:10px;flex-wrap:wrap;">
                            <button class="pill" type="submit" style="border:0;cursor:pointer;">Apply filters</button>
                            <a class="ops-nav-item active" href="{{ route('founder.commerce.bookings') }}" style="display:inline-flex;text-decoration:none;">Clear</a>
                        </div>
                    </form>
                    <div class="muted" style="margin-top:12px;">Showing queue: {{ $activeQueueLabel }}</div>
                </section>

                <section class="ops-card" style="margin-top:12px;">
                    <h2 style="margin-bottom:12px;">Booking Operations</h2>
                    <div class="ops-stack">
                        @forelse ($recentBookings as $booking)
                            <div class="rail-item">
                                <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
                                    <div>
                                        <strong>#{{ $booking['booking_number'] }}</strong><br>
                                        <span class="muted">{{ $booking['customer_name'] }} · {{ $booking['service_name'] }}</span><br>
                                        <span class="muted">Status {{ ucfirst($booking['status'] ?? 'pending') }} · Payment {{ ucfirst($booking['payment_status'] ?? 'unpaid') }}</span><br>
                                        <span class="muted">{{ $booking['customer_email'] ?? '' }}{{ !empty($booking['customer_mobile']) ? ' · ' . $booking['customer_mobile'] : '' }}</span>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('founder.commerce.bookings.update') }}" style="margin-top:14px;display:grid;gap:10px;">
                                    @csrf
                                    <input type="hidden" name="booking_number" value="{{ $booking['booking_number'] }}">
                                    <label>
                                        <span class="muted">Booking status</span>
                                        <select name="status" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;">
                                            @foreach (['pending', 'processing', 'completed', 'cancelled'] as $status)
                                                <option value="{{ $status }}" @selected(($booking['status'] ?? 'pending') === $status)>{{ ucfirst($status) }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label>
                                        <span class="muted">Payment status</span>
                                        <select name="payment_status" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;">
                                            @foreach (['unpaid', 'paid'] as $paymentStatus)
                                                <option value="{{ $paymentStatus }}" @selected(($booking['payment_status'] ?? 'unpaid') === $paymentStatus)>{{ ucfirst($paymentStatus) }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label>
                                        <span class="muted">Assigned staff member</span>
                                        <input name="staff_id" type="text" value="{{ $booking['staff_id'] ?? '' }}" placeholder="Optional staff member id" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;">
                                    </label>
                                    <label>
                                        <span class="muted">Vendor note</span>
                                        <textarea name="vendor_note" rows="3" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;">{{ $booking['vendor_note'] ?? '' }}</textarea>
                                    </label>
                                    <div><button class="pill" type="submit" style="border:0;cursor:pointer;">Save booking in OS</button></div>
                                </form>
                                <form method="POST" action="{{ route('founder.commerce.bookings.customer') }}" style="margin-top:14px;display:grid;gap:10px;">
                                    @csrf
                                    <input type="hidden" name="booking_number" value="{{ $booking['booking_number'] }}">
                                    <label><span class="muted">Customer name</span><input name="customer_name" type="text" value="{{ $booking['customer_name'] ?? '' }}" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                                    <label><span class="muted">Customer email</span><input name="customer_email" type="text" value="{{ $booking['customer_email'] ?? '' }}" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                                    <label><span class="muted">Customer mobile</span><input name="customer_mobile" type="text" value="{{ $booking['customer_mobile'] ?? '' }}" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                                    <label><span class="muted">Address</span><input name="address" type="text" value="{{ $booking['address'] ?? '' }}" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                                    <label><span class="muted">Landmark</span><input name="landmark" type="text" value="{{ $booking['landmark'] ?? '' }}" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                                    <label><span class="muted">Postal code</span><input name="postal_code" type="text" value="{{ $booking['postal_code'] ?? '' }}" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                                    <label><span class="muted">City</span><input name="city" type="text" value="{{ $booking['city'] ?? '' }}" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                                    <label><span class="muted">State</span><input name="state" type="text" value="{{ $booking['state'] ?? '' }}" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                                    <label><span class="muted">Country</span><input name="country" type="text" value="{{ $booking['country'] ?? '' }}" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                                    <div><button class="ops-nav-item active" type="submit" style="border:0;cursor:pointer;display:inline-flex;">Save customer details</button></div>
                                </form>
                                <details style="margin-top:14px;">
                                    <summary style="cursor:pointer;font-weight:600;">Booking details</summary>
                                    <div style="margin-top:12px;display:grid;gap:10px;">
                                        <div class="muted">Date {{ $booking['booking_date'] ?? 'Not set' }} · Time {{ $booking['booking_time'] ?? 'Not set' }}{{ !empty($booking['booking_endtime']) ? ' to ' . $booking['booking_endtime'] : '' }}</div>
                                        <div class="muted">Subtotal {{ $ops['currency'] }} {{ number_format((float) ($booking['sub_total'] ?? 0), 2) }} · Discount {{ $ops['currency'] }} {{ number_format((float) ($booking['offer_amount'] ?? 0), 2) }}{{ !empty($booking['offer_code']) ? ' · Code ' . $booking['offer_code'] : '' }}</div>
                                        @if (!empty($booking['additional_service_name']))
                                            <div class="rail-item"><strong>Additional services</strong><br><span class="muted">{{ str_replace('|', ', ', $booking['additional_service_name']) }}</span></div>
                                        @endif
                                        @if (!empty($booking['booking_notes']))
                                            <div class="rail-item">{{ $booking['booking_notes'] }}</div>
                                        @endif
                                        @if (!empty($booking['join_url']))
                                            <div><a class="pill" href="{{ $booking['join_url'] }}" target="_blank" rel="noreferrer">Open session link</a></div>
                                        @endif
                                        <form method="POST" action="{{ route('founder.commerce.bookings.schedule') }}" style="margin-top:6px;display:grid;gap:10px;">
                                            @csrf
                                            <input type="hidden" name="booking_number" value="{{ $booking['booking_number'] }}">
                                            <label><span class="muted">Booking date</span><input name="booking_date" type="date" value="{{ $booking['booking_date'] ?? '' }}" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                                            <label><span class="muted">Start time</span><input name="booking_time" type="text" value="{{ $booking['booking_time'] ?? '' }}" placeholder="10:00 AM" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                                            <label><span class="muted">End time</span><input name="booking_endtime" type="text" value="{{ $booking['booking_endtime'] ?? '' }}" placeholder="10:30 AM" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                                            <label><span class="muted">Booking notes</span><textarea name="booking_notes" rows="3" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;">{{ $booking['booking_notes'] ?? '' }}</textarea></label>
                                            <label><span class="muted">Message channel</span><select name="message_channel" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"><option value="manual">Manual note</option><option value="email">Email</option><option value="whatsapp">WhatsApp</option><option value="sms">SMS</option></select></label>
                                            <label>
                                                <span class="muted">Message template</span>
                                                <select name="message_template" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;">
                                                    <option value="">Write custom message</option>
                                                    <option value="confirmed">Booking confirmed</option>
                                                    <option value="rescheduled">Booking rescheduled</option>
                                                    <option value="provider_assigned">Provider assigned</option>
                                                    <option value="completed">Booking completed</option>
                                                </select>
                                            </label>
                                            <label><span class="muted">Customer update message</span><textarea name="customer_message" rows="3" placeholder="We have moved your booking to the new requested time." style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></textarea></label>
                                            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                                <button class="ops-nav-item" type="submit" name="message_template" value="confirmed" style="border:0;cursor:pointer;display:inline-flex;">Confirm booking</button>
                                                <button class="ops-nav-item" type="submit" name="message_template" value="rescheduled" style="border:0;cursor:pointer;display:inline-flex;">Rescheduled</button>
                                                <button class="ops-nav-item" type="submit" name="message_template" value="provider_assigned" style="border:0;cursor:pointer;display:inline-flex;">Assign provider</button>
                                            </div>
                                            <div><button class="pill" type="submit" style="border:0;cursor:pointer;">Save schedule details</button></div>
                                        </form>
                                        @if (!empty($booking['communication_timeline']))
                                            <div style="display:grid;gap:8px;">
                                                <strong>Communication timeline</strong>
                                                @foreach ($booking['communication_timeline'] as $event)
                                                    <div class="rail-item">
                                                        <strong>{{ ucfirst($event['channel']) }}</strong> · <span class="muted">{{ $event['timestamp'] }}</span><br>
                                                        <span class="muted">{{ $event['message'] }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </details>
                            </div>
                        @empty
                            <div class="rail-item">No Servio bookings have synced into the OS yet.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        </main>

        <aside class="ops-rightbar">
            <div class="ops-rightbar-inner">
                <h3 style="font-size:0.83rem;letter-spacing:0.06em;text-transform:uppercase;color:var(--muted);margin-bottom:12px;">Connected Tools</h3>
                <div class="ops-stack">
                    @foreach ($launchCards as $launch)
                        <div class="rail-item">
                            <strong>{{ $launch['label'] }}</strong><br>
                            <span class="muted">{{ $launch['description'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="mini-note" style="margin-top:18px;">
                    <strong>Service coupons</strong><br>
                    @if (!empty($recentCoupons))
                        {{ collect($recentCoupons)->pluck('code')->filter()->take(3)->implode(', ') }}
                    @else
                        No Servio coupons synced yet.
                    @endif
                </div>
            </div>
        </aside>
    </div>
@endsection
