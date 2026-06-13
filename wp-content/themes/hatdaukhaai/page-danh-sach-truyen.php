<?php
/**
 * Template Name: Danh Sách Truyện
 * Page: Listing all stories with filters
 */

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$status = $_GET['status'] ?? '';
$category = (int)($_GET['category'] ?? 0);
$is_free = isset($_GET['free']) ? 1 : null;
$search = sanitize_text_field($_GET['s'] ?? '');
$orderby = sanitize_text_field($_GET['orderby'] ?? 'updated_at');
$per_page = 20;

$args = ['page' => $page, 'per_page' => $per_page, 'orderby' => $orderby];
if ($status) $args['status'] = $status;
if ($category) $args['category_id'] = $category;
if ($is_free !== null) $args['is_free'] = 1;
if ($search) $args['search'] = $search;

$result = HDK_DB::get_stories($args);

// Get all categories for filter
global $wpdb;
$categories = $wpdb->get_results("SELECT * FROM " . HDK_DB::table('hdk_categories') . " ORDER BY sort_order");

get_header();
?>

<div class="container" style="padding-top:24px;">
    <div class="section-header">
        <h1 class="section-title">Danh sách truyện</h1>
        <span style="color:var(--color-text-muted);"><?php echo $result['total']; ?> truyện</span>
    </div>

    <!-- Filters -->
    <div style="background:var(--color-bg);border-radius:var(--radius-md);padding:16px;border:1px solid var(--color-border);margin-bottom:20px;display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
        <input type="text" id="search-input" placeholder="Tìm truyện..." value="<?php echo esc_attr($search); ?>"
               style="flex:1;min-width:200px;padding:8px 16px;border:2px solid var(--color-border);border-radius:var(--radius-pill);font-family:var(--font-family);min-height:var(--touch-target);">
        <select id="status-filter" style="padding:8px 16px;border:2px solid var(--color-border);border-radius:var(--radius-pill);font-family:var(--font-family);min-height:var(--touch-target);">
            <option value="">Tất cả trạng thái</option>
            <option value="ongoing" <?php selected($status, 'ongoing'); ?>>Đang ra</option>
            <option value="completed" <?php selected($status, 'completed'); ?>>Hoàn thành</option>
            <option value="dropped" <?php selected($status, 'dropped'); ?>>Ngừng</option>
        </select>
        <select id="category-filter" style="padding:8px 16px;border:2px solid var(--color-border);border-radius:var(--radius-pill);font-family:var(--font-family);min-height:var(--touch-target);">
            <option value="0">Tất cả thể loại</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat->id; ?>" <?php selected($category, $cat->id); ?>><?php echo esc_html($cat->name); ?></option>
            <?php endforeach; ?>
        </select>
        <select id="orderby-filter" style="padding:8px 16px;border:2px solid var(--color-border);border-radius:var(--radius-pill);font-family:var(--font-family);min-height:var(--touch-target);">
            <option value="updated_at" <?php selected($orderby, 'updated_at'); ?>>Mới cập nhật</option>
            <option value="total_views" <?php selected($orderby, 'total_views'); ?>>Lượt xem</option>
            <option value="average_rating" <?php selected($orderby, 'average_rating'); ?>>Đánh giá</option>
            <option value="total_favorites" <?php selected($orderby, 'total_favorites'); ?>>Yêu thích</option>
            <option value="published_at" <?php selected($orderby, 'published_at'); ?>>Mới xuất bản</option>
            <option value="title" <?php selected($orderby, 'title'); ?>>Tên A-Z</option>
        </select>
        <button class="btn btn-primary btn-sm" onclick="applyFilters()">Lọc</button>
    </div>

    <?php if (!empty($result['stories'])): ?>
        <div class="grid grid-4">
            <?php foreach ($result['stories'] as $i => $story): ?>
                <?php hdk_get_story_card($story, $i); ?>
            <?php endforeach; ?>
        </div>
        <?php hdk_get_pagination($result['pages'], $page); ?>
    <?php else: ?>
        <div style="text-align:center;padding:60px 0;">
            <p style="font-size:var(--font-size-2xl);margin-bottom:12px;">🔍</p>
            <p style="font-size:var(--font-size-lg);color:var(--color-text-muted);">Không tìm thấy truyện nào.</p>
        </div>
    <?php endif; ?>
</div>

<script>
function applyFilters() {
    var params = new URLSearchParams();
    var s = document.getElementById('search-input').value;
    var status = document.getElementById('status-filter').value;
    var cat = document.getElementById('category-filter').value;
    var order = document.getElementById('orderby-filter').value;
    if (s) params.set('s', s);
    if (status) params.set('status', status);
    if (cat && cat !== '0') params.set('category', cat);
    if (order && order !== 'updated_at') params.set('orderby', order);
    window.location.href = '?' + params.toString();
}
</script>

<?php get_footer(); ?>
