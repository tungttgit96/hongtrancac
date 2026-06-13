<?php
/**
 * HDK Rewrite - URL rewrite rules for story slugs and taxonomies
 */

class HDK_Rewrite {
    public static function init() {
        self::add_rules();
        add_filter('query_vars', [__CLASS__, 'query_vars']);
        add_action('init', [__CLASS__, 'add_rewrite_tags']);
        add_filter('request', [__CLASS__, 'filter_request']);
    }

    public static function filter_request($vars) {
        if (!empty($vars['hdk_story'])) {
            $slug = $vars['hdk_story'];
            // Check if it's a WordPress page
            $page = get_page_by_path($slug, OBJECT, 'page');
            if ($page && $page->post_status === 'publish') {
                unset($vars['hdk_story']);
                $vars['page_id'] = $page->ID;
                $vars['pagename'] = $slug;
                return $vars;
            }
            // Check if it's a WordPress post (news)
            $post = get_page_by_path($slug, OBJECT, 'post');
            if ($post && $post->post_status === 'publish') {
                unset($vars['hdk_story']);
                $vars['name'] = $slug;
                $vars['post_type'] = 'post';
                return $vars;
            }
        }
        return $vars;
    }

    public static function add_rules() {
        // Static routes handled by WordPress pages
        // Story detail: /{storySlug}
        add_rewrite_rule(
            '^([^/]+)/?$',
            'index.php?hdk_story=$matches[1]',
            'top'
        );

        // Chapter reader: /{storySlug}?chuong={number} (handled via query var)
        // Taxonomy pages
        add_rewrite_rule(
            '^the-loai/([^/]+)/?$',
            'index.php?hdk_taxonomy=category&hdk_slug=$matches[1]',
            'top'
        );

        add_rewrite_rule(
            '^tac-gia/([^/]+)/?$',
            'index.php?hdk_taxonomy=author&hdk_slug=$matches[1]',
            'top'
        );

        add_rewrite_rule(
            '^nhan-vat/([^/]+)/?$',
            'index.php?hdk_taxonomy=character&hdk_slug=$matches[1]',
            'top'
        );
    }

    public static function add_rewrite_tags() {
        add_rewrite_tag('%hdk_story%', '([^&]+)');
        add_rewrite_tag('%hdk_taxonomy%', '([^&]+)');
        add_rewrite_tag('%hdk_slug%', '([^&]+)');
    }

    public static function query_vars($vars) {
        $vars[] = 'hdk_story';
        $vars[] = 'hdk_taxonomy';
        $vars[] = 'hdk_slug';
        $vars[] = 'chuong';
        return $vars;
    }
}
