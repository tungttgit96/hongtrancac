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
        add_submenu_page('hdk-stories', 'Seed Demo', 'Seed Demo', 'manage_options', 'hdk-seed', [__CLASS__, 'seed_page']);

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
        $chap_data = [
            'story_id' => $story_id,
            'chapter_number' => (int)($data['chapter_number'] ?? 0),
            'title' => sanitize_text_field($data['title'] ?? ''),
            'content' => wp_kses_post($data['content'] ?? ''),
            'word_count' => str_word_count(strip_tags($data['content'] ?? '')),
            'status' => $data['status'] ?? 'draft',
            'updated_at' => $now,
        ];
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

        wp_redirect(admin_url('admin.php?page=hdk-chapters&story_id=' . $story_id . '&message=saved'));
        exit;
    }

    private static function save_bulk_chapters($data) {
        global $wpdb;
        $table = HDK_DB::table('hdk_chapters');
        $now = current_time('mysql');
        $story_id = (int)($data['story_id'] ?? 0);
        $status = $data['bulk_status'] ?? 'published';

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
                'status' => $status,
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
                        <th>ID</th><th>Ảnh</th><th>Tiêu đề</th><th>Tác giả</th><th>Thể loại</th><th>Trạng thái</th><th>Chương</th><th>Lượt xem</th><th>Đánh giá</th><th>Ngày cập nhật</th><th></th>
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
                            <thead><tr><th>#</th><th>Tiêu đề</th><th>Trạng thái</th><th>Lượt xem</th><th></th></tr></thead>
                            <tbody>
                                <?php foreach ($chapters as $c): ?>
                                <tr>
                                    <td><?php echo $c->chapter_number; ?></td>
                                    <td><?php echo esc_html($c->title); ?></td>
                                    <td><?php echo $c->status === 'published' ? '✅' : '📝 Nháp'; ?></td>
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
                                    <th>Nội dung</th>
                                    <td>
                                        <?php
                                        $content = $edit_chapter->content ?? '';
                                        wp_editor($content, 'chapter_content', [
                                            'textarea_name' => 'content',
                                            'textarea_rows' => 20,
                                            'media_buttons' => false,
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
        ?>
        <div class="wrap">
            <h1>Import nội dung</h1>
            <p>Sử dụng WP-CLI: <code>wp hdk import --source=/path/to/export.csv</code></p>
            <p>Hoặc upload file CSV/JSON:</p>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="import_file" accept=".csv,.json" />
                <?php submit_button('Import'); ?>
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
}
