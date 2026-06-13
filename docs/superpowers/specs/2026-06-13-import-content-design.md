# Content Import Design

**Date:** 2026-06-13
**Status:** Approved
**Scope:** HDK Core Plugin (admin + CLI)

## Overview

Implement CSV/JSON import for stories, chapters, authors, and categories. Admin page with file upload, parse preview, error reporting, and confirm import. CLI command for direct import. Deduplication by slug.

## Format

**CSV columns:** type, title, slug, author, categories, summary, status, is_free, chapter_number, chapter_title, content

**JSON format:** Array of objects with same fields

**Type values:** story, chapter, author, category

## Features

### Admin Import Page

1. Upload CSV/JSON file
2. Parse and show preview table (first 20 rows)
3. Show errors per row (missing title, duplicate slug, chapter without story)
4. "Confirm Import" button → batch insert
5. Show results: X created, Y skipped, Z errors

### CLI Command

```
wp hdk import --source=file.csv
```

Same parse+import logic, output progress per row.

### Deduplication

- Authors/categories: match by slug, update if exists
- Stories: match by slug, skip if exists
- Chapters: match by story_id+chapter_number, skip if exists

## Code Changes

| File | What |
|------|------|
| `plugins/hdk-core/includes/class-admin.php` | import_page() + form handler |
| `plugins/hdk-core/includes/class-cli.php` | import() implementation |
