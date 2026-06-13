<?php
/**
 * WP-CLI commands for HDK Core
 * Usage: wp hdk import --source=file.csv
 *        wp hdk seed
 */

if (defined('WP_CLI') && WP_CLI) {

    class HDK_CLI {
        /**
         * Seed demo data: authors, categories, stories, chapters
         */
        public function seed() {
            HDK_DB::seed_demo_data();
            WP_CLI::success('Demo data seeded! 5 authors, 10 categories, 30 stories with chapters.');
        }

        /**
         * Import content from CSV/JSON file
         *
         * ## OPTIONS
         * --source=<file>
         * : Path to the import file (CSV or JSON)
         *
         * @when after_wp_load
         */
        public function import($args, $assoc_args) {
            $source = $assoc_args['source'] ?? '';
            if (!$source || !file_exists($source)) {
                WP_CLI::error('Source file not found: ' . $source);
            }

            $ext = pathinfo($source, PATHINFO_EXTENSION);
            $content = file_get_contents($source);

            if ($ext === 'json') {
                $rows = json_decode($content, true);
                if (!is_array($rows)) WP_CLI::error('Invalid JSON format');
            } else {
                $rows = \HDK_Admin::parse_csv_public($content);
            }

            if (empty($rows)) WP_CLI::error('No rows found in file');

            $results = \HDK_Admin::process_import_rows_public($rows, true);
            WP_CLI::success(sprintf(
                'Import done: %d created, %d skipped, %d errors.',
                $results['created'], $results['skipped'], $results['errors']
            ));
        }

        /**
         * Create account page if not exists
         *
         * @when after_wp_load
         */
        public function create_account_page() {
            self::ensure_account_page();
            WP_CLI::success('Account page (tai-khoan) created or already exists.');
        }

        public static function ensure_account_page() {
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
    }

    WP_CLI::add_command('hdk', 'HDK_CLI');
}
