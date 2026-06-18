<?php
/**
 * Template: Search Results
 */
get_header();
?>

<div class="container" style="padding-top:24px;">
    <?php hdk_visual_breadcrumbs(); ?>

    <header style="margin-bottom:24px;">
        <h1 style="font-size:var(--font-size-2xl);font-weight:700;">
            <?php printf(esc_html__('Ket qua tim kiem: %s', 'truyenmotion'), '<strong>' . get_search_query() . '</strong>'); ?>
        </h1>
    </header>

    <?php if (have_posts()): ?>
        <div style="display:flex;flex-direction:column;gap:16px;">
            <?php while (have_posts()): the_post(); ?>
                <article class="card" style="overflow:hidden;">
                    <?php if (has_post_thumbnail()): ?>
                        <a href="<?php the_permalink(); ?>" style="flex-shrink:0;">
                            <img src="<?php the_post_thumbnail_url('thumbnail'); ?>" alt="<?php the_title_attribute(); ?>" style="width:120px;height:160px;object-fit:cover;" loading="lazy">
                        </a>
                    <?php endif; ?>
                    <div class="card-body">
                        <h2 class="card-title">
                            <a href="<?php the_permalink(); ?>" style="text-decoration:none;color:inherit;"><?php the_title(); ?></a>
                        </h2>
                        <div class="card-meta" style="display:flex;gap:16px;margin-top:8px;">
                            <span>📅 <?php echo get_the_date(); ?></span>
                            <span>✍️ <?php the_author(); ?></span>
                            <?php if (has_category()): ?>
                                <span>📂 <?php the_category(', '); ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top:8px;font-size:var(--font-size-sm);color:var(--color-text-muted);">
                            <?php echo wp_trim_words(get_the_excerpt(), 30, '...'); ?>
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
            <p style="font-size:var(--font-size-xl);color:var(--color-text-muted);">🔍 <?php esc_html_e('Khong tim thay ket qua phu hop.', 'truyenmotion'); ?></p>
            <p style="margin-top:8px;color:var(--color-text-muted);"><?php esc_html_e('Thu lai voi tu khoa khac.', 'truyenmotion'); ?></p>
            <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" style="margin-top:20px;display:flex;gap:8px;justify-content:center;">
                <input type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('Tim kiem...', 'truyenmotion'); ?>" style="max-width:400px;">
                <button type="submit" class="btn btn-primary"><?php esc_html_e('Tim', 'truyenmotion'); ?></button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
