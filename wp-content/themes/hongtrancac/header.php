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

<header class="site-header" style="background:var(--color-bg);border-bottom:1px solid var(--color-border);position:sticky;top:0;z-index:100;height:var(--header-height);">
    <div class="container" style="display:flex;align-items:center;justify-content:space-between;height:100%;gap:16px;">
        <a href="<?php echo home_url('/'); ?>" class="site-logo" style="font-size:var(--font-size-xl);font-weight:700;color:var(--color-primary);display:flex;align-items:center;gap:8px;text-decoration:none;">
            🏯 Hồng Trần Các
        </a>

        <nav class="main-nav" style="display:flex;align-items:center;gap:4px;" id="main-nav">
            <a href="<?php echo home_url('/danh-sach-truyen'); ?>" class="btn btn-ghost btn-sm">Danh sách</a>
            <a href="<?php echo home_url('/bang-xep-hang'); ?>" class="btn btn-ghost btn-sm">Xếp hạng</a>
            <a href="<?php echo home_url('/the-loai'); ?>" class="btn btn-ghost btn-sm">Thể loại</a>
            <a href="<?php echo home_url('/hoan-thanh'); ?>" class="btn btn-ghost btn-sm">Hoàn thành</a>
            <a href="<?php echo home_url('/truyen-free'); ?>" class="btn btn-ghost btn-sm">Free</a>
        </nav>

        <div style="display:flex;align-items:center;gap:8px;">
            <button type="button" class="btn btn-ghost btn-sm theme-toggle" id="theme-toggle" aria-label="Chuyển chế độ sáng/tối" aria-pressed="false" style="min-height:var(--touch-target);min-width:var(--touch-target);font-size:1.15rem;">☀</button>
            <button type="button" class="btn btn-ghost btn-sm search-toggle" aria-label="Tìm kiếm" style="min-height:var(--touch-target);min-width:var(--touch-target);">
                🔍
            </button>
            <?php if (is_user_logged_in()): ?>
                <span id="notif-bell" style="position:relative;cursor:pointer;min-height:var(--touch-target);min-width:var(--touch-target);display:inline-flex;align-items:center;justify-content:center;font-size:1.15rem;" onclick="toggleNotifDropdown()" aria-label="Thông báo">
                    🔔
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
                    <a href="/tai-khoan?tab=notifications" style="display:block;padding:10px 16px;text-align:center;border-top:1px solid var(--color-border-light);text-decoration:none;color:var(--color-primary);font-size:var(--font-size-sm);">Xem tất cả</a>
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
                        <span style="font-size:1rem;">👤</span>
                        <span class="user-name"><?php echo esc_html($current_user->display_name); ?></span>
                        <span class="dropdown-arrow">▾</span>
                    </button>
                    <div class="user-dropdown-menu" id="user-dropdown-menu"
                         style="display:none;position:absolute;top:100%;right:0;min-width:200px;background:var(--color-bg);border:1px solid var(--color-border);border-radius:var(--radius-md);box-shadow:0 4px 16px rgba(0,0,0,0.12);z-index:150;padding:8px 0;margin-top:4px;">
                        <div class="dropdown-item" style="padding:8px 16px;color:var(--color-text-muted);font-size:var(--font-size-sm);border-bottom:1px solid var(--color-border-light);">
                            💎 <strong style="color:var(--color-primary);"><?php echo number_format($credits); ?></strong> hạt
                        </div>
                        <a href="<?php echo home_url('/tai-khoan'); ?>" class="dropdown-item" style="display:block;padding:10px 16px;text-decoration:none;color:var(--color-text-primary);">
                            📖 Tài khoản
                        </a>
                        <?php if (current_user_can('manage_options')): ?>
                            <a href="<?php echo admin_url(); ?>" class="dropdown-item" style="display:block;padding:10px 16px;text-decoration:none;color:var(--color-text-primary);">
                                ⚙ Admin
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo wp_logout_url(home_url()); ?>" class="dropdown-item" style="display:block;padding:10px 16px;text-decoration:none;color:var(--color-text-primary);border-top:1px solid var(--color-border-light);">
                            🚪 Đăng xuất
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?php echo home_url('/dang-nhap'); ?>" class="btn btn-primary btn-sm">Đăng nhập</a>
            <?php endif; ?>
            <button type="button" class="btn btn-ghost btn-sm mobile-menu-toggle" style="display:none;min-height:var(--touch-target);">
                ☰
            </button>
        </div>
    </div>
</header>

<!-- Search Modal -->
<div id="search-modal" class="search-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:var(--color-overlay);z-index:200;" x-data="{q:'',results:{},loading:false}">
    <div style="max-width:600px;margin:80px auto 0;background:var(--color-bg);border-radius:var(--radius-lg);padding:24px;">
        <div style="display:flex;gap:8px;margin-bottom:16px;">
            <input type="text" x-model="q" name="search" aria-label="Tìm truyện" autocomplete="off" placeholder="Tìm truyện, tác giả, thể loại…" style="flex:1;padding:10px 16px;border:2px solid var(--color-input-border);background:var(--color-input-bg);color:var(--color-text-primary);border-radius:var(--radius-pill);font-family:var(--font-family);font-size:var(--font-size-base);min-height:var(--touch-target);"
                @input.debounce.300ms="if(q.length>=2){loading=true;fetch('/wp-json/hdk/v1/search?q='+encodeURIComponent(q)).then(r=>r.json()).then(d=>{results=d;loading=false})}">
            <button type="button" class="btn btn-ghost btn-sm" data-close-search aria-label="Đóng tìm kiếm" style="min-height:var(--touch-target);">✕</button>
        </div>
        <div x-show="loading" aria-live="polite" style="text-align:center;padding:20px;">Đang tìm…</div>
        <div x-show="!loading && results.stories && results.stories.length">
            <div style="font-weight:600;margin-bottom:8px;">Truyện</div>
            <template x-for="s in results.stories">
                <a :href="'/' + s.slug" style="display:flex;align-items:center;gap:12px;padding:8px 0;border-bottom:1px solid var(--color-border-light);text-decoration:none;">
                    <img :src="s.cover_url" style="width:40px;height:56px;object-fit:cover;border-radius:4px;" :alt="s.title">
                    <div>
                        <div style="font-weight:600;color:var(--color-text-primary);" x-text="s.title"></div>
                        <div style="font-size:var(--font-size-xs);color:var(--color-text-muted);" x-text="s.status"></div>
                    </div>
                </a>
            </template>
        </div>
    </div>
</div>

<!-- Mobile Drawer -->
<div id="mobile-drawer" class="mobile-drawer" style="position:fixed;top:0;left:0;right:0;bottom:0;background:var(--color-overlay);z-index:150;display:none;">
    <div style="background:var(--color-bg);width:280px;height:100%;padding:20px;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <span style="font-weight:700;">Menu</span>
            <button type="button" class="btn btn-ghost btn-sm" data-close-mobile-drawer aria-label="Đóng menu">✕</button>
        </div>
        <nav style="display:flex;flex-direction:column;gap:4px;">
            <a href="<?php echo home_url('/'); ?>" class="btn btn-ghost">Trang chủ</a>
            <a href="<?php echo home_url('/danh-sach-truyen'); ?>" class="btn btn-ghost">Danh sách truyện</a>
            <a href="<?php echo home_url('/bang-xep-hang'); ?>" class="btn btn-ghost">Bảng xếp hạng</a>
            <a href="<?php echo home_url('/the-loai'); ?>" class="btn btn-ghost">Thể loại</a>
            <a href="<?php echo home_url('/hoan-thanh'); ?>" class="btn btn-ghost">Hoàn thành</a>
            <a href="<?php echo home_url('/truyen-free'); ?>" class="btn btn-ghost">Truyện Free</a>
        </nav>
    </div>
</div>

<main class="site-main">
