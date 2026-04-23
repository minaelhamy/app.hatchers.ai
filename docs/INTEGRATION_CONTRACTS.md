# Hatchers Ai OS Integration Contracts

## Purpose

This document defines the integration contract between `app.hatchers.ai` and the current backend engines:

- LMS
- Atlas
- Bazaar
- Servio

The goal is to make every cross-tool connection consistent, signed, observable, and safe to extend.

## Shared Security Contract

All signed server-to-server traffic should use:

- `WEBSITE_PLATFORM_SHARED_SECRET`

### Required behavior

- the sender signs the raw request body with HMAC SHA-256
- the receiver validates the signature before processing
- invalid signatures return `403`

## Contract Types

### 1. Identity contract

Purpose:

- authenticate users against an engine when needed
- upsert or refresh OS identity state

Current direction:

- LMS is currently the main bridge for legacy auth fallback
- OS remains the main user-facing login surface

Expected contract shape:

- request contains login identifier and password
- response returns:
  - success boolean
  - canonical role
  - normalized user profile
  - engine-specific identifiers if available

### 2. Snapshot contract

Purpose:

- allow each engine to send summary state into the OS

Expected request:

- founder identity or engine-side external identifier
- module name
- updated timestamp
- readiness score
- normalized payload summary

Expected response:

- success boolean
- stored module name
- stored updated timestamp

Rules:

- snapshots must be lightweight summary payloads
- snapshots are for rendering and orchestration, not full data duplication

### 3. Action contract

Purpose:

- allow the OS to trigger a write operation inside an engine

Expected request:

- founder identity or engine-side identifier
- action type
- normalized action payload
- actor role
- idempotency-friendly structure where possible

Expected response:

- success boolean
- human-readable message
- changed resource identifiers when applicable
- optional refresh hints for the OS

### 4. Launch contract

Purpose:

- allow OS to create a trusted session handoff into an engine during the transition period

Expected request:

- signed user context
- target module
- target destination or route

Expected response:

- redirect or launch-ready URL

Rules:

- launch is transitional
- long term, OS-native UI should replace normal dependence on these launches

## Module-Specific Expectations

### LMS

Must support:

- identity bridge
- snapshot sync
- launch handoff
- milestone and task actions
- mentor-related reads and writes needed by OS-native mentor flows

### Atlas

Must support:

- intelligence sync
- assistant chat operations
- campaigns and content actions
- archive, restore, duplicate, and list actions
- launch handoff during transition

### Bazaar

Must support:

- store snapshot sync
- website setup and publish actions
- product create and update actions
- order summaries for dashboard and admin views
- launch handoff during transition

### Servio

Must support:

- service snapshot sync
- website setup and publish actions
- service create and update actions
- booking summaries for dashboard and admin views
- launch handoff during transition

## Response Standards

Every integration response should aim to include:

- `success`
- `message` or `error`
- `updated_at` when data changed
- `resource_id` when a resource was created or changed
- `meta` only when needed for OS rendering or retries

## Logging Standards

For each integration call, log:

- module
- action type
- founder or actor identifier
- success or failure state
- failure reason
- request correlation details if available

## Reliability Standards

- engine failures should not crash OS rendering
- failed actions should return human-readable OS errors
- failed syncs should be retriable
- stale module state should be visible in admin health views

## Immediate Build Contract

For the current build phase, every new OS-native workflow should follow this pattern:

1. render the workflow in `app.hatchers.ai`
2. call the engine through a signed action or API
3. update or refresh the normalized snapshot
4. reflect the result back in the OS UI

This is the standard approach until an engine domain is fully migrated into the OS.
