# Reading Experience Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Enhance chapter reader with customizable font size, font family, line height, reading themes, reading width, floating TOC drawer, and preference persistence (localStorage + DB).

**Architecture:** CSS variables on #chapter-content driven by JS settings bar. Preferences saved to localStorage (all users) + `hdk_user_reader_prefs` table (logged-in). Floating TOC drawer fetches chapters via REST API.

**Tech Stack:** WordPress, PHP, MySQL, Alpine.js, CSS custom properties

---

## File Structure

| File | Action |
|------|--------|
| `plugins/hdk-core/includes/class-schema.php` | Modify |
| `plugins/hdk-core/includes/class-db.php` | Modify |
| `plugins/hdk-core/includes/class-rest-api.php` | Modify |
| `themes/hatdaukhaai/templates/chapter-reader.php` | Modify |
| `themes/hatdaukhaai/assets/css/main.css` | Modify |
| `themes/hatdaukhaai/assets/js/main.js` | Modify |

---

### Task 1: DB schema + query methods

**Files:**
- `wp-content/plugins/hdk-core/includes/class-schema.php`
- `wp-content/plugins/hdk-core/includes/class-db.php`

- [ ] **Step 1: Add table to schema**

In `class-schema.php`, before `foreach ($sql as $query)`:

```php
        // User Reader Preferences
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hdk_user_reader_prefs (
            user_id BIGINT UNSIGNED PRIMARY KEY,
            font_size INT DEFAULT 20,
            font_family VARCHAR(100) DEFAULT 'Be Vietnam Pro',
            line_height DECIMAL(3,1) DEFAULT 2.0,
            theme ENUM('light','dark','sepia') DEFAULT 'light',
            reading_width ENUM('narrow','wide') DEFAULT 'wide',
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset;";
```

- [ ] **Step 2: Add DB methods to class-db.php**

Before class closing `}`:

```php
    public static function get_chapters_toc($story_id) {
        global $wpdb;
        $table = self::table('hdk_chapters');
        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, chapter_number, title, status, word_count FROM $table
             WHERE story_id = %d AND status IN ('published','scheduled') ORDER BY chapter_number ASC",
            $story_id
        ));
    }

    public static function get_reader_prefs($user_id) {
        global $wpdb;
        $table = self::table('hdk_user_reader_prefs');
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d", $user_id));
    }

    public static function save_reader_prefs($user_id, $data) {
        global $wpdb;
        $table = self::table('hdk_user_reader_prefs');
        $existing = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM $table WHERE user_id = %d", $user_id));
        $data['updated_at'] = current_time('mysql');
        if ($existing) {
            $wpdb->update($table, $data, ['user_id' => $user_id]);
        } else {
            $data['user_id'] = $user_id;
            $wpdb->insert($table, $data);
        }
    }
```

- [ ] **Step 3: Commit**

```bash
git add wp-content/plugins/hdk-core/includes/class-schema.php wp-content/plugins/hdk-core/includes/class-db.php
git commit -m "feat: add reader prefs table and TOC/prefs DB methods"
```

---

### Task 2: REST API endpoints

**File:** `wp-content/plugins/hdk-core/includes/class-rest-api.php`

- [ ] **Step 1: Register routes in init()**

```php
        register_rest_route('hdk/v1', '/chapters/(?P<story_id>\d+)', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_chapters'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('hdk/v1', '/reader-prefs', [
            'methods' => 'PATCH',
            'callback' => [__CLASS__, 'save_reader_prefs'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('hdk/v1', '/reader-prefs', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_reader_prefs'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);
```

- [ ] **Step 2: Add handlers before class closing `}`**

```php
    public static function get_chapters($request) {
        $story_id = (int)$request->get_param('story_id');
        $chapters = HDK_DB::get_chapters_toc($story_id);
        $user_id = get_current_user_id();
        
        // Add purchase status for logged-in users
        if ($user_id) {
            global $wpdb;
            $purchased = $wpdb->get_results($wpdb->prepare(
                "SELECT chapter_number, is_full FROM " . HDK_DB::table('hdk_purchased_chapters') . " WHERE user_id = %d AND story_id = %d",
                $user_id, $story_id
            ));
            $purchased_map = [];
            $has_full = false;
            foreach ($purchased as $p) {
                if ($p->is_full) $has_full = true;
                else $purchased_map[$p->chapter_number] = true;
            }
            foreach ($chapters as $ch) {
                $ch->is_purchased = $has_full || isset($purchased_map[$ch->chapter_number]);
            }
        }
        
        return rest_ensure_response(['chapters' => $chapters]);
    }

    public static function get_reader_prefs($request) {
        $prefs = HDK_DB::get_reader_prefs(get_current_user_id());
        return rest_ensure_response(['prefs' => $prefs]);
    }

    public static function save_reader_prefs($request) {
        $body = json_decode($request->get_body(), true) ?? [];
        $data = [];
        if (isset($body['font_size'])) $data['font_size'] = max(16, min(28, (int)$body['font_size']));
        if (isset($body['font_family'])) $data['font_family'] = sanitize_text_field($body['font_family']);
        if (isset($body['line_height'])) $data['line_height'] = (float)$body['line_height'];
        if (isset($body['theme'])) $data['theme'] = sanitize_text_field($body['theme']);
        if (isset($body['reading_width'])) $data['reading_width'] = sanitize_text_field($body['reading_width']);
        
        HDK_DB::save_reader_prefs(get_current_user_id(), $data);
        return rest_ensure_response(['saved' => true]);
    }
```

- [ ] **Step 3: Commit**

```bash
git add wp-content/plugins/hdk-core/includes/class-rest-api.php
git commit -m "feat: add chapter TOC and reader prefs REST endpoints"
```

---

### Task 3: Update chapter reader template

**File:** `wp-content/themes/hatdaukhaai/templates/chapter-reader.php`

- [ ] **Step 1: Add settings bar and TOC button**

Insert after the breadcrumb div and before the top nav bar:

```php
<!-- Reader Settings Bar -->
<div class="reader-settings" id="reader-settings" style="display:flex;align-items:center;gap:8px;padding:8px 16px;background:var(--color-bg-secondary);border-radius:var(--radius-md);margin-bottom:12px;flex-wrap:wrap;font-size:var(--font-size-sm);">
    <span style="color:var(--color-text-muted);margin-right:4px;">Cỡ chữ</span>
    <button type="button" class="btn btn-ghost btn-sm" onclick="adjustFontSize(-2)" style="min-width:32px;">A⁻</button>
    <span id="font-size-val" style="min-width:32px;text-align:center;font-weight:600;">20</span>
    <button type="button" class="btn btn-ghost btn-sm" onclick="adjustFontSize(2)" style="min-width:32px;">A⁺</button>

    <span style="color:var(--color-text-muted);margin-left:12px;margin-right:4px;">Font</span>
    <select id="font-family-select" onchange="setFontFamily(this.value)" style="padding:4px 8px;border:1px solid var(--color-border);border-radius:4px;background:var(--color-bg);color:var(--color-text-primary);font-size:var(--font-size-sm);">
        <option value="Be Vietnam Pro">Be Vietnam Pro</option>
        <option value="Georgia">Georgia</option>
        <option value="Arial">Arial</option>
        <option value="Times New Roman">Times New Roman</option>
    </select>

    <span style="color:var(--color-text-muted);margin-left:12px;margin-right:4px;">Giãn dòng</span>
    <select id="line-height-select" onchange="setLineHeight(this.value)" style="padding:4px 8px;border:1px solid var(--color-border);border-radius:4px;background:var(--color-bg);color:var(--color-text-primary);font-size:var(--font-size-sm);">
        <option value="1.5">1.5</option>
        <option value="1.8">1.8</option>
        <option value="2.0">2.0</option>
        <option value="2.5">2.5</option>
    </select>

    <span style="color:var(--color-text-muted);margin-left:12px;margin-right:4px;">Theme</span>
    <button type="button" class="btn btn-ghost btn-sm reader-theme-btn" data-theme="light" onclick="setReaderTheme('light')" id="theme-btn-light">☀️</button>
    <button type="button" class="btn btn-ghost btn-sm reader-theme-btn" data-theme="dark" onclick="setReaderTheme('dark')" id="theme-btn-dark">🌙</button>
    <button type="button" class="btn btn-ghost btn-sm reader-theme-btn" data-theme="sepia" onclick="setReaderTheme('sepia')" id="theme-btn-sepia">📜</button>

    <button type="button" class="btn btn-ghost btn-sm" onclick="toggleReadingWidth()" id="width-toggle-btn" style="margin-left:12px;">📏</button>
</div>
```

- [ ] **Step 2: Add floating TOC button**

Insert after the settings bar and before the nav:

```php
<!-- Floating TOC -->
<button type="button" class="toc-float-btn" id="toc-float-btn" onclick="toggleTOC()" aria-label="Mục lục" style="position:fixed;right:16px;bottom:80px;z-index:90;width:48px;height:48px;border-radius:50%;background:var(--color-primary);color:#fff;border:none;font-size:20px;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,0.15);display:flex;align-items:center;justify-content:center;">📋</button>

<!-- TOC Drawer -->
<div class="toc-overlay" id="toc-overlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:var(--color-overlay);z-index:200;" onclick="closeTOC()"></div>
<div class="toc-drawer" id="toc-drawer" style="position:fixed;top:0;right:0;width:320px;max-width:85vw;height:100%;background:var(--color-bg);z-index:201;transform:translateX(100%);transition:transform 0.3s ease;overflow-y:auto;padding:20px;box-shadow:-4px 0 16px rgba(0,0,0,0.1);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3 style="margin:0;">Mục lục</h3>
        <button type="button" class="btn btn-ghost btn-sm" onclick="closeTOC()" aria-label="Đóng">✕</button>
    </div>
    <div id="toc-list" style="display:flex;flex-direction:column;gap:4px;">
        <p style="color:var(--color-text-muted);text-align:center;padding:20px;">Đang tải…</p>
    </div>
</div>
```

- [ ] **Step 3: Add chapter progress indicator to nav**

In the top nav bar, replace the center section that shows chapter title with:

```php
                <div style="text-align:center;">
                    <h1 class="chapter-title" style="font-size:var(--font-size-xl);font-weight:700;margin:0 0 4px;"><?php echo esc_html($chapter->title); ?></h1>
                    <div style="font-size:var(--font-size-sm);color:var(--color-text-muted);">
                        Chương <?php echo $chapter->chapter_number; ?> / <?php echo $total_chapters; ?>
                        <?php if ($chapter->views): ?> · 👁 <?php echo number_format((int)$chapter->views); ?> lượt đọc<?php endif; ?>
                    </div>
                </div>
```

- [ ] **Step 4: Change "Mục lục" link to TOC button**

In BOTH top nav and bottom nav, find the `href="/<?php echo esc_attr($story->slug); ?>"` "Mục lục" link and replace with:

```php
                    <button type="button" class="btn btn-ghost btn-sm" onclick="toggleTOC()" style="min-height:var(--touch-target);">📋 Mục lục</button>
```

- [ ] **Step 5: Add data attributes for JS**

Add to the chapter-content div and nearby elements:

```php
<?php
// At top of file, add after existing PHP block that fetches chapter data:
$total_chapters = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM " . HDK_DB::table('hdk_chapters') . " WHERE story_id = %d AND status = 'published'",
    $story_id
));
// Add data attributes to a script tag or body for JS use:
?>
```

In the `<article id="chapter-content">` tag, add:
```php
style="font-size:var(--reader-font-size, var(--font-size-lg));font-family:var(--reader-font-family, var(--font-family));line-height:var(--reader-line-height, 2);max-width:var(--reader-max-width, none);margin:0 auto;"
```

Actually, to keep clean, add these data attributes to the article:
```php
<article id="chapter-content" class="chapter-content" data-story-id="<?php echo (int)$story_id; ?>" data-chapter-number="<?php echo (int)$chapter->chapter_number; ?>">
```

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/hatdaukhaai/templates/chapter-reader.php
git commit -m "feat: add reader settings bar, floating TOC, nav improvements"
```

---

### Task 4: Add CSS for reader features

**File:** `wp-content/themes/hatdaukhaai/assets/css/main.css`

- [ ] **Step 1: Append reader CSS**

```css
/* ===== Reader Settings ===== */
.reader-settings {
    user-select: none;
}

.reader-theme-btn.active {
    background: var(--color-primary);
    color: #fff;
}

/* ===== Reader Content ===== */
.chapter-content {
    transition: font-size 0.15s, line-height 0.15s;
    word-wrap: break-word;
}

/* ===== Reader Themes ===== */
.reader-theme-dark #chapter-content {
    background: #1a1a1a !important;
    color: #d4d4d4 !important;
}

.reader-theme-sepia #chapter-content {
    background: #F4ECD8 !important;
    color: #5B4636 !important;
}

/* ===== Floating TOC ===== */
.toc-float-btn {
    transition: transform 0.2s, opacity 0.2s;
}

.toc-float-btn:hover {
    transform: scale(1.1);
}

.toc-drawer.open {
    transform: translateX(0) !important;
}

.toc-chapter {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: var(--radius-sm);
    text-decoration: none;
    color: var(--color-text-primary);
    transition: background 0.15s;
}

.toc-chapter:hover {
    background: var(--color-bg-secondary);
}

.toc-chapter.current {
    background: var(--color-primary-light);
    font-weight: 600;
}

.toc-chapter-num {
    min-width: 36px;
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
}

.toc-chapter-lock {
    margin-left: auto;
    font-size: 14px;
}

/* ===== Mobile Reader ===== */
@media (max-width: 768px) {
    .reader-settings {
        gap: 4px;
        padding: 6px 10px;
    }
    
    .reader-settings select {
        max-width: 90px;
    }
    
    .toc-drawer {
        width: 100%;
        max-width: 100%;
    }
    
    .toc-float-btn {
        right: 8px;
        bottom: 60px;
        width: 42px;
        height: 42px;
    }
}

@media print {
    .reader-settings,
    .toc-float-btn,
    .toc-drawer,
    .toc-overlay {
        display: none !important;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add wp-content/themes/hatdaukhaai/assets/css/main.css
git commit -m "feat: add reader settings, TOC, and theme CSS"
```

---

### Task 5: Add reader JS

**File:** `wp-content/themes/hatdaukhaai/assets/js/main.js`

- [ ] **Step 1: Append reader JS**

Insert before `})();`:

```js
    // ===== Reader Settings =====
    var readerContent = document.getElementById('chapter-content');
    if (readerContent) {
        var PREFS_KEY = 'hdk-reader-prefs';
        var STORY_ID = readerContent.dataset.storyId;
        var defaults = {font_size: 20, font_family: 'Be Vietnam Pro', line_height: '2.0', theme: 'light', reading_width: 'wide'};
        
        function loadPrefs() {
            var prefs = {};
            try { var local = JSON.parse(safeGetStorage(PREFS_KEY)); if (local) prefs = local; } catch(e) {}
            return Object.assign({}, defaults, prefs);
        }

        function savePrefs(prefs) {
            safeSetStorage(PREFS_KEY, JSON.stringify(prefs));
            // Also save server-side if logged in
            fetch('/wp-json/hdk/v1/reader-prefs', {
                method: 'PATCH',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(prefs)
            }).catch(function(){});
        }

        function applyPrefs(prefs) {
            readerContent.style.fontSize = prefs.font_size + 'px';
            readerContent.style.fontFamily = prefs.font_family;
            readerContent.style.lineHeight = prefs.line_height;
            document.getElementById('font-size-val').textContent = prefs.font_size;
            document.getElementById('font-family-select').value = prefs.font_family;
            document.getElementById('line-height-select').value = prefs.line_height;
            
            // Theme
            var themeBtns = document.querySelectorAll('.reader-theme-btn');
            themeBtns.forEach(function(b) { b.classList.remove('active'); });
            var activeBtn = document.getElementById('theme-btn-' + prefs.theme);
            if (activeBtn) activeBtn.classList.add('active');
            document.body.classList.remove('reader-theme-dark', 'reader-theme-sepia');
            if (prefs.theme !== 'light') document.body.classList.add('reader-theme-' + prefs.theme);
            
            // Width
            if (prefs.reading_width === 'narrow') {
                readerContent.style.maxWidth = '700px';
                readerContent.style.margin = '0 auto';
            } else {
                readerContent.style.maxWidth = '';
                readerContent.style.margin = '';
            }
        }

        var currentPrefs = loadPrefs();
        applyPrefs(currentPrefs);

        window.adjustFontSize = function(delta) {
            currentPrefs.font_size = Math.max(16, Math.min(28, currentPrefs.font_size + delta));
            applyPrefs(currentPrefs);
            savePrefs(currentPrefs);
        };

        window.setFontFamily = function(family) {
            currentPrefs.font_family = family;
            applyPrefs(currentPrefs);
            savePrefs(currentPrefs);
        };

        window.setLineHeight = function(lh) {
            currentPrefs.line_height = lh;
            applyPrefs(currentPrefs);
            savePrefs(currentPrefs);
        };

        window.setReaderTheme = function(theme) {
            currentPrefs.theme = theme;
            applyPrefs(currentPrefs);
            savePrefs(currentPrefs);
        };

        window.toggleReadingWidth = function() {
            currentPrefs.reading_width = currentPrefs.reading_width === 'narrow' ? 'wide' : 'narrow';
            applyPrefs(currentPrefs);
            savePrefs(currentPrefs);
        };

        // Fetch server prefs for logged-in users (override localStorage)
        fetch('/wp-json/hdk/v1/reader-prefs')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.prefs && data.prefs.user_id) {
                    currentPrefs.font_size = parseInt(data.prefs.font_size) || defaults.font_size;
                    currentPrefs.font_family = data.prefs.font_family || defaults.font_family;
                    currentPrefs.line_height = data.prefs.line_height || defaults.line_height;
                    currentPrefs.theme = data.prefs.theme || defaults.theme;
                    currentPrefs.reading_width = data.prefs.reading_width || defaults.reading_width;
                    applyPrefs(currentPrefs);
                }
            }).catch(function(){});

        // ===== TOC =====
        window.toggleTOC = function() {
            var drawer = document.getElementById('toc-drawer');
            var overlay = document.getElementById('toc-overlay');
            var isOpen = drawer.classList.contains('open');
            if (isOpen) { closeTOC(); }
            else {
                overlay.style.display = 'block';
                drawer.classList.add('open');
                if (!drawer.dataset.loaded) loadTOC();
            }
        };

        window.closeTOC = function() {
            document.getElementById('toc-drawer').classList.remove('open');
            document.getElementById('toc-overlay').style.display = 'none';
        };

        function loadTOC() {
            var drawer = document.getElementById('toc-drawer');
            var list = document.getElementById('toc-list');
            fetch('/wp-json/hdk/v1/chapters/' + STORY_ID)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var chapters = data.chapters || [];
                    var currentChapter = parseInt(readerContent.dataset.chapterNumber);
                    var html = '';
                    chapters.forEach(function(ch) {
                        var cls = 'toc-chapter';
                        if (ch.chapter_number === currentChapter) cls += ' current';
                        var lockIcon = '🔓';
                        if (!ch.is_purchased && ch.chapter_number > 0) lockIcon = '🔒';
                        if (ch.is_purchased) lockIcon = '✅';
                        var url = '/<?php echo esc_js($story->slug ?? ''); ?>?chuong=' + ch.chapter_number;
                        html += '<a href="' + url + '" class="' + cls + '">' +
                            '<span class="toc-chapter-num">' + ch.chapter_number + '</span>' +
                            '<span class="toc-chapter-title">' + escapeHtml(ch.title) + '</span>' +
                            '<span class="toc-chapter-lock">' + lockIcon + '</span>' +
                            '</a>';
                    });
                    list.innerHTML = html || '<p style="color:var(--color-text-muted);text-align:center;padding:20px;">Không có chương nào</p>';
                    drawer.dataset.loaded = '1';
                    
                    // Scroll to current chapter
                    var current = list.querySelector('.toc-chapter.current');
                    if (current) current.scrollIntoView({block: 'center'});
                })
                .catch(function() {
                    list.innerHTML = '<p style="color:var(--color-text-muted);text-align:center;padding:20px;">Không thể tải danh sách chương</p>';
                });
        }

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }
```

- [ ] **Step 2: Commit**

```bash
git add wp-content/themes/hatdaukhaai/assets/js/main.js
git commit -m "feat: add reader settings JS and floating TOC"
```

---

### Task 6: Sync Herd and verify

- [ ] **Step 1: Sync and migrate**

```bash
cp -r /Users/tungtt96/code/truyen/wp-content/themes/hatdaukhaai/* /Users/tungtt96/Herd/hongtrancac/wp-content/themes/hatdaukhaai/
cp -r /Users/tungtt96/code/truyen/wp-content/plugins/hdk-core/* /Users/tungtt96/Herd/hongtrancac/wp-content/plugins/hdk-core/
"/Users/tungtt96/Library/Application Support/Herd/bin/php" -r "
define('WP_USE_THEMES', false);
require '/Users/tungtt96/Herd/hongtrancac/wp-load.php';
HDK_Schema::create_tables();
echo 'OK';
"
```

- [ ] **Step 2: Verify chapter reader loads with settings bar**

```bash
curl -sk "https://hongtrancac.test/qua-ngay?chuong=1" -w "HTTP %{http_code}" | grep -c "reader-settings\|toc-float-btn\|reader-theme-btn"
```
Expected: HTTP 200, matches > 0

- [ ] **Step 3: Verify TOC API**

```bash
curl -sk "https://hongtrancac.test/wp-json/hdk/v1/chapters/1" | python3 -m json.tool 2>/dev/null | head -10
```
Expected: JSON with chapters array

- [ ] **Step 4: Open site**

```bash
"/Users/tungtt96/Library/Application Support/Herd/bin/herd" open hongtrancac
```
