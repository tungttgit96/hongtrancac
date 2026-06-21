<?php

require_once __DIR__ . '/TestCase.php';

$GLOBALS['hdk_tests'] = [];

function hdk_test($name, callable $test) {
    $GLOBALS['hdk_tests'][$name] = $test;
}

if (!class_exists('WP_Error')) {
    class WP_Error {
        private $code;
        private $message;
        private $data;

        public function __construct($code = '', $message = '', $data = null) {
            $this->code = $code;
            $this->message = $message;
            $this->data = $data;
        }

        public function get_error_code() { return $this->code; }
        public function get_error_message() { return $this->message; }
        public function get_error_data() { return $this->data; }
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($value) { return $value instanceof WP_Error; }
}

if (!function_exists('current_time')) {
    function current_time($type) { return '2026-06-21 12:00:00'; }
}

if (!function_exists('delete_transient')) {
    function delete_transient($key) { $GLOBALS['hdk_deleted_transients'][] = $key; return true; }
}

if (!function_exists('get_transient')) {
    function get_transient($key) { return $GLOBALS['hdk_transients'][$key] ?? false; }
}

if (!function_exists('set_transient')) {
    function set_transient($key, $value, $ttl) { $GLOBALS['hdk_transients'][$key] = $value; return true; }
}

if (!function_exists('get_comment')) {
    function get_comment($id) { return $GLOBALS['hdk_comments'][$id] ?? null; }
}

if (!function_exists('get_comment_meta')) {
    function get_comment_meta($id, $key, $single = false) { return $GLOBALS['hdk_comment_meta'][$id][$key] ?? ''; }
}

if (!function_exists('wp_allow_comment')) {
    function wp_allow_comment($data, $avoid_die = false) { return $GLOBALS['hdk_comment_approval'] ?? 0; }
}

if (!class_exists('HDK_DB')) {
    class HDK_DB {
        public static $notifications = [];
        public static function table($name) { return $name; }
        public static function get_story($id) { return (object)['id' => $id, 'title' => 'Story ' . $id, 'slug' => 'story-' . $id]; }
        public static function notify_favoriting_users($story_id, $chapter_number, $chapter_title, $story_title, $story_slug) {
            self::$notifications[] = compact('story_id', 'chapter_number', 'chapter_title', 'story_title', 'story_slug');
        }
    }
}
