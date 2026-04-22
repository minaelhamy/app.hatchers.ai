# Next Build Steps

## What is now scaffolded

The initial OS shell scaffolding includes:

- a Laravel-style `composer.json`
- `.env.example`
- initial `routes/web.php`
- `OsShellController`
- shell module config
- blade-style views for landing, plans, onboarding, and dashboard
- canonical migration files for the first OS entities
- matching Eloquent model stubs

## What should be built next

### 1. Real Laravel bootstrap

Add the rest of the framework skeleton or scaffold the project from Composer when ready.

### 2. Auth and role model

Implement:

- founder auth
- mentor auth
- admin auth
- role and entitlement middleware

### 3. Real Laravel bootstrap and migration execution

Add the remaining framework bootstrap and wire the actual migration pipeline so the new canonical schema can be created in a real database.

### 4. Atlas integration service

Build a service layer for:

- founder intelligence reads
- assistant chat calls
- action-plan retrieval
- cross-platform sync handling

### 5. Onboarding implementation

Turn the onboarding view into a real multi-step form that writes to:

- OS canonical models
- Atlas intelligence layer

### 6. Dashboard service layer

Build a unified founder dashboard aggregator using normalized module snapshots.

### 7. Website workspace

Build a unified website workspace in Hatchers OS that:

- recommends Bazaar or Servio based on business model
- summarizes website readiness from both engines
- explains the founder-facing domain model
- becomes the future home for publishing and website setup flows
