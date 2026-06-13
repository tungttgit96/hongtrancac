# Comment Moderation & Reports Design

**Date:** 2026-06-13 | **Status:** Approved

## Overview

Add comment moderation (approve/delete) and chapter error reporting system.

## Features

### Admin Comment Moderation
- Page `hdk-comments` under Hồng Trần Các menu
- List all comments with story title, chapter, author, content, date
- Filter by status (approved/pending/spam)
- Actions: Approve, Unapprove, Trash, Spam

### Chapter Error Reports
- "Báo lỗi" button on chapter reader → modal with type select + note
- New table `hdk_reports`
- Admin page `hdk-reports` - queue with filter by type/status
- Actions: Mark resolved, delete

## DB

### New Table: `hdk_chapter_reports`
| Column | Type |
|--------|------|
| id | BIGINT UNSIGNED PK |
| user_id | BIGINT UNSIGNED |
| story_id | BIGINT UNSIGNED |
| chapter_number | INT |
| report_type | ENUM('typo','wrong_content','display_error','other') |
| note | TEXT |
| status | ENUM('pending','resolved') DEFAULT 'pending' |
| created_at | DATETIME |

## Code Changes

| File | What |
|------|------|
| `class-schema.php` | Add table |
| `class-db.php` | Report + comment query methods |
| `class-rest-api.php` | POST /reports endpoint |
| `class-admin.php` | 2 admin pages + handlers |
| `templates/chapter-reader.php` | Report button + modal |
| `main.js` | Report modal JS |
| `main.css` | Report styles |
