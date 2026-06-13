# HatDauKhaAi MVP Core Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:subagent-driven-development` or `superpowers:executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Xây lại MVP core của Hạt Đậu Khả Ái bằng PHP 8.3+, Laravel 13 và MySQL 8.x, bám theo sitemap và DesignMD đã kiểm tra.  
**Architecture:** Server-rendered Laravel Blade + Alpine.js, MySQL schema chuẩn hóa, cache cho danh mục/home/ranking, admin CMS nội bộ. Public URL giữ tương thích với sitemap hiện tại.  
**Tech Stack:** PHP 8.3+, Laravel 13, MySQL 8.x/InnoDB/utf8mb4, Blade, Vite, Alpine.js, Pest/PHPUnit.

---

## Summary

Nguồn đã kiểm tra: [sitemap.xml](https://hatdaukhaai.com/sitemap.xml), [DesignMD](https://designmd.me/s/hatdaukhaai-79nch), [trang chủ](https://hatdaukhaai.com/), [danh sách truyện](https://hatdaukhaai.com/danh-sach-truyen), [bảng xếp hạng](https://hatdaukhaai.com/bang-xep-hang), [trang truyện mẫu](https://hatdaukhaai.com/qua-ngay), [tin tức mẫu](https://hatdaukhaai.com/tin-tuc/hat-dau-kha-ai-chinh-thuc-ra-mat), [Laravel support policy](https://laravel.com/docs/master/releases).

Sitemap hiện có khoảng 56k URL: static, thể loại, tác giả, nhân vật, tin tức và hơn 42k truyện. MVP tập trung: public site, đọc truyện, tìm kiếm/lọc, đăng nhập, tủ truyện, lịch sử đọc, đánh giá, bình luận, admin CMS, SEO sitemap. Không làm v1: AI chat, VIP/coin, doanh thu tác giả, quảng bá trả phí, PWA reward, game, audio TTS.

## Key Interfaces

Public routes:
- `GET /`, `/danh-sach-truyen`, `/hoan-thanh`, `/truyen-free`
- `GET /the-loai`, `/the-loai/{slug}`, `/tac-gia/{slug}`, `/nhan-vat/{slug}`
- `GET /bang-xep-hang?metric=views|favorites|ratings&period=day|week|month|year|all&category={slug}`
- `GET /tin-tuc`, `/tin-tuc/{slug}`
- `GET /{storySlug}` for story detail and `GET /{storySlug}?chuong={number}` for chapter reader, matching the current site pattern.

Auth/admin routes:
- Reader: favorite/unfavorite, rating, comments, reading progress, bookshelf, history, notifications.
- Admin: CRUD stories, chapters, categories, authors, characters, news posts, users, comments, sitemap refresh.

Internal JSON endpoints:
- `GET /api/search?q=&type=all|stories|authors|categories`
- `POST /api/stories/{story}/favorite`
- `POST /api/stories/{story}/rating`
- `POST /api/comments`
- `PATCH /api/reading-progress`

## Implementation Changes

- [ ] Scaffold Laravel 13 app with Breeze-style Blade auth, roles `reader`, `contributor`, `moderator`, `admin`, CSRF/session auth, and MySQL migrations.
- [ ] Create core schema: `users`, `authors`, `categories`, `characters`, `stories`, `chapters`, `story_category`, `story_character`, `news_posts`, `comments`, `ratings`, `favorites`, `reading_progress`, `notifications`, `newsletter_subscriptions`, `daily_story_stats`.
- [ ] Add indexes: unique slugs, fulltext on story title/summary and author name, composite indexes for status/category/ranking queries, and foreign keys with controlled deletes.
- [ ] Build Blade design system from DesignMD tokens: `#54CFD6` primary, `#410000` hero accent, `#0F1419/#333333` text, `#E1EAEF` borders, Be Vietnam Pro, 12px cards, 24px pill buttons, 44px touch targets.
- [ ] Implement reusable components: layout, navbar, mobile drawer, story card, category badge, status badge, search modal, pagination, rating widget, comments, footer.
- [ ] Implement public pages from sitemap: home sections, story listing filters, category/author/character pages, ranking tabs, story detail, chapter reader, news listing/detail, contact/privacy.
- [ ] Implement admin CMS with image upload, chapter editor, publish status, moderation queue, and sitemap regeneration.
- [ ] Implement SEO: canonical URLs, Open Graph/Twitter tags, JSON-LD Organization/Book/Article where applicable, robots.txt, sitemap index split by static/categories/authors/characters/news/stories with max 40k story URLs per file.
- [ ] Add import command `php artisan hdk:import-content` reading authorized CSV/JSON exports only; do not crawl or copy third-party story content from the live site.

## Test Plan

- Feature tests for every public route, including fallback story slug route after static routes.
- Filter tests for `/danh-sach-truyen`, `/the-loai/{slug}`, `/bang-xep-hang`, and search API.
- Auth tests for favorite, rating, comment, reading progress, and access denial when logged out.
- Admin tests for CRUD story/chapter/category/news and comment moderation.
- SEO tests for canonical tags, sitemap split counts, image sitemap entries, and `lastmod`.
- Responsive checks at 375, 768, 1024, 1440px against DesignMD rules: no text overlap, 44px touch targets, 1/2/3/4-column card grids.
- Performance seed test with at least 50k stories, 13k authors, 200 categories, 700 characters; listing pages must paginate and avoid N+1 queries.

## Assumptions

- Scope selected: MVP core.
- Stack selected: Laravel.
- PHP target is PHP 8.3+ because Laravel 13 requires it; if hosting is locked to PHP 8.2, pin Laravel 12 instead as a separate constraint change.
- Existing live content must come from a legal export or owner-provided database dump, not uncontrolled scraping.
- Payment, VIP/coin, AI chat, PWA reward, TTS audio, author revenue, promotion marketplace, and game links are phase 2 backlog items.
# HatDauKhaAi WordPress MVP Core Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:subagent-driven-development` or `superpowers:executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Xây MVP core Hạt Đậu Khả Ái bằng WordPress, PHP 8 và MySQL, giữ URL/SEO theo sitemap hiện tại.  
**Architecture:** Custom WordPress theme cho giao diện + custom plugin `hdk-core` cho truyện/chương/ranking/search/sitemap/admin. Dữ liệu truyện dùng custom DB tables để chịu tải 42k+ truyện tốt hơn CPT thuần.  
**Tech Stack:** WordPress current stable, PHP 8.3+, MySQL 8.0+ hoặc MariaDB 10.6+, Nginx/Apache rewrite, custom theme, custom plugin, WP REST API, `wpdb`, Vite hoặc build CSS/JS nhẹ.

---

## Summary

Nguồn bám theo: [sitemap.xml](https://hatdaukhaai.com/sitemap.xml), [DesignMD](https://designmd.me/s/hatdaukhaai-79nch), các trang mẫu live, và [WordPress.org requirements](https://wordpress.org/about/requirements/).

Sitemap hiện có các nhóm chính: static, thể loại, tác giả, nhân vật, tin tức, và hơn 42k truyện. MVP gồm public site, đọc truyện, tìm kiếm/lọc, đăng nhập reader, tủ truyện, lịch sử đọc, đánh giá, bình luận, admin CMS và sitemap SEO. Không làm v1: AI chat, VIP/coin, doanh thu tác giả, quảng bá, PWA reward, game, TTS/audio.

## Key Interfaces

Public routes:
- `GET /`, `/danh-sach-truyen`, `/hoan-thanh`, `/truyen-free`
- `GET /the-loai`, `/the-loai/{slug}`, `/tac-gia/{slug}`, `/nhan-vat/{slug}`
- `GET /bang-xep-hang?metric=views|favorites|ratings&period=day|week|month|year|all&category={slug}`
- `GET /tin-tuc`, `/tin-tuc/{slug}` using WordPress post type `post` or custom `hdk_news`
- `GET /{storySlug}` story detail
- `GET /{storySlug}?chuong={number}` chapter reader, matching current live pattern

WordPress REST/AJAX endpoints:
- `GET /wp-json/hdk/v1/search?q=&type=all|stories|authors|categories`
- `POST /wp-json/hdk/v1/stories/{id}/favorite`
- `POST /wp-json/hdk/v1/stories/{id}/rating`
- `POST /wp-json/hdk/v1/comments`
- `PATCH /wp-json/hdk/v1/reading-progress`

## Implementation Changes

- [ ] Create theme `wp-content/themes/hatdaukhaai` with Blade-like PHP templates or native WP template parts: header, footer, home, listing, story detail, chapter reader, taxonomy pages, news pages.
- [ ] Create plugin `wp-content/plugins/hdk-core` containing activation migrations, rewrite rules, REST routes, admin pages, import/export, sitemap providers, cache invalidation, and scheduled ranking aggregation.
- [ ] Create custom DB tables: `hdk_stories`, `hdk_chapters`, `hdk_authors`, `hdk_categories`, `hdk_characters`, `hdk_story_categories`, `hdk_story_characters`, `hdk_ratings`, `hdk_favorites`, `hdk_reading_progress`, `hdk_daily_story_stats`.
- [ ] Use WordPress native tables for users, roles, sessions, comments where practical; map story comments with `comment_post_ID = 0` plus custom meta/story id, or use `hdk_comments` if moderation/query performance requires it.
- [ ] Add indexes: unique slugs, fulltext story title/summary, author name, chapter story/order, ranking period metrics, category-story joins, and reader progress lookup.
- [ ] Implement WP rewrite rules so static WordPress pages win first, then story slug fallback resolves `/{storySlug}` without breaking `/tin-tuc/*`, `/the-loai/*`, `/tac-gia/*`, `/nhan-vat/*`.
- [ ] Build DesignMD theme tokens: `#54CFD6` primary, `#410000` hero accent, `#0F1419/#333333` text, `#E1EAEF` border, Be Vietnam Pro, 12px cards, 24px pill buttons, 44px touch targets, responsive 1/2/3/4-column card grids.
- [ ] Implement admin CMS pages under WP Admin: stories, chapters, authors, categories, characters, imports, rankings, reports, settings.
- [ ] Implement SEO: canonical, OG/Twitter tags, JSON-LD Organization/Book/Article, custom sitemap index split into static/categories/authors/characters/news/stories with max 40k story URLs per file.
- [ ] Implement import command via WP-CLI: `wp hdk import --source=...`; only import from owner-provided CSV/JSON/SQL exports, not uncontrolled scraping.

## Test Plan

- Route tests for all public URLs, including story slug fallback and `?chuong=`.
- DB migration activation/deactivation tests for `hdk-core`.
- REST tests for search, favorite, rating, comment, and reading progress with nonce/capability checks.
- Admin capability tests for editor/moderator/admin permissions.
- Query performance tests seeded with 50k stories, 13k authors, 200 categories, 700 characters.
- Sitemap tests for split counts, `lastmod`, canonical URLs, and image entries.
- Responsive visual checks at 375, 768, 1024, 1440px against DesignMD rules.
- Security checks for `wpdb->prepare`, nonce validation, escaping output, upload MIME validation, rate limits on comments/search.

## Assumptions

- Chọn kiến trúc: custom theme + custom plugin.
- Chọn data model: custom DB tables cho truyện/chương/ranking.
- WordPress native post types chỉ dùng cho tin tức/trang tĩnh nếu không cần query đặc thù.
- Hosting hỗ trợ PHP 8.3+, MySQL 8.0+ hoặc MariaDB 10.6+ theo khuyến nghị WordPress.org.
- Các feature AI/VIP/PWA/game/audio/doanh thu tác giả nằm ở phase 2.