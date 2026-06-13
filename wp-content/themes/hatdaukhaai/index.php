<?php
/**
 * Template: Home Page
 */

get_header();

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;

// Get new/updated stories
$new_stories = HDK_DB::get_stories(['per_page' => 12, 'orderby' => 'updated_at', 'order' => 'DESC']);

// Get hot stories (most views)
$hot_stories = HDK_DB::get_stories(['per_page' => 12, 'orderby' => 'total_views', 'order' => 'DESC']);

// Get completed stories
$completed_stories = HDK_DB::get_stories(['per_page' => 12, 'status' => 'completed', 'orderby' => 'updated_at', 'order' => 'DESC']);

// Get free stories
$free_stories = HDK_DB::get_stories(['per_page' => 12, 'is_free' => 1, 'orderby' => 'total_views', 'order' => 'DESC']);

// Get categories
global $wpdb;
$categories = $wpdb->get_results("SELECT * FROM " . HDK_DB::table('hdk_categories') . " ORDER BY sort_order");
?>

<?php hdk_get_hero_section(); ?>

<!-- New Stories -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">🆕 Truyện mới cập nhật</h2>
            <a href="/danh-sach-truyen" class="btn btn-outline btn-sm">Xem tất cả</a>
        </div>
        <div class="grid grid-6">
            <?php foreach ($new_stories['stories'] as $i => $story): ?>
                <?php hdk_get_story_card($story, $i); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Hot Stories -->
<section class="section" style="background:var(--color-bg);">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">🔥 Truyện hot nhất</h2>
            <a href="/bang-xep-hang" class="btn btn-outline btn-sm">Bảng xếp hạng</a>
        </div>
        <div class="grid grid-6">
            <?php foreach ($hot_stories['stories'] as $i => $story): ?>
                <?php hdk_get_story_card($story, $i); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Categories -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">📂 Thể loại</h2>
            <a href="/the-loai" class="btn btn-outline btn-sm">Tất cả thể loại</a>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            <?php foreach ($categories as $cat): ?>
                <a href="/the-loai/<?php echo $cat->slug; ?>" class="badge badge-primary" style="text-decoration:none;padding:8px 16px;font-size:var(--font-size-sm);">
                    <?php echo esc_html($cat->name); ?>
                    <span style="opacity:0.7;">(<?php echo $cat->story_count; ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Completed Stories -->
<section class="section" style="background:var(--color-bg);">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">✅ Truyện hoàn thành</h2>
            <a href="/hoan-thanh" class="btn btn-outline btn-sm">Xem tất cả</a>
        </div>
        <div class="grid grid-6">
            <?php foreach ($completed_stories['stories'] as $i => $story): ?>
                <?php hdk_get_story_card($story, $i); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Free Stories -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">🎁 Truyện miễn phí</h2>
            <a href="/truyen-free" class="btn btn-outline btn-sm">Xem tất cả</a>
        </div>
        <div class="grid grid-6">
            <?php foreach ($free_stories['stories'] as $i => $story): ?>
                <?php hdk_get_story_card($story, $i); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php get_footer(); ?>
