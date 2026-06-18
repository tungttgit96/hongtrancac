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
        'tiên hiệp' => 'swords', 'huyền huyễn' => 'sparkles', 'kiếm hiệp' => 'sword',
        'đô thị' => 'building-2', 'dị giới' => 'globe', 'trọng sinh' => 'refresh-ccw',
        'xuyên không' => 'rocket', 'linh dị' => 'ghost', 'mạt thế' => 'skull',
        'ngôn tình' => 'heart', 'sắc' => 'flame', 'quân sự' => 'award',
        'lịch sử' => 'scroll-text', 'hài hước' => 'laugh', 'trinh thám' => 'search',
        'võng du' => 'gamepad-2', 'khoa huyễn' => 'bot', 'đam mỹ' => 'heart',
        'bách hợp' => 'flower-2', 'cung đấu' => 'crown', 'hệ thống' => 'settings',
        'điền văn' => 'wheat', 'phương tây' => 'castle', 'dị năng' => 'zap',
        'huyền nghi' => 'puzzle', 'viễn tưởng' => 'satellite', 'light novel' => 'book',
        'truyện teen' => 'sparkles', 'ngược' => 'heart-crack', 'sủng' => 'star',
        'nữ cường' => 'shield', 'nam chính' => 'shield',
    ];
    foreach ($icons as $key => $icon) {
        if (mb_strpos($name_lower, $key) !== false) return $icon;
    }
    return 'book';
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
                <div class="category-card-icon"><?php echo hdk_icon($icon); ?></div>
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
