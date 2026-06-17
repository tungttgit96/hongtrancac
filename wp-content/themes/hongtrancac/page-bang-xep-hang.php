<?php
/**
 * Template Name: Bảng Xếp Hạng
 * Page: Ranking page with metric and period filters
 */

$metric = sanitize_text_field($_GET['metric'] ?? 'views');
$period = sanitize_text_field($_GET['period'] ?? 'all');
$category = (int)($_GET['category'] ?? 0);
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$valid_metrics = ['views', 'favorites', 'ratings'];
$valid_periods = ['day', 'week', 'month', 'year', 'all'];
if (!in_array($metric, $valid_metrics)) $metric = 'views';
if (!in_array($period, $valid_periods)) $period = 'all';

$result = HDK_DB::get_ranking($metric, $period, $category, $page);

global $wpdb;
$categories = $wpdb->get_results("SELECT * FROM " . HDK_DB::table('hdk_categories') . " ORDER BY sort_order");

$metric_labels = ['views' => 'Lượt xem', 'favorites' => 'Yêu thích', 'ratings' => 'Đánh giá'];
$period_labels = ['day' => 'Hôm nay', 'week' => 'Tuần này', 'month' => 'Tháng này', 'year' => 'Năm nay', 'all' => 'Tất cả'];

get_header();
?>

<div class="container page-shell">
    <h1 class="section-title" style="margin-bottom:16px;">🏆 Bảng xếp hạng</h1>

    <!-- Filter Tabs -->
    <div style="background:var(--color-bg);border-radius:var(--radius-md);padding:16px;border:1px solid var(--color-border);margin-bottom:20px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
        <span style="font-weight:600;margin-right:8px;font-size:var(--font-size-sm);">Xếp theo:</span>
        <?php foreach ($valid_metrics as $m): ?>
            <a href="?metric=<?php echo $m; ?>&period=<?php echo $period; ?>&category=<?php echo $category; ?>"
               class="btn <?php echo $metric === $m ? 'btn-primary' : 'btn-ghost'; ?> btn-sm"><?php echo $metric_labels[$m]; ?></a>
        <?php endforeach; ?>
    </div>

    <div style="background:var(--color-bg);border-radius:var(--radius-md);padding:16px;border:1px solid var(--color-border);margin-bottom:20px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
        <span style="font-weight:600;margin-right:8px;font-size:var(--font-size-sm);">Thời gian:</span>
        <?php foreach ($valid_periods as $p): ?>
            <a href="?metric=<?php echo $metric; ?>&period=<?php echo $p; ?>&category=<?php echo $category; ?>"
               class="btn <?php echo $period === $p ? 'btn-primary' : 'btn-ghost'; ?> btn-sm"><?php echo $period_labels[$p]; ?></a>
        <?php endforeach; ?>
    </div>

    <!-- Category filter -->
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px;">
        <span style="font-weight:600;font-size:var(--font-size-sm);align-self:center;min-width:60px;">Thể loại:</span>
        <a href="?metric=<?php echo $metric; ?>&period=<?php echo $period; ?>&category=0"
           class="badge <?php echo $category === 0 ? 'badge-primary' : ''; ?>"
           style="text-decoration:none;cursor:pointer;padding:6px 12px;">Tất cả</a>
        <?php foreach ($categories as $cat): ?>
            <a href="?metric=<?php echo $metric; ?>&period=<?php echo $period; ?>&category=<?php echo $cat->id; ?>"
               class="badge <?php echo $category === $cat->id ? 'badge-primary' : ''; ?>"
               style="text-decoration:none;cursor:pointer;padding:6px 12px;"><?php echo esc_html($cat->name); ?></a>
        <?php endforeach; ?>
    </div>

    <!-- Ranking List -->
    <div style="background:var(--color-bg);border-radius:var(--radius-md);border:1px solid var(--color-border);overflow:hidden;">
        <?php foreach ($result['stories'] as $i => $story):
            $rank = ($page - 1) * 20 + $i + 1;
            $medal = $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : '#'.$rank));
        ?>
        <div class="story-list-row">
            <div style="font-size:var(--font-size-xl);font-weight:700;min-width:50px;text-align:center;"><?php echo $medal; ?></div>
            <a href="<?php echo esc_url(hdk_story_url($story->slug)); ?>" style="flex:0 0 60px;">
                <img src="<?php echo esc_url($story->cover_url); ?>" alt="<?php echo esc_attr($story->title); ?>"
                     style="width:60px;height:80px;object-fit:cover;border-radius:4px;">
            </a>
            <div style="flex:1;min-width:0;">
                <a href="<?php echo esc_url(hdk_story_url($story->slug)); ?>" style="font-weight:600;color:var(--color-text-primary);display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <?php echo esc_html($story->title); ?>
                </a>
                <div style="font-size:var(--font-size-xs);color:var(--color-text-muted);"><?php echo esc_html($story->author_name); ?></div>
                <div style="margin-top:4px;">
                    <?php echo hdk_get_story_status_badge($story); ?>
                </div>
            </div>
            <div style="text-align:right;min-width:100px;">
                <div style="font-weight:700;font-size:var(--font-size-lg);color:var(--color-primary);">
                    <?php
                    if ($metric === 'views') echo number_format($story->total_views) . ' 👁';
                    elseif ($metric === 'favorites') echo number_format($story->total_favorites) . ' ❤️';
                    else echo $story->average_rating . ' ⭐ (' . $story->total_ratings . ')';
                    ?>
                </div>
                <div style="font-size:var(--font-size-xs);color:var(--color-text-muted);"><?php echo $story->total_chapters; ?> chương</div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php hdk_get_pagination($result['pages'], $page, [
        'metric' => $metric !== 'views' ? $metric : '',
        'period' => $period !== 'all' ? $period : '',
        'category' => $category,
    ]); ?>
</div>

<?php get_footer(); ?>
