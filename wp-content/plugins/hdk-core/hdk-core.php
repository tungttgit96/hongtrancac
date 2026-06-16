<?php
/**
 * Plugin Name: HDK Core
 * Plugin URI: https://hongtrancac.com
 * Description: Core plugin for Hồng Trần Các - custom DB tables, rewrite rules, REST API, admin CMS, sitemap.
 * Version: 1.0.0
 * Author: HDK Team
 * Text Domain: hongtrancac
 */

defined('ABSPATH') || exit;

/**
 * Let local development run from 127.0.0.1/localhost even if the DB was
 * installed with a .test domain. This keeps home_url(), site_url(), assets,
 * redirects, canonical URLs and REST links on the current loopback host.
 */
function hdk_local_request_origin() {
    $host_header = $_SERVER['HTTP_HOST'] ?? '';
    if ($host_header === '') {
        return '';
    }

    $host = strtolower($host_header);
    if (str_starts_with($host, '[')) {
        $host_only = trim(strstr($host, ']', true) ?: $host, '[]');
    } else {
        $host_only = preg_replace('/:\d+$/', '', $host);
    }

    if (!in_array($host_only, ['127.0.0.1', 'localhost', '::1'], true)) {
        return '';
    }

    $scheme = is_ssl() ? 'https' : 'http';
    return $scheme . '://' . $host_header;
}

function hdk_filter_local_site_url($value) {
    return hdk_local_request_origin() ?: $value;
}

function hdk_filter_local_absolute_url($url) {
    $origin = hdk_local_request_origin();
    if ($origin === '' || !is_string($url) || $url === '') {
        return $url;
    }

    $url = str_replace(
        [
            'https://hongtrancac.test',
            'http://hongtrancac.test',
            'http://localhost/hongtrancac',
            'https://localhost/hongtrancac',
        ],
        $origin,
        $url
    );

    return preg_replace('#https?://(?:127\.0\.0\.1|localhost|\[::1\])(?::\d+)?#i', $origin, $url);
}

add_filter('option_home', 'hdk_filter_local_site_url', 1);
add_filter('option_siteurl', 'hdk_filter_local_site_url', 1);
add_filter('clean_url', 'hdk_filter_local_absolute_url', 1);
add_filter('content_url', 'hdk_filter_local_absolute_url', 1);
add_filter('plugins_url', 'hdk_filter_local_absolute_url', 1);
add_filter('script_loader_src', 'hdk_filter_local_absolute_url', 1);
add_filter('style_loader_src', 'hdk_filter_local_absolute_url', 1);
add_filter('stylesheet_directory_uri', 'hdk_filter_local_absolute_url', 1);
add_filter('template_directory_uri', 'hdk_filter_local_absolute_url', 1);

define('HDK_VERSION', '1.0.0');
define('HDK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HDK_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload
require_once HDK_PLUGIN_DIR . 'includes/class-activator.php';
require_once HDK_PLUGIN_DIR . 'includes/class-db.php';
require_once HDK_PLUGIN_DIR . 'includes/class-schema.php';
require_once HDK_PLUGIN_DIR . 'includes/class-rewrite.php';
require_once HDK_PLUGIN_DIR . 'includes/class-rest-api.php';
require_once HDK_PLUGIN_DIR . 'includes/class-seo.php';
require_once HDK_PLUGIN_DIR . 'includes/class-sitemap.php';
require_once HDK_PLUGIN_DIR . 'includes/class-admin.php';
require_once HDK_PLUGIN_DIR . 'includes/class-cache.php';
require_once HDK_PLUGIN_DIR . 'includes/class-template-loader.php';
require_once HDK_PLUGIN_DIR . 'includes/class-protection.php';
require_once HDK_PLUGIN_DIR . 'includes/class-cli.php';
require_once HDK_PLUGIN_DIR . 'includes/class-media-compress.php';

// Protection: anti-crawl, anti-webtoepub
HDK_Protection::init();

// Media upload compressor - must register early, before admin_menu
HDK_Media_Compress::init();

// Activation / Deactivation
register_activation_hook(__FILE__, ['HDK_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['HDK_Activator', 'deactivate']);

// Runtime schema migrations for active installs
add_action('plugins_loaded', ['HDK_Schema', 'maybe_upgrade']);

// Init
add_action('init', ['HDK_Rewrite', 'init']);
add_action('rest_api_init', ['HDK_REST_API', 'init']);
add_action('admin_menu', ['HDK_Admin', 'init']);
add_action('init', ['HDK_Sitemap', 'init']);
add_action('template_redirect', ['HDK_Template_Loader', 'init']);
HDK_SEO::init();

// Cache invalidation on story update
add_action('hdk_story_updated', ['HDK_Cache', 'invalidate_story']);
add_action('hdk_chapter_updated', ['HDK_Cache', 'invalidate_chapter']);

// Cron: auto-publish scheduled chapters every 5 minutes
add_action('hdk_publish_scheduled_chapters', ['HDK_Cache', 'publish_scheduled']);
if (!wp_next_scheduled('hdk_publish_scheduled_chapters')) {
    wp_schedule_event(time(), 'five_minutes', 'hdk_publish_scheduled_chapters');
}

// Add 5-minute cron interval
add_filter('cron_schedules', function($schedules) {
    $schedules['five_minutes'] = ['interval' => 300, 'display' => 'Every 5 Minutes'];
    return $schedules;
});
