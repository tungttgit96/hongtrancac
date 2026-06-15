<?php
/**
 * Template Name: Đăng nhập
 * Page: Themed reader login
 */

$redirect_to = sanitize_url($_GET['redirect_to'] ?? home_url('/tai-khoan'));
if (is_user_logged_in()) {
    wp_redirect($redirect_to);
    exit;
}

$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hdk_site_login'])) {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'hdk_site_login')) {
        $login_error = 'Phiên đăng nhập hết hạn, vui lòng thử lại.';
    } else {
        $redirect_to = sanitize_url($_POST['redirect_to'] ?? $redirect_to);
        $user = wp_signon([
            'user_login' => sanitize_user($_POST['log'] ?? ''),
            'user_password' => $_POST['pwd'] ?? '',
            'remember' => !empty($_POST['rememberme']),
        ], is_ssl());

        if (is_wp_error($user)) {
            $login_error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        } else {
            wp_redirect($redirect_to);
            exit;
        }
    }
}

get_header();
?>

<div class="container page-shell" style="padding-bottom:48px;">
    <div style="max-width:420px;margin:0 auto;" class="panel panel-pad">
        <h1 style="font-size:var(--font-size-2xl);font-weight:700;margin-bottom:8px;text-align:center;">Đăng nhập</h1>
        <p style="color:var(--color-text-muted);text-align:center;margin-bottom:24px;">Chào mừng bạn quay lại Hồng Trần Các</p>

        <?php if ($login_error): ?>
            <div style="border:1px solid var(--color-danger);color:var(--color-danger);border-radius:var(--radius-sm);padding:10px 12px;margin-bottom:16px;font-size:var(--font-size-sm);">
                <?php echo esc_html($login_error); ?>
            </div>
        <?php endif; ?>

        <form method="post" id="hdk-login-form">
            <?php wp_nonce_field('hdk_site_login'); ?>
            <input type="hidden" name="hdk_site_login" value="1">
            <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">

            <p>
                <label for="hdk-user-login">Tên đăng nhập hoặc email</label>
                <input type="text" name="log" id="hdk-user-login" class="input" value="<?php echo esc_attr($_POST['log'] ?? ''); ?>" required autocomplete="username" style="width:100%;">
            </p>
            <p>
                <label for="hdk-user-pass">Mật khẩu</label>
                <input type="password" name="pwd" id="hdk-user-pass" class="input" required autocomplete="current-password" style="width:100%;">
            </p>
            <p style="display:flex;align-items:center;gap:8px;color:var(--color-text-muted);font-size:var(--font-size-sm);">
                <input type="checkbox" name="rememberme" id="hdk-remember" value="1">
                <label for="hdk-remember">Ghi nhớ đăng nhập</label>
            </p>
            <button type="submit" class="btn btn-primary" style="width:100%;">Đăng nhập</button>
        </form>

        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-top:16px;padding-top:16px;border-top:1px solid var(--color-border-light);font-size:var(--font-size-sm);">
            <a href="<?php echo wp_lostpassword_url($redirect_to); ?>" style="color:var(--color-text-muted);">Quên mật khẩu?</a>
            <a href="<?php echo esc_url(hdk_register_url($redirect_to)); ?>" style="color:var(--color-primary);">Tạo tài khoản mới</a>
        </div>
    </div>
</div>

<?php get_footer(); ?>
