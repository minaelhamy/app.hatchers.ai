# Platform Decisions

This document records the current long-term platform decisions for Hatchers Ai Business OS.

## Shared Platform Services

### Should engine logic move into shared platform services?

Decision: selectively, not by default.

- Keep specialized business logic in LMS, Atlas, Bazaar, and Servio while those engines remain stable and useful.
- Move logic into shared OS services only when:
  - multiple engines need the same logic
  - the OS needs a source-of-truth function that should not depend on one engine
  - the engine version of the logic becomes a delivery bottleneck

## Billing

### Should billing become OS-native source of truth?

Decision: yes, strategically.

- The OS should become the long-term source of truth for subscription, billing state, and entitlements.
- Engine-specific monetization details can remain downstream while the OS owns customer account state and access.

## Permissions

### Should permissions become fully centralized in OS?

Decision: yes.

- Role, permission, and entitlement checks should be owned by the OS.
- Engines should accept signed OS actions and trust OS-issued context rather than acting as separate permission authorities for daily user work.

## Old Frontends

### Can old frontends be retired entirely?

Decision: gradually, by workflow.

- Founder and mentor daily work should move completely into the OS.
- Admin daily operations should also move into the OS.
- Old frontends may remain as:
  - backend operations consoles
  - rare fallback surfaces
  - internal maintenance tools

The retirement threshold is reached when:

- the workflow replacement map is covered in OS-native screens
- launches are used only for rare fallback
- engine UI is no longer part of normal journeys
