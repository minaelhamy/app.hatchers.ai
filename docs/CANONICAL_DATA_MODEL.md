# Canonical Data Model

## Goal

This document defines the first version of the canonical entities for Hatchers OS.

The OS should not copy every table from every module. It should define shared entities that allow a unified experience across modules.

## Core entities

## 1. Founder

The root identity for a business operator using Hatchers OS.

Suggested fields:

- `id`
- `username`
- `email`
- `password_hash`
- `status`
- `role`
- `full_name`
- `phone`
- `country`
- `timezone`
- `subscription_id`
- `mentor_entitled_until`
- `created_at`
- `updated_at`

## 2. Company

The core business entity attached to a founder.

Suggested fields:

- `id`
- `founder_id`
- `company_name`
- `business_model`
  - `product`
  - `service`
  - `hybrid`
- `industry`
- `stage`
  - `idea`
  - `launching`
  - `operating`
  - `scaling`
- `website_status`
- `website_url`
- `company_brief`
- `created_at`
- `updated_at`

## 3. Company Intelligence

The shared business-memory layer used by Atlas and the OS.

Suggested fields:

- `id`
- `company_id`
- `target_audience`
- `ideal_customer_profile`
- `brand_voice`
- `differentiators`
- `content_goals`
- `visual_style`
- `core_offer`
- `pricing_notes`
- `primary_growth_goal`
- `known_blockers`
- `last_summary`
- `updated_at`

## 4. Subscription

The source of truth for founder billing state.

Suggested fields:

- `id`
- `founder_id`
- `plan_code`
- `plan_name`
- `billing_status`
- `amount`
- `currency`
- `started_at`
- `mentor_phase_started_at`
- `mentor_phase_ends_at`
- `transitions_to_plan_code`
- `transitions_on`
- `next_billing_at`
- `cancelled_at`
- `created_at`
- `updated_at`

## 5. Mentor Assignment

The founder-to-mentor relationship visible at the OS level.

Suggested fields:

- `id`
- `founder_id`
- `mentor_user_id`
- `status`
- `assigned_at`
- `ended_at`
- `notes`

## 6. Founder Weekly State

An OS-level summary entity optimized for dashboard rendering.

Suggested fields:

- `id`
- `founder_id`
- `open_tasks`
- `completed_tasks`
- `open_milestones`
- `completed_milestones`
- `next_meeting_at`
- `weekly_focus`
- `weekly_progress_percent`
- `updated_at`

## 7. Commercial Summary

An OS-level aggregate record used for dashboard and Atlas context.

Suggested fields:

- `id`
- `founder_id`
- `business_model`
- `product_count`
- `service_count`
- `order_count`
- `booking_count`
- `gross_revenue`
- `currency`
- `customers_count`
- `updated_at`

## 8. Content Summary

Tracks cross-platform AI and growth output at a summary level.

Suggested fields:

- `id`
- `founder_id`
- `generated_posts_count`
- `generated_campaigns_count`
- `generated_images_count`
- `last_generated_at`
- `latest_content_summary`
- `updated_at`

## 9. Founder Action Plan

Stores recommended OS next actions.

Suggested fields:

- `id`
- `founder_id`
- `title`
- `description`
- `platform`
- `priority`
- `status`
- `cta_label`
- `cta_url`
- `created_at`
- `updated_at`

## 10. Module Snapshot

Normalized snapshot row per source module.

Suggested fields:

- `id`
- `founder_id`
- `module`
- `snapshot_version`
- `payload_json`
- `readiness_score`
- `updated_at`

## Ownership rules

- OS owns founder identity, subscriptions, entitlements, and dashboard summaries.
- Atlas owns company intelligence depth and AI memory.
- LMS owns mentor execution detail.
- Bazaar owns ecommerce operational detail.
- Servio owns bookings and service operational detail.

## Immediate implementation note

The first build should focus on these entities conceptually even before final migration files are written:

- Founder
- Company
- Company Intelligence summary
- Subscription
- Mentor Assignment summary
- Commercial Summary
- Founder Action Plan
- Module Snapshot
