<?php

class HDK_Purchase_Service {
    public static function purchase_chapter($user_id, $story, $chapter_number, $price) {
        global $wpdb;

        if (!self::begin()) return self::failed();
        if (self::lock_balance($user_id) === null) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('insufficient_credits', 'Không đủ Linh Thạch.', ['status' => 402]);
        }

        $purchase_table = HDK_DB::table('hdk_purchased_chapters');
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $purchase_table WHERE user_id = %d AND story_id = %d AND chapter_number = %d",
            $user_id, $story->id, $chapter_number
        ));
        $has_full = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $purchase_table WHERE user_id = %d AND story_id = %d AND is_full = 1",
            $user_id, $story->id
        ));
        if ($existing || $has_full) {
            $balance = self::balance($user_id);
            $wpdb->query('COMMIT');
            return ['success' => true, 'already_purchased' => true, 'credits_remaining' => $balance];
        }

        $debit = self::debit($user_id, $price);
        if (is_wp_error($debit)) return $debit;

        $inserted = $wpdb->insert($purchase_table, [
            'user_id' => $user_id,
            'story_id' => $story->id,
            'chapter_number' => $chapter_number,
            'is_full' => 0,
            'credits_spent' => $price,
            'created_at' => current_time('mysql'),
        ]);
        if ($inserted === false) return self::rollback();

        $balance = self::balance($user_id);
        if (!self::insert_transaction($user_id, -$price, $balance, 'chapter_purchase', $story->id, 'Mua chương ' . $chapter_number . ' - ' . $story->title)) {
            return self::rollback();
        }

        if ($wpdb->query('COMMIT') === false) return self::rollback();
        return ['success' => true, 'credits_spent' => $price, 'credits_remaining' => $balance];
    }

    public static function purchase_full($user_id, $story, $price) {
        global $wpdb;

        if (!self::begin()) return self::failed();
        if (self::lock_balance($user_id) === null) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('insufficient_credits', 'Không đủ Linh Thạch.', ['status' => 402]);
        }

        $purchase_table = HDK_DB::table('hdk_purchased_chapters');
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $purchase_table WHERE user_id = %d AND story_id = %d AND is_full = 1",
            $user_id, $story->id
        ));
        if ($existing) {
            $balance = self::balance($user_id);
            $wpdb->query('COMMIT');
            return ['success' => true, 'already_purchased' => true, 'credits_remaining' => $balance];
        }

        $debit = self::debit($user_id, $price);
        if (is_wp_error($debit)) return $debit;

        $wpdb->delete($purchase_table, ['user_id' => $user_id, 'story_id' => $story->id, 'is_full' => 0]);
        $inserted = $wpdb->insert($purchase_table, [
            'user_id' => $user_id,
            'story_id' => $story->id,
            'chapter_number' => 0,
            'is_full' => 1,
            'credits_spent' => $price,
            'created_at' => current_time('mysql'),
        ]);
        if ($inserted === false) return self::rollback();

        $balance = self::balance($user_id);
        if (!self::insert_transaction($user_id, -$price, $balance, 'full_purchase', $story->id, 'Mua full truyện - ' . $story->title)) {
            return self::rollback();
        }

        if ($wpdb->query('COMMIT') === false) return self::rollback();
        return ['success' => true, 'credits_spent' => $price, 'credits_remaining' => $balance];
    }

    private static function begin() {
        global $wpdb;
        return $wpdb->query('START TRANSACTION') !== false;
    }

    private static function debit($user_id, $price) {
        global $wpdb;
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE " . HDK_DB::table('hdk_user_credits') . " SET credits = credits - %d, total_spent = total_spent + %d WHERE user_id = %d AND credits >= %d",
            $price, $price, $user_id, $price
        ));
        if ($result === false || (int)$wpdb->rows_affected !== 1) {
            $wpdb->query('ROLLBACK');
            if ($result === false || !empty($wpdb->last_error)) return self::failed();
            return new WP_Error('insufficient_credits', 'Không đủ Linh Thạch.', ['status' => 402]);
        }
        return true;
    }

    private static function lock_balance($user_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT credits FROM " . HDK_DB::table('hdk_user_credits') . " WHERE user_id = %d FOR UPDATE",
            $user_id
        ));
    }

    private static function balance($user_id) {
        global $wpdb;
        return (int)$wpdb->get_var($wpdb->prepare(
            "SELECT credits FROM " . HDK_DB::table('hdk_user_credits') . " WHERE user_id = %d",
            $user_id
        ));
    }

    private static function insert_transaction($user_id, $credits, $balance, $source_type, $source_id, $note) {
        global $wpdb;
        return $wpdb->insert(HDK_DB::table('hdk_credit_transactions'), [
            'user_id' => $user_id,
            'type' => 'spend',
            'credits' => $credits,
            'balance_after' => $balance,
            'source_type' => $source_type,
            'source_id' => $source_id,
            'note' => $note,
            'status' => 'completed',
            'created_at' => current_time('mysql'),
        ]) !== false;
    }

    private static function rollback() {
        global $wpdb;
        $wpdb->query('ROLLBACK');
        return self::failed();
    }

    private static function failed() {
        return new WP_Error('purchase_failed', 'Không thể hoàn tất giao dịch. Vui lòng thử lại.', ['status' => 500]);
    }
}
