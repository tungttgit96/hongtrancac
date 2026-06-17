<?php
/**
 * Template Name: Thể Loại
 * Page: All categories listing - MotionSites card style
 */

global $wpdb;
$categories = $wpdb->get_results("SELECT * FROM " . HDK_DB::table('hdk_categories') . " ORDER BY sort_order");

// Category icon mapping based on common Vietnamese genre names
function hdk_get_category_icon($name) {
    $name_lower = mb_strtolower($name);
    $icons = [
        'tiên hiệp' => '⚔️', 'huyền huyễn' => '🔮', 'kiếm hiệp' => '🗡️',
        'đô thị' => '🏙️', 'dị giới' => '🌌', 'trọng sinh' => '🔄',
        'xuyên không' => '🚀', 'linh dị' => '👻', 'mạt thế' => '💀',
        'ngôn tình' => '💕', 'sắc' => '🌶️', 'quân sự' => '🎖️',
        'lịch sử' => '📜', 'hài hước' => '😂', 'trinh thám' => '🔍',
        'võng du' => '🎮', 'khoa huyễn' => '🤖', 'đam mỹ' => '💝',
        'bách hợp' => '🌺', 'cung đấu' => '👑', 'hệ thống' => '⚙️',
        'điền văn' => '🌾', 'phương tây' => '🏰', 'dị năng' => '⚡',
        'huyền nghi' => '🧩', 'viễn tưởng' => '🛸', 'light novel' => '📘',
        'truyện teen' => '💫', 'ngược' => '💔', 'sủng' => '🌟',
        'nữ cường' => '💪', 'nam chính' => '🦸',
    ];
    foreach ($icons as $key => $icon) {
        if (mb_strpos($name_lower, $key) !== false) return $icon;
    }
    return '📚';
}

get_header();
?>

<div class="container page-shell">
    <div class="section-header">
        <h1 class="section-title">Tất cả thể loại</h1>
        <span class="badge badge-primary"><?php echo count($categories); ?> thể loại</span>
    </div>

    <div class="category-grid">
        <?php foreach ($categories as $i => $cat):
            $icon = hdk_get_category_icon($cat->name);
        ?>
            <a href="<?php echo esc_url(hdk_category_url($cat->slug)); ?>" class="category-card">
                <div class="category-card-icon"><?php echo $icon; ?></div>
                <div class="category-card-content">
                    <h3 class="category-card-title"><?php echo esc_html($cat->name); ?></h3>
                    <?php if (!empty($cat->description)): ?>
                        <p class="category-card-desc"><?php echo esc_html($cat->description); ?></p>
                    <?php endif; ?>
                    <span class="category-card-count"><?php echo $cat->story_count; ?> truyện</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php get_footer(); ?>
