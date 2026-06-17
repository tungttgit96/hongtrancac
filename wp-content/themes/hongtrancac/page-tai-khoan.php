<?php
/**
 * Template: Tài khoản độc giả
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/dang-nhap?redirect_to=' . rawurlencode(home_url('/tai-khoan'))));
    exit;
}

$user_id = get_current_user_id();
$user = get_userdata($user_id);
$credits_table = HDK_DB::table('hdk_user_credits');

global $wpdb;
$credits = (int)$wpdb->get_var($wpdb->prepare("SELECT credits FROM $credits_table WHERE user_id = %d", $user_id));
if ($credits === null && $wpdb->last_error === '') {
    $wpdb->insert($credits_table, ['user_id' => $user_id, 'credits' => 0]);
    $credits = 0;
}

$purchased_count = HDK_DB::get_user_purchased_count($user_id);
$reading_count = (int)$wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(DISTINCT story_id) FROM " . HDK_DB::table('hdk_reading_history') . " WHERE user_id = %d",
    $user_id
));
$following_count = (int)$wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM " . HDK_DB::table('hdk_favorites') . " WHERE user_id = %d",
    $user_id
));
$listening_history = get_user_meta($user_id, 'hdk_listening_history', true);
if (!is_array($listening_history)) {
    $listening_history = [];
}
$listening_count = count($listening_history);
$account_message = '';
$account_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hdk_account_settings'])) {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'hdk_account_settings')) {
        $account_error = 'Phiên cập nhật hết hạn, vui lòng thử lại.';
    } else {
        $display_name = sanitize_text_field($_POST['display_name'] ?? '');
        $user_email = sanitize_email($_POST['user_email'] ?? '');
        $avatar_url = esc_url_raw($_POST['avatar_url'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $update = ['ID' => $user_id];

        if ($display_name === '') {
            $account_error = 'Tên hiển thị không được để trống.';
        } elseif (!$user_email || !is_email($user_email)) {
            $account_error = 'Email không hợp lệ.';
        } else {
            $email_owner = email_exists($user_email);
            if ($email_owner && (int)$email_owner !== $user_id) {
                $account_error = 'Email này đã được tài khoản khác sử dụng.';
            } else {
                $update['display_name'] = $display_name;
                $update['user_email'] = $user_email;
            }
        }

        if (!$account_error && ($new_password !== '' || $confirm_password !== '' || $current_password !== '')) {
            if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
                $account_error = 'Mật khẩu hiện tại không đúng.';
            } elseif (strlen($new_password) < 8) {
                $account_error = 'Mật khẩu mới cần ít nhất 8 ký tự.';
            } elseif ($new_password !== $confirm_password) {
                $account_error = 'Mật khẩu mới nhập lại không khớp.';
            } else {
                $update['user_pass'] = $new_password;
            }
        }

        if (!$account_error) {
            $result = wp_update_user($update);
            if (is_wp_error($result)) {
                $account_error = $result->get_error_message();
            } else {
                update_user_meta($user_id, 'hdk_avatar_url', $avatar_url);
                if (!empty($update['user_pass'])) {
                    wp_set_auth_cookie($user_id, true, is_ssl());
                }
                $account_message = 'Đã cập nhật thông tin tài khoản.';
                $user = get_userdata($user_id);
            }
        }
    }
}

$tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'favorites';
$valid_tabs = ['favorites', 'reading', 'purchased', 'history', 'listening', 'comments', 'wallet', 'notifications', 'settings'];
if (!in_array($tab, $valid_tabs)) $tab = 'favorites';

$page = max(1, (int)($_GET['paged'] ?? 1));

get_header();
$custom_avatar = get_user_meta($user_id, 'hdk_avatar_url', true);
$avatar_html = $custom_avatar
    ? '<img src="' . esc_url($custom_avatar) . '" alt="' . esc_attr($user->display_name) . '">'
    : get_avatar($user_id, 96, '', '', ['style' => '']);
?>

<div class="container" style="padding:32px 0;">
    <nav class="breadcrumb" style="margin-bottom:16px;">
        <a href="<?php echo home_url('/'); ?>">Trang chủ</a> &rsaquo; Tài khoản
    </nav>

    <section class="account-hero">
        <div class="account-cover"></div>
        <div class="account-profile">
            <div class="account-avatar">
                <?php echo $avatar_html; ?>
            </div>
            <div class="account-profile-copy">
                <h1><?php echo esc_html($user->display_name); ?></h1>
                <p><?php echo esc_html($user->user_login); ?> · <?php echo esc_html($user->user_email); ?></p>
            </div>
            <div class="account-stats">
                <div>
                    <strong><?php echo number_format($reading_count); ?></strong>
                    <span>Truyện đã đọc</span>
                </div>
                <div>
                    <strong><?php echo number_format($following_count); ?></strong>
                    <span>Đang theo dõi</span>
                </div>
                <div>
                    <strong><?php echo number_format($listening_count); ?></strong>
                    <span>Lịch sử nghe</span>
                </div>
            </div>
        </div>
    </section>

    <?php if ($account_message || $account_error): ?>
        <div class="<?php echo $account_error ? 'account-alert account-alert-error' : 'account-alert account-alert-success'; ?>">
            <?php echo esc_html($account_error ?: $account_message); ?>
        </div>
    <?php endif; ?>

    <?php
    $account_menu = [
        'favorites' => '📖 Tủ truyện',
        'reading' => '📌 Đang đọc',
        'purchased' => '💎 Đã mua',
        'history' => '🕐 Lịch sử đọc truyện',
        'listening' => '🎧 Lịch sử nghe truyện',
        'comments' => '💬 Bình luận của tôi',
        'wallet' => '💎 Ví Linh Thạch',
        'notifications' => '🔔 Thông báo',
        'settings' => '⚙️ Cài đặt tài khoản',
    ];
    ?>
    <div class="account-layout">
        <nav class="account-menu-grid" aria-label="Lối tắt tài khoản">
            <?php foreach ($account_menu as $key => $label): ?>
                <a href="<?php echo esc_url(hdk_page_url('tai-khoan', ['tab' => $key])); ?>" class="<?php echo $tab === $key ? 'active' : ''; ?>">
                    <?php echo $label; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="account-content-main">
        <?php
        switch ($tab) {
        case 'favorites':
            $data = HDK_DB::get_favorites($user_id, $page);
            $stories = $data['stories'];
            ?>
            <?php if (empty($stories)): ?>
                <div class="empty-state">
                    <div style="font-size:48px;margin-bottom:16px;">📖</div>
                    <p style="margin-bottom:16px;">Bạn chưa yêu thích truyện nào.</p>
                    <a href="<?php echo home_url('/danh-sach-truyen'); ?>" class="btn btn-primary">Khám phá truyện ngay!</a>
                </div>
            <?php else: ?>
                <div class="grid grid-6">
                    <?php foreach ($stories as $story): ?>
                        <?php hdk_get_story_card($story); ?>
                    <?php endforeach; ?>
                </div>
                <?php hdk_get_pagination($data['pages'], $page); ?>
            <?php endif; ?>
            <?php break;

        case 'reading':
            $stories = HDK_DB::get_reading_stories($user_id);
            ?>
            <?php if (empty($stories)): ?>
                <div class="empty-state">
                    <div style="font-size:48px;margin-bottom:16px;">📌</div>
                    <p style="color:var(--color-text-muted);margin-bottom:16px;">Bắt đầu đọc truyện đầu tiên của bạn!</p>
                    <a href="<?php echo home_url('/danh-sach-truyen'); ?>" class="btn btn-primary">Khám phá truyện</a>
                </div>
            <?php else: ?>
                <div class="grid grid-6">
                    <?php foreach ($stories as $story): ?>
                        <a href="<?php echo home_url('/' . ($story->slug ?? '') . '?chuong=' . (int)($story->current_chapter ?? 1)); ?>" class="card story-card">
                            <img src="<?php echo esc_url($story->cover_url ?? get_template_directory_uri() . '/assets/img/placeholder.svg'); ?>" alt="<?php echo esc_html($story->title); ?>" class="card-img" loading="lazy">
                            <div class="card-body">
                                <h3 class="card-title"><?php echo esc_html($story->title); ?></h3>
                                <div class="card-meta"><?php echo esc_html($story->author_name ?? ''); ?></div>
                                <div style="margin-top:8px;">
                                    <span class="badge badge-primary">Đọc tiếp chương <?php echo (int)($story->current_chapter ?? 0); ?></span>
                                </div>
                                <?php $pct = max(1, min(100, (int)($story->scroll_percent ?? 0))); ?>
                                <div class="progress-bar" style="height:4px;background:var(--color-border);border-radius:2px;margin-top:8px;overflow:hidden;">
                                    <div style="height:100%;width:<?php echo $pct; ?>%;background:var(--color-primary);border-radius:2px;"></div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php break;

        case 'purchased':
            $data = HDK_DB::get_purchased_stories($user_id, $page);
            $stories = $data['stories'];
            ?>
            <?php if (empty($stories)): ?>
                <div class="empty-state">
                    <div style="font-size:48px;margin-bottom:16px;">💎</div>
                    <p style="color:var(--color-text-muted);margin-bottom:16px;">Khám phá truyện hay để mua bằng Linh Thạch</p>
                    <a href="<?php echo home_url('/danh-sach-truyen'); ?>" class="btn btn-primary">Khám phá truyện</a>
                </div>
            <?php else: ?>
                <div class="grid grid-6">
                    <?php foreach ($stories as $story): ?>
                        <a href="<?php echo home_url('/' . ($story->slug ?? '')); ?>" class="card story-card">
                            <img src="<?php echo esc_url($story->cover_url ?? get_template_directory_uri() . '/assets/img/placeholder.svg'); ?>" alt="<?php echo esc_html($story->title); ?>" class="card-img" loading="lazy">
                            <div class="card-body">
                                <h3 class="card-title"><?php echo esc_html($story->title); ?></h3>
                                <div class="card-meta"><?php echo esc_html($story->author_name ?? ''); ?></div>
                                <div style="margin-top:8px;">
                                    <span class="badge badge-warning">💎 Đã mua</span>
                                </div>
                                <div class="card-meta" style="margin-top:6px"><?php echo (int)($story->chapter_count ?? 0); ?> chương</div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php hdk_get_pagination($data['pages'], $page); ?>
            <?php endif; ?>
            <?php break;

        case 'history':
            $data = HDK_DB::get_reading_history($user_id, $page);
            $rows = $data['rows'];
            ?>
            <?php if (empty($rows)): ?>
                <div class="empty-state">
                    <div style="font-size:48px;margin-bottom:16px;">🕐</div>
                    <p style="color:var(--color-text-muted);">Lịch sử đọc sẽ xuất hiện ở đây</p>
                </div>
            <?php else: ?>
                <div class="history-list" style="display:flex;flex-direction:column;gap:1px;background:var(--color-border);border-radius:var(--radius-md);overflow:hidden;">
                    <?php foreach ($rows as $row): ?>
                        <a href="<?php echo home_url('/' . ($row->slug ?? '') . '?chuong=' . (int)$row->chapter_number); ?>"
                           style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:var(--color-bg);text-decoration:none;color:var(--color-text-primary);gap:12px;flex-wrap:wrap;">
                            <div>
                                <strong><?php echo esc_html($row->title); ?></strong>
                                <span style="color:var(--color-text-muted);"> — Chương <?php echo (int)$row->chapter_number; ?></span>
                            </div>
                            <span style="color:var(--color-text-muted);font-size:var(--font-size-sm);white-space:nowrap;">
                                <?php echo mysql2date('H:i d/m/Y', $row->created_at); ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php hdk_get_pagination($data['pages'], $page); ?>
            <?php endif; ?>
            <?php break;

        case 'listening':
            ?>
            <?php if (empty($listening_history)): ?>
                <div class="empty-state">
                    <div style="font-size:48px;margin-bottom:16px;">🎧</div>
                    <p style="color:var(--color-text-muted);margin-bottom:16px;">Lịch sử nghe truyện sẽ xuất hiện ở đây.</p>
                    <a href="<?php echo esc_url(hdk_page_url('danh-sach-truyen', ['audio' => '1'])); ?>" class="btn btn-primary">Khám phá truyện audio</a>
                </div>
            <?php else: ?>
                <div class="history-list" style="display:flex;flex-direction:column;gap:1px;background:var(--color-border);border-radius:var(--radius-md);overflow:hidden;">
                    <?php foreach (array_slice($listening_history, 0, 30) as $item): ?>
                        <a href="<?php echo esc_url($item['url'] ?? '#'); ?>"
                           style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:var(--color-bg);text-decoration:none;color:var(--color-text-primary);gap:12px;flex-wrap:wrap;">
                            <div>
                                <strong><?php echo esc_html($item['title'] ?? 'Truyện audio'); ?></strong>
                                <span style="color:var(--color-text-muted);"> — <?php echo esc_html($item['position'] ?? 'Đang nghe'); ?></span>
                            </div>
                            <span style="color:var(--color-text-muted);font-size:var(--font-size-sm);white-space:nowrap;">
                                <?php echo esc_html($item['time'] ?? ''); ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php break;

        case 'comments':
            $comments = get_comments([
                'user_id' => $user_id,
                'number' => 30,
                'status' => 'all',
                'orderby' => 'comment_date_gmt',
                'order' => 'DESC',
            ]);
            ?>
            <?php if (empty($comments)): ?>
                <div class="empty-state">
                    <div style="font-size:48px;margin-bottom:16px;">💬</div>
                    <p style="color:var(--color-text-muted);">Bình luận của bạn sẽ xuất hiện ở đây.</p>
                </div>
            <?php else: ?>
                <div class="history-list" style="display:flex;flex-direction:column;gap:1px;background:var(--color-border);border-radius:var(--radius-md);overflow:hidden;">
                    <?php foreach ($comments as $comment): ?>
                        <a href="<?php echo esc_url(get_comment_link($comment)); ?>"
                           style="display:block;padding:12px 16px;background:var(--color-bg);text-decoration:none;color:var(--color-text-primary);">
                            <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:4px;">
                                <strong><?php echo esc_html(get_the_title($comment->comment_post_ID)); ?></strong>
                                <span style="color:var(--color-text-muted);font-size:var(--font-size-sm);"><?php echo esc_html(mysql2date('H:i d/m/Y', $comment->comment_date)); ?></span>
                            </div>
                            <div style="color:var(--color-text-muted);font-size:var(--font-size-sm);"><?php echo esc_html(wp_trim_words($comment->comment_content, 24)); ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php break;

        case 'wallet':
            $stats = HDK_DB::get_user_credit_stats($user_id);
            $tx_data = HDK_DB::get_credit_transactions($user_id, $page, 20);
            $transactions = $tx_data['rows'];
            $packages = HDK_DB::get_credit_packages(true);
            ?>
            <div class="wallet-summary" style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap;">
                <div class="wallet-stat" style="flex:1;min-width:140px;padding:20px;background:var(--color-bg-secondary);border-radius:var(--radius-md);text-align:center;">
                    <div style="font-size:var(--font-size-sm);color:var(--color-text-muted);margin-bottom:4px;">Số dư</div>
                    <div style="font-size:var(--font-size-2xl);font-weight:700;color:var(--color-primary);">💎 <?php echo number_format($stats['credits']); ?></div>
                </div>
                <div class="wallet-stat" style="flex:1;min-width:140px;padding:20px;background:var(--color-bg-secondary);border-radius:var(--radius-md);text-align:center;">
                    <div style="font-size:var(--font-size-sm);color:var(--color-text-muted);margin-bottom:4px;">Đã nạp</div>
                    <div style="font-size:var(--font-size-xl);font-weight:600;color:var(--color-success);">📥 <?php echo number_format($stats['total_earned']); ?></div>
                </div>
                <div class="wallet-stat" style="flex:1;min-width:140px;padding:20px;background:var(--color-bg-secondary);border-radius:var(--radius-md);text-align:center;">
                    <div style="font-size:var(--font-size-sm);color:var(--color-text-muted);margin-bottom:4px;">Đã tiêu</div>
                    <div style="font-size:var(--font-size-xl);font-weight:600;color:var(--color-danger);">📤 <?php echo number_format($stats['total_spent']); ?></div>
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap;">
                <button type="button" class="btn btn-primary" onclick="document.getElementById('purchase-modal').style.display='flex'">
                    💳 Nạp Linh Thạch
                </button>
                <button type="button" class="btn btn-outline daily-claim-btn" id="daily-claim-btn" onclick="claimDaily()">
                    📅 Điểm danh +<?php echo (int)get_option('hdk_daily_credits', 10); ?> Linh Thạch
                </button>
            </div>

            <!-- Purchase Modal -->
            <div id="purchase-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:var(--color-overlay);z-index:999;align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
                <div style="background:var(--color-bg);border-radius:var(--radius-lg);padding:32px;max-width:500px;width:90%;max-height:80vh;overflow-y:auto;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                        <h2 style="margin:0;">Nạp Linh Thạch</h2>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('purchase-modal').style.display='none'">✕</button>
                    </div>
                    <?php if (empty($packages)): ?>
                        <p style="color:var(--color-text-muted);">Chưa có gói nạp nào.</p>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:12px;">
                            <?php foreach ($packages as $pkg): ?>
                                <div style="padding:16px;border:2px solid var(--color-border);border-radius:var(--radius-md);cursor:pointer;transition:border-color 0.2s;" class="package-card"
                                     onmouseover="this.style.borderColor='var(--color-primary)'" onmouseout="this.style.borderColor='var(--color-border)'">
                                    <div style="display:flex;justify-content:space-between;align-items:center;">
                                        <div>
                                            <strong style="font-size:var(--font-size-lg);"><?php echo esc_html($pkg->name); ?></strong>
                                            <?php if ($pkg->bonus_credits > 0): ?>
                                                <span style="color:var(--color-primary);font-size:var(--font-size-sm);margin-left:8px;">+<?php echo (int)$pkg->bonus_credits; ?> bonus</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="text-align:right;">
                                            <div style="font-weight:700;font-size:var(--font-size-lg);">💎 <?php echo number_format((int)$pkg->credits); ?></div>
                                            <div style="color:var(--color-text-muted);font-size:var(--font-size-sm);"><?php echo number_format((int)$pkg->price_vnd); ?> đ</div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <p style="color:var(--color-text-muted);font-size:var(--font-size-sm);text-align:center;margin-top:8px;">
                                Liên hệ admin để thanh toán và nhận Linh Thạch.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <h3 style="margin-bottom:12px;">Lịch sử giao dịch</h3>
            <?php if (empty($transactions)): ?>
                <div class="empty-state">
                    <div style="font-size:48px;margin-bottom:16px;">💎</div>
                    <p style="color:var(--color-text-muted);">Chưa có giao dịch nào</p>
                </div>
            <?php else: ?>
                <div class="history-list" style="display:flex;flex-direction:column;gap:1px;background:var(--color-border);border-radius:var(--radius-md);overflow:hidden;">
                    <?php foreach ($transactions as $tx): ?>
                        <?php
                        $is_positive = $tx->credits >= 0;
                        $type_labels = ['earn' => 'Nạp', 'spend' => 'Tiêu', 'daily' => 'Điểm danh', 'admin_add' => 'Admin +', 'admin_deduct' => 'Admin -', 'refund' => 'Hoàn'];
                        ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:var(--color-bg);gap:12px;flex-wrap:wrap;">
                            <div>
                                <span style="color:var(--color-text-muted);font-size:var(--font-size-sm);"><?php echo $type_labels[$tx->type] ?? $tx->type; ?></span>
                                <?php if ($tx->note): ?>
                                    <span style="margin-left:8px;"><?php echo esc_html($tx->note); ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <span style="font-weight:600;color:<?php echo $is_positive ? 'var(--color-success)' : 'var(--color-danger)'; ?>;white-space:nowrap;">
                                    <?php echo $is_positive ? '+' : ''; ?><?php echo number_format((int)$tx->credits); ?>
                                </span>
                                <span style="color:var(--color-text-muted);font-size:var(--font-size-sm);white-space:nowrap;">
                                    <?php echo mysql2date('H:i d/m/Y', $tx->created_at); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php hdk_get_pagination($tx_data['pages'], $page); ?>
            <?php endif; ?>
            <?php break;

        case 'notifications':
            $notif_data = HDK_DB::get_notifications($user_id, $page, 20);
            $notifications = $notif_data['rows'];
            ?>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <h3 style="margin:0;">Thông báo</h3>
                <?php if (!empty($notifications)): ?>
                    <button type="button" class="btn btn-outline btn-sm" onclick="markAllRead()" style="font-size:var(--font-size-sm);">Đánh dấu tất cả đã đọc</button>
                <?php endif; ?>
            </div>
            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <div style="font-size:48px;margin-bottom:16px;">🔔</div>
                    <p style="color:var(--color-text-muted);">Chưa có thông báo nào</p>
                </div>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:1px;background:var(--color-border);border-radius:var(--radius-md);overflow:hidden;">
                    <?php foreach ($notifications as $notif): ?>
                        <?php $is_unread = !$notif->is_read; ?>
                        <a href="<?php echo esc_url($notif->link ?: '#'); ?>" 
                           style="display:flex;gap:12px;padding:12px 16px;background:<?php echo $is_unread ? 'var(--color-primary-light)' : 'var(--color-bg)'; ?>;text-decoration:none;color:var(--color-text-primary);transition:background 0.15s;align-items:flex-start;"
                           class="notif-item" data-notif-id="<?php echo (int)$notif->id; ?>">
                            <div style="flex:1;">
                                <div style="font-weight:<?php echo $is_unread ? '600' : '400'; ?>;margin-bottom:4px;"><?php echo esc_html($notif->title); ?></div>
                                <?php if ($notif->message): ?>
                                    <div style="color:var(--color-text-muted);font-size:var(--font-size-sm);"><?php echo esc_html($notif->message); ?></div>
                                <?php endif; ?>
                                <div style="color:var(--color-text-muted);font-size:12px;margin-top:4px;"><?php echo mysql2date('H:i d/m/Y', $notif->created_at); ?></div>
                            </div>
                            <?php if ($is_unread): ?>
                                <span style="width:8px;height:8px;border-radius:50%;background:var(--color-primary);flex-shrink:0;margin-top:6px;"></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php hdk_get_pagination($notif_data['pages'], $page); ?>
            <?php endif; ?>
            <?php break;

        case 'settings':
            $avatar_url = get_user_meta($user_id, 'hdk_avatar_url', true);
            ?>
            <div class="account-settings-panel panel panel-pad">
                <h2 style="font-size:var(--font-size-xl);font-weight:700;margin-bottom:8px;">Cài đặt tài khoản</h2>
                <p style="color:var(--color-text-muted);margin-bottom:20px;">Cập nhật tên hiển thị, email, avatar và mật khẩu đăng nhập.</p>
                <form method="post" class="account-settings-form">
                    <?php wp_nonce_field('hdk_account_settings'); ?>
                    <input type="hidden" name="hdk_account_settings" value="1">

                    <div class="form-grid-2">
                        <p>
                            <label for="account-login">Tên đăng nhập</label>
                            <input type="text" id="account-login" value="<?php echo esc_attr($user->user_login); ?>" disabled style="width:100%;">
                        </p>
                        <p>
                            <label for="account-display-name">Tên hiển thị</label>
                            <input type="text" name="display_name" id="account-display-name" value="<?php echo esc_attr($user->display_name); ?>" required style="width:100%;">
                        </p>
                        <p>
                            <label for="account-email">Email</label>
                            <input type="email" name="user_email" id="account-email" value="<?php echo esc_attr($user->user_email); ?>" required style="width:100%;">
                        </p>
                        <p>
                            <label for="account-avatar-url">Avatar URL</label>
                            <input type="url" name="avatar_url" id="account-avatar-url" value="<?php echo esc_attr($avatar_url); ?>" placeholder="https://..." style="width:100%;">
                        </p>
                    </div>

                    <h3 style="font-size:var(--font-size-base);margin:18px 0 10px;">Đổi mật khẩu</h3>
                    <div class="form-grid-3">
                        <p>
                            <label for="account-current-password">Mật khẩu hiện tại</label>
                            <input type="password" name="current_password" id="account-current-password" autocomplete="current-password" style="width:100%;">
                        </p>
                        <p>
                            <label for="account-new-password">Mật khẩu mới</label>
                            <input type="password" name="new_password" id="account-new-password" autocomplete="new-password" minlength="8" style="width:100%;">
                        </p>
                        <p>
                            <label for="account-confirm-password">Nhập lại mật khẩu mới</label>
                            <input type="password" name="confirm_password" id="account-confirm-password" autocomplete="new-password" minlength="8" style="width:100%;">
                        </p>
                    </div>

                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                </form>
            </div>
            <?php break;
        }
        ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
