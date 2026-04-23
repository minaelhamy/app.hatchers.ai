# Hatchers Ai OS Login Authority And Fallback

## Purpose

This document defines how login should work while Hatchers Ai OS becomes the primary operating system.

## Current Rule

`app.hatchers.ai` is the main login surface.

Users should start at the OS, not at the legacy tools.

## Current Login Order

1. The OS checks the local Hatchers Ai OS account by username or email.
2. If the local account exists and the password matches, the OS signs the user in directly.
3. If the local account does not exist, or the password does not match, the OS can fall back to the LMS bridge.
4. If the LMS bridge succeeds, the OS refreshes or creates the local OS identity and then signs the user into the OS.

## Current Identity Sources

- `os`
  - account was created directly in the OS
- `lms_bridge`
  - account was refreshed or created through LMS fallback login
- `integration_sync`
  - account was created or refreshed by a signed backend identity sync

## Target State

- users authenticate through Hatchers Ai OS
- the OS remains the identity authority for access
- backend engines accept OS-trusted identity context instead of acting as fallback login authorities
