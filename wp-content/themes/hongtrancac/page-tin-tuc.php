<?php
/**
 * Template Name: Tin Tức
 * Blog/News listing page using native WordPress posts
 */

get_header();

$paged = isset($_GET['page']) ? max(1, (int)$_GET['page']) : (get_query_var('paged') ?: 1);
$posts_query = new WP_Query([
    'post_type' => 'post',
    'posts_per_page' => 12,
    'paged' => $paged,
]);
?>

<div class="container" style="padding-top:24px;">
    <div class="section-header">
        <h1 class="section-title"><?php echo hdk_icon('newspaper'); ?> Tin tức</h1>
    </div>

    <?php if ($posts_query->have_posts()): ?>
        <div class="grid grid-3">
            <?php while ($posts_query->have_posts()): $posts_query->the_post(); ?>
                <article class="card">
                    <?php if (has_post_thumbnail()): ?>
                        <img src="<?php the_post_thumbnail_url('medium'); ?>" alt="<?php the_title_attribute(); ?>" class="card-img" style="aspect-ratio:16/9;">
                    <?php endif; ?>
                    <div class="card-body">
                        <h2 class="card-title" style="font-size:var(--font-size-base);">
                            <a href="<?php the_permalink(); ?>" style="color:var(--color-text-primary);"><?php the_title(); ?></a>
                        </h2>
                        <div class="card-meta">
                            <?php echo get_the_date(); ?> · <?php echo get_the_author(); ?>
                        </div>
                        <p style="font-size:var(--font-size-sm);color:var(--color-text-muted);margin-top:8px;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">
                            <?php echo wp_trim_words(get_the_excerpt(), 25); ?>
                        </p>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>

        <div style="display:flex;justify-content:center;margin-top:24px;">
            <?php
            $total = $posts_query->max_num_pages;
            if ($total > 1): ?>
                <nav class="pagination" style="display:flex;gap:8px;">
                    <?php if ($paged > 1): ?>
                        <a href="?page=<?php echo $paged - 1; ?>" class="btn btn-ghost btn-sm"><?php echo hdk_icon('chevron-left'); ?> Trước</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="btn <?php echo $i === $paged ? 'btn-primary' : 'btn-ghost'; ?> btn-sm"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($paged < $total): ?>
                        <a href="?page=<?php echo $paged + 1; ?>" class="btn btn-ghost btn-sm">Sau <?php echo hdk_icon('chevron-right'); ?></a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div style="text-align:center;padding:60px 0;">
            <p style="font-size:var(--font-size-lg);color:var(--color-text-muted);">Chưa có tin tức nào.</p>
        </div>
    <?php endif; wp_reset_postdata(); ?>
</div>

<?php get_footer(); ?>
