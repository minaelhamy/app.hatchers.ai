# Module Snapshot Contracts

## Purpose

These contracts define the normalized summary payloads that Hatchers OS expects from each underlying module.

The goal is to avoid tight coupling to module internals while still giving the OS enough information to render a unified dashboard and power Atlas context.

## General contract shape

Each snapshot should follow this high-level structure:

```json
{
  "founder_id": "string-or-integer",
  "module": "lms|atlas|bazaar|servio",
  "updated_at": "ISO-8601 datetime",
  "readiness_score": 0,
  "current_page": "string",
  "key_counts": {},
  "status_flags": {},
  "recent_activity": [],
  "summary": {}
}
```

## LMS snapshot

### Required fields

```json
{
  "module": "lms",
  "key_counts": {
    "task_count": 0,
    "completed_task_count": 0,
    "milestone_count": 0,
    "completed_milestone_count": 0
  },
  "status_flags": {
    "mentor_assigned": true,
    "has_upcoming_meeting": false
  },
  "summary": {
    "weekly_progress_percent": 0,
    "next_meeting_at": null,
    "weekly_focus": ""
  }
}
```

## Atlas snapshot

### Required fields

```json
{
  "module": "atlas",
  "key_counts": {
    "generated_posts_count": 0,
    "generated_campaigns_count": 0,
    "generated_images_count": 0
  },
  "status_flags": {
    "company_profile_complete": false,
    "company_intelligence_complete": false
  },
  "summary": {
    "company_name": "",
    "business_model": "",
    "brand_voice": "",
    "primary_growth_goal": "",
    "latest_content_summary": ""
  }
}
```

## Bazaar snapshot

### Required fields

```json
{
  "module": "bazaar",
  "key_counts": {
    "product_count": 0,
    "order_count": 0,
    "customer_count": 0
  },
  "status_flags": {
    "store_connected": false,
    "theme_selected": false,
    "store_ready": false
  },
  "summary": {
    "website_title": "",
    "theme_template": "",
    "gross_revenue": 0,
    "currency": "USD"
  }
}
```

## Servio snapshot

### Required fields

```json
{
  "module": "servio",
  "key_counts": {
    "service_count": 0,
    "booking_count": 0,
    "customer_count": 0
  },
  "status_flags": {
    "site_connected": false,
    "theme_selected": false,
    "booking_ready": false
  },
  "summary": {
    "website_title": "",
    "theme_template": "",
    "gross_revenue": 0,
    "currency": "USD"
  }
}
```

## OS aggregate founder state

The OS should combine module snapshots into one unified founder state object with:

- founder identity
- plan and entitlements
- company summary
- weekly execution summary
- commercial summary
- content summary
- module readiness summary
- recommended actions

## Sync guidelines

- snapshots should be small and operational
- detail data remains in source modules
- module snapshots should update after meaningful events
- OS dashboard should primarily read from normalized snapshot state rather than direct module queries
