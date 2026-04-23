# Hatchers Ai OS Environment Setup

## Purpose

This document lists the environment and shared-secret setup needed for the current multi-app Hatchers Ai OS architecture.

The goal is to make sure:

- every app points to the correct OS and engine URLs
- every signed request uses the same shared secret
- deployments are consistent across OS, LMS, Atlas, Bazaar, and Servio

## Shared Secret

The same secret must be present anywhere signed server-to-server requests are used:

- `WEBSITE_PLATFORM_SHARED_SECRET`

This value must match in:

- `app.hatchers.ai`
- `lms.hatchers.ai`
- `atlas.hatchers.ai`
- `bazaar.hatchers.ai`
- `servio.hatchers.ai`

## Hatchers Ai OS

Required environment values for `app.hatchers.ai`:

- `APP_URL=https://app.hatchers.ai`
- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `APP_KEY`
- `WEBSITE_PLATFORM_SHARED_SECRET`
- `LMS_BASE_URL=https://lms.hatchers.ai`
- `ATLAS_BASE_URL=https://atlas.hatchers.ai`
- `BAZAAR_BASE_URL=https://bazaar.hatchers.ai`
- `SERVIO_BASE_URL=https://servio.hatchers.ai`

## LMS

Required environment values for LMS:

- `WEBSITE_PLATFORM_SHARED_SECRET`
- `HATCHERS_OS_URL=https://app.hatchers.ai`
- `LMS_URL=https://lms.hatchers.ai`

For the current LMS structure, these may be bootstrapped in `index.php` when a traditional `.env` file is not present.

## Atlas

Required environment values for Atlas:

- `WEBSITE_PLATFORM_SHARED_SECRET`
- `HATCHERS_OS_URL=https://app.hatchers.ai`
- `ATLAS_URL=https://atlas.hatchers.ai`

## Bazaar

Required environment values for Bazaar:

- `WEBSITE_PLATFORM_SHARED_SECRET`
- `HATCHERS_OS_URL=https://app.hatchers.ai`
- `BAZAAR_URL=https://bazaar.hatchers.ai`

## Servio

Required environment values for Servio:

- `WEBSITE_PLATFORM_SHARED_SECRET`
- `HATCHERS_OS_URL=https://app.hatchers.ai`
- `SERVIO_URL=https://servio.hatchers.ai`

## Operational Checks

After deployment, verify:

- signed snapshot requests return success
- signed action endpoints return success
- OS login can use the LMS bridge when needed
- module launches open correctly
- module health in the OS admin dashboard reflects live sync activity

## Immediate Working Rule

Whenever a signed request fails unexpectedly, check these first:

- the secret matches across all apps
- the base URL in the calling app is correct
- the receiving route is deployed and reachable
- the receiving app has the required environment bootstrapped
