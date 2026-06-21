# Core Hardening Design

**Date:** 2026-06-21
**Status:** Approved for implementation planning

## Objective

Make the WordPress reading platform safe to extend by fixing correctness and
abuse-control defects in its financial, scheduling, moderation, and REST
workflows, and by adding a repeatable automated test baseline.

This is Phase 1 of the platform upgrade. Phase 2 will cover verified accounts,
audio continuity, notifications, and PWA behavior. Phase 3 will add payment
providers, signed webhooks, reconciliation, and refunds. Those phases must not
start until this phase is verified.

## Scope

Phase 1 includes:

- Atomic chapter and full-story purchases.
- Correct scheduled chapter publication and downstream notifications/cache
  invalidation.
- Effective nonce validation for admin comment and report actions.
- Trusted-proxy-aware client IP resolution.
- Comment length limits, per-user throttling, parent validation, and moderation.
- A lightweight PHP test harness that can run without booting a production
  WordPress database.
- Syntax checks and local HTTP smoke checks when MySQL is available.

Phase 1 does not include payment gateway integration, UI redesign, PWA support,
email delivery, or broad restructuring of the theme and admin plugin.

## Architecture

### Test Boundary

Create a small test suite under `tests/` using PHP's built-in assertions and
project-owned WordPress stubs. The suite will load focused production classes,
inject a deterministic fake `$wpdb`, and exercise observable outcomes. This
avoids introducing a large testing framework before the project has a stable
Composer toolchain while still enforcing red-green TDD.

`package.json` will expose one canonical `npm test` command that runs PHP unit
tests, PHP syntax checks, and the JavaScript syntax check. A separate smoke
script may call localhost routes and will be skipped with a clear message when
the database is unavailable.

### Atomic Purchases

Move purchase orchestration behind one database-layer operation per purchase
type. The operation will:

1. Begin a database transaction.
2. Lock or conditionally debit the user's credit row.
3. Insert the unique purchase record.
4. Insert the credit transaction record.
5. Commit only when all required writes succeed.
6. Roll back on insufficient balance, duplicates, or database failure.

Notifications are emitted only after a successful commit. The REST response
reads the committed balance rather than calculating it from a stale value.
Duplicate requests are idempotent: an existing purchase returns success without
another debit.

For full-story purchases, existing single-chapter purchases are removed only
inside the successful transaction. This phase preserves the current pricing
rule and does not introduce partial refunds or price offsets.

### Scheduled Publication

The cron handler first selects due chapters and groups them by story, then
publishes them in one update. After a successful update it recalculates each
affected story's published chapter count, invalidates story/home cache, and
sends one new-chapter notification for each chapter that changed from
`scheduled` to `published`.

Repeated cron runs must not notify twice because only rows still in the
`scheduled` state are selected.

### Admin Action Protection

Comment and report action handlers validate the same action-specific nonce used
to construct their URLs before changing state. Existing capability checks stay
in place. Invalid or missing nonces terminate through WordPress's standard
admin-referer failure path.

### IP Resolution and Rate Limits

`REMOTE_ADDR` is authoritative by default. Forwarded headers are considered
only when `REMOTE_ADDR` belongs to a configured trusted proxy supplied by the
`hdk_trusted_proxy_ips` filter. The first valid public/client address in the
forwarding chain is used; malformed values fall back to `REMOTE_ADDR`.

General REST/chapter throttling remains IP-based. Comment throttling is
user-based so a shared network does not allow one account to evade limits or
one busy account to block every reader behind the same NAT.

### Comment Safety

Comments must be non-empty and no longer than 2,000 Unicode characters. Each
user may submit at most five comments in five minutes. A reply's parent must be
an HDK comment for the same story and chapter; otherwise the API returns a 400
error and sends no notification.

New comments use WordPress's moderation policy instead of unconditional
approval. Administrators can still approve them through the protected admin
workflow. Rate-limit responses use HTTP 429 and include a stable error code for
the frontend.

## Data Integrity and Compatibility

- Existing table names and public REST routes remain unchanged.
- Existing unique purchase indexes remain the idempotency backstop.
- No destructive schema migration is required for Phase 1.
- Transactional guarantees require InnoDB, matching the documented MySQL 8 or
  MariaDB 10.6 deployment requirement.
- If the database rejects `START TRANSACTION`, purchase endpoints fail closed;
  they never grant access without a confirmed debit and purchase record.
- Existing unlocked chapters and balances are not recalculated.

## Error Handling

REST errors retain machine-readable codes and appropriate statuses:

- `insufficient_credits`: 402
- `purchase_failed`: 500
- `invalid_parent`: 400
- `comment_too_long`: 400
- `comment_rate_limited`: 429
- `rest_forbidden`: 403

Database errors are logged through WordPress when debug logging is enabled, but
raw SQL errors are never returned to readers.

## Verification

Automated tests must prove:

- A failed debit cannot create a purchase or transaction.
- A failed purchase insert rolls back the debit.
- Duplicate purchase requests debit once.
- Full purchase cleanup occurs only on commit.
- Scheduled chapters are discovered before update, published once, counted,
  notified once, and invalidated.
- Missing or invalid moderation nonces cannot change comment/report state.
- Spoofed forwarding headers are ignored for untrusted peers.
- Trusted proxy forwarding chains resolve deterministically.
- Empty, oversized, rapid, or cross-story reply comments are rejected.
- Valid comments follow the configured WordPress moderation result.

The completion gate is a clean `npm test`, clean PHP/JavaScript syntax checks,
and successful route smoke checks when the local database is reachable.

## Rollout

Deploy plugin and test changes together. Run schema verification and the full
test command before deployment. After deployment, perform one free chapter
read, one paid chapter purchase, one duplicate purchase retry, one moderated
comment, and one scheduled chapter publication in a staging environment before
enabling the release in production.

## Follow-on Phases

Phase 2 consumes the stable account and notification behavior from this phase.
Phase 3 consumes the atomic credit ledger and will add payment-intent and
webhook idempotency as separate tables rather than overloading chapter purchase
records.
