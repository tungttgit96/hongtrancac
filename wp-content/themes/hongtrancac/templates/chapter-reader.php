<?php
/**
 * Template: Chapter Reader
 * @global object $hdk_story
 * @global object $hdk_chapter
 */
global $hdk_story, $hdk_chapter;

if (!$hdk_story || !$hdk_chapter) {
    wp_redirect(home_url('/'));
    exit;
}

$story = $hdk_story;
$chapter = $hdk_chapter;

// Get prev/next chapters
global $wpdb;
$prev = $wpdb->get_var($wpdb->prepare(
    "SELECT chapter_number FROM " . HDK_DB::table('hdk_chapters') . "
     WHERE story_id = %d AND chapter_number < %d AND status = 'published' ORDER BY chapter_number DESC LIMIT 1",
    $story->id, $chapter->chapter_number
));
$next = $wpdb->get_var($wpdb->prepare(
    "SELECT chapter_number FROM " . HDK_DB::table('hdk_chapters') . "
     WHERE story_id = %d AND chapter_number > %d AND status = 'published' ORDER BY chapter_number ASC LIMIT 1",
    $story->id, $chapter->chapter_number
));

// Reading progress auto-save
$progress_saved = false;
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM " . HDK_DB::table('hdk_reading_progress') . " WHERE story_id = %d AND user_id = %d",
        $story->id, $user_id
    ));
    if (!$existing || $existing > 0) {
        $wpdb->replace(HDK_DB::table('hdk_reading_progress'), [
            'story_id' => $story->id,
            'user_id' => $user_id,
            'chapter_number' => $chapter->chapter_number,
            'scroll_percent' => 0,
            'updated_at' => current_time('mysql'),
        ]);
        $progress_saved = true;
    }
}

get_header();
?>

<div class="container page-shell" style="padding-top:16px;padding-bottom:32px;">
    <!-- Breadcrumb -->
    <nav style="font-size:var(--font-size-sm);color:var(--color-text-muted);margin-bottom:16px;">
        <a href="<?php echo home_url('/'); ?>">Trang chủ</a> &raquo;
        <a href="<?php echo esc_url(hdk_story_url($story->slug)); ?>"><?php echo esc_html($story->title); ?></a> &raquo;
        <span>Chương <?php echo $chapter->chapter_number; ?></span>
    </nav>

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

    <!-- Floating TOC -->
    <button type="button" class="toc-float-btn" id="toc-float-btn" onclick="toggleTOC()" aria-label="Mục lục" style="position:fixed;right:16px;bottom:80px;z-index:90;width:48px;height:48px;border-radius:50%;background:var(--color-primary);color:var(--color-on-primary);border:none;font-size:20px;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,0.15);display:flex;align-items:center;justify-content:center;">📋</button>

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

    <!-- Report Button -->
    <div style="text-align:right;margin-bottom:8px;">
        <button type="button" class="btn btn-ghost btn-sm" onclick="toggleReportModal()" style="font-size:var(--font-size-sm);">🚩 Báo lỗi</button>
    </div>

    <!-- Report Modal -->
    <div id="report-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:var(--color-overlay);z-index:300;align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
        <div style="background:var(--color-bg);border-radius:var(--radius-lg);padding:24px;max-width:420px;width:90%;">
            <h3 style="margin:0 0 16px;">Báo lỗi chương</h3>
            <form id="report-form" onsubmit="submitReport(event)" style="display:flex;flex-direction:column;gap:12px;">
                <input type="hidden" id="report-story-id" value="<?php echo (int)$story->id; ?>">
                <input type="hidden" id="report-chapter" value="<?php echo (int)$chapter->chapter_number; ?>">
                <select id="report-type" required style="padding:8px;border:1px solid var(--color-border);border-radius:4px;background:var(--color-bg);color:var(--color-text-primary);">
                    <option value="">Chọn loại lỗi</option>
                    <option value="typo">Lỗi chính tả</option>
                    <option value="wrong_content">Sai nội dung</option>
                    <option value="display_error">Lỗi hiển thị</option>
                    <option value="other">Khác</option>
                </select>
                <textarea id="report-note" placeholder="Mô tả lỗi (không bắt buộc)" rows="3" style="padding:8px;border:1px solid var(--color-border);border-radius:4px;background:var(--color-bg);color:var(--color-text-primary);resize:vertical;"></textarea>
                <div style="display:flex;gap:8px;justify-content:flex-end;">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('report-modal').style.display='none'">Hủy</button>
                    <button type="submit" class="btn btn-primary btn-sm">Gửi báo lỗi</button>
                </div>
            </form>
            <div id="report-msg" style="margin-top:12px;text-align:center;display:none;"></div>
        </div>
    </div>

    <!-- Chapter Navigation -->
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;background:var(--color-bg);border-radius:var(--radius-md);padding:16px;border:1px solid var(--color-border);">
        <?php if ($prev): ?>
            <a href="<?php echo esc_url(hdk_story_url($story->slug, ['chuong' => $prev])); ?>" class="btn btn-ghost btn-sm">&laquo; Chương trước</a>
        <?php else: ?>
            <span></span>
        <?php endif; ?>

        <div style="text-align:center;">
            <h1 style="font-size:var(--font-size-lg);font-weight:700;margin-bottom:4px;"><?php echo esc_html($chapter->title); ?></h1>
            <span style="font-size:var(--font-size-xs);color:var(--color-text-muted);">👁 <?php echo number_format($chapter->views); ?> lượt đọc</span>
        </div>

        <?php if ($next): ?>
            <a href="<?php echo esc_url(hdk_story_url($story->slug, ['chuong' => $next])); ?>" class="btn btn-primary btn-sm">Chương sau &raquo;</a>
        <?php else: ?>
            <span></span>
        <?php endif; ?>
    </div>

    <!-- Chapter Content (JS-decoded to prevent scraping) -->
    <article class="chapter-content" style="background:var(--color-bg);border-radius:var(--radius-md);padding:24px;border:1px solid var(--color-border);line-height:2;font-size:var(--font-size-lg);min-height:60vh;" id="chapter-content" data-story-id="<?php echo (int)$story->id; ?>" data-chapter-number="<?php echo (int)$chapter->chapter_number; ?>">
        <div id="content-loading" aria-live="polite" style="text-align:center;padding:40px;color:var(--color-text-muted);">Đang tải nội dung…</div>
    </article>

    <script id="chapter-data" type="text/plain" style="display:none;"><?php echo HDK_Protection::obfuscate($chapter->content); ?></script>

    <!-- Bottom Navigation -->
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-top:20px;background:var(--color-bg);border-radius:var(--radius-md);padding:16px;border:1px solid var(--color-border);">
        <?php if ($prev): ?>
            <a href="<?php echo esc_url(hdk_story_url($story->slug, ['chuong' => $prev])); ?>" class="btn btn-outline btn-sm">&laquo; Chương trước</a>
        <?php else: ?>
            <span></span>
        <?php endif; ?>
        <button type="button" class="btn btn-ghost btn-sm" onclick="toggleTOC()" style="min-height:var(--touch-target);">📋 Mục lục</button>
        <?php if ($next): ?>
            <a href="<?php echo esc_url(hdk_story_url($story->slug, ['chuong' => $next])); ?>" class="btn btn-primary">Chương sau &raquo;</a>
        <?php else: ?>
            <span></span>
        <?php endif; ?>
    </div>
</div>

<script>
var HDK_READER_STORY_URL = <?php echo wp_json_encode(hdk_story_url($story->slug)); ?>;
var HDK_READER_PREV_URL = <?php echo wp_json_encode($prev ? hdk_story_url($story->slug, ['chuong' => $prev]) : ''); ?>;
var HDK_READER_NEXT_URL = <?php echo wp_json_encode($next ? hdk_story_url($story->slug, ['chuong' => $next]) : ''); ?>;
var HDK_READER_API_BASE = (window.hdkApi && window.hdkApi.restBase) || <?php echo wp_json_encode(rest_url('hdk/v1')); ?>;
// Decode and render chapter content (anti-scraping)
(function() {
    var dataEl = document.getElementById('chapter-data');
    var target = document.getElementById('chapter-content');
    if (!dataEl || !target) return;

    try {
        var encoded = dataEl.textContent.replace(/\s+/g, '');
        var decoded = atob(encoded);
        target.innerHTML = decoded;
    } catch(e) {
        target.innerHTML = '<div style="text-align:center;padding:40px;color:var(--color-text-muted);">Không thể tải nội dung. Vui lòng bật JavaScript.</div>';
    }

    // Disable right-click + text selection on content
    target.addEventListener('contextmenu', function(e) { e.preventDefault(); });
    target.addEventListener('copy', function(e) { 
        e.preventDefault(); 
        alert('Sao chép nội dung bị vô hiệu hóa.');
    });
    target.addEventListener('selectstart', function(e) { e.preventDefault(); });
    target.style.userSelect = 'none';
    target.style.webkitUserSelect = 'none';

    // Prevent print screen via CSS
    var style = document.createElement('style');
    style.textContent = '@media print { #chapter-content { display: none !important; } #chapter-content::after { content: "Nội dung bị ẩn khi in. Vui lòng đọc online."; } }';
    document.head.appendChild(style);
})();

// Auto-save reading progress
var scrollTimer;
window.addEventListener('scroll', function() {
    clearTimeout(scrollTimer);
    scrollTimer = setTimeout(function() {
        var scrollPercent = Math.round((window.scrollY + window.innerHeight) / document.documentElement.scrollHeight * 100);
        if (typeof fetch !== 'undefined') {
            fetch(HDK_READER_API_BASE.replace(/\/$/, '') + '/reading-progress', {
                method: 'PATCH',
                headers: {'Content-Type': 'application/json', 'X-WP-Nonce': window.hdkRestNonce || ''},
                body: JSON.stringify({
                    story_id: <?php echo (int)$story->id; ?>,
                    chapter_number: <?php echo (int)$chapter->chapter_number; ?>,
                    scroll_percent: scrollPercent
                })
            });
        }
    }, 2000);
});

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowLeft') {
        <?php if ($prev): ?>
        window.location.href = HDK_READER_PREV_URL;
        <?php endif; ?>
    } else if (e.key === 'ArrowRight') {
        <?php if ($next): ?>
        window.location.href = HDK_READER_NEXT_URL;
        <?php endif; ?>
    }
});
</script>

<?php get_footer(); ?>
