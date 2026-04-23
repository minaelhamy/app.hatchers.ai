# Workflow Replacement Map

This document defines the top daily workflows that must be OS-native so Hatchers Ai Business OS becomes the normal product surface and old apps become fallback-only.

## Founder Workflows

1. Weekly home and priorities  
OS route: `/dashboard/founder`

2. Notifications and inbox  
OS routes: `/inbox`, `/notifications`

3. Weekly tasks and milestone completion  
OS route: `/tasks`

4. Learning plan and lesson progression  
OS route: `/learning-plan`

5. Campaign creation and campaign management  
OS route: `/marketing`

6. Content planning, draft generation, review, and publish handoff  
OS route: `/marketing`

7. Website setup, title, path, domain, theme, and publish  
OS route: `/website`

8. Product and service offer management  
OS route: `/commerce`

9. Orders and bookings operations  
OS routes: `/commerce/orders`, `/commerce/bookings`

10. Search, analytics, activity, media, and automations  
OS routes: `/search`, `/analytics`, `/activity`, `/media-library`, `/automations`

## Mentor Workflows

1. Portfolio overview  
OS route: `/dashboard/mentor`

2. Founder review  
OS route: `/mentor/founders/{founder}`

3. Mentor notes and guidance  
OS route: `/mentor/founders/{founder}`

4. Mentor-side task and milestone updates  
OS route: `/mentor/founders/{founder}`

5. Meeting prep and execution summaries  
OS route: `/mentor/founders/{founder}`

## Admin Workflows

1. System overview  
OS route: `/dashboard/admin`

2. Subscriber reporting and filtering  
OS route: `/admin/subscribers`

3. Founder operations  
OS route: `/admin/control`

4. Mentor and admin access control  
OS route: `/admin/system-access`

5. Identity health and login authority  
OS route: `/admin/identity`

6. Module monitoring and recovery  
OS route: `/admin/modules`

7. Support and exception handling  
OS route: `/admin/support`

## Replacement Rule

Normal user journeys should route to the OS pages above.

Legacy engine launch is allowed only when:

- support needs backend verification
- a temporary engine capability gap still exists
- an admin is handling a rare edge case

Legacy launches should not appear as the primary call to action on daily founder or mentor pages.
