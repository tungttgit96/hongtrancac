<?php
/**
 * HDK Sitemap - generates XML sitemap index and sub-sitemaps
 */

class HDK_Sitemap {
    public static function init() {
        // Prevent canonical redirect for sitemap URLs
        add_filter('redirect_canonical', function($redirect_url, $requested_url) {
            if (str_contains($requested_url, '/sitemap')) return false;
            return $redirect_url;
        }, 10, 2);

        add_action('template_redirect', function() {
            global $wp;
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (str_starts_with($uri, '/sitemap')) {
                self::render();
                exit;
            }
        }, 1);

        // Scheduled sitemap refresh
        if (!wp_next_scheduled('hdk_refresh_sitemap')) {
            wp_schedule_event(time(), 'daily', 'hdk_refresh_sitemap');
        }
    }

    public static function render() {
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

        $uri = $_SERVER['REQUEST_URI'];
        $parts = explode('/', trim($uri, '/'));

        if ($uri === '/sitemap.xml' || $uri === '/sitemap_index.xml') {
            self::render_index();
        } elseif (preg_match('/^\/?sitemap-(\w+)-(\d+)\.xml$/', $uri, $m)) {
            self::render_sub_sitemap($m[1], (int)$m[2]);
        }
    }

    private static function render_index() {
        ?>
        <sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
            <sitemap><loc><?php echo home_url('/sitemap-static-1.xml'); ?></loc></sitemap>
            <?php
            global $wpdb;
            $total_stories = $wpdb->get_var("SELECT COUNT(*) FROM " . HDK_DB::table('hdk_stories'));
            $per_file = 40000;
            $files = ceil($total_stories / $per_file);
            for ($i = 1; $i <= $files; $i++) {
                echo "<sitemap><loc>" . home_url("/sitemap-stories-$i.xml") . "</loc></sitemap>\n";
            }
            ?>
            <sitemap><loc><?php echo home_url('/sitemap-categories-1.xml'); ?></loc></sitemap>
            <sitemap><loc><?php echo home_url('/sitemap-authors-1.xml'); ?></loc></sitemap>
            <sitemap><loc><?php echo home_url('/sitemap-characters-1.xml'); ?></loc></sitemap>
        </sitemapindex>
        <?php
    }

    private static function render_sub_sitemap($type, $page) {
        global $wpdb;
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        switch ($type) {
            case 'static':
                $urls = [
                    ['loc' => home_url('/'), 'lastmod' => date('Y-m-d'), 'changefreq' => 'daily', 'priority' => '1.0'],
                    ['loc' => home_url('/danh-sach-truyen'), 'lastmod' => date('Y-m-d'), 'changefreq' => 'hourly', 'priority' => '0.9'],
                    ['loc' => home_url('/bang-xep-hang'), 'lastmod' => date('Y-m-d'), 'changefreq' => 'hourly', 'priority' => '0.9'],
                    ['loc' => home_url('/hoan-thanh'), 'lastmod' => date('Y-m-d'), 'changefreq' => 'daily', 'priority' => '0.8'],
                    ['loc' => home_url('/truyen-free'), 'lastmod' => date('Y-m-d'), 'changefreq' => 'daily', 'priority' => '0.8'],
                    ['loc' => home_url('/the-loai'), 'lastmod' => date('Y-m-d'), 'changefreq' => 'weekly', 'priority' => '0.7'],
                    ['loc' => home_url('/tin-tuc'), 'lastmod' => date('Y-m-d'), 'changefreq' => 'daily', 'priority' => '0.7'],
                ];
                foreach ($urls as $u) self::url_entry($u['loc'], $u['lastmod'], $u['changefreq'], $u['priority']);
                break;

            case 'stories':
                $per_file = 40000;
                $offset = ($page - 1) * $per_file;
                $stories = $wpdb->get_results($wpdb->prepare(
                    "SELECT slug, cover_url, updated_at FROM " . HDK_DB::table('hdk_stories') . " ORDER BY id LIMIT %d OFFSET %d",
                    $per_file, $offset
                ));
                foreach ($stories as $s) {
                    $loc = home_url('/' . $s->slug);
                    $lastmod = date('Y-m-d', strtotime($s->updated_at));
                    echo "<url><loc>$loc</loc><lastmod>$lastmod</lastmod><changefreq>daily</changefreq><priority>0.8</priority>";
                    if ($s->cover_url) {
                        echo "<image:image><image:loc>" . esc_url($s->cover_url) . "</image:loc></image:image>";
                    }
                    echo "</url>\n";
                }
                break;

            case 'categories':
                $cats = $wpdb->get_results("SELECT slug, updated_at FROM " . HDK_DB::table('hdk_categories'));
                foreach ($cats as $c) {
                    self::url_entry(home_url('/the-loai/' . $c->slug), date('Y-m-d', strtotime($c->updated_at)), 'daily', '0.7');
                }
                break;

            case 'authors':
                $authors = $wpdb->get_results("SELECT slug, updated_at FROM " . HDK_DB::table('hdk_authors'));
                foreach ($authors as $a) {
                    self::url_entry(home_url('/tac-gia/' . $a->slug), date('Y-m-d', strtotime($a->updated_at)), 'weekly', '0.6');
                }
                break;

            case 'characters':
                $chars = $wpdb->get_results("SELECT slug, updated_at FROM " . HDK_DB::table('hdk_characters'));
                foreach ($chars as $c) {
                    self::url_entry(home_url('/nhan-vat/' . $c->slug), date('Y-m-d', strtotime($c->updated_at)), 'weekly', '0.5');
                }
                break;
        }

        echo '</urlset>';
    }

    private static function url_entry($loc, $lastmod, $changefreq, $priority) {
        echo "<url><loc>$loc</loc><lastmod>$lastmod</lastmod><changefreq>$changefreq</changefreq><priority>$priority</priority></url>\n";
    }
}
