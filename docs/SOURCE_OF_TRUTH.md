# Hatchers Ai OS Source Of Truth

## Purpose

This document defines which system owns which category of data in Hatchers Ai OS.

The goal is to prevent duplicate ownership, conflicting edits, and unclear integration behavior while `app.hatchers.ai` becomes the central operating system.

## Core Rule

`app.hatchers.ai` is the primary experience layer and orchestration layer.

The backend engines continue to own their specialized operational domains until we deliberately migrate ownership.

## Ownership Map

### Hatchers Ai OS owns

- identity records used for OS access
- roles and permissions at the OS level
- subscriptions and plan entitlements
- company profile summary
- unified founder dashboard state
- unified mentor dashboard state
- unified admin dashboard state
- cross-tool summary cards
- unified navigation and workflow routing
- normalized module snapshots
- cross-tool alerts, health, and activity summaries

### LMS owns

- curriculum and program structure
- learning tasks
- milestones
- mentor execution state
- mentor-founder meeting rhythm
- detailed learning progress records

### Atlas owns

- company intelligence depth
- AI chat history
- campaigns
- content generation
- social media planning
- agent conversations and outputs

### Bazaar owns

- products
- product categories
- store configuration
- ecommerce operational records
- orders and order states
- storefront commerce behavior

### Servio owns

- services
- staff availability and schedules
- bookings and booking state
- service configuration
- service storefront behavior

## Write Rules

### OS-native writes should update OS first when the OS owns the field

Examples:

- founder profile
- subscription plan state
- company profile summary
- role and entitlement data

### OS-native writes should call the engine first when the engine owns the field

Examples:

- LMS milestone completion
- Atlas campaign updates
- Bazaar product edits
- Servio service edits

In these cases the OS should then refresh or update the normalized module snapshot.

## Sync Rules

- engines push normalized snapshots into the OS
- the OS never tries to fully mirror every engine table
- the OS stores summary state for rendering, orchestration, health checks, and assistant context
- when detailed data is needed, the OS requests it through engine APIs or signed actions

## Conflict Resolution Rules

When a field exists in both the OS summary and an engine:

- the source-of-truth owner wins
- the non-owning copy must be treated as a cache or summary only
- if a conflict is detected, log it and refresh the summary from the owning system

## Migration Rules

Ownership should only move from an engine into the OS when all of these are true:

- the OS already provides a complete operational UI for that domain
- the OS can safely validate and persist that data
- engine integrations no longer rely on the engine being the write owner
- monitoring and rollback paths are in place

## Immediate Working Agreement

For the current build phase, we will follow this model:

- OS is the only user-facing shell we are actively expanding
- LMS, Atlas, Bazaar, and Servio remain backend engines
- old frontends are transitional, not the final experience
- new founder, mentor, and admin workflows should be built in OS first whenever possible
