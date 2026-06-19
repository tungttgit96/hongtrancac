# Security Review Plan - 2026-06-15

## Scope

Reviewed the custom WordPress theme `wp-content/themes/hongtrancac` and plugin `wp-content/plugins/hdk-core`, focused on:

- Login and Gmail registration flow
- REST API permissions and nonce checks
- Credit purchase and chapter unlock logic
- Admin CMS actions
- Public anti-abuse controls and security headers

## Findings

### P1: Login/register redirect can become open redirect

Files:
- `wp-content/themes/hongtrancac/page-dang-nhap.php`
- `wp-content/themes/hongtrancac/page-dang-ky.php`
- `wp-content/themes/hongtrancac/inc/template-functions.php`

Current code accepts `redirect_to` from GET/POST, sanitizes it, then calls `wp_redirect($redirect_to)`. Sanitization does not guarantee same-site redirects.

Fix plan:
- Add `hdk_safe_redirect_target($url, $fallback)` helper.
- Use `wp_validate_redirect()` and `wp_safe_redirect()` in login/register/account redirects.
- Keep external URLs out of hidden `redirect_to` values.

### P1: Purchase flow is not atomic

Files:
- `wp-content/plugins/hdk-core/includes/class-rest-api.php`
- `wp-content/plugins/hdk-core/includes/class-schema.php`

The purchase endpoints check balance, run a conditional `UPDATE credits = credits - price WHERE credits >= price`, then insert purchase/log notification without checking whether the update actually affected a row. A race or DB failure can create a purchase without a successful debit.

Fix plan:
- Wrap purchase in transaction where available.
- Check `$wpdb->rows_affected === 1` after credit deduction.
- Insert purchase with duplicate handling before/inside the same transaction.
- If insert fails due duplicate key, roll back or refund/return already purchased.
- Return actual remaining balance from DB after commit.

### P1: Registration email sending has no rate limit

File:
- `wp-content/themes/hongtrancac/page-dang-ky.php`

Anyone can repeatedly request verification emails to Gmail addresses. This can be used for email bombing and resource abuse.

Fix plan:
- Add per-IP and per-email transients for send-code requests.
- Recommended limits: 3 sends per email per 15 minutes; 10 sends per IP per hour.
- Add cooldown message in UI.
- Do not reveal whether existing email/username belongs to an account in the send-code response.

### P2: Admin comment/report GET actions lack nonce checks

File:
- `wp-content/plugins/hdk-core/includes/class-admin.php`

Comment moderation and report resolution use GET parameters and capability checks, but no nonce validation. A logged-in admin/editor could be tricked into clicking a crafted URL.

Fix plan:
- Wrap action links with `wp_nonce_url()`.
- Validate with `check_admin_referer()` for:
  - comment approve/unapprove/trash/spam
  - report resolve
- Keep capability checks as-is.

### P2: Rate limiting trusts spoofable forwarded headers

File:
- `wp-content/plugins/hdk-core/includes/class-protection.php`

`get_ip()` trusts `HTTP_X_FORWARDED_FOR` and similar headers directly. If the site is not behind a trusted proxy that strips these headers, clients can rotate fake IP values.

Fix plan:
- Use `REMOTE_ADDR` by default.
- Only honor `X-Forwarded-For` when `REMOTE_ADDR` is a configured trusted proxy.
- Add config constant/filter for trusted proxy IPs.

### P2: Public comment endpoint auto-approves all comments

File:
- `wp-content/plugins/hdk-core/includes/class-rest-api.php`

Comments are sanitized but automatically approved, and the endpoint has no rate limit beyond the generic REST limiter.

Fix plan:
- Add per-user comment rate limit.
- Optionally default to moderation for new/low-trust users.
- Limit comment length.

### P3: Registration reveals existing email/username

File:
- `wp-content/themes/hongtrancac/page-dang-ky.php`

The registration form returns distinct messages for existing email and existing username.

Fix plan:
- Use a generic message: "Không thể đăng ký với thông tin này."
- Log detailed reason server-side if needed.

### P3: Security headers can be strengthened

File:
- `wp-content/plugins/hdk-core/includes/class-protection.php`

Current headers include `nosniff`, `SAMEORIGIN`, and referrer policy. Missing hardening headers for production.

Fix plan:
- Add `Permissions-Policy`.
- Add HSTS only when the site is confirmed HTTPS-only.
- Evaluate CSP after inline scripts are reduced or nonce-based.

## Recommended Execution Order

1. Fix safe redirects in login/register.
2. Make purchase chapter/full transactional and verify debit/insert success.
3. Add registration send-code rate limits.
4. Add admin nonces for comment/report GET actions.
5. Harden IP rate limiting with trusted proxy handling.
6. Add comment rate limits/moderation rules.
7. Strengthen headers after confirming deployment constraints.

## Verification Checklist

- External `redirect_to=https://evil.example` redirects to `/tai-khoan` or home.
- Valid internal `redirect_to=/mat-biec/?chuong=1` still works.
- Simulated double purchase cannot create an unlock without a debit.
- Repeated registration send-code requests hit cooldown.
- Admin comment/report action URLs fail without nonce.
- REST/chapter rate limit cannot be bypassed by spoofing `X-Forwarded-For`.
