# Core Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make purchases, scheduled publication, moderation, client-IP resolution, and comments deterministic and regression-tested.

**Architecture:** Add a dependency-light PHP test runner and extract financial/comment policy decisions into focused plugin classes. Keep existing REST routes and database tables, with transactional orchestration behind the current callbacks.

**Tech Stack:** WordPress 7, PHP 8+, MySQL/MariaDB InnoDB, Node package scripts, project-owned PHP test doubles.

## Global Constraints

- Preserve all existing public URLs and REST route names.
- Do not perform destructive schema changes.
- Use transactions for every multi-write credit operation.
- Follow red-green TDD for every behavioral change.
- Keep production responses free of raw SQL errors.

---

### Task 1: Executable Test Baseline

**Files:**
- Create: `tests/bootstrap.php`
- Create: `tests/run.php`
- Create: `tests/TestCase.php`
- Modify: `package.json`

**Interfaces:**
- Produces: `HDK_TestCase`, `hdk_test(string $name, callable $test)`, and one canonical `npm test` command.

- [ ] **Step 1: Write a failing runner self-test**

Register a test that calls `HDK_TestCase::assert_same(1, 1)` before the class exists.

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/run.php`
Expected: non-zero exit because the test API is unavailable.

- [ ] **Step 3: Implement the minimal runner and WordPress stubs**

The runner records pass/fail counts, prints one line per test, and exits non-zero on failure. Bootstrap defines only the WordPress functions/classes needed by loaded units.

- [ ] **Step 4: Expose the complete check command**

Set `npm test` to run `php tests/run.php`, PHP lint for custom theme/plugin PHP files, and `node --check` for `main.js`.

- [ ] **Step 5: Run tests**

Run: `npm test`
Expected: all runner and syntax checks pass.

### Task 2: Atomic Purchase Service

**Files:**
- Create: `wp-content/plugins/hdk-core/includes/class-purchase-service.php`
- Create: `tests/PurchaseServiceTest.php`
- Modify: `wp-content/plugins/hdk-core/hdk-core.php`
- Modify: `wp-content/plugins/hdk-core/includes/class-rest-api.php:360-489`

**Interfaces:**
- Produces: `HDK_Purchase_Service::purchase_chapter(int $user_id, object $story, int $chapter_number, int $price): array|WP_Error`
- Produces: `HDK_Purchase_Service::purchase_full(int $user_id, object $story, int $price): array|WP_Error`

- [ ] **Step 1: Test failed debit, failed insert, duplicate request, committed balance, and full-purchase rollback**

Use a stateful fake `wpdb` and assert `ROLLBACK` restores credits/purchases while duplicate success leaves credits unchanged.

- [ ] **Step 2: Run tests to verify RED**

Run: `php tests/run.php PurchaseServiceTest`
Expected: failures because `HDK_Purchase_Service` does not exist.

- [ ] **Step 3: Implement transaction orchestration**

Begin transaction, short-circuit an existing purchase, conditionally debit with `rows_affected === 1`, insert the purchase and credit transaction, query committed balance, then commit. Roll back and return stable `WP_Error` codes on every failed required write.

- [ ] **Step 4: Delegate current REST callbacks to the service**

Keep story/price validation and post-commit notifications in `HDK_REST_API`; return service errors unchanged.

- [ ] **Step 5: Run focused and full tests**

Run: `php tests/run.php PurchaseServiceTest && npm test`
Expected: all checks pass.

### Task 3: Scheduled Chapter Publication

**Files:**
- Create: `tests/ScheduledPublicationTest.php`
- Modify: `wp-content/plugins/hdk-core/includes/class-cache.php:57-89`

**Interfaces:**
- Preserves: `HDK_Cache::publish_scheduled(): void`

- [ ] **Step 1: Test due rows are selected before update and processed once**

Assert the fake database observes `SELECT` before `UPDATE`, chapter totals are recalculated, cache invalidation runs, and each newly published chapter notifies once.

- [ ] **Step 2: Run test to verify RED**

Run: `php tests/run.php ScheduledPublicationTest`
Expected: failure because the current update precedes selection.

- [ ] **Step 3: Implement select-before-update processing**

Select due chapter identifiers/story metadata, update only those IDs still scheduled, then recalculate each affected story and trigger notification/cache work only for successfully transitioned rows.

- [ ] **Step 4: Run focused and full tests**

Run: `php tests/run.php ScheduledPublicationTest && npm test`
Expected: all checks pass.

### Task 4: Admin Moderation Nonces

**Files:**
- Create: `tests/AdminNonceTest.php`
- Modify: `wp-content/plugins/hdk-core/includes/class-admin.php:204-225`

**Interfaces:**
- Consumes existing nonce actions: `hdk_comment_<id>` and `hdk_report_<id>`.

- [ ] **Step 1: Test both handlers invoke the matching nonce check before mutation**

Stub WordPress capability, nonce, mutation, and redirect functions; assert missing/invalid verification prevents the mutation callback.

- [ ] **Step 2: Run test to verify RED**

Run: `php tests/run.php AdminNonceTest`
Expected: failure because the handlers currently omit `check_admin_referer`.

- [ ] **Step 3: Add action-specific checks**

Call `check_admin_referer('hdk_comment_' . $cid)` or `check_admin_referer('hdk_report_' . $rid)` immediately after capability and ID validation.

- [ ] **Step 4: Run focused and full tests**

Run: `php tests/run.php AdminNonceTest && npm test`
Expected: all checks pass.

### Task 5: Trusted Client IP and Comment Policy

**Files:**
- Create: `wp-content/plugins/hdk-core/includes/class-comment-policy.php`
- Create: `tests/ProtectionTest.php`
- Create: `tests/CommentPolicyTest.php`
- Modify: `wp-content/plugins/hdk-core/hdk-core.php`
- Modify: `wp-content/plugins/hdk-core/includes/class-protection.php:110-123`
- Modify: `wp-content/plugins/hdk-core/includes/class-rest-api.php:278-320`

**Interfaces:**
- Produces: `HDK_Protection::resolve_ip(array $server, array $trusted_proxies): string`
- Produces: `HDK_Comment_Policy::validate(int $user_id, int $story_id, int $chapter_number, string $content, int $parent_id): true|WP_Error`

- [ ] **Step 1: Test untrusted spoofing and trusted proxy chains**

Assert untrusted peers always resolve to `REMOTE_ADDR`, trusted proxies accept the first valid forwarded client, and malformed chains fall back safely.

- [ ] **Step 2: Run protection test to verify RED**

Run: `php tests/run.php ProtectionTest`
Expected: failure because `resolve_ip` is unavailable.

- [ ] **Step 3: Implement trusted-proxy resolution**

Read trusted proxy IPs through `apply_filters('hdk_trusted_proxy_ips', [])`; do not trust forwarded headers otherwise.

- [ ] **Step 4: Test comment content, throttling, parent scope, and moderation result**

Assert empty/over-2,000-character content, sixth comment within five minutes, and cross-story/chapter parents return stable errors. Assert valid input proceeds with `wp_allow_comment` moderation.

- [ ] **Step 5: Run comment test to verify RED**

Run: `php tests/run.php CommentPolicyTest`
Expected: failure because policy is unavailable.

- [ ] **Step 6: Implement and integrate comment policy**

Validate before `wp_insert_comment`, increment the user transient only after successful insertion, and set `comment_approved` from WordPress moderation instead of hard-coding `1`.

- [ ] **Step 7: Run focused and full tests**

Run: `php tests/run.php ProtectionTest CommentPolicyTest && npm test`
Expected: all checks pass.

### Task 6: Verification, Documentation, and Main Delivery

**Files:**
- Modify: `docs/review/website-smoke-checks.md`
- Modify: this plan's checkboxes as tasks complete.

- [ ] **Step 1: Run the complete verification gate**

Run: `npm test && git diff --check`
Expected: zero failures and clean diff validation.

- [ ] **Step 2: Run local smoke checks**

Start MySQL and `bash start-local.sh`; verify `/`, listing, ranking, sitemap, 404, login, and one story route. If local service access is blocked, record the exact blocker without claiming smoke success.

- [ ] **Step 3: Review the final diff against the approved design**

Confirm every Phase 1 requirement maps to a passing test or explicitly reported environment blocker; ensure Phase 2/3 work was not introduced.

- [ ] **Step 4: Commit implementation**

Run: `git add ... && git commit -m "fix: harden core platform workflows"`
Expected: one implementation commit after the design/plan commits.

- [ ] **Step 5: Merge and push main**

Merge `codex/platform-upgrades` into `main`, rerun `npm test`, and push `main` to `origin` as explicitly authorized by the user.
