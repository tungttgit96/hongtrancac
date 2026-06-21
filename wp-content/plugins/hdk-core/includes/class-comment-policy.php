<?php

class HDK_Comment_Policy {
    const MAX_LENGTH = 2000;
    const MAX_SUBMISSIONS = 5;
    const WINDOW_SECONDS = 300;

    public static function validate($user_id, $story_id, $chapter_number, $content, $parent_id = 0) {
        $content = trim((string)$content);
        if ($content === '') {
            return new WP_Error('empty_comment', 'Comment cannot be empty', ['status' => 400]);
        }

        $length = function_exists('mb_strlen') ? mb_strlen($content, 'UTF-8') : strlen($content);
        if ($length > self::MAX_LENGTH) {
            return new WP_Error('comment_too_long', 'Bình luận không được vượt quá 2.000 ký tự.', ['status' => 400]);
        }

        if ((int)get_transient(self::rate_key($user_id)) >= self::MAX_SUBMISSIONS) {
            return new WP_Error('comment_rate_limited', 'Bạn gửi bình luận quá nhanh. Vui lòng thử lại sau.', ['status' => 429]);
        }

        if ($parent_id > 0) {
            $parent = get_comment($parent_id);
            $parent_story = (int)get_comment_meta($parent_id, 'hdk_story_id', true);
            $parent_chapter = (int)get_comment_meta($parent_id, 'hdk_chapter_number', true);
            if (!$parent || $parent_story !== (int)$story_id || $parent_chapter !== (int)$chapter_number) {
                return new WP_Error('invalid_parent', 'Bình luận trả lời không hợp lệ.', ['status' => 400]);
            }
        }

        return true;
    }

    public static function record_submission($user_id) {
        $key = self::rate_key($user_id);
        set_transient($key, (int)get_transient($key) + 1, self::WINDOW_SECONDS);
    }

    public static function approval($comment_data) {
        return wp_allow_comment($comment_data, true);
    }

    private static function rate_key($user_id) {
        return 'hdk_comment_rl_' . (int)$user_id;
    }
}
