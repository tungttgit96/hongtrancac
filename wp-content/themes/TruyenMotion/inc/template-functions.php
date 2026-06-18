<?php
/**
 * Template helper functions for TruyenMotion
 */

/**
 * Output visual breadcrumbs based on current context.
 */
function hdk_visual_breadcrumbs() {
    global $hdk_story, $hdk_chapter, $hdk_category, $hdk_author;
    $items = [['name' => 'Trang chủ', 'url' => home_url('/')]];

    // Story/Chapter context
    if (is_object($hdk_story)) {
        $items[] = ['name' => $hdk_story->title, 'url' => home_url('/' . $hdk_story->slug)];
        if (is_object($hdk_chapter)) {
            $items[] = ['name' => 'Chương ' . (int)$hdk_chapter->chapter_number, 'url' => null];
        }
    }
    // Category context
    elseif (is_object($hdk_category)) {
        $items[] = ['name' => 'Thể loại', 'url' => home_url('/the-loai')];
        $items[] = ['name' => $hdk_category->name, 'url' => null];
    }
    // Author context
    elseif (is_object($hdk_author)) {
        $items[] = ['name' => 'Tác giả', 'url' => home_url('/tac-gia')];
        $items[] = ['name' => $hdk_author->name, 'url' => null];
    }
    // Blog archive / category / tag
    elseif (is_category() || is_tag() || is_tax()) {
        $term = get_queried_object();
        if (is_category()) {
            $items[] = ['name' => 'Tin tức', 'url' => home_url('/tin-tuc')];
        }
        if ($term && !is_wp_error($term)) {
            $items[] = ['name' => $term->name, 'url' => null];
        }
    }
    // Blog single post
    elseif (is_single()) {
        $items[] = ['name' => 'Tin tức', 'url' => home_url('/tin-tuc')];
        $items[] = ['name' => get_the_title(), 'url' => null];
    }
    // Search
    elseif (is_search()) {
        $items[] = ['name' => 'Tìm kiếm: ' . get_search_query(), 'url' => null];
    }
    // Page
    elseif (is_page()) {
        $items[] = ['name' => get_the_title(), 'url' => null];
    }
    // Archive
    elseif (is_archive()) {
        $items[] = ['name' => get_the_archive_title(), 'url' => null];
    }

    $count = count($items);
    if ($count <= 1) return;

    echo '<div class="container"><nav class="hdk-breadcrumbs" aria-label="Breadcrumb"><ol style="list-style:none;display:flex;flex-wrap:wrap;gap:4px;align-items:center;padding:0;margin:0;">';
    foreach ($items as $i => $item) {
        $last = ($i === $count - 1);
        if ($i > 0) {
            echo '<li style="color:var(--color-text-muted);"> / </li>';
        }
        echo '<li>';
        if (!$last && $item['url']) {
            echo '<a href="' . esc_url($item['url']) . '">' . esc_html($item['name']) . '</a>';
        } else {
            echo '<span>' . esc_html($item['name']) . '</span>';
        }
        echo '</li>';
    }
    echo '</ol></nav></div>';
}

/**
 * Output reading time estimate.
 */
function truyenmotion_reading_time($content = null) {
    if (!$content) $content = get_the_content();
    $word_count = str_word_count(wp_strip_all_tags($content));
    $minutes = ceil($word_count / 238);
    return sprintf(
        _n('%d phút đọc', '%d phút đọc', $minutes, 'truyenmotion'),
        $minutes
    );
}
