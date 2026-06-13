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
            WP_CLI::success('Import not implemented yet. Source: ' . $source);
        }
    }

    WP_CLI::add_command('hdk', 'HDK_CLI');
}
