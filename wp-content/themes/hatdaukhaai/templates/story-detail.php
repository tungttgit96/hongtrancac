<?php
/**
 * Template: Story Detail
 * @global object $hdk_story
 */
global $hdk_story;

if (!$hdk_story) {
    wp_redirect(home_url('/'));
    exit;
}

$story = $hdk_story;
$is_favorited = false;
if (is_user_logged_in()) {
    global $wpdb;
    $is_favorited = (bool)$wpdb->get_var($wpdb->prepare(
        "SELECT id FROM " . HDK_DB::table('hdk_favorites') . " WHERE story_id = %d AND user_id = %d",
        $story->id, get_current_user_id()
    ));
}

// Get chapters
global $wpdb;
$chapters = $wpdb->get_results($wpdb->prepare(
    "SELECT id, chapter_number, title, views, updated_at FROM " . HDK_DB::table('hdk_chapters') . "
     WHERE story_id = %d AND status = 'published' ORDER BY chapter_number ASC LIMIT 100",
    $story->id
));

// Get comments
$comments = get_comments([
    'meta_key' => 'hdk_story_id',
    'meta_value' => $story->id,
    'status' => 'approve',
    'number' => 20,
]);

get_header();
?>

<div class="container" style="padding-top:24px;">
    <nav style="font-size:var(--font-size-sm);color:var(--color-text-muted);margin-bottom:20px;">
        <a href="<?php echo home_url('/'); ?>">Trang chủ</a> &raquo;
        <?php if (!empty($story->categories)): ?>
            <a href="/the-loai/<?php echo $story->categories[0]->slug; ?>"><?php echo esc_html($story->categories[0]->name); ?></a> &raquo;
        <?php endif; ?>
        <span><?php echo esc_html($story->title); ?></span>
    </nav>

    <div class="story-header" style="display:flex;gap:24px;flex-wrap:wrap;">
        <div style="flex:0 0 200px;">
            <img src="<?php echo esc_url($story->cover_url); ?>" alt="<?php echo esc_attr($story->title); ?>"
                 style="width:100%;border-radius:var(--radius-md);box-shadow:var(--shadow-lg);aspect-ratio:3/4;object-fit:cover;">
        </div>
        <div style="flex:1;min-width:280px;">
            <h1 style="font-size:var(--font-size-2xl);font-weight:700;margin-bottom:8px;"><?php echo esc_html($story->title); ?></h1>
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px;">
                <?php echo hdk_get_story_status_badge($story); ?>
                <?php if ($story->is_free): ?>
                    <span class="badge badge-success">Free</span>
                <?php endif; ?>
                <?php foreach ($story->categories as $cat): ?>
                    <a href="/the-loai/<?php echo $cat->slug; ?>" class="badge badge-primary" style="text-decoration:none;"><?php echo esc_html($cat->name); ?></a>
                <?php endforeach; ?>
            </div>

            <div style="display:flex;gap:24px;flex-wrap:wrap;margin-bottom:16px;font-size:var(--font-size-sm);">
                <div><strong>Tác giả:</strong> <a href="/tac-gia/<?php echo sanitize_title($story->author_name); ?>"><?php echo esc_html($story->author_name); ?></a></div>
                <div><strong>Chương:</strong> <?php echo $story->total_chapters; ?></div>
                <div><strong>Lượt xem:</strong> <?php echo number_format($story->total_views); ?></div>
                <div><strong>Yêu thích:</strong> <?php echo number_format($story->total_favorites); ?></div>
            </div>

            <?php hdk_get_rating_widget($story->id, $story->average_rating * $story->total_ratings, $story->total_ratings); ?>

            <div style="margin-top:16px;display:flex;gap:12px;flex-wrap:wrap;">
                <a href="/<?php echo $story->slug; ?>?chuong=1" class="btn btn-primary">📖 Đọc từ đầu</a>
                <?php if ($chapters): ?>
                    <a href="/<?php echo $story->slug; ?>?chuong=<?php echo $chapters[count($chapters)-1]->chapter_number; ?>" class="btn btn-outline">📄 Chương mới nhất</a>
                <?php endif; ?>
                <button class="btn btn-outline favorite-btn" data-story-id="<?php echo $story->id; ?>" data-favorited="<?php echo $is_favorited ? '1' : '0'; ?>">
                    <?php echo $is_favorited ? '❤️ Bỏ yêu thích' : '🤍 Yêu thích'; ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Summary -->
    <section style="margin-top:24px;background:var(--color-bg);border-radius:var(--radius-md);padding:20px;border:1px solid var(--color-border);">
        <h2 style="font-size:var(--font-size-lg);font-weight:600;margin-bottom:12px;">Tóm tắt</h2>
        <div style="line-height:1.8;color:var(--color-text-secondary);"><?php echo nl2br(esc_html($story->summary)); ?></div>
    </section>

    <!-- Chapter List -->
    <section style="margin-top:24px;background:var(--color-bg);border-radius:var(--radius-md);padding:20px;border:1px solid var(--color-border);">
        <h2 style="font-size:var(--font-size-lg);font-weight:600;margin-bottom:12px;">Danh sách chương (<?php echo count($chapters); ?>)</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:8px;">
            <?php foreach ($chapters as $chap): ?>
                <a href="/<?php echo $story->slug; ?>?chuong=<?php echo $chap->chapter_number; ?>"
                   style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;
                          border-radius:var(--radius-sm);transition:background 0.2s;
                          color:var(--color-text-primary);text-decoration:none;"
                   onmouseover="this.style.background='var(--color-bg-tertiary)'"
                   onmouseout="this.style.background='transparent'">
                    <span style="font-size:var(--font-size-sm);"><?php echo esc_html($chap->title); ?></span>
                    <span style="font-size:var(--font-size-xs);color:var(--color-text-muted);">👁 <?php echo number_format($chap->views); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Comments -->
    <section style="margin-top:24px;background:var(--color-bg);border-radius:var(--radius-md);padding:20px;border:1px solid var(--color-border);margin-bottom:32px;">
        <h2 style="font-size:var(--font-size-lg);font-weight:600;margin-bottom:16px;">Bình luận</h2>
        <?php if (is_user_logged_in()): ?>
            <form class="comment-form" style="margin-bottom:16px;" data-story-id="<?php echo $story->id; ?>">
                <textarea placeholder="Viết bình luận..." style="width:100%;padding:12px;border:2px solid var(--color-border);border-radius:var(--radius-md);font-family:var(--font-family);min-height:80px;resize:vertical;"></textarea>
                <button type="submit" class="btn btn-primary btn-sm" style="margin-top:8px;">Gửi bình luận</button>
            </form>
        <?php else: ?>
            <p style="color:var(--color-text-muted);margin-bottom:16px;"><a href="<?php echo wp_login_url(); ?>">Đăng nhập</a> để bình luận.</p>
        <?php endif; ?>

        <div class="comments-list">
            <?php if ($comments): ?>
                <?php foreach ($comments as $comment): ?>
                    <div style="padding:12px 0;border-bottom:1px solid var(--color-border-light);">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                            <strong style="font-size:var(--font-size-sm);"><?php echo esc_html($comment->comment_author); ?></strong>
                            <span style="font-size:var(--font-size-xs);color:var(--color-text-muted);"><?php echo human_time_diff(strtotime($comment->comment_date)); ?> trước</span>
                        </div>
                        <p style="font-size:var(--font-size-sm);color:var(--color-text-secondary);"><?php echo esc_html($comment->comment_content); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:var(--color-text-muted);">Chưa có bình luận nào.</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php get_footer(); ?>
