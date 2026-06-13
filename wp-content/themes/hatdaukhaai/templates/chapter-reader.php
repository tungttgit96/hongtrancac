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

<div class="container" style="padding-top:16px;padding-bottom:32px;">
    <!-- Breadcrumb -->
    <nav style="font-size:var(--font-size-sm);color:var(--color-text-muted);margin-bottom:16px;">
        <a href="<?php echo home_url('/'); ?>">Trang chủ</a> &raquo;
        <a href="/<?php echo $story->slug; ?>"><?php echo esc_html($story->title); ?></a> &raquo;
        <span>Chương <?php echo $chapter->chapter_number; ?></span>
    </nav>

    <!-- Chapter Navigation -->
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;background:var(--color-bg);border-radius:var(--radius-md);padding:16px;border:1px solid var(--color-border);">
        <?php if ($prev): ?>
            <a href="/<?php echo $story->slug; ?>?chuong=<?php echo $prev; ?>" class="btn btn-ghost btn-sm">&laquo; Chương trước</a>
        <?php else: ?>
            <span></span>
        <?php endif; ?>

        <div style="text-align:center;">
            <h1 style="font-size:var(--font-size-lg);font-weight:700;margin-bottom:4px;"><?php echo esc_html($chapter->title); ?></h1>
            <span style="font-size:var(--font-size-xs);color:var(--color-text-muted);">👁 <?php echo number_format($chapter->views); ?> lượt đọc</span>
        </div>

        <?php if ($next): ?>
            <a href="/<?php echo $story->slug; ?>?chuong=<?php echo $next; ?>" class="btn btn-primary btn-sm">Chương sau &raquo;</a>
        <?php else: ?>
            <span></span>
        <?php endif; ?>
    </div>

    <!-- Chapter Content -->
    <article style="background:var(--color-bg);border-radius:var(--radius-md);padding:24px;border:1px solid var(--color-border);line-height:2;font-size:var(--font-size-lg);min-height:60vh;">
        <?php echo $chapter->content; ?>
    </article>

    <!-- Bottom Navigation -->
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-top:20px;background:var(--color-bg);border-radius:var(--radius-md);padding:16px;border:1px solid var(--color-border);">
        <?php if ($prev): ?>
            <a href="/<?php echo $story->slug; ?>?chuong=<?php echo $prev; ?>" class="btn btn-outline btn-sm">&laquo; Chương trước</a>
        <?php else: ?>
            <span></span>
        <?php endif; ?>
        <a href="/<?php echo $story->slug; ?>" class="btn btn-ghost btn-sm">📋 Mục lục</a>
        <?php if ($next): ?>
            <a href="/<?php echo $story->slug; ?>?chuong=<?php echo $next; ?>" class="btn btn-primary">Chương sau &raquo;</a>
        <?php else: ?>
            <span></span>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-save reading progress
var scrollTimer;
window.addEventListener('scroll', function() {
    clearTimeout(scrollTimer);
    scrollTimer = setTimeout(function() {
        var scrollPercent = Math.round((window.scrollY + window.innerHeight) / document.documentElement.scrollHeight * 100);
        if (typeof fetch !== 'undefined') {
            fetch('/wp-json/hdk/v1/reading-progress', {
                method: 'PATCH',
                headers: {'Content-Type': 'application/json'},
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
        window.location.href = '/<?php echo $story->slug; ?>?chuong=<?php echo $prev; ?>';
        <?php endif; ?>
    } else if (e.key === 'ArrowRight') {
        <?php if ($next): ?>
        window.location.href = '/<?php echo $story->slug; ?>?chuong=<?php echo $next; ?>';
        <?php endif; ?>
    }
});
</script>

<?php get_footer(); ?>
