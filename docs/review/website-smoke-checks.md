# Website Smoke Checks

## Routes

| URL | Expected |
| --- | --- |
| `/` | 200, homepage title |
| `/danh-sach-truyen` | 200, listing filters visible |
| `/bang-xep-hang` | 200, ranking tabs visible |
| `/the-loai` | 200, category cards visible |
| `/mat-biec/` | 200, title contains `Mắt Biếc` |
| `/mat-biec/?chuong=1` | 200 for browser UA, chapter/paywall title contains story/chapter |
| `/khong-ton-tai-review` | 404 |
| `/sitemap.xml` | 200 XML |
| `/robots.txt` | 200 text, contains sitemap |

## User Flows

- Search `mat`, click result, lands on `/mat-biec`.
- Filter listing by completed + views, paginate, filters remain.
- Open ranking by ratings + month, paginate, filters remain.
- Logged-out locked chapter shows login/paywall.
- Logged-in purchase sends nonce and updates balance.
- Reader settings save and persist after reload.
- Mobile drawer opens/closes at 375px.

## Automated Syntax Checks

```bash
npm test
```

Expected: all PHP regression tests pass, followed by no PHP or JavaScript syntax errors.

The regression suite covers atomic purchases, scheduled publication, admin
moderation nonces, trusted proxy IP resolution, and comment policy behavior.

## Core Hardening Checks

- Retrying the same chapter purchase debits Linh Thạch only once.
- A failed purchase or audit-log insert leaves balance and unlocks unchanged.
- A full-story purchase cannot race with a chapter purchase for the same user.
- A due scheduled chapter publishes and notifies followers exactly once.
- Comment/report moderation actions fail without their action-specific nonce.
- `X-Forwarded-For` is ignored unless `REMOTE_ADDR` is configured through the
  `hdk_trusted_proxy_ips` filter.
- The sixth comment in five minutes returns HTTP 429.
- Replies to comments from another story or chapter return HTTP 400.
