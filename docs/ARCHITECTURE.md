# Hatchers OS Architecture

## Core principle

Hatchers OS should be built as a unified shell, not as an immediate full merge of all legacy codebases.

That means:

- one user-facing OS at `app.hatchers.ai`
- one shared identity model
- one shared subscription model
- one shared intelligence model
- multiple specialized engines behind the shell

## Target architecture

### OS shell responsibilities

- authentication
- plan selection
- billing and entitlement enforcement
- onboarding
- founder workspace
- mentor workspace
- admin workspace
- unified navigation
- unified dashboard
- website workspace
- module orchestration

### Underlying engines

- `LMS`
  - mentor assignment
  - tasks
  - milestones
  - meetings
  - mentor-founder execution tracking
- `Atlas`
  - company intelligence
  - AI assistant
  - content generation
  - action planning
  - cross-platform memory
- `Bazaar`
  - ecommerce storefronts
  - products
  - categories
  - orders
  - store settings
- `Servio`
  - service storefronts
  - services
  - staff and schedules
  - bookings
  - service settings

## Experience model

The founder should feel they are inside one operating system.

The system should be organized around founder jobs, not old product boundaries:

- home
- weekly plan
- AI assistant
- website
- products or services
- orders or bookings
- marketing
- mentor
- analytics
- settings

## Data flow model

1. Founder authenticates in Hatchers OS.
2. Hatchers OS loads the founder profile, subscription, and entitlements.
3. Hatchers OS loads the central founder intelligence summary.
4. Hatchers OS loads cached module snapshots from LMS, Atlas, Bazaar, and Servio.
5. Dashboard and navigation render from the unified founder state object.
6. Atlas assistant answers using the unified founder state object and module summaries.

## Website and domain model

- `app.hatchers.ai` is the founder operating system and should remain the primary logged-in workspace.
- Bazaar and Servio remain the website engines behind the OS.
- Public founder websites should publish on a unified Hatchers domain model first, then optionally move to custom domains.
- The founder should choose a website path from the OS, while the OS routes the work into Bazaar or Servio behind the scenes.

## Performance model

Do not fetch every module live on every page view.

Use:

- cached module snapshots
- event-driven sync
- scheduled refreshes where needed
- central dashboard summaries

## Security model

Cross-platform requests should continue using signed server-to-server communication with:

- `WEBSITE_PLATFORM_SHARED_SECRET`

Longer term, the system should move toward OS-issued trusted tokens or SSO-style handoff.
