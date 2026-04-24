# Bazaar + Servio OS Audit

This document tracks the complete Bazaar and Servio feature surface against what is already available inside `app.hatchers.ai`.

Purpose:
- define the real founder/admin feature scope from the existing Bazaar and Servio codebases
- mark what is already exposed in Hatchers Ai Business OS
- make the remaining integration work explicit

Status labels:
- `Done in OS`: usable from `app.hatchers.ai`
- `Partial in OS`: some OS support exists, but the original tool still has deeper capability
- `Not in OS yet`: still only available in Bazaar/Servio or not yet wired

## Product Direction

Decision:
- public storefront/service URLs should resolve under `app.hatchers.ai/{company-slug}`
- Bazaar and Servio should remain backend engines for commerce and service operations
- founders should not need to visit `bazaar.hatchers.ai` or `servio.hatchers.ai` in normal use

Current known commerce gap:
- website publish/path routing is not fully finished for OS-native public URLs yet
- this is a platform blocker and should be treated as high priority

---

## Bazaar: Founder Surface

### Storefront + Website

| Feature | Status | Notes |
|---|---|---|
| Store title / public URL | Partial in OS | OS shows and edits website title/path, but public route publishing is not fully working yet |
| Theme selection | Partial in OS | Theme selection exists in OS, but dropdown behavior and full theme parity need finishing |
| Theme previews | Partial in OS | Basic OS support exists |
| Publish website/storefront | Partial in OS | UI exists in OS, but real public routing is not complete |
| Custom domain management | Not in OS yet | Exists in Bazaar admin |
| Store landing/about/privacy/terms/refund pages | Not in OS yet | Bazaar has native other-pages management |
| Banners/sliders/promotional banners | Not in OS yet | Native Bazaar admin surface exists |
| Pixel/tracking settings | Not in OS yet | Bazaar supports pixel settings |
| PWA settings | Not in OS yet | Bazaar supports PWA |
| Age verification | Not in OS yet | Bazaar addon surface exists |
| SEO/search-related storefront settings | Not in OS yet | Not fully surfaced in OS |

### Product Catalog

| Feature | Status | Notes |
|---|---|---|
| Product create/edit | Partial in OS | Offer manager exists, but full Bazaar product form parity is not complete |
| Product availability active/inactive | Done in OS | Can toggle from OS offer manager |
| SKU management | Done in OS | OS writes SKU updates |
| Stock quantity | Done in OS | OS supports stock movement and exact quantity updates |
| Low-stock threshold | Done in OS | OS supports low-stock controls |
| Categories | Not in OS yet | Bazaar categories exist natively, not fully managed in OS |
| Tax assignment | Not in OS yet | Bazaar tax management exists natively |
| Global extras | Not in OS yet | Bazaar extras/addons not fully surfaced in OS |
| Variants | Not in OS yet | Bazaar has product variant management not yet in OS |
| Digital products | Not in OS yet | Native Bazaar support exists |
| Bulk import | Not in OS yet | Bazaar import flow exists |
| Product media gallery | Partial in OS | OS has media library, but not full Bazaar product-media parity |
| Product Q&A | Not in OS yet | Bazaar addon exists |
| Product inquiries | Not in OS yet | Bazaar native admin exists |
| Product reviews | Not in OS yet | Bazaar supports product reviews |
| Wishlist/favorites | Not in OS yet | Frontend capability exists, not OS-managed |

### Orders + Fulfillment

| Feature | Status | Notes |
|---|---|---|
| Order list | Done in OS | Orders page exists |
| Order detail view | Done in OS | Line items, totals, notes, delivery details surfaced |
| Order filtering | Done in OS | Status, search, queue filters |
| Order status update | Done in OS | OS writes to Bazaar |
| Payment status update | Done in OS | OS writes to Bazaar |
| Vendor/internal note | Done in OS | OS writes note updates |
| Delivery date/time | Done in OS | OS writes fulfillment updates |
| Customer update message | Done in OS | With timeline and channel tracking |
| Email customer follow-up | Done in OS | Wired through Bazaar mail templates |
| WhatsApp/SMS/manual message logging | Partial in OS | Timeline/logging exists, delivery engine is not fully automated from OS |
| Customer details edit | Done in OS | Name/email/mobile/address fields editable from OS |
| Order operational queues | Done in OS | Pending, unpaid, ready-to-ship queues exist |
| Invoice/print/PDF | Not in OS yet | Bazaar has native invoice/print views |
| Order tracking page | Not in OS yet | Bazaar-specific frontend not yet surfaced in OS |
| Custom order statuses | Not in OS yet | Bazaar supports custom statuses |
| POS | Not in OS yet | Bazaar has POS addon/admin surface |
| Refund operations | Not in OS yet | Not surfaced in OS |

### Promotions

| Feature | Status | Notes |
|---|---|---|
| Coupon create | Done in OS | Bazaar coupon create supported |
| Coupon edit | Done in OS | Existing coupons editable |
| Coupon activate/deactivate | Done in OS | Supported in OS |
| Top deals | Not in OS yet | Bazaar native feature |
| Fake sales notifications | Not in OS yet | Bazaar native feature |
| Newsletter/promotional messaging | Not in OS yet | Native/addon surfaces exist |

### Shipping + Delivery

| Feature | Status | Notes |
|---|---|---|
| Shipping plan/zone create | Done in OS | Supported |
| Shipping plan/zone edit | Done in OS | Supported |
| Shipping plan/zone activate/deactivate | Done in OS | Supported |
| Delivery area mapping | Partial in OS | Basic fields exist, but full native Bazaar shipping model parity not confirmed |
| Advanced shipping rules | Not in OS yet | Needs deeper Bazaar parity review |

### Customers + Commerce Ops

| Feature | Status | Notes |
|---|---|---|
| Customer list | Not in OS yet | Bazaar has native customer module |
| Customer order history | Partial in OS | Visible through order records, not a dedicated customer workspace |
| Commerce alerts | Done in OS | Low stock/out-of-stock surfaced on home |
| Reminder automations | Partial in OS | OS reminder rules exist, but execution automation is not fully complete yet |
| Wallet / add money | Not in OS yet | Native Bazaar customer frontend exists |

---

## Bazaar: Admin Surface

### Vendor / Platform Management

| Feature | Status | Notes |
|---|---|---|
| Vendor/user management | Not in OS yet | Bazaar admin has dedicated user management |
| Customer management | Not in OS yet | Native Bazaar admin exists |
| Subscriber management | Partial in OS | OS has subscriber reporting, but not Bazaar-specific parity |
| Plans / subscriptions | Partial in OS | OS manages plans at platform level, not full Bazaar-native plan admin parity |
| Role manager / employee permissions | Partial in OS | OS has system access/permissions, but not full Bazaar role manager parity |

### Platform Commerce Settings

| Feature | Status | Notes |
|---|---|---|
| Payment gateway settings | Not in OS yet | Bazaar has broad gateway support |
| Currency settings | Not in OS yet | Native Bazaar admin exists |
| Tax settings | Not in OS yet | Native Bazaar admin exists |
| App/web settings | Not in OS yet | Bazaar settings are broader than OS website controls today |
| Email settings/templates | Partial in OS | OS mail ops exists, not full Bazaar template/settings parity |
| Media manager | Partial in OS | OS media library exists, not full Bazaar admin parity |
| Theme manager | Partial in OS | OS website/theme controls exist, not full Bazaar theme admin parity |
| Store categories | Not in OS yet | Native Bazaar admin exists |
| Language settings | Not in OS yet | Addon surface exists |
| Blog/content settings | Not in OS yet | Native/addon surface exists |
| Custom domain settings | Not in OS yet | Native Bazaar admin exists |
| Analytics/reporting | Partial in OS | OS analytics exists, but not full Bazaar report parity |

### Platform Operations

| Feature | Status | Notes |
|---|---|---|
| Orders admin/reporting | Partial in OS | Founder-level order ops exist; platform-wide Bazaar admin ops do not |
| Transactions | Not in OS yet | Native Bazaar admin exists |
| Feature flags / addons | Not in OS yet | Bazaar system addon admin exists |
| Country/city tables | Not in OS yet | Native Bazaar admin exists |
| POS admin | Not in OS yet | Native Bazaar exists |

---

## Servio: Founder Surface

### Service Website + Website

| Feature | Status | Notes |
|---|---|---|
| Service site title / public URL | Partial in OS | OS shows/edits path, but public OS publishing is not fully working yet |
| Theme selection | Partial in OS | Some theme support exists, but parity is incomplete |
| Publish service website | Partial in OS | Button exists, but final public route is not wired correctly yet |
| Custom domain management | Not in OS yet | Native Servio admin exists |
| Website pages/settings | Not in OS yet | Servio website settings are deeper than OS currently |
| Gallery | Not in OS yet | Native Servio gallery exists |
| Why choose us / how it works / about / contact pages | Not in OS yet | Native Servio admin exists |
| Embedded widget / booking embed | Not in OS yet | Servio has embedded booking support |
| PWA / pixel / recaptcha | Not in OS yet | Native Servio settings exist |

### Services + Scheduling

| Feature | Status | Notes |
|---|---|---|
| Service create/edit | Partial in OS | Offer manager exists, but not full native Servio service form parity |
| Service availability active/inactive | Done in OS | Supported in OS |
| Duration | Done in OS | Supported in OS |
| Duration unit | Done in OS | Supported in OS |
| Capacity / bookings per slot | Done in OS | Supported in OS |
| Staff assignment mode | Done in OS | Supported in OS |
| Specific staff id | Done in OS | Supported in OS |
| Active weekdays | Done in OS | Supported in OS |
| Open/close time | Done in OS | Supported in OS |
| Categories | Not in OS yet | Native Servio categories exist |
| Staff list / assignment admin | Partial in OS | Can assign staff id in booking/service ops, but no full staff workspace yet |
| Additional services | Not in OS yet | Native Servio supports additional services |
| Taxes | Not in OS yet | Native Servio tax management exists |
| Bulk import | Not in OS yet | Native import exists |
| Service media/gallery | Partial in OS | OS media exists, not full Servio parity |
| Service Q&A | Not in OS yet | Native/addon exists |
| Service reviews | Not in OS yet | Native storefront capability exists |
| Google Calendar / calendar integration | Not in OS yet | Native Servio calendar settings exist |
| Zoom/meeting integration | Not in OS yet | Native Servio integration exists |
| iCal / vendor calendar | Not in OS yet | Native Servio routes exist |

### Bookings

| Feature | Status | Notes |
|---|---|---|
| Booking list | Done in OS | Bookings page exists |
| Booking detail view | Done in OS | Times, notes, pricing, extras, join link surfaced |
| Booking filtering | Done in OS | Status, search, queue filters |
| Booking status update | Done in OS | OS writes to Servio |
| Payment status update | Done in OS | OS writes to Servio |
| Vendor/internal note | Done in OS | Supported |
| Reschedule booking | Done in OS | Date/start/end edits from OS |
| Staff assignment | Done in OS | Staff id updates supported |
| Customer update message | Done in OS | Supported with communication timeline |
| Email customer follow-up | Done in OS | Wired through Servio mail templates |
| WhatsApp/SMS/manual message logging | Partial in OS | Timeline/logging exists; channel delivery is not fully automated from OS |
| Customer details edit | Done in OS | Name/email/mobile/address/city/state/country supported |
| Booking operational queues | Done in OS | Unscheduled / needs staff / pending queues exist |
| Booking invoice/PDF | Not in OS yet | Native Servio admin exists |
| Booking tracking page | Not in OS yet | Native Servio frontend exists |
| Provider assignment reminders | Partial in OS | OS reminder rules exist, not full automation execution yet |

### Product Shop Inside Servio

| Feature | Status | Notes |
|---|---|---|
| Product create/edit | Partial in OS | OS offer manager supports product-mode fields, but full Servio product parity not complete |
| Product category management | Not in OS yet | Native Servio exists |
| Product shipping | Partial in OS | OS shipping controls exist, deeper Servio product-shop parity still needed |
| Product orders | Partial in OS | Founder order ops exist, but dedicated Servio product-shop parity still needs review |

### Promotions

| Feature | Status | Notes |
|---|---|---|
| Coupon/promocode create | Done in OS | Supported |
| Coupon/promocode edit | Done in OS | Supported |
| Coupon/promocode activate/deactivate | Done in OS | Supported |
| Banners | Not in OS yet | Native Servio admin exists |
| Top deals / fake sales notifications | Not in OS yet | Native Servio feature/addon surfaces exist |

### Customers + Commerce Ops

| Feature | Status | Notes |
|---|---|---|
| Customer list | Not in OS yet | Native Servio admin exists |
| Customer booking history | Partial in OS | Visible through booking records, not a dedicated customer workspace |
| Commerce alerts | Done in OS | No-availability / inactive service alerts surface in OS |
| Reminder automations | Partial in OS | Templates/rules exist, but deeper execution still pending |

---

## Servio: Admin Surface

### Vendor / Platform Management

| Feature | Status | Notes |
|---|---|---|
| Vendor management | Not in OS yet | Native Servio admin exists |
| Customer management | Not in OS yet | Native Servio admin exists |
| Subscriber management | Partial in OS | Platform-level subscriber reporting exists in OS |
| Plans / subscriptions | Partial in OS | OS handles platform plans, not full Servio admin parity |
| Role manager / employee permissions | Partial in OS | OS has access control but not full Servio role-manager parity |
| Bank details / payout admin | Not in OS yet | Servio native payout/bank surfaces exist |

### Platform Service Settings

| Feature | Status | Notes |
|---|---|---|
| Payment gateway settings | Not in OS yet | Native Servio exists |
| Currency settings | Not in OS yet | Native Servio exists |
| Tax settings | Not in OS yet | Native Servio exists |
| Website/basic settings | Not in OS yet | Native Servio website settings are broader than OS today |
| Email settings/templates | Partial in OS | OS mail ops exists, not full Servio admin parity |
| Theme manager | Partial in OS | Basic OS theme controls exist |
| Media manager | Partial in OS | OS media library exists, not full admin parity |
| Analytics settings | Not in OS yet | Native Servio analytics setting surface exists |
| Working hours | Partial in OS | Founder service availability exists, but not full admin working-hours tooling |
| Calendar / Google calendar | Not in OS yet | Native Servio integration exists |
| Embedded/widget settings | Not in OS yet | Native Servio embedded/widget exists |
| Custom domain settings | Not in OS yet | Native Servio admin exists |
| Blog/language/testimonials | Not in OS yet | Native/addon surfaces exist |
| Gallery / why choose us / how it works | Not in OS yet | Native Servio admin exists |

### Platform Operations

| Feature | Status | Notes |
|---|---|---|
| Bookings admin/reporting | Partial in OS | Founder-level booking ops exist; platform-wide admin parity does not |
| Orders admin/reporting | Partial in OS | Founder-level order ops exist; platform-wide admin parity does not |
| Reports | Partial in OS | OS analytics/reporting exists, not full Servio report parity |
| Transactions | Not in OS yet | Native Servio admin exists |
| Payouts | Not in OS yet | Native Servio admin exists |
| POS | Not in OS yet | Native Servio POS/product-shop admin exists |
| Country/city tables | Not in OS yet | Native Servio admin exists |
| Feature flags / addons | Not in OS yet | Native system addon surfaces exist |

---

## Commerce Work Already Built In OS

The following commerce capabilities are already present in `app.hatchers.ai`:

### Shared Founder Commerce
- commerce dashboard
- service/product offer manager
- orders page
- bookings page
- order and booking detail panels
- order and booking filters
- communication timeline
- customer update message templates
- reminder rules and reminder templates
- next best actions and operational queue on Home

### Bazaar Founder Operations Already In OS
- coupon create/edit/activate/deactivate
- shipping plan create/edit/activate/deactivate
- order status update
- payment status update
- vendor note update
- customer detail updates
- delivery date/time updates
- order notes
- stock movement
- SKU/stock/low-stock controls
- order operational queues

### Servio Founder Operations Already In OS
- coupon create/edit/activate/deactivate
- booking status update
- payment status update
- vendor note update
- customer detail updates
- reschedule booking
- staff assignment
- service duration/capacity/staff mode controls
- service availability days and time windows
- booking operational queues

---

## Highest-Priority Remaining Commerce Work

These are the most important remaining items if the goal is that founders only use `app.hatchers.ai`:

### P0: Public Website Routing + Publishing
- OS-native public route for product/service sites under `app.hatchers.ai/{company-slug}`
- working publish flow for Bazaar-backed and Servio-backed sites
- correct engine-aware site generation so service founders do not get Bazaar paths and product founders do not get Servio paths
- correct slug/path collision handling

### P0: Founder-Visible Website Parity
- complete theme selection reliability
- homepage/section editing parity
- policy/about/contact pages
- banner/hero management
- custom domain connection inside OS

### P1: Catalog + Service Form Parity
- Bazaar categories, variants, taxes, extras
- Servio categories, staff workspace, additional services, tax assignment
- full product/service media management

### P1: Customer + Invoice Parity
- dedicated customer workspace
- invoice/PDF/print flows in OS
- tracking pages and customer self-service links exposed through OS

### P1: Automation Execution
- reminder rules should not only exist as saved templates
- they need actual timed execution / trigger workers / send logs inside OS

### P2: Admin Parity
- platform-wide Bazaar admin operations in OS
- platform-wide Servio admin operations in OS
- payouts, gateway settings, currency/tax/language/blog/custom-domain parity

---

## Immediate Build Order For Commerce Completion

1. Fix OS-native website publish and public routing under `app.hatchers.ai/{company-slug}`
2. Enforce engine selection by company type:
   - service founder -> Servio only
   - product founder -> Bazaar only
   - hybrid founder -> both
3. Finish website/theme/page editing parity
4. Finish Bazaar catalog parity:
   - categories
   - variants
   - tax
   - extras
5. Finish Servio service parity:
   - staff workspace
   - additional services
   - category/tax parity
   - calendar integrations
6. Add invoice/tracking/customer workspaces
7. Add real automation execution and delivery logs
8. Add admin parity surfaces for Bazaar and Servio

