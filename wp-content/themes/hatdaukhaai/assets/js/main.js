/**
 * Hong Tran Cac - Main JavaScript
 * Handles: banner, favorites, ratings, comments.
 */

(function() {
    'use strict';

    // ===== Theme Toggle =====
    var STORAGE_KEY = 'hdk-theme';
    var toggleBtn = document.getElementById('theme-toggle');

    function safeGetStorage(key) {
        try { return localStorage.getItem(key); } catch (e) { return null; }
    }

    function safeSetStorage(key, value) {
        try { localStorage.setItem(key, value); } catch (e) {}
    }

    function getTheme() {
        var saved = safeGetStorage(STORAGE_KEY);
        if (saved === 'light' || saved === 'dark') return saved;
        try {
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        } catch (e) {
            return 'light';
        }
    }

    function applyTheme(theme) {
        try {
            document.documentElement.setAttribute('data-theme', theme);
        } catch (e) {}
        if (toggleBtn) {
            toggleBtn.textContent = theme === 'dark' ? '\u263E' : '\u2600';
            toggleBtn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
            toggleBtn.setAttribute('aria-label', theme === 'dark' ? 'Chuy\u1EC3n sang ch\u1EBF \u0111\u1ED9 s\u00E1ng' : 'Chuy\u1EC3n sang ch\u1EBF \u0111\u1ED9 t\u1ED1i');
        }
    }

    function toggleTheme() {
        var current = document.documentElement.getAttribute('data-theme');
        var next = current === 'dark' ? 'light' : 'dark';
        safeSetStorage(STORAGE_KEY, next);
        applyTheme(next);
    }

    applyTheme(getTheme());

    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleTheme);
    }

    try {
        var mq = window.matchMedia('(prefers-color-scheme: dark)');
        var listener = function(e) {
            if (!safeGetStorage(STORAGE_KEY)) {
                applyTheme(e.matches ? 'dark' : 'light');
            }
        };
        if (mq.addEventListener) {
            mq.addEventListener('change', listener);
        } else if (mq.addListener) {
            mq.addListener(listener);
        }
    } catch (e) {}

    // ===== Hero Banner =====
    var bannerCards = document.querySelectorAll('.banner-card');
    var bannerCover = document.getElementById('banner-cover-img');
    var bannerTitle = document.querySelector('.banner-title');
    var bannerSummary = document.querySelector('.banner-summary');
    var bannerLink = document.querySelector('.banner-read-link');
    var bannerViews = document.querySelector('.banner-views');
    var bannerActiveIndex = 0;
    var bannerTotal = bannerCards.length;
    var bannerInterval = null;

    function initBanner() {
        if (!bannerCards.length || !bannerCover) return;

        bannerCards.forEach(function(card, index) {
            card.addEventListener('click', function() {
                resetBannerInterval();
                updateBannerActive(index);
            });
        });

        resetBannerInterval();
    }

    function updateBannerActive(index) {
        var card = bannerCards[index];
        if (!card) return;

        bannerActiveIndex = index;
        bannerCards.forEach(function(c) { c.classList.remove('active'); });
        card.classList.add('active');

        bannerCover.style.opacity = '0';
        setTimeout(function() {
            bannerCover.src = card.dataset.cover;
            bannerCover.alt = card.dataset.title;
            bannerCover.style.opacity = '1';
        }, 200);

        if (bannerTitle) bannerTitle.textContent = card.dataset.title;
        if (bannerSummary) bannerSummary.textContent = card.dataset.summary;
        if (bannerLink) bannerLink.href = card.dataset.url;
        if (bannerViews) {
            var views = parseInt(card.dataset.views, 10) || 0;
            bannerViews.textContent = views.toLocaleString('vi-VN') + ' lượt xem';
        }
    }

    function resetBannerInterval() {
        if (bannerInterval) clearInterval(bannerInterval);
        if (bannerTotal > 1) {
            bannerInterval = setInterval(function() {
                updateBannerActive((bannerActiveIndex + 1) % bannerTotal);
            }, 12000);
        }
    }

    initBanner();

    // Favorite toggle
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.favorite-btn');
        if (!btn) return;

        var storyId = btn.dataset.storyId;
        if (!storyId) return;

        fetch('/wp-json/hdk/v1/stories/' + storyId + '/favorite', { method: 'POST' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.dataset.favorited = data.favorited ? '1' : '0';
                btn.textContent = data.favorited ? 'Bỏ yêu thích' : 'Yêu thích';
                if (data.total_favorites !== undefined) {
                    var countEl = document.querySelector('.favorite-count');
                    if (countEl) countEl.textContent = data.total_favorites;
                }
            })
            .catch(function() {
                window.location.href = '/wp-login.php';
            });
    });

    // Star rating
    document.addEventListener('click', function(e) {
        var star = e.target.closest('.star');
        if (!star) return;

        var rating = parseInt(star.dataset.value, 10);
        var widget = star.closest('.rating-widget');
        var storyId = widget ? widget.dataset.storyId : 0;
        if (!storyId) return;

        fetch('/wp-json/hdk/v1/stories/' + storyId + '/rating', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ rating: rating })
        })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var stars = widget.querySelectorAll('.star');
                stars.forEach(function(s, i) {
                    s.textContent = i < rating ? '★' : '☆';
                });

                var textEl = widget.querySelector('span:last-child');
                if (textEl && data.average_rating) {
                    textEl.textContent = '(' + data.average_rating + ' - ' + data.total_ratings + ' đánh giá)';
                }
            })
            .catch(function() {
                window.location.href = '/wp-login.php';
            });

        e.stopPropagation();
    });

    // Comment submission
    document.addEventListener('submit', function(e) {
        var form = e.target.closest('.comment-form');
        if (!form) return;

        e.preventDefault();
        var textarea = form.querySelector('textarea');
        var storyId = form.dataset.storyId;
        var content = textarea.value.trim();
        if (!content) return;

        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Đang gửi…';
        }

        fetch('/wp-json/hdk/v1/comments', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ story_id: parseInt(storyId, 10), chapter_number: 0, content: content })
        })
            .then(function(r) { return r.json(); })
            .then(function() {
                textarea.value = '';
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Gửi bình luận';
                }
                location.reload();
            })
            .catch(function() {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Gửi bình luận';
                }
                window.location.href = '/wp-login.php';
            });
    });

    // ===== User Dropdown Toggle =====
    var dropdown = document.getElementById('user-dropdown');
    if (dropdown) {
        var dropdownToggle = document.getElementById('user-dropdown-toggle');
        var dropdownMenu = document.getElementById('user-dropdown-menu');

        dropdownToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            var isOpen = dropdownMenu.style.display === 'block';
            dropdownMenu.style.display = isOpen ? 'none' : 'block';
            dropdownToggle.setAttribute('aria-expanded', !isOpen);
        });

        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target)) {
                dropdownMenu.style.display = 'none';
                dropdownToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // ===== Daily Claim =====
    window.claimDaily = function() {
        var btn = document.getElementById('daily-claim-btn');
        if (!btn) return;

        btn.disabled = true;
        btn.textContent = 'Đang xử lý…';

        fetch('/wp-json/hdk/v1/daily-claim', { method: 'POST' })
            .then(function(r) { return r.json().then(function(d) { return {status: r.status, data: d}; }); })
            .then(function(result) {
                if (result.status === 200 && result.data.success) {
                    btn.textContent = 'Đã nhận +' + result.data.credits_earned + ' hạt!';
                    btn.style.background = 'var(--color-success)';
                    btn.style.color = '#fff';
                    btn.style.borderColor = 'var(--color-success)';
                    setTimeout(function() { location.reload(); }, 1500);
                } else if (result.status === 409) {
                    btn.textContent = 'Đã điểm danh hôm nay';
                    btn.disabled = true;
                    btn.style.opacity = '0.5';
                } else {
                    btn.textContent = 'Lỗi, thử lại';
                    btn.disabled = false;
                }
            })
            .catch(function() {
                window.location.href = '/wp-login.php';
            });
    };
})();
