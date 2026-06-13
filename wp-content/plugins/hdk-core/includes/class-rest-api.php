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
                "SELECT id, title, slug, cover_url, status FROM " . HDK_DB::table('hdk_stories') . "
                 WHERE title LIKE %s ORDER BY total_views DESC LIMIT 10",
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

    public static function toggle_favorite($request) {
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
        $story_id = (int)$request->get_param('story_id');
        $chapter_number = (int)$request->get_param('chapter_number');
        $content = sanitize_textarea_field($request->get_param('content') ?? '');
        $parent_id = (int)$request->get_param('parent_id');

        if (empty($content)) return new WP_Error('empty_comment', 'Comment cannot be empty', ['status' => 400]);

        $comment_data = [
            'comment_post_ID' => 0,
            'comment_content' => $content,
            'user_id' => get_current_user_id(),
            'comment_approved' => 1,
            'comment_meta' => [
                'hdk_story_id' => $story_id,
                'hdk_chapter_number' => $chapter_number,
            ],
        ];
        if ($parent_id) $comment_data['comment_parent'] = $parent_id;

        $comment_id = wp_insert_comment($comment_data);
        return rest_ensure_response(['comment_id' => $comment_id]);
    }

    public static function update_progress($request) {
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
        $story_id = (int)$request->get_param('story_id');
        $chapter_number = (int)$request->get_param('chapter_number');
        $user_id = get_current_user_id();
        global $wpdb;

        $story = HDK_DB::get_story($story_id);
        if (!$story) return new WP_Error('not_found', 'Story not found', ['status' => 404]);

        $price = (int)($story->chapter_price ?? 0);
        if ($price <= 0) return new WP_Error('free', 'This chapter is free', ['status' => 400]);

        if ($chapter_number <= (int)($story->free_chapters ?? 0)) {
            return new WP_Error('free', 'This chapter is in free range', ['status' => 400]);
        }

        // Check already purchased
        $purchased = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . HDK_DB::table('hdk_purchased_chapters') . " WHERE user_id = %d AND story_id = %d AND chapter_number = %d",
            $user_id, $story_id, $chapter_number
        ));
        if ($purchased) return rest_ensure_response(['success' => true, 'already_purchased' => true]);

        // Check if full story purchased
        $has_full = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . HDK_DB::table('hdk_purchased_chapters') . " WHERE user_id = %d AND story_id = %d AND is_full = 1",
            $user_id, $story_id
        ));
        if ($has_full) return rest_ensure_response(['success' => true, 'already_purchased' => true]);

        // Check credits
        $credits = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT credits FROM " . HDK_DB::table('hdk_user_credits') . " WHERE user_id = %d", $user_id
        ));
        if ($credits < $price) return new WP_Error('insufficient_credits', 'Không đủ hạt. Cần ' . $price . ' hạt, bạn có ' . $credits . ' hạt.', ['status' => 402]);

        // Deduct credits
        $wpdb->query($wpdb->prepare(
            "UPDATE " . HDK_DB::table('hdk_user_credits') . " SET credits = credits - %d, total_spent = total_spent + %d WHERE user_id = %d",
            $price, $price, $user_id
        ));

        // Record purchase
        $wpdb->insert(HDK_DB::table('hdk_purchased_chapters'), [
            'user_id' => $user_id,
            'story_id' => $story_id,
            'chapter_number' => $chapter_number,
            'is_full' => 0,
            'credits_spent' => $price,
            'created_at' => current_time('mysql'),
        ]);

        $remaining = $credits - $price;
        return rest_ensure_response(['success' => true, 'credits_spent' => $price, 'credits_remaining' => $remaining]);
    }

    public static function purchase_full_story($request) {
        $story_id = (int)$request->get_param('story_id');
        $user_id = get_current_user_id();
        global $wpdb;

        $story = HDK_DB::get_story($story_id);
        if (!$story) return new WP_Error('not_found', 'Story not found', ['status' => 404]);

        $price = (int)($story->full_price ?? 0);
        if ($price <= 0) return new WP_Error('free', 'Full purchase not available', ['status' => 400]);

        // Check already purchased full
        $has_full = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . HDK_DB::table('hdk_purchased_chapters') . " WHERE user_id = %d AND story_id = %d AND is_full = 1",
            $user_id, $story_id
        ));
        if ($has_full) return rest_ensure_response(['success' => true, 'already_purchased' => true]);

        // Check credits
        $credits = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT credits FROM " . HDK_DB::table('hdk_user_credits') . " WHERE user_id = %d", $user_id
        ));
        if ($credits < $price) return new WP_Error('insufficient_credits', 'Không đủ hạt. Cần ' . $price . ' hạt, bạn có ' . $credits . ' hạt.', ['status' => 402]);

        // Deduct credits
        $wpdb->query($wpdb->prepare(
            "UPDATE " . HDK_DB::table('hdk_user_credits') . " SET credits = credits - %d, total_spent = total_spent + %d WHERE user_id = %d",
            $price, $price, $user_id
        ));

        // Record full purchase (delete existing single chapter purchases for this story)
        $wpdb->delete(HDK_DB::table('hdk_purchased_chapters'), ['user_id' => $user_id, 'story_id' => $story_id, 'is_full' => 0]);
        $wpdb->insert(HDK_DB::table('hdk_purchased_chapters'), [
            'user_id' => $user_id,
            'story_id' => $story_id,
            'chapter_number' => 0,
            'is_full' => 1,
            'credits_spent' => $price,
            'created_at' => current_time('mysql'),
        ]);

        $remaining = $credits - $price;
        return rest_ensure_response(['success' => true, 'credits_spent' => $price, 'credits_remaining' => $remaining]);
    }
}
