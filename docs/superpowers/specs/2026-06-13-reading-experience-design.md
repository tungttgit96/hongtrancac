# Reading Experience Design

**Date:** 2026-06-13
**Status:** Approved
**Scope:** Hồng Trần Các - Theme + HDK Core Plugin

---

## Overview

Enhance the chapter reader with customizable reading settings (font size, font family, line height, theme, width), a floating chapter list panel, and improved navigation. Preferences are saved both locally (localStorage for guests) and server-side (DB for logged-in users). 

Current reader has basic prev/next buttons, keyboard arrows, and scroll tracking. Missing all user-facing reading customization.

---

## Architecture

### Approach: CSS Variables + JS Settings Bar + localStorage/DB Persistence

All reading settings applied via CSS custom properties on `#chapter-content`. A settings toolbar rendered at top of reader. Preferences saved to localStorage for all users, and additionally to DB for logged-in users via REST API. Floating TOC drawer fetches chapter list via REST endpoint.

---

## Features

### 1. Reader Settings Bar

Horizontal toolbar between breadcrumb and reading content:

| Control | Type | Values | Default |
|---------|------|--------|---------|
| A⁻ / A⁺ | Buttons (±2px) | 16-28px | 20px |
| Font | Dropdown | Be Vietnam Pro, Georgia, Arial, Times New Roman | Be Vietnam Pro |
| Line height | Dropdown | 1.5 / 1.8 / 2.0 / 2.5 | 2.0 |
| Theme | Toggle buttons | ☀️ Light / 🌙 Dark / 📜 Sepia | Light |
| Width | Toggle | Narrow (700px) / Wide (full) | Wide |

### 2. Preferences Persistence

- **Guest:** `localStorage('hdk-reader-prefs')` JSON object
- **Logged in:** DB table `hdk_user_reader_prefs` + PATCH `/hdk/v1/reader-prefs`
- On page load: check server-side first (if logged in), fallback to localStorage, fallback to defaults
- On change: apply immediately + save with 500ms debounce

### 3. Floating TOC Drawer

- Floating button "📋 Mục lục" at fixed position right side of screen
- Click opens drawer sliding from right (300ms transition)
- Drawer content: chapter list fetched via `GET /hdk/v1/chapters/{story_id}`
- Each row: chapter number + title + lock status (🔓/🔒/✅)
- Current chapter highlighted + auto-scrolled into view
- Click outside or ✕ button to close

### 4. Navigation Improvements

- Replace "Mục lục" link in nav bars with button that opens floating TOC (no page navigation)
- Add chapter progress indicator: "Chương 42 / 200" in top nav
- Keep all existing prev/next buttons + keyboard arrows

### 5. Reader Themes

Applied via CSS classes on the content area:
- `reader-theme-light`: Uses existing CSS variables (default)
- `reader-theme-dark`: `background: #1a1a1a; color: #d4d4d4`
- `reader-theme-sepia`: `background: #F4ECD8; color: #5B4636`

Theme setting overrides the global dark/light toggle for the reader area only.

---

## Database Changes

### New Table: `hdk_user_reader_prefs`

| Column | Type | Default |
|--------|------|---------|
| user_id | BIGINT UNSIGNED PK | |
| font_size | INT | 20 |
| font_family | VARCHAR(100) | 'Be Vietnam Pro' |
| line_height | DECIMAL(3,1) | 2.0 |
| theme | ENUM('light','dark','sepia') | 'light' |
| reading_width | ENUM('narrow','wide') | 'wide' |
| updated_at | DATETIME | CURRENT_TIMESTAMP |

---

## API Changes

| Method | Route | Auth | Purpose |
|--------|-------|------|---------|
| `GET` | `/hdk/v1/chapters/{story_id}` | No | List all chapters for TOC |
| `PATCH` | `/hdk/v1/reader-prefs` | Yes | Save reader preferences |

---

## Code Changes

### Files to Modify

| File | What changes |
|------|-------------|
| `plugins/hdk-core/includes/class-schema.php` | Add `hdk_user_reader_prefs` table |
| `plugins/hdk-core/includes/class-db.php` | `get_chapters_toc()`, `get_reader_prefs()`, `save_reader_prefs()` |
| `plugins/hdk-core/includes/class-rest-api.php` | 2 new endpoints |
| `themes/hatdaukhaai/templates/chapter-reader.php` | Settings bar + TOC drawer + improved nav |
| `themes/hatdaukhaai/assets/css/main.css` | Reader settings, TOC drawer, theme styles |
| `themes/hatdaukhaai/assets/js/main.js` | Settings logic + TOC toggle + prefs persistence |

---

## Error Handling

- **Settings out of range:** Clamp font size 16-28, line height to valid options
- **Failed prefs save:** Silently fallback to localStorage, no error shown to user
- **TOC load failed:** Show "Không thể tải danh sách chương" in drawer

---

## Testing

- Settings bar renders and applies CSS variables on change
- Preferences persist across page reloads (localStorage + DB)
- Logged-in prefs sync across devices
- TOC drawer opens/closes, scrolls to current chapter
- Reader themes apply correctly without affecting global theme
- Narrow/wide width toggle works
- Mobile responsive (settings bar wraps, TOC full-width on mobile)
