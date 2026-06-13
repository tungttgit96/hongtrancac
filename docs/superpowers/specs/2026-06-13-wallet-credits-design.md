# Wallet & Credits System Design

**Date:** 2026-06-13
**Status:** Approved
**Scope:** Hồng Trần Các - HDK Core Plugin + Theme

---

## Overview

Build a complete credit wallet system including transaction history logging, admin credit management, configurable credit packages, daily login reward, and user-facing wallet tab. Currently `hdk_user_credits` stores balances but has no transaction log, no admin management, no earning mechanism, and no purchase history display beyond the basic paywall flow.

---

## Architecture

### Approach: Transaction Table + Admin CRUD + Account Tab

Add `hdk_credit_transactions` table as append-only audit log. Add `hdk_credit_packages` for configurable credit bundles. Modify existing purchase endpoints to log transactions. Add 3 admin pages and 1 new account tab.

---

## Database Changes

### New Table: `hdk_credit_transactions`

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED PK AUTO_INCREMENT | |
| user_id | BIGINT UNSIGNED NOT NULL | |
| type | ENUM('earn','spend','daily','admin_add','admin_deduct','refund') | |
| credits | INT NOT NULL | Positive=earn, negative=spend |
| balance_after | INT UNSIGNED NOT NULL | Balance after transaction |
| source_type | VARCHAR(50) | 'package','daily_login','chapter_purchase','full_purchase','admin' |
| source_id | BIGINT UNSIGNED NULL | Reference ID (package, chapter, etc.) |
| note | VARCHAR(500) | Description |
| status | ENUM('completed','pending','failed') DEFAULT 'completed' | |
| created_at | DATETIME DEFAULT CURRENT_TIMESTAMP | |

Indexes: `INDEX idx_user_created (user_id, created_at)`, `INDEX idx_type (type)`

### New Table: `hdk_credit_packages`

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED PK AUTO_INCREMENT | |
| name | VARCHAR(255) NOT NULL | "Gói 100 hạt" |
| credits | INT UNSIGNED NOT NULL | |
| price_vnd | INT UNSIGNED NOT NULL | |
| bonus_credits | INT UNSIGNED DEFAULT 0 | |
| is_active | TINYINT(1) DEFAULT 1 | |
| sort_order | INT DEFAULT 0 | |
| created_at / updated_at | DATETIME | |

### Modified Table: `hdk_user_credits`

Add column: `last_daily_at DATETIME NULL` (tracks last daily login claim, for 1/day limit)

---

## Features

### 1. Wallet Tab on Account Page (`/tai-khoan?tab=wallet`)

**Profile summary:**
- Current balance (credits)
- Total earned (sum of positive transactions)
- Total spent (sum of negative transactions)
- "Nạp hạt" button → opens purchase modal
- "Điểm danh" button → claims daily reward

**Transaction history:**
- List of transactions sorted by `created_at DESC`
- Each row: type icon (earn/spend/daily), description, amount (+/-), status badge
- Paginated (20 per page)
- Empty state: "Chưa có giao dịch nào"

**Purchase modal:**
- Lists active credit packages from `hdk_credit_packages`
- Each card: name, credits, bonus (if any), price VNĐ
- Click → redirect to Sepay payment (future) / currently shows bank transfer info + admin manual confirm

**Daily login reward:**
- REST endpoint: `POST /hdk/v1/daily-claim`
- Checks `last_daily_at` — only 1 claim per calendar day
- Credits amount from option `hdk_daily_credits` (default 10)
- Logs transaction type `daily`

### 2. Admin: Credit Management (`hdk-credits`)

- Table: username, balance, total earned, total spent
- Search by username
- Action: adjust credits (+/-) with note
- Logs transaction type `admin_add` / `admin_deduct`
- Capability: `manage_options`

### 3. Admin: Credit Packages (`hdk-packages`)

- CRUD table: name, credits, bonus, price (VNĐ), status (active/inactive)
- Add/edit form
- Toggle active/inactive
- Delete (only if no transactions reference it)
- Capability: `manage_options`

### 4. Admin: Transaction History (`hdk-transactions`)

- Table: user, type, credits (+/-), note, time
- Filter by: transaction type, user search
- Paginated (50 per page)
- Read-only
- Capability: `manage_options`

### 5. Purchase Transaction Logging

Modify existing purchase endpoints (`purchase_chapter`, `purchase_full_story`) to insert into `hdk_credit_transactions` with:
- type: `spend`
- source_type: `chapter_purchase` / `full_purchase`
- source_id: chapter ID (for chapter) or 0 (for full)
- note: "Mua chương N - Story Title" or "Mua full - Story Title"

---

## Code Changes

### Files to Create

| File | Purpose |
|------|---------|
| None (no new files) | |

### Files to Modify

| File | What changes |
|------|-------------|
| `plugins/hdk-core/includes/class-schema.php` | Add 2 new tables + 1 new column |
| `plugins/hdk-core/includes/class-db.php` | New query methods for transactions, packages, daily claim |
| `plugins/hdk-core/includes/class-rest-api.php` | Log transactions on purchase, add daily-claim endpoint |
| `plugins/hdk-core/includes/class-admin.php` | 3 new admin pages + form handlers |
| `themes/hatdaukhaai/page-tai-khoan.php` | Add wallet tab |
| `themes/hatdaukhaai/assets/js/main.js` | Purchase modal + daily claim + wallet tab UI |
| `themes/hatdaukhaai/assets/css/main.css` | Wallet tab + modal styles |

### New HDK_DB Methods

```
log_credit_transaction($user_id, $type, $credits, $source_type, $source_id, $note, $status)
get_credit_transactions($user_id, $page, $per_page)
get_credit_packages($active_only)
create_credit_package($data)
update_credit_package($id, $data)
delete_credit_package($id)
claim_daily_credits($user_id)
get_user_credit_stats($user_id)  // balance, total_earned, total_spent
get_all_user_credits($search, $page)  // admin user list with balances
get_all_transactions($filters, $page)  // admin transaction log
```

### New REST API Endpoints

| Method | Route | Auth | Purpose |
|--------|-------|------|---------|
| `POST` | `/hdk/v1/daily-claim` | Yes | Claim daily login credits |
| `GET` | `/hdk/v1/me/transactions?page=` | Yes | User's transaction history |
| `GET` | `/hdk/v1/packages` | No | List active credit packages |

---

## Error Handling

- **Insufficient credits:** Return 402 with balance info (existing)
- **Already claimed today:** Return 409 with next available time
- **Invalid package:** Return 404
- **Admin without permission:** WP handles via capability check
- **Race condition on credit deduct:** Use SELECT...FOR UPDATE or atomic UPDATE WHERE credits >= amount

---

## Testing

- Daily claim works once per day
- Purchase logs transaction correctly
- Admin can add/deduct credits with proper logging
- Admin can CRUD packages
- Wallet tab shows correct balance, history
- Transaction log shows all types with correct +/- amounts
- Purchase modal lists active packages
