<?php
/**
 * Template Name: Hoàn Thành
 * Page: Completed stories listing
 */

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$result = HDK_DB::get_stories(['status' => 'completed', 'page' => $page, 'per_page' => 20, 'orderby' => 'total_views']);

get_header();
?>

<div class="container" style="padding-top:24px;">
    <div class="section-header">
        <h1 class="section-title"><?php echo hdk_icon('check-circle'); ?> Truyện hoàn thành</h1>
        <span style="color:var(--color-text-muted);"><?php echo $result['total']; ?> truyện</span>
    </div>
    <p style="color:var(--color-text-muted);margin-bottom:24px;">
        Danh sách những truyện đã hoàn thành trọn bộ. Bạn có thể đọc một mạch không lo bị ngắt quãng.
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
            <p style="font-size:var(--font-size-lg);color:var(--color-text-muted);">Chưa có truyện hoàn thành nào.</p>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
