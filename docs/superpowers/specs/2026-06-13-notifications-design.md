# Notifications Design Spec

**Date:** 2026-06-13
**Status:** Approved

## Overview

Implement notification system using existing `hdk_notifications` table. Create notifications when: chapter published for favorited stories, comment reply received, purchase completed. Display via header bell badge + account tab.

## Database

Use existing `hdk_notifications` table (columns: id, user_id, type, title, message, link, is_read, created_at). No schema changes needed.

## API

| Route | Method | Auth | Purpose |
|-------|--------|------|---------|
| `/hdk/v1/notifications?page=` | GET | Yes | Paginated notification list |
| `/hdk/v1/notifications/read` | POST | Yes | Mark all as read (or by id) |
| `/hdk/v1/notifications/unread-count` | GET | Yes | Unread count for bell badge |

## Integration Points

| Event | File:Lines | Trigger |
|-------|-----------|---------|
| Chapter published (admin save) | `class-admin.php::save_chapter` | When status='published', notify all favoriting users |
| Chapter published (cron) | `class-cache.php::publish_scheduled` | After bulk UPDATE, loop stories and notify |
| Comment reply | `class-rest-api.php::add_comment` | When `parent_id > 0`, notify parent comment author |
| Purchase success | `class-rest-api.php::purchase_*` | After purchase, notify buyer |

## Frontend

- **Header bell**: 🔔 icon + unread count badge (red circle). Poll API every 30s
- **Account tab "🔔 Thông báo"**: List notifications, click → link to target, mark all read button

## Code Changes

| File | Action | What |
|------|--------|------|
| `plugins/hdk-core/includes/class-db.php` | Modify | 3 notification methods |
| `plugins/hdk-core/includes/class-rest-api.php` | Modify | 3 endpoints + comment reply notification + purchase notification |
| `plugins/hdk-core/includes/class-admin.php` | Modify | Notify on chapter publish |
| `plugins/hdk-core/includes/class-cache.php` | Modify | Notify on cron chapter publish |
| `themes/hatdaukhaai/header.php` | Modify | Bell icon + badge |
| `themes/hatdaukhaai/page-tai-khoan.php` | Modify | Notification tab |
| `themes/hatdaukhaai/assets/css/main.css` | Modify | Bell badge + notification list styles |
| `themes/hatdaukhaai/assets/js/main.js` | Modify | Bell polling + mark read |
