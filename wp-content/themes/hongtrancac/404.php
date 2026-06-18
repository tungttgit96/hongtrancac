<?php
/**
 * 404 Template
 */

get_header();
?>
<div class="container" style="padding:80px 0;text-align:center;">
    <div style="font-size:100px;line-height:1;margin-bottom:16px;"><?php echo hdk_icon('search', ['size' => '100px']); ?></div>
    <h1 style="font-size:var(--font-size-3xl);font-weight:700;margin-bottom:12px;">404 - Không tìm thấy</h1>
    <p style="color:var(--color-text-muted);font-size:var(--font-size-lg);margin-bottom:24px;">
        Trang bạn đang tìm không tồn tại hoặc đã bị di chuyển.
    </p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="<?php echo home_url('/'); ?>" class="btn btn-primary">Về trang chủ</a>
        <a href="<?php echo home_url('/danh-sach-truyen'); ?>" class="btn btn-outline">Danh sách truyện</a>
    </div>
</div>
<?php get_footer(); ?>
