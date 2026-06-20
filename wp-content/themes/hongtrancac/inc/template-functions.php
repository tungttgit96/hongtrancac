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

function hdk_login_url($redirect_to = '') {
    $redirect_to = $redirect_to ?: home_url(add_query_arg([]));
    return add_query_arg('redirect_to', $redirect_to, home_url('/dang-nhap'));
}

function hdk_register_url($redirect_to = '') {
    $redirect_to = $redirect_to ?: home_url(add_query_arg([]));
    return add_query_arg('redirect_to', $redirect_to, home_url('/dang-ky'));
}

/**
 * Validate a username for the themed registration/login flow.
 * Restricts to lowercase letters, numbers, underscores and hyphens.
 * Spaces, dots, @ symbols and other special characters are rejected
 * so usernames are easy to type and login stays predictable.
 */
function hdk_validate_username_format($username) {
    return (bool) preg_match('/^[a-z0-9_-]+$/', $username);
}

/**
 * Perform a strict, consistent sanitize of the username used across
 * registration and login so both endpoints agree on the final value.
 */
function hdk_sanitize_username_input($username) {
    return sanitize_user((string) $username, true);
}

/**
 * Safe redirect wrapper that only allows redirects to the local site.
 * Falls back to the site home URL if the provided URL is external.
 */
function hdk_safe_redirect($location, $status = 302) {
    $safe = wp_validate_redirect($location, home_url('/'));
    wp_safe_redirect($safe, $status);
    exit;
}

function hdk_story_url($slug, $args = []) {
    $url = home_url('/' . ltrim((string)$slug, '/'));
    return $args ? add_query_arg($args, $url) : $url;
}

function hdk_page_url($path = '', $args = []) {
    $url = home_url('/' . ltrim((string)$path, '/'));
    return $args ? add_query_arg($args, $url) : $url;
}

function hdk_category_url($slug) {
    return hdk_page_url('the-loai/' . ltrim((string)$slug, '/'));
}

function hdk_story_has_audio($story) {
    return !empty($story->audio_url);
}

function hdk_get_story_card($story, $index = 0, $attrs = []) {
    $url = hdk_story_url($story->slug ?? '');
    $title = esc_html($story->title ?? '');
    $cover = $story->cover_url ?? get_template_directory_uri() . '/assets/img/placeholder.svg';
    $author = esc_html($story->author_name ?? '');
    $chapters = (int)($story->chapter_count ?? 0);
    $views = number_format((int)($story->total_views ?? 0));
    $views_label = sprintf('%s lượt xem', $views);
    $chapters_label = sprintf('%d chương', $chapters);
    $price_summary = class_exists('HDK_DB') ? HDK_DB::get_story_price_summary($story) : ['has_pricing' => false, 'label' => ''];
    $lazy = $index >= 8 ? ' loading="lazy"' : '';
    $variant = '';
    $extra_attrs = '';
    if (is_array($attrs)) {
        if (isset($attrs['variant'])) {
            $variant = $attrs['variant'];
            unset($attrs['variant']);
        }
        foreach ($attrs as $name => $value) {
            if ($value === false || $value === null) {
                continue;
            }
            $extra_attrs .= ' ' . esc_attr($name);
            if ($value !== true) {
                $extra_attrs .= '="' . esc_attr($value) . '"';
            }
        }
    } elseif (is_string($attrs)) {
        $extra_attrs = $attrs;
    }
    if ($variant === 'compact-new'): ?>
    <a href="<?php echo esc_url($url); ?>" class="card story-card story-card-compact motion-stagger" title="<?php echo $title; ?>"<?php echo $extra_attrs; ?>>
        <div class="card-img-wrap">
            <img src="<?php echo esc_url($cover); ?>" alt="<?php echo $title; ?>" class="card-img"<?php echo $lazy; ?>>
            <span class="card-views-overlay" aria-label="<?php echo esc_attr($views_label); ?>"><?php echo hdk_icon('eye'); ?> <?php echo $views; ?></span>
        </div>
        <div class="card-body">
            <h3 class="card-title"><?php echo $title; ?></h3>
            <div class="card-meta card-meta-row">
                <span class="story-card-status"><?php echo hdk_get_story_status_badge($story); ?></span>
                <span class="story-card-chapters" aria-label="<?php echo esc_attr($chapters_label); ?>"><?php echo hdk_icon('book-open'); ?> <?php echo $chapters; ?></span>
            </div>
        </div>
    </a>
    <?php else: ?>
    <a href="<?php echo esc_url($url); ?>" class="card story-card motion-stagger" title="<?php echo $title; ?>"<?php echo $extra_attrs; ?>>
        <div class="card-img-wrap">
            <img src="<?php echo esc_url($cover); ?>" alt="<?php echo $title; ?>" class="card-img"<?php echo $lazy; ?>>
            <span class="card-views-overlay" aria-label="<?php echo esc_attr($views_label); ?>"><?php echo hdk_icon('eye'); ?> <?php echo $views; ?></span>
        </div>
        <div class="card-body">
            <h3 class="card-title"><?php echo $title; ?></h3>
            <?php if ($author): ?>
                <div class="card-meta"><?php echo $author; ?></div>
            <?php endif; ?>
            <?php if (!empty($price_summary['has_pricing'])): ?>
                <div class="card-meta" style="margin-top:4px;">
                    <span style="color:var(--color-warning);"><?php echo hdk_icon('gem'); ?> <?php echo esc_html($price_summary['label']); ?></span>
                </div>
            <?php endif; ?>
            <div class="card-meta" style="display:flex;justify-content:space-between;margin-top:6px">
                <span><?php echo hdk_get_story_status_badge($story); ?></span>
                <span><?php echo $chapters; ?> chương</span>
            </div>
            <?php if (hdk_story_has_audio($story)): ?>
                <div class="card-meta" style="margin-top:6px;color:var(--color-primary);"><?php echo hdk_icon('headphones'); ?> Có audio</div>
            <?php endif; ?>
        </div>
    </a>
    <?php endif;
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
                            <a href="<?php echo esc_url(hdk_page_url('danh-sach-truyen')); ?>" class="btn btn-primary">Khám phá truyện</a>
                            <a href="<?php echo esc_url(hdk_page_url('bang-xep-hang')); ?>" class="btn btn-outline" style="border-color:var(--color-banner-text);color:var(--color-banner-text)">Bảng xếp hạng</a>
                        </div>
                    </div>
                    <div class="hero-visual" style="flex:0 0 240px;text-align:center;">
                        <div style="font-size:120px;line-height:1;"><?php echo hdk_icon('book-open', ['size' => '120px']); ?></div>
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
            <!-- Desktop banner -->
            <div class="banner-shell banner-desktop" data-banner-component>
                <button class="banner-nav banner-nav-prev" type="button" aria-label="Truyện trước"><?php echo hdk_icon('chevron-left'); ?></button>
                <div class="banner-grid">
                    <div class="banner-info">
                        <div class="banner-meta">
                            <span class="banner-kicker">Khám phá hôm nay</span>
                            <span class="banner-position">01 / <?php echo sprintf('%02d', count($stories)); ?></span>
                        </div>
                        <h1 class="banner-title"><?php echo esc_html($active->title); ?></h1>
                        <p class="banner-summary"><?php echo esc_html($active->summary_trimmed); ?></p>
                        <div class="banner-actions">
                            <a href="<?php echo esc_url($active->url); ?>" class="btn btn-primary banner-read-link">Đọc truyện</a>
                            <span class="banner-views"><?php echo number_format((int)($active->total_views ?? 0)); ?> lượt xem</span>
                        </div>
                    </div>

                    <div class="banner-cover">
                        <img src="<?php echo esc_url($active->cover_url); ?>"
                             alt="<?php echo esc_attr($active->title); ?>"
                             class="banner-cover-img">
                    </div>

                    <div class="banner-cards">
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
                                    <span class="banner-card-views"><?php echo hdk_icon('eye'); ?> <?php echo number_format((int)($story->total_views ?? 0)); ?></span>
                                </span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button class="banner-nav banner-nav-next" type="button" aria-label="Truyện sau"><?php echo hdk_icon('chevron-right'); ?></button>
            </div>

            <!-- Mobile banner -->
            <div class="banner-shell banner-mobile" data-banner-component>
                <div class="banner-mobile-stage">
                    <div class="banner-mobile-backdrop" style="<?php echo esc_attr('background-image:url("' . esc_url($active->cover_url) . '")'); ?>"></div>
                    <button class="banner-nav banner-nav-prev" type="button" aria-label="Truyện trước"><?php echo hdk_icon('chevron-left'); ?></button>
                    <div class="banner-cover">
                        <img src="<?php echo esc_url($active->cover_url); ?>"
                             alt="<?php echo esc_attr($active->title); ?>"
                             class="banner-cover-img">
                    </div>
                    <div class="banner-info">
                        <span class="banner-kicker">Truyện nổi bật</span>
                        <h1 class="banner-title"><?php echo esc_html($active->title); ?></h1>
                        <p class="banner-summary"><?php echo esc_html($active->summary_trimmed); ?></p>
                        <span class="banner-views"><?php echo number_format((int)($active->total_views ?? 0)); ?> lượt xem</span>
                        <a href="<?php echo esc_url($active->url); ?>" class="btn btn-primary banner-read-link">Đọc ngay</a>
                    </div>
                    <button class="banner-nav banner-nav-next" type="button" aria-label="Truyện sau"><?php echo hdk_icon('chevron-right'); ?></button>
                </div>

                <div class="banner-cards" aria-label="Danh sách truyện nổi bật">
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
                            <img src="<?php echo esc_url($story->cover_url); ?>" alt="" loading="lazy">
                            <span class="banner-card-rank"><?php echo sprintf('%02d', $index + 1); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php
}

function hdk_get_pagination($total_pages, $current_page = 1, $base_args = [], $options = []) {
    if ($total_pages <= 1) return;
    $base_args = array_filter($base_args, function($value) {
        return $value !== '' && $value !== null && $value !== 0 && $value !== '0';
    });
    $defaults = [
        'total' => 0,
        'per_page' => 20,
        'label' => '',
        'show_count' => true,
    ];
    $options = wp_parse_args($options, $defaults);
    $total = max(0, (int)$options['total']);
    $per_page = max(1, (int)$options['per_page']);
    $label = $options['label'];
    $show_count = $options['show_count'];
    $from = ($current_page - 1) * $per_page + 1;
    $to = min($current_page * $per_page, $total);
    ?>
    <nav class="pagination">
        <div class="pagination-links">
            <?php if ($current_page > 1): ?>
                <a href="<?php echo esc_url(add_query_arg(array_merge($base_args, ['page' => $current_page - 1]))); ?>" class="btn btn-ghost btn-sm page-prev"><?php echo hdk_icon('chevron-left'); ?> Trước</a>
            <?php else: ?>
                <span class="btn btn-ghost btn-sm page-prev disabled"><?php echo hdk_icon('chevron-left'); ?> Trước</span>
            <?php endif; ?>
            <?php
            $start = max(1, $current_page - 2);
            $end = min($total_pages, $current_page + 2);
            for ($i = $start; $i <= $end; $i++):
                $active = $i === $current_page ? 'btn-primary' : 'btn-ghost';
            ?>
                <a href="<?php echo esc_url(add_query_arg(array_merge($base_args, ['page' => $i]))); ?>" class="btn <?php echo $active; ?> btn-sm page-num"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($current_page < $total_pages): ?>
                <a href="<?php echo esc_url(add_query_arg(array_merge($base_args, ['page' => $current_page + 1]))); ?>" class="btn btn-ghost btn-sm page-next">Sau <?php echo hdk_icon('chevron-right'); ?></a>
            <?php else: ?>
                <span class="btn btn-ghost btn-sm page-next disabled">Sau <?php echo hdk_icon('chevron-right'); ?></span>
            <?php endif; ?>
        </div>
        <?php if ($show_count && $total > 0): ?>
            <div class="pagination-summary">Hiển thị <?php echo $from; ?> &ndash; <?php echo $to; ?> / <?php echo number_format($total); ?> <?php echo $label ? esc_html($label) : ''; ?></div>
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
                <span class="star" data-value="<?php echo $i; ?>" style="cursor:pointer;"><?php echo $i <= round($avg) ? hdk_icon('star', ['attrs' => ['fill' => 'currentColor', 'data-star' => 'filled']]) : hdk_icon('star'); ?></span>
            <?php endfor; ?>
        </div>
        <span style="font-size:var(--font-size-sm);color:var(--color-text-muted);">(<?php echo $avg; ?> - <?php echo number_format($total_ratings); ?> đánh giá)</span>
    </div>
    <?php
}

// ===== Female Novel Home Helpers =====

function hdk_fnh_get_latest_chapters($story_ids) {
    if (empty($story_ids)) return [];
    global $wpdb;
    $table = HDK_DB::table('hdk_chapters');
    $ids = array_map('intval', $story_ids);
    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT c.story_id, c.chapter_number, c.title, c.updated_at
         FROM $table c
         INNER JOIN (
             SELECT story_id, MAX(chapter_number) AS max_chapter
             FROM $table
             WHERE story_id IN ($placeholders) AND status = 'published'
             GROUP BY story_id
         ) latest ON c.story_id = latest.story_id AND c.chapter_number = latest.max_chapter",
        ...$ids
    ));
    $map = [];
    foreach ($results as $row) {
        $map[(int)$row->story_id] = $row;
    }
    return $map;
}

function hdk_fnh_clean_chapter_title($title, $chapter_number = 0) {
    if (empty($title)) return '';
    // Strip "Chương X: " or "Chương X - " prefix if already present in title
    $patterns = [
        '/^Chương\s+' . (int)$chapter_number . '\s*[:\-]\s*/iu',
        '/^Chương\s+' . (int)$chapter_number . '\b/iu',
    ];
    foreach ($patterns as $pattern) {
        $title = preg_replace($pattern, '', $title);
    }
    return trim($title);
}

function hdk_fnh_feature_card($story, $index = 0, $extra_attrs = '') {
    $url = hdk_story_url($story->slug ?? '');
    $title_raw = $story->title ?? '';
    $title = esc_html($title_raw);
    $cover = $story->cover_url ?: get_template_directory_uri() . '/assets/img/placeholder.svg';
    $placeholder = get_template_directory_uri() . '/assets/img/placeholder.svg';
    $chapters = (int)($story->chapter_count ?? 0);
    $views = number_format((int)($story->total_views ?? 0));
    $rating = isset($story->average_rating) ? round((float)$story->average_rating, 1) : 0;
    $genre = '';
    foreach (($story->categories ?? []) as $category) {
        if (!empty($category->name)) {
            $genre = $category->name;
            break;
        }
    }
    $lazy = $index >= 2 ? ' loading="lazy"' : '';
    $attrs_str = '';
    if (is_array($extra_attrs)) {
        foreach ($extra_attrs as $name => $value) {
            if ($value === false || $value === null) continue;
            $attrs_str .= ' ' . esc_attr($name);
            if ($value !== true) $attrs_str .= '="' . esc_attr($value) . '"';
        }
    } elseif (is_string($extra_attrs) && $extra_attrs !== '') {
        $attrs_str = ' ' . $extra_attrs;
    }
    ?>
    <a href="<?php echo esc_url($url); ?>" class="fnh-feature-card motion-stagger" title="<?php echo esc_attr($title_raw); ?>"<?php echo $attrs_str; ?>>
        <div class="fnh-feature-cover">
            <img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($title_raw); ?>" width="200" height="300" decoding="async"<?php echo $lazy; ?> onerror="this.src='<?php echo esc_url($placeholder); ?>'">
            <?php if ($rating > 0): ?>
            <span class="fnh-feature-rating"><?php echo hdk_icon('star', ['attrs' => ['fill' => 'currentColor']]); ?> <?php echo $rating; ?></span>
            <?php endif; ?>
            <span class="fnh-feature-cover-meta">
                <span><?php echo hdk_icon('eye'); ?> <?php echo $views; ?></span>
                <span><?php echo hdk_icon('book-open'); ?> <?php echo $chapters; ?></span>
            </span>
        </div>
        <div class="fnh-feature-body">
            <h3 class="fnh-feature-title"><?php echo $title; ?></h3>
            <div class="fnh-feature-status">
                <?php echo hdk_get_story_status_badge($story); ?>
                <?php if ($genre): ?>
                <span class="badge badge-primary fnh-feature-genre" title="<?php echo esc_attr($genre); ?>"><?php echo esc_html($genre); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </a>
    <?php
}

function hdk_fnh_hot_card($story, $rank = 1, $is_block = false) {
    $url = hdk_story_url($story->slug ?? '');
    $title_raw = $story->title ?? '';
    $title = esc_html($title_raw);
    $cover = $story->cover_url ?: get_template_directory_uri() . '/assets/img/placeholder.svg';
    $placeholder = get_template_directory_uri() . '/assets/img/placeholder.svg';
    $author = esc_html($story->author_name ?? '');
    $chapters = (int)($story->chapter_count ?? 0);
    $views = number_format((int)($story->total_views ?? 0));
    $summary = wp_trim_words($story->summary ?? '', 30, '…');
    if ($is_block):
    ?>
    <a href="<?php echo esc_url($url); ?>" class="fnh-hot-content" title="<?php echo esc_attr($title_raw); ?>">
        <div class="fnh-hot-cover">
            <img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($title_raw); ?>" width="110" height="165" decoding="async" onerror="this.src='<?php echo esc_url($placeholder); ?>'">
            <span class="fnh-hot-rank"><?php echo sprintf('%02d', $rank); ?></span>
        </div>
        <div class="fnh-hot-body">
            <h3 class="fnh-hot-title"><?php echo $title; ?></h3>
            <?php if ($author): ?>
            <div class="fnh-hot-author"><?php echo $author; ?></div>
            <?php endif; ?>
            <p class="fnh-hot-summary"><?php echo esc_html($summary); ?></p>
            <div class="fnh-hot-meta">
                <?php echo hdk_get_story_status_badge($story); ?>
                <span><?php echo hdk_icon('book-open'); ?> <?php echo $chapters; ?> chương</span>
                <span><?php echo hdk_icon('eye'); ?> <?php echo $views; ?></span>
            </div>
        </div>
    </a>
    <?php else: ?>
    <a href="<?php echo esc_url($url); ?>" class="fnh-hot-card motion-stagger" title="<?php echo esc_attr($title_raw); ?>">
        <div class="fnh-hot-cover">
            <img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($title_raw); ?>" width="110" height="165" decoding="async" onerror="this.src='<?php echo esc_url($placeholder); ?>'">
            <span class="fnh-hot-rank"><?php echo sprintf('%02d', $rank); ?></span>
        </div>
        <div class="fnh-hot-body">
            <h3 class="fnh-hot-title"><?php echo $title; ?></h3>
            <?php if ($author): ?>
            <div class="fnh-hot-author"><?php echo $author; ?></div>
            <?php endif; ?>
            <p class="fnh-hot-summary"><?php echo esc_html($summary); ?></p>
            <div class="fnh-hot-meta">
                <?php echo hdk_get_story_status_badge($story); ?>
                <span><?php echo hdk_icon('book-open'); ?> <?php echo $chapters; ?> chương</span>
                <span><?php echo hdk_icon('eye'); ?> <?php echo $views; ?></span>
            </div>
        </div>
    </a>
    <?php endif;
}

function hdk_fnh_latest_row($story, $chapter = null) {
    $url = hdk_story_url($story->slug ?? '');
    $title_raw = $story->title ?? '';
    $title = esc_html($title_raw);
    $cover = $story->cover_url ?: get_template_directory_uri() . '/assets/img/placeholder.svg';
    $placeholder = get_template_directory_uri() . '/assets/img/placeholder.svg';
    $chap_num = $chapter ? (int)$chapter->chapter_number : 0;
    $chap_title_raw = $chapter ? $chapter->title : '';
    $chap_title = hdk_fnh_clean_chapter_title($chap_title_raw, $chap_num);
    ?>
    <a href="<?php echo esc_url($url); ?>" class="fnh-latest-row motion-stagger" title="<?php echo esc_attr($title_raw); ?>">
        <img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($title_raw); ?>" class="fnh-latest-thumb" width="48" height="72" loading="lazy" decoding="async" onerror="this.src='<?php echo esc_url($placeholder); ?>'">
        <div class="fnh-latest-info">
            <h4 class="fnh-latest-title"><?php echo $title; ?></h4>
            <div class="fnh-latest-meta">
                <?php echo hdk_get_story_status_badge($story); ?>
                <?php if ($chap_num): ?>
                <span class="fnh-latest-chapter">Chương <?php echo $chap_num; ?><?php echo $chap_title ? ': ' . esc_html($chap_title) : ''; ?></span>
                <?php endif; ?>
            </div>
        </div>
    </a>
    <?php
}

function hdk_fnh_ranking_item($story, $rank) {
    $url = hdk_story_url($story->slug ?? '');
    $title_raw = $story->title ?? '';
    $title = esc_html($title_raw);
    $cover = $story->cover_url ?: get_template_directory_uri() . '/assets/img/placeholder.svg';
    $placeholder = get_template_directory_uri() . '/assets/img/placeholder.svg';
    $author = esc_html($story->author_name ?? '');
    $chapters = (int)($story->chapter_count ?? 0);
    $views = (int)($story->total_views ?? 0);
    $rank_class = $rank <= 3 ? ' fnh-rank-top' : '';
    ?>
    <a href="<?php echo esc_url($url); ?>" class="fnh-ranking-item<?php echo $rank_class; ?>" title="<?php echo esc_attr($title_raw); ?>">
        <span class="fnh-ranking-num"><?php echo sprintf('%02d', $rank); ?></span>
        <img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($title_raw); ?>" class="fnh-ranking-thumb" width="40" height="60" loading="lazy" decoding="async" onerror="this.src='<?php echo esc_url($placeholder); ?>'">
        <div class="fnh-ranking-info">
            <span class="fnh-ranking-title"><?php echo $title; ?></span>
            <span class="fnh-ranking-meta"><?php echo $author; ?> · <?php echo $chapters; ?> chương</span>
        </div>
        <span class="fnh-ranking-views"><?php echo hdk_icon('eye'); ?> <?php echo number_format($views); ?></span>
    </a>
    <?php
}

function hdk_get_category_icon_name($name, $slug = '') {
    // Map by slug first (exact match)
    $slug_map = [
        'de-cu' => 'crown',
        'ngon-tinh' => 'heart',
        'co-dai' => 'castle',
        'huyen-huyen' => 'sparkles',
        'truyen-tranh' => 'book-heart',
        'danh-muc' => 'grid-2x2',
        'xuyen-khong' => 'rocket',
        'hien-dai' => 'building',
        'he-thong' => 'cpu',
        'trong-sinh' => 'refresh-cw',
        'dien-van' => 'feather',
        'do-thi' => 'building-2',
        'kiem-hiep' => 'swords',
        'tien-hiep' => 'swords',
        'dam-my' => 'flower-2',
        'trinh-tham' => 'search',
        'kinh-di' => 'ghost',
        'linh-di' => 'ghost',
        'hai-huoc' => 'laugh',
        'lich-su' => 'scroll-text',
        'quan-su' => 'shield',
    ];
    if (!empty($slug) && isset($slug_map[$slug])) {
        return $slug_map[$slug];
    }
    // Fallback by name keyword matching
    $name_lower = mb_strtolower($name, 'UTF-8');
    $keyword_map = [
        'đề cử' => 'crown', 'ngôn tình' => 'heart', 'cổ đại' => 'castle',
        'huyền' => 'sparkles', 'truyện tranh' => 'book-heart', 'xuyên không' => 'rocket',
        'hiện đại' => 'building', 'hệ thống' => 'cpu', 'trọng sinh' => 'refresh-cw',
        'kiếm hiệp' => 'swords', 'tiên hiệp' => 'swords', 'đô thị' => 'building-2',
        'trinh thám' => 'search', 'kinh dị' => 'ghost', 'hài' => 'laugh',
        'lịch sử' => 'scroll-text', 'quân sự' => 'shield', 'đam mỹ' => 'flower-2',
        'linh dị' => 'ghost', 'sắc' => 'heart', 'sủng' => 'heart', 'điền văn' => 'feather',
    ];
    foreach ($keyword_map as $keyword => $icon) {
        if (mb_strpos($name_lower, $keyword) !== false) {
            return $icon;
        }
    }
    return 'book-open';
}

function hdk_fnh_quick_categories() {
    global $wpdb;
    $table = HDK_DB::table('hdk_categories');
    $cats = $wpdb->get_results("SELECT id, name, slug, story_count FROM $table WHERE story_count > 0 ORDER BY sort_order, story_count DESC LIMIT 30");

    if (empty($cats)) return;

    $by_slug = [];
    foreach ($cats as $c) { $by_slug[$c->slug] = $c; }

    // Build fixed 12-item layout
    // "Đề cử" links to /danh-sach-truyen, "Danh mục" links to /the-loai
    $items = [];

    // Slot 1: Đề cử (Recommendations)
    $items[] = (object)[
        'name' => 'Đề cử',
        'slug' => 'de-cu',
        'story_count' => 0,
        'url' => hdk_page_url('danh-sach-truyen'),
        'is_special' => true,
    ];

    // Slot 2-11: Match preferred slugs from DB (with fallback)
    $preferred = ['ngon-tinh', 'co-dai', 'huyen-huyen', 'truyen-tranh', 'xuyen-khong', 'trong-sinh', 'dam-my', 'do-thi', 'kiem-hiep', 'kinh-di'];
    foreach ($preferred as $slug) {
        if (isset($by_slug[$slug])) {
            $cat = $by_slug[$slug];
            $items[] = (object)[
                'name' => $cat->name,
                'slug' => $cat->slug,
                'story_count' => (int)$cat->story_count,
                'url' => hdk_category_url($cat->slug),
                'is_special' => false,
            ];
            unset($by_slug[$slug]);
        } else {
            // Fill slot with a fallback from top remaining
            $keys = array_keys($by_slug);
            if (!empty($keys)) {
                $fallback = $by_slug[$keys[0]];
                $items[] = (object)[
                    'name' => $fallback->name,
                    'slug' => $fallback->slug,
                    'story_count' => (int)$fallback->story_count,
                    'url' => hdk_category_url($fallback->slug),
                    'is_special' => false,
                ];
                unset($by_slug[$keys[0]]);
            }
        }
    }

    // Slot 12: Danh mục (All Categories)
    $items[] = (object)[
        'name' => 'Danh mục',
        'slug' => 'danh-muc',
        'story_count' => 0,
        'url' => hdk_page_url('the-loai'),
        'is_special' => true,
    ];

    ?>
    <div class="fnh-categories" role="navigation" aria-label="Danh mục thể loại">
        <?php foreach ($items as $cat):
            $icon = hdk_get_category_icon_name($cat->name, $cat->slug);
            ?>
            <a href="<?php echo esc_url($cat->url); ?>" class="fnh-cat-card motion-stagger" title="<?php echo esc_attr($cat->name); ?>">
                <span class="fnh-cat-icon"><?php echo hdk_icon($icon); ?></span>
                <span class="fnh-cat-name"><?php echo esc_html($cat->name); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
    <?php
}

function hdk_fnh_daily_claim_btn() {
    $daily_credits = (int)get_option('hdk_daily_credits', 10);
    if (is_user_logged_in()):
        ?>
        <button type="button" class="fnh-daily-btn" id="daily-claim-btn" onclick="claimDaily()">
            <?php echo hdk_icon('calendar'); ?> Điểm danh nhận <?php echo $daily_credits; ?> Linh Thạch
        </button>
        <?php
    else:
        ?>
        <a href="<?php echo esc_url(hdk_login_url(home_url('/'))); ?>" class="fnh-daily-btn fnh-daily-guest">
            <?php echo hdk_icon('log-in'); ?> Đăng nhập để điểm danh
        </a>
        <?php
    endif;
}

function hdk_fnh_grid_card($story, $index = 0) {
    $url = hdk_story_url($story->slug ?? '');
    $title_raw = $story->title ?? '';
    $title = esc_html($title_raw);
    $cover = $story->cover_url ?: get_template_directory_uri() . '/assets/img/placeholder.svg';
    $placeholder = get_template_directory_uri() . '/assets/img/placeholder.svg';
    $chapters = (int)($story->chapter_count ?? 0);
    $lazy = $index >= 4 ? ' loading="lazy"' : '';
    ?>
    <a href="<?php echo esc_url($url); ?>" class="fnh-grid-card motion-stagger" title="<?php echo esc_attr($title_raw); ?>">
        <div class="fnh-grid-cover">
            <img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($title_raw); ?>" width="200" height="300" decoding="async"<?php echo $lazy; ?> onerror="this.src='<?php echo esc_url($placeholder); ?>'">
        </div>
        <div class="fnh-grid-body">
            <h4 class="fnh-grid-title"><?php echo $title; ?></h4>
            <div class="fnh-grid-meta">
                <?php echo hdk_get_story_status_badge($story); ?>
                <span><?php echo $chapters; ?> chương</span>
            </div>
        </div>
    </a>
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
            return '<div class="story-badge story-badge-purchased">' . hdk_icon('gem') . ' Đã mua</div>';
        case 'history':
            $chap = (int)($data->chapter_number ?? 0);
            $time = mysql2date('H:i d/m/Y', $data->created_at ?? '');
            return '<div class="story-badge story-badge-history">Chương ' . $chap . ' — ' . $time . '</div>';
        default:
            return '';
    }
}
