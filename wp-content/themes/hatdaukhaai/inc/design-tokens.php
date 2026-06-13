<?php
/**
 * DesignMD Design Tokens for Hạt Đậu Khả Ái
 * Primary: #54CFD6 | Hero accent: #410000 | Text: #0F1419 / #333333 | Border: #E1EAEF
 * Font: Be Vietnam Pro | Cards: 12px radius | Buttons: 24px pill | Touch: 44px
 */

function hdk_design_tokens_css() {
    ?>
    <style>
        :root {
            --color-primary: #54CFD6;
            --color-primary-hover: #3BB5BC;
            --color-primary-light: #E0F7F9;
            --color-hero-accent: #410000;
            --color-hero-bg: #410000;
            --color-text-primary: #0F1419;
            --color-text-secondary: #333333;
            --color-text-muted: #6B7280;
            --color-bg: #FFFFFF;
            --color-bg-secondary: #F9FAFB;
            --color-bg-tertiary: #F3F4F6;
            --color-border: #E1EAEF;
            --color-border-light: #EEF2F5;
            --color-success: #10B981;
            --color-warning: #F59E0B;
            --color-danger: #EF4444;
            --color-rating: #F59E0B;

            --font-family: 'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --font-size-xs: 0.75rem;
            --font-size-sm: 0.875rem;
            --font-size-base: 1rem;
            --font-size-lg: 1.125rem;
            --font-size-xl: 1.25rem;
            --font-size-2xl: 1.5rem;
            --font-size-3xl: 1.875rem;

            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-pill: 24px;
            --radius-full: 9999px;

            --touch-target: 44px;

            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);

            --container-max: 1280px;
            --sidebar-width: 300px;

            --header-height: 64px;
            --card-min-width: 200px;

            --grid-gap: 16px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-size: 16px; scroll-behavior: smooth; }
        body {
            font-family: var(--font-family);
            color: var(--color-text-primary);
            background: var(--color-bg-secondary);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        a { color: var(--color-primary); text-decoration: none; transition: color 0.2s; }
        a:hover { color: var(--color-primary-hover); }
        img { max-width: 100%; height: auto; }

        .container { max-width: var(--container-max); margin: 0 auto; padding: 0 16px; }

        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            min-height: var(--touch-target); padding: 8px 24px;
            border-radius: var(--radius-pill); border: none; cursor: pointer;
            font-family: var(--font-family); font-weight: 600;
            font-size: var(--font-size-sm); transition: all 0.2s;
            gap: 8px; white-space: nowrap;
        }
        .btn-primary { background: var(--color-primary); color: #FFF; }
        .btn-primary:hover { background: var(--color-primary-hover); }
        .btn-outline { background: transparent; border: 2px solid var(--color-primary); color: var(--color-primary); }
        .btn-outline:hover { background: var(--color-primary); color: #FFF; }
        .btn-ghost { background: transparent; color: var(--color-text-secondary); }
        .btn-ghost:hover { background: var(--color-bg-tertiary); }
        .btn-sm { min-height: 36px; padding: 6px 16px; font-size: var(--font-size-xs); }
        .btn-lg { min-height: 52px; padding: 12px 32px; font-size: var(--font-size-base); }

        .card {
            background: var(--color-bg); border-radius: var(--radius-md);
            border: 1px solid var(--color-border); overflow: hidden;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
        .card-img { width: 100%; aspect-ratio: 3/4; object-fit: cover; display: block; }
        .card-body { padding: 12px; }
        .card-title {
            font-size: var(--font-size-sm); font-weight: 600;
            line-height: 1.4; display: -webkit-box;
            -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
            color: var(--color-text-primary);
        }
        .card-meta { font-size: var(--font-size-xs); color: var(--color-text-muted); margin-top: 4px; }

        .grid { display: grid; gap: var(--grid-gap); }
        .grid-6 { grid-template-columns: repeat(6, 1fr); }
        .grid-4 { grid-template-columns: repeat(4, 1fr); }
        .grid-3 { grid-template-columns: repeat(3, 1fr); }
        .grid-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-1 { grid-template-columns: 1fr; }

        @media (max-width: 1024px) {
            .grid-6 { grid-template-columns: repeat(4, 1fr); }
            .grid-4 { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 768px) {
            .grid-6, .grid-4, .grid-3 { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 375px) {
            .grid-6, .grid-4, .grid-3, .grid-2 { grid-template-columns: 1fr; }
        }

        .badge {
            display: inline-flex; align-items: center;
            padding: 4px 12px; border-radius: var(--radius-full);
            font-size: var(--font-size-xs); font-weight: 600;
            min-height: 24px;
        }
        .badge-primary { background: var(--color-primary-light); color: var(--color-primary-hover); }
        .badge-success { background: #D1FAE5; color: #065F46; }
        .badge-warning { background: #FEF3C7; color: #92400E; }
        .badge-danger { background: #FEE2E2; color: #991B1B; }

        .section { padding: 32px 0; }
        .section-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 20px; flex-wrap: wrap; gap: 12px;
        }
        .section-title {
            font-size: var(--font-size-xl); font-weight: 700;
            color: var(--color-text-primary);
        }

        .sr-only {
            position: absolute; width: 1px; height: 1px;
            padding: 0; margin: -1px; overflow: hidden;
            clip: rect(0,0,0,0); border: 0;
        }
    </style>
    <?php
}
add_action('wp_head', 'hdk_design_tokens_css', 1);
