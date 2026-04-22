# app.hatchers.ai

## Purpose

`app.hatchers.ai` is the new unified shell for Hatchers OS.

Its job is to become the single founder workspace across:

- mentoring and execution
- company intelligence
- ecommerce and service website building
- content generation and growth
- admin and mentor operations

In the near term, this app will act as the OS shell while existing systems continue to power major workflows behind the scenes:

- LMS for mentoring
- Atlas for shared intelligence and AI
- Bazaar for ecommerce
- Servio for service and booking businesses

## Phase 1 goals

The first version of this project should establish:

- centralized onboarding
- centralized subscriptions and plan entitlements
- canonical founder and company data models
- unified dashboard and navigation
- module snapshot contracts
- embedded Atlas assistant experience

## Current project structure

- [`docs/ARCHITECTURE.md`](/Users/minaelhamy/Hatchers-All%20Base/app.hatchers.ai/docs/ARCHITECTURE.md)
- [`docs/CANONICAL_DATA_MODEL.md`](/Users/minaelhamy/Hatchers-All%20Base/app.hatchers.ai/docs/CANONICAL_DATA_MODEL.md)
- [`contracts/MODULE_SNAPSHOT_CONTRACTS.md`](/Users/minaelhamy/Hatchers-All%20Base/app.hatchers.ai/contracts/MODULE_SNAPSHOT_CONTRACTS.md)
- [`ui/founder-dashboard.html`](/Users/minaelhamy/Hatchers-All%20Base/app.hatchers.ai/ui/founder-dashboard.html)

## Recommended implementation direction

Build this project as a new Laravel-based shell application.

The shell should own:

- auth
- subscriptions
- onboarding
- unified navigation
- unified dashboard
- feature entitlements
- role routing
- cross-platform module launch and trusted handoff

The shell should not immediately replace the backend engines of LMS, Atlas, Bazaar, and Servio. It should orchestrate them first, then absorb the highest-value workflows over time.
