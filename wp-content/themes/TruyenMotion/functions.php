<?php
/**
 * TruyenMotion Theme Functions
 * Child theme of hongtrancac
 */

// Enqueue parent theme styles
function truyenmotion_enqueue_assets() {
    $theme_uri = get_stylesheet_directory_uri();

    wp_enqueue_style(
        'hongtrancac-parent',
        get_template_directory_uri() . '/style.css',
        [],
        wp_get_theme('hongtrancac')->get('Version')
    );

    wp_enqueue_style(
        'truyenmotion-main',
        $theme_uri . '/style.css',
        ['hongtrancac-parent'],
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'truyenmotion_enqueue_assets');

// Include template helpers
require_once get_stylesheet_directory() . '/inc/template-functions.php';

// Skip-to-content link for accessibility
add_action('wp_body_open', function() {
    echo '<a href="#main-content" class="skip-link screen-reader-text" style="position:absolute;top:-100px;left:0;background:var(--color-primary);color:var(--color-on-primary);padding:12px 20px;z-index:9999;text-decoration:none;font-weight:600;border-radius:0 0 var(--radius-sm) 0;transition:top 0.2s;">' . esc_html__('Bo qua dieu huong', 'truyenmotion') . '</a>';
}, 1);

// Preconnect for Google Fonts
add_action('wp_head', function() {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    echo '<link rel="dns-prefetch" href="https://www.googletagmanager.com">' . "\n";
}, 1);

// Google Analytics 4 placeholder
add_action('wp_head', function() {
    ?>
    <!-- Google Analytics 4 -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-XXXXXXXXXX');
    </script>
    <?php
}, 20);

// Inject visual breadcrumbs on HDK story/chapter/taxonomy pages
add_action('template_redirect', 'truyenmotion_inject_breadcrumbs');
function truyenmotion_inject_breadcrumbs() {
    global $hdk_story, $hdk_category, $hdk_author;
    if (!is_object($hdk_story) && !is_object($hdk_category) && !is_object($hdk_author)) return;

    // Capture breadcrumbs output
    ob_start();
    hdk_visual_breadcrumbs();
    $bc = ob_get_clean();

    // Start output buffer to inject after header
    ob_start(function($html) use ($bc) {
        $pos = strpos($html, '</header>');
        if ($pos === false) return $html;
        return substr_replace($html, '</header>' . $bc, $pos, strlen('</header>'));
    });
}

// Filter robots.txt to use dynamic sitemap URL
add_filter('robots_txt', function($output, $public) {
    if (!$public) return $output;
    $output .= "\nSitemap: " . home_url('/sitemap.xml') . "\n";
    return $output;
}, 10, 2);
