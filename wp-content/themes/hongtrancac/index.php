<?php
/**
 * Template: Home Page
 */

get_header();

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;

// Get new/updated stories (carousel: 16 stories for infinite loop)
$new_stories = HDK_Cache::get_home_stories(['per_page' => 16, 'orderby' => 'updated_at', 'order' => 'DESC', 'exclude_hidden' => true], 'home_new_marquee');

// Get hot stories (most views)
$hot_stories = HDK_Cache::get_home_stories(['per_page' => 12, 'orderby' => 'total_views', 'order' => 'DESC', 'exclude_hidden' => true], 'home_hot');

// Get completed stories
$completed_stories = HDK_Cache::get_home_stories(['per_page' => 12, 'status' => 'completed', 'orderby' => 'updated_at', 'order' => 'DESC', 'exclude_hidden' => true], 'home_completed');

// Get free stories
$free_stories = HDK_Cache::get_home_stories(['per_page' => 12, 'is_free' => 1, 'orderby' => 'total_views', 'order' => 'DESC', 'exclude_hidden' => true], 'home_free');

global $wpdb;
$audio_stories = $wpdb->get_results(
    "SELECT * FROM " . HDK_DB::table('hdk_stories') . "
     WHERE audio_url IS NOT NULL AND audio_url <> '' AND title <> '' AND is_featured_hidden = 0
     ORDER BY updated_at DESC LIMIT 6"
);
foreach ($audio_stories as $audio_story) {
    $audio_story->author_name = HDK_DB::get_author_name($audio_story->author_id);
    $audio_story->categories = HDK_DB::get_story_categories($audio_story->id);
    $audio_story->chapter_count = (int)$audio_story->total_chapters;
}

?>

<?php hdk_get_hero_section(); ?>

<!-- New Stories -->
<section class="section motion-reveal">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?php echo hdk_icon('sparkles'); ?> Truyện mới cập nhật</h2>
            <a href="<?php echo esc_url(hdk_page_url('danh-sach-truyen')); ?>" class="btn btn-outline btn-sm">Xem tất cả</a>
        </div>
        <div class="home-new-marquee">
            <div class="home-new-track">
                <div class="home-new-group">
                    <?php foreach ($new_stories['stories'] as $i => $story): ?>
                        <?php hdk_get_story_card($story, $i, ['variant' => 'compact-new']); ?>
                    <?php endforeach; ?>
                </div>
                <div class="home-new-group" aria-hidden="true">
                    <?php foreach ($new_stories['stories'] as $i => $story): ?>
                        <?php hdk_get_story_card($story, $i + 16, ['variant' => 'compact-new', 'tabindex' => '-1', 'aria-hidden' => 'true']); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Hot Stories -->
<section class="section motion-reveal" style="background:var(--color-bg);">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?php echo hdk_icon('flame'); ?> Truyện hot nhất</h2>
            <a href="<?php echo esc_url(hdk_page_url('bang-xep-hang')); ?>" class="btn btn-outline btn-sm">Bảng xếp hạng</a>
        </div>
        <div class="grid grid-6 home-story-grid">
            <?php foreach ($hot_stories['stories'] as $i => $story): ?>
                <?php hdk_get_story_card($story, $i); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Completed Stories -->
<section class="section motion-reveal" style="background:var(--color-bg);">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?php echo hdk_icon('check-circle'); ?> Truyện hoàn thành</h2>
            <a href="<?php echo esc_url(hdk_page_url('hoan-thanh')); ?>" class="btn btn-outline btn-sm">Xem tất cả</a>
        </div>
        <div class="grid grid-6 home-story-grid">
            <?php foreach ($completed_stories['stories'] as $i => $story): ?>
                <?php hdk_get_story_card($story, $i); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Free Stories -->
<section class="section motion-reveal">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?php echo hdk_icon('gift'); ?> Truyện miễn phí</h2>
            <a href="<?php echo esc_url(hdk_page_url('truyen-free')); ?>" class="btn btn-outline btn-sm">Xem tất cả</a>
        </div>
        <div class="grid grid-6 home-story-grid">
            <?php foreach ($free_stories['stories'] as $i => $story): ?>
                <?php hdk_get_story_card($story, $i); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if (!empty($audio_stories)): ?>
<!-- Audio Stories -->
<section class="section motion-reveal audio-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?php echo hdk_icon('headphones'); ?> Truyện audio</h2>
            <a href="<?php echo esc_url(hdk_page_url('danh-sach-truyen', ['audio' => '1'])); ?>" class="btn btn-outline btn-sm">Nghe thêm</a>
        </div>
        <div class="audio-story-strip">
            <?php foreach ($audio_stories as $story): ?>
                <article class="audio-story-card">
                    <img src="<?php echo esc_url($story->cover_url ?: get_template_directory_uri() . '/assets/img/placeholder.svg'); ?>" alt="<?php echo esc_attr($story->title); ?>">
                    <div>
                        <h3><?php echo esc_html($story->title); ?></h3>
                        <p><?php echo esc_html(($story->audio_title ?? '') ?: 'Bản audio'); ?><?php echo !empty($story->audio_duration) ? ' · ' . esc_html($story->audio_duration) : ''; ?></p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm hdk-audio-play"
                            data-audio-src="<?php echo esc_url($story->audio_url); ?>"
                            data-audio-title="<?php echo esc_attr(($story->audio_title ?? '') ?: $story->title); ?>"
                            data-story-title="<?php echo esc_attr($story->title); ?>"
                            data-story-url="<?php echo esc_url(hdk_story_url($story->slug)); ?>">
                        <?php echo hdk_icon('play'); ?>
                    </button>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Editor Picks -->
<?php
$editor_picks = HDK_Cache::get_home_stories(['orderby' => 'average_rating', 'order' => 'DESC', 'per_page' => 6, 'exclude_hidden' => true], 'home_editor');
if (!empty($editor_picks['stories'])): ?>
<section class="section motion-reveal">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?php echo hdk_icon('star'); ?> Đề cử biên tập</h2>
        </div>
        <div class="grid grid-6 home-story-grid">
            <?php foreach ($editor_picks['stories'] as $i => $story): ?>
                <?php hdk_get_story_card($story, $i); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Weekly Hot -->
<?php
$weekly_hot = HDK_Cache::get_home_ranking('views', 'week', 0, 1, 6, 'home_weekly');
if (!empty($weekly_hot['stories'])): ?>
<section class="section motion-reveal" style="background:var(--color-bg);">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?php echo hdk_icon('flame'); ?> Hot tuần này</h2>
            <a href="<?php echo esc_url(hdk_page_url('bang-xep-hang')); ?>" class="btn btn-outline btn-sm">Xem thêm <?php echo hdk_icon('arrow-right'); ?></a>
        </div>
        <div class="grid grid-6 home-story-grid">
            <?php foreach ($weekly_hot['stories'] as $i => $story): ?>
                <?php hdk_get_story_card($story, $i); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php get_footer(); ?>
