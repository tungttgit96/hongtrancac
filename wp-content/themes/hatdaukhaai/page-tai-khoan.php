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
$valid_tabs = ['favorites', 'reading', 'purchased', 'history'];
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
        ];
        foreach ($tabs as $key => $label) {
            $is_active = $tab === $key;
            $url = home_url('/tai-khoan?tab=' . $key);
            ?>
            <a href="<?php echo esc_url($url); ?>" class="account-tab <?php echo $is_active ? 'active' : ''; ?>"
               style="padding:12px 20px;text-decoration:none;font-weight:600;color:var(--color-text-<?php echo $is_active ? 'primary' : 'muted'; ?>);border-bottom:3px solid <?php echo $is_active ? 'var(--color-primary)' : 'transparent'; ?>;transition:all 0.2s;">
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
    }
    ?>
</div>

<?php get_footer(); ?>
