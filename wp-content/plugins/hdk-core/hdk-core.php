<?php
/**
 * Plugin Name: HDK Core
 * Plugin URI: https://hatdaukhaai.com
 * Description: Core plugin for Hồng Trần Các - custom DB tables, rewrite rules, REST API, admin CMS, sitemap.
 * Version: 1.0.0
 * Author: HDK Team
 * Text Domain: hatdaukhaai
 */

defined('ABSPATH') || exit;

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

// Protection: anti-crawl, anti-webtoepub
HDK_Protection::init();

// Activation / Deactivation
register_activation_hook(__FILE__, ['HDK_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['HDK_Activator', 'deactivate']);

// Init
add_action('init', ['HDK_Rewrite', 'init']);
add_action('rest_api_init', ['HDK_REST_API', 'init']);
add_action('admin_menu', ['HDK_Admin', 'init']);
add_action('init', ['HDK_Sitemap', 'init']);
add_action('template_redirect', ['HDK_Template_Loader', 'init']);
add_action('wp_head', ['HDK_SEO', 'head_meta']);

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
