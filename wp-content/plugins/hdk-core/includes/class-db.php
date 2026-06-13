<?php
/**
 * HDK DB - database query helpers
 */

class HDK_DB {
    public static function table($name) {
        global $wpdb;
        return $wpdb->prefix . $name;
    }

    public static function get_story($slug_or_id) {
        global $wpdb;
        $table = self::table('hdk_stories');
        if (is_numeric($slug_or_id)) {
            $story = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $slug_or_id));
        } else {
            $story = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE slug = %s", $slug_or_id));
        }
        if ($story) {
            $story->author_name = self::get_author_name($story->author_id);
            $story->categories = self::get_story_categories($story->id);
            $story->chapter_count = self::get_chapter_count($story->id);
        }
        return $story;
    }

    public static function get_author_name($author_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT name FROM " . self::table('hdk_authors') . " WHERE id = %d",
            $author_id
        ));
    }

    public static function get_story_categories($story_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT c.* FROM " . self::table('hdk_categories') . " c
             JOIN " . self::table('hdk_story_categories') . " sc ON c.id = sc.category_id
             WHERE sc.story_id = %d ORDER BY c.sort_order",
            $story_id
        ));
    }

    public static function get_chapter_count($story_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::table('hdk_chapters') . " WHERE story_id = %d AND status = 'published'",
            $story_id
        ));
    }

    public static function get_chapter($story_id, $chapter_number) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::table('hdk_chapters') . "
             WHERE story_id = %d AND chapter_number = %d AND status = 'published'",
            $story_id, $chapter_number
        ));
    }

    public static function get_stories($args = []) {
        global $wpdb;
        $table = self::table('hdk_stories');
        $defaults = [
            'per_page' => 20,
            'page' => 1,
            'status' => '',
            'category_id' => 0,
            'author_id' => 0,
            'is_free' => null,
            'search' => '',
            'orderby' => 'updated_at',
            'order' => 'DESC',
        ];
        $args = wp_parse_args($args, $defaults);

        $where = ["1=1"];
        if ($args['status']) $where[] = $wpdb->prepare("s.status = %s", $args['status']);
        if ($args['category_id']) {
            $where[] = $wpdb->prepare("s.id IN (SELECT story_id FROM " . self::table('hdk_story_categories') . " WHERE category_id = %d)", $args['category_id']);
        }
        if ($args['author_id']) $where[] = $wpdb->prepare("s.author_id = %d", $args['author_id']);
        if ($args['is_free'] !== null) $where[] = $wpdb->prepare("s.is_free = %d", (int)$args['is_free']);
        if ($args['search']) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = $wpdb->prepare("(s.title LIKE %s OR s.summary LIKE %s)", $search, $search);
        }

        $orderby = in_array($args['orderby'], ['updated_at','total_views','average_rating','total_favorites','published_at','title'])
            ? "s.{$args['orderby']}" : 's.updated_at';
        $order = $args['order'] === 'ASC' ? 'ASC' : 'DESC';
        $offset = ($args['page'] - 1) * $args['per_page'];

        $where_sql = implode(' AND ', $where);
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table s WHERE $where_sql");

        $stories = $wpdb->get_results($wpdb->prepare(
            "SELECT s.* FROM $table s WHERE $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d",
            $args['per_page'], $offset
        ));

        foreach ($stories as $story) {
            $story->author_name = self::get_author_name($story->author_id);
            $story->categories = self::get_story_categories($story->id);
            $story->chapter_count = (int)$story->total_chapters;
        }

        // Bulk fetch max chapter prices for all stories in one query
        if (!empty($stories)) {
            $story_ids = array_map(function($s) { return $s->id; }, $stories);
            $ids_list = implode(',', array_map('intval', $story_ids));
            $chap_table = self::table('hdk_chapters');
            $prices = $wpdb->get_results(
                "SELECT story_id, MAX(price) as max_price FROM $chap_table WHERE story_id IN ($ids_list) GROUP BY story_id"
            );
            $price_map = [];
            foreach ($prices as $p) $price_map[$p->story_id] = (int)$p->max_price;
            foreach ($stories as $story) {
                if (empty($story->chapter_price) && isset($price_map[$story->id]) && $price_map[$story->id] > 0) {
                    $story->chapter_price = $price_map[$story->id];
                }
            }
        }

        return ['stories' => $stories, 'total' => (int)$total, 'pages' => (int)ceil($total / $args['per_page'])];
    }

    public static function get_ranking($metric = 'views', $period = 'all', $category_id = 0, $page = 1, $per_page = 20) {
        global $wpdb;
        $stories_table = self::table('hdk_stories');
        $stats_table = self::table('hdk_daily_story_stats');

        if ($period === 'all') {
            $order = $metric === 'favorites' ? 's.total_favorites' : ($metric === 'ratings' ? 's.average_rating' : 's.total_views');
            $where = '1=1';
            if ($category_id) {
                $where = $wpdb->prepare("s.id IN (SELECT story_id FROM " . self::table('hdk_story_categories') . " WHERE category_id = %d)", $category_id);
            }
            $offset = ($page - 1) * $per_page;
            $total = $wpdb->get_var("SELECT COUNT(*) FROM $stories_table s WHERE $where");
            $stories = $wpdb->get_results($wpdb->prepare(
                "SELECT s.* FROM $stories_table s WHERE $where ORDER BY $order DESC LIMIT %d OFFSET %d",
                $per_page, $offset
            ));
        } else {
            // Period-based ranking from daily stats
            $days = ['day' => 1, 'week' => 7, 'month' => 30, 'year' => 365];
            $days_ago = $days[$period] ?? 7;
            $stat_col = $metric === 'favorites' ? 'daily_favorites' : ($metric === 'ratings' ? 'daily_ratings' : 'daily_views');

            $where = $wpdb->prepare("ds.stat_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)", $days_ago);
            if ($category_id) {
                $where .= $wpdb->prepare(" AND s.id IN (SELECT story_id FROM " . self::table('hdk_story_categories') . " WHERE category_id = %d)", $category_id);
            }

            $offset = ($page - 1) * $per_page;
            $total = $wpdb->get_var("SELECT COUNT(DISTINCT ds.story_id) FROM $stats_table ds JOIN $stories_table s ON ds.story_id = s.id WHERE $where");
            $stories = $wpdb->get_results($wpdb->prepare(
                "SELECT s.*, SUM(ds.$stat_col) as period_total
                 FROM $stats_table ds JOIN $stories_table s ON ds.story_id = s.id
                 WHERE $where GROUP BY ds.story_id ORDER BY period_total DESC LIMIT %d OFFSET %d",
                $per_page, $offset
            ));
        }

        foreach ($stories as $story) {
            $story->author_name = self::get_author_name($story->author_id);
            $story->chapter_count = (int)$story->total_chapters;
        }

        // Bulk fetch max chapter prices
        if (!empty($stories)) {
            $story_ids = array_map(function($s) { return $s->id; }, $stories);
            $ids_list = implode(',', array_map('intval', $story_ids));
            $chap_table = self::table('hdk_chapters');
            $prices = $wpdb->get_results(
                "SELECT story_id, MAX(price) as max_price FROM $chap_table WHERE story_id IN ($ids_list) GROUP BY story_id"
            );
            $price_map = [];
            foreach ($prices as $p) $price_map[$p->story_id] = (int)$p->max_price;
            foreach ($stories as $story) {
                if (empty($story->chapter_price) && isset($price_map[$story->id]) && $price_map[$story->id] > 0) {
                    $story->chapter_price = $price_map[$story->id];
                }
            }
        }

        return ['stories' => $stories, 'total' => (int)$total, 'pages' => (int)ceil($total / $per_page)];
    }

    public static function log_view($story_id, $chapter_number = 0) {
        global $wpdb;
        $stories_table = self::table('hdk_stories');
        $chapters_table = self::table('hdk_chapters');
        $stats_table = self::table('hdk_daily_story_stats');

        $wpdb->query($wpdb->prepare("UPDATE $stories_table SET total_views = total_views + 1 WHERE id = %d", $story_id));
        if ($chapter_number) {
            $wpdb->query($wpdb->prepare("UPDATE $chapters_table SET views = views + 1 WHERE story_id = %d AND chapter_number = %d", $story_id, $chapter_number));
        }
        $today = current_time('Y-m-d');
        $wpdb->query($wpdb->prepare(
            "INSERT INTO $stats_table (story_id, stat_date, daily_views) VALUES (%d, %s, 1)
             ON DUPLICATE KEY UPDATE daily_views = daily_views + 1",
            $story_id, $today
        ));
    }

    public static function seed_demo_data() {
        global $wpdb;
        $now = current_time('mysql');

        // Seed authors
        $authors = [
            ['name' => 'Nguyễn Nhật Ánh', 'slug' => 'nguyen-nhat-anh', 'bio' => 'Nhà văn nổi tiếng Việt Nam'],
            ['name' => 'Tô Hoài', 'slug' => 'to-hoai', 'bio' => 'Nhà văn Việt Nam với nhiều tác phẩm kinh điển'],
            ['name' => 'Nam Cao', 'slug' => 'nam-cao', 'bio' => 'Cây bút hiện thực xuất sắc'],
            ['name' => 'Vũ Trọng Phụng', 'slug' => 'vu-trong-phung', 'bio' => 'Ông vua phóng sự đất Bắc'],
            ['name' => 'Ngô Tất Tố', 'slug' => 'ngo-tat-to', 'bio' => 'Nhà văn, nhà báo nổi tiếng'],
        ];
        foreach ($authors as $a) {
            $wpdb->insert(self::table('hdk_authors'), array_merge($a, ['created_at' => $now, 'updated_at' => $now]));
        }

        // Seed categories
        $categories = [
            ['name' => 'Tiểu Thuyết', 'slug' => 'tieu-thuyet', 'sort_order' => 1],
            ['name' => 'Truyện Ngắn', 'slug' => 'truyen-ngan', 'sort_order' => 2],
            ['name' => 'Ngôn Tình', 'slug' => 'ngon-tinh', 'sort_order' => 3],
            ['name' => 'Kiếm Hiệp', 'slug' => 'kiem-hiep', 'sort_order' => 4],
            ['name' => 'Trinh Thám', 'slug' => 'trinh-tham', 'sort_order' => 5],
            ['name' => 'Kinh Dị', 'slug' => 'kinh-di', 'sort_order' => 6],
            ['name' => 'Hài Hước', 'slug' => 'hai-huoc', 'sort_order' => 7],
            ['name' => 'Xuyên Không', 'slug' => 'xuyen-khong', 'sort_order' => 8],
            ['name' => 'Đam Mỹ', 'slug' => 'dam-my', 'sort_order' => 9],
            ['name' => 'Light Novel', 'slug' => 'light-novel', 'sort_order' => 10],
        ];
        foreach ($categories as $c) {
            $wpdb->insert(self::table('hdk_categories'), array_merge($c, ['created_at' => $now, 'updated_at' => $now]));
        }

        // Seed 30 stories
        $story_titles = [
            'Cánh Đồng Bất Tận', 'Mắt Biếc', 'Tôi Thấy Hoa Vàng Trên Cỏ Xanh',
            'Dế Mèn Phiêu Lưu Ký', 'Số Đỏ', 'Tắt Đèn', 'Chí Phèo',
            'Lão Hạc', 'Vợ Nhặt', 'Chiếc Lược Ngà', 'Những Ngày Thơ Ấu',
            'Bỉ Vỏ', 'Đất Rừng Phương Nam', 'Tuổi Thơ Dữ Dội',
            'Bến Không Chồng', 'Mùa Hè Năm Ấy', 'Cô Gái Đến Từ Hôm Qua',
            'Đi Qua Hoa Cúc', 'Chuyện Tình Nàng Hề', 'Hồn Ma Đêm Giáng Sinh',
            'Bí Mật Của Gió', 'Ánh Trăng Không Màu', 'Mưa Trên Cánh Bướm',
            'Ngày Xưa Có Một Chuyện Tình', 'Đảo Mộng Mơ', 'Thiên Thần Nhỏ Của Tôi',
            'Kẻ Săn Đuổi Ánh Sáng', 'Lời Nguyền Hoa Hồng', 'Bản Tình Ca Mùa Đông',
            'Dấu Chân Trên Cát',
        ];

        foreach ($story_titles as $i => $title) {
            $author_id = ($i % 5) + 1;
            $status = $i % 3 === 0 ? 'completed' : ($i % 3 === 1 ? 'ongoing' : 'ongoing');
            $views = rand(1000, 1000000);
            $chaps = $status === 'completed' ? rand(20, 200) : rand(5, 50);
            $slug = sanitize_title($title);

            $wpdb->insert(self::table('hdk_stories'), [
                'title' => $title,
                'slug' => $slug,
                'author_id' => $author_id,
                'cover_url' => 'https://picsum.photos/seed/' . $slug . '/300/400',
                'summary' => "Đây là phần tóm tắt của truyện \"$title\". Một câu chuyện hấp dẫn với nhiều tình tiết ly kỳ và cảm động.",
                'status' => $status,
                'is_free' => $i % 4 === 0 ? 1 : 0,
                'total_chapters' => $chaps,
                'total_views' => $views,
                'average_rating' => round(rand(30, 50) / 10, 1),
                'total_ratings' => rand(10, 5000),
                'total_favorites' => rand(0, 10000),
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $story_id = $wpdb->insert_id;

            // Assign random 2-3 categories
            $cat_ids = array_rand(array_flip(range(1, 10)), rand(2, 3));
            $cat_ids = (array) $cat_ids;
            foreach ($cat_ids as $cid) {
                $wpdb->insert(self::table('hdk_story_categories'), ['story_id' => $story_id, 'category_id' => (int)$cid]);
            }

            // Seed chapters
            for ($chap = 1; $chap <= min($chaps, 5); $chap++) {
                $content = "<p>Chương $chap của truyện \"$title\".</p>";
                $content .= "<p>" . self::generate_lorem(rand(500, 2000)) . "</p>";
                $wpdb->insert(self::table('hdk_chapters'), [
                    'story_id' => $story_id,
                    'chapter_number' => $chap,
                    'title' => "Chương $chap: " . self::random_chapter_title(),
                    'content' => $content,
                    'word_count' => str_word_count(strip_tags($content)),
                    'views' => rand(100, 5000),
                    'status' => 'published',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Update category counts
        foreach (range(1, 10) as $cid) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM " . self::table('hdk_story_categories') . " WHERE category_id = %d", $cid
            ));
            $wpdb->update(self::table('hdk_categories'), ['story_count' => $count], ['id' => $cid]);
        }

        // Update author counts
        foreach (range(1, 5) as $aid) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM " . self::table('hdk_stories') . " WHERE author_id = %d", $aid
            ));
            $wpdb->update(self::table('hdk_authors'), ['story_count' => $count], ['id' => $aid]);
        }
    }

    private static function generate_lorem($words = 100) {
        $lorem = "Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua ut enim ad minim veniam quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur excepteur sint occaecat cupidatat non proident sunt in culpa qui officia deserunt mollit anim id est laborum sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium totam rem aperiam eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt neque porro quisquam est qui dolorem ipsum quia dolor sit amet consectetur adipisci velit sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem";
        $words_arr = explode(' ', $lorem);
        shuffle($words_arr);
        return implode(' ', array_slice($words_arr, 0, $words));
    }

    private static function random_chapter_title() {
        $titles = [
            'Khởi Đầu', 'Bí Mật Được Tiết Lộ', 'Cuộc Gặp Gỡ Định Mệnh',
            'Âm Mưu Trong Bóng Tối', 'Ánh Sáng Cuối Đường Hầm',
            'Người Lạ Bí Ẩn', 'Thử Thách Mới', 'Đối Mặt Với Quá Khứ',
            'Cuộc Chiến Sinh Tử', 'Khoảnh Khắc Quyết Định',
        ];
        return $titles[array_rand($titles)];
    }

    public static function get_favorites($user_id, $page = 1, $per_page = 12) {
        global $wpdb;
        $fav_table = self::table('hdk_favorites');
        $story_table = self::table('hdk_stories');
        $offset = ($page - 1) * $per_page;

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $fav_table f JOIN $story_table s ON f.story_id = s.id WHERE f.user_id = %d",
            $user_id
        ));

        $stories = $wpdb->get_results($wpdb->prepare(
            "SELECT s.* FROM $fav_table f JOIN $story_table s ON f.story_id = s.id
             WHERE f.user_id = %d ORDER BY f.created_at DESC LIMIT %d OFFSET %d",
            $user_id, $per_page, $offset
        ));

        foreach ($stories as $story) {
            $story->author_name = self::get_author_name($story->author_id);
            $story->categories = self::get_story_categories($story->id);
            $story->chapter_count = (int)$story->total_chapters;
        }

        return ['stories' => $stories, 'total' => (int)$total, 'pages' => (int)ceil($total / $per_page)];
    }

    public static function get_reading_stories($user_id) {
        global $wpdb;
        $progress_table = self::table('hdk_reading_progress');
        $story_table = self::table('hdk_stories');

        $stories = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, p.chapter_number as current_chapter, p.scroll_percent
             FROM $progress_table p JOIN $story_table s ON p.story_id = s.id
             WHERE p.user_id = %d ORDER BY p.updated_at DESC",
            $user_id
        ));

        foreach ($stories as $story) {
            $story->author_name = self::get_author_name($story->author_id);
            $story->categories = self::get_story_categories($story->id);
            $story->chapter_count = (int)$story->total_chapters;
        }

        return $stories;
    }

    public static function get_purchased_stories($user_id, $page = 1, $per_page = 12) {
        global $wpdb;
        $purch_table = self::table('hdk_purchased_chapters');
        $story_table = self::table('hdk_stories');
        $offset = ($page - 1) * $per_page;

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.story_id) FROM $purch_table p JOIN $story_table s ON p.story_id = s.id WHERE p.user_id = %d",
            $user_id
        ));

        $stories = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, MAX(p.created_at) as purchased_at
             FROM $purch_table p JOIN $story_table s ON p.story_id = s.id
             WHERE p.user_id = %d GROUP BY p.story_id ORDER BY purchased_at DESC LIMIT %d OFFSET %d",
            $user_id, $per_page, $offset
        ));

        foreach ($stories as $story) {
            $story->author_name = self::get_author_name($story->author_id);
            $story->categories = self::get_story_categories($story->id);
            $story->chapter_count = (int)$story->total_chapters;
        }

        return ['stories' => $stories, 'total' => (int)$total, 'pages' => (int)ceil($total / $per_page)];
    }

    public static function get_reading_history($user_id, $page = 1, $per_page = 20) {
        global $wpdb;
        $hist_table = self::table('hdk_reading_history');
        $story_table = self::table('hdk_stories');
        $offset = ($page - 1) * $per_page;

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $hist_table WHERE user_id = %d", $user_id
        ));

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT h.*, s.title, s.slug FROM $hist_table h
             JOIN $story_table s ON h.story_id = s.id
             WHERE h.user_id = %d ORDER BY h.created_at DESC LIMIT %d OFFSET %d",
            $user_id, $per_page, $offset
        ));

        return ['rows' => $rows, 'total' => (int)$total, 'pages' => (int)ceil($total / $per_page)];
    }

    public static function log_reading_history($user_id, $story_id, $chapter_number) {
        global $wpdb;
        $table = self::table('hdk_reading_history');

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND story_id = %d AND chapter_number = %d LIMIT 1",
            $user_id, $story_id, $chapter_number
        ));

        if (!$exists) {
            $wpdb->insert($table, [
                'user_id' => $user_id,
                'story_id' => $story_id,
                'chapter_number' => $chapter_number,
                'created_at' => current_time('mysql'),
            ]);
        }
    }

    public static function get_user_purchased_count($user_id) {
        global $wpdb;
        return (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT story_id) FROM " . self::table('hdk_purchased_chapters') . " WHERE user_id = %d",
            $user_id
        ));
    }

    public static function log_credit_transaction($user_id, $type, $credits, $source_type, $source_id, $note, $status = 'completed') {
        global $wpdb;
        $credit_table = self::table('hdk_user_credits');
        $trans_table = self::table('hdk_credit_transactions');

        $current = (int)$wpdb->get_var($wpdb->prepare("SELECT credits FROM $credit_table WHERE user_id = %d", $user_id));
        if ($current === null && !$wpdb->last_error) {
            $wpdb->insert($credit_table, ['user_id' => $user_id, 'credits' => 0]);
            $current = 0;
        }
        $balance_after = $current + $credits;

        $wpdb->insert($trans_table, [
            'user_id' => $user_id,
            'type' => $type,
            'credits' => $credits,
            'balance_after' => $balance_after,
            'source_type' => $source_type,
            'source_id' => $source_id,
            'note' => $note,
            'status' => $status,
            'created_at' => current_time('mysql'),
        ]);
    }

    public static function get_credit_transactions($user_id, $page = 1, $per_page = 20) {
        global $wpdb;
        $table = self::table('hdk_credit_transactions');
        $offset = ($page - 1) * $per_page;

        $total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE user_id = %d", $user_id));
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $user_id, $per_page, $offset
        ));

        return ['rows' => $rows, 'total' => (int)$total, 'pages' => (int)ceil($total / $per_page)];
    }

    public static function get_credit_packages($active_only = false) {
        global $wpdb;
        $table = self::table('hdk_credit_packages');
        $where = $active_only ? "WHERE is_active = 1" : "";
        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY sort_order ASC");
    }

    public static function create_credit_package($data) {
        global $wpdb;
        $wpdb->insert(self::table('hdk_credit_packages'), array_merge($data, [
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]));
        return $wpdb->insert_id;
    }

    public static function update_credit_package($id, $data) {
        global $wpdb;
        $data['updated_at'] = current_time('mysql');
        $wpdb->update(self::table('hdk_credit_packages'), $data, ['id' => $id]);
    }

    public static function delete_credit_package($id) {
        global $wpdb;
        $wpdb->delete(self::table('hdk_credit_packages'), ['id' => $id]);
    }

    public static function get_user_credit_stats($user_id) {
        global $wpdb;
        $table = self::table('hdk_user_credits');
        $row = $wpdb->get_row($wpdb->prepare("SELECT credits, total_earned, total_spent FROM $table WHERE user_id = %d", $user_id));
        if (!$row) {
            $wpdb->insert($table, ['user_id' => $user_id, 'credits' => 0]);
            return ['credits' => 0, 'total_earned' => 0, 'total_spent' => 0];
        }
        return ['credits' => (int)$row->credits, 'total_earned' => (int)$row->total_earned, 'total_spent' => (int)$row->total_spent];
    }

    public static function claim_daily_credits($user_id) {
        global $wpdb;
        $credit_table = self::table('hdk_user_credits');
        $daily_amount = (int)get_option('hdk_daily_credits', 10);

        $row = $wpdb->get_row($wpdb->prepare("SELECT credits, total_earned, last_daily_at FROM $credit_table WHERE user_id = %d", $user_id));

        $today = current_time('Y-m-d');
        if ($row && $row->last_daily_at) {
            $last_date = date('Y-m-d', strtotime($row->last_daily_at));
            if ($last_date === $today) {
                return ['success' => false, 'message' => 'Bạn đã điểm danh hôm nay rồi!'];
            }
        }

        $current = $row ? (int)$row->credits : 0;
        $new_balance = $current + $daily_amount;

        if ($row) {
            $wpdb->update($credit_table, [
                'credits' => $new_balance,
                'total_earned' => (int)$row->total_earned + $daily_amount,
                'last_daily_at' => current_time('mysql'),
            ], ['user_id' => $user_id]);
        } else {
            $wpdb->insert($credit_table, [
                'user_id' => $user_id,
                'credits' => $daily_amount,
                'total_earned' => $daily_amount,
                'last_daily_at' => current_time('mysql'),
            ]);
        }

        self::log_credit_transaction($user_id, 'daily', $daily_amount, 'daily_login', 0, 'Điểm danh hàng ngày +' . $daily_amount . ' hạt');

        return ['success' => true, 'credits_earned' => $daily_amount, 'balance' => $new_balance];
    }

    public static function get_all_user_credits($search = '', $page = 1, $per_page = 20) {
        global $wpdb;
        $credit_table = self::table('hdk_user_credits');
        $user_table = $wpdb->users;
        $offset = ($page - 1) * $per_page;

        $where = "1=1";
        if ($search) {
            $s = '%' . $wpdb->esc_like($search) . '%';
            $where .= $wpdb->prepare(" AND u.user_login LIKE %s", $s);
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $credit_table c JOIN $user_table u ON c.user_id = u.ID WHERE $where");
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, u.user_login, u.display_name FROM $credit_table c
             JOIN $user_table u ON c.user_id = u.ID WHERE $where ORDER BY c.credits DESC LIMIT %d OFFSET %d",
            $per_page, $offset
        ));

        return ['rows' => $rows, 'total' => (int)$total, 'pages' => (int)ceil($total / $per_page)];
    }

    public static function get_all_transactions($filters = [], $page = 1, $per_page = 50) {
        global $wpdb;
        $trans_table = self::table('hdk_credit_transactions');
        $user_table = $wpdb->users;
        $offset = ($page - 1) * $per_page;

        $where = ["1=1"];
        if (!empty($filters['type'])) {
            $where[] = $wpdb->prepare("t.type = %s", $filters['type']);
        }
        if (!empty($filters['user'])) {
            $s = '%' . $wpdb->esc_like($filters['user']) . '%';
            $where[] = $wpdb->prepare("u.user_login LIKE %s", $s);
        }

        $where_sql = implode(' AND ', $where);
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $trans_table t JOIN $user_table u ON t.user_id = u.ID WHERE $where_sql");
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, u.user_login, u.display_name FROM $trans_table t
             JOIN $user_table u ON t.user_id = u.ID WHERE $where_sql ORDER BY t.created_at DESC LIMIT %d OFFSET %d",
            $per_page, $offset
        ));

        return ['rows' => $rows, 'total' => (int)$total, 'pages' => (int)ceil($total / $per_page)];
    }

    public static function get_chapters_toc($story_id) {
        global $wpdb;
        $table = self::table('hdk_chapters');
        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, chapter_number, title, status, word_count FROM $table
             WHERE story_id = %d AND status IN ('published','scheduled') ORDER BY chapter_number ASC",
            $story_id
        ));
    }

    public static function get_reader_prefs($user_id) {
        global $wpdb;
        $table = self::table('hdk_user_reader_prefs');
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d", $user_id));
    }

    public static function save_reader_prefs($user_id, $data) {
        global $wpdb;
        $table = self::table('hdk_user_reader_prefs');
        $existing = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM $table WHERE user_id = %d", $user_id));
        $data['updated_at'] = current_time('mysql');
        if ($existing) {
            $wpdb->update($table, $data, ['user_id' => $user_id]);
        } else {
            $data['user_id'] = $user_id;
            $wpdb->insert($table, $data);
        }
    }

    public static function create_notification($user_id, $type, $title, $message, $link = '') {
        global $wpdb;
        $table = self::table('hdk_notifications');
        $wpdb->insert($table, [
            'user_id' => $user_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'is_read' => 0,
            'created_at' => current_time('mysql'),
        ]);
        return $wpdb->insert_id;
    }

    public static function get_notifications($user_id, $page = 1, $per_page = 20) {
        global $wpdb;
        $table = self::table('hdk_notifications');
        $offset = ($page - 1) * $per_page;

        $total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE user_id = %d", $user_id));
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $user_id, $per_page, $offset
        ));

        return ['rows' => $rows, 'total' => (int)$total, 'pages' => (int)ceil($total / $per_page)];
    }

    public static function get_unread_notification_count($user_id) {
        global $wpdb;
        return (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::table('hdk_notifications') . " WHERE user_id = %d AND is_read = 0",
            $user_id
        ));
    }

    public static function mark_notifications_read($user_id, $notification_id = 0) {
        global $wpdb;
        $table = self::table('hdk_notifications');
        if ($notification_id) {
            $wpdb->update($table, ['is_read' => 1], ['id' => $notification_id, 'user_id' => $user_id]);
        } else {
            $wpdb->update($table, ['is_read' => 1], ['user_id' => $user_id, 'is_read' => 0]);
        }
    }

    public static function create_report($user_id, $story_id, $chapter_number, $type, $note) {
        global $wpdb;
        $wpdb->insert(self::table('hdk_chapter_reports'), [
            'user_id' => $user_id, 'story_id' => $story_id,
            'chapter_number' => $chapter_number, 'report_type' => $type,
            'note' => $note, 'created_at' => current_time('mysql'),
        ]);
        return $wpdb->insert_id;
    }

    public static function get_reports($filters = [], $page = 1, $per_page = 20) {
        global $wpdb;
        $table = self::table('hdk_chapter_reports');
        $story_table = self::table('hdk_stories');
        $user_table = $wpdb->users;
        $offset = ($page - 1) * $per_page;

        $where = ["1=1"];
        if (!empty($filters['status'])) $where[] = $wpdb->prepare("r.status = %s", $filters['status']);
        if (!empty($filters['type'])) $where[] = $wpdb->prepare("r.report_type = %s", $filters['type']);

        $where_sql = implode(' AND ', $where);
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table r WHERE $where_sql");
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, s.title as story_title, s.slug as story_slug, u.display_name
             FROM $table r JOIN $story_table s ON r.story_id = s.id
             JOIN $user_table u ON r.user_id = u.ID
             WHERE $where_sql ORDER BY r.created_at DESC LIMIT %d OFFSET %d",
            $per_page, $offset
        ));
        return ['rows' => $rows, 'total' => (int)$total, 'pages' => (int)ceil($total / $per_page)];
    }

    public static function update_report_status($id, $status) {
        global $wpdb;
        $wpdb->update(self::table('hdk_chapter_reports'), ['status' => $status], ['id' => $id]);
    }

    public static function get_all_comments($filters = [], $page = 1, $per_page = 20) {
        global $wpdb;
        $offset = ($page - 1) * $per_page;

        $where = ["c.comment_approved != 'trash'"];
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $where[] = $wpdb->prepare("c.comment_approved = %s", $filters['status'] === 'approved' ? '1' : '0');
        }

        $where_sql = implode(' AND ', $where);

        $total = $wpdb->get_var(
            "SELECT COUNT(*) FROM $wpdb->comments c
             JOIN $wpdb->commentmeta cm ON c.comment_ID = cm.comment_id AND cm.meta_key = 'hdk_story_id'
             WHERE $where_sql"
        );

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, u.display_name as user_name,
                    (SELECT meta_value FROM $wpdb->commentmeta WHERE comment_id = c.comment_ID AND meta_key = 'hdk_story_id') as story_id,
                    (SELECT meta_value FROM $wpdb->commentmeta WHERE comment_id = c.comment_ID AND meta_key = 'hdk_chapter_number') as chapter_number
             FROM $wpdb->comments c
             JOIN $wpdb->users u ON c.user_id = u.ID
             JOIN $wpdb->commentmeta cm ON c.comment_ID = cm.comment_id AND cm.meta_key = 'hdk_story_id'
             WHERE $where_sql
             ORDER BY c.comment_date DESC LIMIT %d OFFSET %d",
            $per_page, $offset
        ));

        if (!empty($rows)) {
            $story_ids = array_unique(array_map(function($r) { return (int)$r->story_id; }, $rows));
            $ids_list = implode(',', $story_ids);
            $stories = $wpdb->get_results("SELECT id, title, slug FROM " . self::table('hdk_stories') . " WHERE id IN ($ids_list)");
            $story_map = [];
            foreach ($stories as $s) $story_map[$s->id] = $s;
            foreach ($rows as $row) {
                $sid = (int)$row->story_id;
                $row->story_title = isset($story_map[$sid]) ? $story_map[$sid]->title : '';
                $row->story_slug = isset($story_map[$sid]) ? $story_map[$sid]->slug : '';
            }
        }

        return ['rows' => $rows, 'total' => (int)$total, 'pages' => (int)ceil($total / $per_page)];
    }
}
