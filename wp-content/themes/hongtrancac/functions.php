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
        'restBase' => rest_url('hdk/v1'),
        'homeUrl' => home_url('/'),
        'currencyLabel' => 'Linh Thạch',
    ]);
}
add_action('wp_enqueue_scripts', 'hdk_enqueue_assets');

// Expose REST nonce early for inline scripts
add_action('wp_head', function() {
    echo '<script>window.hdkRestNonce = ' . wp_json_encode(wp_create_nonce('wp_rest')) . ';</script>' . "\n";
}, 1);

/**
 * Render a Lucide SVG icon inline.
 *
 * Reads the SVG file from assets/icons/{$name}.svg, sanitizes it,
 * and returns safe HTML with ARIA attributes.
 *
 * @param string $name Icon name (kebab-case, e.g. "book-open").
 * @param array  $args {
 *     Optional. Override attributes.
 *     @type string $class Additional CSS classes.
 *     @type string $label Accessible label (sets aria-label + role="img").
 *     @type string $size   Width & height in CSS units (e.g. "1em", "24px").
 *     @type array  $attrs  Extra HTML attributes as key => value pairs.
 * }
 * @return string SVG markup or empty string on failure.
 */
function hdk_icon($name, $args = []) {
    if (!preg_match('/^[a-z][a-z0-9-]*$/', $name)) {
        return '';
    }

    $file = get_template_directory() . '/assets/icons/' . $name . '.svg';
    if (!file_exists($file)) {
        return '';
    }

    $svg = trim(file_get_contents($file));
    if ($svg === '' || $svg === false) {
        return '';
    }

    $svg = preg_replace('/^<\?xml.*?\?>\s*/s', '', $svg);
    $svg = preg_replace('/<!--.*?-->\s*/s', '', $svg);

    $class = 'hdk-icon hdk-icon-' . $name;
    if (!empty($args['class'])) {
        $class .= ' ' . esc_attr($args['class']);
    }

    $has_label = !empty($args['label']);

    // Strip attributes we will inject (avoid duplicates)
    $strip_attrs = 'class|width|height|fill|aria-hidden|aria-label|role';
    if (!empty($args['attrs']) && is_array($args['attrs'])) {
        $strip_attrs .= '|' . implode('|', array_map('preg_quote', array_keys($args['attrs'])));
    }
    $svg = preg_replace('/\s(?:' . $strip_attrs . ')="[^"]*"/', '', $svg);

    // Build all SVG attributes in one shot
    $svg_attrs = ' class="' . $class . '"';

    $user_fill = !empty($args['attrs']) && is_array($args['attrs']) && isset($args['attrs']['fill']);
    if (!$user_fill) {
        $svg_attrs .= ' fill="none"';
    }

    if ($has_label) {
        $svg_attrs .= ' aria-label="' . esc_attr($args['label']) . '" role="img"';
    } else {
        $svg_attrs .= ' aria-hidden="true"';
    }

    if (!empty($args['size'])) {
        $s = esc_attr($args['size']);
        $svg_attrs .= ' width="' . $s . '" height="' . $s . '"';
    }

    if (!empty($args['attrs']) && is_array($args['attrs'])) {
        foreach ($args['attrs'] as $k => $v) {
            $svg_attrs .= ' ' . esc_attr($k) . '="' . esc_attr($v) . '"';
        }
    }

    $svg = preg_replace('/<svg/', '<svg' . $svg_attrs, $svg, 1);

    return $svg;
}

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
