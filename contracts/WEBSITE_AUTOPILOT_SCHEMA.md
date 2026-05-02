# Website Autopilot Schema

## Purpose

This contract defines the normalized payload Hatchers should generate before mapping content into Servio or Bazaar.

The goal is to make website creation fully autopilot:

- template choice is only a presentation decision
- the AI generates all copy, structure, pricing, catalog, trust, SEO, and media direction
- every website field is filled
- every relevant feature is turned on

## Core rules

- No blank critical fields
- No placeholder repetition
- No missing media for visible sections
- At least one long-form blog article with a featured image
- Contact, CTA, pricing, trust, and story must always exist
- Platform toggles should default to enabled when relevant

## Normalized schema

### Identity

- `business_name`
- `founder_name`
- `brand_name`
- `website_title`
- `website_path`
- `custom_domain`
- `website_engine`
- `website_mode`
- `theme_template`
- `theme_label`

### Market

- `industry`
- `niche`
- `city`
- `state`
- `country`
- `target_audience`
- `primary_icp_name`
- `ideal_customer_profile`
- `pain_points`
- `desired_outcomes`
- `objections`
- `competitor_summary`
- `competitor_price_range`
- `local_market_notes`
- `seo_keyword_cluster`

### Brand

- `brand_voice`
- `brand_tone`
- `brand_values`
- `visual_direction`
- `brand_description`
- `primary_color`
- `secondary_color`
- `logo`
- `dark_logo`
- `favicon`
- `og_image`

### Hero

- `hero_headline`
- `hero_subhead`
- `hero_brief`
- `hero_primary_cta`
- `hero_secondary_cta`
- `hero_image`
- `hero_image_alt`
- `hero_trust_line`

### Story

- `about_content`
- `story_title`
- `story_subtitle`
- `story_description`
- `story_items`
- `founder_story`
- `credibility_points`
- `philosophy`

### Conversion

- `core_offer`
- `offer_stack`
- `pricing_strategy`
- `starter_offer`
- `anchor_offer`
- `premium_offer`
- `upsells`
- `cta_goal`
- `booking_goal`
- `lead_magnet`

### Trust

- `feature_items`
- `why_choose_us_items`
- `testimonials`
- `faq_items`
- `guarantees`
- `proof_points`
- `social_links`
- `google_review_url`

### Contact

- `contact_email`
- `contact_phone`
- `business_address`
- `business_hours`
- `whatsapp_number`
- `contact_page_copy`
- `contact_image`

### SEO

- `meta_title`
- `meta_description`
- `indexable_pages`
- `internal_links`
- `blog_keyword_targets`

### Media

- `media_assets`
- `media_queries`
- `hero_banner`
- `feature_images`
- `testimonial_images`
- `blog_featured_image`
- `faq_image`
- `subscribe_image`
- `fallback_images`

### Blog

- `blog_title`
- `blog_slug`
- `blog_excerpt`
- `blog_body`
- `blog_featured_image`
- `blog_featured_image_alt`
- `blog_cta`

### Ops

- `launch_checklist`
- `quality_audit`
- `readiness_flags`
- `publish_status`

## Servio checklist

### Website settings

- website title
- website path
- custom domain
- theme
- description
- meta title
- meta description
- contact email
- contact phone
- business address
- business hours
- WhatsApp number
- Google review URL
- about content
- story title
- story subtitle
- story description
- hero headline
- hero subhead
- hero brief
- logo
- dark logo
- favicon
- primary color
- secondary color
- footer description
- homepage title
- homepage subtitle
- homepage banner
- subscribe image
- FAQ image
- contact image
- store unavailable image
- no-data image
- order success image
- auth image
- admin auth image
- referral image

### Content modules

- FAQs
- social links
- features
- testimonials
- why choose us items
- story items
- banners
- blog posts

### Service catalog

- service categories
- services
- service images
- pricing
- durations
- capacity
- open time
- close time
- availability days
- staff assignments
- add-on services
- taxes
- promocodes

### Toggles

- landing page on
- online booking on
- service menu on
- shop menu on when relevant
- ratings on
- Google review on
- payment process options on
- mobile app section on
- Android link filled
- iOS link filled

## Bazaar checklist

### Website settings

- website title
- website path
- custom domain
- theme
- description
- meta title
- meta description
- contact email
- contact phone
- business address
- WhatsApp number
- about content
- story title
- story subtitle
- story description
- hero headline
- hero subhead
- hero brief
- logo
- dark logo
- favicon
- primary color
- secondary color
- footer description
- homepage title
- homepage subtitle
- landing home banner
- subscribe image
- FAQ image
- maintenance image
- store unavailable image
- admin auth background image
- order detail image
- order success image
- no-data image

### Content modules

- FAQs
- social links
- features
- testimonials
- footer features
- blog posts

### Catalog

- product categories
- products
- product images
- variants
- extras
- SKU
- prices
- original prices
- taxes
- stock management
- quantity
- low-stock threshold
- min order
- max order
- shipping zones
- coupons

### Toggles

- landing page on
- online order on
- delivery type filled
- ratings on
- Google review on
- product section display filled
- product display limit filled
- mobile app section on
- Android link filled
- iOS link filled
- tips settings enabled when strategy wants it

## Mapping notes

The current direct platform payloads are still smaller than this normalized schema. The normalized schema should be treated as the source of truth, then mapped into:

- Servio website payload
- Bazaar website payload
- service or product creation payloads
- blog creation payloads
- media generation jobs
- review and publish checks

## Required quality gate

Before a site can be published, the build should fail if any of the following are missing:

- website title
- slug
- theme
- hero headline
- hero subhead
- hero image
- logo or branded fallback
- about/story
- at least 3 services or products
- pricing for every offer
- at least 5 FAQs
- at least 3 testimonials
- contact email or phone
- primary CTA
- meta title
- meta description
- one long-form blog article
- one blog featured image
- visible section media coverage
- relevant platform toggles enabled
