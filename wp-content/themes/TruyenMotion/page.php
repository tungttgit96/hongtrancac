<?php
/**
 * Template: Generic Page
 * Used for: Lien he, Dieu khoan, Chinh sach bao mat, and any standard WordPress page
 */
get_header();
?>

<div class="container" style="padding-top:24px;">
    <?php hdk_visual_breadcrumbs(); ?>

    <?php while (have_posts()): the_post(); ?>
        <article style="max-width:800px;margin:0 auto;">
            <header style="margin-bottom:24px;">
                <h1 style="font-size:var(--font-size-3xl);font-weight:700;margin-bottom:12px;"><?php the_title(); ?></h1>
            </header>

            <div style="line-height:1.8;font-size:var(--font-size-lg);color:var(--color-text-secondary);">
                <?php the_content(); ?>
            </div>
        </article>
    <?php endwhile; ?>
</div>

<?php get_footer(); ?>
