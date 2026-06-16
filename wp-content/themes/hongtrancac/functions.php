<?php
/**
 * Hồng Trần Các Theme Functions
 */

// Theme setup
function hdk_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
    add_theme_support('custom-logo');
    add_theme_support('responsive-embeds');

    register_nav_menus([
        'primary' => __('Primary Menu', 'hongtrancac'),
        'footer'  => __('Footer Menu', 'hongtrancac'),
    ]);
}
add_action('after_setup_theme', 'hdk_theme_setup');

// Enqueue assets
function hdk_enqueue_assets() {
    $theme_dir = get_template_directory();
    $theme_uri = get_template_directory_uri();
    $css_version = filemtime($theme_dir . '/assets/css/main.css') ?: wp_get_theme()->get('Version');
    $js_version = filemtime($theme_dir . '/assets/js/main.js') ?: wp_get_theme()->get('Version');

    wp_enqueue_style('hdk-font', 'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap', [], null);
    wp_enqueue_style('hdk-main', $theme_uri . '/assets/css/main.css', [], $css_version);
    wp_enqueue_script('alpinejs', 'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js', [], '3', ['strategy' => 'defer']);
    wp_enqueue_script('hdk-main', $theme_uri . '/assets/js/main.js', ['alpinejs'], $js_version, true);
    wp_localize_script('hdk-main', 'hdkApi', [
        'nonce' => wp_create_nonce('wp_rest'),
        'loginUrl' => function_exists('hdk_login_url') ? hdk_login_url(home_url(add_query_arg([]))) : wp_login_url(home_url(add_query_arg([]))),
    ]);
}
add_action('wp_enqueue_scripts', 'hdk_enqueue_assets');

// Expose REST nonce early for inline scripts
add_action('wp_head', function() {
    echo '<script>window.hdkRestNonce = ' . wp_json_encode(wp_create_nonce('wp_rest')) . ';</script>' . "\n";
}, 1);

// Include template parts
require_once get_template_directory() . '/inc/template-functions.php';

add_action('template_redirect', function() {
    $path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    if ($path === 'danh-sach-truyen' && isset($_GET['s'])) {
        $args = $_GET;
        $args['keyword'] = sanitize_text_field(wp_unslash($args['s']));
        unset($args['s']);
        wp_safe_redirect(add_query_arg($args, home_url('/danh-sach-truyen/')), 301);
        exit;
    }

    if ($path === 'dang-ky') {
        include get_template_directory() . '/page-dang-ky.php';
        exit;
    }
}, 0);
