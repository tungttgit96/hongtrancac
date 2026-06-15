<?php
/**
 * Template Name: Đăng ký
 * Page: Simple username/password registration
 */

$redirect_to = sanitize_url($_GET['redirect_to'] ?? ($_POST['redirect_to'] ?? home_url('/tai-khoan')));
if (is_user_logged_in()) {
    wp_redirect($redirect_to);
    exit;
}

$errors = [];
$user_login = '';
$display_name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hdk_register_account'])) {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'hdk_register_account')) {
        $errors[] = 'Phiên đăng ký hết hạn, vui lòng thử lại.';
    } else {
        $redirect_to = sanitize_url($_POST['redirect_to'] ?? $redirect_to);
        $user_login = sanitize_user($_POST['user_login'] ?? '', true);
        $display_name = sanitize_text_field($_POST['display_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if ($user_login === '') {
            $errors[] = 'Vui lòng nhập tên đăng nhập.';
        } elseif (!validate_username($user_login)) {
            $errors[] = 'Tên đăng nhập chỉ nên dùng chữ, số, dấu gạch dưới hoặc gạch ngang.';
        } elseif (username_exists($user_login)) {
            $errors[] = 'Tên đăng nhập này đã tồn tại.';
        }

        if ($display_name === '') {
            $errors[] = 'Vui lòng nhập tên hiển thị.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Mật khẩu cần ít nhất 8 ký tự.';
        } elseif ($password !== $password_confirm) {
            $errors[] = 'Mật khẩu nhập lại không khớp.';
        }

        if (!$errors) {
            $placeholder_email = $user_login . '@no-email.hongtrancac.local';
            $user_id = wp_insert_user([
                'user_login' => $user_login,
                'user_email' => $placeholder_email,
                'display_name' => $display_name,
                'user_pass' => $password,
                'role' => 'subscriber',
            ]);

            if (is_wp_error($user_id)) {
                $errors[] = $user_id->get_error_message();
            } else {
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id, true, is_ssl());
                wp_redirect($redirect_to);
                exit;
            }
        }
    }
}

get_header();
?>

<div class="container page-shell" style="padding-bottom:48px;">
    <div style="max-width:460px;margin:0 auto;" class="panel panel-pad">
        <h1 style="font-size:var(--font-size-2xl);font-weight:700;margin-bottom:8px;text-align:center;">Đăng ký</h1>
        <p style="color:var(--color-text-muted);text-align:center;margin-bottom:24px;">Tạo tài khoản đọc truyện bằng tên đăng nhập và mật khẩu.</p>

        <?php if ($errors): ?>
            <div style="border:1px solid var(--color-danger);color:var(--color-danger);border-radius:var(--radius-sm);padding:10px 12px;margin-bottom:16px;font-size:var(--font-size-sm);">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo esc_html($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <?php wp_nonce_field('hdk_register_account'); ?>
            <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
            <input type="hidden" name="hdk_register_account" value="1">

            <p>
                <label for="hdk-register-login">Tên đăng nhập</label>
                <input type="text" name="user_login" id="hdk-register-login" value="<?php echo esc_attr($user_login); ?>" required autocomplete="username" style="width:100%;">
            </p>
            <p>
                <label for="hdk-register-name">Tên hiển thị</label>
                <input type="text" name="display_name" id="hdk-register-name" value="<?php echo esc_attr($display_name); ?>" required autocomplete="name" style="width:100%;">
            </p>
            <p>
                <label for="hdk-register-password">Mật khẩu</label>
                <input type="password" name="password" id="hdk-register-password" required autocomplete="new-password" minlength="8" style="width:100%;">
            </p>
            <p>
                <label for="hdk-register-password-confirm">Nhập lại mật khẩu</label>
                <input type="password" name="password_confirm" id="hdk-register-password-confirm" required autocomplete="new-password" minlength="8" style="width:100%;">
            </p>
            <button type="submit" class="btn btn-primary" style="width:100%;">Tạo tài khoản</button>
        </form>

        <div style="display:flex;justify-content:center;margin-top:16px;padding-top:16px;border-top:1px solid var(--color-border-light);font-size:var(--font-size-sm);">
            <a href="<?php echo esc_url(hdk_login_url($redirect_to)); ?>" style="color:var(--color-primary);">Đã có tài khoản? Đăng nhập</a>
        </div>
    </div>
</div>

<?php get_footer(); ?>
