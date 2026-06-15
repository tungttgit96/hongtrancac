<?php
/**
 * Template helper functions for Hồng Trần Các
 */

function hdk_get_story_status_badge($story) {
    $status = $story->status ?? 'ongoing';
    $labels = [
        'ongoing' => ['text' => 'Đang ra', 'class' => 'badge-primary'],
        'completed' => ['text' => 'Hoàn thành', 'class' => 'badge-success'],
        'dropped' => ['text' => 'Ngừng', 'class' => 'badge-danger'],
    ];
    $label = $labels[$status] ?? $labels['ongoing'];
    return sprintf('<span class="badge %s">%s</span>', $label['class'], $label['text']);
}

function hdk_get_story_card($story, $index = 0) {
    $url = home_url('/' . ($story->slug ?? ''));
    $title = esc_html($story->title ?? '');
    $cover = $story->cover_url ?? get_template_directory_uri() . '/assets/img/placeholder.svg';
    $author = esc_html($story->author_name ?? '');
    $chapters = (int)($story->chapter_count ?? 0);
    $views = number_format((int)($story->total_views ?? 0));
    $chapter_price = (int)($story->chapter_price ?? 0);
    $full_price = (int)($story->full_price ?? 0);
    $free_chapters = (int)($story->free_chapters ?? 0);
    $has_pricing = ($chapter_price > 0 || $full_price > 0 || $free_chapters > 0);
    $lazy = $index >= 8 ? ' loading="lazy"' : '';
    ?>
    <a href="<?php echo esc_url($url); ?>" class="card story-card" title="<?php echo $title; ?>">
        <img src="<?php echo esc_url($cover); ?>" alt="<?php echo $title; ?>" class="card-img"<?php echo $lazy; ?>>
        <div class="card-body">
            <h3 class="card-title"><?php echo $title; ?></h3>
            <?php if ($author): ?>
                <div class="card-meta"><?php echo $author; ?></div>
            <?php endif; ?>
            <?php if ($has_pricing): ?>
                <div class="card-meta" style="margin-top:4px;">
                    <span style="color:var(--color-warning);">💎 <?php echo $free_chapters; ?> chương free · <?php echo $chapter_price; ?> hạt/chương<?php echo $full_price > 0 ? ' · Full ' . $full_price . ' hạt' : ''; ?></span>
                </div>
            <?php endif; ?>
            <div class="card-meta" style="display:flex;justify-content:space-between;margin-top:6px">
                <span><?php echo hdk_get_story_status_badge($story); ?></span>
                <span><?php echo $chapters; ?> chương</span>
            </div>
            <div class="card-meta" style="text-align:right">👁 <?php echo $views; ?></div>
        </div>
    </a>
    <?php
}

function hdk_get_hero_section() {
    global $wpdb;

    $saved_ids = get_option('hdk_home_banner_story_ids', []);
    $stories_table = HDK_DB::table('hdk_stories');
    $stories = [];
    $placeholder = get_template_directory_uri() . '/assets/img/placeholder.svg';
    $has_manual_banner = !empty($saved_ids) && is_array($saved_ids);

    if ($has_manual_banner) {
        $ids = array_values(array_filter(array_map('intval', $saved_ids), function($id) {
            return $id > 0;
        }));

        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT id, title, slug, cover_url, summary, total_views FROM $stories_table WHERE id IN ($placeholders)",
                $ids
            ));

            foreach ($ids as $id) {
                foreach ($results as $result) {
                    if ((int)$result->id === $id) {
                        $stories[] = $result;
                        break;
                    }
                }
            }
        }
    }

    if (!$has_manual_banner && count($stories) < 6) {
        $existing_ids = array_map(function($story) {
            return (int)$story->id;
        }, $stories);
        $limit = 6 - count($stories);
        $where = 'WHERE is_featured_hidden = 0 AND title <> \'\' AND LENGTH(title) >= 3';

        if (!empty($existing_ids)) {
            $where .= ' AND id NOT IN (' . implode(',', array_map('intval', $existing_ids)) . ')';
        }

        $fallback = $wpdb->get_results($wpdb->prepare(
            "SELECT id, title, slug, cover_url, summary, total_views FROM $stories_table $where ORDER BY total_views DESC LIMIT %d",
            $limit
        ));
        $stories = array_merge($stories, $fallback);
    }

    foreach ($stories as $story) {
        if (empty($story->cover_url)) {
            $story->cover_url = $placeholder;
        }
        $story->url = home_url('/' . ltrim($story->slug ?? '', '/'));
        $story->summary_trimmed = wp_trim_words($story->summary ?? '', 30, '…');
    }

    if (empty($stories)) {
        ?>
        <section class="hero" style="background:linear-gradient(135deg, var(--color-hero-bg), #362336); color:var(--color-banner-text); padding:48px 0;">
            <div class="container">
                <div class="hero-content" style="display:flex;align-items:center;gap:32px;flex-wrap:wrap;">
                    <div class="hero-text" style="flex:1;min-width:280px;">
                        <h1 style="font-size:var(--font-size-3xl);font-weight:700;margin-bottom:12px;">Hồng Trần Các</h1>
                        <p style="font-size:var(--font-size-lg);opacity:0.9;margin-bottom:20px;max-width:560px;">
                            Nền tảng đọc truyện chữ online. Hàng ngàn truyện hay, cập nhật liên tục mỗi ngày.
                        </p>
                        <div style="display:flex;gap:12px;flex-wrap:wrap;">
                            <a href="/danh-sach-truyen" class="btn btn-primary">Khám phá truyện</a>
                            <a href="/bang-xep-hang" class="btn btn-outline" style="border-color:var(--color-banner-text);color:var(--color-banner-text)">Bảng xếp hạng</a>
                        </div>
                    </div>
                    <div class="hero-visual" style="flex:0 0 240px;text-align:center;">
                        <div style="font-size:120px;line-height:1;">📚</div>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return;
    }

    $active = $stories[0];
    ?>
    <section class="hero-banner">
        <div class="container">
            <div class="banner-shell">
                <div class="banner-grid">
                    <div class="banner-info">
                        <h1 class="banner-title"><?php echo esc_html($active->title); ?></h1>
                        <p class="banner-summary"><?php echo esc_html($active->summary_trimmed); ?></p>
                        <div class="banner-actions">
                            <a href="<?php echo esc_url($active->url); ?>" class="btn btn-primary banner-read-link">Đọc truyện</a>
                            <span class="banner-views"><?php echo number_format((int)($active->total_views ?? 0)); ?> lượt xem</span>
                        </div>
                    </div>

                    <div class="banner-cover" id="banner-cover-wrapper">
                        <img src="<?php echo esc_url($active->cover_url); ?>"
                             alt="<?php echo esc_attr($active->title); ?>"
                             class="banner-cover-img"
                             id="banner-cover-img">
                    </div>

                    <div class="banner-cards" id="banner-cards">
                        <?php foreach ($stories as $index => $story): ?>
                            <button class="banner-card <?php echo $index === 0 ? 'active' : ''; ?>"
                                    type="button"
                                    data-index="<?php echo (int)$index; ?>"
                                    data-title="<?php echo esc_attr($story->title); ?>"
                                    data-summary="<?php echo esc_attr($story->summary_trimmed); ?>"
                                    data-url="<?php echo esc_url($story->url); ?>"
                                    data-cover="<?php echo esc_url($story->cover_url); ?>"
                                    data-views="<?php echo (int)($story->total_views ?? 0); ?>"
                                    aria-label="<?php echo esc_attr('Hiển thị truyện ' . $story->title); ?>">
                                <img src="<?php echo esc_url($story->cover_url); ?>" alt="<?php echo esc_attr($story->title); ?>">
                                <span class="banner-card-rank"><?php echo sprintf('%02d', $index + 1); ?></span>
                                <span class="banner-card-overlay">
                                    <span class="banner-card-title"><?php echo esc_html($story->title); ?></span>
                                    <span class="banner-card-views"><?php echo number_format((int)($story->total_views ?? 0)); ?> lượt đọc</span>
                                </span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php
}

function hdk_get_pagination($total_pages, $current_page = 1, $base_args = []) {
    if ($total_pages <= 1) return;
    $base_args = array_filter($base_args, function($value) {
        return $value !== '' && $value !== null && $value !== 0 && $value !== '0';
    });
    ?>
    <nav class="pagination" style="display:flex;justify-content:center;align-items:center;gap:8px;padding:24px 0;flex-wrap:wrap;">
        <?php if ($current_page > 1): ?>
            <a href="<?php echo esc_url(add_query_arg(array_merge($base_args, ['page' => $current_page - 1]))); ?>" class="btn btn-ghost btn-sm">&laquo; Trước</a>
        <?php endif; ?>
        <?php
        $start = max(1, $current_page - 2);
        $end = min($total_pages, $current_page + 2);
        for ($i = $start; $i <= $end; $i++):
            $active = $i === $current_page ? 'btn-primary' : 'btn-ghost';
        ?>
            <a href="<?php echo esc_url(add_query_arg(array_merge($base_args, ['page' => $i]))); ?>" class="btn <?php echo $active; ?> btn-sm"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($current_page < $total_pages): ?>
            <a href="<?php echo esc_url(add_query_arg(array_merge($base_args, ['page' => $current_page + 1]))); ?>" class="btn btn-ghost btn-sm">Sau &raquo;</a>
        <?php endif; ?>
    </nav>
    <?php
}

function hdk_get_rating_widget($story_id = 0, $current_rating = 0, $total_ratings = 0) {
    $avg = $total_ratings > 0 ? round($current_rating / $total_ratings, 1) : 0;
    ?>
    <div class="rating-widget" style="display:flex;align-items:center;gap:8px;" data-story-id="<?php echo (int)$story_id; ?>">
        <div class="stars" style="display:flex;gap:2px;color:var(--color-rating);font-size:20px;">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="star" data-value="<?php echo $i; ?>" style="cursor:pointer;"><?php echo $i <= round($avg) ? '★' : '☆'; ?></span>
            <?php endfor; ?>
        </div>
        <span style="font-size:var(--font-size-sm);color:var(--color-text-muted);">(<?php echo $avg; ?> - <?php echo number_format($total_ratings); ?> đánh giá)</span>
    </div>
    <?php
}

function hdk_get_story_card_badge($type, $data = null) {
    switch ($type) {
        case 'reading':
            $chap = (int)($data->current_chapter ?? 0);
            $pct = (int)($data->scroll_percent ?? 0);
            return sprintf(
                '<div class="story-badge story-badge-reading">Đọc tiếp chương %d<span class="badge-progress" style="width:%d%%"></span></div>',
                $chap, $pct
            );
        case 'purchased':
            return '<div class="story-badge story-badge-purchased">💎 Đã mua</div>';
        case 'history':
            $chap = (int)($data->chapter_number ?? 0);
            $time = mysql2date('H:i d/m/Y', $data->created_at ?? '');
            return '<div class="story-badge story-badge-history">Chương ' . $chap . ' — ' . $time . '</div>';
        default:
            return '';
    }
}
