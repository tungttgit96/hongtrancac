# Reader Account Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build reader account page (`/tai-khoan`) with 4 tabs (favorites, reading progress, purchased, history) and user dropdown menu in header.

**Architecture:** Server-rendered page template with `?tab=` query param tabs, reusing existing `hdk_get_story_card()`. New `HDK_DB` query methods + 2 REST API endpoints. New `hdk_reading_history` table for append-only read log.

**Tech Stack:** WordPress, PHP, MySQL, Alpine.js, existing HDK Core plugin + hatdaukhaai theme

---

## File Structure

| File | Action | Responsibility |
|------|--------|----------------|
| `plugins/hdk-core/includes/class-schema.php` | Modify | Add `hdk_reading_history` table |
| `plugins/hdk-core/includes/class-db.php` | Modify | 5 new query methods |
| `plugins/hdk-core/includes/class-rest-api.php` | Modify | Log reading history on progress update + 2 new GET endpoints |
| `plugins/hdk-core/includes/class-cli.php` | Modify | Add `wp hdk create-account-page` command |
| `plugins/hdk-core/includes/class-activator.php` | Modify | Create account page on plugin activation |
| `themes/hatdaukhaai/inc/template-functions.php` | Modify | Add `hdk_get_story_card_badge()` helper |
| `themes/hatdaukhaai/page-tai-khoan.php` | **Create** | Account page template |
| `themes/hatdaukhaai/header.php` | Modify | User dropdown menu |
| `themes/hatdaukhaai/assets/css/main.css` | Modify | Account page + dropdown styles |
| `themes/hatdaukhaai/assets/js/main.js` | Modify | Dropdown toggle |

---

### Task 1: Add `hdk_reading_history` table to schema

**Files:**
- Modify: `wp-content/plugins/hdk-core/includes/class-schema.php:216`

- [ ] **Step 1: Add table creation SQL**

Insert after the `hdk_purchased_chapters` table block (line 216, before `foreach ($sql as $query)`):

```php
        // Reading History (append-only log of completed chapter reads)
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hdk_reading_history (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            story_id BIGINT UNSIGNED NOT NULL,
            chapter_number INT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_history (user_id, created_at),
            INDEX idx_user_story (user_id, story_id)
        ) $charset;";
```

- [ ] **Step 2: Verify table exists**

Run:
```bash
"/Users/tungtt96/Library/Application Support/Herd/bin/php" -r "
define('WP_USE_THEMES', false);
require '/Users/tungtt96/Herd/hongtrancac/wp-load.php';
HDK_Schema::create_tables();
echo 'OK';
"
```
Expected: `OK`

- [ ] **Step 3: Commit**

```bash
git add wp-content/plugins/hdk-core/includes/class-schema.php
git commit -m "feat: add hdk_reading_history table"
```

---

### Task 2: Add `HDK_DB` query methods

**Files:**
- Modify: `wp-content/plugins/hdk-core/includes/class-db.php:341`

- [ ] **Step 1: Add 5 new static methods**

Insert before the closing `}` of the class (line 341):

```php
    public static function get_favorites($user_id, $page = 1, $per_page = 12) {
        global $wpdb;
        $fav_table = self::table('hdk_favorites');
        $story_table = self::table('hdk_stories');
        $offset = ($page - 1) * $per_page;

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $fav_table f JOIN $story_table s ON f.story_id = s.id WHERE f.user_id = %d",
            $user_id
        ));

        $stories = $wpdb->get_results($wpdb->prepare(
            "SELECT s.* FROM $fav_table f JOIN $story_table s ON f.story_id = s.id
             WHERE f.user_id = %d ORDER BY f.created_at DESC LIMIT %d OFFSET %d",
            $user_id, $per_page, $offset
        ));

        foreach ($stories as $story) {
            $story->author_name = self::get_author_name($story->author_id);
            $story->categories = self::get_story_categories($story->id);
            $story->chapter_count = (int)$story->total_chapters;
        }

        return ['stories' => $stories, 'total' => (int)$total, 'pages' => (int)ceil($total / $per_page)];
    }

    public static function get_reading_stories($user_id) {
        global $wpdb;
        $progress_table = self::table('hdk_reading_progress');
        $story_table = self::table('hdk_stories');

        $stories = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, p.chapter_number as current_chapter, p.scroll_percent
             FROM $progress_table p JOIN $story_table s ON p.story_id = s.id
             WHERE p.user_id = %d ORDER BY p.updated_at DESC",
            $user_id
        ));

        foreach ($stories as $story) {
            $story->author_name = self::get_author_name($story->author_id);
            $story->categories = self::get_story_categories($story->id);
            $story->chapter_count = (int)$story->total_chapters;
        }

        return $stories;
    }

    public static function get_purchased_stories($user_id, $page = 1, $per_page = 12) {
        global $wpdb;
        $purch_table = self::table('hdk_purchased_chapters');
        $story_table = self::table('hdk_stories');
        $offset = ($page - 1) * $per_page;

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.story_id) FROM $purch_table p JOIN $story_table s ON p.story_id = s.id WHERE p.user_id = %d",
            $user_id
        ));

        $stories = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, MAX(p.created_at) as purchased_at
             FROM $purch_table p JOIN $story_table s ON p.story_id = s.id
             WHERE p.user_id = %d GROUP BY p.story_id ORDER BY purchased_at DESC LIMIT %d OFFSET %d",
            $user_id, $per_page, $offset
        ));

        foreach ($stories as $story) {
            $story->author_name = self::get_author_name($story->author_id);
            $story->categories = self::get_story_categories($story->id);
            $story->chapter_count = (int)$story->total_chapters;
        }

        return ['stories' => $stories, 'total' => (int)$total, 'pages' => (int)ceil($total / $per_page)];
    }

    public static function get_reading_history($user_id, $page = 1, $per_page = 20) {
        global $wpdb;
        $hist_table = self::table('hdk_reading_history');
        $story_table = self::table('hdk_stories');
        $offset = ($page - 1) * $per_page;

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $hist_table WHERE user_id = %d", $user_id
        ));

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT h.*, s.title, s.slug FROM $hist_table h
             JOIN $story_table s ON h.story_id = s.id
             WHERE h.user_id = %d ORDER BY h.created_at DESC LIMIT %d OFFSET %d",
            $user_id, $per_page, $offset
        ));

        return ['rows' => $rows, 'total' => (int)$total, 'pages' => (int)ceil($total / $per_page)];
    }

    public static function log_reading_history($user_id, $story_id, $chapter_number) {
        global $wpdb;
        $table = self::table('hdk_reading_history');

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND story_id = %d AND chapter_number = %d LIMIT 1",
            $user_id, $story_id, $chapter_number
        ));

        if (!$exists) {
            $wpdb->insert($table, [
                'user_id' => $user_id,
                'story_id' => $story_id,
                'chapter_number' => $chapter_number,
                'created_at' => current_time('mysql'),
            ]);
        }
    }

    public static function get_user_purchased_count($user_id) {
        global $wpdb;
        return (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT story_id) FROM " . self::table('hdk_purchased_chapters') . " WHERE user_id = %d",
            $user_id
        ));
    }
```

- [ ] **Step 2: Commit**

```bash
git add wp-content/plugins/hdk-core/includes/class-db.php
git commit -m "feat: add DB query methods for reader account (favorites, reading, purchased, history)"
```

---

### Task 3: Update REST API - log reading history + new GET endpoints

**Files:**
- Modify: `wp-content/plugins/hdk-core/includes/class-rest-api.php`

- [ ] **Step 1: Add reading history logging to update_progress**

At line 194, after the `$wpdb->replace()` call in `update_progress()`, add:

```php
        // Log to reading history (deduplicated by user+story+chapter)
        HDK_DB::log_reading_history($user_id, $story_id, $chapter_number);
```

- [ ] **Step 2: Add GET /me/favorites endpoint**

Insert before the closing `}` of `init()` (line 55):

```php
        register_rest_route('hdk/v1', '/me/favorites', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_favorites'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);
```

- [ ] **Step 3: Add GET /me/purchases endpoint**

```php
        register_rest_route('hdk/v1', '/me/purchases', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_purchases'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);
```

- [ ] **Step 4: Add endpoint handler methods**

Insert before the closing `}` of the class (after `purchase_full_story` at line 308):

```php
    public static function get_favorites($request) {
        $user_id = get_current_user_id();
        $page = (int)($request->get_param('page') ?? 1);
        $result = HDK_DB::get_favorites($user_id, max(1, $page));
        return rest_ensure_response($result);
    }

    public static function get_purchases($request) {
        $user_id = get_current_user_id();
        $page = (int)($request->get_param('page') ?? 1);
        $result = HDK_DB::get_purchased_stories($user_id, max(1, $page));
        return rest_ensure_response($result);
    }
```

- [ ] **Step 5: Commit**

```bash
git add wp-content/plugins/hdk-core/includes/class-rest-api.php
git commit -m "feat: log reading history + REST endpoints for favorites and purchases"
```

---

### Task 4: Add `hdk_get_story_card_badge()` helper

**Files:**
- Modify: `wp-content/themes/hatdaukhaai/inc/template-functions.php:217`

- [ ] **Step 1: Add badge helper function**

Insert before closing `?>`:

```php
function hdk_get_story_card_badge($type, $data = null) {
    switch ($type) {
        case 'reading':
            $chap = (int)($data->current_chapter ?? 0);
            $pct = (int)($data->scroll_percent ?? 0);
            return sprintf(
                '<div class="story-badge story-badge-reading">Đọc tiếp chương %d<span class="badge-progress" style="width:%d%%"></span></div>',
                $chap, $pct
            );
        case 'purchased':
            return '<div class="story-badge story-badge-purchased">💎 Đã mua</div>';
        case 'history':
            $chap = (int)($data->chapter_number ?? 0);
            $time = mysql2date('H:i d/m/Y', $data->created_at ?? '');
            return '<div class="story-badge story-badge-history">Chương ' . $chap . ' — ' . $time . '</div>';
        default:
            return '';
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add wp-content/themes/hatdaukhaai/inc/template-functions.php
git commit -m "feat: add story card badge helper for account page"
```

---

### Task 5: Create account page template

**Files:**
- Create: `wp-content/themes/hatdaukhaai/page-tai-khoan.php`

- [ ] **Step 1: Create the template file**

```php
<?php
/**
 * Template: Tài khoản độc giả
 */

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(home_url('/tai-khoan')));
    exit;
}

$user_id = get_current_user_id();
$user = get_userdata($user_id);
$credits_table = HDK_DB::table('hdk_user_credits');

global $wpdb;
$credits = (int)$wpdb->get_var($wpdb->prepare("SELECT credits FROM $credits_table WHERE user_id = %d", $user_id));
if ($credits === null && $wpdb->last_error === '') {
    $wpdb->insert($credits_table, ['user_id' => $user_id, 'credits' => 0]);
    $credits = 0;
}

$purchased_count = HDK_DB::get_user_purchased_count($user_id);

$tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'favorites';
$valid_tabs = ['favorites', 'reading', 'purchased', 'history'];
if (!in_array($tab, $valid_tabs)) $tab = 'favorites';

$page = max(1, (int)($_GET['paged'] ?? 1));

get_header();
?>

<div class="container" style="padding:32px 0;">
    <nav class="breadcrumb" style="margin-bottom:16px;">
        <a href="<?php echo home_url('/'); ?>">Trang chủ</a> &rsaquo; Tài khoản
    </nav>

    <div class="account-profile" style="display:flex;align-items:center;gap:20px;padding:24px;background:var(--color-bg-secondary);border-radius:var(--radius-lg);margin-bottom:24px;flex-wrap:wrap;">
        <div class="account-avatar" style="width:64px;height:64px;border-radius:50%;background:var(--color-primary-light);display:flex;align-items:center;justify-content:center;font-size:28px;flex-shrink:0;">
            <?php echo get_avatar($user_id, 64, '', '', ['style' => 'border-radius:50%;width:64px;height:64px;']); ?>
        </div>
        <div style="flex:1;min-width:200px;">
            <h1 style="font-size:var(--font-size-xl);font-weight:700;margin:0 0 8px;"><?php echo esc_html($user->display_name); ?></h1>
            <div style="display:flex;gap:24px;flex-wrap:wrap;color:var(--color-text-muted);">
                <span>💎 <strong style="color:var(--color-primary);"><?php echo number_format($credits); ?></strong> hạt</span>
                <span>📚 Đã mua: <strong style="color:var(--color-text-primary);"><?php echo number_format($purchased_count); ?></strong> truyện</span>
            </div>
        </div>
    </div>

    <nav class="account-tabs" style="display:flex;gap:4px;margin-bottom:24px;border-bottom:2px solid var(--color-border);">
        <?php
        $tabs = [
            'favorites' => '📖 Tủ truyện',
            'reading'   => '📌 Đang đọc',
            'purchased' => '💎 Đã mua',
            'history'   => '🕐 Lịch sử đọc',
        ];
        foreach ($tabs as $key => $label) {
            $is_active = $tab === $key;
            $url = home_url('/tai-khoan?tab=' . $key);
            ?>
            <a href="<?php echo esc_url($url); ?>" class="account-tab <?php echo $is_active ? 'active' : ''; ?>"
               style="padding:12px 20px;text-decoration:none;font-weight:600;color:var(--color-text-<?php echo $is_active ? 'primary' : 'muted'; ?>);border-bottom:3px solid <?php echo $is_active ? 'var(--color-primary)' : 'transparent'; ?>;transition:all 0.2s;">
                <?php echo $label; ?>
            </a>
        <?php } ?>
    </nav>

    <?php
    switch ($tab) {
        case 'favorites':
            $data = HDK_DB::get_favorites($user_id, $page);
            $stories = $data['stories'];
            ?>
            <?php if (empty($stories)): ?>
                <div class="empty-state" style="text-align:center;padding:48px 0;">
                    <div style="font-size:48px;margin-bottom:16px;">📖</div>
                    <p style="color:var(--color-text-muted);margin-bottom:16px;">Bạn chưa yêu thích truyện nào.</p>
                    <a href="<?php echo home_url('/danh-sach-truyen'); ?>" class="btn btn-primary">Khám phá truyện ngay!</a>
                </div>
            <?php else: ?>
                <div class="grid grid-6">
                    <?php foreach ($stories as $story): ?>
                        <?php hdk_get_story_card($story); ?>
                    <?php endforeach; ?>
                </div>
                <?php hdk_get_pagination($data['pages'], $page); ?>
            <?php endif; ?>
            <?php break;

        case 'reading':
            $stories = HDK_DB::get_reading_stories($user_id);
            ?>
            <?php if (empty($stories)): ?>
                <div class="empty-state" style="text-align:center;padding:48px 0;">
                    <div style="font-size:48px;margin-bottom:16px;">📌</div>
                    <p style="color:var(--color-text-muted);margin-bottom:16px;">Bắt đầu đọc truyện đầu tiên của bạn!</p>
                    <a href="<?php echo home_url('/danh-sach-truyen'); ?>" class="btn btn-primary">Khám phá truyện</a>
                </div>
            <?php else: ?>
                <div class="grid grid-6">
                    <?php foreach ($stories as $story): ?>
                        <a href="<?php echo home_url('/' . ($story->slug ?? '') . '?chuong=' . (int)($story->current_chapter ?? 1)); ?>" class="card story-card">
                            <img src="<?php echo esc_url($story->cover_url ?? get_template_directory_uri() . '/assets/img/placeholder.svg'); ?>" alt="<?php echo esc_html($story->title); ?>" class="card-img" loading="lazy">
                            <div class="card-body">
                                <h3 class="card-title"><?php echo esc_html($story->title); ?></h3>
                                <div class="card-meta"><?php echo esc_html($story->author_name ?? ''); ?></div>
                                <div style="margin-top:8px;">
                                    <span class="badge badge-primary">Đọc tiếp chương <?php echo (int)($story->current_chapter ?? 0); ?></span>
                                </div>
                                <?php $pct = max(1, min(100, (int)($story->scroll_percent ?? 0))); ?>
                                <div class="progress-bar" style="height:4px;background:var(--color-border);border-radius:2px;margin-top:8px;overflow:hidden;">
                                    <div style="height:100%;width:<?php echo $pct; ?>%;background:var(--color-primary);border-radius:2px;"></div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php break;

        case 'purchased':
            $data = HDK_DB::get_purchased_stories($user_id, $page);
            $stories = $data['stories'];
            ?>
            <?php if (empty($stories)): ?>
                <div class="empty-state" style="text-align:center;padding:48px 0;">
                    <div style="font-size:48px;margin-bottom:16px;">💎</div>
                    <p style="color:var(--color-text-muted);margin-bottom:16px;">Khám phá truyện hay để mua bằng hạt</p>
                    <a href="<?php echo home_url('/danh-sach-truyen'); ?>" class="btn btn-primary">Khám phá truyện</a>
                </div>
            <?php else: ?>
                <div class="grid grid-6">
                    <?php foreach ($stories as $story): ?>
                        <a href="<?php echo home_url('/' . ($story->slug ?? '')); ?>" class="card story-card">
                            <img src="<?php echo esc_url($story->cover_url ?? get_template_directory_uri() . '/assets/img/placeholder.svg'); ?>" alt="<?php echo esc_html($story->title); ?>" class="card-img" loading="lazy">
                            <div class="card-body">
                                <h3 class="card-title"><?php echo esc_html($story->title); ?></h3>
                                <div class="card-meta"><?php echo esc_html($story->author_name ?? ''); ?></div>
                                <div style="margin-top:8px;">
                                    <span class="badge badge-warning">💎 Đã mua</span>
                                </div>
                                <div class="card-meta" style="margin-top:6px"><?php echo (int)($story->chapter_count ?? 0); ?> chương</div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php hdk_get_pagination($data['pages'], $page); ?>
            <?php endif; ?>
            <?php break;

        case 'history':
            $data = HDK_DB::get_reading_history($user_id, $page);
            $rows = $data['rows'];
            ?>
            <?php if (empty($rows)): ?>
                <div class="empty-state" style="text-align:center;padding:48px 0;">
                    <div style="font-size:48px;margin-bottom:16px;">🕐</div>
                    <p style="color:var(--color-text-muted);">Lịch sử đọc sẽ xuất hiện ở đây</p>
                </div>
            <?php else: ?>
                <div class="history-list" style="display:flex;flex-direction:column;gap:1px;background:var(--color-border);border-radius:var(--radius-md);overflow:hidden;">
                    <?php foreach ($rows as $row): ?>
                        <a href="<?php echo home_url('/' . ($row->slug ?? '') . '?chuong=' . (int)$row->chapter_number); ?>"
                           style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:var(--color-bg);text-decoration:none;color:var(--color-text-primary);gap:12px;flex-wrap:wrap;">
                            <div>
                                <strong><?php echo esc_html($row->title); ?></strong>
                                <span style="color:var(--color-text-muted);"> — Chương <?php echo (int)$row->chapter_number; ?></span>
                            </div>
                            <span style="color:var(--color-text-muted);font-size:var(--font-size-sm);white-space:nowrap;">
                                <?php echo mysql2date('H:i d/m/Y', $row->created_at); ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php hdk_get_pagination($data['pages'], $page); ?>
            <?php endif; ?>
            <?php break;
    }
    ?>
</div>

<?php get_footer(); ?>
```

- [ ] **Step 2: Commit**

```bash
git add wp-content/themes/hatdaukhaai/page-tai-khoan.php
git commit -m "feat: create reader account page template"
```

---

### Task 6: Update header with user dropdown menu

**Files:**
- Modify: `wp-content/themes/hatdaukhaai/header.php:37-41`

- [ ] **Step 1: Replace the login/admin link block**

Replace lines 37-41:

```php
            <?php if (is_user_logged_in()): ?>
                <a href="<?php echo admin_url(); ?>" class="btn btn-outline btn-sm">Admin</a>
            <?php else: ?>
                <a href="<?php echo wp_login_url(); ?>" class="btn btn-primary btn-sm">Đăng nhập</a>
            <?php endif; ?>
```

With:

```php
            <?php if (is_user_logged_in()): ?>
                <?php
                $current_user = wp_get_current_user();
                $credits_table = HDK_DB::table('hdk_user_credits');
                $credits = (int)$GLOBALS['wpdb']->get_var($GLOBALS['wpdb']->prepare("SELECT credits FROM $credits_table WHERE user_id = %d", get_current_user_id()));
                ?>
                <div class="user-dropdown" id="user-dropdown">
                    <button type="button" class="btn btn-ghost btn-sm user-dropdown-toggle" id="user-dropdown-toggle"
                            aria-haspopup="true" aria-expanded="false"
                            style="min-height:var(--touch-target);display:flex;align-items:center;gap:6px;">
                        <span style="font-size:1rem;">👤</span>
                        <span class="user-name"><?php echo esc_html($current_user->display_name); ?></span>
                        <span class="dropdown-arrow">▾</span>
                    </button>
                    <div class="user-dropdown-menu" id="user-dropdown-menu"
                         style="display:none;position:absolute;top:100%;right:0;min-width:200px;background:var(--color-bg);border:1px solid var(--color-border);border-radius:var(--radius-md);box-shadow:0 4px 16px rgba(0,0,0,0.12);z-index:150;padding:8px 0;margin-top:4px;">
                        <div class="dropdown-item" style="padding:8px 16px;color:var(--color-text-muted);font-size:var(--font-size-sm);border-bottom:1px solid var(--color-border-light);">
                            💎 <strong style="color:var(--color-primary);"><?php echo number_format($credits); ?></strong> hạt
                        </div>
                        <a href="<?php echo home_url('/tai-khoan'); ?>" class="dropdown-item" style="display:block;padding:10px 16px;text-decoration:none;color:var(--color-text-primary);">
                            📖 Tài khoản
                        </a>
                        <?php if (current_user_can('manage_options')): ?>
                            <a href="<?php echo admin_url(); ?>" class="dropdown-item" style="display:block;padding:10px 16px;text-decoration:none;color:var(--color-text-primary);">
                                ⚙ Admin
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo wp_logout_url(home_url()); ?>" class="dropdown-item" style="display:block;padding:10px 16px;text-decoration:none;color:var(--color-text-primary);border-top:1px solid var(--color-border-light);">
                            🚪 Đăng xuất
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?php echo wp_login_url(); ?>" class="btn btn-primary btn-sm">Đăng nhập</a>
            <?php endif; ?>
```

- [ ] **Step 2: Commit**

```bash
git add wp-content/themes/hatdaukhaai/header.php
git commit -m "feat: add user dropdown menu in header with credits and account link"
```

---

### Task 7: Add user dropdown JavaScript

**Files:**
- Modify: `wp-content/themes/hatdaukhaai/assets/js/main.js:224`

- [ ] **Step 1: Add dropdown toggle logic**

Insert before the closing `})();` (line 224):

```js
    // ===== User Dropdown Toggle =====
    var dropdown = document.getElementById('user-dropdown');
    if (dropdown) {
        var dropdownToggle = document.getElementById('user-dropdown-toggle');
        var dropdownMenu = document.getElementById('user-dropdown-menu');

        dropdownToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            var isOpen = dropdownMenu.style.display === 'block';
            dropdownMenu.style.display = isOpen ? 'none' : 'block';
            dropdownToggle.setAttribute('aria-expanded', !isOpen);
        });

        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target)) {
                dropdownMenu.style.display = 'none';
                dropdownToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }
```

- [ ] **Step 2: Commit**

```bash
git add wp-content/themes/hatdaukhaai/assets/js/main.js
git commit -m "feat: add user dropdown toggle JS"
```

---

### Task 8: Add account page + dropdown CSS

**Files:**
- Modify: `wp-content/themes/hatdaukhaai/assets/css/main.css`

- [ ] **Step 1: Append styles**

Append to end of `main.css`:

```css
/* ===== User Dropdown ===== */
.user-dropdown {
    position: relative;
}

.user-dropdown-toggle {
    cursor: pointer;
    white-space: nowrap;
}

.user-dropdown-toggle .dropdown-arrow {
    font-size: 0.7rem;
    transition: transform 0.2s;
}

.user-dropdown-toggle[aria-expanded="true"] .dropdown-arrow {
    transform: rotate(180deg);
}

.dropdown-item:hover {
    background: var(--color-bg-secondary);
}

.user-name {
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ===== Account Page Tabs ===== */
.account-tab:hover {
    color: var(--color-text-primary) !important;
    border-bottom-color: var(--color-border) !important;
}

/* ===== Progress Bar ===== */
.progress-bar {
    width: 100%;
}

/* ===== History List ===== */
.history-list a:hover {
    background: var(--color-bg-secondary) !important;
}

/* ===== Empty State ===== */
.empty-state a {
    display: inline-block;
    margin-top: 8px;
}

/* ===== Mobile: hide user name, show icon only ===== */
@media (max-width: 768px) {
    .user-name {
        display: none;
    }
    
    .user-dropdown-menu {
        position: fixed;
        top: var(--header-height);
        right: 8px;
        left: auto;
        min-width: 180px;
    }
    
    .account-tab {
        padding: 10px 14px;
        font-size: var(--font-size-sm);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add wp-content/themes/hatdaukhaai/assets/css/main.css
git commit -m "feat: add account page and dropdown styles"
```

---

### Task 9: Add CLI command + activator for account page

**Files:**
- Modify: `wp-content/plugins/hdk-core/includes/class-cli.php:37`
- Modify: `wp-content/plugins/hdk-core/includes/class-activator.php:27`

- [ ] **Step 1: Add CLI command**

Insert before closing `}` of class (line 35, before the `seed()` close or after `import()`):

```php
        /**
         * Create account page if not exists
         *
         * @when after_wp_load
         */
        public function create_account_page() {
            self::ensure_account_page();
            WP_CLI::success('Account page (tai-khoan) created or already exists.');
        }

        public static function ensure_account_page() {
            $existing = get_page_by_path('tai-khoan');
            if (!$existing) {
                wp_insert_post([
                    'post_type' => 'page',
                    'post_title' => 'Tài khoản',
                    'post_name' => 'tai-khoan',
                    'post_status' => 'publish',
                    'post_content' => '',
                    'comment_status' => 'closed',
                ]);
            }
        }
```

- [ ] **Step 2: Add activator call**

In `class-activator.php`, add after `add_role('moderator', ...)` line 26:

```php
        // Create account page
        HDK_CLI::ensure_account_page();
```

- [ ] **Step 3: Create page via CLI**

```bash
"/Users/tungtt96/Library/Application Support/Herd/bin/wp" --path=/Users/tungtt96/Herd/hongtrancac hdk create-account-page
```
Expected: `Success: Account page (tai-khoan) created or already exists.`

- [ ] **Step 4: Commit**

```bash
git add wp-content/plugins/hdk-core/includes/class-cli.php wp-content/plugins/hdk-core/includes/class-activator.php
git commit -m "feat: auto-create account page on activation + CLI command"
```

---

### Task 10: Sync to Herd site and verify

**Files:** None (deploy step)

- [ ] **Step 1: Sync theme and plugin to Herd site**

```bash
cp -r /Users/tungtt96/code/truyen/wp-content/themes/hatdaukhaai/* /Users/tungtt96/Herd/hongtrancac/wp-content/themes/hatdaukhaai/
cp -r /Users/tungtt96/code/truyen/wp-content/plugins/hdk-core/* /Users/tungtt96/Herd/hongtrancac/wp-content/plugins/hdk-core/
```

- [ ] **Step 2: Verify account page loads**

```bash
curl -sk -w "%{http_code}" https://hongtrancac.test/tai-khoan -o /dev/null
```
Expected: `302` (redirect to login - correct for logged-out users)

- [ ] **Step 3: Verify account page accessible when logged in**

```bash
curl -sk https://hongtrancac.test/wp-login.php -c /tmp/cookies.txt -d "log=admin&pwd=admin123&wp-submit=Log+In" -o /dev/null && curl -sk https://hongtrancac.test/tai-khoan -b /tmp/cookies.txt -w "%{http_code}" -o /dev/null
```
Expected: `200`

- [ ] **Step 4: Test each tab returns data correctly**

```bash
# Favorites tab
curl -sk "https://hongtrancac.test/tai-khoan?tab=favorites" -b /tmp/cookies.txt | grep -o "Tủ truyện\|grid grid-6\|empty-state" | head -3

# Reading tab
curl -sk "https://hongtrancac.test/tai-khoan?tab=reading" -b /tmp/cookies.txt | grep -o "Đang đọc\|Đọc tiếp\|empty-state" | head -3

# Purchased tab  
curl -sk "https://hongtrancac.test/tai-khoan?tab=purchased" -b /tmp/cookies.txt | grep -o "Đã mua\|empty-state" | head -3

# History tab
curl -sk "https://hongtrancac.test/tai-khoan?tab=history" -b /tmp/cookies.txt | grep -o "Lịch sử\|empty-state" | head -3
```

- [ ] **Step 5: Verify REST API endpoints**

```bash
curl -sk https://hongtrancac.test/wp-json/hdk/v1/me/favorites -b /tmp/cookies.txt | python3 -m json.tool | head -10
curl -sk https://hongtrancac.test/wp-json/hdk/v1/me/purchases -b /tmp/cookies.txt | python3 -m json.tool | head -10
```
Expected: JSON response with `stories`, `total`, `pages`

- [ ] **Step 6: Open site in browser**

```bash
"/Users/tungtt96/Library/Application Support/Herd/bin/herd" open hongtrancac
```
