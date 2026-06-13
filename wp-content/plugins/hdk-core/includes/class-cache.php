<?php
/**
 * HDK Cache - simple transient-based cache for categories, home, ranking
 */

class HDK_Cache {
    const PREFIX = 'hdk_';
    const TTL_STORY = 3600;
    const TTL_LIST = 300; // 5 min
    const TTL_HOME = 600; // 10 min

    public static function get($key) {
        return get_transient(self::PREFIX . $key);
    }

    public static function set($key, $data, $ttl = 300) {
        set_transient(self::PREFIX . $key, $data, $ttl);
    }

    public static function delete($key) {
        delete_transient(self::PREFIX . $key);
    }

    public static function invalidate_story($story_id) {
        self::delete('story_' . $story_id);
        self::delete('home_sections');
        self::delete('home_new');
        self::delete('home_hot');
        self::delete('home_completed');
        self::delete('ranking_views_all');
    }

    public static function invalidate_chapter($story_id) {
        self::invalidate_story($story_id);
    }

    public static function publish_scheduled() {
        global $wpdb;
        $now = current_time('mysql');
        $table = HDK_DB::table('hdk_chapters');
        $wpdb->query($wpdb->prepare(
            "UPDATE $table SET status = 'published', scheduled_at = NULL, updated_at = %s WHERE status = 'scheduled' AND scheduled_at <= %s",
            $now, $now
        ));
    }
}
