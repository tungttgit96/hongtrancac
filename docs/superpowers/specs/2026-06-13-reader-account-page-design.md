# Reader Account Page Design

**Date:** 2026-06-13
**Status:** Approved
**Scope:** Hồng Trần Các - WordPress Theme + HDK Core Plugin

---

## Overview

Build a dedicated reader account page (`/tai-khoan`) serving as the central hub for logged-in users. Currently, logged-in users only see a bare "Admin" link in the header with no way to access their favorites, reading progress, purchased stories, or reading history — despite all the underlying DB tables existing.

This is the foundation feature; wallet, notifications, and reading experience improvements build on top of it.

---

## Architecture

### Approach: WordPress Page Template with Server-Side Tabs

Single WordPress page `/tai-khoan` rendered by `page-tai-khoan.php`. Four tabs rendered server-side via `?tab=` query param. Reuses existing `hdk_get_story_card()` for story grids.

```
GET /tai-khoan?tab=favorites   → Tủ truyện (default)
GET /tai-khoan?tab=reading     → Đang đọc
GET /tai-khoan?tab=purchased   → Đã mua
GET /tai-khoan?tab=history     → Lịch sử đọc
```

### Why not SPA (REST API only) or multi-page?
- **SPA rejected:** Adds JS complexity, loses SEO, doesn't reuse existing server-side story card rendering
- **Multi-page rejected:** Requires complex rewrite rules, fragmented navigation
- **Page template chosen:** Minimal new code, reuses existing patterns (story cards, pagination, DB queries), works with existing template loader

---

## Features

### 1. User Dropdown Menu (header.php)

Replace the bare "Admin" / "Đăng nhập" in the header with a user-aware dropdown:

**Logged out:**
```
[Đăng nhập]
```

**Logged in:**
```
[👤 Username ▾]
 ├── 💎 X hạt (credit balance)
 ├── 📖 Tài khoản
 ├── ⚙ Admin (only if user has manage_options capability)
 └── 🚪 Đăng xuất
```

### 2. Profile Card (top of account page)

```
┌────────────────────────────────┐
│ 👤 [avatar]  Username          │
│ 💎 1,234 hạt                   │
│ 📚 Đã mua: 15 truyện           │
└────────────────────────────────┘
```

Data sources:
- `get_userdata()` → display_name, avatar
- `hdk_user_credits.credits` → current balance
- `COUNT(DISTINCT story_id)` from `hdk_purchased_chapters` → purchased story count (all purchases, not just full story unlocks)

### 3. Tab Navigation

4 tabs rendered as `<nav>` with links:

```
[Tủ truyện] [Đang đọc] [Đã mua] [Lịch sử đọc]
```

Active tab highlighted. Each link sets `?tab=<slug>`.

### 4. Tab: Tủ truyện (Favorites)

- Data: `HDK_DB::get_favorites($user_id, $page, 12)`
- JOIN `hdk_favorites` + `hdk_stories` + `hdk_authors`, ordered by `favorites.created_at DESC`
- Rendered with `hdk_get_story_card()` (standard card)
- Pagination if > 12 items
- Empty state: "Bạn chưa yêu thích truyện nào. [Khám phá truyện ngay!]"

### 5. Tab: Đang đọc (Reading Progress)

- Data: `HDK_DB::get_reading_stories($user_id)`
- JOIN `hdk_reading_progress` + `hdk_stories`, ordered by `progress.updated_at DESC`
- Each card shows badge: "Đọc tiếp chương N" + mini progress bar (scroll_percent)
- No pagination (typically < 20 ongoing reads)
- Empty state: "Bắt đầu đọc truyện đầu tiên của bạn!"

### 6. Tab: Đã mua (Purchased)

- Data: `HDK_DB::get_purchased_stories($user_id, $page, 12)`
- JOIN `hdk_purchased_chapters` + `hdk_stories`, grouped by story, ordered by purchase time DESC
- Card shows badge: "💎 Đã mua"
- Pagination if > 12 items
- Empty state: "Khám phá truyện hay để mua bằng hạt"

### 7. Tab: Lịch sử đọc (Reading History)

- Data: `HDK_DB::get_reading_history($user_id, $page, 20)`
- Tracked via a new DB insert each time reading progress updates (insert into `hdk_reading_history`)
- Compact row-style display: "Chương X - Story Title — HH:MM DD/MM/YYYY"
- Each row links to `/{storySlug}?chuong={N}`
- Pagination (20 per page)
- Empty state: "Lịch sử đọc sẽ xuất hiện ở đây"

---

## Database Changes

### New Table: `hdk_reading_history`

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED PK AUTO_INCREMENT | |
| user_id | BIGINT UNSIGNED NOT NULL | |
| story_id | BIGINT UNSIGNED NOT NULL | |
| chapter_number | INT UNSIGNED NOT NULL | |
| created_at | DATETIME DEFAULT CURRENT_TIMESTAMP | |

Purpose: Append-only log of every chapter read. Separate from `hdk_reading_progress` which stores only current position (last read).

Insert rule: Only log when user opens a new chapter (chapter_number changes from previous read). Do NOT log on scroll-based progress saves to avoid flooding the table.

### New Indexes

- `hdk_reading_history`: INDEX(user_id, created_at)
- `hdk_purchased_chapters`: INDEX(user_id, story_id, created_at) (already has UNIQUE)
- `hdk_favorites`: INDEX(user_id, created_at) (already has UNIQUE)

---

## Code Changes

### Files to Create

| File | Purpose |
|------|---------|
| `wp-content/themes/hatdaukhaai/page-tai-khoan.php` | Account page template |

### Files to Modify

| File | What changes |
|------|-------------|
| `wp-content/themes/hatdaukhaai/header.php` | Replace login/admin link with user dropdown |
| `wp-content/themes/hatdaukhaai/assets/css/main.css` | Account page styles, user dropdown styles |
| `wp-content/themes/hatdaukhaai/assets/js/main.js` | User dropdown toggle |
| `wp-content/themes/hatdaukhaai/inc/template-functions.php` | Add badge/progress variants to story cards |
| `wp-content/plugins/hdk-core/includes/class-db.php` | New query methods |
| `wp-content/plugins/hdk-core/includes/class-schema.php` | New `hdk_reading_history` table |
| `wp-content/plugins/hdk-core/includes/class-rest-api.php` | New GET endpoints for favorites/purchases |
| `wp-content/plugins/hdk-core/hdk-core.php` | Register activation hook for new table, create account page on activation |

### New HDK_DB Methods

```php
// Get user's favorited stories (paginated)
get_favorites($user_id, $page = 1, $per_page = 12)

// Get stories user is currently reading (has progress)
get_reading_stories($user_id)

// Get stories user has purchased (paginated)
get_purchased_stories($user_id, $page = 1, $per_page = 12)

// Get reading history log (paginated)
get_reading_history($user_id, $page = 1, $per_page = 20)

// Log a reading event (appends to history)
log_reading_history($user_id, $story_id, $chapter_number)
```

### New REST API Endpoints

| Method | Route | Auth | Purpose |
|--------|-------|------|---------|
| `GET` | `/hdk/v1/me/favorites?page=` | Yes | List user's favorites |
| `GET` | `/hdk/v1/me/purchases?page=` | Yes | List user's purchases |

### New WP-CLI Command

```
wp hdk create-account-page    # Create /tai-khoan page if not exists
```

---

## Error Handling & Edge Cases

- **Not logged in:** Redirect to `wp_login_url()` with redirect back to `/tai-khoan`
- **Invalid tab:** Default to `favorites`
- **Empty tabs:** Show appropriate empty state message with CTA
- **No credits record:** User hasn't earned/spent yet → show "0 hạt"
- **No avatar:** Show WordPress default gravatar
- **Pagination out of bounds:** Clamp to max pages

---

## Testing

- Page accessible when logged in → 200
- Redirect to login when logged out → 302
- Each tab renders correct data
- Empty states display correctly
- Pagination works for tabs with > 12 items
- User dropdown shows correct data in header
- Admin link only visible for admin users
- Reading history appends on each chapter view
- Mobile responsive at 375px, 768px, 1024px
