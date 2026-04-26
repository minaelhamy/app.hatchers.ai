# Hatchers Vertical Revenue OS Roadmap

## Purpose

This document defines the next product-build roadmap for Hatchers after the current OS, commerce, website, and payment foundation.

The goal is not to rebuild the platform.
The goal is to use the current Hatchers OS stack and push it into a stronger moat:

- verticalized local business operating systems
- daily execution systems
- local customer acquisition systems
- conversion systems
- first-100-customers feedback loops

This roadmap assumes we will continue using:

- `app.hatchers.ai` as the founder, mentor, and admin operating system
- `Atlas` as intelligence, prompt orchestration, content generation, website copy generation, image sourcing, and strategy engine
- `Bazaar` as the product-commerce backend engine
- `Servio` as the service-commerce backend engine
- `LMS` as the learning, milestones, mentor, and accountability engine

The roadmap also assumes one key product principle:

- founders write their business brief and ICP
- Hatchers builds the first version of the business for them
- the system chooses the right engine, template, imagery, copy, offer structure, and launch plan
- founders can then edit and optimize what was generated

This is the opposite of a blank-canvas builder.

---

## Strategic Product Position

Hatchers should not present itself as:

- an AI tools platform
- a website builder
- a CRM
- a booking tool
- a store builder

Hatchers should present itself as:

- the operating system that helps local businesses launch and start making money

Big AI companies will keep building general intelligence.
Hatchers should build:

- business-specific launch systems
- local customer acquisition playbooks
- daily execution systems
- conversion systems
- payments and cashflow systems
- performance loops for the first 100 customers

---

## Core Founder Methodology

The founder system should be guided by the commercial logic of **Sell Like Crazy** by Sabri Suby.

This means every founder workflow should orient around:

1. clear offer
2. clear niche / ICP
3. direct-response messaging
4. lead capture
5. follow-up
6. conversion
7. upsell / lifetime value
8. measurement and optimization

This should shape:

- onboarding questions
- ICP capture
- website structure
- lead magnet logic where relevant
- offer packaging
- landing page copy
- CTA strategy
- daily tasks
- ad/content/outreach scripts
- follow-up automations
- reporting

Hatchers should not just “help them build a business.”
It should help them:

- attract attention
- capture leads
- follow up
- close sales
- increase customer value

---

## Use What Already Exists

We should not start from scratch.
We should build on the current OS foundation that already exists:

### Already Built And Reusable

- founder, mentor, and admin OS shells
- founder onboarding and founder plan flow
- OS-native marketing, website, commerce, tasks, learning, analytics, activity, automations
- Bazaar and Servio founder-side offer management
- Bazaar and Servio founder-side orders and bookings operations
- OS-native public sites under `app.hatchers.ai/{company-slug}`
- Stripe checkout, wallet, payouts, refunds, disputes, finance control
- module snapshots and signed engine actions
- website provisioning and theme selection primitives
- Atlas-backed generation and workflow launching
- Pexels API integration readiness in Atlas

### What We Should Reuse Aggressively

- `FounderActionPlan` for structured execution tasks and daily systems
- `CompanyIntelligence` for company brief, ICP, positioning, offer logic, and generated strategy
- existing website generation flow for OS-native site creation
- existing commerce engine routing logic to choose `Bazaar` or `Servio`
- existing marketing workspace for campaign and content generation
- existing automations for reminders and follow-up logic
- existing wallet/payments for founder monetization

---

## First Five Vertical Systems

These first five verticals should be built as “Hatchers Business-in-a-Box” systems:

1. Dog Walking
2. Home Cleaning
3. Barbershop / Barber Services
4. Tutoring / Coaching Sessions
5. Handmade Products / Small Product Brand

### Why These Five

- strong local demand
- easy to understand offers
- good mix of service and product models
- repeat purchase potential
- high relevance for first-customer playbooks
- good fit for Bazaar/Servio split

### Engine Mapping

- Dog Walking -> `Servio`
- Home Cleaning -> `Servio`
- Barbershop / Barber Services -> `Servio`
- Tutoring / Coaching Sessions -> `Servio`
- Handmade Products / Small Product Brand -> `Bazaar`

---

## Exact Modules To Create

These are the new product modules to add on top of the current OS.

### 1. Vertical Blueprint Engine

Purpose:
- choose the right business system by vertical
- define the founder’s launch path before they touch tools

Responsibilities:
- vertical selection
- business model lock
- starter offer templates
- vertical-specific onboarding fields
- recommended pricing model
- default website structure
- default lead capture CTA
- default first-30-day task engine

Outputs:
- company blueprint
- ICP baseline
- offer baseline
- default channel strategy
- default website generation brief

### 2. Business Brief + ICP Engine

Purpose:
- collect founder business brief and ICP in a structured way
- turn it into execution-ready inputs for Atlas, Bazaar, Servio, and website generation

Responsibilities:
- founder brief intake
- ICP intake
- problem / outcome capture
- offer structure
- differentiator
- local geography / service radius / target location
- primary CTA model

Outputs:
- company brief
- ICP profile
- offer narrative
- proof / trust positioning
- pricing strategy prompt inputs

### 3. Sell Like Crazy Funnel Engine

Purpose:
- create a direct-response launch funnel, not just a brochure site

Responsibilities:
- choose funnel type by vertical
- hero offer
- CTA strategy
- lead magnet where relevant
- booking CTA or product purchase CTA
- trust / proof section generation
- urgency / guarantee / objection handling blocks
- offer stack and upsell logic

Outputs:
- website structure
- landing page copy structure
- form / booking / buy CTA logic
- follow-up sequence trigger points

### 4. Website Autopilot Builder

Purpose:
- generate the first ready-to-launch site for the founder automatically

Responsibilities:
- select engine: `Bazaar` or `Servio`
- select internal template based on vertical
- pull images using Pexels through Atlas
- generate all first-pass copy through Atlas
- structure pages
- wire forms / products / services / booking / checkout
- publish preview-ready site under `app.hatchers.ai/{company-slug}`

Outputs:
- first generated website
- page sections
- image set
- CTA wiring
- service/product cards

### 5. Daily Revenue OS

Purpose:
- tell the founder what to do today to move revenue

Responsibilities:
- daily task generation
- local acquisition tasks
- website improvement tasks
- offer optimization tasks
- follow-up tasks
- lead response tasks
- fulfillment tasks
- “money-making next action” prioritization

Outputs:
- daily task queue
- weekly action plan
- milestone progress
- execution reminders

### 6. Local Customer Acquisition Engine

Purpose:
- tell local businesses where and how to get customers now

Responsibilities:
- vertical + city strategy
- local channels list
- Facebook group strategy
- Nextdoor strategy
- Google Business Profile tasks
- SEO keyword starter set
- outreach scripts
- offer promotion suggestions

Outputs:
- channel plan
- first 7-day acquisition sprint
- scripts
- local keyword sets
- CTA suggestions

### 7. Conversation + Follow-Up Engine

Purpose:
- help founders convert inbound leads faster

Responsibilities:
- DM reply suggestions
- WhatsApp / SMS / email reply suggestions
- lead follow-up timing
- booking closing messages
- product order confirmation scripts
- upsell follow-up

Outputs:
- saved templates
- suggested replies
- follow-up automations
- conversion-stage messaging

### 8. Offer + Pricing Optimizer

Purpose:
- turn weak founder offers into clearer, higher-converting offers

Responsibilities:
- pricing tier generation
- bundle suggestions
- add-on suggestions
- weekly / package / subscription pricing
- service premium options
- product upsell logic

Outputs:
- recommended pricing ladder
- bundle structure
- upsell structure
- A/B test ideas later

### 9. First 100 Customers Tracker

Purpose:
- create a tight feedback loop around customer acquisition and conversion

Responsibilities:
- track leads
- track outreach volume
- track conversions by channel
- track repeat purchases / rebookings
- compare channel performance
- show “what’s working”

Outputs:
- first 10 / 25 / 50 / 100 customer milestones
- conversion dashboard
- channel insights
- optimization suggestions

### 10. Offline-to-Online Bridge

Purpose:
- support real-world local acquisition

Responsibilities:
- QR flyer generator
- local promo links
- referral codes
- SMS capture entry points
- printable offer cards
- in-person lead capture forms

Outputs:
- trackable local links
- QR assets
- promo code setup
- offline conversion tracking

### 11. Vertical Pod Community Layer

Purpose:
- create retention and peer-learning loops

Responsibilities:
- founder grouping by vertical and stage
- pod prompts
- weekly wins / blockers
- shared script examples

Outputs:
- pod membership
- insight feed
- peer benchmarks

This should come later, after the execution engine is solid.

---

## Database / Data Model Changes

These are the exact data model additions recommended for the next stage.

### New Or Expanded Entities

#### 1. `vertical_blueprints`

Purpose:
- canonical definitions for each business vertical

Fields:
- `id`
- `code`
- `name`
- `business_model`
- `engine`
- `description`
- `default_offer_json`
- `default_pricing_json`
- `default_pages_json`
- `default_tasks_json`
- `default_channels_json`
- `default_cta_json`
- `default_image_queries_json`
- `status`

#### 2. `founder_business_briefs`

Purpose:
- founder’s structured company brief

Fields:
- `id`
- `founder_id`
- `company_id`
- `vertical_blueprint_id`
- `business_name`
- `business_summary`
- `problem_solved`
- `core_offer`
- `business_type_detail`
- `location_city`
- `location_country`
- `service_radius`
- `delivery_scope`
- `proof_points`
- `founder_story`
- `constraints_json`
- `status`

#### 3. `founder_icp_profiles`

Purpose:
- structured ICP capture

Fields:
- `id`
- `founder_id`
- `company_id`
- `primary_icp_name`
- `age_range`
- `gender_focus`
- `life_stage`
- `pain_points_json`
- `desired_outcomes_json`
- `buying_triggers_json`
- `objections_json`
- `price_sensitivity`
- `primary_channels_json`
- `local_area_focus_json`
- `language_style`

#### 4. `founder_launch_systems`

Purpose:
- bind the founder to a generated launch system

Fields:
- `id`
- `founder_id`
- `company_id`
- `vertical_blueprint_id`
- `funnel_type`
- `primary_goal`
- `launch_status`
- `website_generation_status`
- `offer_generation_status`
- `acquisition_plan_status`
- `daily_execution_status`
- `first_100_status`
- `generated_assets_json`

#### 5. `founder_lead_channels`

Purpose:
- track acquisition channels

Fields:
- `id`
- `founder_id`
- `company_id`
- `channel_type`
- `channel_name`
- `channel_url`
- `channel_region`
- `status`
- `cost`
- `notes`

#### 6. `founder_leads`

Purpose:
- first-100-customers and outreach tracking

Fields:
- `id`
- `founder_id`
- `company_id`
- `source_channel_id`
- `lead_name`
- `lead_contact`
- `lead_stage`
- `lead_source`
- `lead_value_estimate`
- `first_contacted_at`
- `last_contacted_at`
- `converted_at`
- `notes`
- `meta_json`

#### 7. `founder_conversation_threads`

Purpose:
- store customer conversation context

Fields:
- `id`
- `founder_id`
- `company_id`
- `lead_id`
- `channel`
- `subject`
- `status`
- `last_message_at`
- `meta_json`

#### 8. `founder_pricing_recommendations`

Purpose:
- recommended pricing / bundles / upsells

Fields:
- `id`
- `founder_id`
- `company_id`
- `offer_reference`
- `recommendation_type`
- `recommendation_json`
- `accepted_at`
- `rejected_at`

#### 9. `founder_site_generation_runs`

Purpose:
- track generated websites and regeneration cycles

Fields:
- `id`
- `founder_id`
- `company_id`
- `engine`
- `template_key`
- `generation_type`
- `input_snapshot_json`
- `output_snapshot_json`
- `status`
- `generated_at`

#### 10. `founder_first_100_trackers`

Purpose:
- milestone tracking toward first 100 customers

Fields:
- `id`
- `founder_id`
- `company_id`
- `target_customers`
- `customers_acquired`
- `repeat_customers`
- `best_channel`
- `best_offer`
- `current_bottleneck`
- `insights_json`

### Existing Tables To Extend

#### `companies`

Add:
- `vertical_blueprint_id`
- `primary_city`
- `service_radius`
- `primary_goal`
- `website_generation_status`
- `launch_stage`

#### `company_intelligence`

Expand use for:
- offer narrative
- ICP summary
- direct-response hooks
- objections
- guarantees
- upsells
- CTA strategy

#### `founder_action_plans`

Continue using for:
- daily execution tasks
- local acquisition tasks
- follow-up tasks
- website improvement tasks
- first-100-customer tasks

#### `commercial_summaries`

Extend to support:
- lead counts
- lead conversion rate
- rebooking rate
- repeat customer rate
- top acquisition channel

---

## Founder UX Flow

This should be the exact founder journey.

### Phase A: Founder Signup

1. founder signs up
2. founder verifies email
3. founder logs in
4. founder lands in a guided “Build Your Business OS” flow

### Phase B: Vertical + Brief Intake

Questions:
- what kind of business are you building?
- service or product?
- what do you sell?
- who is it for?
- where do you want customers from?
- what result do you help customers get?
- what makes your offer different?
- do you want leads, bookings, or purchases?

Then:
- founder writes business brief
- founder writes ICP
- founder confirms city / target market

### Phase C: Hatchers Builds The First Business

System action:
- choose `Bazaar` or `Servio`
- choose internal vertical template
- use Atlas to generate:
  - headline
  - subheadline
  - offer sections
  - about copy
  - FAQ
  - CTA copy
  - follow-up messaging
- use Atlas + Pexels API to source images
- generate pricing structure
- generate starter products or services
- create ready website under `app.hatchers.ai/{company-slug}`

Founder sees:
- “Your first business system is ready”

### Phase D: Founder Review + Edit

Founder can then:
- edit website sections
- edit offer and pricing
- edit products or services
- swap images
- edit CTA
- publish

But the founder is not starting blank.

### Phase E: Launch Sprint

Founder home should then switch to:
- Today’s revenue tasks
- local acquisition tasks
- follow-up tasks
- first-100-customer tracker

### Phase F: Optimization Loop

Once there is activity:
- show which channel is working
- show which offer is converting
- suggest pricing changes
- suggest follow-up actions
- suggest website changes

---

## Admin Controls To Build

Admins need operational control over the vertical system.

### Vertical Blueprint Control

Admins should manage:
- vertical definitions
- vertical activation/deactivation
- template versions
- default task systems
- default pricing models
- default image queries
- default CTA/funnel logic

### Business Brief Review

Admins should be able to:
- inspect founder brief
- inspect ICP
- override engine choice
- trigger regeneration
- trigger manual review flags

### Website Generation Control

Admins should be able to:
- see generation runs
- regenerate copy
- regenerate imagery
- change template selection
- change engine mapping

### Acquisition Control

Admins should manage:
- vertical channel playbooks
- city-specific channel suggestions
- outreach script libraries
- local keyword sets

### Pricing / Offer Governance

Admins should manage:
- offer templates
- vertical pricing presets
- bundle presets
- add-on presets

### First 100 Customers Oversight

Admins should be able to:
- see funnel bottlenecks across founders
- compare vertical performance
- identify best-performing acquisition channels
- identify weak conversions by vertical

### Mentor Controls

Mentors should be able to:
- review founder brief and ICP
- review generated website
- review daily execution queue
- see acquisition progress
- suggest revisions to offer and pricing

---

## Phase-By-Phase Execution Plan

## Phase 1: Blueprint Foundation

Goal:
- make the OS understand business verticals and founder brief/ICP

Build:
- `Vertical Blueprint Engine`
- `Business Brief + ICP Engine`
- data models for blueprint, business brief, ICP
- founder onboarding extension to collect these
- admin blueprint management workspace

Use current assets:
- founder onboarding
- company / company_intelligence
- founder settings

Exit criteria:
- every founder is attached to a vertical blueprint
- every founder has a structured business brief and ICP
- OS knows whether to build with Bazaar or Servio automatically

## Phase 2: Website Autopilot Builder

Goal:
- auto-build the founder’s first website

Build:
- Atlas prompt pipelines for website copy
- Pexels image selection via Atlas
- vertical-specific template selector
- automatic page/section generation
- automatic product/service generation
- founder review/regenerate flow

Use current assets:
- website workspace
- website provisioning
- public website rendering
- Bazaar/Servio offer manager
- Pexels API readiness in Atlas

Exit criteria:
- founder fills brief + ICP
- founder receives a ready first site
- founder can edit and publish that site

## Phase 3: Sell Like Crazy Funnel Layer

Goal:
- make the websites direct-response systems, not brochure sites

Build:
- vertical CTA frameworks
- headline frameworks
- hero / proof / objections / FAQ / urgency blocks
- lead capture and booking/purchase logic
- guarantee/risk-reversal blocks where relevant
- offer stack generation

Use current assets:
- website builder
- public-site forms
- checkout / booking request flows
- marketing content generation

Exit criteria:
- every generated site follows direct-response structure
- every founder has a primary offer and CTA flow

## Phase 4: Daily Revenue OS

Goal:
- turn the founder dashboard into a “do this today” system

Build:
- daily execution engine
- task prioritization by revenue impact
- follow-up tasks
- website optimization tasks
- acquisition tasks
- first-week, first-30-day playbooks

Use current assets:
- founder dashboard
- tasks
- learning plan
- automations
- founder action plans

Exit criteria:
- founder home is primarily a revenue execution interface
- tasks are vertical-specific and brief-specific

## Phase 5: Local Customer Acquisition Engine

Goal:
- tell founders where to get customers locally

Build:
- local channel database
- vertical + city playbooks
- outreach scripts
- local SEO starter pack
- local promo campaign builder

Use current assets:
- marketing workspace
- Atlas generation
- automations
- activity tracking

Exit criteria:
- founder gets a local acquisition plan immediately after launch
- founder gets scripts and channels, not generic advice

## Phase 6: Offer + Pricing Optimizer

Goal:
- improve conversion and average order value

Build:
- pricing recommendation engine
- bundle recommendations
- upsell recommendations
- package suggestions
- service tiering

Use current assets:
- commerce workspace
- offer manager
- Bazaar and Servio pricing fields

Exit criteria:
- founder sees pricing recommendations in Commerce
- founder can adopt recommendations in one click

## Phase 7: First 100 Customers Tracker

Goal:
- create a measurable founder growth loop

Build:
- lead tracker
- customer milestone tracker
- channel conversion summary
- best channel / best offer reporting

Use current assets:
- analytics
- activity
- wallet / orders / bookings
- commercial summaries

Exit criteria:
- founder sees progress toward first 100 customers
- founder sees what is working and what is not

## Phase 8: Conversation + Follow-Up Engine

Goal:
- help founders close more customers faster

Build:
- DM reply suggestion interface
- WhatsApp/SMS/email follow-up templates
- objection-handling scripts
- closing scripts
- follow-up automations triggered by lead stage

Use current assets:
- automations
- order/booking communication timelines
- marketing content generation

Exit criteria:
- founders can respond faster with better scripts
- follow-up becomes part of the OS instead of an external habit

## Phase 9: Offline-to-Online Bridge

Goal:
- support local demand capture from real-world channels

Build:
- QR offers
- flyer links
- promo code tracking
- quick lead capture forms
- trackable local landing links

Use current assets:
- website
- public routes
- commerce checkout
- Atlas asset generation

Exit criteria:
- founders can run street-level or neighborhood-level acquisition with tracking

## Phase 10: Pod Community Layer

Goal:
- increase retention and shared learning

Build:
- vertical pods
- stage-based pods
- peer prompt flows
- shared wins / blockers

Use current assets:
- founder activity and notification primitives

Exit criteria:
- founders are grouped by vertical and stage
- Hatchers creates network effects around execution

---

## Exact Build Plan For The First Five Verticals

## 1. Dog Walking

Engine:
- `Servio`

Website structure:
- hero
- how it works
- service packages
- service area
- trust / safety
- testimonials
- booking CTA

Daily task examples:
- join 5 local pet owner Facebook groups
- post “new local dog walker” offer
- message 10 neighbors / local leads
- collect 3 testimonials
- follow up with all booking inquiries

Pricing presets:
- single walk
- 3-walk weekly plan
- 5-walk weekly plan
- add-ons: feeding, photo updates, extended walk

## 2. Home Cleaning

Engine:
- `Servio`

Website structure:
- hero
- before/after trust positioning
- cleaning packages
- service area
- why choose us
- FAQ
- quote / booking CTA

Daily task examples:
- list in local groups
- message apartment communities
- post before/after educational content
- follow up with quote leads
- optimize Google Business Profile tasks

Pricing presets:
- standard clean
- deep clean
- weekly plan
- move-in / move-out

## 3. Barbershop / Barber Services

Engine:
- `Servio`

Website structure:
- hero
- service menu
- book-now CTA
- style gallery
- location / hours
- trust / reviews

Daily task examples:
- post haircut examples
- follow up with prior customers
- launch referral offer
- update booking availability
- DM local prospects / community groups

Pricing presets:
- haircut
- beard trim
- combo
- premium grooming package

## 4. Tutoring / Coaching Sessions

Engine:
- `Servio`

Website structure:
- hero
- outcomes
- who it’s for
- session plans
- credibility
- FAQ
- book consultation CTA

Daily task examples:
- post expertise content
- contact parent groups / local student groups
- follow up with inquiries
- improve authority section
- ask for referral introductions

Pricing presets:
- intro session
- single session
- weekly package
- monthly package

## 5. Handmade Products / Small Product Brand

Engine:
- `Bazaar`

Website structure:
- hero
- featured collection
- product grid
- about the maker
- trust / shipping
- offer CTA

Daily task examples:
- post product photos
- offer limited bundle
- message local communities and interest groups
- optimize product pages
- follow up with abandoned interest / inquiries

Pricing presets:
- single item
- bundle
- limited edition
- upsell extras

---

## Recommended Delivery Order

### Sprint Order

1. blueprint engine
2. brief + ICP intake
3. autopilot website builder
4. direct-response funnel layer
5. daily revenue OS
6. local acquisition engine
7. pricing optimizer
8. first 100 customers tracker
9. conversation engine
10. offline-to-online bridge
11. community pods

### First Vertical Rollout Order

1. Dog Walking
2. Home Cleaning
3. Tutoring / Coaching
4. Barbershop
5. Handmade Products

This order is recommended because:
- dog walking and cleaning are strong local-service launch cases
- tutoring adds another service type with consultative selling
- barber adds appointment-driven local repeat demand
- handmade products gives us the first fully product-led blueprint

---

## Definition Of Done For This Next Stage

Hatchers reaches the next strategic milestone when:

- a founder chooses a vertical
- writes a business brief and ICP
- gets a ready website generated for them
- gets products or services generated for them
- gets pricing generated for them
- gets daily revenue tasks generated for them
- gets local acquisition scripts generated for them
- can sell and get paid immediately
- can track first-customer progress inside the OS

At that point Hatchers is no longer just a unified tool OS.
It becomes:

- a local revenue operating system

