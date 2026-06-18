<?php
/**
 * Template Name: Danh Sách Truyện
 * Page: Listing all stories with filters
 */

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$status = $_GET['status'] ?? '';
$category = (int)($_GET['category'] ?? 0);
$is_free = isset($_GET['free']) ? 1 : null;
$has_audio = isset($_GET['audio']) ? 1 : null;
$search = sanitize_text_field($_GET['keyword'] ?? '');
$orderby = sanitize_text_field($_GET['orderby'] ?? 'updated_at');
$per_page = 20;

$args = ['page' => $page, 'per_page' => $per_page, 'orderby' => $orderby];
if ($status) $args['status'] = $status;
if ($category) $args['category_id'] = $category;
if ($is_free !== null) $args['is_free'] = 1;
if ($has_audio !== null) $args['has_audio'] = 1;
if ($search) $args['search'] = $search;

$result = HDK_DB::get_stories($args);

// Get all categories for filter
global $wpdb;
$categories = $wpdb->get_results("SELECT * FROM " . HDK_DB::table('hdk_categories') . " ORDER BY sort_order");

get_header();
?>

<div class="container page-shell">
    <?php if ($search): ?>
    <div class="search-page-header">
        <h1 class="section-title">Kết quả tìm kiếm: <span class="search-highlight">"<?php echo esc_html($search); ?>"</span></h1>
        <span style="color:var(--color-text-muted);"><?php echo $result['total']; ?> truyện được tìm thấy</span>
        <div class="search-type-chips" aria-label="Loại kết quả">
            <a href="<?php echo esc_url(hdk_page_url('danh-sach-truyen', ['keyword' => $search])); ?>">Tất cả</a>
            <a class="active" href="<?php echo esc_url(hdk_page_url('danh-sach-truyen', ['keyword' => $search])); ?>">Truyện</a>
            <span>Tác giả</span>
            <span>Thể loại</span>
        </div>
    </div>
    <?php elseif ($has_audio): ?>
    <div class="section-header">
        <h1 class="section-title"><?php echo hdk_icon('headphones'); ?> Truyện audio</h1>
        <span style="color:var(--color-text-muted);"><?php echo $result['total']; ?> truyện</span>
    </div>
    <?php else: ?>
    <div class="section-header">
        <h1 class="section-title">Danh sách truyện</h1>
        <span style="color:var(--color-text-muted);"><?php echo $result['total']; ?> truyện</span>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <form class="panel toolbar" method="get" action="<?php echo esc_url(home_url('/danh-sach-truyen/')); ?>" style="padding:16px;margin-bottom:20px;">
        <input type="search" id="search-input" name="keyword" aria-label="Tìm truyện" autocomplete="off" placeholder="Tìm truyện…" value="<?php echo esc_attr($search); ?>"
               style="flex:1;min-width:200px;padding:8px 16px;border:2px solid var(--color-input-border);background:var(--color-input-bg);color:var(--color-text-primary);border-radius:var(--radius-pill);font-family:var(--font-family);min-height:var(--touch-target);">
        <?php if ($has_audio): ?>
            <input type="hidden" name="audio" value="1">
        <?php endif; ?>
        <select id="status-filter" name="status" aria-label="Lọc theo trạng thái" style="padding:8px 16px;border:2px solid var(--color-input-border);background:var(--color-input-bg);color:var(--color-text-primary);border-radius:var(--radius-pill);font-family:var(--font-family);min-height:var(--touch-target);">
            <option value="">Tất cả trạng thái</option>
            <option value="ongoing" <?php selected($status, 'ongoing'); ?>>Đang ra</option>
            <option value="completed" <?php selected($status, 'completed'); ?>>Hoàn thành</option>
            <option value="dropped" <?php selected($status, 'dropped'); ?>>Ngừng</option>
        </select>
        <select id="category-filter" name="category" aria-label="Lọc theo thể loại" style="padding:8px 16px;border:2px solid var(--color-input-border);background:var(--color-input-bg);color:var(--color-text-primary);border-radius:var(--radius-pill);font-family:var(--font-family);min-height:var(--touch-target);">
            <option value="0">Tất cả thể loại</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat->id; ?>" <?php selected($category, $cat->id); ?>><?php echo esc_html($cat->name); ?></option>
            <?php endforeach; ?>
        </select>
        <select id="orderby-filter" name="orderby" aria-label="Sắp xếp theo" style="padding:8px 16px;border:2px solid var(--color-input-border);background:var(--color-input-bg);color:var(--color-text-primary);border-radius:var(--radius-pill);font-family:var(--font-family);min-height:var(--touch-target);">
            <option value="updated_at" <?php selected($orderby, 'updated_at'); ?>>Mới cập nhật</option>
            <option value="total_views" <?php selected($orderby, 'total_views'); ?>>Lượt xem</option>
            <option value="average_rating" <?php selected($orderby, 'average_rating'); ?>>Đánh giá</option>
            <option value="total_favorites" <?php selected($orderby, 'total_favorites'); ?>>Yêu thích</option>
            <option value="published_at" <?php selected($orderby, 'published_at'); ?>>Mới xuất bản</option>
            <option value="title" <?php selected($orderby, 'title'); ?>>Tên A-Z</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Lọc</button>
    </form>

    <?php if (!empty($result['stories'])): ?>
        <div class="grid grid-4">
            <?php foreach ($result['stories'] as $i => $story): ?>
                <?php hdk_get_story_card($story, $i); ?>
            <?php endforeach; ?>
        </div>
        <?php hdk_get_pagination($result['pages'], $page, [
            'keyword' => $search,
            'status' => $status,
            'category' => $category,
            'audio' => $has_audio ? 1 : '',
            'orderby' => $orderby !== 'updated_at' ? $orderby : '',
        ]); ?>
    <?php else: ?>
        <?php if ($search): ?>
        <div class="search-empty-state">
            <div class="search-empty-icon"><?php echo hdk_icon('search'); ?></div>
            <h3>Không tìm thấy truyện nào</h3>
            <p>Không tìm thấy kết quả cho <strong>"<?php echo esc_html($search); ?>"</strong>. Hãy thử từ khóa khác.</p>
            <a href="<?php echo esc_url(hdk_page_url('danh-sach-truyen')); ?>" class="btn btn-outline">Xem tất cả truyện</a>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:60px 0;">
            <p style="font-size:var(--font-size-2xl);margin-bottom:12px;"><?php echo hdk_icon('search', ['size' => '2em']); ?></p>
            <p style="font-size:var(--font-size-lg);color:var(--color-text-muted);">Không tìm thấy truyện nào.</p>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
