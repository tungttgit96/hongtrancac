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
    "SELECT id, chapter_number, title, price, price_mode, views, updated_at FROM " . HDK_DB::table('hdk_chapters') . "
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

<div class="container page-shell">
    <nav style="font-size:var(--font-size-sm);color:var(--color-text-muted);margin-bottom:20px;">
        <a href="<?php echo home_url('/'); ?>">Trang chủ</a> <?php echo hdk_icon('chevron-right'); ?>
        <?php if (!empty($story->categories)): ?>
            <a href="<?php echo esc_url(hdk_category_url($story->categories[0]->slug)); ?>"><?php echo esc_html($story->categories[0]->name); ?></a> <?php echo hdk_icon('chevron-right'); ?>
        <?php endif; ?>
        <span><?php echo esc_html($story->title); ?></span>
    </nav>

    <div class="story-header" style="display:flex;gap:24px;flex-wrap:wrap;">
        <div style="flex:0 0 200px;">
            <img src="<?php echo esc_url($story->cover_url); ?>" alt="<?php echo esc_attr($story->title); ?>"
                 style="width:100%;border-radius:var(--radius-md);box-shadow:var(--shadow-lg);aspect-ratio:2/3;object-fit:cover;object-position:center;">
        </div>
        <div style="flex:1;min-width:280px;">
            <h1 style="font-size:var(--font-size-2xl);font-weight:700;margin-bottom:8px;"><?php echo esc_html($story->title); ?></h1>
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px;">
                <?php echo hdk_get_story_status_badge($story); ?>
                <?php if ($story->is_free): ?>
                    <span class="badge badge-success">Free</span>
                <?php endif; ?>
                <?php foreach ($story->categories as $cat): ?>
                    <a href="<?php echo esc_url(hdk_category_url($cat->slug)); ?>" class="badge badge-primary" style="text-decoration:none;"><?php echo esc_html($cat->name); ?></a>
                <?php endforeach; ?>
            </div>

            <div style="display:flex;gap:24px;flex-wrap:wrap;margin-bottom:16px;font-size:var(--font-size-sm);">
                <div><strong>Tác giả:</strong> <a href="<?php echo esc_url(hdk_page_url('tac-gia/' . sanitize_title($story->author_name))); ?>"><?php echo esc_html($story->author_name); ?></a></div>
                <div><strong>Chương:</strong> <?php echo $story->total_chapters; ?></div>
                <div><strong>Lượt xem:</strong> <?php echo number_format($story->total_views); ?></div>
                <div><strong>Yêu thích:</strong> <?php echo number_format($story->total_favorites); ?></div>
            </div>

            <?php hdk_get_rating_widget($story->id, $story->average_rating * $story->total_ratings, $story->total_ratings); ?>

            <div style="margin-top:16px;display:flex;gap:12px;flex-wrap:wrap;">
                <a href="<?php echo esc_url(hdk_story_url($story->slug, ['chuong' => 1])); ?>" class="btn btn-primary"><?php echo hdk_icon('book-open'); ?> Đọc từ đầu</a>
                <?php if ($chapters): ?>
                    <a href="<?php echo esc_url(hdk_story_url($story->slug, ['chuong' => $chapters[count($chapters)-1]->chapter_number])); ?>" class="btn btn-outline"><?php echo hdk_icon('file-text'); ?> Chương mới nhất</a>
                <?php endif; ?>
                <?php if (hdk_story_has_audio($story)): ?>
                    <button type="button" class="btn btn-outline hdk-audio-play"
                            data-audio-src="<?php echo esc_url($story->audio_url); ?>"
                            data-audio-title="<?php echo esc_attr(($story->audio_title ?? '') ?: $story->title); ?>"
                            data-story-title="<?php echo esc_attr($story->title); ?>"
                            data-story-url="<?php echo esc_url(hdk_story_url($story->slug)); ?>">
                        <?php echo hdk_icon('headphones'); ?> Nghe truyện
                    </button>
                <?php endif; ?>
                <button type="button" class="btn btn-outline favorite-btn" data-story-id="<?php echo $story->id; ?>" data-favorited="<?php echo $is_favorited ? '1' : '0'; ?>">
                    <?php echo $is_favorited ? hdk_icon('heart') . ' Bỏ yêu thích' : hdk_icon('heart') . ' Yêu thích'; ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Summary -->
    <section class="panel panel-pad" style="margin-top:24px;">
        <h2 style="font-size:var(--font-size-lg);font-weight:600;margin-bottom:12px;">Tóm tắt</h2>
        <div style="line-height:1.8;color:var(--color-text-secondary);"><?php echo nl2br(esc_html($story->summary)); ?></div>
    </section>

    <!-- Chapter List -->
    <section class="panel panel-pad" style="margin-top:24px;">
        <h2 style="font-size:var(--font-size-lg);font-weight:600;margin-bottom:12px;">Danh sách chương (<?php echo count($chapters); ?>)</h2>
        <?php
        $free_limit = (int)($story->free_chapters ?? 0);
        $full_price = (int)($story->full_price ?? 0);
        $price_summary = HDK_DB::get_story_price_summary($story);
        $chapter_prices = [];
        $has_priced_chapters = false;
        foreach ($chapters as $chapter_for_price) {
            $effective_price = HDK_DB::get_chapter_price($story, $chapter_for_price->chapter_number);
            $chapter_prices[(int)$chapter_for_price->chapter_number] = $effective_price;
            if ($effective_price > 0 && (int)$chapter_for_price->chapter_number > $free_limit) {
                $has_priced_chapters = true;
            }
        }
        $has_pricing = !empty($price_summary['has_pricing']) || $has_priced_chapters;
        $user_id = get_current_user_id();
        ?>
        <?php if ($has_pricing): ?>
        <div style="margin-bottom:12px;font-size:13px;color:var(--color-text-muted);">
            <?php if (!empty($price_summary['label'])): ?><?php echo hdk_icon('gem'); ?> <?php echo esc_html($price_summary['label']); ?><?php endif; ?>
            <?php if ($full_price > 0): ?>
                <?php if ($user_id): ?>
                <button type="button" onclick="purchaseFullStory(<?php echo $story->id; ?>)" style="margin-left:8px;font-size:12px;padding:2px 10px;border-radius:12px;border:1px solid var(--color-primary);background:var(--color-primary);color:var(--color-on-primary);cursor:pointer;">Mua full</button>
                <?php endif; ?>
            <?php endif; ?>
            <span id="purchase-msg" style="margin-left:8px;font-weight:600;"></span>
        </div>
        <?php endif; ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:8px;">
            <?php foreach ($chapters as $chap):
                $chap_price = $chapter_prices[(int)$chap->chapter_number] ?? HDK_DB::get_chapter_price($story, $chap->chapter_number);
                $chap_price_mode = $chap->price_mode ?? ((int)($chap->price ?? 0) > 0 ? 'custom' : 'inherit');
                $locked = !$story->is_free && $chap_price_mode !== 'free' && $chap->chapter_number > $free_limit && ($chap_price > 0 || $full_price > 0);
                $purchased = $locked && $user_id ? HDK_Template_Loader::has_purchased_chapter($story->id, $chap->chapter_number) : false;
                $icon = !$locked ? hdk_icon('unlock') : ($purchased ? hdk_icon('check') : hdk_icon('lock'));
            ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;
                           border-radius:var(--radius-sm);border:1px solid var(--color-border-light);
                           <?php echo $locked && !$purchased ? 'background:var(--color-bg-tertiary);' : ''; ?>">
                    <a href="<?php echo esc_url(hdk_story_url($story->slug, ['chuong' => $chap->chapter_number])); ?>"
                       style="color:var(--color-text-primary);text-decoration:none;font-size:14px;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <span style="margin-right:6px;"><?php echo $icon; ?></span>
                        <?php echo esc_html($chap->title); ?>
                    </a>
                    <?php if ($locked && !$purchased && $user_id): ?>
                        <button type="button" onclick="event.preventDefault();purchaseChapterInline(<?php echo $story->id; ?>, <?php echo $chap->chapter_number; ?>, this)"
                            style="margin-left:8px;font-size:11px;padding:3px 10px;border-radius:12px;border:1px solid var(--color-primary);background:transparent;color:var(--color-primary);cursor:pointer;white-space:nowrap;">
                            <?php echo $chap_price; ?> <?php echo hdk_icon('gem'); ?>
                        </button>
                    <?php elseif (!$locked || $purchased): ?>
                        <span style="font-size:var(--font-size-xs);color:var(--color-text-muted);white-space:nowrap;"><?php echo hdk_icon('eye'); ?> <?php echo number_format($chap->views); ?></span>
                    <?php else: ?>
                        <a href="<?php echo esc_url(hdk_login_url(hdk_story_url($story->slug, ['chuong' => $chap->chapter_number]))); ?>" style="font-size:11px;color:var(--color-text-muted);white-space:nowrap;text-decoration:none;"><?php echo hdk_icon('lock'); ?> Đăng nhập</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Comments -->
    <section class="panel panel-pad" style="margin-top:24px;margin-bottom:32px;">
        <h2 style="font-size:var(--font-size-lg);font-weight:600;margin-bottom:16px;">Bình luận</h2>
        <?php if (is_user_logged_in()): ?>
            <form class="comment-form" style="margin-bottom:16px;" data-story-id="<?php echo $story->id; ?>">
                <textarea name="comment" aria-label="Viết bình luận" autocomplete="off" placeholder="Viết bình luận…" style="width:100%;padding:12px;border:2px solid var(--color-input-border);background:var(--color-input-bg);color:var(--color-text-primary);border-radius:var(--radius-md);font-family:var(--font-family);min-height:80px;resize:vertical;"></textarea>
                <button type="submit" class="btn btn-primary btn-sm" style="margin-top:8px;">Gửi bình luận</button>
            </form>
        <?php else: ?>
            <p style="color:var(--color-text-muted);margin-bottom:16px;"><a href="<?php echo esc_url(hdk_login_url(home_url('/' . $story->slug))); ?>">Đăng nhập</a> để bình luận.</p>
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

<script>
var apiBase = (window.hdkApi && window.hdkApi.restBase) || <?php echo wp_json_encode(rest_url('hdk/v1')); ?>;
function purchaseChapterInline(storyId, chapterNum, btn) {
    btn.disabled = true;
    if (!btn.dataset.originalHtml) btn.dataset.originalHtml = btn.innerHTML;
    btn.innerHTML = 'Đang xử lý…';
    var msg = document.getElementById('purchase-msg');
    fetch(apiBase + '/purchase/chapter', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-WP-Nonce': window.hdkRestNonce || ''},
        body: JSON.stringify({story_id: storyId, chapter_number: chapterNum})
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            if (msg) { msg.innerHTML = '<span style="color:var(--color-success);">Mua thành công!</span>'; }
            btn.innerHTML = window.hdkIcon('check');
            btn.style.borderColor = 'var(--color-success)';
            btn.style.color = 'var(--color-success)';
            btn.style.background = 'transparent';
            setTimeout(function(){ location.reload(); }, 500);
        } else if (d.code === 'insufficient_credits') {
            if (msg) msg.innerHTML = '<span style="color:var(--color-danger);">' + d.message + '</span>';
            btn.disabled = false;
            btn.innerHTML = btn.dataset.originalHtml;
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = btn.dataset.originalHtml;
    });
}

function purchaseFullStory(storyId) {
    if (!confirm('Mở toàn bộ truyện với giá <?php echo $full_price; ?> Linh Thạch?')) return;
    var msg = document.getElementById('purchase-msg');
    if (msg) msg.innerHTML = 'Đang xử lý…';
    fetch(apiBase + '/purchase/full', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-WP-Nonce': window.hdkRestNonce || ''},
        body: JSON.stringify({story_id: storyId})
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            if (msg) msg.innerHTML = '<span style="color:var(--color-success);">Mua full thành công!</span>';
            setTimeout(function(){ location.reload(); }, 500);
        } else if (d.code === 'insufficient_credits') {
            if (msg) msg.innerHTML = '<span style="color:var(--color-danger);">' + d.message + '</span>';
        }
    });
}
</script>
