<?php
/**
 * Header template - Hạt Đậu Khả Ái
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
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
            <button class="btn btn-ghost btn-sm search-toggle" onclick="document.getElementById('search-modal').classList.toggle('active')" aria-label="Tìm kiếm" style="min-height:var(--touch-target);min-width:var(--touch-target);">
                🔍
            </button>
            <?php if (is_user_logged_in()): ?>
                <a href="<?php echo admin_url(); ?>" class="btn btn-outline btn-sm">Admin</a>
            <?php else: ?>
                <a href="<?php echo wp_login_url(); ?>" class="btn btn-primary btn-sm">Đăng nhập</a>
            <?php endif; ?>
            <button class="btn btn-ghost btn-sm mobile-menu-toggle" onclick="document.getElementById('mobile-drawer').classList.toggle('open')" style="display:none;min-height:var(--touch-target);">
                ☰
            </button>
        </div>
    </div>
</header>

<!-- Search Modal -->
<div id="search-modal" class="search-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:200;" x-data="{q:'',results:{},loading:false}">
    <div style="max-width:600px;margin:80px auto 0;background:var(--color-bg);border-radius:var(--radius-lg);padding:24px;">
        <div style="display:flex;gap:8px;margin-bottom:16px;">
            <input type="text" x-model="q" placeholder="Tìm truyện, tác giả, thể loại..." style="flex:1;padding:10px 16px;border:2px solid var(--color-border);border-radius:var(--radius-pill);font-family:var(--font-family);font-size:var(--font-size-base);min-height:var(--touch-target);"
                @input.debounce.300ms="if(q.length>=2){loading=true;fetch('/wp-json/hdk/v1/search?q='+encodeURIComponent(q)).then(r=>r.json()).then(d=>{results=d;loading=false})}">
            <button class="btn btn-ghost btn-sm search-toggle" onclick="document.getElementById('search-modal').classList.remove('active')" style="min-height:var(--touch-target);">✕</button>
        </div>
        <div x-show="loading" style="text-align:center;padding:20px;">Đang tìm...</div>
        <div x-show="!loading && results.stories && results.stories.length">
            <div style="font-weight:600;margin-bottom:8px;">Truyện</div>
            <template x-for="s in results.stories">
                <a :href="'/<?php echo esc_js($_SERVER['REQUEST_URI'] ?? ''); ?>?s='+s.slug" style="display:flex;align-items:center;gap:12px;padding:8px 0;border-bottom:1px solid var(--color-border-light);text-decoration:none;">
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
<div id="mobile-drawer" class="mobile-drawer" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:150;display:none;">
    <div style="background:var(--color-bg);width:280px;height:100%;padding:20px;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <span style="font-weight:700;">Menu</span>
            <button class="btn btn-ghost btn-sm" onclick="document.getElementById('mobile-drawer').classList.remove('open')">✕</button>
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
