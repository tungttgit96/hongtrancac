<?php
/**
 * Template helper functions for Hạt Đậu Khả Ái
 */

function hdk_get_story_status_badge($story) {
    $status = $story->status ?? 'ongoing';
    $labels = [
        'ongoing'  => ['text' => 'Đang ra', 'class' => 'badge-primary'],
        'completed' => ['text' => 'Hoàn thành', 'class' => 'badge-success'],
        'dropped'  => ['text' => 'Ngừng', 'class' => 'badge-danger'],
    ];
    $label = $labels[$status] ?? $labels['ongoing'];
    return sprintf('<span class="badge %s">%s</span>', $label['class'], $label['text']);
}

function hdk_get_story_card($story, $index = 0) {
    $url = home_url('/' . ($story->slug ?? ''));
    $title = esc_html($story->title ?? '');
    $cover = $story->cover_url ?? get_template_directory_uri() . '/assets/img/placeholder.png';
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
                    <span style="color:#F59E0B;">💎 <?php echo $free_chapters; ?> chương free · <?php echo $chapter_price; ?> hạt/chương<?php echo $full_price > 0 ? ' · Full ' . $full_price . ' hạt' : ''; ?></span>
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
    ?>
    <section class="hero" style="background:linear-gradient(135deg, var(--color-hero-bg), #5B1500); color:#FFF; padding:48px 0;">
        <div class="container">
            <div class="hero-content" style="display:flex;align-items:center;gap:32px;flex-wrap:wrap;">
                <div class="hero-text" style="flex:1;min-width:280px;">
                    <h1 style="font-size:var(--font-size-3xl);font-weight:700;margin-bottom:12px;">
                        Hồng Trần Các
                    </h1>
                    <p style="font-size:var(--font-size-lg);opacity:0.9;margin-bottom:20px;max-width:560px;">
                        Nền tảng đọc truyện chữ online. Hàng ngàn truyện hay, cập nhật liên tục mỗi ngày.
                    </p>
                    <div style="display:flex;gap:12px;flex-wrap:wrap;">
                        <a href="/danh-sach-truyen" class="btn btn-primary" style="background:#54CFD6;color:#FFF">Khám phá truyện</a>
                        <a href="/bang-xep-hang" class="btn btn-outline" style="border-color:#FFF;color:#FFF">Bảng xếp hạng</a>
                    </div>
                </div>
                <div class="hero-visual" style="flex:0 0 240px;text-align:center;">
                    <div style="font-size:120px;line-height:1;">📚</div>
                </div>
            </div>
        </div>
    </section>
    <?php
}

function hdk_get_pagination($total_pages, $current_page = 1) {
    if ($total_pages <= 1) return;
    ?>
    <nav class="pagination" style="display:flex;justify-content:center;align-items:center;gap:8px;padding:24px 0;flex-wrap:wrap;">
        <?php if ($current_page > 1): ?>
            <a href="?page=<?php echo $current_page - 1; ?>" class="btn btn-ghost btn-sm">&laquo; Trước</a>
        <?php endif; ?>
        <?php
        $start = max(1, $current_page - 2);
        $end = min($total_pages, $current_page + 2);
        for ($i = $start; $i <= $end; $i++):
            $active = $i === $current_page ? 'btn-primary' : 'btn-ghost';
        ?>
            <a href="?page=<?php echo $i; ?>" class="btn <?php echo $active; ?> btn-sm"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($current_page < $total_pages): ?>
            <a href="?page=<?php echo $current_page + 1; ?>" class="btn btn-ghost btn-sm">Sau &raquo;</a>
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
