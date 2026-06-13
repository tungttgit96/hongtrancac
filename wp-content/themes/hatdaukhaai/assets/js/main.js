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

    // ===== Reader Settings =====
    var readerContent = document.getElementById('chapter-content');
    if (readerContent) {
        var PREFS_KEY = 'hdk-reader-prefs';
        var STORY_ID = readerContent.dataset.storyId;
        var defaults = {font_size: 20, font_family: 'Be Vietnam Pro', line_height: '2.0', theme: 'light', reading_width: 'wide'};
        
        function loadPrefs() {
            var prefs = {};
            try { var local = JSON.parse(safeGetStorage(PREFS_KEY)); if (local) prefs = local; } catch(e) {}
            return Object.assign({}, defaults, prefs);
        }

        function savePrefs(prefs) {
            safeSetStorage(PREFS_KEY, JSON.stringify(prefs));
            fetch('/wp-json/hdk/v1/reader-prefs', {
                method: 'PATCH',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(prefs)
            }).catch(function(){});
        }

        function applyPrefs(prefs) {
            readerContent.style.fontSize = prefs.font_size + 'px';
            readerContent.style.fontFamily = prefs.font_family;
            readerContent.style.lineHeight = prefs.line_height;
            var fsv = document.getElementById('font-size-val');
            if (fsv) fsv.textContent = prefs.font_size;
            var ffs = document.getElementById('font-family-select');
            if (ffs) ffs.value = prefs.font_family;
            var lhs = document.getElementById('line-height-select');
            if (lhs) lhs.value = prefs.line_height;
            
            var themeBtns = document.querySelectorAll('.reader-theme-btn');
            themeBtns.forEach(function(b) { b.classList.remove('active'); });
            var activeBtn = document.getElementById('theme-btn-' + prefs.theme);
            if (activeBtn) activeBtn.classList.add('active');
            document.body.classList.remove('reader-theme-dark', 'reader-theme-sepia');
            if (prefs.theme !== 'light') document.body.classList.add('reader-theme-' + prefs.theme);
            
            if (prefs.reading_width === 'narrow') {
                readerContent.style.maxWidth = '700px';
                readerContent.style.margin = '0 auto';
            } else {
                readerContent.style.maxWidth = '';
                readerContent.style.margin = '';
            }
        }

        var currentPrefs = loadPrefs();
        applyPrefs(currentPrefs);

        window.adjustFontSize = function(delta) {
            currentPrefs.font_size = Math.max(16, Math.min(28, currentPrefs.font_size + delta));
            applyPrefs(currentPrefs);
            savePrefs(currentPrefs);
        };

        window.setFontFamily = function(family) {
            currentPrefs.font_family = family;
            applyPrefs(currentPrefs);
            savePrefs(currentPrefs);
        };

        window.setLineHeight = function(lh) {
            currentPrefs.line_height = lh;
            applyPrefs(currentPrefs);
            savePrefs(currentPrefs);
        };

        window.setReaderTheme = function(theme) {
            currentPrefs.theme = theme;
            applyPrefs(currentPrefs);
            savePrefs(currentPrefs);
        };

        window.toggleReadingWidth = function() {
            currentPrefs.reading_width = currentPrefs.reading_width === 'narrow' ? 'wide' : 'narrow';
            applyPrefs(currentPrefs);
            savePrefs(currentPrefs);
        };

        // Fetch server prefs for logged-in users
        fetch('/wp-json/hdk/v1/reader-prefs')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.prefs && data.prefs.user_id) {
                    currentPrefs.font_size = parseInt(data.prefs.font_size) || defaults.font_size;
                    currentPrefs.font_family = data.prefs.font_family || defaults.font_family;
                    currentPrefs.line_height = data.prefs.line_height || defaults.line_height;
                    currentPrefs.theme = data.prefs.theme || defaults.theme;
                    currentPrefs.reading_width = data.prefs.reading_width || defaults.reading_width;
                    applyPrefs(currentPrefs);
                }
            }).catch(function(){});

        // ===== TOC =====
        window.toggleTOC = function() {
            var drawer = document.getElementById('toc-drawer');
            var overlay = document.getElementById('toc-overlay');
            var isOpen = drawer.classList.contains('open');
            if (isOpen) { closeTOC(); }
            else {
                overlay.style.display = 'block';
                drawer.classList.add('open');
                if (!drawer.dataset.loaded) loadTOC();
            }
        };

        window.closeTOC = function() {
            var drawer = document.getElementById('toc-drawer');
            var overlay = document.getElementById('toc-overlay');
            if (drawer) drawer.classList.remove('open');
            if (overlay) overlay.style.display = 'none';
        };

        function loadTOC() {
            var drawer = document.getElementById('toc-drawer');
            var list = document.getElementById('toc-list');
            var storySlug = window.location.pathname.replace(/\/$/, '').split('/').pop();
            if (storySlug.includes('?')) storySlug = storySlug.split('?')[0];
            
            fetch('/wp-json/hdk/v1/chapters/' + STORY_ID)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var chapters = data.chapters || [];
                    var currentChapter = parseInt(readerContent.dataset.chapterNumber);
                    var html = '';
                    chapters.forEach(function(ch) {
                        var cls = 'toc-chapter';
                        if (ch.chapter_number === currentChapter) cls += ' current';
                        var lockIcon = '🔓';
                        if (ch.is_purchased) lockIcon = '✅';
                        var url = '/' + storySlug + '?chuong=' + ch.chapter_number;
                        html += '<a href="' + url + '" class="' + cls + '">' +
                            '<span class="toc-chapter-num">' + ch.chapter_number + '</span>' +
                            '<span class="toc-chapter-title">' + escapeHtml(ch.title) + '</span>' +
                            '<span class="toc-chapter-lock">' + lockIcon + '</span>' +
                            '</a>';
                    });
                    list.innerHTML = html || '<p style="color:var(--color-text-muted);text-align:center;padding:20px;">Không có chương nào</p>';
                    drawer.dataset.loaded = '1';
                    
                    var current = list.querySelector('.toc-chapter.current');
                    if (current) current.scrollIntoView({block: 'center'});
                })
                .catch(function() {
                    list.innerHTML = '<p style="color:var(--color-text-muted);text-align:center;padding:20px;">Không thể tải danh sách chương</p>';
                });
        }

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }

})();