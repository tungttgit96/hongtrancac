<?php
/**
 * Template: Taxonomy (Category / Author / Character listing)
 */
global $hdk_taxonomy_type, $hdk_taxonomy_slug, $hdk_category, $hdk_author, $hdk_character;

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;

switch ($hdk_taxonomy_type) {
    case 'category':
        $term = $hdk_category;
        $page_title = 'Thể loại: ' . ($term->name ?? $hdk_taxonomy_slug);
        $stories = HDK_DB::get_stories(['category_id' => $term->id ?? 0, 'page' => $page, 'per_page' => $per_page]);
        break;
    case 'author':
        $term = $hdk_author;
        $page_title = 'Tác giả: ' . ($term->name ?? $hdk_taxonomy_slug);
        $stories = HDK_DB::get_stories(['author_id' => $term->id ?? 0, 'page' => $page, 'per_page' => $per_page]);
        break;
    case 'character':
        $term = $hdk_character;
        $page_title = 'Nhân vật: ' . ($term->name ?? $hdk_taxonomy_slug);
        $stories = HDK_DB::get_stories(['page' => $page, 'per_page' => $per_page]);
        break;
    default:
        wp_redirect(home_url('/'));
        exit;
}

if (!$term) {
    status_header(404);
    get_header();
    echo '<div class="container" style="padding:60px 0;text-align:center;"><h2>Không tìm thấy</h2></div>';
    get_footer();
    exit;
}

get_header();
?>

<div class="container" style="padding-top:24px;">
    <nav style="font-size:var(--font-size-sm);color:var(--color-text-muted);margin-bottom:20px;">
        <a href="<?php echo home_url('/'); ?>">Trang chủ</a> <?php echo hdk_icon('chevron-right'); ?>
        <span><?php echo esc_html($page_title); ?></span>
    </nav>

    <?php if ($hdk_taxonomy_type === 'author' && !empty($term->bio)): ?>
        <div style="background:var(--color-bg);border-radius:var(--radius-md);padding:20px;border:1px solid var(--color-border);margin-bottom:24px;">
            <div style="display:flex;align-items:center;gap:16px;">
                <?php if (!empty($term->avatar_url)): ?>
                    <img src="<?php echo esc_url($term->avatar_url); ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;">
                <?php endif; ?>
                <div>
                    <h2 style="font-size:var(--font-size-xl);font-weight:700;"><?php echo esc_html($term->name); ?></h2>
                    <p style="color:var(--color-text-secondary);margin-top:4px;"><?php echo esc_html($term->bio); ?></p>
                    <p style="color:var(--color-text-muted);font-size:var(--font-size-sm);margin-top:4px;">
                        <?php echo $term->story_count ?? 0; ?> truyện
                    </p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="section-header">
            <h2 class="section-title"><?php echo esc_html($page_title); ?></h2>
            <span style="color:var(--color-text-muted);"><?php echo $stories['total']; ?> truyện</span>
        </div>
    <?php endif; ?>

    <?php if (!empty($stories['stories'])): ?>
        <div class="grid grid-4">
            <?php foreach ($stories['stories'] as $i => $story): ?>
                <?php hdk_get_story_card($story, $i); ?>
            <?php endforeach; ?>
        </div>
        <?php hdk_get_pagination($stories['pages'], $page); ?>
    <?php else: ?>
        <div style="text-align:center;padding:60px 0;">
            <p style="font-size:var(--font-size-lg);color:var(--color-text-muted);">Chưa có truyện nào.</p>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
