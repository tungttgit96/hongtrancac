<?php
/**
 * Template: Chapter Paywall
 */
global $hdk_story, $hdk_chapter, $hdk_access;

$story = $hdk_story;
$chapter = $hdk_chapter;
$access = $hdk_access;
$chapter_price = $access['chapter_price'] ?? 0;
$full_price = $access['full_price'] ?? 0;
$free_chapters = $access['free_chapters'] ?? 0;
$chapter_number = $access['chapter_number'] ?? 0;

get_header();
?>

<div class="container page-shell" style="padding-bottom:32px;">
    <nav style="font-size:var(--font-size-sm);color:var(--color-text-muted);margin-bottom:16px;">
        <a href="<?php echo home_url('/'); ?>">Trang chủ</a> &raquo;
        <a href="/<?php echo $story->slug; ?>"><?php echo esc_html($story->title); ?></a> &raquo;
        <span>Chương <?php echo $chapter->chapter_number; ?></span>
    </nav>

    <?php if ($access['reason'] === 'login_required'): ?>
        <!-- Login Required -->
        <div style="text-align:center;padding:60px 0;max-width:500px;margin:0 auto;">
            <div style="font-size:80px;margin-bottom:16px;">🔒</div>
            <h2 style="font-size:var(--font-size-2xl);margin-bottom:12px;">Cần đăng nhập để đọc</h2>
            <p style="color:var(--color-text-muted);margin-bottom:8px;">
                <?php echo $free_chapters; ?> chương đầu miễn phí. Từ chương <?php echo $free_chapters + 1; ?> cần đăng nhập.
            </p>
            <div style="display:flex;gap:12px;justify-content:center;margin-top:20px;">
                <a href="<?php echo wp_login_url($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary">Đăng nhập</a>
                <a href="<?php echo wp_registration_url(); ?>" class="btn btn-outline">Đăng ký</a>
            </div>
        </div>
    <?php else: ?>
        <!-- Paywall -->
        <div style="text-align:center;padding:40px 0;max-width:600px;margin:0 auto;" id="paywall-container">
            <div style="font-size:60px;margin-bottom:12px;">💎</div>
            <h2 style="font-size:var(--font-size-2xl);margin-bottom:8px;">Chương <?php echo $chapter->chapter_number; ?>: <?php echo esc_html($chapter->title); ?></h2>
            <p style="color:var(--color-text-muted);margin-bottom:24px;">
                <?php echo $free_chapters; ?> chương đầu miễn phí. Chương này cần <strong><?php echo $chapter_price; ?> hạt</strong> để đọc.
            </p>

            <!-- Preview: first 200 chars -->
            <div class="panel panel-pad" style="text-align:left;margin-bottom:24px;max-height:200px;overflow:hidden;position:relative;">
                <div style="line-height:2;font-size:var(--font-size-base);opacity:0.6;">
                    <?php echo wp_trim_words(strip_tags($chapter->content ?? ''), 50, '…'); ?>
                </div>
                <div style="position:absolute;bottom:0;left:0;right:0;height:80px;background:linear-gradient(transparent, var(--color-bg));"></div>
            </div>

            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;align-items:center;" id="purchase-buttons">
                <button type="button" class="btn btn-primary" onclick="purchaseChapter(<?php echo $story->id; ?>, <?php echo $chapter_number; ?>)">
                    🔑 Mua chương này (<?php echo $chapter_price; ?> hạt)
                </button>
                <?php if ($full_price > 0): ?>
                <button type="button" class="btn btn-outline" onclick="purchaseFull(<?php echo $story->id; ?>)">
                    📚 Mở toàn bộ (<?php echo $full_price; ?> hạt)
                </button>
                <?php endif; ?>
            </div>

            <div style="margin-top:12px;font-size:var(--font-size-sm);color:var(--color-text-muted);" id="credits-display">
                Đang kiểm tra số dư…
            </div>
            <div id="purchase-message" style="margin-top:8px;font-weight:600;"></div>
        </div>
    <?php endif; ?>
</div>

<script>
var storyId = <?php echo $story->id; ?>;
var chapterNum = <?php echo $chapter_number; ?>;

// Load credits
fetch('/wp-json/hdk/v1/credits')
    .then(r => r.json())
    .then(d => {
        document.getElementById('credits-display').textContent = 'Số dư: ' + d.credits + ' hạt';
    })
    .catch(() => {
        document.getElementById('credits-display').textContent = '';
    });

function purchaseChapter(sid, cnum) {
    var btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Đang xử lý…';
    document.getElementById('purchase-message').textContent = '';

    fetch('/wp-json/hdk/v1/purchase/chapter', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-WP-Nonce': window.hdkRestNonce || ''},
        body: JSON.stringify({story_id: sid, chapter_number: cnum})
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            document.getElementById('purchase-message').innerHTML = '<span style="color:var(--color-success);">Mua thành công! Đang chuyển trang…</span>';
            setTimeout(function(){ location.reload(); }, 800);
        } else if (d.code === 'insufficient_credits') {
            document.getElementById('purchase-message').innerHTML = '<span style="color:var(--color-danger);">' + d.message + '</span>';
            btn.disabled = false;
            btn.textContent = '🔑 Mua chương này (' + <?php echo $chapter_price; ?> + ' hạt)';
        }
    })
    .catch(function() {
        document.getElementById('purchase-message').innerHTML = '<span style="color:var(--color-danger);">Lỗi kết nối. Thử lại.</span>';
        btn.disabled = false;
        btn.textContent = '🔑 Mua chương này (' + <?php echo $chapter_price; ?> + ' hạt)';
    });
}

function purchaseFull(sid) {
    var btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Đang xử lý…';

    fetch('/wp-json/hdk/v1/purchase/full', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-WP-Nonce': window.hdkRestNonce || ''},
        body: JSON.stringify({story_id: sid})
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            document.getElementById('purchase-message').innerHTML = '<span style="color:var(--color-success);">Mua full thành công! Đang chuyển trang…</span>';
            setTimeout(function(){ location.reload(); }, 800);
        } else if (d.code === 'insufficient_credits') {
            document.getElementById('purchase-message').innerHTML = '<span style="color:var(--color-danger);">' + d.message + '</span>';
            btn.disabled = false;
            btn.textContent = '📚 Mở toàn bộ (' + <?php echo $full_price; ?> + ' hạt)';
        }
    })
    .catch(function() {
        document.getElementById('purchase-message').innerHTML = '<span style="color:var(--color-danger);">Lỗi kết nối.</span>';
        btn.disabled = false;
        btn.textContent = '📚 Mở toàn bộ (' + <?php echo $full_price; ?> + ' hạt)';
    });
}
</script>

<?php get_footer(); ?>
