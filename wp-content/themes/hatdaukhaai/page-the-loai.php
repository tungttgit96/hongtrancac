<?php
/**
 * Template Name: Thể Loại
 * Page: All categories listing
 */

global $wpdb;
$categories = $wpdb->get_results("SELECT * FROM " . HDK_DB::table('hdk_categories') . " ORDER BY sort_order");

get_header();
?>

<div class="container" style="padding-top:24px;">
    <div class="section-header">
        <h1 class="section-title">📂 Tất cả thể loại</h1>
        <span style="color:var(--color-text-muted);"><?php echo count($categories); ?> thể loại</span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px;">
        <?php foreach ($categories as $cat): ?>
            <a href="/the-loai/<?php echo $cat->slug; ?>"
               style="background:var(--color-bg);border-radius:var(--radius-md);padding:20px;border:1px solid var(--color-border);
                      text-decoration:none;transition:box-shadow 0.2s;"
               onmouseover="this.style.boxShadow='var(--shadow-md)'"
               onmouseout="this.style.boxShadow='none'">
                <div style="font-size:var(--font-size-lg);font-weight:600;color:var(--color-text-primary);margin-bottom:8px;">
                    <?php echo esc_html($cat->name); ?>
                </div>
                <?php if (!empty($cat->description)): ?>
                    <p style="font-size:var(--font-size-sm);color:var(--color-text-muted);margin-bottom:8px;">
                        <?php echo esc_html($cat->description); ?>
                    </p>
                <?php endif; ?>
                <span class="badge badge-primary"><?php echo $cat->story_count; ?> truyện</span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php get_footer(); ?>
