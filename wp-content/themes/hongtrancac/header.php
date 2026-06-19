<?php
/**
 * Header template - Hồng Trần Các
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <script>
        (function(){var t='light';try{var s=localStorage.getItem('hdk-theme');t=s||(window.matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light')}catch(e){}try{document.documentElement.setAttribute('data-theme',t)}catch(e){}})();
    </script>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <div class="container" style="display:flex;align-items:center;justify-content:space-between;height:100%;gap:16px;">
        <a href="<?php echo home_url('/'); ?>" class="site-logo" style="font-size:var(--font-size-xl);font-weight:700;color:var(--color-primary);display:flex;align-items:center;gap:8px;text-decoration:none;">
            <?php echo hdk_icon('castle'); ?> Hồng Trần Các
        </a>

        <nav class="main-nav" style="display:flex;align-items:center;gap:4px;" id="main-nav">
            <a href="<?php echo home_url('/danh-sach-truyen'); ?>" class="btn btn-ghost btn-sm">Danh sách</a>
            <a href="<?php echo home_url('/bang-xep-hang'); ?>" class="btn btn-ghost btn-sm">Xếp hạng</a>
            <a href="<?php echo home_url('/the-loai'); ?>" class="btn btn-ghost btn-sm">Thể loại</a>
            <a href="<?php echo home_url('/hoan-thanh'); ?>" class="btn btn-ghost btn-sm">Hoàn thành</a>
            <a href="<?php echo home_url('/truyen-free'); ?>" class="btn btn-ghost btn-sm">Free</a>
        </nav>

        <div class="header-actions" style="display:flex;align-items:center;gap:8px;">
            <button type="button" class="btn btn-ghost btn-sm theme-toggle" id="theme-toggle" aria-label="Chuyển chế độ sáng/tối" aria-pressed="false" style="min-height:var(--touch-target);min-width:var(--touch-target);"><?php echo hdk_icon('sun'); ?></button>
            <button type="button" class="btn btn-ghost btn-sm search-toggle" aria-label="Tìm kiếm" aria-controls="search-modal" aria-expanded="false" style="min-height:var(--touch-target);min-width:var(--touch-target);">
                <?php echo hdk_icon('search'); ?>
            </button>
            <?php if (is_user_logged_in()): ?>
                <span id="notif-bell" style="position:relative;cursor:pointer;min-height:var(--touch-target);min-width:var(--touch-target);display:inline-flex;align-items:center;justify-content:center;" onclick="toggleNotifDropdown()" aria-label="Thông báo">
                    <?php echo hdk_icon('bell'); ?>
                    <span id="notif-badge" style="display:none;position:absolute;top:2px;right:2px;background:var(--color-danger);color:var(--color-on-danger);border-radius:10px;padding:0 5px;font-size:10px;font-weight:700;min-width:16px;text-align:center;line-height:16px;"></span>
                </span>
                <!-- Notification Dropdown -->
                <div id="notif-dropdown" style="display:none;position:absolute;top:100%;right:0;min-width:320px;max-width:90vw;background:var(--color-bg);border:1px solid var(--color-border);border-radius:var(--radius-md);box-shadow:0 4px 16px rgba(0,0,0,0.12);z-index:150;max-height:400px;overflow-y:auto;margin-top:4px;">
                    <div style="padding:12px 16px;border-bottom:1px solid var(--color-border-light);display:flex;justify-content:space-between;align-items:center;">
                        <strong>Thông báo</strong>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="markAllRead()" style="font-size:var(--font-size-sm);">Đánh dấu đã đọc</button>
                    </div>
                    <div id="notif-list" style="padding:8px 0;">
                        <div style="padding:16px;text-align:center;color:var(--color-text-muted);">Đang tải…</div>
                    </div>
                    <a href="<?php echo esc_url(hdk_page_url('tai-khoan', ['tab' => 'notifications'])); ?>" style="display:block;padding:10px 16px;text-align:center;border-top:1px solid var(--color-border-light);text-decoration:none;color:var(--color-primary);font-size:var(--font-size-sm);">Xem tất cả</a>
                </div>
                <?php
                $current_user = wp_get_current_user();
                $credits_table = HDK_DB::table('hdk_user_credits');
                $credits = (int)$GLOBALS['wpdb']->get_var($GLOBALS['wpdb']->prepare("SELECT credits FROM $credits_table WHERE user_id = %d", get_current_user_id()));
                ?>
                <div class="user-dropdown" id="user-dropdown">
                    <button type="button" class="btn btn-ghost btn-sm user-dropdown-toggle" id="user-dropdown-toggle"
                            aria-haspopup="true" aria-expanded="false"
                            style="min-height:var(--touch-target);display:flex;align-items:center;gap:6px;">
                        <?php echo hdk_icon('user'); ?>
                        <span class="user-name"><?php echo esc_html($current_user->display_name); ?></span>
                        <span class="dropdown-arrow"><?php echo hdk_icon('chevron-down', ['size' => '0.7rem']); ?></span>
                    </button>
                    <div class="user-dropdown-menu" id="user-dropdown-menu"
                         style="display:none;position:absolute;top:100%;right:0;min-width:200px;background:var(--color-bg);border:1px solid var(--color-border);border-radius:var(--radius-md);box-shadow:0 4px 16px rgba(0,0,0,0.12);z-index:150;padding:8px 0;margin-top:4px;">
                        <div class="dropdown-item" style="padding:8px 16px;color:var(--color-text-muted);font-size:var(--font-size-sm);border-bottom:1px solid var(--color-border-light);">
                            <?php echo hdk_icon('gem'); ?> <strong style="color:var(--color-primary);"><?php echo number_format($credits); ?></strong> Linh Thạch
                        </div>
                        <a href="<?php echo home_url('/tai-khoan'); ?>" class="dropdown-item" style="display:block;padding:10px 16px;text-decoration:none;color:var(--color-text-primary);">
                            <?php echo hdk_icon('book-open'); ?> Tài khoản
                        </a>
                        <?php if (current_user_can('manage_options')): ?>
                            <a href="<?php echo admin_url(); ?>" class="dropdown-item" style="display:block;padding:10px 16px;text-decoration:none;color:var(--color-text-primary);">
                                <?php echo hdk_icon('settings'); ?> Admin
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo wp_logout_url(home_url()); ?>" class="dropdown-item" style="display:block;padding:10px 16px;text-decoration:none;color:var(--color-text-primary);border-top:1px solid var(--color-border-light);">
                            <?php echo hdk_icon('log-out'); ?> Đăng xuất
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?php echo esc_url(hdk_login_url(home_url(add_query_arg([])))); ?>" class="btn btn-primary btn-sm auth-login-link">Đăng nhập</a>
                <a href="<?php echo esc_url(hdk_register_url(home_url(add_query_arg([])))); ?>" class="btn btn-outline btn-sm auth-register-link">Đăng ký</a>
            <?php endif; ?>
            <button type="button" class="btn btn-ghost btn-sm mobile-menu-toggle" style="display:none;min-height:var(--touch-target);">
                <?php echo hdk_icon('menu'); ?>
            </button>
        </div>
    </div>
</header>

<!-- Search Modal -->
<div id="search-modal" class="search-modal" aria-hidden="true" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:var(--color-overlay);">
    <div class="search-dialog" role="dialog" aria-modal="true" aria-label="Tìm kiếm truyện">
        <form id="site-search-form" class="search-form" action="<?php echo esc_url(home_url('/danh-sach-truyen/')); ?>" method="get">
            <input type="search" id="site-search-input" name="keyword" aria-label="Tìm truyện" autocomplete="off" placeholder="Tìm truyện, tác giả, thể loại…">
            <button type="submit" class="btn btn-primary btn-sm">Tìm</button>
            <button type="button" class="btn btn-ghost btn-sm" data-close-search aria-label="Đóng tìm kiếm"><?php echo hdk_icon('x'); ?></button>
        </form>
        <div id="site-search-status" class="search-status" aria-live="polite">Nhập ít nhất 2 ký tự để tìm truyện.</div>
        <div id="site-search-results" class="search-results"></div>
    </div>
</div>

<!-- Mobile Drawer -->
<div id="mobile-drawer" class="mobile-drawer" style="position:fixed;top:0;left:0;right:0;bottom:0;background:var(--color-overlay);z-index:150;display:none;">
    <div style="background:var(--color-bg);width:280px;height:100%;padding:20px;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <span style="font-weight:700;">Menu</span>
            <button type="button" class="btn btn-ghost btn-sm" data-close-mobile-drawer aria-label="Đóng menu"><?php echo hdk_icon('x'); ?></button>
        </div>
        <nav style="display:flex;flex-direction:column;gap:4px;">
            <a href="<?php echo home_url('/'); ?>" class="btn btn-ghost">Trang chủ</a>
            <a href="<?php echo home_url('/danh-sach-truyen'); ?>" class="btn btn-ghost">Danh sách truyện</a>
            <a href="<?php echo home_url('/bang-xep-hang'); ?>" class="btn btn-ghost">Bảng xếp hạng</a>
            <a href="<?php echo home_url('/the-loai'); ?>" class="btn btn-ghost">Thể loại</a>
            <a href="<?php echo home_url('/hoan-thanh'); ?>" class="btn btn-ghost">Hoàn thành</a>
            <a href="<?php echo home_url('/truyen-free'); ?>" class="btn btn-ghost">Truyện Free</a>
            <?php if (is_user_logged_in()): ?>
                <a href="<?php echo home_url('/tai-khoan'); ?>" class="btn btn-primary">Tài khoản</a>
                <?php if (current_user_can('manage_options')): ?>
                    <a href="<?php echo admin_url(); ?>" class="btn btn-outline">Admin</a>
                <?php endif; ?>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn btn-ghost">Đăng xuất</a>
            <?php else: ?>
                <a href="<?php echo esc_url(hdk_login_url(home_url(add_query_arg([])))); ?>" class="btn btn-primary">Đăng nhập</a>
                <a href="<?php echo esc_url(hdk_register_url(home_url(add_query_arg([])))); ?>" class="btn btn-outline">Đăng ký</a>
            <?php endif; ?>
        </nav>
    </div>
</div>

<!-- Mobile Bottom Navigation -->
<?php $hdk_account_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : ''; ?>
<nav class="mobile-bottom-nav" id="mobile-bottom-nav" aria-label="Điều hướng chính">
    <a href="<?php echo home_url('/'); ?>" class="bottom-nav-item <?php echo is_front_page() ? 'active' : ''; ?>" aria-label="Trang chủ">
        <span class="bottom-nav-icon hdk-icon-bottom-nav"><?php echo hdk_icon('home'); ?></span>
        <span class="bottom-nav-label">Trang chủ</span>
    </a>
    <a href="<?php echo home_url('/the-loai'); ?>" class="bottom-nav-item <?php echo is_page('the-loai') ? 'active' : ''; ?>" aria-label="Thể loại">
        <span class="bottom-nav-icon hdk-icon-bottom-nav"><?php echo hdk_icon('folder'); ?></span>
        <span class="bottom-nav-label">Thể loại</span>
    </a>
    <button type="button" class="bottom-nav-item bottom-nav-search" aria-label="Tìm kiếm" data-bottom-nav-action="search">
        <span class="bottom-nav-icon hdk-icon-bottom-nav"><?php echo hdk_icon('search'); ?></span>
        <span class="bottom-nav-label">Tìm kiếm</span>
    </button>
    <?php if (is_user_logged_in()): ?>
    <a href="<?php echo esc_url(hdk_page_url('tai-khoan', ['tab' => 'favorites'])); ?>" class="bottom-nav-item <?php echo is_page('tai-khoan') && $hdk_account_tab === 'favorites' ? 'active' : ''; ?>" aria-label="Yêu thích">
        <span class="bottom-nav-icon hdk-icon-bottom-nav"><?php echo hdk_icon('heart'); ?></span>
        <span class="bottom-nav-label">Yêu thích</span>
    </a>
    <a href="<?php echo esc_url(hdk_page_url('tai-khoan')); ?>" class="bottom-nav-item <?php echo is_page('tai-khoan') && $hdk_account_tab === '' ? 'active' : ''; ?>" aria-label="Cá nhân">
        <span class="bottom-nav-icon hdk-icon-bottom-nav"><?php echo hdk_icon('user'); ?></span>
        <span class="bottom-nav-label">Cá nhân</span>
    </a>
    <?php else: ?>
    <a href="<?php echo esc_url(hdk_login_url(home_url(add_query_arg([])))); ?>" class="bottom-nav-item" aria-label="Yêu thích">
        <span class="bottom-nav-icon hdk-icon-bottom-nav"><?php echo hdk_icon('heart'); ?></span>
        <span class="bottom-nav-label">Yêu thích</span>
    </a>
    <a href="<?php echo esc_url(hdk_login_url(home_url(add_query_arg([])))); ?>" class="bottom-nav-item" aria-label="Cá nhân">
        <span class="bottom-nav-icon hdk-icon-bottom-nav"><?php echo hdk_icon('user'); ?></span>
        <span class="bottom-nav-label">Cá nhân</span>
    </a>
    <?php endif; ?>
</nav>

<main class="site-main">
