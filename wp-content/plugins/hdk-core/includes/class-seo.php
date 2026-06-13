<?php
/**
 * HDK SEO - canonical URLs, Open Graph, Twitter Cards, JSON-LD
 */

class HDK_SEO {
    public static function head_meta() {
        global $hdk_story, $hdk_chapter, $hdk_category, $hdk_author;

        $title = wp_get_document_title();
        $description = get_bloginfo('description');
        $url = home_url($_SERVER['REQUEST_URI']);
        $image = '';
        $type = 'website';

        if ($hdk_chapter) {
            $title = sprintf('%s - Chương %d - %s', $hdk_story->title, $hdk_chapter->chapter_number, get_bloginfo('name'));
            $description = wp_trim_words(strip_tags($hdk_chapter->content ?? ''), 30, '...');
            $url = home_url('/' . $hdk_story->slug . '?chuong=' . $hdk_chapter->chapter_number);
            $image = $hdk_story->cover_url ?? '';
            $type = 'article';
        } elseif ($hdk_story) {
            $title = sprintf('%s - %s', $hdk_story->title, get_bloginfo('name'));
            $description = wp_trim_words($hdk_story->summary ?? '', 30, '...');
            $url = home_url('/' . $hdk_story->slug);
            $image = $hdk_story->cover_url ?? '';
            $type = 'book';
        } elseif ($hdk_category) {
            $title = sprintf('Truyện %s - %s', $hdk_category->name, get_bloginfo('name'));
            $url = home_url('/the-loai/' . $hdk_category->slug);
        } elseif ($hdk_author) {
            $title = sprintf('Tác giả %s - %s', $hdk_author->name, get_bloginfo('name'));
            $url = home_url('/tac-gia/' . $hdk_author->slug);
        }

        ?>
        <link rel="canonical" href="<?php echo esc_url($url); ?>" />
        <?php
        // Category/Author meta description
        if ($hdk_category && isset($hdk_category->description) && $hdk_category->description) {
            $description = $hdk_category->description;
        }
        if ($hdk_author && isset($hdk_author->bio) && $hdk_author->bio) {
            $description = $hdk_author->bio;
        }
        ?>
        <meta name="description" content="<?php echo esc_attr($description); ?>" />
        <meta property="og:title" content="<?php echo esc_attr($title); ?>" />
        <meta property="og:description" content="<?php echo esc_attr($description); ?>" />
        <meta property="og:url" content="<?php echo esc_url($url); ?>" />
        <meta property="og:type" content="<?php echo esc_attr($type); ?>" />
        <meta property="og:site_name" content="<?php echo esc_attr(get_bloginfo('name')); ?>" />
        <?php if ($image): ?>
        <meta property="og:image" content="<?php echo esc_url($image); ?>" />
        <?php endif; ?>
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="<?php echo esc_attr($title); ?>" />
        <meta name="twitter:description" content="<?php echo esc_attr($description); ?>" />
        <?php if ($image): ?>
        <meta name="twitter:image" content="<?php echo esc_url($image); ?>" />
        <?php endif; ?>

        <?php if ($hdk_story && !$hdk_chapter): ?>
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Book",
            "name": "<?php echo esc_js($hdk_story->title); ?>",
            "author": {"@type": "Person", "name": "<?php echo esc_js($hdk_story->author_name); ?>"},
            "description": "<?php echo esc_js(wp_trim_words($hdk_story->summary ?? '', 50)); ?>",
            "image": "<?php echo esc_url($image); ?>"
        }
        </script>
        <?php elseif ($hdk_story && $hdk_chapter):
            $article = [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $hdk_story->title . ' - Chương ' . $hdk_chapter->chapter_number,
                'author' => ['@type' => 'Person', 'name' => $hdk_story->author_name],
                'isPartOf' => ['@type' => 'Book', 'name' => $hdk_story->title, 'url' => home_url('/' . $hdk_story->slug)],
            ];
            if (!empty($hdk_story->published_at)) $article['datePublished'] = $hdk_story->published_at;
            if (!empty($hdk_story->updated_at)) $article['dateModified'] = $hdk_story->updated_at;
            if (!empty($hdk_story->cover_url)) $article['image'] = $hdk_story->cover_url;
        ?>
        <script type="application/ld+json"><?php echo json_encode($article, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
        <?php else: ?>
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "<?php echo esc_js(get_bloginfo('name')); ?>",
            "url": "<?php echo esc_url(home_url()); ?>",
            "description": "<?php echo esc_js(get_bloginfo('description')); ?>"
        }
        </script>
        <?php endif; ?>

        <?php
        // JSON-LD BreadcrumbList
        $breadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => home_url('/')],
            ]
        ];
        if ($hdk_story) {
            $breadcrumb['itemListElement'][] = ['@type' => 'ListItem', 'position' => 2, 'name' => $hdk_story->title, 'item' => home_url('/' . $hdk_story->slug)];
            if ($hdk_chapter) {
                $breadcrumb['itemListElement'][] = ['@type' => 'ListItem', 'position' => 3, 'name' => 'Chương ' . $hdk_chapter->chapter_number];
            }
        } elseif ($hdk_category) {
            $breadcrumb['itemListElement'][] = ['@type' => 'ListItem', 'position' => 2, 'name' => $hdk_category->name, 'item' => home_url('/the-loai/' . $hdk_category->slug)];
        } elseif ($hdk_author) {
            $breadcrumb['itemListElement'][] = ['@type' => 'ListItem', 'position' => 2, 'name' => $hdk_author->name, 'item' => home_url('/tac-gia/' . $hdk_author->slug)];
        }
        echo "\n" . '<script type="application/ld+json">' . json_encode($breadcrumb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        ?>
        <?php
    }
}
