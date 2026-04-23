# Hatchers Ai OS Shared Identifier Strategy

## Purpose

This document defines how Hatchers Ai OS identifies the same person across the OS, LMS, Atlas, Bazaar, and Servio while the platform is still in migration.

## Current Strategy

The OS internal primary key is:

- `founders.id`

For cross-system matching, the working shared identity key is:

- `role:username`

Fallback matching keys are:

- `email`
- `previous_username`
- `previous_email`

## Practical Rule

Every integration should try to send:

1. `founder_id` when the OS user id is already known
2. `username`
3. `email`

This gives the OS the safest possible matching path during migration.

## Why Username Is The Current Shared Key

- it already exists across the current systems
- it is more stable than display names
- it is easy to expose in signed payloads
- it keeps the bridge simple while legacy systems are still being normalized

## Long-Term Direction

The long-term target is:

- OS remains the canonical identity authority
- backend engines store an OS external identifier
- OS-originated identity context becomes the preferred integration key

## Current Build Guidance

- new identity sync payloads should include `username`, `email`, and any previous values when renames happen
- snapshot and action payloads should include `founder_id` whenever possible
- admin identity review in the OS should use the shared identity key to spot stale or ambiguous records
