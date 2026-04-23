# Hatchers Ai OS Implementation Task List

## Purpose

This document is the execution checklist for turning `app.hatchers.ai` into the one central operating system for founders, mentors, and admins.

The target state is:

- founders use only `app.hatchers.ai`
- mentors use only `app.hatchers.ai`
- admins operate primarily from `app.hatchers.ai`
- LMS, Atlas, Bazaar, and Servio remain backend engines behind the OS until their frontends are no longer needed

This is not a rebuild-from-scratch plan.
This is a phased migration plan:

- keep the current tools as backend engines
- build the unified frontend and orchestration layer in `app.hatchers.ai`
- gradually absorb the daily workflows into the OS

## Core Build Principles

- Build new user-facing workflows in `app.hatchers.ai`
- Keep existing engines as backend systems unless there is a strong reason to move logic
- Do not duplicate ownership of the same data in multiple places without defining a source of truth
- Replace old frontends gradually by workflow, not by trying to clone everything at once
- Prefer API calls, signed actions, sync jobs, and normalized summaries over screen-to-screen handoff

## Current State

### Already Working

- [x] `app.hatchers.ai` is deployed
- [x] founder login and signup flow exists
- [x] founder dashboard shell exists
- [x] mentor dashboard shell exists
- [x] admin dashboard shell exists
- [x] LMS identity bridge exists
- [x] signed launch and handoff flows exist across tools
- [x] module snapshots are already syncing into the OS
- [x] Bazaar and Servio website workspace scaffolding exists
- [x] some Atlas, Bazaar, and Servio actions already execute from the OS

### Not Yet Complete

- [x] founders can fully work from OS without using old tools
- [x] mentors can fully work from OS without using LMS frontend
- [x] admins can fully operate subscribers, modules, and support from OS
- [x] old frontends are no longer part of normal user journeys
- [x] cross-tool source-of-truth rules are enforced in code and operations

## Source Of Truth Rules

These rules should stay stable unless we deliberately migrate ownership.

### OS owns

- [x] identity
- [x] roles
- [x] subscriptions
- [x] entitlements
- [x] company profile
- [x] unified founder summary
- [x] unified navigation
- [x] unified notifications

### LMS owns

- [x] program structure
- [x] milestones
- [x] learning tasks
- [x] mentor program state
- [x] meeting and execution rhythm records

### Atlas owns

- [x] company intelligence
- [x] AI chats
- [x] campaigns
- [x] content generation
- [x] social planning
- [x] agent outputs

### Bazaar owns

- [x] products
- [x] categories
- [x] store settings
- [x] orders
- [x] ecommerce storefront state

### Servio owns

- [x] services
- [x] staff and schedules
- [x] bookings
- [x] service settings
- [x] service storefront state

## Phase 1: Foundation And Stabilization

Goal: make the OS stable enough to become the main surface.

### Identity and Access

- [x] define the canonical OS user model for founder, mentor, and admin
- [x] confirm shared identifier strategy across OS, LMS, Atlas, Bazaar, and Servio
- [x] complete identity sync for existing founders
- [x] complete identity sync for existing mentors
- [x] complete identity sync for existing admins
- [x] enforce role-based redirects and permissions in OS
- [x] document the login authority and fallback rules
- [x] replace any remaining legacy login assumptions with OS-first identity rules

### Integration Reliability

- [x] standardize signed request validation across all engines
- [x] standardize response format for engine actions and sync endpoints
- [x] add consistent timeout and retry handling for engine calls
- [x] add better failure logging for sync, auth, and write actions
- [x] create a module health status view in OS
- [x] create a failed-sync queue or manual retry action in OS

### Documentation

- [x] document source-of-truth ownership in one canonical file
- [x] document the integration contract for each engine
- [x] document environment variables and shared secrets for every app

### Exit Criteria

- [x] every founder, mentor, and admin can authenticate through OS
- [x] every module can send snapshots to OS reliably
- [x] every module can accept signed OS actions reliably
- [x] support team can diagnose integration failures from OS logs and health views

## Phase 2: Unified Founder Home

Goal: make OS the default founder starting point every day.

### Dashboard

- [x] finalize founder dashboard layout and card hierarchy
- [x] show weekly focus and weekly priorities from LMS and OS
- [x] show milestone progress from LMS
- [x] show campaign and content activity from Atlas
- [x] show website and business status from Bazaar or Servio
- [x] show business health summary cards
- [x] show founder alerts and blockers
- [x] show personalized next best actions

### Activity and Context

- [x] create a unified founder activity feed
- [x] create a unified notifications center
- [x] show latest module sync timestamps on the dashboard
- [x] expose founder-facing sync issues in a human-readable way

### Exit Criteria

- [x] founder can understand their business state from one dashboard
- [x] founder no longer needs to open other tools just to see what is going on

## Phase 3: OS-Native Founder Workflows

Goal: let founders actually work inside OS instead of using old frontends.

### Founder Task Center

- [x] build OS-native weekly task list
- [x] allow marking tasks complete from OS
- [x] allow milestone updates from OS
- [x] show mentor-linked tasks when applicable
- [x] sync completion state back to LMS

### Founder Campaign and Content Center

- [x] build OS-native campaign list and detail views
- [x] allow campaign creation from OS
- [x] allow archive and restore from OS
- [x] allow duplicate campaign from OS
- [x] show active and archived campaigns in OS
- [x] build OS-native content generation flow
- [x] build OS-native social media generation flow
- [x] show Atlas chat, agent, and content history in OS

### Founder Website and Commerce Center

- [x] finish OS-native website builder flow
- [x] show themes and preview cards in OS
- [x] allow title, path, domain, and publish actions in OS
- [x] build OS-native products manager backed by Bazaar
- [x] build OS-native services manager backed by Servio
- [x] build OS-native orders view backed by Bazaar
- [x] build OS-native bookings view backed by Servio
- [x] expose storefront performance in OS

### Founder Settings

- [x] build founder profile settings in OS
- [x] build company settings in OS
- [x] build subscription and plan visibility in OS
- [x] build billing state visibility in OS

### Exit Criteria

- [x] founders can complete their main weekly work from OS
- [x] founders do not need to visit Atlas, Bazaar, or Servio for daily workflows

## Phase 4: Mentor Workspace

Goal: make OS the main mentor workspace.

### Mentor Dashboard

- [x] finalize mentor dashboard structure to match LMS needs
- [x] show assigned founders with business status and risk signals
- [x] show upcoming meetings and founder priorities
- [x] show open tasks and blocked milestones
- [x] show founder commercial and Atlas context beside LMS progress

### Mentor Workflow Tools

- [x] build founder portfolio list in OS
- [x] build founder detail review screen in OS
- [x] show meeting prep notes and execution summaries
- [x] allow mentor notes and guidance actions from OS
- [x] allow mentor-side task updates from OS
- [x] sync mentor actions back to LMS

### Exit Criteria

- [x] mentors can manage their weekly founder portfolio from OS
- [x] LMS becomes a backend engine for mentors instead of the daily frontend

## Phase 5: Admin Control Center

Goal: centralize operational control in OS.

### Subscriber and User Operations

- [x] build full subscriber list and filtering
- [x] build founder profile management in OS
- [x] build mentor profile management in OS
- [x] build admin profile and permission controls in OS
- [x] build founder lifecycle status controls
- [x] build mentor assignment and rebalance tools
- [x] build plan and billing state controls

### System Operations

- [x] build module sync monitoring page
- [x] build manual sync actions per founder and per module
- [x] build audit log for admin actions
- [x] build exception queue for failed module operations
- [x] build subscriber growth and health reporting

### Exit Criteria

- [x] admins can operate the business from OS
- [x] old tools are only needed for rare fallback or legacy operations

## Phase 6: Replace Daily Frontends

Goal: stop sending normal users to old tools.

### Founder Frontend Replacement

- [x] identify top 10 founder workflows across all tools
- [x] rebuild each founder workflow in OS
- [x] remove dashboard links that send founders to old tools for daily work
- [x] keep engine launches only for temporary fallback

### Mentor Frontend Replacement

- [x] identify top mentor workflows in LMS
- [x] rebuild each mentor workflow in OS
- [x] remove mentor dependence on LMS frontend

### Admin Frontend Replacement

- [x] identify top admin workflows across LMS, Atlas, Bazaar, and Servio
- [x] rebuild each admin workflow in OS
- [x] reserve legacy tool access for edge cases only

### Exit Criteria

- [x] founders do not need old frontends
- [x] mentors do not need old frontends
- [x] admins rarely need old frontends

## Phase 7: Platform Maturity

Goal: turn OS into the real long-term platform layer.

### Shared Platform Services

- [x] build unified search across modules
- [x] build unified notifications and inbox
- [x] build shared media and asset library
- [x] build unified analytics and reporting
- [x] build event-driven workflow automations
- [x] build cross-tool activity history

### Long-Term Backend Decisions

- [x] review whether any engine logic should move into shared platform services
- [x] evaluate whether billing should become OS-native source of truth
- [x] evaluate whether permissions should become fully centralized in OS
- [x] evaluate whether some old frontends can be retired entirely

### Exit Criteria

- [x] OS is the real product layer
- [x] old apps function as backend engines or internal tools only

## Build Order For The Next Major Work Cycles

This is the recommended order to execute now.

### Cycle 1

- [x] finish source-of-truth documentation
- [x] finish identity and role stabilization
- [x] finish module integration reliability and health monitoring

### Cycle 2

- [x] complete founder dashboard
- [x] complete founder task center
- [x] complete founder campaign and content center

### Cycle 3

- [x] complete website, product, and service management in OS
- [x] complete founder settings and subscription visibility

### Cycle 4

- [x] complete mentor workspace
- [x] complete mentor founder review flows

### Cycle 5

- [x] complete admin control center
- [x] complete module health and audit tooling

### Cycle 6

- [x] remove normal dependence on legacy frontends
- [x] keep launches only for fallback and internal operations

## Definition Of Done For Hatchers Ai OS

Hatchers Ai OS is considered complete when:

- [x] a founder can run their business from OS without using LMS, Atlas, Bazaar, or Servio frontends
- [x] a mentor can manage founders from OS without using LMS frontend
- [x] an admin can manage subscribers, mentors, founders, and module operations from OS
- [x] all major workflows are OS-native
- [x] old tools act as backend engines or internal support consoles only

## Immediate Next Tasks

These are the highest-priority next tasks from the current state.

- [x] finalize source-of-truth ownership document
- [x] finalize integration contract document for LMS, Atlas, Bazaar, and Servio
- [x] complete founder dashboard so it becomes the single daily home
- [x] build OS-native founder task and milestone center
- [x] build OS-native founder campaign and content center
- [x] build OS-native products and services management
- [x] deepen admin control center and sync health tooling
