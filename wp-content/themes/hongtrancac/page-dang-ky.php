<?php
/**
 * Template Name: Đăng ký
 * Page: Gmail-only registration with email verification
 */

$redirect_to = sanitize_url($_GET['redirect_to'] ?? ($_POST['redirect_to'] ?? home_url('/tai-khoan')));
if (is_user_logged_in()) {
    wp_redirect($redirect_to);
    exit;
}

$errors = [];
$success = '';
$step = 'request';
$pending_email = '';
$pending_login = '';
$pending_name = '';

if (!function_exists('hdk_registration_email_is_gmail')) {
    function hdk_registration_email_is_gmail($email) {
        return (bool)preg_match('/^[A-Z0-9._%+\-]+@gmail\.com$/i', $email);
    }
}

if (!function_exists('hdk_registration_transient_key')) {
    function hdk_registration_transient_key($email) {
        return 'hdk_reg_' . md5(strtolower(trim($email)));
    }
}

if (!function_exists('hdk_registration_validate_identity')) {
    function hdk_registration_validate_identity($email, $user_login, $display_name) {
        $errors = [];

        if (!is_email($email) || !hdk_registration_email_is_gmail($email)) {
            $errors[] = 'Chỉ chấp nhận email Gmail, ví dụ tenban@gmail.com.';
        }
        if (email_exists($email)) {
            $errors[] = 'Email này đã được đăng ký.';
        }

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

        return $errors;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hdk_send_registration_code'])) {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'hdk_send_registration_code')) {
        $errors[] = 'Phiên đăng ký hết hạn, vui lòng thử lại.';
    } else {
        $pending_email = strtolower(sanitize_email($_POST['email'] ?? ''));
        $pending_login = sanitize_user($_POST['user_login'] ?? '', true);
        $pending_name = sanitize_text_field($_POST['display_name'] ?? '');
        $errors = hdk_registration_validate_identity($pending_email, $pending_login, $pending_name);

        if (!$errors) {
            $code = (string)random_int(100000, 999999);
            $payload = [
                'email' => $pending_email,
                'user_login' => $pending_login,
                'display_name' => $pending_name,
                'code_hash' => wp_hash_password($code),
                'attempts' => 0,
                'created_at' => time(),
            ];

            $sent = wp_mail(
                $pending_email,
                'Mã xác minh Hồng Trần Các',
                "Mã xác minh đăng ký của bạn là: {$code}\n\nMã có hiệu lực trong 15 phút."
            );

            if ($sent) {
                set_transient(hdk_registration_transient_key($pending_email), $payload, 15 * MINUTE_IN_SECONDS);
                $step = 'verify';
                $success = 'Mã xác minh đã được gửi tới Gmail của bạn.';
            } else {
                $errors[] = 'Không gửi được email xác minh. Vui lòng kiểm tra cấu hình gửi mail của website.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hdk_verify_registration_code'])) {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'hdk_verify_registration_code')) {
        $errors[] = 'Phiên xác minh hết hạn, vui lòng thử lại.';
    } else {
        $pending_email = strtolower(sanitize_email($_POST['email'] ?? ''));
        $payload = get_transient(hdk_registration_transient_key($pending_email));
        $code = preg_replace('/\D+/', '', $_POST['verification_code'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $step = 'verify';

        if (!$payload || empty($payload['email']) || $payload['email'] !== $pending_email) {
            $errors[] = 'Mã xác minh đã hết hạn. Vui lòng gửi mã mới.';
            $step = 'request';
        } elseif (($payload['attempts'] ?? 0) >= 5) {
            delete_transient(hdk_registration_transient_key($pending_email));
            $errors[] = 'Bạn đã nhập sai quá nhiều lần. Vui lòng gửi mã mới.';
            $step = 'request';
        } elseif (!wp_check_password($code, $payload['code_hash'])) {
            $payload['attempts'] = (int)($payload['attempts'] ?? 0) + 1;
            set_transient(hdk_registration_transient_key($pending_email), $payload, 15 * MINUTE_IN_SECONDS);
            $errors[] = 'Mã xác minh không đúng.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Mật khẩu cần ít nhất 8 ký tự.';
        } elseif ($password !== $password_confirm) {
            $errors[] = 'Mật khẩu nhập lại không khớp.';
        } else {
            $identity_errors = hdk_registration_validate_identity($payload['email'], $payload['user_login'], $payload['display_name']);
            if ($identity_errors) {
                $errors = array_merge($errors, $identity_errors);
                $step = 'request';
            } else {
                $user_id = wp_insert_user([
                    'user_login' => $payload['user_login'],
                    'user_email' => $payload['email'],
                    'display_name' => $payload['display_name'],
                    'user_pass' => $password,
                    'role' => 'subscriber',
                ]);

                if (is_wp_error($user_id)) {
                    $errors[] = $user_id->get_error_message();
                } else {
                    delete_transient(hdk_registration_transient_key($pending_email));
                    wp_set_current_user($user_id);
                    wp_set_auth_cookie($user_id, true, is_ssl());
                    wp_redirect($redirect_to);
                    exit;
                }
            }
        }
    }
}

get_header();
?>

<div class="container page-shell" style="padding-bottom:48px;">
    <div style="max-width:460px;margin:0 auto;" class="panel panel-pad">
        <h1 style="font-size:var(--font-size-2xl);font-weight:700;margin-bottom:8px;text-align:center;">Đăng ký</h1>
        <p style="color:var(--color-text-muted);text-align:center;margin-bottom:24px;">Dùng Gmail để nhận mã xác minh tài khoản.</p>

        <?php if ($errors): ?>
            <div style="border:1px solid var(--color-danger);color:var(--color-danger);border-radius:var(--radius-sm);padding:10px 12px;margin-bottom:16px;font-size:var(--font-size-sm);">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo esc_html($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div style="border:1px solid var(--color-success);color:var(--color-success);border-radius:var(--radius-sm);padding:10px 12px;margin-bottom:16px;font-size:var(--font-size-sm);">
                <?php echo esc_html($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($step === 'verify'): ?>
            <?php
            $payload = get_transient(hdk_registration_transient_key($pending_email));
            $pending_login = $payload['user_login'] ?? $pending_login;
            $pending_name = $payload['display_name'] ?? $pending_name;
            ?>
            <form method="post">
                <?php wp_nonce_field('hdk_verify_registration_code'); ?>
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
                <input type="hidden" name="email" value="<?php echo esc_attr($pending_email); ?>">
                <input type="hidden" name="hdk_verify_registration_code" value="1">

                <p style="color:var(--color-text-muted);font-size:var(--font-size-sm);margin-bottom:16px;">
                    Gmail: <strong><?php echo esc_html($pending_email); ?></strong><br>
                    Tên đăng nhập: <strong><?php echo esc_html($pending_login); ?></strong>
                </p>
                <p>
                    <label for="hdk-verification-code">Mã xác minh</label>
                    <input type="text" name="verification_code" id="hdk-verification-code" inputmode="numeric" maxlength="6" required autocomplete="one-time-code" style="width:100%;">
                </p>
                <p>
                    <label for="hdk-register-password">Mật khẩu</label>
                    <input type="password" name="password" id="hdk-register-password" required autocomplete="new-password" minlength="8" style="width:100%;">
                </p>
                <p>
                    <label for="hdk-register-password-confirm">Nhập lại mật khẩu</label>
                    <input type="password" name="password_confirm" id="hdk-register-password-confirm" required autocomplete="new-password" minlength="8" style="width:100%;">
                </p>
                <button type="submit" class="btn btn-primary" style="width:100%;">Xác minh và tạo tài khoản</button>
            </form>
        <?php else: ?>
            <form method="post">
                <?php wp_nonce_field('hdk_send_registration_code'); ?>
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
                <input type="hidden" name="hdk_send_registration_code" value="1">

                <p>
                    <label for="hdk-register-email">Gmail</label>
                    <input type="email" name="email" id="hdk-register-email" value="<?php echo esc_attr($pending_email); ?>" required autocomplete="email" pattern="^[A-Za-z0-9._%+\-]+@gmail\.com$" placeholder="tenban@gmail.com" style="width:100%;">
                </p>
                <p>
                    <label for="hdk-register-login">Tên đăng nhập</label>
                    <input type="text" name="user_login" id="hdk-register-login" value="<?php echo esc_attr($pending_login); ?>" required autocomplete="username" style="width:100%;">
                </p>
                <p>
                    <label for="hdk-register-name">Tên hiển thị</label>
                    <input type="text" name="display_name" id="hdk-register-name" value="<?php echo esc_attr($pending_name); ?>" required autocomplete="name" style="width:100%;">
                </p>
                <button type="submit" class="btn btn-primary" style="width:100%;">Gửi mã xác minh</button>
            </form>
        <?php endif; ?>

        <div style="display:flex;justify-content:center;margin-top:16px;padding-top:16px;border-top:1px solid var(--color-border-light);font-size:var(--font-size-sm);">
            <a href="<?php echo esc_url(hdk_login_url($redirect_to)); ?>" style="color:var(--color-primary);">Đã có tài khoản? Đăng nhập</a>
        </div>
    </div>
</div>

<?php get_footer(); ?>
