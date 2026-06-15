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
        self::invalidate_home();
        self::delete('ranking_views_all');
    }

    public static function invalidate_chapter($story_id) {
        self::invalidate_story($story_id);
    }

    public static function invalidate_home() {
        $keys = ['home_new', 'home_hot', 'home_completed', 'home_free', 'home_editor', 'home_weekly'];
        foreach ($keys as $key) {
            self::delete($key);
        }
    }

    public static function get_home_stories($args, $cache_key, $ttl = self::TTL_HOME) {
        $cached = self::get($cache_key);
        if ($cached !== false) return $cached;
        $result = HDK_DB::get_stories($args);
        self::set($cache_key, $result, $ttl);
        return $result;
    }

    public static function get_home_ranking($metric, $period, $category_id, $page, $per_page, $cache_key, $ttl = self::TTL_HOME) {
        $cached = self::get($cache_key);
        if ($cached !== false) return $cached;
        $result = HDK_DB::get_ranking($metric, $period, $category_id, $page, $per_page, true);
        self::set($cache_key, $result, $ttl);
        return $result;
    }

    public static function publish_scheduled() {
        global $wpdb;
        $now = current_time('mysql');
        $table = HDK_DB::table('hdk_chapters');
        $wpdb->query($wpdb->prepare(
            "UPDATE $table SET status = 'published', scheduled_at = NULL, updated_at = %s WHERE status = 'scheduled' AND scheduled_at <= %s",
            $now, $now
        ));

        // Get affected story IDs before UPDATE (scheduled_at will be NULL after update)
        $affected = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT story_id FROM $table WHERE status = 'scheduled' AND scheduled_at <= %s",
            $now
        ));
        
        foreach ($affected as $story_id) {
            $story = HDK_DB::get_story($story_id);
            if (!$story) continue;
            $chapter = $wpdb->get_row($wpdb->prepare(
                "SELECT chapter_number, title FROM $table
                 WHERE story_id = %d AND status = 'published'
                 ORDER BY chapter_number DESC LIMIT 1",
                $story_id
            ));
            if ($chapter) {
                HDK_DB::notify_favoriting_users(
                    $story_id, $chapter->chapter_number,
                    $chapter->title ?: 'Chương ' . $chapter->chapter_number,
                    $story->title, $story->slug
                );
            }
        }
    }
}
