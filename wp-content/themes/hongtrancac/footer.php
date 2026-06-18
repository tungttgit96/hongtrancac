<?php
/**
 * Footer template - Hồng Trần Các
 */
?>
</main>

<footer class="site-footer" style="background:var(--color-footer-bg);color:var(--color-footer-text);padding:40px 0 24px;margin-top:48px;">
    <div class="container">
        <div class="grid grid-3" style="margin-bottom:24px;">
            <div>
                <h4 style="font-size:var(--font-size-lg);margin-bottom:12px;"><?php echo hdk_icon('castle'); ?> Hồng Trần Các</h4>
                <p style="font-size:var(--font-size-sm);opacity:0.8;line-height:1.6;">
                    Nền tảng đọc truyện chữ online miễn phí, cập nhật liên tục hàng ngày với hàng ngàn truyện hay.
                </p>
            </div>
            <div>
                <h4 style="font-size:var(--font-size-base);margin-bottom:12px;">Liên kết</h4>
                <ul style="list-style:none;display:flex;flex-direction:column;gap:8px;">
                    <li><a href="<?php echo home_url('/danh-sach-truyen'); ?>" class="footer-link">Danh sách truyện</a></li>
                    <li><a href="<?php echo home_url('/bang-xep-hang'); ?>" class="footer-link">Bảng xếp hạng</a></li>
                    <li><a href="<?php echo home_url('/the-loai'); ?>" class="footer-link">Thể loại</a></li>
                    <li><a href="<?php echo home_url('/tin-tuc'); ?>" class="footer-link">Tin tức</a></li>
                </ul>
            </div>
            <div>
                <h4 style="font-size:var(--font-size-base);margin-bottom:12px;">Hỗ trợ</h4>
                <ul style="list-style:none;display:flex;flex-direction:column;gap:8px;">
                    <li><a href="<?php echo home_url('/lien-he'); ?>" class="footer-link">Liên hệ</a></li>
                    <li><a href="<?php echo home_url('/dieu-khoan'); ?>" class="footer-link">Điều khoản</a></li>
                    <li><a href="<?php echo home_url('/chinh-sach-bao-mat'); ?>" class="footer-link">Chính sách bảo mật</a></li>
                </ul>
            </div>
        </div>
        <div style="border-top:1px solid var(--color-footer-border);padding-top:16px;text-align:center;font-size:var(--font-size-xs);color:var(--color-footer-text-muted);">
            &copy; <?php echo date('Y'); ?> Hồng Trần Các. All rights reserved.
        </div>
    </div>
</footer>

<?php wp_footer(); ?>

</body>
</html>
