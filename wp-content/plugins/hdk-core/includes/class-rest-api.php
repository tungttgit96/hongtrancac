<?php
/**
 * HDK REST API - JSON endpoints for search, favorite, rating, reading progress
 */

class HDK_REST_API {
    public static function init() {
        register_rest_route('hdk/v1', '/search', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'search'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('hdk/v1', '/stories/(?P<id>\d+)/favorite', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'toggle_favorite'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('hdk/v1', '/stories/(?P<id>\d+)/rating', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'rate_story'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('hdk/v1', '/comments', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'add_comment'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('hdk/v1', '/reading-progress', [
            'methods' => 'PATCH',
            'callback' => [__CLASS__, 'update_progress'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('hdk/v1', '/purchase/chapter', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'purchase_chapter'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('hdk/v1', '/purchase/full', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'purchase_full_story'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('hdk/v1', '/credits', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_credits'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('hdk/v1', '/me/favorites', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_favorites'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('hdk/v1', '/me/purchases', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_purchases'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('hdk/v1', '/daily-claim', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'daily_claim'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('hdk/v1', '/me/transactions', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_transactions'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('hdk/v1', '/packages', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_packages'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('hdk/v1', '/chapters/(?P<story_id>\d+)', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_chapters'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('hdk/v1', '/reader-prefs', [
            'methods' => 'PATCH',
            'callback' => [__CLASS__, 'save_reader_prefs'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('hdk/v1', '/reader-prefs', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_reader_prefs'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('hdk/v1', '/notifications', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_notifications'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('hdk/v1', '/notifications/read', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'mark_read'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('hdk/v1', '/notifications/unread-count', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'unread_count'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('hdk/v1', '/reports', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'create_report'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('hdk/v1', '/listening-history', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'save_listening_history'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);
    }

    private static function verify_nonce() {
        $nonce = $_REQUEST['_wpnonce'] ?? $_SERVER['HTTP_X_WP_NONCE'] ?? '';
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_forbidden', 'Nonce verification failed', ['status' => 403]);
        }
        return true;
    }

    public static function search($request) {
        $q = sanitize_text_field($request->get_param('q') ?? '');
        $type = sanitize_text_field($request->get_param('type') ?? 'all');

        $results = ['stories' => [], 'authors' => [], 'categories' => []];

        if (strlen($q) < 2) return rest_ensure_response($results);

        global $wpdb;

        if ($type === 'all' || $type === 'stories') {
            $search = '%' . $wpdb->esc_like($q) . '%';
            $results['stories'] = $wpdb->get_results($wpdb->prepare(
                "SELECT s.id, s.title, s.slug, s.cover_url, s.status, s.total_chapters, s.average_rating, a.name AS author_name
                 FROM " . HDK_DB::table('hdk_stories') . " s
                 LEFT JOIN " . HDK_DB::table('hdk_authors') . " a ON a.id = s.author_id
                 WHERE s.title LIKE %s OR s.summary LIKE %s OR a.name LIKE %s
                 ORDER BY s.total_views DESC LIMIT 10",
                $search,
                $search,
                $search
            ));
        }

        if ($type === 'all' || $type === 'authors') {
            $search = '%' . $wpdb->esc_like($q) . '%';
            $results['authors'] = $wpdb->get_results($wpdb->prepare(
                "SELECT id, name, slug, avatar_url FROM " . HDK_DB::table('hdk_authors') . "
                 WHERE name LIKE %s LIMIT 5",
                $search
            ));
        }

        if ($type === 'all' || $type === 'categories') {
            $search = '%' . $wpdb->esc_like($q) . '%';
            $results['categories'] = $wpdb->get_results($wpdb->prepare(
                "SELECT id, name, slug FROM " . HDK_DB::table('hdk_categories') . "
                 WHERE name LIKE %s LIMIT 5",
                $search
            ));
        }

        return rest_ensure_response($results);
    }

    public static function save_listening_history($request) {
        $nonce_check = self::verify_nonce();
        if (is_wp_error($nonce_check)) return $nonce_check;

        $body = json_decode($request->get_body(), true);
        if (!is_array($body)) {
            $body = [];
        }

        $item = [
            'title' => sanitize_text_field($body['title'] ?? 'Truyện audio'),
            'url' => esc_url_raw($body['url'] ?? home_url('/')),
            'position' => sanitize_text_field($body['position'] ?? 'Đang nghe'),
            'time' => current_time('d/m/Y H:i'),
        ];

        $user_id = get_current_user_id();
        $history = get_user_meta($user_id, 'hdk_listening_history', true);
        if (!is_array($history)) {
            $history = [];
        }

        array_unshift($history, $item);
        $history = array_slice($history, 0, 50);
        update_user_meta($user_id, 'hdk_listening_history', $history);

        return rest_ensure_response(['success' => true, 'history' => $history]);
    }

    public static function toggle_favorite($request) {
        $nonce_check = self::verify_nonce();
        if (is_wp_error($nonce_check)) return $nonce_check;

        $story_id = (int)$request->get_param('id');
        $user_id = get_current_user_id();
        global $wpdb;
        $table = HDK_DB::table('hdk_favorites');

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE story_id = %d AND user_id = %d", $story_id, $user_id
        ));

        if ($existing) {
            $wpdb->delete($table, ['id' => $existing]);
            $favorited = false;
        } else {
            $wpdb->insert($table, ['story_id' => $story_id, 'user_id' => $user_id, 'created_at' => current_time('mysql')]);
            $favorited = true;
        }

        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE story_id = %d", $story_id));
        $wpdb->update(HDK_DB::table('hdk_stories'), ['total_favorites' => $count], ['id' => $story_id]);

        return rest_ensure_response(['favorited' => $favorited, 'total_favorites' => (int)$count]);
    }

    public static function rate_story($request) {
        $nonce_check = self::verify_nonce();
        if (is_wp_error($nonce_check)) return $nonce_check;

        $story_id = (int)$request->get_param('id');
        $rating = (int)$request->get_param('rating');
        if ($rating < 1 || $rating > 5) return new WP_Error('invalid_rating', 'Rating must be 1-5', ['status' => 400]);

        $user_id = get_current_user_id();
        global $wpdb;
        $table = HDK_DB::table('hdk_ratings');

        $wpdb->replace($table, [
            'story_id' => $story_id,
            'user_id' => $user_id,
            'rating' => $rating,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);

        $avg = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(rating) FROM $table WHERE story_id = %d", $story_id
        ));
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE story_id = %d", $story_id
        ));
        $wpdb->update(HDK_DB::table('hdk_stories'), [
            'average_rating' => round($avg, 2),
            'total_ratings' => $count,
        ], ['id' => $story_id]);

        return rest_ensure_response(['average_rating' => round($avg, 1), 'total_ratings' => (int)$count]);
    }

    public static function add_comment($request) {
        $nonce_check = self::verify_nonce();
        if (is_wp_error($nonce_check)) return $nonce_check;

        $story_id = (int)$request->get_param('story_id');
        $chapter_number = (int)$request->get_param('chapter_number');
        $content = sanitize_textarea_field($request->get_param('content') ?? '');
        $parent_id = (int)$request->get_param('parent_id');
        $user_id = get_current_user_id();

        $policy = HDK_Comment_Policy::validate($user_id, $story_id, $chapter_number, $content, $parent_id);
        if (is_wp_error($policy)) return $policy;

        $user = wp_get_current_user();

        $comment_data = [
            'comment_post_ID' => 0,
            'comment_content' => $content,
            'comment_author' => $user->display_name,
            'comment_author_email' => $user->user_email,
            'comment_author_url' => $user->user_url,
            'comment_author_IP' => $_SERVER['REMOTE_ADDR'] ?? '',
            'comment_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'user_id' => $user_id,
            'comment_meta' => [
                'hdk_story_id' => $story_id,
                'hdk_chapter_number' => $chapter_number,
            ],
        ];
        if ($parent_id) $comment_data['comment_parent'] = $parent_id;

        $approval = HDK_Comment_Policy::approval($comment_data);
        if (is_wp_error($approval)) return $approval;
        $comment_data['comment_approved'] = $approval;

        $comment_id = wp_insert_comment($comment_data);
        if (!$comment_id) return new WP_Error('comment_failed', 'Không thể lưu bình luận.', ['status' => 500]);
        HDK_Comment_Policy::record_submission($user_id);

        $story = HDK_DB::get_story($story_id);
        $story_slug = $story->slug ?? '';

        // Notify parent comment author
        if ($parent_id > 0) {
            $parent = get_comment($parent_id);
            if ($parent && $parent->user_id && $parent->user_id != $user_id) {
                HDK_DB::create_notification(
                    $parent->user_id, 'comment_reply',
                    'Có người trả lời bình luận của bạn',
                    'Một người dùng đã trả lời bình luận của bạn.',
                    home_url('/' . $story_slug . '?chuong=' . $chapter_number)
                );
            }
        }

        return rest_ensure_response(['comment_id' => $comment_id]);
    }

    public static function update_progress($request) {
        $nonce_check = self::verify_nonce();
        if (is_wp_error($nonce_check)) return $nonce_check;

        $story_id = (int)$request->get_param('story_id');
        $chapter_number = (int)$request->get_param('chapter_number');
        $scroll_percent = (float)($request->get_param('scroll_percent') ?? 0);

        $user_id = get_current_user_id();
        global $wpdb;
        $table = HDK_DB::table('hdk_reading_progress');

        $wpdb->replace($table, [
            'story_id' => $story_id,
            'user_id' => $user_id,
            'chapter_number' => $chapter_number,
            'scroll_percent' => $scroll_percent,
            'updated_at' => current_time('mysql'),
        ]);

        // Log to reading history (deduplicated by user+story+chapter)
        HDK_DB::log_reading_history($user_id, $story_id, $chapter_number);

        return rest_ensure_response(['saved' => true]);
    }

    public static function get_credits($request) {
        $user_id = get_current_user_id();
        global $wpdb;
        $table = HDK_DB::table('hdk_user_credits');
        $credits = $wpdb->get_var($wpdb->prepare("SELECT credits FROM $table WHERE user_id = %d", $user_id));
        if ($credits === null) {
            $wpdb->insert($table, ['user_id' => $user_id, 'credits' => 0]);
            $credits = 0;
        }
        return rest_ensure_response(['credits' => (int)$credits]);
    }

    public static function purchase_chapter($request) {
        $nonce_check = self::verify_nonce();
        if (is_wp_error($nonce_check)) return $nonce_check;

        $story_id = (int)$request->get_param('story_id');
        $chapter_number = (int)$request->get_param('chapter_number');
        $user_id = get_current_user_id();
        global $wpdb;

        $story = HDK_DB::get_story($story_id);
        if (!$story) return new WP_Error('not_found', 'Story not found', ['status' => 404]);

        $price = HDK_DB::get_chapter_price($story, $chapter_number);
        if ($price <= 0) return new WP_Error('free', 'This chapter is free', ['status' => 400]);

        if ($chapter_number <= (int)($story->free_chapters ?? 0)) {
            return new WP_Error('free', 'This chapter is in free range', ['status' => 400]);
        }

        $result = HDK_Purchase_Service::purchase_chapter($user_id, $story, $chapter_number, $price);
        if (is_wp_error($result)) return $result;

        if (empty($result['already_purchased'])) {
            HDK_DB::create_notification(
                $user_id, 'purchase_success',
                'Mua chương thành công',
                'Bạn đã mua chương ' . $chapter_number . ' - ' . $story->title,
                home_url('/' . $story->slug . '?chuong=' . $chapter_number)
            );
        }

        return rest_ensure_response($result);
    }

    public static function purchase_full_story($request) {
        $nonce_check = self::verify_nonce();
        if (is_wp_error($nonce_check)) return $nonce_check;

        $story_id = (int)$request->get_param('story_id');
        $user_id = get_current_user_id();
        global $wpdb;

        $story = HDK_DB::get_story($story_id);
        if (!$story) return new WP_Error('not_found', 'Story not found', ['status' => 404]);

        $price = (int)($story->full_price ?? 0);
        if ($price <= 0) return new WP_Error('free', 'Full purchase not available', ['status' => 400]);

        $result = HDK_Purchase_Service::purchase_full($user_id, $story, $price);
        if (is_wp_error($result)) return $result;

        if (empty($result['already_purchased'])) {
            HDK_DB::create_notification(
                $user_id, 'purchase_success',
                'Mua full truyện thành công',
                'Bạn đã mua toàn bộ truyện ' . $story->title,
                home_url('/' . $story->slug)
            );
        }

        return rest_ensure_response($result);
    }

    public static function get_favorites($request) {
        $user_id = get_current_user_id();
        $page = (int)($request->get_param('page') ?? 1);
        $result = HDK_DB::get_favorites($user_id, max(1, $page));
        return rest_ensure_response($result);
    }

    public static function get_purchases($request) {
        $user_id = get_current_user_id();
        $page = (int)($request->get_param('page') ?? 1);
        $result = HDK_DB::get_purchased_stories($user_id, max(1, $page));
        return rest_ensure_response($result);
    }

    public static function daily_claim($request) {
        $nonce_check = self::verify_nonce();
        if (is_wp_error($nonce_check)) return $nonce_check;

        $user_id = get_current_user_id();
        $result = HDK_DB::claim_daily_credits($user_id);
        if (!$result['success']) {
            return new WP_Error('already_claimed', $result['message'], ['status' => 409]);
        }
        return rest_ensure_response($result);
    }

    public static function get_transactions($request) {
        $user_id = get_current_user_id();
        $page = (int)($request->get_param('page') ?? 1);
        $result = HDK_DB::get_credit_transactions($user_id, max(1, $page));
        return rest_ensure_response($result);
    }

    public static function get_packages($request) {
        $result = HDK_DB::get_credit_packages(true);
        return rest_ensure_response(['packages' => $result]);
    }

    public static function get_chapters($request) {
        $story_id = (int)$request->get_param('story_id');
        $chapters = HDK_DB::get_chapters_toc($story_id);
        $story = HDK_DB::get_story($story_id);
        $free_chapters = (int)($story->free_chapters ?? 0);
        $full_price = (int)($story->full_price ?? 0);
        $is_free_story = (int)($story->is_free ?? 0) === 1;
        $user_id = get_current_user_id();

        foreach ($chapters as $ch) {
            $chapter_number = (int)$ch->chapter_number;
            $price = $story ? HDK_DB::get_chapter_price($story, $chapter_number) : 0;
            $price_mode = $ch->price_mode ?: ((int)($ch->price ?? 0) > 0 ? 'custom' : 'inherit');
            $ch->chapter_number = $chapter_number;
            $ch->price = $price;
            $ch->price_mode = $price_mode;
            $ch->is_locked = !$is_free_story && $price_mode !== 'free' && $chapter_number > $free_chapters && ($price > 0 || $full_price > 0);
            $ch->is_purchased = false;
        }
        
        if ($user_id) {
            global $wpdb;
            $purchased = $wpdb->get_results($wpdb->prepare(
                "SELECT chapter_number, is_full FROM " . HDK_DB::table('hdk_purchased_chapters') . " WHERE user_id = %d AND story_id = %d",
                $user_id, $story_id
            ));
            $purchased_map = [];
            $has_full = false;
            foreach ($purchased as $p) {
                if ($p->is_full) $has_full = true;
                else $purchased_map[$p->chapter_number] = true;
            }
            foreach ($chapters as $ch) {
                $ch->is_purchased = $has_full || isset($purchased_map[$ch->chapter_number]);
            }
        }
        
        return rest_ensure_response(['chapters' => $chapters]);
    }

    public static function get_reader_prefs($request) {
        $prefs = HDK_DB::get_reader_prefs(get_current_user_id());
        return rest_ensure_response(['prefs' => $prefs]);
    }

    public static function save_reader_prefs($request) {
        $nonce_check = self::verify_nonce();
        if (is_wp_error($nonce_check)) return $nonce_check;

        $body = json_decode($request->get_body(), true) ?? [];
        $data = [];
        if (isset($body['font_size'])) $data['font_size'] = max(16, min(28, (int)$body['font_size']));
        if (isset($body['font_family'])) $data['font_family'] = sanitize_text_field($body['font_family']);
        if (isset($body['line_height'])) $data['line_height'] = (float)$body['line_height'];
        if (isset($body['theme'])) $data['theme'] = sanitize_text_field($body['theme']);
        if (isset($body['reading_width'])) $data['reading_width'] = sanitize_text_field($body['reading_width']);
        
        HDK_DB::save_reader_prefs(get_current_user_id(), $data);
        return rest_ensure_response(['saved' => true]);
    }

    public static function get_notifications($request) {
        $user_id = get_current_user_id();
        $page = (int)($request->get_param('page') ?? 1);
        $result = HDK_DB::get_notifications($user_id, max(1, $page));
        return rest_ensure_response($result);
    }

    public static function mark_read($request) {
        $nonce_check = self::verify_nonce();
        if (is_wp_error($nonce_check)) return $nonce_check;

        $user_id = get_current_user_id();
        $body = json_decode($request->get_body(), true) ?? [];
        $notification_id = (int)($body['id'] ?? 0);
        HDK_DB::mark_notifications_read($user_id, $notification_id);
        return rest_ensure_response(['success' => true]);
    }

    public static function unread_count($request) {
        $count = HDK_DB::get_unread_notification_count(get_current_user_id());
        return rest_ensure_response(['count' => $count]);
    }

    public static function create_report($request) {
        $nonce_check = self::verify_nonce();
        if (is_wp_error($nonce_check)) return $nonce_check;

        $story_id = (int)$request->get_param('story_id');
        $chapter_number = (int)$request->get_param('chapter_number');
        $type = sanitize_text_field($request->get_param('report_type') ?? 'other');
        $note = sanitize_textarea_field($request->get_param('note') ?? '');

        $valid_types = ['typo','wrong_content','display_error','other'];
        if (!in_array($type, $valid_types)) $type = 'other';

        $id = HDK_DB::create_report(get_current_user_id(), $story_id, $chapter_number, $type, $note);
        return rest_ensure_response(['report_id' => $id, 'success' => true]);
    }
}
