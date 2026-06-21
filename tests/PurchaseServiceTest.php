<?php

class HDK_Purchase_Fake_WPDB {
    public $rows_affected = 0;
    public $last_error = '';
    public $credits = 100;
    public $purchases = [];
    public $transactions = [];
    public $queries = [];
    public $reads = [];
    public $fail_debit = false;
    public $fail_purchase_insert = false;
    public $fail_transaction_insert = false;
    private $snapshot;

    public function prepare($query, ...$args) {
        foreach ($args as $arg) {
            $replacement = is_int($arg) ? (string)$arg : "'" . addslashes((string)$arg) . "'";
            $query = preg_replace('/%[ds]/', $replacement, $query, 1);
        }
        return $query;
    }

    public function query($query) {
        $this->queries[] = $query;
        if ($query === 'START TRANSACTION') {
            $this->snapshot = [$this->credits, $this->purchases, $this->transactions];
            return true;
        }
        if ($query === 'ROLLBACK') {
            [$this->credits, $this->purchases, $this->transactions] = $this->snapshot;
            return true;
        }
        if ($query === 'COMMIT') return true;
        if (str_contains($query, 'SET credits = credits -')) {
            if ($this->fail_debit || !preg_match('/credits - (\d+)/', $query, $match) || $this->credits < (int)$match[1]) {
                $this->rows_affected = 0;
                return $this->fail_debit ? false : 0;
            }
            $this->credits -= (int)$match[1];
            $this->rows_affected = 1;
            return 1;
        }
        return true;
    }

    public function get_var($query) {
        $this->reads[] = $query;
        if (str_contains($query, 'SELECT credits')) return $this->credits;
        if (str_contains($query, 'is_full = 1')) {
            foreach ($this->purchases as $purchase) if ($purchase['is_full']) return 1;
            return null;
        }
        if (str_contains($query, 'SELECT id FROM') && preg_match('/chapter_number = (\d+)/', $query, $match)) {
            foreach ($this->purchases as $purchase) {
                if ($purchase['chapter_number'] === (int)$match[1]) return 1;
            }
        }
        return null;
    }

    public function insert($table, $data) {
        if ($table === 'hdk_purchased_chapters') {
            if ($this->fail_purchase_insert) return false;
            $this->purchases[] = $data;
            return 1;
        }
        if ($table === 'hdk_credit_transactions') {
            if ($this->fail_transaction_insert) return false;
            $this->transactions[] = $data;
            return 1;
        }
        return 1;
    }

    public function delete($table, $where) {
        if ($table !== 'hdk_purchased_chapters') return 0;
        $before = count($this->purchases);
        $this->purchases = array_values(array_filter($this->purchases, function($purchase) use ($where) {
            return !(!$purchase['is_full'] && $purchase['story_id'] === $where['story_id']);
        }));
        return $before - count($this->purchases);
    }
}

require_once dirname(__DIR__) . '/wp-content/plugins/hdk-core/includes/class-purchase-service.php';

hdk_test('purchase rolls back when debit fails', function(HDK_TestCase $t) {
    global $wpdb;
    $wpdb = new HDK_Purchase_Fake_WPDB();
    $wpdb->fail_debit = true;
    $story = (object)['id' => 9, 'title' => 'Story'];
    $result = HDK_Purchase_Service::purchase_chapter(3, $story, 2, 20);
    $t->assert_instance_of(WP_Error::class, $result);
    $t->assert_same(100, $wpdb->credits);
    $t->assert_count(0, $wpdb->purchases);
});

hdk_test('purchase insert failure restores credits', function(HDK_TestCase $t) {
    global $wpdb;
    $wpdb = new HDK_Purchase_Fake_WPDB();
    $wpdb->fail_purchase_insert = true;
    $story = (object)['id' => 9, 'title' => 'Story'];
    $result = HDK_Purchase_Service::purchase_chapter(3, $story, 2, 20);
    $t->assert_instance_of(WP_Error::class, $result);
    $t->assert_same(100, $wpdb->credits);
    $t->assert_count(0, $wpdb->purchases);
});

hdk_test('duplicate chapter purchase does not debit twice', function(HDK_TestCase $t) {
    global $wpdb;
    $wpdb = new HDK_Purchase_Fake_WPDB();
    $story = (object)['id' => 9, 'title' => 'Story'];
    $first = HDK_Purchase_Service::purchase_chapter(3, $story, 2, 20);
    $second = HDK_Purchase_Service::purchase_chapter(3, $story, 2, 20);
    $t->assert_true($first['success']);
    $t->assert_true($second['already_purchased']);
    $t->assert_same(80, $wpdb->credits);
    $t->assert_count(1, $wpdb->purchases);
    $t->assert_count(1, $wpdb->transactions);
});

hdk_test('failed full purchase keeps prior chapter purchases', function(HDK_TestCase $t) {
    global $wpdb;
    $wpdb = new HDK_Purchase_Fake_WPDB();
    $wpdb->purchases[] = ['story_id' => 9, 'chapter_number' => 2, 'is_full' => 0];
    $wpdb->fail_purchase_insert = true;
    $story = (object)['id' => 9, 'title' => 'Story'];
    $result = HDK_Purchase_Service::purchase_full(3, $story, 50);
    $t->assert_instance_of(WP_Error::class, $result);
    $t->assert_same(100, $wpdb->credits);
    $t->assert_count(1, $wpdb->purchases);
});

hdk_test('purchase locks credit row before checking existing purchases', function(HDK_TestCase $t) {
    global $wpdb;
    $wpdb = new HDK_Purchase_Fake_WPDB();
    $story = (object)['id' => 9, 'title' => 'Story'];
    HDK_Purchase_Service::purchase_chapter(3, $story, 2, 20);
    $t->assert_true(str_contains($wpdb->reads[0], 'FOR UPDATE'), 'first read must lock user credits');
});

hdk_test('transaction log failure restores credits and purchase', function(HDK_TestCase $t) {
    global $wpdb;
    $wpdb = new HDK_Purchase_Fake_WPDB();
    $wpdb->fail_transaction_insert = true;
    $story = (object)['id' => 9, 'title' => 'Story'];
    $result = HDK_Purchase_Service::purchase_chapter(3, $story, 2, 20);
    $t->assert_instance_of(WP_Error::class, $result);
    $t->assert_same(100, $wpdb->credits);
    $t->assert_count(0, $wpdb->purchases);
    $t->assert_count(0, $wpdb->transactions);
});
