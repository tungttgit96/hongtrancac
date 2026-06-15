<?php
/**
 * Template Name: Đăng nhập
 * Page: Themed reader login
 */

$redirect_to = sanitize_url($_GET['redirect_to'] ?? home_url('/tai-khoan'));

get_header();
?>

<div class="container page-shell" style="padding-bottom:48px;">
    <div style="max-width:420px;margin:0 auto;" class="panel panel-pad">
        <h1 style="font-size:var(--font-size-2xl);font-weight:700;margin-bottom:8px;text-align:center;">Đăng nhập</h1>
        <p style="color:var(--color-text-muted);text-align:center;margin-bottom:24px;">Chào mừng bạn quay lại Hồng Trần Các</p>

        <?php
        wp_login_form([
            'redirect' => $redirect_to,
            'form_id' => 'hdk-login-form',
            'label_username' => 'Tên đăng nhập hoặc email',
            'label_password' => 'Mật khẩu',
            'label_remember' => 'Ghi nhớ đăng nhập',
            'label_log_in' => 'Đăng nhập',
            'remember' => true,
        ]);
        ?>

        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-top:16px;padding-top:16px;border-top:1px solid var(--color-border-light);font-size:var(--font-size-sm);">
            <a href="<?php echo wp_lostpassword_url($redirect_to); ?>" style="color:var(--color-text-muted);">Quên mật khẩu?</a>
            <a href="<?php echo wp_registration_url(); ?>" style="color:var(--color-primary);">Tạo tài khoản mới</a>
        </div>
    </div>
</div>

<?php get_footer(); ?>
