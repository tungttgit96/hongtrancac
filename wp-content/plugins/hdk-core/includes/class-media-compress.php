<?php
/**
 * HDK Media Compress - auto-compress oversized image uploads before they reach the server.
 * Registered early at plugin bootstrap, independent of admin-menu lifecycle.
 *
 * Uses the REAL server PHP limit (wp_max_upload_size) as the compression target.
 * Plupload client-side cap is raised to 50 MB so large images can enter the queue
 * for JS-side Canvas compression.
 */
class HDK_Media_Compress {

    public static function init() {
        add_filter('plupload_default_settings', [__CLASS__, 'raise_plupload_limit']);
        add_filter('plupload_init', [__CLASS__, 'raise_plupload_limit']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_compressor']);
    }

    /**
     * Raise Plupload client-side max to 50 MB so oversized images can enter
     * the queue for JS-side Canvas compression.
     *
     * plupload_default_settings covers the wp.media() modal uploader;
     * plupload_init covers the legacy async uploader.
     */
    public static function raise_plupload_limit($settings) {
        if (!isset($settings['filters']) || !is_array($settings['filters'])) {
            $settings['filters'] = [];
        }
        $settings['filters']['max_file_size'] = (50 * MB_IN_BYTES) . 'b';
        return $settings;
    }

    /**
     * Enqueue the compressor JS on all admin pages for users who can upload.
     *
     * hardLimitBytes  = real server PHP upload limit (wp_max_upload_size)
     * targetBytes     = hardLimitBytes - 64 KB (safety margin so compressed
     *                    images are definitely under the server limit)
     * sourceMaxBytes  = 50 MB (Plupload accepts large files for compression)
     */
    public static function enqueue_compressor() {
        if (!is_admin() || !current_user_can('upload_files')) {
            return;
        }

        $hard_limit = wp_max_upload_size();
        $target     = max(1024, $hard_limit - 64 * 1024);
        $source_max = 50 * MB_IN_BYTES;

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
            'hardLimitBytes' => $hard_limit,
            'targetBytes'    => $target,
            'sourceMaxBytes' => $source_max,
            'supportedTypes' => ['image/jpeg', 'image/png', 'image/webp'],
        ]);
    }
}
