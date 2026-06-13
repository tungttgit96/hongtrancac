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
            --color-on-primary: #FFFFFF;
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

            /* Footer */
            --color-footer-bg: #0F1419;
            --color-footer-text: #FFFFFF;
            --color-footer-text-muted: rgba(255,255,255,0.6);
            --color-footer-border: rgba(255,255,255,0.1);
            --color-footer-link: rgba(255,255,255,0.8);
            --color-footer-link-hover: #FFFFFF;

            /* Input */
            --color-input-bg: #FFFFFF;
            --color-input-border: var(--color-border);
            --color-input-focus-ring: rgba(84,207,214,0.3);

            /* Overlay */
            --color-overlay: rgba(0,0,0,0.5);

            /* Select indicator */
            --color-select-indicator: #6B7280;

            /* Banner card overlay (always bright for readability over images) */
            --color-banner-overlay-text: #FFFFFF;
            --color-banner-overlay-text-muted: rgba(255,255,255,0.72);

            /* Badge semantic backgrounds (light) */
            --color-badge-primary-bg: var(--color-primary-light);
            --color-badge-primary-text: var(--color-primary-hover);
            --color-badge-success-bg: #D1FAE5;
            --color-badge-success-text: #065F46;
            --color-badge-warning-bg: #FEF3C7;
            --color-badge-warning-text: #92400E;
            --color-badge-danger-bg: #FEE2E2;
            --color-badge-danger-text: #991B1B;

            /* Banner (light mode) */
            --color-banner-bg: var(--color-bg-secondary);
            --color-banner-text: var(--color-text-primary);
            --color-banner-text-muted: var(--color-text-muted);
            --color-banner-card-bg: var(--color-bg-tertiary);
            --color-banner-shell-gradient: linear-gradient(135deg, rgba(84,207,214,0.06), rgba(84,207,214,0.02) 48%, transparent);
            --color-banner-shell-ambient: radial-gradient(circle at 50% 18%, rgba(84,207,214,0.1), transparent 32%);
            --color-banner-shell-border: var(--color-border);
            --color-banner-veil: none;
            --color-banner-cover-border: var(--color-border);
            --color-banner-cover-shadow: 0 8px 32px rgba(0,0,0,0.12);
            --color-banner-card-active-border: var(--color-primary);
            --color-banner-card-active-shadow: 0 0 18px rgba(84,207,214,0.3);
            --color-banner-card-hover-border: rgba(84,207,214,0.5);
            --color-banner-card-hover-shadow: 0 0 12px rgba(84,207,214,0.15);
            --color-banner-card-rank-bg: rgba(0,0,0,0.65);
            --color-banner-card-rank-active-bg: var(--color-primary);
            --color-banner-card-overlay: linear-gradient(transparent, rgba(0,0,0,0.78));
            --shadow-banner: 0 12px 40px rgba(0,0,0,0.1);

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

        html[data-theme="dark"] {
            --color-primary: #54CFD6;
            --color-primary-hover: #6FE0E6;
            --color-primary-light: #0D3B3E;

            --color-text-primary: #E5E7EB;
            --color-text-secondary: #D1D5DB;
            --color-text-muted: #9CA3AF;

            --color-bg: #111827;
            --color-bg-secondary: #0F172A;
            --color-bg-tertiary: #1F2937;

            --color-border: #374151;
            --color-border-light: #2D3748;

            --color-footer-bg: #0A0F1A;

            --color-input-bg: #1F2937;
            --color-input-border: #4B5563;
            --color-input-focus-ring: rgba(84,207,214,0.2);

            --color-overlay: rgba(0,0,0,0.7);

            --color-select-indicator: #9CA3AF;

            --color-badge-success-bg: #064E3B;
            --color-badge-success-text: #6EE7B7;
            --color-badge-warning-bg: #78350F;
            --color-badge-warning-text: #FCD34D;
            --color-badge-danger-bg: #7F1D1D;
            --color-badge-danger-text: #FCA5A5;

            --shadow-sm: 0 1px 2px rgba(0,0,0,0.3);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.4);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.5);

            /* Banner dark mode */
            --color-banner-bg: #070b12;
            --color-banner-text: #FFFFFF;
            --color-banner-text-muted: rgba(255,255,255,0.72);
            --color-banner-card-bg: #10151f;
            --color-banner-shell-gradient: linear-gradient(100deg, rgba(8,12,23,0.96), rgba(34,54,70,0.92) 48%, rgba(12,19,31,0.98));
            --color-banner-shell-ambient: radial-gradient(circle at 50% 18%, rgba(255,255,255,0.16), transparent 32%);
            --color-banner-shell-border: rgba(255,255,255,0.08);
            --color-banner-veil: linear-gradient(90deg, rgba(0,0,0,0.38), transparent 36%, rgba(0,0,0,0.26));
            --color-banner-cover-border: rgba(255,255,255,0.16);
            --color-banner-cover-shadow: 0 20px 50px rgba(0,0,0,0.42);
            --color-banner-card-active-shadow: 0 0 18px rgba(84,207,214,0.5);
            --color-banner-card-hover-border: rgba(84,207,214,0.75);
            --color-banner-card-hover-shadow: 0 0 12px rgba(84,207,214,0.34);
            --shadow-banner: 0 24px 60px rgba(0,0,0,0.35);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-size: 16px; scroll-behavior: smooth; color-scheme: light; }
        html[data-theme="dark"] { color-scheme: dark; }
        body {
            font-family: var(--font-family);
            color: var(--color-text-primary);
            background: var(--color-bg-secondary);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            transition: background 0.3s, color 0.3s;
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
            font-size: var(--font-size-sm);
            touch-action: manipulation;
            transition: background-color 0.2s, border-color 0.2s, color 0.2s, box-shadow 0.2s, transform 0.2s;
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
            transition: box-shadow 0.2s, transform 0.2s, background 0.3s, border-color 0.3s;
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
        .badge-primary { background: var(--color-badge-primary-bg); color: var(--color-badge-primary-text); }
        .badge-success { background: var(--color-badge-success-bg); color: var(--color-badge-success-text); }
        .badge-warning { background: var(--color-badge-warning-bg); color: var(--color-badge-warning-text); }
        .badge-danger { background: var(--color-badge-danger-bg); color: var(--color-badge-danger-text); }

        .section { padding: 32px 0; }

        input[type="text"],
        input[type="search"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        select,
        textarea {
            background: var(--color-input-bg);
            border: 2px solid var(--color-input-border);
            border-radius: var(--radius-pill);
            color: var(--color-text-primary);
            font-family: var(--font-family);
            font-size: var(--font-size-base);
            min-height: var(--touch-target);
            max-width: 100%;
            min-width: 0;
            padding: 10px 16px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        textarea {
            width: 100%;
            border-radius: var(--radius-md);
        }
        input:focus-visible,
        select:focus-visible,
        textarea:focus-visible {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px var(--color-input-focus-ring);
        }
        a:focus-visible,
        button:focus-visible,
        .btn:focus-visible {
            outline: 2px solid var(--color-primary);
            outline-offset: 2px;
        }
        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-position: right 14px center;
            background-repeat: no-repeat;
            padding-right: 36px;
        }
        ::placeholder { color: var(--color-text-muted); opacity: 1; }
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

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'hdk_design_tokens_css', 1);
