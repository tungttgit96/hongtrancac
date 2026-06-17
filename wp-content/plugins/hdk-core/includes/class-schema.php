<?php
/**
 * HDK Schema - custom DB tables creation
 */

class HDK_Schema {
    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = [];

        // Authors
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hdk_authors (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            bio TEXT,
            avatar_url VARCHAR(500),
            story_count INT UNSIGNED DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            FULLTEXT idx_name (name)
        ) $charset;";

        // Categories
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hdk_categories (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            parent_id BIGINT UNSIGNED DEFAULT 0,
            story_count INT UNSIGNED DEFAULT 0,
            sort_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            INDEX idx_parent (parent_id)
        ) $charset;";

        // Characters
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hdk_characters (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            role VARCHAR(50) DEFAULT 'supporting',
            description TEXT,
            avatar_url VARCHAR(500),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_slug (slug)
        ) $charset;";

        // Stories
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hdk_stories (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(500) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            author_id BIGINT UNSIGNED,
            cover_url VARCHAR(500),
            audio_url VARCHAR(500),
            audio_title VARCHAR(500),
            audio_duration VARCHAR(50),
            summary TEXT,
            status ENUM('ongoing','completed','dropped') DEFAULT 'ongoing',
            is_free TINYINT(1) DEFAULT 0,
            is_featured_hidden TINYINT(1) DEFAULT 0,
            total_chapters INT UNSIGNED DEFAULT 0,
            free_chapters INT UNSIGNED DEFAULT 0,
            chapter_price INT UNSIGNED DEFAULT 0,
            full_price INT UNSIGNED DEFAULT 0,
            total_views BIGINT UNSIGNED DEFAULT 0,
            average_rating DECIMAL(3,2) DEFAULT 0.00,
            total_ratings INT UNSIGNED DEFAULT 0,
            total_favorites INT UNSIGNED DEFAULT 0,
            published_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            INDEX idx_author (author_id),
            INDEX idx_status (status),
            INDEX idx_views (total_views),
            INDEX idx_ratings (average_rating),
            INDEX idx_favorites (total_favorites),
            INDEX idx_published (published_at),
            FULLTEXT idx_title_summary (title, summary)
        ) $charset;";

        // Chapters
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hdk_chapters (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            story_id BIGINT UNSIGNED NOT NULL,
            chapter_number INT UNSIGNED NOT NULL,
            title VARCHAR(500),
            content LONGTEXT,
            word_count INT UNSIGNED DEFAULT 0,
            price INT UNSIGNED DEFAULT 0,
            price_mode VARCHAR(20) DEFAULT 'inherit',
            views BIGINT UNSIGNED DEFAULT 0,
            status ENUM('draft','published','scheduled') DEFAULT 'draft',
            scheduled_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_story (story_id),
            INDEX idx_number (story_id, chapter_number),
            INDEX idx_price (story_id, price),
            UNIQUE KEY uk_story_chapter (story_id, chapter_number)
        ) $charset;";

        // Story-Category pivot
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hdk_story_categories (
            story_id BIGINT UNSIGNED NOT NULL,
            category_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (story_id, category_id),
            INDEX idx_category (category_id)
        ) $charset;";

        // Story-Character pivot
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hdk_story_characters (
            story_id BIGINT UNSIGNED NOT NULL,
            character_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (story_id, character_id),
            INDEX idx_character (character_id)
        ) $charset;";

        // Ratings
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hdk_ratings (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            story_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            rating TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_user_story (user_id, story_id),
            INDEX idx_story (story_id)
        ) $charset;";

        // Favorites
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hdk_favorites (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            story_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_user_story (user_id, story_id),
            INDEX idx_story (story_id),
            INDEX idx_user (user_id)
        ) $charset;";

        // Reading Progress
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hdk_reading_progress (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            story_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            chapter_number INT UNSIGNED NOT NULL,
            scroll_percent DECIMAL(5,2) DEFAULT 0,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_user_story (user_id, story_id),
            INDEX idx_user (user_id)
        ) $charset;";

        // Daily Story Stats (for ranking)
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hdk_daily_story_stats (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            story_id BIGINT UNSIGNED NOT NULL,
            stat_date DATE NOT NULL,
            daily_views INT UNSIGNED DEFAULT 0,
            daily_favorites INT UNSIGNED DEFAULT 0,
            daily_ratings INT UNSIGNED DEFAULT 0,
            UNIQUE KEY uk_story_date (story_id, stat_date),
            INDEX idx_date (stat_date),
            INDEX idx_views (stat_date, daily_views),
            INDEX idx_favorites (stat_date, daily_favorites),
            INDEX idx_ratings (stat_date, daily_ratings)
        ) $charset;";

        // Notifications
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hdk_notifications (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(500) NOT NULL,
            message TEXT,
            link VARCHAR(500),
            is_read TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_read (user_id, is_read),
            INDEX idx_created (created_at)
        ) $charset;";

        // Newsletter
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hdk_newsletter_subscriptions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            status ENUM('active','unsubscribed') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset;";

        // User Credits (Linh Thạch)
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hdk_user_credits (
            user_id BIGINT UNSIGNED PRIMARY KEY,
            credits INT UNSIGNED DEFAULT 0,
            total_earned INT UNSIGNED DEFAULT 0,
            total_spent INT UNSIGNED DEFAULT 0,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset;";

        // Purchased Chapters
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hdk_purchased_chapters (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            story_id BIGINT UNSIGNED NOT NULL,
            chapter_number INT UNSIGNED DEFAULT 0,
            is_full TINYINT(1) DEFAULT 0,
            credits_spent INT UNSIGNED DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_user_story_chapter (user_id, story_id, chapter_number),
            INDEX idx_user (user_id),
            INDEX idx_user_story (user_id, story_id)
        ) $charset;";

        // Reading History (append-only log of completed chapter reads)
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hdk_reading_history (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            story_id BIGINT UNSIGNED NOT NULL,
            chapter_number INT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_history (user_id, created_at),
            INDEX idx_user_story (user_id, story_id)
        ) $charset;";

        // Credit Transactions (audit log for all credit movements)
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hdk_credit_transactions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            type ENUM('earn','spend','daily','admin_add','admin_deduct','refund') NOT NULL,
            credits INT NOT NULL,
            balance_after INT UNSIGNED NOT NULL,
            source_type VARCHAR(50),
            source_id BIGINT UNSIGNED NULL,
            note VARCHAR(500),
            status ENUM('completed','pending','failed') DEFAULT 'completed',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_created (user_id, created_at),
            INDEX idx_type (type)
        ) $charset;";

        // Credit Packages (configurable bundles for purchase)
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hdk_credit_packages (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            credits INT UNSIGNED NOT NULL,
            price_vnd INT UNSIGNED NOT NULL,
            bonus_credits INT UNSIGNED DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            sort_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset;";

        // User Reader Preferences
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hdk_user_reader_prefs (
            user_id BIGINT UNSIGNED PRIMARY KEY,
            font_size INT DEFAULT 20,
            font_family VARCHAR(100) DEFAULT 'Be Vietnam Pro',
            line_height DECIMAL(3,1) DEFAULT 2.0,
            theme ENUM('light','dark','sepia') DEFAULT 'light',
            reading_width ENUM('narrow','wide') DEFAULT 'wide',
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset;";

        // Chapter Error Reports
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hdk_chapter_reports (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            story_id BIGINT UNSIGNED NOT NULL,
            chapter_number INT UNSIGNED NOT NULL,
            report_type ENUM('typo','wrong_content','display_error','other') NOT NULL,
            note TEXT,
            status ENUM('pending','resolved') DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_story (story_id)
        ) $charset;";

        foreach ($sql as $query) {
            dbDelta($query);
        }

        // Migration: add pricing columns to existing stories table
        self::add_column_if_not_exists("{$wpdb->prefix}hdk_stories", 'is_featured_hidden', "TINYINT(1) DEFAULT 0 AFTER is_free");
        self::add_column_if_not_exists("{$wpdb->prefix}hdk_stories", 'audio_url', "VARCHAR(500) NULL AFTER cover_url");
        self::add_column_if_not_exists("{$wpdb->prefix}hdk_stories", 'audio_title', "VARCHAR(500) NULL AFTER audio_url");
        self::add_column_if_not_exists("{$wpdb->prefix}hdk_stories", 'audio_duration', "VARCHAR(50) NULL AFTER audio_title");
        self::add_column_if_not_exists("{$wpdb->prefix}hdk_stories", 'free_chapters', "INT UNSIGNED DEFAULT 0 AFTER total_chapters");
        self::add_column_if_not_exists("{$wpdb->prefix}hdk_stories", 'chapter_price', "INT UNSIGNED DEFAULT 0 AFTER free_chapters");
        self::add_column_if_not_exists("{$wpdb->prefix}hdk_stories", 'full_price', "INT UNSIGNED DEFAULT 0 AFTER chapter_price");
        self::add_column_if_not_exists("{$wpdb->prefix}hdk_chapters", 'price', "INT UNSIGNED DEFAULT 0 AFTER word_count");
        self::add_column_if_not_exists("{$wpdb->prefix}hdk_chapters", 'price_mode', "VARCHAR(20) DEFAULT 'inherit' AFTER price");
        self::add_column_if_not_exists("{$wpdb->prefix}hdk_chapters", 'scheduled_at', "DATETIME NULL AFTER price");
        self::add_column_if_not_exists("{$wpdb->prefix}hdk_user_credits", 'last_daily_at', "DATETIME NULL AFTER total_spent");

        // Add 'scheduled' to chapters status ENUM for existing installs
        $wpdb->query("ALTER TABLE {$wpdb->prefix}hdk_chapters MODIFY COLUMN status ENUM('draft','published','scheduled') DEFAULT 'draft'");
        $wpdb->query("UPDATE {$wpdb->prefix}hdk_chapters SET price_mode = 'custom' WHERE price > 0 AND (price_mode IS NULL OR price_mode = '' OR price_mode = 'inherit')");
    }

    public static function maybe_upgrade() {
        $target_version = '2026.06.17.1';
        $current_version = get_option('hdk_schema_version', '0');

        if (version_compare($current_version, $target_version, '>=')) {
            return;
        }

        self::create_tables();
        update_option('hdk_schema_version', $target_version, false);
    }

    private static function add_column_if_not_exists($table, $column, $definition) {
        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS 
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = %s",
            $table, $column
        ));
        if (!$exists) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN $column $definition");
        }
    }
}
