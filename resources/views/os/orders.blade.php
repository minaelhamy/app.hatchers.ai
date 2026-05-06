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
        .ops-action { display:inline-flex; align-items:center; gap:10px; padding:10px 14px; border-radius:12px; text-decoration:none; color:var(--ink); font-size:0.95rem; background:#f0ece4; border:0; cursor:pointer; font:inherit; }
        .ops-action.primary { background:linear-gradient(90deg,#8e1c74,#ff2c35); color:#fff; }
        @media (max-width:1240px) { .ops-shell { grid-template-columns:220px 1fr; } .ops-rightbar { display:none; } }
        @media (max-width:900px) { .ops-shell { grid-template-columns:1fr; } .ops-sidebar { min-height:auto; border-right:0; border-bottom:1px solid var(--line); } .ops-sidebar-footer { display:none; } .ops-main { padding:20px 16px 24px; } .ops-grid, .ops-metrics { grid-template-columns:1fr; } }
    </style>
@endsection

@section('content')
    @php
        $founder = $dashboard['founder'];
        $ops = $orderWorkspace;
        $recentOrders = $ops['recent_orders'] ?? [];
        $recentCoupons = $ops['recent_coupons'] ?? [];
        $shippingZones = $ops['shipping_zones'] ?? [];
        $filters = $orderFilters ?? ['status' => 'all', 'queue' => 'all', 'q' => ''];
        $queueLabels = ['all' => 'All order work', 'pending' => 'Pending action', 'unpaid' => 'Awaiting payment', 'ready_to_ship' => 'Ready to ship'];
        $activeQueueLabel = $queueLabels[$filters['queue'] ?? 'all'] ?? 'All order work';
        $pendingOrders = collect($recentOrders)->whereIn('status', ['pending', 'processing'])->count();
        $awaitingPaymentOrders = collect($recentOrders)->where('payment_status', 'unpaid')->count();
        $readyToShipOrders = collect($recentOrders)->filter(fn ($order) => ($order['status'] ?? '') === 'processing' && ($order['payment_status'] ?? '') === 'paid')->count();
        $deliveryScheduledOrders = collect($recentOrders)->filter(fn ($order) => trim((string) ($order['delivery_date'] ?? '')) !== '' || trim((string) ($order['delivery_time'] ?? '')) !== '')->count();
    @endphp
    <div class="ops-shell">
        <aside class="ops-sidebar">
            @include('os.partials.founder-sidebar', [
                'founder' => $founder,
                'businessModel' => $founder->company->business_model ?? 'hybrid',
                'activeKey' => 'orders',
                'navClass' => 'ops-nav',
                'itemClass' => 'ops-nav-item',
                'iconClass' => 'ops-nav-icon',
                'innerClass' => 'ops-sidebar-inner',
                'brandClass' => 'ops-brand',
                'footerClass' => 'ops-sidebar-footer',
                'userClass' => 'ops-user',
                'avatarClass' => 'ops-avatar',
            ])
        </aside>

        <main class="ops-main">
            <div class="ops-main-inner">
                @include('os.partials.guidebook-workspace-topbar', [
                    'founder' => $founder,
                    'company' => $founder->company,
                    'workspace' => $dashboard['workspace'] ?? [],
                    'projectName' => $founder->company->company_name ?? 'Founder workspace',
                    'sectionLabel' => 'Orders',
                    'searchPlaceholder' => 'Monitor orders, fulfillment, customers, and store operations from one workspace...',
                ])
                <h1>Orders</h1>
                <p>Track Bazaar-driven order operations from Hatchers Ai Business OS while the storefront engine keeps doing the backend work.</p>

                <section class="ops-metrics">
                    <div class="ops-metric"><div class="muted">Orders</div><strong>{{ $ops['counts']['orders'] }}</strong></div>
                    <div class="ops-metric"><div class="muted">Products</div><strong>{{ $ops['counts']['products'] }}</strong></div>
                    <div class="ops-metric"><div class="muted">Customers</div><strong>{{ $ops['counts']['customers'] }}</strong></div>
                    <div class="ops-metric"><div class="muted">Revenue</div><strong>{{ $ops['currency'] }} {{ number_format($ops['gross_revenue'], 0) }}</strong></div>
                </section>

                <section class="ops-card" style="margin-bottom:12px;">
                    <h2 style="margin-bottom:12px;">Start With The Queue</h2>
                    <div class="ops-grid">
                        <div class="rail-item">
                            <strong>Pending action</strong><br>
                            <span class="muted">{{ $pendingOrders }} order{{ $pendingOrders === 1 ? '' : 's' }} still need founder action.</span>
                            <div style="margin-top:10px;"><a class="ops-action primary" href="{{ route('founder.commerce.orders', ['status' => 'all', 'queue' => 'pending']) }}">Open pending queue</a></div>
                        </div>
                        <div class="rail-item">
                            <strong>Awaiting payment</strong><br>
                            <span class="muted">{{ $awaitingPaymentOrders }} order{{ $awaitingPaymentOrders === 1 ? '' : 's' }} are still unpaid.</span>
                            <div style="margin-top:10px;"><a class="ops-action" href="{{ route('founder.commerce.orders', ['status' => 'all', 'queue' => 'unpaid']) }}">Open unpaid queue</a></div>
                        </div>
                    </div>
                </section>

                <section class="ops-grid">
                    <div class="ops-card">
                        <h2 style="margin-bottom:12px;">Store Overview</h2>
                        <div class="ops-stack">
                            <div><strong>{{ $ops['website_title'] }}</strong></div>
                            <div class="muted">Readiness {{ $ops['readiness_score'] }}% · Last synced {{ $ops['updated_at'] ?: 'Not synced yet' }}</div>
                            <div class="muted">This page is the OS-native operations view for Bazaar order data.</div>
                            <div style="margin-top:8px;">
                                <a class="pill" href="{{ route('founder.commerce') }}">Back to Commerce</a>
                            </div>
                        </div>
                    </div>

                    <div class="ops-card">
                        <h2 style="margin-bottom:12px;">Order Activity</h2>
                        <div class="ops-stack">
                            @forelse ($ops['activity'] as $item)
                                <div class="rail-item">{{ $item }}</div>
                            @empty
                                <div class="rail-item">No Bazaar order activity has synced yet.</div>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section class="ops-metrics" style="margin-top:12px;">
                    <div class="ops-metric"><div class="muted">Pending action</div><strong>{{ $pendingOrders }}</strong></div>
                    <div class="ops-metric"><div class="muted">Awaiting payment</div><strong>{{ $awaitingPaymentOrders }}</strong></div>
                    <div class="ops-metric"><div class="muted">Ready to ship</div><strong>{{ $readyToShipOrders }}</strong></div>
                    <div class="ops-metric"><div class="muted">Delivery scheduled</div><strong>{{ $deliveryScheduledOrders }}</strong></div>
                </section>

                <section class="ops-card" style="margin-top:12px;">
                    <h2 style="margin-bottom:12px;">Filter Orders</h2>
                    <form method="GET" action="{{ route('founder.commerce.orders') }}" style="display:grid;gap:10px;grid-template-columns:repeat(2,minmax(0,1fr));">
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
                            <input name="q" type="text" value="{{ $filters['q'] ?? '' }}" placeholder="Order number, customer, email" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;">
                        </label>
                        <div style="grid-column:1 / -1;display:flex;gap:10px;flex-wrap:wrap;">
                            <button class="pill" type="submit" style="border:0;cursor:pointer;">Apply filters</button>
                            <a class="ops-action" href="{{ route('founder.commerce.orders') }}" style="text-decoration:none;">Clear</a>
                        </div>
                    </form>
                    <div class="muted" style="margin-top:12px;">Showing queue: {{ $activeQueueLabel }}</div>
                </section>

                <section class="ops-card" style="margin-top:12px;">
                    <h2 style="margin-bottom:12px;">Order Operations</h2>
                    <div class="ops-stack">
                        @forelse ($recentOrders as $order)
                            <div class="rail-item">
                                <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
                                    <div>
                                        <strong>#{{ $order['order_number'] }}</strong><br>
                                        <span class="muted">{{ $order['customer_name'] }} · {{ $ops['currency'] }} {{ number_format((float) ($order['grand_total'] ?? 0), 2) }}</span><br>
                                        <span class="muted">Status {{ ucfirst($order['status'] ?? 'pending') }} · Payment {{ ucfirst($order['payment_status'] ?? 'unpaid') }}</span><br>
                                        <span class="muted">{{ $order['customer_email'] ?? '' }}{{ !empty($order['customer_mobile']) ? ' · ' . $order['customer_mobile'] : '' }}</span>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('founder.commerce.orders.update') }}" style="margin-top:14px;display:grid;gap:10px;">
                                    @csrf
                                    <input type="hidden" name="order_number" value="{{ $order['order_number'] }}">
                                    <label>
                                        <span class="muted">Order status</span>
                                        <select name="status" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;">
                                            @foreach (['pending', 'processing', 'completed', 'cancelled'] as $status)
                                                <option value="{{ $status }}" @selected(($order['status'] ?? 'pending') === $status)>{{ ucfirst($status) }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label>
                                        <span class="muted">Payment status</span>
                                        <select name="payment_status" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;">
                                            @foreach (['unpaid', 'paid'] as $paymentStatus)
                                                <option value="{{ $paymentStatus }}" @selected(($order['payment_status'] ?? 'unpaid') === $paymentStatus)>{{ ucfirst($paymentStatus) }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label>
                                        <span class="muted">Vendor note</span>
                                        <textarea name="vendor_note" rows="3" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;">{{ $order['vendor_note'] ?? '' }}</textarea>
                                    </label>
                                    <div><button class="pill" type="submit" style="border:0;cursor:pointer;">Save order in OS</button></div>
                                </form>
                                @if (($order['payment_status'] ?? 'unpaid') === 'paid')
                                    <form method="POST" action="{{ route('founder.commerce.orders.refund') }}" style="margin-top:12px;display:grid;gap:10px;">
                                        @csrf
                                        <input type="hidden" name="order_number" value="{{ $order['order_number'] }}">
                                        <label>
                                            <span class="muted">Refund reason</span>
                                            <input name="reason" type="text" placeholder="Optional refund note" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;">
                                        </label>
                                        <div><button class="ops-action" type="submit">Refund order and reverse wallet</button></div>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('founder.commerce.orders.customer') }}" style="margin-top:14px;display:grid;gap:10px;">
                                    @csrf
                                    <input type="hidden" name="order_number" value="{{ $order['order_number'] }}">
                                    <label><span class="muted">Customer name</span><input name="customer_name" type="text" value="{{ $order['customer_name'] ?? '' }}" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                                    <label><span class="muted">Customer email</span><input name="customer_email" type="text" value="{{ $order['customer_email'] ?? '' }}" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                                    <label><span class="muted">Customer mobile</span><input name="customer_mobile" type="text" value="{{ $order['customer_mobile'] ?? '' }}" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                                    <label><span class="muted">Address</span><input name="address" type="text" value="{{ $order['address'] ?? '' }}" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                                    <label><span class="muted">Building</span><input name="building" type="text" value="{{ $order['building'] ?? '' }}" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                                    <label><span class="muted">Landmark</span><input name="landmark" type="text" value="{{ $order['landmark'] ?? '' }}" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                                    <label><span class="muted">Postal code</span><input name="postal_code" type="text" value="{{ $order['postal_code'] ?? '' }}" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                                    <label><span class="muted">Delivery area</span><input name="delivery_area" type="text" value="{{ $order['delivery_area'] ?? '' }}" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                                    <div><button class="ops-action primary" type="submit">Save customer details</button></div>
                                </form>
                                <details style="margin-top:14px;">
                                    <summary style="cursor:pointer;font-weight:600;">Order details and line items</summary>
                                    <div style="margin-top:12px;display:grid;gap:10px;">
                                        <div class="muted">Subtotal {{ $ops['currency'] }} {{ number_format((float) ($order['sub_total'] ?? 0), 2) }} · Delivery {{ $ops['currency'] }} {{ number_format((float) ($order['delivery_charge'] ?? 0), 2) }} · Discount {{ $ops['currency'] }} {{ number_format((float) ($order['discount_amount'] ?? 0), 2) }}</div>
                                        <div class="muted">Delivery {{ $order['delivery_date'] ?? 'Not set' }} {{ !empty($order['delivery_time']) ? '· ' . $order['delivery_time'] : '' }} · Type {{ ucfirst((string) ($order['order_type'] ?? 'standard')) }}</div>
                                        @if (!empty($order['order_notes']))
                                            <div class="rail-item">{{ $order['order_notes'] }}</div>
                                        @endif
                                        <div style="display:grid;gap:8px;">
                                            @forelse (($order['line_items'] ?? []) as $lineItem)
                                                <div class="rail-item">
                                                    <strong>{{ $lineItem['item_name'] }}</strong>{{ !empty($lineItem['variant_name']) ? ' · ' . $lineItem['variant_name'] : '' }}<br>
                                                    <span class="muted">Qty {{ $lineItem['qty'] }} · {{ $ops['currency'] }} {{ number_format((float) ($lineItem['price'] ?? 0), 2) }}</span>
                                                </div>
                                            @empty
                                                <div class="rail-item">No line items have synced for this order yet.</div>
                                            @endforelse
                                        </div>
                                        <form method="POST" action="{{ route('founder.commerce.orders.fulfillment') }}" style="margin-top:6px;display:grid;gap:10px;">
                                            @csrf
                                            <input type="hidden" name="order_number" value="{{ $order['order_number'] }}">
                                            <label><span class="muted">Delivery date</span><input name="delivery_date" type="date" value="{{ $order['delivery_date'] ?? '' }}" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                                            <label><span class="muted">Delivery time</span><input name="delivery_time" type="text" value="{{ $order['delivery_time'] ?? '' }}" placeholder="2:00 PM - 4:00 PM" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></label>
                                            <label><span class="muted">Order notes</span><textarea name="order_notes" rows="3" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;">{{ $order['order_notes'] ?? '' }}</textarea></label>
                                            <label><span class="muted">Message channel</span><select name="message_channel" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"><option value="manual">Manual note</option><option value="email">Email</option><option value="whatsapp">WhatsApp</option><option value="sms">SMS</option></select></label>
                                            <label>
                                                <span class="muted">Message template</span>
                                                <select name="message_template" style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;">
                                                    <option value="">Write custom message</option>
                                                    <option value="packed">Packed and preparing dispatch</option>
                                                    <option value="out_for_delivery">Out for delivery</option>
                                                    <option value="delivered">Delivered</option>
                                                    <option value="delayed">Delayed update</option>
                                                </select>
                                            </label>
                                            <label><span class="muted">Customer update message</span><textarea name="customer_message" rows="3" placeholder="Your order is packed and scheduled for delivery tomorrow." style="width:100%;margin-top:6px;border:1px solid rgba(220,207,191,0.9);background:#fff;border-radius:12px;padding:10px 12px;"></textarea></label>
                                            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                                <button class="ops-nav-item" type="submit" name="message_template" value="packed" style="border:0;cursor:pointer;display:inline-flex;">Mark packed</button>
                                                <button class="ops-nav-item" type="submit" name="message_template" value="out_for_delivery" style="border:0;cursor:pointer;display:inline-flex;">Out for delivery</button>
                                                <button class="ops-nav-item" type="submit" name="message_template" value="delivered" style="border:0;cursor:pointer;display:inline-flex;">Mark delivered</button>
                                            </div>
                                            <div><button class="pill" type="submit" style="border:0;cursor:pointer;">Save fulfillment details</button></div>
                                        </form>
                                        @if (!empty($order['communication_timeline']))
                                            <div style="display:grid;gap:8px;">
                                                <strong>Communication timeline</strong>
                                                @foreach ($order['communication_timeline'] as $event)
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
                            <div class="rail-item">No Bazaar orders have synced into the OS yet.</div>
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
                    <strong>Coupons</strong><br>
                    @if (!empty($recentCoupons))
                        {{ collect($recentCoupons)->pluck('code')->filter()->take(3)->implode(', ') }}
                    @else
                        No Bazaar coupons synced yet.
                    @endif
                </div>
                <div class="mini-note" style="margin-top:12px;">
                    <strong>Shipping zones</strong><br>
                    @if (!empty($shippingZones))
                        {{ collect($shippingZones)->pluck('title')->filter()->take(3)->implode(', ') }}
                    @else
                        No Bazaar shipping zones synced yet.
                    @endif
                </div>
            </div>
        </aside>
    </div>
@endsection
