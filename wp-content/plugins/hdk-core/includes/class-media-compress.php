<?php
/**
 * HDK Media Compress - auto-compress oversized image uploads before they reach the server.
 * Registered early at plugin bootstrap, independent of admin-menu lifecycle.
 */
class HDK_Media_Compress {

    public static function init() {
        add_filter('plupload_default_settings', [__CLASS__, 'raise_plupload_limit']);
        add_filter('plupload_init', [__CLASS__, 'raise_plupload_limit']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_compressor']);
    }

    /**
     * Raise Plupload client-side max so oversized images can enter the queue
     * for JS-side Canvas compression. Uses max(50MB, server limit) for safety.
     *
     * plupload_default_settings covers the wp.media() modal uploader;
     * plupload_init covers the legacy async uploader.
     */
    public static function raise_plupload_limit($settings) {
        if (!isset($settings['filters']) || !is_array($settings['filters'])) {
            $settings['filters'] = [];
        }
        $source_max_bytes = max(50 * MB_IN_BYTES, wp_max_upload_size());
        $settings['filters']['max_file_size'] = $source_max_bytes . 'b';
        return $settings;
    }

    /**
     * Enqueue the compressor JS on all admin pages for users who can upload.
     * Uses filemtime() for cache busting.
     */
    public static function enqueue_compressor() {
        if (!is_admin() || !current_user_can('upload_files')) {
            return;
        }

        $source_max_bytes = max(50 * MB_IN_BYTES, wp_max_upload_size());
        $js_path = HDK_PLUGIN_DIR . 'assets/js/admin-media-compress.js';
        $ver = file_exists($js_path) ? filemtime($js_path) : HDK_VERSION;

        wp_enqueue_script(
            'hdk-media-compress',
            HDK_PLUGIN_URL . 'assets/js/admin-media-compress.js',
            ['jquery', 'wp-plupload'],
            $ver,
            true
        );

        wp_localize_script('hdk-media-compress', '_hdkMediaCompress', [
            'targetBytes'    => wp_max_upload_size(),
            'sourceMaxBytes' => $source_max_bytes,
            'supportedTypes' => ['image/jpeg', 'image/png', 'image/webp'],
        ]);
    }
}
