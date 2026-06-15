<?php
/**
 * Template Name: Truyện Free
 * Page: Free stories listing
 */

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$result = HDK_DB::get_stories(['is_free' => 1, 'page' => $page, 'per_page' => 20, 'orderby' => 'total_views']);

get_header();
?>

<div class="container" style="padding-top:24px;">
    <div class="section-header">
        <h1 class="section-title">🎁 Truyện miễn phí</h1>
        <span style="color:var(--color-text-muted);"><?php echo $result['total']; ?> truyện</span>
    </div>
    <p style="color:var(--color-text-muted);margin-bottom:24px;">
        Truyện miễn phí - đọc thoải mái không giới hạn, không cần đăng ký.
    </p>

    <?php if (!empty($result['stories'])): ?>
        <div class="grid grid-4">
            <?php foreach ($result['stories'] as $i => $story): ?>
                <?php hdk_get_story_card($story, $i); ?>
            <?php endforeach; ?>
        </div>
        <?php hdk_get_pagination($result['pages'], $page); ?>
    <?php else: ?>
        <div style="text-align:center;padding:60px 0;">
            <p style="font-size:var(--font-size-lg);color:var(--color-text-muted);">Chưa có truyện miễn phí nào.</p>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
