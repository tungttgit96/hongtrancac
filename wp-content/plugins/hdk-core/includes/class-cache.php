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

        $published = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT c.story_id, s.title, s.slug FROM {$wpdb->prefix}hdk_chapters c
             JOIN {$wpdb->prefix}hdk_stories s ON c.story_id = s.id
             WHERE c.status = 'published' AND c.scheduled_at IS NOT NULL AND c.scheduled_at <= %s",
            current_time('mysql')
        ));
        foreach ($published as $p) {
            $chapter = $wpdb->get_row($wpdb->prepare(
                "SELECT chapter_number, title FROM {$wpdb->prefix}hdk_chapters
                 WHERE story_id = %d AND status = 'published' AND scheduled_at IS NOT NULL
                 ORDER BY chapter_number DESC LIMIT 1",
                $p->story_id
            ));
            if ($chapter) {
                HDK_DB::notify_favoriting_users(
                    $p->story_id, $chapter->chapter_number,
                    $chapter->title ?: 'Chương ' . $chapter->chapter_number,
                    $p->title, $p->slug
                );
            }
        }
    }
}
