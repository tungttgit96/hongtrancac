<?php

class HDK_Scheduled_Fake_WPDB {
    public $rows_affected = 0;
    public $events = [];
    public $story_updates = [];

    public function prepare($query, ...$args) {
        foreach ($args as $arg) $query = preg_replace('/%[ds]/', (string)$arg, $query, 1);
        return $query;
    }

    public function get_results($query) {
        $this->events[] = 'select_due';
        return [
            (object)['id' => 11, 'story_id' => 7, 'chapter_number' => 3, 'title' => 'Three'],
            (object)['id' => 12, 'story_id' => 7, 'chapter_number' => 4, 'title' => 'Four'],
        ];
    }

    public function get_col($query) {
        $this->events[] = 'select_due';
        return [7];
    }

    public function get_row($query) {
        return (object)['chapter_number' => 4, 'title' => 'Four'];
    }

    public function query($query) {
        if (str_contains($query, "SET status = 'published'")) {
            $this->events[] = 'publish';
            $this->rows_affected = 1;
        }
        return 1;
    }

    public function get_var($query) { return 4; }

    public function update($table, $data, $where) {
        $this->story_updates[] = [$table, $data, $where];
        return 1;
    }
}

require_once dirname(__DIR__) . '/wp-content/plugins/hdk-core/includes/class-cache.php';

hdk_test('scheduled publication selects before update and processes affected story once', function(HDK_TestCase $t) {
    global $wpdb;
    $wpdb = new HDK_Scheduled_Fake_WPDB();
    HDK_DB::$notifications = [];
    $GLOBALS['hdk_deleted_transients'] = [];

    HDK_Cache::publish_scheduled();

    $t->assert_same('select_due', $wpdb->events[0], 'due chapters must be selected first');
    $t->assert_count(2, HDK_DB::$notifications, 'each transitioned chapter notifies once');
    $t->assert_count(1, $wpdb->story_updates, 'story chapter count updates once');
    $t->assert_same(4, $wpdb->story_updates[0][1]['total_chapters']);
    $t->assert_true(in_array('hdk_story_7', $GLOBALS['hdk_deleted_transients'], true));
});
