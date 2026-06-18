<?php
/**
 * Single Post (News Detail)
 */

get_header();

while (have_posts()): the_post();
?>
<div class="container" style="padding-top:24px;">
    <nav style="font-size:var(--font-size-sm);color:var(--color-text-muted);margin-bottom:20px;">
        <a href="<?php echo home_url('/'); ?>">Trang chủ</a> <?php echo hdk_icon('chevron-right'); ?>
        <a href="<?php echo home_url('/tin-tuc'); ?>">Tin tức</a> <?php echo hdk_icon('chevron-right'); ?>
        <span><?php the_title(); ?></span>
    </nav>

    <article style="max-width:800px;margin:0 auto;">
        <header style="margin-bottom:24px;">
            <?php if (has_post_thumbnail()): ?>
                <img src="<?php the_post_thumbnail_url('large'); ?>" alt="<?php the_title_attribute(); ?>" style="width:100%;border-radius:var(--radius-md);margin-bottom:16px;">
            <?php endif; ?>
            <h1 style="font-size:var(--font-size-3xl);font-weight:700;margin-bottom:12px;"><?php the_title(); ?></h1>
            <div style="display:flex;align-items:center;gap:16px;color:var(--color-text-muted);font-size:var(--font-size-sm);">
                <span><?php echo hdk_icon('pen-tool'); ?> <?php the_author(); ?></span>
                <span><?php echo hdk_icon('calendar'); ?> <?php echo get_the_date(); ?></span>
                <span><?php echo hdk_icon('folder'); ?> <?php the_category(', '); ?></span>
            </div>
        </header>

        <div style="line-height:1.8;font-size:var(--font-size-lg);color:var(--color-text-secondary);">
            <?php the_content(); ?>
        </div>

        <!-- Tags -->
        <?php if (has_tag()): ?>
            <div style="margin-top:24px;display:flex;gap:8px;flex-wrap:wrap;">
                <?php the_tags('<span class="badge badge-primary">', '</span><span class="badge badge-primary">', '</span>'); ?>
            </div>
        <?php endif; ?>

        <!-- Prev/Next -->
        <div style="margin-top:32px;padding-top:24px;border-top:1px solid var(--color-border);display:flex;justify-content:space-between;">
            <div><?php previous_post_link('%link', hdk_icon('chevron-left') . ' %title'); ?></div>
            <div><?php next_post_link('%link', '%title ' . hdk_icon('chevron-right')); ?></div>
        </div>
    </article>
</div>
<?php endwhile;

get_footer();
