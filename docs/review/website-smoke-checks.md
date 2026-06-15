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
find wp-content/themes/hongtrancac wp-content/plugins/hdk-core/includes -name '*.php' -print0 | xargs -0 -n1 php -l
node --check wp-content/themes/hongtrancac/assets/js/main.js
```

Expected: no syntax errors.
