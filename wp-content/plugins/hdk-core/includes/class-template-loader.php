<?php
/**
 * HDK Template Loader - routes custom URLs to template files
 */

class HDK_Template_Loader {
    public static function init() {
        $story_slug = get_query_var('hdk_story');
        $taxonomy = get_query_var('hdk_taxonomy');
        $hdk_slug = get_query_var('hdk_slug');

        if ($story_slug) {
            // Check if this matches a WordPress page first
            $page = get_page_by_path($story_slug, OBJECT, 'page');
            if ($page && $page->post_status === 'publish') {
                // Redirect WordPress to the actual page
                global $wp_query, $wp;
                $wp_query = new WP_Query(['page_id' => $page->ID]);
                $wp_query->is_page = true;
                $wp_query->is_singular = true;
                $wp_query->is_home = false;
                return;
            }
            
            // Check if it's a post (news)
            $post = get_page_by_path($story_slug, OBJECT, 'post');
            if ($post && $post->post_status === 'publish') {
                global $wp_query;
                $wp_query = new WP_Query(['p' => $post->ID, 'post_type' => 'post']);
                $wp_query->is_single = true;
                $wp_query->is_singular = true;
                $wp_query->is_home = false;
                return;
            }

            self::load_story_template($story_slug);
        } elseif ($taxonomy && $hdk_slug) {
            self::load_taxonomy_template($taxonomy, $hdk_slug);
        }
    }

    private static function load_story_template($slug) {
        $story = HDK_DB::get_story($slug);
        if (!$story) return;

        // Log view
        $chapter_number = isset($_GET['chuong']) ? (int)$_GET['chuong'] : 0;
        if (defined('HDK_LOG_VIEWS') && HDK_LOG_VIEWS) {
            HDK_DB::log_view($story->id, $chapter_number);
        }

        if ($chapter_number > 0) {
            global $hdk_chapter, $hdk_story;
            $hdk_story = $story;
            $hdk_chapter = HDK_DB::get_chapter($story->id, $chapter_number);
            if (!$hdk_chapter) {
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
                return;
            }

            // Auto-publish if scheduled and past due
            if ($hdk_chapter->status === 'scheduled' && strtotime($hdk_chapter->scheduled_at) <= time()) {
                global $wpdb;
                $wpdb->update(HDK_DB::table('hdk_chapters'), 
                    ['status' => 'published', 'scheduled_at' => null, 'updated_at' => current_time('mysql')],
                    ['id' => $hdk_chapter->id]
                );
                $hdk_chapter->status = 'published';
            }

            // Don't show draft or future scheduled chapters
            if ($hdk_chapter->status === 'draft' || $hdk_chapter->status === 'scheduled') {
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
                return;
            }

            // Check chapter access (paywall)
            $access = self::check_chapter_access($story, $chapter_number);
            global $hdk_access;
            $hdk_access = $access;

            if (!$access['can_read']) {
                get_template_part('templates/chapter-paywall');
            } else {
                get_template_part('templates/chapter-reader');
            }
        } else {
            global $hdk_story;
            $hdk_story = $story;
            get_template_part('templates/story-detail');
        }
        exit;
    }

    private static function load_taxonomy_template($type, $slug) {
        global $wpdb, $hdk_taxonomy_type, $hdk_taxonomy_slug;

        $hdk_taxonomy_type = $type;
        $hdk_taxonomy_slug = $slug;

        switch ($type) {
            case 'category':
                $term = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM " . HDK_DB::table('hdk_categories') . " WHERE slug = %s", $slug
                ));
                if ($term) {
                    global $hdk_category;
                    $hdk_category = $term;
                }
                break;
            case 'author':
                $term = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM " . HDK_DB::table('hdk_authors') . " WHERE slug = %s", $slug
                ));
                if ($term) {
                    global $hdk_author;
                    $hdk_author = $term;
                }
                break;
            case 'character':
                $term = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM " . HDK_DB::table('hdk_characters') . " WHERE slug = %s", $slug
                ));
                if ($term) {
                    global $hdk_character;
                    $hdk_character = $term;
                }
                break;
        }

        if (isset($term) && $term) {
            get_template_part('templates/taxonomy');
        }
        exit;
    }

    public static function check_chapter_access($story, $chapter_number) {
        $free_chapters = (int)($story->free_chapters ?? 0);
        $full_price = (int)($story->full_price ?? 0);
        $user_id = get_current_user_id();
        global $wpdb;

        // Get chapter-level price if set, otherwise use story default
        $chapter = $wpdb->get_row($wpdb->prepare(
            "SELECT price FROM " . HDK_DB::table('hdk_chapters') . " WHERE story_id = %d AND chapter_number = %d",
            $story->id, $chapter_number
        ));
        $chapter_price = $chapter && $chapter->price > 0 ? (int)$chapter->price : (int)($story->chapter_price ?? 0);

        // Free story or chapter is within free range
        if ($chapter_price <= 0 && $full_price <= 0) {
            return ['can_read' => true, 'reason' => 'free'];
        }
        if ($chapter_number <= $free_chapters) {
            return ['can_read' => true, 'reason' => 'free_chapter'];
        }
        if (!$user_id) {
            return ['can_read' => false, 'reason' => 'login_required', 'story' => $story, 'chapter_number' => $chapter_number];
        }

        // Check if user purchased full story
        $has_full = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . HDK_DB::table('hdk_purchased_chapters') . " WHERE user_id = %d AND story_id = %d AND is_full = 1",
            $user_id, $story->id
        ));
        if ($has_full) {
            return ['can_read' => true, 'reason' => 'purchased_full'];
        }

        // Check if user purchased this specific chapter
        $has_chapter = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . HDK_DB::table('hdk_purchased_chapters') . " WHERE user_id = %d AND story_id = %d AND chapter_number = %d",
            $user_id, $story->id, $chapter_number
        ));
        if ($has_chapter) {
            return ['can_read' => true, 'reason' => 'purchased_chapter'];
        }

        return [
            'can_read' => false,
            'reason' => 'paywall',
            'story' => $story,
            'chapter_number' => $chapter_number,
            'chapter_price' => $chapter_price,
            'full_price' => $full_price,
            'free_chapters' => $free_chapters,
        ];
    }

    public static function get_chapter_price($story, $chapter_number) {
        global $wpdb;
        $chapter = $wpdb->get_row($wpdb->prepare(
            "SELECT price FROM " . HDK_DB::table('hdk_chapters') . " WHERE story_id = %d AND chapter_number = %d",
            $story->id, $chapter_number
        ));
        if ($chapter && $chapter->price > 0) return (int)$chapter->price;
        return (int)($story->chapter_price ?? 0);
    }

    public static function has_purchased_chapter($story_id, $chapter_number) {
        $user_id = get_current_user_id();
        if (!$user_id) return false;
        global $wpdb;
        $has = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . HDK_DB::table('hdk_purchased_chapters') . " WHERE user_id = %d AND story_id = %d AND (chapter_number = %d OR is_full = 1)",
            $user_id, $story_id, $chapter_number
        ));
        return (bool)$has;
    }
}
