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
                <h4 style="font-size:var(--font-size-lg);margin-bottom:12px;">🏯 Hồng Trần Các</h4>
                <p style="font-size:var(--font-size-sm);opacity:0.8;line-height:1.6;">
                    Nền tảng đọc truyện chữ online miễn phí, cập nhật liên tục hàng ngày với hàng ngàn truyện hay.
                </p>
            </div>
            <div>
                <h4 style="font-size:var(--font-size-base);margin-bottom:12px;">Liên kết</h4>
                <ul style="list-style:none;display:flex;flex-direction:column;gap:8px;">
                    <li><a href="<?php echo home_url('/danh-sach-truyen'); ?>" style="color:var(--color-footer-link);">Danh sách truyện</a></li>
                    <li><a href="<?php echo home_url('/bang-xep-hang'); ?>" style="color:var(--color-footer-link);">Bảng xếp hạng</a></li>
                    <li><a href="<?php echo home_url('/the-loai'); ?>" style="color:var(--color-footer-link);">Thể loại</a></li>
                    <li><a href="<?php echo home_url('/tin-tuc'); ?>" style="color:var(--color-footer-link);">Tin tức</a></li>
                </ul>
            </div>
            <div>
                <h4 style="font-size:var(--font-size-base);margin-bottom:12px;">Hỗ trợ</h4>
                <ul style="list-style:none;display:flex;flex-direction:column;gap:8px;">
                    <li><a href="#" style="color:var(--color-footer-link);">Liên hệ</a></li>
                    <li><a href="#" style="color:var(--color-footer-link);">Điều khoản</a></li>
                    <li><a href="#" style="color:var(--color-footer-link);">Chính sách bảo mật</a></li>
                </ul>
            </div>
        </div>
        <div style="border-top:1px solid var(--color-footer-border);padding-top:16px;text-align:center;font-size:var(--font-size-xs);color:var(--color-footer-text-muted);">
            &copy; <?php echo date('Y'); ?> Hồng Trần Các. All rights reserved.
        </div>
    </div>
</footer>

<?php wp_footer(); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu
    if (window.innerWidth < 768) {
        document.querySelector('.mobile-menu-toggle').style.display = 'flex';
        document.querySelector('.main-nav').style.display = 'none';
    }
    window.addEventListener('resize', function() {
        var nav = document.querySelector('.main-nav');
        var btn = document.querySelector('.mobile-menu-toggle');
        if (window.innerWidth < 768) {
            btn.style.display = 'flex';
            nav.style.display = 'none';
        } else {
            btn.style.display = 'none';
            nav.style.display = 'flex';
        }
    });

    // Search modal toggle
    document.querySelectorAll('.search-toggle').forEach(function(el) {
        el.addEventListener('click', function() {
            var modal = document.getElementById('search-modal');
            modal.style.display = modal.style.display === 'block' ? 'none' : 'block';
        });
    });

    // Mobile drawer toggle
    document.querySelector('.mobile-menu-toggle').addEventListener('click', function() {
        var drawer = document.getElementById('mobile-drawer');
        drawer.style.display = drawer.style.display === 'block' ? 'none' : 'block';
    });
    document.getElementById('mobile-drawer').addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});
</script>
</body>
</html>
