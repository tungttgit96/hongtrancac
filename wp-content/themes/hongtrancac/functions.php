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
    wp_enqueue_style('hdk-font', 'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap', [], null);
    wp_enqueue_style('hdk-main', get_template_directory_uri() . '/assets/css/main.css', [], wp_get_theme()->get('Version'));
    wp_enqueue_script('alpinejs', 'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js', [], '3', ['strategy' => 'defer']);
    wp_enqueue_script('hdk-main', get_template_directory_uri() . '/assets/js/main.js', ['alpinejs'], wp_get_theme()->get('Version'), true);
    wp_localize_script('hdk-main', 'hdkApi', [
        'nonce' => wp_create_nonce('wp_rest'),
    ]);
}
add_action('wp_enqueue_scripts', 'hdk_enqueue_assets');

// Include template parts
require_once get_template_directory() . '/inc/template-functions.php';
require_once get_template_directory() . '/inc/design-tokens.php';
