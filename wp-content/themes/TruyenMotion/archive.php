<?php
/**
 * Template: Archive (Blog / Category / Tag / Author)
 */
get_header();
?>

<div class="container" style="padding-top:24px;">
    <?php hdk_visual_breadcrumbs(); ?>

    <header style="margin-bottom:24px;">
        <h1 style="font-size:var(--font-size-2xl);font-weight:700;"><?php the_archive_title(); ?></h1>
        <?php
        $description = get_the_archive_description();
        if ($description): ?>
            <div style="color:var(--color-text-muted);margin-top:8px;"><?php echo wp_kses_post($description); ?></div>
        <?php endif; ?>
    </header>

    <?php if (have_posts()): ?>
        <div class="grid grid-3" style="gap:24px;">
            <?php while (have_posts()): the_post(); ?>
                <article class="card" style="overflow:hidden;">
                    <?php if (has_post_thumbnail()): ?>
                        <a href="<?php the_permalink(); ?>">
                            <img src="<?php the_post_thumbnail_url('medium'); ?>" alt="<?php the_title_attribute(); ?>" class="card-img" loading="lazy">
                        </a>
                    <?php endif; ?>
                    <div class="card-body">
                        <h2 class="card-title">
                            <a href="<?php the_permalink(); ?>" style="text-decoration:none;color:inherit;"><?php the_title(); ?></a>
                        </h2>
                        <div class="card-meta" style="display:flex;justify-content:space-between;margin-top:8px;">
                            <span>📅 <?php echo get_the_date(); ?></span>
                            <span>✍️ <?php the_author(); ?></span>
                        </div>
                        <div style="margin-top:8px;font-size:var(--font-size-sm);color:var(--color-text-muted);">
                            <?php echo wp_trim_words(get_the_excerpt(), 20, '...'); ?>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>

        <?php
        global $wp_query;
        $total_pages = $wp_query->max_num_pages;
        if ($total_pages > 1): ?>
        <nav class="pagination" style="display:flex;justify-content:center;align-items:center;gap:8px;padding:24px 0;flex-wrap:wrap;">
            <?php
            $current = max(1, get_query_var('paged', 1));
            echo paginate_links([
                'base'      => str_replace(999999999, '%#%', get_pagenum_link(999999999)),
                'format'    => '?paged=%#%',
                'current'   => $current,
                'total'     => $total_pages,
                'prev_text' => '&laquo; Truoc',
                'next_text' => 'Sau &raquo;',
                'type'      => 'list',
            ]);
            ?>
        </nav>
        <?php endif; ?>

    <?php else: ?>
        <div style="text-align:center;padding:48px 0;">
            <p style="font-size:var(--font-size-lg);color:var(--color-text-muted);">Khong co bai viet nao.</p>
            <a href="<?php echo home_url('/tin-tuc'); ?>" class="btn btn-primary" style="margin-top:16px;">Xem tin tuc</a>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
