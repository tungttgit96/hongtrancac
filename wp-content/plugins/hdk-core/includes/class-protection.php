<?php
/**
 * HDK Protection - anti-crawl & anti-webtoepub
 */

class HDK_Protection {
    private static $bot_patterns = [
        'bot', 'crawl', 'spider', 'scraper', 'curl', 'wget', 'python', 'java/',
        'php', 'perl', 'ruby', 'go-http', 'node-fetch', 'axios', 'okhttp',
        'scrapy', 'mechanize', 'lwp', 'httpclient', 'urllib', 'libwww',
        'webcopier', 'httrack', 'offline', 'webzip', 'teleport',
        'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider',
        'yandexbot', 'facebot', 'ia_archiver', 'bytespider', 'petalbot',
        'semrushbot', 'ahrefsbot', 'mj12bot', 'dotbot', 'rogerbot',
    ];

    public static function init() {
        add_action('wp', [__CLASS__, 'block_bots'], 1);
        add_action('wp', [__CLASS__, 'rate_limit'], 2);
        add_action('send_headers', [__CLASS__, 'security_headers']);
    }

    // Block known bot user agents (only on chapter pages)
    public static function block_bots() {
        global $wp;
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $is_chapter = (!empty(get_query_var('hdk_story')) && !empty($_GET['chuong']))
                   || strpos($uri, 'wp-json/hdk/v1/purchase') !== false
                   || strpos($uri, 'wp-json/hdk/v1/search') !== false;

        if (!$is_chapter) return;

        $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        if (empty($ua) && $is_chapter) {
            self::deny('Empty User-Agent', 406);
        }
        foreach (self::$bot_patterns as $pattern) {
            if (strpos($ua, $pattern) !== false) {
                // Allow search engines that follow robots.txt
                $allowed = ['googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider', 'yandexbot'];
                $is_allowed = false;
                foreach ($allowed as $a) {
                    if (strpos($ua, $a) !== false) $is_allowed = true;
                }
                if (!$is_allowed) {
                    self::deny('Bot detected: ' . $pattern, 403);
                }
            }
        }

        // Block WebToEpub specifically
        if (strpos($ua, 'webtoepub') !== false || strpos($ua, 'epubpress') !== false) {
            self::deny('WebToEpub blocked', 403);
        }
    }

    // Rate limiting per IP (chapter + API only)
    public static function rate_limit() {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $is_chapter = (!empty(get_query_var('hdk_story')) && !empty($_GET['chuong']))
                   || strpos($uri, 'wp-json/hdk') !== false;
        if (!$is_chapter) return;

        $ip = self::get_ip();
        $key = 'hdk_rl_' . md5($ip);
        $count = (int) get_transient($key);
        $max_requests = 20; // per 60 seconds

        if ($count >= $max_requests && !is_admin() && !wp_doing_ajax()) {
            self::deny('Rate limit exceeded', 429);
        }

        if ($count === 0) {
            set_transient($key, 1, 60);
        } else {
            set_transient($key, $count + 1, 60);
        }
    }

    // Security headers
    public static function security_headers() {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (str_starts_with($uri, '/sitemap') || str_starts_with($uri, '/robots.txt')) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        if (self::is_chapter_page()) {
            header('X-Robots-Tag: noarchive, nocache');
            header('Cache-Control: no-store, must-revalidate');
        }
    }

    // Encode content for JS-only decoding (anti-WebToEpub)
    public static function obfuscate($content) {
        if (empty($content)) return '';
        $encoded = base64_encode($content);
        // Split into chunks to make scraping harder
        $chunks = str_split($encoded, 64);
        return implode("\n", $chunks);
    }

    // Check if current request is a chapter page
    private static function is_chapter_page() {
        return !empty(get_query_var('hdk_story')) && !empty($_GET['chuong']);
    }

    private static function get_ip() {
        $trusted_proxies = apply_filters('hdk_trusted_proxy_ips', []);
        return self::resolve_ip($_SERVER, is_array($trusted_proxies) ? $trusted_proxies : []);
    }

    public static function resolve_ip($server, $trusted_proxies = []) {
        $remote = trim((string)($server['REMOTE_ADDR'] ?? ''));
        if (!filter_var($remote, FILTER_VALIDATE_IP)) {
            $remote = '127.0.0.1';
        }

        $trusted_proxies = array_map('strval', $trusted_proxies);
        if (!in_array($remote, $trusted_proxies, true)) {
            return $remote;
        }

        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'] as $header) {
            if (empty($server[$header])) continue;
            foreach (explode(',', (string)$server[$header]) as $candidate) {
                $candidate = trim($candidate);
                if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                    return $candidate;
                }
            }
        }

        return $remote;
    }

    private static function deny($reason, $code = 403) {
        status_header($code);
        header('Content-Type: text/plain; charset=utf-8');
        die("Access denied. ($reason)");
    }
}
