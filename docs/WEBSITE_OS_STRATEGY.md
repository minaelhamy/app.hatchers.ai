# Hatchers OS Website Strategy

## Core idea

Founders should no longer think in terms of:

- "I am now in Bazaar"
- "I am now in Servio"

They should think in terms of:

- "I am building my website from Hatchers OS"

Bazaar and Servio should remain the underlying execution engines for now, but the founder-facing control surface should move to `app.hatchers.ai`.

## What Hatchers OS should own

The OS should own the founder-facing website workflow:

- website path selection
- website setup guidance
- website readiness summary
- publishing status
- domain strategy
- AI and mentor guidance around website progress

## What the engines still own

### Bazaar

- storefront theme rendering
- product catalog operations
- order operations
- ecommerce website settings

### Servio

- service theme rendering
- booking operations
- schedule and availability logic
- service website settings

## Founder-facing publishing model

### Workspace domain

- `app.hatchers.ai`

This is where the founder logs in, sees the dashboard, talks to Atlas, and manages the business.

### Default published site

Use a Hatchers-managed public site for fast launch:

- `brand.hatchers.site`

This keeps the publishing language simple across both product and service businesses.

### Custom domain

When the founder is ready, map the public site to a branded domain:

- `www.brand.com`

## Engine-routing rule

- `product` businesses should route to Bazaar first
- `service` businesses should route to Servio first
- `hybrid` businesses can activate both, but should still manage the experience from one OS workspace

## Current implementation status

Already working:

- Bazaar and Servio sync signed snapshots into Hatchers OS
- Hatchers OS summarizes website readiness from both engines
- Atlas in Hatchers OS can create and update Bazaar and Servio records after confirmation

Still to build:

- OS-native website setup steps
- OS-native publish flow
- domain connection flow from the OS
- OS-native pages for products, services, and website settings

## Practical migration path

### Phase 1

Keep Bazaar and Servio UIs available, but place a Website workspace in Hatchers OS above them.

### Phase 2

Move the most important founder tasks into OS-native flows:

- choose website type
- choose theme
- set website title
- add first product or first service
- set primary CTA
- publish website

### Phase 3

Treat Bazaar and Servio primarily as internal engines rather than founder-facing app boundaries.
