<?php
/**
 * Template: Tài khoản độc giả
 */

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(home_url('/tai-khoan')));
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

$tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'favorites';
$valid_tabs = ['favorites', 'reading', 'purchased', 'history', 'wallet', 'notifications'];
if (!in_array($tab, $valid_tabs)) $tab = 'favorites';

$page = max(1, (int)($_GET['paged'] ?? 1));

get_header();
?>

<div class="container" style="padding:32px 0;">
    <nav class="breadcrumb" style="margin-bottom:16px;">
        <a href="<?php echo home_url('/'); ?>">Trang chủ</a> &rsaquo; Tài khoản
    </nav>

    <div class="account-profile" style="display:flex;align-items:center;gap:20px;padding:24px;background:var(--color-bg-secondary);border-radius:var(--radius-lg);margin-bottom:24px;flex-wrap:wrap;">
        <div class="account-avatar" style="width:64px;height:64px;border-radius:50%;background:var(--color-primary-light);display:flex;align-items:center;justify-content:center;font-size:28px;flex-shrink:0;">
            <?php echo get_avatar($user_id, 64, '', '', ['style' => 'border-radius:50%;width:64px;height:64px;']); ?>
        </div>
        <div style="flex:1;min-width:200px;">
            <h1 style="font-size:var(--font-size-xl);font-weight:700;margin:0 0 8px;"><?php echo esc_html($user->display_name); ?></h1>
            <div style="display:flex;gap:24px;flex-wrap:wrap;color:var(--color-text-muted);">
                <span>💎 <strong style="color:var(--color-primary);"><?php echo number_format($credits); ?></strong> hạt</span>
                <span>📚 Đã mua: <strong style="color:var(--color-text-primary);"><?php echo number_format($purchased_count); ?></strong> truyện</span>
            </div>
        </div>
    </div>

    <nav class="account-tabs" style="display:flex;gap:4px;margin-bottom:24px;border-bottom:2px solid var(--color-border);">
        <?php
        $tabs = [
            'favorites' => '📖 Tủ truyện',
            'reading'   => '📌 Đang đọc',
            'purchased' => '💎 Đã mua',
            'history'   => '🕐 Lịch sử đọc',
            'wallet'    => '💎 Ví hạt',
            'notifications' => '🔔 Thông báo',
        ];
        foreach ($tabs as $key => $label) {
            $is_active = $tab === $key;
            $url = home_url('/tai-khoan?tab=' . $key);
            ?>
            <a href="<?php echo esc_url($url); ?>" class="account-tab <?php echo $is_active ? 'active' : ''; ?>"
               style="padding:12px 20px;text-decoration:none;font-weight:600;color:var(--color-text-<?php echo $is_active ? 'primary' : 'muted'; ?>);border-bottom:3px solid <?php echo $is_active ? 'var(--color-primary)' : 'transparent'; ?>;transition:color 0.2s, border-color 0.2s;">
                <?php echo $label; ?>
            </a>
        <?php } ?>
    </nav>

    <?php
    switch ($tab) {
        case 'favorites':
            $data = HDK_DB::get_favorites($user_id, $page);
            $stories = $data['stories'];
            ?>
            <?php if (empty($stories)): ?>
                <div class="empty-state" style="text-align:center;padding:48px 0;">
                    <div style="font-size:48px;margin-bottom:16px;">📖</div>
                    <p style="color:var(--color-text-muted);margin-bottom:16px;">Bạn chưa yêu thích truyện nào.</p>
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
                <div class="empty-state" style="text-align:center;padding:48px 0;">
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
                <div class="empty-state" style="text-align:center;padding:48px 0;">
                    <div style="font-size:48px;margin-bottom:16px;">💎</div>
                    <p style="color:var(--color-text-muted);margin-bottom:16px;">Khám phá truyện hay để mua bằng hạt</p>
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
                <div class="empty-state" style="text-align:center;padding:48px 0;">
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
                    💳 Nạp hạt
                </button>
                <button type="button" class="btn btn-outline daily-claim-btn" id="daily-claim-btn" onclick="claimDaily()">
                    📅 Điểm danh +<?php echo (int)get_option('hdk_daily_credits', 10); ?> hạt
                </button>
            </div>

            <!-- Purchase Modal -->
            <div id="purchase-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:var(--color-overlay);z-index:999;align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
                <div style="background:var(--color-bg);border-radius:var(--radius-lg);padding:32px;max-width:500px;width:90%;max-height:80vh;overflow-y:auto;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                        <h2 style="margin:0;">Nạp hạt</h2>
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
                                Liên hệ admin để thanh toán và nhận hạt.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <h3 style="margin-bottom:12px;">Lịch sử giao dịch</h3>
            <?php if (empty($transactions)): ?>
                <div class="empty-state" style="text-align:center;padding:48px 0;">
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
                <div class="empty-state" style="text-align:center;padding:48px 0;">
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
    }
    ?>
</div>

<?php get_footer(); ?>
