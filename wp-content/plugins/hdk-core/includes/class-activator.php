<?php
/**
 * HDK Activator - handles plugin activation/deactivation
 */

class HDK_Activator {
    public static function activate() {
        HDK_Schema::create_tables();
        HDK_Rewrite::add_rules();
        flush_rewrite_rules();

        // Default roles
        add_role('reader', __('Reader', 'hatdaukhaai'), ['read' => true]);
        add_role('contributor_custom', __('Contributor', 'hatdaukhaai'), [
            'read' => true,
            'edit_stories' => true,
            'publish_stories' => true,
            'delete_stories' => true,
        ]);
        add_role('moderator', __('Moderator', 'hatdaukhaai'), [
            'read' => true,
            'edit_stories' => true,
            'publish_stories' => true,
            'delete_stories' => true,
            'moderate_comments' => true,
        ]);

        // Create account page
        $existing = get_page_by_path('tai-khoan');
        if (!$existing) {
            wp_insert_post([
                'post_type' => 'page',
                'post_title' => 'Tài khoản',
                'post_name' => 'tai-khoan',
                'post_status' => 'publish',
                'post_content' => '',
                'comment_status' => 'closed',
            ]);
        }
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }
}
