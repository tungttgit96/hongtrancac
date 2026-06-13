# Wallet & Credits System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build complete wallet system: transaction logging, admin credit management, credit packages, daily login reward, wallet tab on account page.

**Architecture:** New `hdk_credit_transactions` (audit log) + `hdk_credit_packages` (configurable bundles). Modify purchase endpoints to log transactions. Add 3 admin pages + 1 account tab. Daily login reward via REST endpoint.

**Tech Stack:** WordPress, PHP, MySQL, HDK Core plugin, hatdaukhaai theme

---

## File Structure

| File | Action | Responsibility |
|------|--------|----------------|
| `plugins/hdk-core/includes/class-schema.php` | Modify | 2 new tables + 1 column migration |
| `plugins/hdk-core/includes/class-db.php` | Modify | 10 new query methods |
| `plugins/hdk-core/includes/class-rest-api.php` | Modify | Log purchases, daily-claim, list endpoints |
| `plugins/hdk-core/includes/class-admin.php` | Modify | 3 admin pages + form handlers |
| `themes/hatdaukhaai/page-tai-khoan.php` | Modify | Add wallet tab |
| `themes/hatdaukhaai/assets/js/main.js` | Modify | Modal + daily claim + wallet UI |
| `themes/hatdaukhaai/assets/css/main.css` | Modify | Wallet styles |

---

### Task 1: Add credit schema (2 tables + 1 column)

**Files:**
- Modify: `wp-content/plugins/hdk-core/includes/class-schema.php`

- [ ] **Step 1: Add hdk_credit_transactions table**

Insert before `foreach ($sql as $query)`:

```php
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
```

- [ ] **Step 2: Add last_daily_at column to hdk_user_credits**

Add after the existing migration lines (after the `hdk_chapters` column additions):

```php
        self::add_column_if_not_exists("{$wpdb->prefix}hdk_user_credits", 'last_daily_at', "DATETIME NULL AFTER total_spent");
```

- [ ] **Step 3: Run schema migration**

```bash
"/Users/tungtt96/Library/Application Support/Herd/bin/php" -r "
define('WP_USE_THEMES', false);
require '/Users/tungtt96/Herd/hongtrancac/wp-load.php';
HDK_Schema::create_tables();
echo 'OK';
"
```
Expected: `OK`

- [ ] **Step 4: Commit**

```bash
git add wp-content/plugins/hdk-core/includes/class-schema.php
git commit -m "feat: add credit_transactions and credit_packages tables + last_daily_at column"
```

---

### Task 2: Add HDK_DB credit methods

**Files:**
- Modify: `wp-content/plugins/hdk-core/includes/class-db.php`

- [ ] **Step 1: Add 10 new static methods before class closing `}`**

```php
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

        $row = $wpdb->get_row($wpdb->prepare("SELECT credits, last_daily_at FROM $credit_table WHERE user_id = %d", $user_id));

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
                'total_earned' => $current + $daily_amount,
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
```

- [ ] **Step 2: Commit**

```bash
git add wp-content/plugins/hdk-core/includes/class-db.php
git commit -m "feat: add credit transaction, package, daily claim DB methods"
```

---

### Task 3: Update REST API - purchase logging + daily-claim + list endpoints

**Files:**
- Modify: `wp-content/plugins/hdk-core/includes/class-rest-api.php`

- [ ] **Step 1: Add transaction logging to purchase_chapter**

In `purchase_chapter()`, after the credits deduction UPDATE (after line 248) and before return, add:

```php
        // Log transaction
        HDK_DB::log_credit_transaction($user_id, 'spend', -$price, 'chapter_purchase', $story_id,
            'Mua chương ' . $chapter_number . ' - ' . $story->title);
```

Also refactor the credit deduction to use the new pattern. Replace lines 244-248:

```php
        // Deduct credits and update stats
        $wpdb->query($wpdb->prepare(
            "UPDATE " . HDK_DB::table('hdk_user_credits') . " SET credits = credits - %d, total_spent = total_spent + %d WHERE user_id = %d AND credits >= %d",
            $price, $price, $user_id, $price
        ));
```

- [ ] **Step 2: Add transaction logging to purchase_full_story**

Same pattern in `purchase_full_story()`, after the deduction (line 292) and before return, add:

```php
        // Log transaction
        HDK_DB::log_credit_transaction($user_id, 'spend', -$price, 'full_purchase', $story_id,
            'Mua full truyện - ' . $story->title);
```

Also refactor the deduction UPDATE to include `AND credits >= %d` check:

```php
        $wpdb->query($wpdb->prepare(
            "UPDATE " . HDK_DB::table('hdk_user_credits') . " SET credits = credits - %d, total_spent = total_spent + %d WHERE user_id = %d AND credits >= %d",
            $price, $price, $user_id, $price
        ));
```

- [ ] **Step 3: Add daily-claim endpoint registration**

In `init()`, add:

```php
        register_rest_route('hdk/v1', '/daily-claim', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'daily_claim'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('hdk/v1', '/me/transactions', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_transactions'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('hdk/v1', '/packages', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_packages'],
            'permission_callback' => '__return_true',
        ]);
```

- [ ] **Step 4: Add handler methods**

Before class closing `}`:

```php
    public static function daily_claim($request) {
        $user_id = get_current_user_id();
        $result = HDK_DB::claim_daily_credits($user_id);
        if (!$result['success']) {
            return new WP_Error('already_claimed', $result['message'], ['status' => 409]);
        }
        return rest_ensure_response($result);
    }

    public static function get_transactions($request) {
        $user_id = get_current_user_id();
        $page = (int)($request->get_param('page') ?? 1);
        $result = HDK_DB::get_credit_transactions($user_id, max(1, $page));
        return rest_ensure_response($result);
    }

    public static function get_packages($request) {
        $result = HDK_DB::get_credit_packages(true);
        return rest_ensure_response(['packages' => $result]);
    }
```

- [ ] **Step 5: Commit**

```bash
git add wp-content/plugins/hdk-core/includes/class-rest-api.php
git commit -m "feat: log purchase transactions + daily-claim and transaction list endpoints"
```

---

### Task 4: Add admin pages (credits, packages, transactions)

**Files:**
- Modify: `wp-content/plugins/hdk-core/includes/class-admin.php`

- [ ] **Step 1: Register admin submenu pages**

In the `init()` method (after the existing submenu registrations, around line 27), add:

```php
        add_submenu_page('hdk-stories', 'Quản lý hạt', 'Quản lý hạt', 'manage_options', 'hdk-credits', [__CLASS__, 'credits_page']);
        add_submenu_page('hdk-stories', 'Gói nạp', 'Gói nạp', 'manage_options', 'hdk-packages', [__CLASS__, 'packages_page']);
        add_submenu_page('hdk-stories', 'Lịch sử giao dịch', 'Lịch sử GD', 'manage_options', 'hdk-transactions', [__CLASS__, 'transactions_page']);
```

- [ ] **Step 2: Add form handlers**

In `handle_form_submissions()` (which runs on `admin_init`), add at the end of the method:

```php
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
            if ($current === null && !$wpdb->last_error) {
                $wpdb->insert($credit_table, ['user_id' => $uid, 'credits' => 0]);
                $current = 0;
            }
            $new_balance = max(0, $current + $amount);

            $wpdb->update($credit_table, [
                'credits' => $new_balance,
                'total_earned' => $amount > 0 ? $current + $amount : $current,
                'total_spent' => $amount < 0 ? abs($amount) : 0,
            ], ['user_id' => $uid]);

            HDK_DB::log_credit_transaction($uid, $type, $amount, 'admin', 0, $note);

            wp_redirect(admin_url('admin.php?page=hdk-credits&message=adjusted'));
            exit;
        }
```

- [ ] **Step 3: Add credits_page() method**

Add to the class:

```php
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
```

- [ ] **Step 4: Add packages_page() method**

```php
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
```

- [ ] **Step 5: Add transactions_page() method**

```php
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
```

- [ ] **Step 6: Commit**

```bash
git add wp-content/plugins/hdk-core/includes/class-admin.php
git commit -m "feat: add admin pages for credit management, packages, transactions"
```

---

### Task 5: Add wallet tab to account page

**Files:**
- Modify: `wp-content/themes/hatdaukhaai/page-tai-khoan.php`

- [ ] **Step 1: Add 'wallet' to tabs array and valid tabs**

Change lines:
```php
$valid_tabs = ['favorites', 'reading', 'purchased', 'history'];
```
To:
```php
$valid_tabs = ['favorites', 'reading', 'purchased', 'history', 'wallet'];
```

And in the `$tabs` array, add after 'history':

```php
            'wallet'    => '💎 Ví hạt',
```

- [ ] **Step 2: Add wallet case in switch**

Insert before the closing of the switch statement (before the default break or end):

```php
        case 'wallet':
            $stats = HDK_DB::get_user_credit_stats($user_id);
            $tx_data = HDK_DB::get_credit_transactions($user_id, $page, 20);
            $transactions = $tx_data['rows'];
            $packages = HDK_DB::get_credit_packages(true);
            ?>
            <div class="wallet-summary" style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap;">
                <div class="wallet-stat" style="flex:1;min-width:140px;padding:20px;background:var(--color-bg-secondary);border-radius:var(--radius-md);text-align:center;">
                    <div style="font-size:var(--font-size-sm);color:var(--color-text-muted);margin-bottom:4px;">Số dư</div>
                    <div style="font-size:var(--font-size-2xl);font-weight:700;color:var(--color-primary);">💎 <?php echo number_format($stats['credits']); ?></div>
                </div>
                <div class="wallet-stat" style="flex:1;min-width:140px;padding:20px;background:var(--color-bg-secondary);border-radius:var(--radius-md);text-align:center;">
                    <div style="font-size:var(--font-size-sm);color:var(--color-text-muted);margin-bottom:4px;">Đã nạp</div>
                    <div style="font-size:var(--font-size-xl);font-weight:600;color:var(--color-success);">📥 <?php echo number_format($stats['total_earned']); ?></div>
                </div>
                <div class="wallet-stat" style="flex:1;min-width:140px;padding:20px;background:var(--color-bg-secondary);border-radius:var(--radius-md);text-align:center;">
                    <div style="font-size:var(--font-size-sm);color:var(--color-text-muted);margin-bottom:4px;">Đã tiêu</div>
                    <div style="font-size:var(--font-size-xl);font-weight:600;color:var(--color-danger);">📤 <?php echo number_format($stats['total_spent']); ?></div>
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap;">
                <button type="button" class="btn btn-primary" onclick="document.getElementById('purchase-modal').style.display='flex'">
                    💳 Nạp hạt
                </button>
                <button type="button" class="btn btn-outline daily-claim-btn" id="daily-claim-btn" onclick="claimDaily()">
                    📅 Điểm danh +<?php echo (int)get_option('hdk_daily_credits', 10); ?> hạt
                </button>
            </div>

            <!-- Purchase Modal -->
            <div id="purchase-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:var(--color-overlay);z-index:999;align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
                <div style="background:var(--color-bg);border-radius:var(--radius-lg);padding:32px;max-width:500px;width:90%;max-height:80vh;overflow-y:auto;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                        <h2 style="margin:0;">Nạp hạt</h2>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('purchase-modal').style.display='none'">✕</button>
                    </div>
                    <?php if (empty($packages)): ?>
                        <p style="color:var(--color-text-muted);">Chưa có gói nạp nào.</p>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:12px;">
                            <?php foreach ($packages as $pkg): ?>
                                <div style="padding:16px;border:2px solid var(--color-border);border-radius:var(--radius-md);cursor:pointer;transition:border-color 0.2s;" class="package-card"
                                     onmouseover="this.style.borderColor='var(--color-primary)'" onmouseout="this.style.borderColor='var(--color-border)'">
                                    <div style="display:flex;justify-content:space-between;align-items:center;">
                                        <div>
                                            <strong style="font-size:var(--font-size-lg);"><?php echo esc_html($pkg->name); ?></strong>
                                            <?php if ($pkg->bonus_credits > 0): ?>
                                                <span style="color:var(--color-primary);font-size:var(--font-size-sm);margin-left:8px;">+<?php echo (int)$pkg->bonus_credits; ?> bonus</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="text-align:right;">
                                            <div style="font-weight:700;font-size:var(--font-size-lg);">💎 <?php echo number_format((int)$pkg->credits); ?></div>
                                            <div style="color:var(--color-text-muted);font-size:var(--font-size-sm);"><?php echo number_format((int)$pkg->price_vnd); ?> đ</div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <p style="color:var(--color-text-muted);font-size:var(--font-size-sm);text-align:center;margin-top:8px;">
                                Liên hệ admin để thanh toán và nhận hạt.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <h3 style="margin-bottom:12px;">Lịch sử giao dịch</h3>
            <?php if (empty($transactions)): ?>
                <div class="empty-state" style="text-align:center;padding:48px 0;">
                    <div style="font-size:48px;margin-bottom:16px;">💎</div>
                    <p style="color:var(--color-text-muted);">Chưa có giao dịch nào</p>
                </div>
            <?php else: ?>
                <div class="history-list" style="display:flex;flex-direction:column;gap:1px;background:var(--color-border);border-radius:var(--radius-md);overflow:hidden;">
                    <?php foreach ($transactions as $tx): ?>
                        <?php
                        $is_positive = $tx->credits >= 0;
                        $type_labels = ['earn' => 'Nạp', 'spend' => 'Tiêu', 'daily' => 'Điểm danh', 'admin_add' => 'Admin +', 'admin_deduct' => 'Admin -', 'refund' => 'Hoàn'];
                        ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:var(--color-bg);gap:12px;flex-wrap:wrap;">
                            <div>
                                <span style="color:var(--color-text-muted);font-size:var(--font-size-sm);"><?php echo $type_labels[$tx->type] ?? $tx->type; ?></span>
                                <?php if ($tx->note): ?>
                                    <span style="margin-left:8px;"><?php echo esc_html($tx->note); ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <span style="font-weight:600;color:<?php echo $is_positive ? 'var(--color-success)' : 'var(--color-danger)'; ?>;white-space:nowrap;">
                                    <?php echo $is_positive ? '+' : ''; ?><?php echo number_format((int)$tx->credits); ?>
                                </span>
                                <span style="color:var(--color-text-muted);font-size:var(--font-size-sm);white-space:nowrap;">
                                    <?php echo mysql2date('H:i d/m/Y', $tx->created_at); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php hdk_get_pagination($tx_data['pages'], $page); ?>
            <?php endif; ?>
            <?php break;
```

- [ ] **Step 3: Commit**

```bash
git add wp-content/themes/hatdaukhaai/page-tai-khoan.php
git commit -m "feat: add wallet tab with transaction history and purchase modal"
```

---

### Task 6: Add wallet CSS and JS

**Files:**
- Modify: `wp-content/themes/hatdaukhaai/assets/css/main.css`
- Modify: `wp-content/themes/hatdaukhaai/assets/js/main.js`

- [ ] **Step 1: Append wallet CSS to main.css**

```css
/* ===== Wallet Tab ===== */
.wallet-summary .wallet-stat {
    transition: transform 0.2s;
}

.wallet-stat:hover {
    transform: translateY(-2px);
}

.package-card:hover {
    border-color: var(--color-primary) !important;
}

.daily-claim-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

#purchase-modal > div {
    animation: modalIn 0.2s ease;
}

@keyframes modalIn {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

@media (max-width: 768px) {
    .wallet-summary {
        flex-direction: column;
    }
}
```

- [ ] **Step 2: Add daily claim JS to main.js**

Insert before `})();`:

```js
    // ===== Daily Claim =====
    window.claimDaily = function() {
        var btn = document.getElementById('daily-claim-btn');
        if (!btn) return;

        btn.disabled = true;
        btn.textContent = 'Đang xử lý…';

        fetch('/wp-json/hdk/v1/daily-claim', { method: 'POST' })
            .then(function(r) { return r.json().then(function(d) { return {status: r.status, data: d}; }); })
            .then(function(result) {
                if (result.status === 200 && result.data.success) {
                    btn.textContent = 'Đã nhận +' + result.data.credits_earned + ' hạt!';
                    btn.style.background = 'var(--color-success)';
                    btn.style.color = '#fff';
                    btn.style.borderColor = 'var(--color-success)';
                    setTimeout(function() { location.reload(); }, 1500);
                } else if (result.status === 409) {
                    btn.textContent = 'Đã điểm danh hôm nay';
                    btn.disabled = true;
                    btn.style.opacity = '0.5';
                } else {
                    btn.textContent = 'Lỗi, thử lại';
                    btn.disabled = false;
                }
            })
            .catch(function() {
                window.location.href = '/wp-login.php';
            });
    };
```

- [ ] **Step 3: Commit**

```bash
git add wp-content/themes/hatdaukhaai/assets/css/main.css wp-content/themes/hatdaukhaai/assets/js/main.js
git commit -m "feat: add wallet tab CSS and daily claim JS"
```

---

### Task 7: Sync to Herd and verify

- [ ] **Step 1: Sync files**

```bash
cp -r /Users/tungtt96/code/truyen/wp-content/themes/hatdaukhaai/* /Users/tungtt96/Herd/hongtrancac/wp-content/themes/hatdaukhaai/
cp -r /Users/tungtt96/code/truyen/wp-content/plugins/hdk-core/* /Users/tungtt96/Herd/hongtrancac/wp-content/plugins/hdk-core/
```

- [ ] **Step 2: Run schema migration**

```bash
"/Users/tungtt96/Library/Application Support/Herd/bin/php" -r "
define('WP_USE_THEMES', false);
require '/Users/tungtt96/Herd/hongtrancac/wp-load.php';
HDK_Schema::create_tables();
echo 'OK';
"
```
Expected: `OK`

- [ ] **Step 3: Verify wallet tab loads**

```bash
curl -sk https://hongtrancac.test/wp-login.php -c /tmp/cookies.txt -d "log=admin&pwd=admin123&wp-submit=Log+In" -o /dev/null
curl -sk "https://hongtrancac.test/tai-khoan/?tab=wallet" -b /tmp/cookies.txt -w "HTTP %{http_code}" -o /tmp/wallet.html
grep -c "Số dư\|Nạp hạt\|Điểm danh\|Lịch sử giao dịch" /tmp/wallet.html
```
Expected: `HTTP 200` and 4 matches

- [ ] **Step 4: Verify admin pages**

```bash
curl -sk "https://hongtrancac.test/wp-admin/admin.php?page=hdk-credits" -b /tmp/cookies.txt -w "HTTP %{http_code}" -o /dev/null
curl -sk "https://hongtrancac.test/wp-admin/admin.php?page=hdk-packages" -b /tmp/cookies.txt -w "HTTP %{http_code}" -o /dev/null
curl -sk "https://hongtrancac.test/wp-admin/admin.php?page=hdk-transactions" -b /tmp/cookies.txt -w "HTTP %{http_code}" -o /dev/null
```
Expected: All `HTTP 200`

- [ ] **Step 5: Verify daily-claim endpoint**

```bash
curl -sk "https://hongtrancac.test/wp-json/hdk/v1/daily-claim" -b /tmp/cookies.txt -X POST -w "HTTP %{http_code}" -o /tmp/claim.json
python3 -m json.tool /tmp/claim.json 2>/dev/null | head -5
```
Expected: JSON with `success: true` or `already_claimed` (409)

- [ ] **Step 6: Open in browser**

```bash
"/Users/tungtt96/Library/Application Support/Herd/bin/herd" open hongtrancac
```
