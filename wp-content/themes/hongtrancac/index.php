<?php
/**
 * Template: Home Page
 */

get_header();

// ---- Female Home Data Queries ----

// Today's picks: 4 stories by average rating
$fnh_picks = HDK_Cache::get_home_stories([
    'per_page' => 4,
    'orderby' => 'average_rating',
    'order' => 'DESC',
    'exclude_hidden' => true
], 'home_female_picks');

// Hot stories: 2 most viewed
$fnh_hot = HDK_Cache::get_home_stories([
    'per_page' => 2,
    'orderby' => 'total_views',
    'order' => 'DESC',
    'exclude_hidden' => true
], 'home_female_hot');

// Latest updates: 8 stories by updated_at
$fnh_latest = HDK_Cache::get_home_stories([
    'per_page' => 8,
    'orderby' => 'updated_at',
    'order' => 'DESC',
    'exclude_hidden' => true
], 'home_female_latest');

// Completed stories: 8 stories
$fnh_completed = HDK_Cache::get_home_stories([
    'per_page' => 8,
    'status' => 'completed',
    'orderby' => 'updated_at',
    'order' => 'DESC',
    'exclude_hidden' => true
], 'home_female_completed');

// Ranking preload: day, week, month
$fnh_rank_day = HDK_Cache::get_home_ranking('views', 'day', 0, 1, 6, 'home_female_rank_day');
$fnh_rank_week = HDK_Cache::get_home_ranking('views', 'week', 0, 1, 6, 'home_female_rank_week');
$fnh_rank_month = HDK_Cache::get_home_ranking('views', 'month', 0, 1, 6, 'home_female_rank_month');

// Ranking fallback: all-time ranking if period data is empty
$fnh_rank_fallback = [];
if (empty($fnh_rank_day['stories']) && empty($fnh_rank_week['stories']) && empty($fnh_rank_month['stories'])) {
    $fnh_rank_fallback = HDK_DB::get_ranking('views', 'all', 0, 1, 6, true);
    if (!empty($fnh_rank_fallback['stories'])) {
        $fnh_rank_day['stories'] = $fnh_rank_fallback['stories'];
        $fnh_rank_week['stories'] = $fnh_rank_fallback['stories'];
        $fnh_rank_month['stories'] = $fnh_rank_fallback['stories'];
    }
}
// Per-panel fallback: if a specific period is empty, use hot stories
$fnh_rank_hot_fallback = !empty($fnh_hot['stories']) ? array_slice($fnh_hot['stories'], 0, 6) : [];
foreach (['day','week','month'] as $period) {
    $key = 'fnh_rank_' . $period;
    if (empty(${$key}['stories']) && !empty($fnh_rank_hot_fallback)) {
        ${$key}['stories'] = $fnh_rank_hot_fallback;
    }
}

// Reading stories (logged-in only)
$fnh_reading = [];
if (is_user_logged_in()) {
    $fnh_reading = HDK_DB::get_reading_stories(get_current_user_id());
    if (!is_array($fnh_reading)) $fnh_reading = [];
}

// Batch latest chapters for latest section
$latest_ids = array_map(function($s) { return (int)$s->id; }, $fnh_latest['stories'] ?? []);
$fnh_latest_chapters = hdk_fnh_get_latest_chapters($latest_ids);

?>

<?php hdk_get_hero_section(); ?>

<!-- Female Novel Home -->
<div class="female-novel-home">
    <div class="fnh-container">

        <!-- Quick Categories Bar -->
        <?php hdk_fnh_quick_categories(); ?>

        <div class="fnh-main">

            <!-- Đề cử hôm nay -->
            <?php if (!empty($fnh_picks['stories'])): ?>
            <section class="fnh-picks-block motion-reveal">
                <div class="fnh-picks-header">
                    <h2 class="fnh-picks-title"><?php echo hdk_icon('sparkles'); ?> Đề cử hôm nay</h2>
                    <a href="<?php echo esc_url(hdk_page_url('danh-sach-truyen')); ?>" class="fnh-section-link">Xem tất cả <?php echo hdk_icon('chevron-right'); ?></a>
                </div>
                <div class="fnh-feature-grid">
                    <?php foreach ($fnh_picks['stories'] as $i => $story): ?>
                        <?php hdk_fnh_feature_card($story, $i); ?>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Truyện hot -->
            <?php if (!empty($fnh_hot['stories'])): ?>
            <section class="fnh-section motion-reveal">
                <div class="fnh-section-header">
                    <h2 class="fnh-section-title"><?php echo hdk_icon('flame'); ?> Truyện hot</h2>
                    <a href="<?php echo esc_url(hdk_page_url('bang-xep-hang')); ?>" class="fnh-section-link">Bảng xếp hạng <?php echo hdk_icon('chevron-right'); ?></a>
                </div>
                <div class="fnh-hot-grid">
                    <?php foreach ($fnh_hot['stories'] as $i => $story): ?>
                        <?php hdk_fnh_hot_card($story, $i + 1); ?>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Mới cập nhật -->
            <?php if (!empty($fnh_latest['stories'])): ?>
            <section class="fnh-section motion-reveal">
                <div class="fnh-section-header">
                    <h2 class="fnh-section-title"><?php echo hdk_icon('clock'); ?> Mới cập nhật</h2>
                    <a href="<?php echo esc_url(hdk_page_url('danh-sach-truyen')); ?>" class="fnh-section-link">Xem thêm <?php echo hdk_icon('chevron-right'); ?></a>
                </div>
                <div class="fnh-latest-list">
                    <?php foreach ($fnh_latest['stories'] as $story):
                        $chap = $fnh_latest_chapters[(int)$story->id] ?? null;
                        hdk_fnh_latest_row($story, $chap);
                    endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Truyện hoàn thành -->
            <?php if (!empty($fnh_completed['stories'])): ?>
            <section class="fnh-section motion-reveal">
                <div class="fnh-section-header">
                    <h2 class="fnh-section-title"><?php echo hdk_icon('check-circle'); ?> Truyện hoàn thành</h2>
                    <a href="<?php echo esc_url(hdk_page_url('hoan-thanh')); ?>" class="fnh-section-link">Xem tất cả <?php echo hdk_icon('chevron-right'); ?></a>
                </div>
                <div class="fnh-grid-wrap">
                    <?php foreach ($fnh_completed['stories'] as $i => $story): ?>
                        <?php hdk_fnh_grid_card($story, $i); ?>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

        </div><!-- /fnh-main -->

        <aside class="fnh-sidebar motion-reveal">

            <!-- Đọc tiếp -->
            <?php if (!empty($fnh_reading)): ?>
            <div class="fnh-sidebar-block">
                <h3 class="fnh-sidebar-title"><?php echo hdk_icon('book-open'); ?> Đọc tiếp</h3>
                <div class="fnh-reading-list">
                    <?php foreach (array_slice($fnh_reading, 0, 5) as $story):
                        $chap_num = (int)($story->current_chapter ?? 0);
                        $progress = (int)($story->scroll_percent ?? 0);
                        $url = hdk_story_url($story->slug ?? '', ['chuong' => $chap_num]);
                    ?>
                        <a href="<?php echo esc_url($url); ?>" class="fnh-reading-item" title="<?php echo esc_attr(strip_tags($story->title ?? '')); ?>">
                            <img src="<?php echo esc_url($story->cover_url ?: get_template_directory_uri() . '/assets/img/placeholder.svg'); ?>" alt="<?php echo esc_attr(strip_tags($story->title ?? '')); ?>" width="44" height="66" loading="lazy" decoding="async" onerror="this.src='<?php echo esc_url(get_template_directory_uri() . '/assets/img/placeholder.svg'); ?>'">
                            <div class="fnh-reading-info">
                                <span class="fnh-reading-title"><?php echo esc_html($story->title ?? ''); ?></span>
                                <span class="fnh-reading-chapter">Chương <?php echo $chap_num; ?></span>
                                <span class="fnh-reading-bar"><i style="width:<?php echo $progress; ?>%"></i></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Top truyện -->
            <div class="fnh-sidebar-block">
                <h3 class="fnh-sidebar-title"><?php echo hdk_icon('crown'); ?> Top truyện</h3>
                <div class="fnh-rank-tabs" role="tablist" aria-label="Khoảng thời gian bảng xếp hạng">
                    <button class="fnh-rank-tab active" role="tab" aria-selected="true" aria-controls="fnh-rank-panel-day" data-fnh-period="day" id="fnh-rank-tab-day">Ngày</button>
                    <button class="fnh-rank-tab" role="tab" aria-selected="false" aria-controls="fnh-rank-panel-week" data-fnh-period="week" id="fnh-rank-tab-week" tabindex="-1">Tuần</button>
                    <button class="fnh-rank-tab" role="tab" aria-selected="false" aria-controls="fnh-rank-panel-month" data-fnh-period="month" id="fnh-rank-tab-month" tabindex="-1">Tháng</button>
                </div>
                <div class="fnh-rank-panels">
                    <div class="fnh-rank-panel" role="tabpanel" id="fnh-rank-panel-day" aria-labelledby="fnh-rank-tab-day" data-fnh-panel="day">
                        <?php if (!empty($fnh_rank_day['stories'])):
                            foreach ($fnh_rank_day['stories'] as $i => $story):
                                hdk_fnh_ranking_item($story, $i + 1);
                            endforeach;
                        else: ?>
                            <div class="fnh-empty">Chưa có dữ liệu</div>
                        <?php endif; ?>
                    </div>
                    <div class="fnh-rank-panel hidden" role="tabpanel" id="fnh-rank-panel-week" aria-labelledby="fnh-rank-tab-week" data-fnh-panel="week" hidden>
                        <?php if (!empty($fnh_rank_week['stories'])):
                            foreach ($fnh_rank_week['stories'] as $i => $story):
                                hdk_fnh_ranking_item($story, $i + 1);
                            endforeach;
                        else: ?>
                            <div class="fnh-empty">Chưa có dữ liệu</div>
                        <?php endif; ?>
                    </div>
                    <div class="fnh-rank-panel hidden" role="tabpanel" id="fnh-rank-panel-month" aria-labelledby="fnh-rank-tab-month" data-fnh-panel="month" hidden>
                        <?php if (!empty($fnh_rank_month['stories'])):
                            foreach ($fnh_rank_month['stories'] as $i => $story):
                                hdk_fnh_ranking_item($story, $i + 1);
                            endforeach;
                        else: ?>
                            <div class="fnh-empty">Chưa có dữ liệu</div>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="<?php echo esc_url(hdk_page_url('bang-xep-hang')); ?>" class="fnh-sidebar-more">Xem bảng xếp hạng đầy đủ <?php echo hdk_icon('chevron-right'); ?></a>
            </div>

            <!-- Điểm danh -->
            <div class="fnh-sidebar-block">
                <h3 class="fnh-sidebar-title"><?php echo hdk_icon('gift'); ?> Quà tặng</h3>
                <p class="fnh-sidebar-desc">Điểm danh mỗi ngày để nhận Linh Thạch đọc truyện miễn phí!</p>
                <?php hdk_fnh_daily_claim_btn(); ?>
            </div>

        </aside><!-- /fnh-sidebar -->

    </div><!-- /fnh-container -->
</div><!-- /female-novel-home -->

<?php get_footer(); ?>
