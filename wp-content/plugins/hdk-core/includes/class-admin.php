<?php
/**
 * HDK Admin - WordPress admin pages for CMS (full CRUD)
 */

class HDK_Admin {
    public static function init() {
        add_menu_page('HDK Stories', 'Hồng Trần Các', 'edit_stories', 'hdk-stories', [__CLASS__, 'stories_page'], 'dashicons-book', 25);
        add_submenu_page('hdk-stories', 'Tất cả truyện', 'Tất cả truyện', 'edit_stories', 'hdk-stories', [__CLASS__, 'stories_page']);
        add_submenu_page('hdk-stories', 'Thêm truyện mới', 'Thêm truyện mới', 'edit_stories', 'hdk-story-form', [__CLASS__, 'story_form_page']);
        add_submenu_page(null, 'Sửa truyện', 'Sửa truyện', 'edit_stories', 'hdk-story-edit', [__CLASS__, 'story_form_page']);
        add_submenu_page('hdk-stories', 'Chapters', 'Chương', 'edit_stories', 'hdk-chapters', [__CLASS__, 'chapters_page']);
        add_submenu_page('hdk-stories', 'Thể loại', 'Thể loại', 'edit_stories', 'hdk-categories', [__CLASS__, 'categories_page']);
        add_submenu_page('hdk-stories', 'Tác giả', 'Tác giả', 'edit_stories', 'hdk-authors', [__CLASS__, 'authors_page']);
        add_submenu_page('hdk-stories', 'Nhân vật', 'Nhân vật', 'edit_stories', 'hdk-characters', [__CLASS__, 'characters_page']);
        add_submenu_page('hdk-stories', 'Import', 'Import', 'manage_options', 'hdk-import', [__CLASS__, 'import_page']);
        add_submenu_page('hdk-stories', 'Banner', 'Banner', 'edit_stories', 'hdk-banner', [__CLASS__, 'banner_page']);
        add_submenu_page('hdk-stories', 'Seed Demo', 'Seed Demo', 'manage_options', 'hdk-seed', [__CLASS__, 'seed_page']);
        add_submenu_page('hdk-stories', 'Quản lý hạt', 'Quản lý hạt', 'manage_options', 'hdk-credits', [__CLASS__, 'credits_page']);
        add_submenu_page('hdk-stories', 'Gói nạp', 'Gói nạp', 'manage_options', 'hdk-packages', [__CLASS__, 'packages_page']);
        add_submenu_page('hdk-stories', 'Lịch sử giao dịch', 'Lịch sử GD', 'manage_options', 'hdk-transactions', [__CLASS__, 'transactions_page']);
        add_submenu_page('hdk-stories', 'Bình luận', 'Bình luận', 'moderate_comments', 'hdk-comments', [__CLASS__, 'comments_page']);
        add_submenu_page('hdk-stories', 'Báo lỗi', 'Báo lỗi', 'edit_stories', 'hdk-reports', [__CLASS__, 'reports_page']);
        add_submenu_page('hdk-stories', 'Thống kê', 'Thống kê', 'manage_options', 'hdk-stats', [__CLASS__, 'stats_page']);

        add_action('admin_init', function() {
            $admin = get_role('administrator');
            if ($admin) {
                $admin->add_cap('edit_stories');
                $admin->add_cap('publish_stories');
                $admin->add_cap('delete_stories');
                $admin->add_cap('moderate_comments');
            }
            self::handle_form_submissions();
        });

        add_action('admin_enqueue_scripts', function($hook) {
            if (str_contains($hook, 'hdk-')) {
                wp_enqueue_media();
                wp_enqueue_style('hdk-admin', HDK_PLUGIN_URL . 'assets/css/admin.css', [], HDK_VERSION);
            }
        });
    }

    // ========== FORM HANDLERS ==========

    public static function handle_form_submissions() {
        if (!current_user_can('edit_stories')) return;

        // Save Story
        if (isset($_POST['hdk_save_story']) && wp_verify_nonce($_POST['_wpnonce'], 'hdk_save_story')) {
            self::save_story($_POST);
        }

        // Delete Story
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['story_id']) && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'hdk_delete_story')) {
            self::delete_story((int)$_GET['story_id']);
        }

        // Save Category
        if (isset($_POST['hdk_save_category']) && wp_verify_nonce($_POST['_wpnonce'], 'hdk_save_category')) {
            self::save_category($_POST);
        }

        // Save Author
        if (isset($_POST['hdk_save_author']) && wp_verify_nonce($_POST['_wpnonce'], 'hdk_save_author')) {
            self::save_author($_POST);
        }

        // Save Character
        if (isset($_POST['hdk_save_character']) && wp_verify_nonce($_POST['_wpnonce'], 'hdk_save_character')) {
            self::save_character($_POST);
        }

        // Save Chapter
        if (isset($_POST['hdk_save_chapter']) && wp_verify_nonce($_POST['_wpnonce'], 'hdk_save_chapter')) {
            self::save_chapter($_POST);
        }

        // Save Bulk Chapters
        if (isset($_POST['hdk_save_bulk_chapters']) && wp_verify_nonce($_POST['_wpnonce'], 'hdk_save_bulk_chapters')) {
            self::save_bulk_chapters($_POST);
        }

        // Save Banner Settings
        if (isset($_POST['hdk_save_banner']) && wp_verify_nonce($_POST['_wpnonce'], 'hdk_save_banner')) {
            self::save_banner($_POST);
        }

        // --- Credit Packages form ---
        if (isset($_POST['hdk_save_package'])) {
            if (!current_user_can('manage_options')) return;
            check_admin_referer('hdk_save_package');

            $id = (int)($_POST['package_id'] ?? 0);
            $data = [
                'name' => sanitize_text_field($_POST['package_name']),
                'credits' => (int)$_POST['package_credits'],
                'price_vnd' => (int)$_POST['package_price'],
                'bonus_credits' => (int)$_POST['package_bonus'],
                'is_active' => isset($_POST['package_active']) ? 1 : 0,
                'sort_order' => (int)$_POST['package_sort'],
            ];

            if ($id) {
                HDK_DB::update_credit_package($id, $data);
            } else {
                HDK_DB::create_credit_package($data);
            }
            wp_redirect(admin_url('admin.php?page=hdk-packages&message=saved'));
            exit;
        }

        if (isset($_GET['action']) && $_GET['action'] === 'delete_package' && !empty($_GET['id'])) {
            if (!current_user_can('manage_options')) return;
            check_admin_referer('hdk_delete_package_' . $_GET['id']);
            HDK_DB::delete_credit_package((int)$_GET['id']);
            wp_redirect(admin_url('admin.php?page=hdk-packages&message=deleted'));
            exit;
        }

        if (isset($_GET['action']) && $_GET['action'] === 'toggle_package' && !empty($_GET['id'])) {
            if (!current_user_can('manage_options')) return;
            check_admin_referer('hdk_toggle_package_' . $_GET['id']);
            $package = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . HDK_DB::table('hdk_credit_packages') . " WHERE id = %d", (int)$_GET['id']
            ));
            if ($package) {
                HDK_DB::update_credit_package($package->id, ['is_active' => $package->is_active ? 0 : 1]);
            }
            wp_redirect(admin_url('admin.php?page=hdk-packages&message=toggled'));
            exit;
        }

        // --- Credit adjustment form ---
        if (isset($_POST['hdk_adjust_credits'])) {
            if (!current_user_can('manage_options')) return;
            check_admin_referer('hdk_adjust_credits');

            $uid = (int)$_POST['user_id'];
            $amount = (int)$_POST['credit_amount'];
            $note = sanitize_text_field($_POST['adjust_note']);
            $type = $amount >= 0 ? 'admin_add' : 'admin_deduct';

            $credit_table = HDK_DB::table('hdk_user_credits');
            $current = (int)$wpdb->get_var($wpdb->prepare("SELECT credits FROM $credit_table WHERE user_id = %d", $uid));
            $stats = $wpdb->get_row($wpdb->prepare("SELECT total_earned, total_spent FROM $credit_table WHERE user_id = %d", $uid));
            if ($current === null && !$wpdb->last_error) {
                $wpdb->insert($credit_table, ['user_id' => $uid, 'credits' => 0]);
                $current = 0;
            }
            $new_balance = max(0, $current + $amount);

            $wpdb->update($credit_table, [
                'credits' => $new_balance,
                'total_earned' => $amount > 0 ? (int)($stats->total_earned ?? 0) + $amount : (int)($stats->total_earned ?? 0),
                'total_spent' => $amount < 0 ? (int)($stats->total_spent ?? 0) + abs($amount) : (int)($stats->total_spent ?? 0),
            ], ['user_id' => $uid]);

            HDK_DB::log_credit_transaction($uid, $type, $amount, 'admin', 0, $note);

            wp_redirect(admin_url('admin.php?page=hdk-credits&message=adjusted'));
            exit;
        }

        // --- Import CSV/JSON ---
        if (isset($_POST['hdk_import_confirm']) && !empty($_FILES['import_file']['tmp_name'])) {
            if (!current_user_can('manage_options')) return;
            check_admin_referer('hdk_import');

            $file = $_FILES['import_file'];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $content = file_get_contents($file['tmp_name']);
            
            if ($ext === 'json') {
                $rows = json_decode($content, true);
                if (!is_array($rows)) $rows = [];
            } else {
                $rows = self::parse_csv($content);
            }

            $results = self::process_import_rows($rows, isset($_POST['skip_errors']));
            set_transient('hdk_import_results', $results, 300);
            wp_redirect(admin_url('admin.php?page=hdk-import&imported=1'));
            exit;
        }

        // --- Comment moderation ---
        if (isset($_GET['hdk_comment_action']) && isset($_GET['comment_id'])) {
            if (!current_user_can('moderate_comments')) return;
            $cid = (int)$_GET['comment_id'];
            $action = $_GET['hdk_comment_action'];
            if ($action === 'approve') wp_set_comment_status($cid, 'approve');
            elseif ($action === 'unapprove') wp_set_comment_status($cid, 'hold');
            elseif ($action === 'trash') wp_trash_comment($cid);
            elseif ($action === 'spam') wp_spam_comment($cid);
            wp_redirect(admin_url('admin.php?page=hdk-comments'));
            exit;
        }

        // --- Report resolution ---
        if (isset($_GET['hdk_report_action']) && isset($_GET['report_id'])) {
            if (!current_user_can('edit_stories')) return;
            $rid = (int)$_GET['report_id'];
            if ($_GET['hdk_report_action'] === 'resolve') {
                HDK_DB::update_report_status($rid, 'resolved');
            }
            wp_redirect(admin_url('admin.php?page=hdk-reports'));
            exit;
        }
    }

    private static function save_story($data) {
        global $wpdb;
        $table = HDK_DB::table('hdk_stories');
        $now = current_time('mysql');

        $story_data = [
            'title' => sanitize_text_field($data['title']),
            'slug' => sanitize_title($data['slug'] ?: $data['title']),
            'author_id' => (int)($data['author_id'] ?? 0),
            'cover_url' => esc_url_raw($data['cover_url'] ?? ''),
            'summary' => wp_kses_post($data['summary'] ?? ''),
            'status' => in_array($data['status'] ?? '', ['ongoing','completed','dropped']) ? $data['status'] : 'ongoing',
            'is_free' => isset($data['is_free']) ? 1 : 0,
            'is_featured_hidden' => isset($data['is_featured_hidden']) ? 1 : 0,
            'free_chapters' => (int)($data['free_chapters'] ?? 0),
            'chapter_price' => (int)($data['chapter_price'] ?? 0),
            'full_price' => (int)($data['full_price'] ?? 0),
            'updated_at' => $now,
        ];

        $story_id = (int)($data['story_id'] ?? 0);

        if ($story_id > 0) {
            $wpdb->update($table, $story_data, ['id' => $story_id]);
            $msg = 'updated';
        } else {
            $story_data['created_at'] = $now;
            $story_data['published_at'] = $now;
            $wpdb->insert($table, $story_data);
            $story_id = $wpdb->insert_id;
            $msg = 'created';
        }

        // Update categories
        if ($story_id) {
            $cat_table = HDK_DB::table('hdk_story_categories');
            $wpdb->delete($cat_table, ['story_id' => $story_id]);
            $categories = array_map('intval', (array)($data['categories'] ?? []));
            foreach ($categories as $cid) {
                if ($cid > 0) $wpdb->insert($cat_table, ['story_id' => $story_id, 'category_id' => $cid]);
            }

            // Update category counts
            foreach (range(1, 200) as $cid) {
                $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . HDK_DB::table('hdk_story_categories') . " WHERE category_id = %d", $cid));
                $wpdb->update(HDK_DB::table('hdk_categories'), ['story_count' => $count], ['id' => $cid]);
            }
        }

        // Update total_chapters count
        $chap_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . HDK_DB::table('hdk_chapters') . " WHERE story_id = %d AND status = 'published'", $story_id));
        $wpdb->update($table, ['total_chapters' => $chap_count], ['id' => $story_id]);

        do_action('hdk_story_updated', $story_id);

        wp_redirect(admin_url('admin.php?page=hdk-stories&message=' . $msg));
        exit;
    }

    private static function delete_story($id) {
        global $wpdb;
        $wpdb->delete(HDK_DB::table('hdk_stories'), ['id' => $id]);
        $wpdb->delete(HDK_DB::table('hdk_chapters'), ['story_id' => $id]);
        $wpdb->delete(HDK_DB::table('hdk_story_categories'), ['story_id' => $id]);
        do_action('hdk_story_updated', $id);
        wp_redirect(admin_url('admin.php?page=hdk-stories&message=deleted'));
        exit;
    }

    private static function save_category($data) {
        global $wpdb;
        $table = HDK_DB::table('hdk_categories');
        $now = current_time('mysql');
        $cat_data = [
            'name' => sanitize_text_field($data['name']),
            'slug' => sanitize_title($data['slug'] ?: $data['name']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'sort_order' => (int)($data['sort_order'] ?? 0),
            'updated_at' => $now,
        ];
        $id = (int)($data['category_id'] ?? 0);
        if ($id > 0) {
            $wpdb->update($table, $cat_data, ['id' => $id]);
        } else {
            $cat_data['created_at'] = $now;
            $wpdb->insert($table, $cat_data);
        }
        wp_redirect(admin_url('admin.php?page=hdk-categories&message=saved'));
        exit;
    }

    private static function save_author($data) {
        global $wpdb;
        $table = HDK_DB::table('hdk_authors');
        $now = current_time('mysql');
        $author_data = [
            'name' => sanitize_text_field($data['name']),
            'slug' => sanitize_title($data['slug'] ?: $data['name']),
            'bio' => sanitize_textarea_field($data['bio'] ?? ''),
            'avatar_url' => esc_url_raw($data['avatar_url'] ?? ''),
            'updated_at' => $now,
        ];
        $id = (int)($data['author_id'] ?? 0);
        if ($id > 0) {
            $wpdb->update($table, $author_data, ['id' => $id]);
        } else {
            $author_data['created_at'] = $now;
            $wpdb->insert($table, $author_data);
        }
        wp_redirect(admin_url('admin.php?page=hdk-authors&message=saved'));
        exit;
    }

    private static function save_character($data) {
        global $wpdb;
        $table = HDK_DB::table('hdk_characters');
        $now = current_time('mysql');
        $char_data = [
            'name' => sanitize_text_field($data['name']),
            'slug' => sanitize_title($data['slug'] ?: $data['name']),
            'role' => sanitize_text_field($data['role'] ?? 'supporting'),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'avatar_url' => esc_url_raw($data['avatar_url'] ?? ''),
            'updated_at' => $now,
        ];
        $id = (int)($data['character_id'] ?? 0);
        if ($id > 0) {
            $wpdb->update($table, $char_data, ['id' => $id]);
        } else {
            $char_data['created_at'] = $now;
            $wpdb->insert($table, $char_data);
        }
        wp_redirect(admin_url('admin.php?page=hdk-characters&message=saved'));
        exit;
    }

    private static function save_chapter($data) {
        global $wpdb;
        $table = HDK_DB::table('hdk_chapters');
        $now = current_time('mysql');
        $story_id = (int)($data['story_id'] ?? 0);
        $story = HDK_DB::get_story($story_id);
        $price_mode = sanitize_key($data['chapter_price_mode'] ?? 'inherit');
        if (!in_array($price_mode, ['inherit', 'custom', 'free'], true)) {
            $price_mode = 'inherit';
        }
        $posted_price = max(0, (int)($data['chapter_price'] ?? 0));
        $stored_price = $price_mode === 'custom' ? $posted_price : 0;
        $chap_data = [
            'story_id' => $story_id,
            'chapter_number' => (int)($data['chapter_number'] ?? 0),
            'title' => sanitize_text_field($data['title'] ?? ''),
            'content' => wp_kses_post($data['content'] ?? ''),
            'word_count' => str_word_count(strip_tags($data['content'] ?? '')),
            'price' => $stored_price,
            'price_mode' => $price_mode,
            'status' => $data['status'] ?? 'draft',
            'updated_at' => $now,
        ];

        // Handle scheduling
        $scheduled = sanitize_text_field($data['scheduled_at'] ?? '');
        if ($scheduled && $chap_data['status'] === 'published' && strtotime($scheduled) > time()) {
            $chap_data['status'] = 'scheduled';
            $chap_data['scheduled_at'] = date('Y-m-d H:i:s', strtotime($scheduled));
        } else {
            $chap_data['scheduled_at'] = null;
        }
        $chapter_id = (int)($data['chapter_id'] ?? 0);
        if ($chapter_id > 0) {
            $wpdb->update($table, $chap_data, ['id' => $chapter_id]);
        } else {
            $chap_data['created_at'] = $now;
            $wpdb->insert($table, $chap_data);
        }

        // Update chapter count
        $chap_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE story_id = %d AND status = 'published'", $story_id
        ));
        $wpdb->update(HDK_DB::table('hdk_stories'), ['total_chapters' => $chap_count, 'updated_at' => $now], ['id' => $story_id]);
        do_action('hdk_chapter_updated', $story_id);

        if (($chap_data['status'] ?? '') === 'published') {
            $story = HDK_DB::get_story($story_id);
            HDK_DB::notify_favoriting_users(
                $story_id,
                (int)$chap_data['chapter_number'],
                $chap_data['title'] ?? 'Chương ' . $chap_data['chapter_number'],
                $story->title ?? '',
                $story->slug ?? ''
            );
        }

        wp_redirect(admin_url('admin.php?page=hdk-chapters&story_id=' . $story_id . '&message=saved'));
        exit;
    }

    private static function save_bulk_chapters($data) {
        global $wpdb;
        $table = HDK_DB::table('hdk_chapters');
        $now = current_time('mysql');
        $story_id = (int)($data['story_id'] ?? 0);
        $story = HDK_DB::get_story($story_id);
        $status = $data['bulk_status'] ?? 'published';
        $default_price_mode = sanitize_key($data['bulk_chapter_price_mode'] ?? 'inherit');
        if (!in_array($default_price_mode, ['inherit', 'custom', 'free'], true)) {
            $default_price_mode = 'inherit';
        }
        $default_price = $default_price_mode === 'custom' ? max(0, (int)($data['bulk_chapter_price'] ?? 0)) : 0;
        $scheduled_at = sanitize_text_field($data['bulk_scheduled_at'] ?? '');
        $is_scheduled = $scheduled_at && strtotime($scheduled_at) > time();

        // Get content from textarea or uploaded file
        $raw = trim($data['bulk_content'] ?? '');

        if (empty($raw) && !empty($_FILES['bulk_file']['tmp_name'])) {
            $file = $_FILES['bulk_file'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $raw = trim(file_get_contents($file['tmp_name']));
            }
        }

        // Split by chapter markers: ChươngX: or Chương X: (case insensitive)
        preg_match_all('/Chương\s*(\d+)\s*[:：]\s*(.+?)(?=Chương\s*\d+\s*[:：]|\z)/uis', $raw, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            wp_redirect(admin_url('admin.php?page=hdk-chapters&story_id=' . $story_id . '&message=no_chapters'));
            exit;
        }

        $inserted = 0;
        foreach ($matches as $match) {
            $chapter_number = (int)$match[1];
            $after_marker = trim($match[2]);

            // First line = title, rest = content
            $lines = preg_split('/\R/', $after_marker, 2);
            $title_line = trim($lines[0]);
            $content_raw = isset($lines[1]) ? trim($lines[1]) : '';

            $final_title = 'Chương ' . $chapter_number . ': ' . $title_line;
            $final_content = $content_raw;

            // Parse inline price: "Tên chương (5 hạt)" -> price = 5
            $chap_price_mode = $default_price_mode;
            $chap_price = $default_price;
            if (preg_match('/\((\d+)\s*hạt\s*\)/ui', $title_line, $pm)) {
                $chap_price_mode = 'custom';
                $chap_price = (int)$pm[1];
                $final_title = 'Chương ' . $chapter_number . ': ' . trim(preg_replace('/\s*\(\d+\s*hạt\s*\)/ui', '', $title_line));
            }

            // Check if chapter number already exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE story_id = %d AND chapter_number = %d", $story_id, $chapter_number
            ));

            $chap_data = [
                'story_id' => $story_id,
                'chapter_number' => $chapter_number,
                'title' => sanitize_text_field($final_title),
                'content' => wp_kses_post('<p>' . nl2br(esc_html($final_content)) . '</p>'),
                'word_count' => str_word_count($final_content),
                'price' => $chap_price,
                'price_mode' => $chap_price_mode,
                'status' => $is_scheduled ? 'scheduled' : $status,
                'scheduled_at' => $is_scheduled ? date('Y-m-d H:i:s', strtotime($scheduled_at)) : null,
                'updated_at' => $now,
            ];

            if ($exists) {
                $wpdb->update($table, $chap_data, ['id' => $exists]);
            } else {
                $chap_data['created_at'] = $now;
                $wpdb->insert($table, $chap_data);
            }
            $inserted++;
        }

        // Update chapter count
        $chap_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE story_id = %d AND status = 'published'", $story_id
        ));
        $wpdb->update(HDK_DB::table('hdk_stories'), ['total_chapters' => $chap_count, 'updated_at' => $now], ['id' => $story_id]);
        do_action('hdk_chapter_updated', $story_id);

        wp_redirect(admin_url('admin.php?page=hdk-chapters&story_id=' . $story_id . '&message=bulk_saved&count=' . $inserted));
        exit;
    }

    // ========== STORIES PAGE ==========

    public static function stories_page() {
        global $wpdb;
        $table = HDK_DB::table('hdk_stories');
        $page = isset($_GET['paged']) ? max(1, (int)$_GET['paged']) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $stories = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table ORDER BY updated_at DESC LIMIT %d OFFSET %d", $per_page, $offset));

        self::admin_notice();
        ?>
        <div class="wrap">
            <h1>Quản lý truyện <a href="?page=hdk-story-form" class="page-title-action">Thêm mới</a></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th><th>Ảnh</th><th>Tiêu đề</th><th>Tác giả</th><th>Thể loại</th><th>Trạng thái</th><th>Giá</th><th>Chương</th><th>Lượt xem</th><th>Đánh giá</th><th>Ngày cập nhật</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stories as $s): 
                        $author = HDK_DB::get_author_name($s->author_id);
                        $cats = HDK_DB::get_story_categories($s->id);
                        $cat_names = implode(', ', array_map(function($c){return $c->name;}, $cats));
                    ?>
                    <tr>
                        <td><?php echo $s->id; ?></td>
                        <td><?php if($s->cover_url): ?><img src="<?php echo esc_url($s->cover_url); ?>" width="40" height="56" style="object-fit:cover;border-radius:4px;"><?php endif; ?></td>
                        <td><strong><?php echo esc_html($s->title); ?></strong>
                            <div class="row-actions">
                                <span><a href="?page=hdk-story-edit&story_id=<?php echo $s->id; ?>">Sửa</a> | </span>
                                <span><a href="?page=hdk-chapters&story_id=<?php echo $s->id; ?>">Chương</a> | </span>
                                <span><a href="<?php echo wp_nonce_url("?page=hdk-stories&action=delete&story_id=$s->id", 'hdk_delete_story'); ?>" onclick="return confirm('Xóa truyện này?')" style="color:#b32d2e;">Xóa</a></span>
                            </div>
                        </td>
                        <td><?php echo esc_html($author); ?></td>
                        <td style="font-size:12px;"><?php echo esc_html($cat_names); ?></td>
                        <td><?php echo $s->status === 'completed' ? '✅ HT' : ($s->status === 'ongoing' ? '🔄 Đang ra' : '⛔ Ngừng'); ?></td>
                        <td style="font-size:12px;">
                            <?php 
                            $cp = (int)($s->chapter_price ?? 0);
                            $fc = (int)($s->free_chapters ?? 0);
                            $fp = (int)($s->full_price ?? 0);
                            if ((int)($s->is_free ?? 0)) {
                                echo 'Free';
                            } elseif ($cp > 0 || $fp > 0) {
                                if ($fc > 0) echo "F:$fc "; 
                                if ($cp > 0) echo "💎$cp"; 
                                if ($fp > 0) echo " Full:$fp";
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td><?php echo $s->total_chapters; ?></td>
                        <td><?php echo number_format($s->total_views); ?></td>
                        <td><?php echo $s->average_rating; ?> ⭐</td>
                        <td style="font-size:11px;"><?php echo date('d/m/Y H:i', strtotime($s->updated_at)); ?></td>
                        <td><a href="<?php echo home_url('/' . $s->slug); ?>" target="_blank">Xem</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
            $total_pages = ceil($total / $per_page);
            if ($total_pages > 1) {
                echo '<div class="tablenav"><div class="tablenav-pages">';
                for ($i = 1; $i <= $total_pages; $i++) {
                    $class = $i === $page ? 'current button' : 'button';
                    echo "<a href=\"?page=hdk-stories&paged=$i\" class=\"$class\">$i</a> ";
                }
                echo '</div></div>';
            }
            ?>
        </div>
        <?php
    }

    // ========== STORY FORM PAGE ==========

    public static function story_form_page() {
        global $wpdb;
        $is_edit = isset($_GET['story_id']);
        $story = null;

        if ($is_edit) {
            $story = HDK_DB::get_story((int)$_GET['story_id']);
            if (!$story) { echo '<div class="wrap"><p>Truyện không tồn tại.</p></div>'; return; }
        }

        $authors = $wpdb->get_results("SELECT * FROM " . HDK_DB::table('hdk_authors') . " ORDER BY name");
        $categories = $wpdb->get_results("SELECT * FROM " . HDK_DB::table('hdk_categories') . " ORDER BY sort_order");
        $story_cats = $is_edit ? array_map(function($c){return $c->id;}, HDK_DB::get_story_categories($story->id)) : [];
        ?>
        <div class="wrap">
            <h1><?php echo $is_edit ? 'Sửa truyện' : 'Thêm truyện mới'; ?></h1>
            <form method="post" style="max-width:800px;">
                <?php wp_nonce_field('hdk_save_story'); ?>
                <input type="hidden" name="story_id" value="<?php echo $story->id ?? ''; ?>">

                <table class="form-table">
                    <tr>
                        <th><label for="title">Tiêu đề <span style="color:red">*</span></label></th>
                        <td><input type="text" name="title" id="title" required class="regular-text" value="<?php echo esc_attr($story->title ?? ''); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="slug">Slug</label></th>
                        <td><input type="text" name="slug" id="slug" class="regular-text" value="<?php echo esc_attr($story->slug ?? ''); ?>" placeholder="Để trống để tự tạo từ tiêu đề"></td>
                    </tr>
                    <tr>
                        <th><label for="author_id">Tác giả</label></th>
                        <td>
                            <select name="author_id" id="author_id">
                                <option value="0">-- Chọn tác giả --</option>
                                <?php foreach ($authors as $a): ?>
                                    <option value="<?php echo $a->id; ?>" <?php selected(($story->author_id ?? 0), $a->id); ?>><?php echo esc_html($a->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <a href="?page=hdk-authors" target="_blank" style="margin-left:8px;">+ Thêm tác giả</a>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Thể loại</label></th>
                        <td>
                            <div style="max-height:200px;overflow-y:auto;border:1px solid #ddd;padding:8px;border-radius:4px;">
                                <?php foreach ($categories as $c): ?>
                                    <label style="display:block;padding:2px 0;">
                                        <input type="checkbox" name="categories[]" value="<?php echo $c->id; ?>" <?php checked(in_array($c->id, $story_cats)); ?>>
                                        <?php echo esc_html($c->name); ?> (<?php echo $c->story_count; ?>)
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="cover_url">Ảnh bìa</label></th>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <input type="text" name="cover_url" id="cover_url" class="regular-text" value="<?php echo esc_attr($story->cover_url ?? ''); ?>" placeholder="URL ảnh bìa">
                                <button type="button" class="button hdk-upload-btn" data-target="cover_url">Chọn ảnh</button>
                            </div>
                            <?php if (!empty($story->cover_url)): ?>
                                <img src="<?php echo esc_url($story->cover_url); ?>" style="max-width:120px;margin-top:8px;border-radius:4px;" id="cover_preview">
                            <?php else: ?>
                                <img src="" style="max-width:120px;margin-top:8px;border-radius:4px;display:none;" id="cover_preview">
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="summary">Tóm tắt</label></th>
                        <td><textarea name="summary" id="summary" rows="6" class="large-text"><?php echo esc_textarea($story->summary ?? ''); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="status">Trạng thái</label></th>
                        <td>
                            <select name="status" id="status">
                                <option value="ongoing" <?php selected(($story->status ?? ''), 'ongoing'); ?>>Đang ra</option>
                                <option value="completed" <?php selected(($story->status ?? ''), 'completed'); ?>>Hoàn thành</option>
                                <option value="dropped" <?php selected(($story->status ?? ''), 'dropped'); ?>>Ngừng</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="is_free">Miễn phí</label></th>
                        <td><label><input type="checkbox" name="is_free" id="is_free" value="1" <?php checked(($story->is_free ?? 0), 1); ?>> Truyện miễn phí</label>
                            <p class="description">Bỏ chọn để set giá bên dưới.</p></td>
                    </tr>
                    <tr>
                        <th><label for="is_featured_hidden">Ẩn khỏi đề cử</label></th>
                        <td><label><input type="checkbox" name="is_featured_hidden" id="is_featured_hidden" value="1" <?php checked(($story->is_featured_hidden ?? 0), 1); ?>> Ẩn khỏi trang chủ / đề cử</label>
                            <p class="description">Truyện vẫn hiển thị qua link trực tiếp. Tùy chọn này chỉ áp dụng cho các khu vực tự động; banner thủ công vẫn theo cấu hình banner.</p></td>
                    </tr>
                    <tr>
                        <th>Thu phí hạt</th>
                        <td style="display:flex;gap:16px;flex-wrap:wrap;align-items:center;">
                            <label>Số chương miễn phí: <input type="number" name="free_chapters" value="<?php echo (int)($story->free_chapters ?? 0); ?>" style="width:80px;" min="0"></label>
                            <label>Giá mỗi chương: <input type="number" name="chapter_price" value="<?php echo (int)($story->chapter_price ?? 0); ?>" style="width:80px;" min="0"> hạt</label>
                            <label>Giá mở full: <input type="number" name="full_price" value="<?php echo (int)($story->full_price ?? 0); ?>" style="width:80px;" min="0"> hạt</label>
                            <p class="description" style="width:100%;margin-top:4px;">Ví dụ: 2 chương free, mỗi chương sau 3 hạt, mở full 15 hạt. Để 0 nếu miễn phí.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button($is_edit ? 'Cập nhật truyện' : 'Đăng truyện', 'primary', 'hdk_save_story'); ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($){
            $('.hdk-upload-btn').click(function(e){
                e.preventDefault();
                var target = $(this).data('target');
                var frame = wp.media({title:'Chọn ảnh bìa', button:{text:'Chọn ảnh'}, multiple:false});
                frame.on('select', function(){
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#'+target).val(attachment.url);
                    $('#cover_preview').attr('src', attachment.url).show();
                });
                frame.open();
            });
        });
        </script>
        <?php
    }

    // ========== CHAPTERS PAGE ==========

    public static function chapters_page() {
        global $wpdb;
        $story_id = (int)($_GET['story_id'] ?? 0);
        if (!$story_id) {
            // Show story selector
            $stories = $wpdb->get_results("SELECT id, title FROM " . HDK_DB::table('hdk_stories') . " ORDER BY title");
            ?>
            <div class="wrap">
                <h1>Quản lý chương</h1>
                <p>Chọn truyện để quản lý chương:</p>
                <ul>
                <?php foreach ($stories as $s): ?>
                    <li><a href="?page=hdk-chapters&story_id=<?php echo $s->id; ?>"><?php echo esc_html($s->title); ?></a></li>
                <?php endforeach; ?>
                </ul>
            </div>
            <?php
            return;
        }

        $story = HDK_DB::get_story($story_id);
        if (!$story) { echo '<p>Truyện không tồn tại.</p>'; return; }

        $chapters = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . HDK_DB::table('hdk_chapters') . " WHERE story_id = %d ORDER BY chapter_number ASC", $story_id
        ));

        $edit_chapter = null;
        if (isset($_GET['edit_chapter'])) {
            foreach ($chapters as $c) {
                if ($c->id == (int)$_GET['edit_chapter']) { $edit_chapter = $c; break; }
            }
        }

        self::admin_notice();
        $tab = $_GET['tab'] ?? 'list';
        ?>
        <div class="wrap">
            <h1>Chương: <?php echo esc_html($story->title); ?>
                <a href="?page=hdk-chapters&story_id=<?php echo $story_id; ?>&tab=bulk" class="page-title-action">Đăng nhiều chương</a>
                <a href="?page=hdk-chapters&story_id=<?php echo $story_id; ?>" class="page-title-action">+ Thêm 1 chương</a>
            </h1>

            <nav class="nav-tab-wrapper" style="margin-bottom:16px;">
                <a href="?page=hdk-chapters&story_id=<?php echo $story_id; ?>" class="nav-tab <?php echo $tab !== 'bulk' ? 'nav-tab-active' : ''; ?>">Danh sách chương</a>
                <a href="?page=hdk-chapters&story_id=<?php echo $story_id; ?>&tab=bulk" class="nav-tab <?php echo $tab === 'bulk' ? 'nav-tab-active' : ''; ?>">Đăng nhiều chương</a>
            </nav>

            <?php if ($tab === 'bulk'): ?>
                <!-- BULK UPLOAD -->
                <div style="max-width:900px;">
                    <div class="card" style="padding:20px;margin-bottom:20px;background:#f0f6fc;border-left:4px solid #2271b1;">
                        <strong>📋 Format:</strong>
                        <code style="display:block;margin:8px 0;padding:12px;background:#fff;border-radius:4px;white-space:pre-wrap;font-size:13px;">Chương1: Tên chương 1
Nội dung chương thứ nhất. Có thể viết nhiều dòng...

Chương2: Tên chương 2
Nội dung chương thứ hai...

Chương3: Tên chương 3
Nội dung chương thứ ba...</code>
                        <span style="font-size:12px;color:#666;">Mỗi chương bắt đầu bằng <strong>Chương[số]: Tên</strong>, xuống dòng là nội dung. Chương đã tồn tại sẽ được cập nhật.</span>
                    </div>

                    <form method="post" enctype="multipart/form-data">
                        <?php wp_nonce_field('hdk_save_bulk_chapters'); ?>
                        <input type="hidden" name="story_id" value="<?php echo $story_id; ?>">
                        <table class="form-table">
                            <tr>
                                <th>Trạng thái</th>
                                <td>
                                    <select name="bulk_status">
                                        <option value="published">Xuất bản</option>
                                        <option value="draft">Nháp</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>Giá mỗi chương</th>
                                <td>
                                    <select name="bulk_chapter_price_mode">
                                        <option value="inherit">Theo giá truyện</option>
                                        <option value="custom">Giá riêng</option>
                                        <option value="free">Miễn phí</option>
                                    </select>
                                    <input type="number" name="bulk_chapter_price" value="0" style="width:100px;" min="0"> hạt
                                    <p class="description">Chọn “Theo giá truyện” để chương tự cập nhật khi đổi giá truyện. Có thể ghi đè từng chương bằng format <code>Chương1: Tên (100 hạt)</code>.</p>
                                </td>
                            </tr>
                            <tr>
                                <th>Lên lịch đăng</th>
                                <td>
                                    <input type="datetime-local" name="bulk_scheduled_at" value="" style="min-width:220px;">
                                    <p class="description">Để trống = đăng ngay. Chọn ngày tương lai = tất cả chương tự publish vào thời điểm đó.</p>
                                </td>
                            </tr>
                            <tr>
                                <th>Upload file .txt</th>
                                <td>
                                    <input type="file" name="bulk_file" accept=".txt" style="margin-bottom:8px;">
                                    <p class="description">Hoặc paste nội dung bên dưới. Nếu có file upload sẽ ưu tiên file.</p>
                                </td>
                            </tr>
                            <tr>
                                <th>Nội dung</th>
                                <td>
                                    <textarea name="bulk_content" rows="25" class="large-text" style="font-family:monospace;font-size:14px;line-height:1.6;"
                                        placeholder="Chương1: Mở đầu
Nội dung chương 1 viết ở đây...

Chương2: Bí mật
Nội dung chương 2 viết ở đây..."
                                    ><?php echo esc_textarea($_POST['bulk_content'] ?? ''); ?></textarea>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button('Đăng tất cả chương', 'primary', 'hdk_save_bulk_chapters'); ?>
                    </form>
                </div>
            <?php else: ?>
                <div style="display:flex;gap:24px;flex-wrap:wrap;">
                    <!-- Chapter List -->
                    <div style="flex:1;min-width:300px;">
                        <h3>Danh sách chương (<?php echo count($chapters); ?>)</h3>
                        <table class="wp-list-table widefat striped">
                            <thead><tr><th>#</th><th>Tiêu đề</th><th>Trạng thái</th><th>Giá</th><th>Lượt xem</th><th></th></tr></thead>
                            <tbody>
                                <?php foreach ($chapters as $c): ?>
                                <tr>
                                    <td><?php echo $c->chapter_number; ?></td>
                                    <td><?php echo esc_html($c->title); ?></td>
                                    <td><?php 
                                        if ($c->status === 'published') echo '✅';
                                        elseif ($c->status === 'scheduled') echo '⏰ ' . esc_html($c->scheduled_at ?? '');
                                        else echo '📝 Nháp';
                                    ?></td>
                                    <td><?php
                                        $mode = $c->price_mode ?? ((int)($c->price ?? 0) > 0 ? 'custom' : 'inherit');
                                        if ($mode === 'custom') echo (int)$c->price . ' 💎';
                                        elseif ($mode === 'free') echo 'Miễn phí';
                                        else echo 'Theo giá truyện';
                                    ?></td>
                                    <td><?php echo number_format($c->views); ?></td>
                                    <td><a href="?page=hdk-chapters&story_id=<?php echo $story_id; ?>&edit_chapter=<?php echo $c->id; ?>">Sửa</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Chapter Form -->
                    <div style="flex:1;min-width:400px;">
                        <h3><?php echo $edit_chapter ? 'Sửa chương' : 'Thêm chương mới'; ?></h3>
                        <form method="post">
                            <?php wp_nonce_field('hdk_save_chapter'); ?>
                            <input type="hidden" name="story_id" value="<?php echo $story_id; ?>">
                            <input type="hidden" name="chapter_id" value="<?php echo $edit_chapter->id ?? ''; ?>">
                            <table class="form-table">
                                <tr>
                                    <th>Chương số</th>
                                    <td><input type="number" name="chapter_number" value="<?php echo $edit_chapter->chapter_number ?? (count($chapters) + 1); ?>" style="width:80px;" required></td>
                                </tr>
                                <tr>
                                    <th>Tiêu đề</th>
                                    <td><input type="text" name="title" class="regular-text" value="<?php echo esc_attr($edit_chapter->title ?? ''); ?>" required></td>
                                </tr>
                                <tr>
                                    <th>Trạng thái</th>
                                    <td>
                                        <select name="status">
                                            <option value="draft" <?php selected(($edit_chapter->status ?? ''), 'draft'); ?>>Nháp</option>
                                            <option value="published" <?php selected(($edit_chapter->status ?? ''), 'published'); ?>>Xuất bản</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Giá (hạt)</th>
                                    <td>
                                        <?php $edit_price_mode = $edit_chapter->price_mode ?? ((int)($edit_chapter->price ?? 0) > 0 ? 'custom' : 'inherit'); ?>
                                        <select name="chapter_price_mode">
                                            <option value="inherit" <?php selected($edit_price_mode, 'inherit'); ?>>Theo giá truyện</option>
                                            <option value="custom" <?php selected($edit_price_mode, 'custom'); ?>>Giá riêng</option>
                                            <option value="free" <?php selected($edit_price_mode, 'free'); ?>>Miễn phí</option>
                                        </select>
                                        <input type="number" name="chapter_price" value="<?php echo (int)($edit_chapter->price ?? 0); ?>" style="width:100px;" min="0"> hạt
                                        <p class="description">“Theo giá truyện” sẽ tự cập nhật khi đổi giá truyện. Chọn “Giá riêng” để set tùy ý, ví dụ 100 hạt.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Lên lịch đăng</th>
                                    <td>
                                        <input type="datetime-local" name="scheduled_at" value="<?php echo esc_attr($edit_chapter->scheduled_at ?? ''); ?>" style="min-width:220px;">
                                        <p class="description">Để trống nếu đăng ngay. Nếu chọn ngày trong tương lai, chương sẽ tự động publish vào thời điểm đó.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Nội dung</th>
                                    <td>
                                        <?php
                                        $content = $edit_chapter->content ?? '';
                                        wp_editor($content, 'chapter_content', [
                                            'textarea_name' => 'content',
                                            'textarea_rows' => 20,
                                            'media_buttons' => true,
                                            'teeny' => false,
                                        ]);
                                        ?>
                                    </td>
                                </tr>
                            </table>
                            <?php submit_button($edit_chapter ? 'Cập nhật chương' : 'Thêm chương', 'primary', 'hdk_save_chapter'); ?>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // ========== CATEGORIES PAGE ==========

    public static function categories_page() {
        global $wpdb;
        $table = HDK_DB::table('hdk_categories');

        // Delete
        if (isset($_GET['delete_cat']) && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'hdk_delete_cat')) {
            $wpdb->delete($table, ['id' => (int)$_GET['delete_cat']]);
            echo '<div class="notice notice-success"><p>Đã xóa thể loại.</p></div>';
        }

        $cats = $wpdb->get_results("SELECT * FROM $table ORDER BY sort_order");
        $edit_cat = null;
        if (isset($_GET['edit_cat'])) {
            foreach ($cats as $c) { if ($c->id == (int)$_GET['edit_cat']) { $edit_cat = $c; break; } }
        }

        self::admin_notice();
        ?>
        <div class="wrap">
            <h1>Quản lý thể loại</h1>

            <div style="display:flex;gap:24px;flex-wrap:wrap;">
                <div style="flex:1;min-width:300px;">
                    <table class="wp-list-table widefat striped">
                        <thead><tr><th>ID</th><th>Tên</th><th>Slug</th><th>Số truyện</th><th>Thứ tự</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($cats as $c): ?>
                            <tr>
                                <td><?php echo $c->id; ?></td>
                                <td><strong><?php echo esc_html($c->name); ?></strong></td>
                                <td><?php echo $c->slug; ?></td>
                                <td><?php echo $c->story_count; ?></td>
                                <td><?php echo $c->sort_order; ?></td>
                                <td>
                                    <a href="?page=hdk-categories&edit_cat=<?php echo $c->id; ?>">Sửa</a> |
                                    <a href="<?php echo wp_nonce_url("?page=hdk-categories&delete_cat=$c->id", 'hdk_delete_cat'); ?>" onclick="return confirm('Xóa?')" style="color:#b32d2e;">Xóa</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="flex:0 0 350px;">
                    <h3><?php echo $edit_cat ? 'Sửa thể loại' : 'Thêm thể loại mới'; ?></h3>
                    <form method="post">
                        <?php wp_nonce_field('hdk_save_category'); ?>
                        <input type="hidden" name="category_id" value="<?php echo $edit_cat->id ?? ''; ?>">
                        <table class="form-table">
                            <tr><th>Tên <span style="color:red">*</span></th><td><input type="text" name="name" required class="regular-text" value="<?php echo esc_attr($edit_cat->name ?? ''); ?>"></td></tr>
                            <tr><th>Slug</th><td><input type="text" name="slug" class="regular-text" value="<?php echo esc_attr($edit_cat->slug ?? ''); ?>"></td></tr>
                            <tr><th>Mô tả</th><td><textarea name="description" rows="3" class="large-text"><?php echo esc_textarea($edit_cat->description ?? ''); ?></textarea></td></tr>
                            <tr><th>Thứ tự</th><td><input type="number" name="sort_order" value="<?php echo $edit_cat->sort_order ?? 0; ?>" style="width:80px;"></td></tr>
                        </table>
                        <?php submit_button($edit_cat ? 'Cập nhật' : 'Thêm thể loại', 'primary', 'hdk_save_category'); ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    // ========== AUTHORS PAGE ==========

    public static function authors_page() {
        global $wpdb;
        $table = HDK_DB::table('hdk_authors');

        if (isset($_GET['delete_author']) && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'hdk_delete_author')) {
            $wpdb->delete($table, ['id' => (int)$_GET['delete_author']]);
            echo '<div class="notice notice-success"><p>Đã xóa tác giả.</p></div>';
        }

        $authors = $wpdb->get_results("SELECT * FROM $table ORDER BY name");
        $edit_author = null;
        if (isset($_GET['edit_author'])) {
            foreach ($authors as $a) { if ($a->id == (int)$_GET['edit_author']) { $edit_author = $a; break; } }
        }

        self::admin_notice();
        ?>
        <div class="wrap">
            <h1>Quản lý tác giả</h1>
            <div style="display:flex;gap:24px;flex-wrap:wrap;">
                <div style="flex:1;min-width:300px;">
                    <table class="wp-list-table widefat striped">
                        <thead><tr><th>ID</th><th>Ảnh</th><th>Tên</th><th>Slug</th><th>Số truyện</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($authors as $a): ?>
                            <tr>
                                <td><?php echo $a->id; ?></td>
                                <td><?php if($a->avatar_url): ?><img src="<?php echo esc_url($a->avatar_url); ?>" width="32" height="32" style="border-radius:50%;object-fit:cover;"><?php endif; ?></td>
                                <td><strong><?php echo esc_html($a->name); ?></strong></td>
                                <td><?php echo $a->slug; ?></td>
                                <td><?php echo $a->story_count; ?></td>
                                <td>
                                    <a href="?page=hdk-authors&edit_author=<?php echo $a->id; ?>">Sửa</a> |
                                    <a href="<?php echo wp_nonce_url("?page=hdk-authors&delete_author=$a->id", 'hdk_delete_author'); ?>" onclick="return confirm('Xóa?')" style="color:#b32d2e;">Xóa</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="flex:0 0 350px;">
                    <h3><?php echo $edit_author ? 'Sửa tác giả' : 'Thêm tác giả mới'; ?></h3>
                    <form method="post">
                        <?php wp_nonce_field('hdk_save_author'); ?>
                        <input type="hidden" name="author_id" value="<?php echo $edit_author->id ?? ''; ?>">
                        <table class="form-table">
                            <tr><th>Tên <span style="color:red">*</span></th><td><input type="text" name="name" required class="regular-text" value="<?php echo esc_attr($edit_author->name ?? ''); ?>"></td></tr>
                            <tr><th>Slug</th><td><input type="text" name="slug" class="regular-text" value="<?php echo esc_attr($edit_author->slug ?? ''); ?>"></td></tr>
                            <tr><th>Tiểu sử</th><td><textarea name="bio" rows="3" class="large-text"><?php echo esc_textarea($edit_author->bio ?? ''); ?></textarea></td></tr>
                            <tr><th>Ảnh đại diện</th><td>
                                <input type="text" name="avatar_url" class="regular-text" value="<?php echo esc_attr($edit_author->avatar_url ?? ''); ?>" placeholder="URL ảnh">
                                <button type="button" class="button hdk-upload-btn" data-target="avatar_url">Chọn ảnh</button>
                            </td></tr>
                        </table>
                        <?php submit_button($edit_author ? 'Cập nhật' : 'Thêm tác giả', 'primary', 'hdk_save_author'); ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    // ========== CHARACTERS PAGE ==========

    public static function characters_page() {
        global $wpdb;
        $table = HDK_DB::table('hdk_characters');

        if (isset($_GET['delete_char']) && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'hdk_delete_char')) {
            $wpdb->delete($table, ['id' => (int)$_GET['delete_char']]);
            echo '<div class="notice notice-success"><p>Đã xóa nhân vật.</p></div>';
        }

        $chars = $wpdb->get_results("SELECT * FROM $table ORDER BY name");
        $edit_char = null;
        if (isset($_GET['edit_char'])) {
            foreach ($chars as $c) { if ($c->id == (int)$_GET['edit_char']) { $edit_char = $c; break; } }
        }

        self::admin_notice();
        ?>
        <div class="wrap">
            <h1>Quản lý nhân vật</h1>
            <div style="display:flex;gap:24px;flex-wrap:wrap;">
                <div style="flex:1;min-width:300px;">
                    <table class="wp-list-table widefat striped">
                        <thead><tr><th>ID</th><th>Tên</th><th>Slug</th><th>Vai trò</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($chars as $c): ?>
                            <tr>
                                <td><?php echo $c->id; ?></td>
                                <td><strong><?php echo esc_html($c->name); ?></strong></td>
                                <td><?php echo $c->slug; ?></td>
                                <td><?php echo $c->role; ?></td>
                                <td>
                                    <a href="?page=hdk-characters&edit_char=<?php echo $c->id; ?>">Sửa</a> |
                                    <a href="<?php echo wp_nonce_url("?page=hdk-characters&delete_char=$c->id", 'hdk_delete_char'); ?>" onclick="return confirm('Xóa?')" style="color:#b32d2e;">Xóa</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="flex:0 0 350px;">
                    <h3><?php echo $edit_char ? 'Sửa nhân vật' : 'Thêm nhân vật mới'; ?></h3>
                    <form method="post">
                        <?php wp_nonce_field('hdk_save_character'); ?>
                        <input type="hidden" name="character_id" value="<?php echo $edit_char->id ?? ''; ?>">
                        <table class="form-table">
                            <tr><th>Tên <span style="color:red">*</span></th><td><input type="text" name="name" required class="regular-text" value="<?php echo esc_attr($edit_char->name ?? ''); ?>"></td></tr>
                            <tr><th>Slug</th><td><input type="text" name="slug" class="regular-text" value="<?php echo esc_attr($edit_char->slug ?? ''); ?>"></td></tr>
                            <tr><th>Vai trò</th><td>
                                <select name="role">
                                    <option value="main" <?php selected(($edit_char->role ?? ''), 'main'); ?>>Nhân vật chính</option>
                                    <option value="supporting" <?php selected(($edit_char->role ?? ''), 'supporting'); ?>>Phụ</option>
                                    <option value="antagonist" <?php selected(($edit_char->role ?? ''), 'antagonist'); ?>>Phản diện</option>
                                </select>
                            </td></tr>
                            <tr><th>Mô tả</th><td><textarea name="description" rows="3" class="large-text"><?php echo esc_textarea($edit_char->description ?? ''); ?></textarea></td></tr>
                            <tr><th>Ảnh</th><td><input type="text" name="avatar_url" class="regular-text" value="<?php echo esc_attr($edit_char->avatar_url ?? ''); ?>" placeholder="URL ảnh"></td></tr>
                        </table>
                        <?php submit_button($edit_char ? 'Cập nhật' : 'Thêm nhân vật', 'primary', 'hdk_save_character'); ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    public static function import_page() {
        $results = get_transient('hdk_import_results');
        $preview = null;
        $errors = [];

        // Handle file upload for preview
        if (isset($_FILES['import_file']['tmp_name']) && !empty($_FILES['import_file']['tmp_name']) && !isset($_POST['hdk_import_confirm'])) {
            $file = $_FILES['import_file'];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $content = file_get_contents($file['tmp_name']);
            
            if ($ext === 'json') {
                $rows = json_decode($content, true);
                if (!is_array($rows)) $rows = [];
            } else {
                $rows = self::parse_csv($content);
            }

            $preview = array_slice($rows, 0, 20);
            $errors = self::validate_import_rows($rows);
        }
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Import nội dung</h1>
            <hr class="wp-header-end">

            <?php if ($results): ?>
                <div class="notice notice-success"><p>Import hoàn tất: <?php echo (int)$results['created']; ?> tạo mới, <?php echo (int)$results['skipped']; ?> bỏ qua, <?php echo (int)$results['errors']; ?> lỗi.</p></div>
                <?php delete_transient('hdk_import_results'); ?>
            <?php endif; ?>

            <div class="card" style="max-width:700px;padding:20px;">
                <h2>Tải file CSV/JSON</h2>
                <p style="color:var(--color-text-muted);">Định dạng CSV: <code>type,title,slug,author,categories,summary,status,is_free,chapter_number,chapter_title,content</code></p>
                <p style="color:var(--color-text-muted);">Các type: <code>story, chapter, author, category</code></p>
                
                <form method="post" enctype="multipart/form-data" style="margin-top:12px;">
                    <?php wp_nonce_field('hdk_import'); ?>
                    <input type="file" name="import_file" accept=".csv,.json" required style="margin-bottom:8px;">
                    <p>
                        <button type="submit" class="button button-primary">Xem trước</button>
                    </p>
                </form>
            </div>

            <?php if ($errors): ?>
                <div class="card" style="max-width:700px;padding:20px;margin-top:16px;border-left:4px solid #dc3232;">
                    <h3 style="color:#dc3232;">⚠ <?php echo count($errors); ?> lỗi phát hiện</h3>
                    <ul style="max-height:200px;overflow-y:auto;">
                        <?php foreach (array_slice($errors, 0, 30) as $err): ?>
                            <li style="color:#dc3232;"><?php echo esc_html($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($preview): ?>
                <div class="card" style="max-width:100%;padding:20px;margin-top:16px;">
                    <h3>Xem trước (<?php echo count($preview); ?>/<?php echo count($rows); ?> dòng)</h3>
                    <div style="overflow-x:auto;">
                        <table class="wp-list-table widefat fixed striped">
                            <thead><tr>
                                <th>Type</th><th>Title</th><th>Slug</th><th>Author</th><th>Status</th><th>Chương</th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($preview as $row): ?>
                                <tr>
                                    <td><span class="badge badge-<?php echo ($row['type'] ?? '') === 'story' ? 'primary' : (($row['type'] ?? '') === 'chapter' ? 'success' : 'warning'); ?>"><?php echo esc_html($row['type'] ?? ''); ?></span></td>
                                    <td><?php echo esc_html($row['title'] ?? ''); ?></td>
                                    <td><?php echo esc_html($row['slug'] ?? ''); ?></td>
                                    <td><?php echo esc_html($row['author'] ?? ''); ?></td>
                                    <td><?php echo esc_html($row['status'] ?? ''); ?></td>
                                    <td><?php echo esc_html($row['chapter_number'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <form method="post" enctype="multipart/form-data" style="margin-top:16px;">
                        <?php wp_nonce_field('hdk_import'); ?>
                        <input type="hidden" name="import_file" value="<?php echo esc_attr($file['tmp_name']); ?>">
                        <label><input type="checkbox" name="skip_errors" value="1" checked> Bỏ qua dòng lỗi</label>
                        <p><button type="submit" name="hdk_import_confirm" class="button button-primary" onclick="return confirm('Xác nhận import?');">Xác nhận import</button></p>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function save_banner($data) {
        $ids = [];
        for ($i = 1; $i <= 6; $i++) {
            $val = (int)($data['banner_story_' . sprintf('%02d', $i)] ?? 0);
            $ids[] = $val > 0 ? $val : 0;
        }
        update_option('hdk_home_banner_story_ids', $ids);
        wp_redirect(admin_url('admin.php?page=hdk-banner&message=saved'));
        exit;
    }

    public static function banner_page() {
        global $wpdb;
        $table = HDK_DB::table('hdk_stories');
        $stories = $wpdb->get_results("SELECT id, title FROM $table ORDER BY title ASC");
        $saved_ids = get_option('hdk_home_banner_story_ids', []);

        if (isset($_GET['message']) && $_GET['message'] === 'saved') {
            echo '<div class="notice notice-success is-dismissible"><p>Đã lưu cấu hình banner.</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Cấu hình Banner Trang Chủ</h1>
            <p>Chọn đúng 6 truyện hiển thị ở banner trang chủ. Thứ tự chọn tương ứng vị trí 01 đến 06.</p>
            <form method="post" style="max-width:700px;">
                <?php wp_nonce_field('hdk_save_banner'); ?>
                <table class="form-table">
                    <?php for ($i = 1; $i <= 6; $i++):
                        $name = 'banner_story_' . sprintf('%02d', $i);
                        $current = $saved_ids[$i - 1] ?? 0;
                    ?>
                    <tr>
                        <th><label for="<?php echo $name; ?>">Vị trí <?php echo sprintf('%02d', $i); ?></label></th>
                        <td>
                            <select name="<?php echo $name; ?>" id="<?php echo $name; ?>" style="width:100%;max-width:500px;">
                                <option value="0">-- Chọn truyện --</option>
                                <?php foreach ($stories as $s): ?>
                                    <option value="<?php echo $s->id; ?>" <?php selected($current, $s->id); ?>>
                                        #<?php echo $s->id; ?> - <?php echo esc_html($s->title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <?php endfor; ?>
                </table>
                <?php submit_button('Lưu cấu hình', 'primary', 'hdk_save_banner'); ?>
            </form>
        </div>
        <?php
    }

    public static function seed_page() {
        if (isset($_POST['seed_demo']) && wp_verify_nonce($_POST['_wpnonce'], 'hdk_seed')) {
            HDK_DB::seed_demo_data();
            echo '<div class="notice notice-success"><p>Demo data seeded successfully! 5 authors, 10 categories, 30 stories.</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Seed dữ liệu demo</h1>
            <p>Tạo 5 tác giả, 10 thể loại, 30 truyện với chương mẫu để test.</p>
            <form method="post">
                <?php wp_nonce_field('hdk_seed'); ?>
                <?php submit_button('Seed dữ liệu demo', 'primary', 'seed_demo'); ?>
            </form>
        </div>
        <?php
    }

    private static function admin_notice() {
        $messages = [
            'created' => 'Đã tạo truyện thành công!',
            'updated' => 'Đã cập nhật truyện!',
            'deleted' => 'Đã xóa truyện!',
            'saved' => 'Đã lưu thành công!',
            'no_chapters' => 'Không tìm thấy chương nào. Kiểm tra lại format.',
        ];
        $msg = $_GET['message'] ?? '';
        $count = (int)($_GET['count'] ?? 0);
        if ($msg === 'bulk_saved' && $count > 0) {
            echo '<div class="notice notice-success is-dismissible"><p>Đã lưu <strong>' . $count . '</strong> chương thành công!</p></div>';
        } elseif (isset($messages[$msg])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . $messages[$msg] . '</p></div>';
        }
    }

    public static function credits_page() {
        global $wpdb;
        $search = sanitize_text_field($_GET['s'] ?? '');
        $page = max(1, (int)($_GET['paged'] ?? 1));
        $data = HDK_DB::get_all_user_credits($search, $page);
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Quản lý hạt</h1>
            <hr class="wp-header-end">

            <form method="get" style="margin-bottom:16px;">
                <input type="hidden" name="page" value="hdk-credits">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Tìm username..." style="padding:6px 10px;min-width:240px;">
                <button type="submit" class="button">Tìm</button>
            </form>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Số dư</th>
                        <th>Tổng nạp</th>
                        <th>Tổng tiêu</th>
                        <th>Điều chỉnh hạt</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($data['rows'] as $row): ?>
                    <tr>
                        <td><strong><?php echo esc_html($row->display_name); ?></strong> (<?php echo esc_html($row->user_login); ?>)</td>
                        <td>💎 <?php echo number_format((int)$row->credits); ?></td>
                        <td><?php echo number_format((int)$row->total_earned); ?></td>
                        <td><?php echo number_format((int)$row->total_spent); ?></td>
                        <td>
                            <form method="post" style="display:inline-flex;gap:8px;align-items:center;">
                                <?php wp_nonce_field('hdk_adjust_credits'); ?>
                                <input type="hidden" name="user_id" value="<?php echo (int)$row->user_id; ?>">
                                <input type="number" name="credit_amount" value="" placeholder="+/- hạt" style="width:80px;" required>
                                <input type="text" name="adjust_note" value="" placeholder="Ghi chú" style="width:120px;" required>
                                <button type="submit" name="hdk_adjust_credits" class="button button-small">Cập nhật</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($data['rows'])): ?>
                    <tr><td colspan="5">Không có dữ liệu</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            <?php if ($data['pages'] > 1): ?>
                <div class="tablenav"><div class="tablenav-pages">
                    <?php for ($i = 1; $i <= $data['pages']; $i++): ?>
                        <a href="?page=hdk-credits&paged=<?php echo $i; ?>&s=<?php echo urlencode($search); ?>" class="button<?php echo $i === $page ? ' button-primary' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div></div>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function packages_page() {
        global $wpdb;
        $edit_id = (int)($_GET['edit'] ?? 0);
        $edit_package = null;
        if ($edit_id) {
            $edit_package = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . HDK_DB::table('hdk_credit_packages') . " WHERE id = %d", $edit_id));
        }
        $packages = HDK_DB::get_credit_packages(false);
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Gói nạp hạt</h1>
            <?php if (!$edit_id): ?>
                <a href="?page=hdk-packages&edit=new" class="page-title-action">Thêm gói mới</a>
            <?php endif; ?>
            <hr class="wp-header-end">

            <?php if ($edit_id || (isset($_GET['edit']) && $_GET['edit'] === 'new')): ?>
                <div class="card" style="max-width:500px;padding:20px;margin-bottom:20px;">
                    <h2><?php echo $edit_package ? 'Sửa gói: ' . esc_html($edit_package->name) : 'Thêm gói mới'; ?></h2>
                    <form method="post">
                        <?php wp_nonce_field('hdk_save_package'); ?>
                        <input type="hidden" name="package_id" value="<?php echo $edit_package ? (int)$edit_package->id : ''; ?>">
                        <table class="form-table">
                            <tr>
                                <th>Tên gói</th>
                                <td><input type="text" name="package_name" value="<?php echo esc_attr($edit_package->name ?? ''); ?>" required class="regular-text"></td>
                            </tr>
                            <tr>
                                <th>Số hạt</th>
                                <td><input type="number" name="package_credits" value="<?php echo (int)($edit_package->credits ?? 0); ?>" required style="width:100px;"></td>
                            </tr>
                            <tr>
                                <th>Hạt thưởng</th>
                                <td><input type="number" name="package_bonus" value="<?php echo (int)($edit_package->bonus_credits ?? 0); ?>" style="width:100px;"></td>
                            </tr>
                            <tr>
                                <th>Giá (VNĐ)</th>
                                <td><input type="number" name="package_price" value="<?php echo (int)($edit_package->price_vnd ?? 0); ?>" required style="width:120px;"></td>
                            </tr>
                            <tr>
                                <th>Thứ tự</th>
                                <td><input type="number" name="package_sort" value="<?php echo (int)($edit_package->sort_order ?? 0); ?>" style="width:80px;"></td>
                            </tr>
                            <tr>
                                <th>Kích hoạt</th>
                                <td><label><input type="checkbox" name="package_active" <?php checked($edit_package->is_active ?? 1, 1); ?>> Hiển thị</label></td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" name="hdk_save_package" class="button button-primary">Lưu</button>
                            <a href="?page=hdk-packages" class="button">Hủy</a>
                        </p>
                    </form>
                </div>
            <?php endif; ?>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Tên gói</th>
                        <th>Hạt</th>
                        <th>Bonus</th>
                        <th>Giá VNĐ</th>
                        <th>TT</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($packages as $pkg): ?>
                    <tr>
                        <td><strong><?php echo esc_html($pkg->name); ?></strong></td>
                        <td><?php echo number_format((int)$pkg->credits); ?></td>
                        <td><?php echo $pkg->bonus_credits > 0 ? '+' . number_format((int)$pkg->bonus_credits) : '—'; ?></td>
                        <td><?php echo number_format((int)$pkg->price_vnd); ?> đ</td>
                        <td><?php echo $pkg->is_active ? '<span style="color:green;">● Active</span>' : '<span style="color:#999;">● Inactive</span>'; ?></td>
                        <td>
                            <a href="?page=hdk-packages&edit=<?php echo (int)$pkg->id; ?>" class="button button-small">Sửa</a>
                            <?php $toggle_url = wp_nonce_url("?page=hdk-packages&action=toggle_package&id=" . (int)$pkg->id, 'hdk_toggle_package_' . $pkg->id); ?>
                            <a href="<?php echo esc_url($toggle_url); ?>" class="button button-small"><?php echo $pkg->is_active ? 'Tắt' : 'Bật'; ?></a>
                            <?php $del_url = wp_nonce_url("?page=hdk-packages&action=delete_package&id=" . (int)$pkg->id, 'hdk_delete_package_' . $pkg->id); ?>
                            <a href="<?php echo esc_url($del_url); ?>" class="button button-small" onclick="return confirm('Xóa gói này?');">Xóa</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($packages)): ?>
                    <tr><td colspan="6">Chưa có gói nạp nào</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function transactions_page() {
        global $wpdb;
        $type_filter = sanitize_text_field($_GET['filter_type'] ?? '');
        $user_search = sanitize_text_field($_GET['filter_user'] ?? '');
        $page = max(1, (int)($_GET['paged'] ?? 1));

        $filters = [];
        if ($type_filter) $filters['type'] = $type_filter;
        if ($user_search) $filters['user'] = $user_search;

        $data = HDK_DB::get_all_transactions($filters, $page);
        $types = ['earn' => 'Nạp', 'spend' => 'Tiêu', 'daily' => 'Điểm danh', 'admin_add' => 'Admin +', 'admin_deduct' => 'Admin -', 'refund' => 'Hoàn'];
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Lịch sử giao dịch</h1>
            <hr class="wp-header-end">

            <form method="get" style="margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <input type="hidden" name="page" value="hdk-transactions">
                <select name="filter_type">
                    <option value="">Tất cả loại</option>
                    <?php foreach ($types as $k => $label): ?>
                        <option value="<?php echo $k; ?>" <?php selected($type_filter, $k); ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="search" name="filter_user" value="<?php echo esc_attr($user_search); ?>" placeholder="Tìm username..." style="padding:4px 8px;">
                <button type="submit" class="button">Lọc</button>
            </form>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Loại</th>
                        <th>Số hạt</th>
                        <th>Ghi chú</th>
                        <th>Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($data['rows'] as $tx): ?>
                    <tr>
                        <td><?php echo esc_html($tx->display_name); ?></td>
                        <td><span class="badge badge-<?php echo $tx->credits >= 0 ? 'success' : 'danger'; ?>"><?php echo $types[$tx->type] ?? $tx->type; ?></span></td>
                        <td style="color:<?php echo $tx->credits >= 0 ? 'green' : 'red'; ?>"><?php echo $tx->credits >= 0 ? '+' : ''; ?><?php echo number_format((int)$tx->credits); ?></td>
                        <td><?php echo esc_html($tx->note); ?></td>
                        <td><?php echo mysql2date('H:i d/m/Y', $tx->created_at); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($data['rows'])): ?>
                    <tr><td colspan="5">Không có giao dịch nào</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            <?php if ($data['pages'] > 1): ?>
                <div class="tablenav"><div class="tablenav-pages">
                    <?php for ($i = 1; $i <= $data['pages']; $i++): ?>
                        <a href="?page=hdk-transactions&paged=<?php echo $i; ?>&filter_type=<?php echo urlencode($type_filter); ?>&filter_user=<?php echo urlencode($user_search); ?>" class="button<?php echo $i === $page ? ' button-primary' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div></div>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function parse_csv($content) {
        $lines = explode("\n", trim($content));
        if (count($lines) < 2) return [];
        
        $header = str_getcsv(array_shift($lines), ',', '"', '');
        $header = array_map('trim', $header);
        
        $rows = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            $values = str_getcsv($line, ',', '"', '');
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = $values[$i] ?? '';
            }
            $rows[] = $row;
        }
        return $rows;
    }

    private static function validate_import_rows($rows) {
        $errors = [];
        foreach ($rows as $i => $row) {
            $line = $i + 2;
            $type = $row['type'] ?? '';
            if (!in_array($type, ['story','chapter','author','category'])) {
                $errors[] = "Dòng $line: type không hợp lệ '$type'";
                continue;
            }
            if (empty($row['title'])) {
                $errors[] = "Dòng $line: thiếu title";
            }
            if ($type === 'chapter' && empty($row['chapter_number'])) {
                $errors[] = "Dòng $line: chapter thiếu chapter_number";
            }
        }
        return $errors;
    }

    private static function process_import_rows($rows, $skip_errors = true) {
        global $wpdb;
        $created = $skipped = $errors = 0;
        $story_map = []; // slug => id
        $author_map = []; // slug => id
        $category_map = []; // slug => id

        foreach ($rows as $i => $row) {
            $type = $row['type'] ?? '';
            
            try {
                switch ($type) {
                    case 'author':
                        $slug = sanitize_title($row['slug'] ?: $row['title']);
                        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . HDK_DB::table('hdk_authors') . " WHERE slug = %s", $slug));
                        if ($exists) { $skipped++; $author_map[$slug] = $exists; continue 2; }
                        $wpdb->insert(HDK_DB::table('hdk_authors'), [
                            'name' => $row['title'], 'slug' => $slug,
                            'bio' => $row['summary'] ?? '',
                            'created_at' => current_time('mysql'), 'updated_at' => current_time('mysql'),
                        ]);
                        $author_map[$slug] = $wpdb->insert_id;
                        $created++;
                        break;

                    case 'category':
                        $slug = sanitize_title($row['slug'] ?: $row['title']);
                        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . HDK_DB::table('hdk_categories') . " WHERE slug = %s", $slug));
                        if ($exists) { $skipped++; $category_map[$slug] = $exists; continue 2; }
                        $wpdb->insert(HDK_DB::table('hdk_categories'), [
                            'name' => $row['title'], 'slug' => $slug,
                            'description' => $row['summary'] ?? '',
                            'created_at' => current_time('mysql'), 'updated_at' => current_time('mysql'),
                        ]);
                        $category_map[$slug] = $wpdb->insert_id;
                        $created++;
                        break;

                    case 'story':
                        $slug = sanitize_title($row['slug'] ?: $row['title']);
                        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . HDK_DB::table('hdk_stories') . " WHERE slug = %s", $slug));
                        if ($exists) { $skipped++; $story_map[$slug] = $exists; continue 2; }

                        // Handle author
                        $author_name = $row['author'] ?? '';
                        $author_id = 0;
                        if ($author_name) {
                            $author_slug = sanitize_title($author_name);
                            if (isset($author_map[$author_slug])) {
                                $author_id = $author_map[$author_slug];
                            } else {
                                $existing_author = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . HDK_DB::table('hdk_authors') . " WHERE slug = %s", $author_slug));
                                if ($existing_author) {
                                    $author_id = $existing_author;
                                    $author_map[$author_slug] = $author_id;
                                } else {
                                    $wpdb->insert(HDK_DB::table('hdk_authors'), [
                                        'name' => $author_name, 'slug' => $author_slug,
                                        'created_at' => current_time('mysql'), 'updated_at' => current_time('mysql'),
                                    ]);
                                    $author_id = $wpdb->insert_id;
                                    $author_map[$author_slug] = $author_id;
                                }
                            }
                        }

                        $wpdb->insert(HDK_DB::table('hdk_stories'), [
                            'title' => $row['title'], 'slug' => $slug,
                            'author_id' => $author_id,
                            'summary' => $row['summary'] ?? '',
                            'status' => in_array($row['status'] ?? '', ['ongoing','completed','dropped']) ? $row['status'] : 'ongoing',
                            'is_free' => ($row['is_free'] ?? '') === '1' ? 1 : 0,
                            'published_at' => current_time('mysql'),
                            'created_at' => current_time('mysql'), 'updated_at' => current_time('mysql'),
                        ]);
                        $story_map[$slug] = $wpdb->insert_id;
                        $created++;
                        break;

                    case 'chapter':
                        $story_title = $row['title'] ?? '';
                        $story_slug = sanitize_title($row['slug'] ?: $story_title);
                        $story_id = $story_map[$story_slug] ?? null;
                        if (!$story_id) {
                            $story_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . HDK_DB::table('hdk_stories') . " WHERE slug = %s", $story_slug));
                            if ($story_id) $story_map[$story_slug] = (int)$story_id;
                        }
                        if (!$story_id) { $errors++; continue 2; }

                        $chap_num = (int)($row['chapter_number'] ?? 0);
                        if (!$chap_num) { $errors++; continue 2; }

                        $exists = $wpdb->get_var($wpdb->prepare(
                            "SELECT id FROM " . HDK_DB::table('hdk_chapters') . " WHERE story_id = %d AND chapter_number = %d",
                            $story_id, $chap_num
                        ));
                        if ($exists) { $skipped++; continue 2; }

                        $wpdb->insert(HDK_DB::table('hdk_chapters'), [
                            'story_id' => $story_id, 'chapter_number' => $chap_num,
                            'title' => $row['chapter_title'] ?: ('Chương ' . $chap_num),
                            'content' => $row['content'] ?? '',
                            'word_count' => str_word_count(strip_tags($row['content'] ?? '')),
                            'status' => 'published',
                            'created_at' => current_time('mysql'), 'updated_at' => current_time('mysql'),
                        ]);
                        $created++;
                        break;
                }
            } catch (\Exception $e) {
                if ($skip_errors) { $errors++; continue; }
                throw $e;
            }
        }

        return ['created' => $created, 'skipped' => $skipped, 'errors' => $errors];
    }

    public static function parse_csv_public($content) {
        return self::parse_csv($content);
    }

    public static function process_import_rows_public($rows, $skip_errors = true) {
        return self::process_import_rows($rows, $skip_errors);
    }

    public static function comments_page() {
        global $wpdb;
        $status = sanitize_text_field($_GET['filter_status'] ?? 'all');
        $page = max(1, (int)($_GET['paged'] ?? 1));
        $data = HDK_DB::get_all_comments(['status' => $status], $page);
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Quản lý bình luận</h1>
            <hr class="wp-header-end">
            <div style="margin-bottom:12px;">
                <a href="?page=hdk-comments&filter_status=all" class="button<?php echo $status==='all'?' button-primary':''; ?>">Tất cả</a>
                <a href="?page=hdk-comments&filter_status=approved" class="button<?php echo $status==='approved'?' button-primary':''; ?>">Đã duyệt</a>
                <a href="?page=hdk-comments&filter_status=pending" class="button<?php echo $status==='pending'?' button-primary':''; ?>">Chờ duyệt</a>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Truyện</th><th>User</th><th>Nội dung</th><th>Trạng thái</th><th>Thời gian</th><th>Thao tác</th></tr></thead>
                <tbody>
                <?php foreach ($data['rows'] as $c): ?>
                    <tr>
                        <td><a href="<?php echo home_url('/' . ($c->story_slug ?? '')); ?>" target="_blank"><?php echo esc_html($c->story_title); ?></a><?php if ($c->chapter_number): ?><br><small>Chương <?php echo (int)$c->chapter_number; ?></small><?php endif; ?></td>
                        <td><?php echo esc_html($c->user_name); ?></td>
                        <td><?php echo esc_html(wp_trim_words($c->comment_content, 20)); ?></td>
                        <td><?php echo $c->comment_approved == '1' ? '<span style="color:green;">Đã duyệt</span>' : '<span style="color:orange;">Chờ duyệt</span>'; ?></td>
                        <td><?php echo mysql2date('d/m/Y H:i', $c->comment_date); ?></td>
                        <td style="white-space:nowrap;">
                            <?php if ($c->comment_approved != '1'): ?><a href="<?php echo wp_nonce_url("?page=hdk-comments&hdk_comment_action=approve&comment_id=".(int)$c->comment_ID, 'hdk_comment_'.(int)$c->comment_ID); ?>" class="button button-small">Duyệt</a><?php endif; ?>
                            <?php if ($c->comment_approved == '1'): ?><a href="<?php echo wp_nonce_url("?page=hdk-comments&hdk_comment_action=unapprove&comment_id=".(int)$c->comment_ID, 'hdk_comment_'.(int)$c->comment_ID); ?>" class="button button-small">Bỏ duyệt</a><?php endif; ?>
                            <a href="<?php echo wp_nonce_url("?page=hdk-comments&hdk_comment_action=trash&comment_id=".(int)$c->comment_ID, 'hdk_comment_'.(int)$c->comment_ID); ?>" class="button button-small" onclick="return confirm('Xóa?');">Xóa</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($data['pages'] > 1): ?>
                <div class="tablenav"><div class="tablenav-pages">
                    <?php for ($i = 1; $i <= $data['pages']; $i++): ?>
                        <a href="?page=hdk-comments&paged=<?php echo $i; ?>&filter_status=<?php echo $status; ?>" class="button<?php echo $i===$page?' button-primary':''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div></div>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function reports_page() {
        $status = sanitize_text_field($_GET['filter_status'] ?? '');
        $page = max(1, (int)($_GET['paged'] ?? 1));
        $filters = [];
        if ($status) $filters['status'] = $status;
        $data = HDK_DB::get_reports($filters, $page);
        $types = ['typo'=>'Lỗi chính tả','wrong_content'=>'Sai nội dung','display_error'=>'Lỗi hiển thị','other'=>'Khác'];
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Báo lỗi chương</h1>
            <hr class="wp-header-end">
            <div style="margin-bottom:12px;">
                <a href="?page=hdk-reports" class="button<?php echo !$status?' button-primary':''; ?>">Tất cả</a>
                <a href="?page=hdk-reports&filter_status=pending" class="button<?php echo $status==='pending'?' button-primary':''; ?>">Chờ xử lý</a>
                <a href="?page=hdk-reports&filter_status=resolved" class="button<?php echo $status==='resolved'?' button-primary':''; ?>">Đã xử lý</a>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Truyện</th><th>Chương</th><th>User</th><th>Loại</th><th>Ghi chú</th><th>Thời gian</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                <tbody>
                <?php foreach ($data['rows'] as $r): ?>
                    <tr>
                        <td><a href="<?php echo home_url('/' . $r->story_slug . '?chuong=' . (int)$r->chapter_number); ?>" target="_blank"><?php echo esc_html($r->story_title); ?></a></td>
                        <td><?php echo (int)$r->chapter_number; ?></td>
                        <td><?php echo esc_html($r->display_name); ?></td>
                        <td><?php echo $types[$r->report_type] ?? $r->report_type; ?></td>
                        <td><?php echo esc_html($r->note); ?></td>
                        <td><?php echo mysql2date('d/m/Y H:i', $r->created_at); ?></td>
                        <td><?php echo $r->status === 'pending' ? '<span style="color:orange;">Chờ XL</span>' : '<span style="color:green;">Đã XL</span>'; ?></td>
                        <td>
                            <?php if ($r->status === 'pending'): ?>
                                <a href="<?php echo wp_nonce_url("?page=hdk-reports&hdk_report_action=resolve&report_id=".(int)$r->id, 'hdk_report_'.(int)$r->id); ?>" class="button button-small">Đã xử lý</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($data['pages'] > 1): ?>
                <div class="tablenav"><div class="tablenav-pages">
                    <?php for ($i = 1; $i <= $data['pages']; $i++): ?>
                        <a href="?page=hdk-reports&paged=<?php echo $i; ?>&filter_status=<?php echo $status; ?>" class="button<?php echo $i===$page?' button-primary':''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div></div>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function stats_page() {
        global $wpdb;
        $stories_table = HDK_DB::table('hdk_stories');
        $chapters_table = HDK_DB::table('hdk_chapters');
        $stats_table = HDK_DB::table('hdk_daily_story_stats');
        $users_table = $wpdb->users;
        $purch_table = HDK_DB::table('hdk_purchased_chapters');

        // Totals
        $total_stories = $wpdb->get_var("SELECT COUNT(*) FROM $stories_table");
        $total_chapters = $wpdb->get_var("SELECT COUNT(*) FROM $chapters_table WHERE status = 'published'");
        $total_views = $wpdb->get_var("SELECT SUM(total_views) FROM $stories_table");
        $total_users = $wpdb->get_var("SELECT COUNT(*) FROM $users_table");
        $total_credits = $wpdb->get_var("SELECT SUM(credits_spent) FROM $purch_table");
        $total_favorites = $wpdb->get_var("SELECT SUM(total_favorites) FROM $stories_table");

        // Today views
        $today = current_time('Y-m-d');
        $today_views = $wpdb->get_var($wpdb->prepare("SELECT SUM(daily_views) FROM $stats_table WHERE stat_date = %s", $today));

        // Top 10 stories today
        $top_today = $wpdb->get_results($wpdb->prepare(
            "SELECT s.title, s.slug, ds.daily_views
             FROM $stats_table ds JOIN $stories_table s ON ds.story_id = s.id
             WHERE ds.stat_date = %s ORDER BY ds.daily_views DESC LIMIT 10", $today
        ));

        // Top 5 purchased stories
        $top_purchased = $wpdb->get_results(
            "SELECT s.title, s.slug, COUNT(*) as cnt FROM $purch_table p
             JOIN $stories_table s ON p.story_id = s.id
             GROUP BY p.story_id ORDER BY cnt DESC LIMIT 5"
        );

        // Recent 7 days view trend
        $trend = $wpdb->get_results(
            "SELECT stat_date, SUM(daily_views) as views FROM $stats_table
             WHERE stat_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY stat_date ORDER BY stat_date ASC"
        );
        ?>
        <div class="wrap">
            <h1>Thống kê vận hành</h1>
            <hr class="wp-header-end">

            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px;margin-bottom:24px;">
                <div class="card" style="padding:20px;text-align:center;">
                    <div style="font-size:32px;font-weight:700;color:var(--color-primary);"><?php echo number_format((int)$total_stories); ?></div>
                    <div style="color:var(--color-text-muted);">Truyện</div>
                </div>
                <div class="card" style="padding:20px;text-align:center;">
                    <div style="font-size:32px;font-weight:700;color:var(--color-primary);"><?php echo number_format((int)$total_chapters); ?></div>
                    <div style="color:var(--color-text-muted);">Chương</div>
                </div>
                <div class="card" style="padding:20px;text-align:center;">
                    <div style="font-size:32px;font-weight:700;color:var(--color-primary);"><?php echo number_format((int)$total_views); ?></div>
                    <div style="color:var(--color-text-muted);">Tổng lượt xem</div>
                </div>
                <div class="card" style="padding:20px;text-align:center;">
                    <div style="font-size:32px;font-weight:700;color:var(--color-primary);"><?php echo number_format((int)$total_users); ?></div>
                    <div style="color:var(--color-text-muted);">Người dùng</div>
                </div>
                <div class="card" style="padding:20px;text-align:center;">
                    <div style="font-size:32px;font-weight:700;color:var(--color-primary);"><?php echo number_format((int)$today_views); ?></div>
                    <div style="color:var(--color-text-muted);">Lượt xem hôm nay</div>
                </div>
                <div class="card" style="padding:20px;text-align:center;">
                    <div style="font-size:32px;font-weight:700;color:var(--color-primary);">💎 <?php echo number_format((int)$total_credits); ?></div>
                    <div style="color:var(--color-text-muted);">Hạt đã tiêu</div>
                </div>
            </div>

            <!-- 7-day trend -->
            <?php if ($trend): ?>
            <div class="card" style="padding:20px;margin-bottom:24px;">
                <h3>Lượt xem 7 ngày qua</h3>
                <div style="display:flex;gap:4px;align-items:flex-end;height:150px;">
                    <?php 
                    $max_val = max(array_column($trend, 'views')) ?: 1;
                    foreach ($trend as $day): 
                        $h = max(4, (int)($day->views / $max_val * 140));
                    ?>
                        <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;">
                            <div style="font-size:10px;margin-bottom:2px;"><?php echo number_format((int)$day->views); ?></div>
                            <div style="width:100%;max-width:40px;height:<?php echo $h; ?>px;background:var(--color-primary);border-radius:4px 4px 0 0;"></div>
                            <div style="font-size:10px;color:var(--color-text-muted);margin-top:4px;"><?php echo date('d/m', strtotime($day->stat_date)); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div style="display:flex;gap:24px;flex-wrap:wrap;">
                <!-- Top stories today -->
                <div class="card" style="flex:1;min-width:300px;padding:20px;">
                    <h3>Top truyện hôm nay</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead><tr><th>#</th><th>Truyện</th><th>Views</th></tr></thead>
                        <tbody>
                        <?php foreach ($top_today as $i => $s): ?>
                            <tr>
                                <td><?php echo $i+1; ?></td>
                                <td><a href="<?php echo home_url('/'.$s->slug); ?>" target="_blank"><?php echo esc_html($s->title); ?></a></td>
                                <td><?php echo number_format((int)$s->daily_views); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Top purchased -->
                <div class="card" style="flex:1;min-width:300px;padding:20px;">
                    <h3>Top truyện bán chạy</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead><tr><th>#</th><th>Truyện</th><th>Lượt mua</th></tr></thead>
                        <tbody>
                        <?php foreach ($top_purchased as $i => $p): ?>
                            <tr>
                                <td><?php echo $i+1; ?></td>
                                <td><a href="<?php echo home_url('/'.$p->slug); ?>" target="_blank"><?php echo esc_html($p->title); ?></a></td>
                                <td><?php echo number_format((int)$p->cnt); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
}
