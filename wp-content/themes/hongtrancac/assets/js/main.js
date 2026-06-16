/**
 * Hong Tran Cac - Main JavaScript
 * Handles: banner, favorites, ratings, comments.
 */

(function() {
    'use strict';

    document.documentElement.classList.add('motion-enabled');

    function restNonce() {
        return window.hdkRestNonce || '';
    }

    function restHeaders(contentType) {
        var headers = { 'X-WP-Nonce': restNonce() };
        if (contentType) headers['Content-Type'] = contentType;
        return headers;
    }

    function redirectToLogin() {
        var fallback = '/dang-nhap?redirect_to=' + encodeURIComponent(window.location.href);
        window.location.href = (window.hdkApi && window.hdkApi.loginUrl) || fallback;
    }

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

    // ===== Header UI =====
    (function initHeader() {
        var searchModal = document.getElementById('search-modal');
        var mobileDrawer = document.getElementById('mobile-drawer');
        var mainNav = document.getElementById('main-nav');
        var mobileToggle = document.querySelector('.mobile-menu-toggle');

        function updateMobileLayout() {
            if (!mainNav || !mobileToggle) return;
            if (window.innerWidth < 768) {
                mobileToggle.style.display = 'flex';
                mainNav.style.display = 'none';
            } else {
                mobileToggle.style.display = 'none';
                mainNav.style.display = 'flex';
                closeMobileDrawer();
            }
        }

        function openSearch() {
            if (!searchModal) return;
            searchModal.classList.add('active');
            var input = searchModal.querySelector('input[name="search"]');
            if (input) setTimeout(function() { input.focus(); }, 10);
        }

        function closeSearch() {
            if (searchModal) searchModal.classList.remove('active');
        }

        function openMobileDrawer() {
            if (mobileDrawer) mobileDrawer.classList.add('open');
        }

        function closeMobileDrawer() {
            if (mobileDrawer) mobileDrawer.classList.remove('open');
        }

        document.querySelectorAll('.search-toggle').forEach(function(el) {
            el.addEventListener('click', function() {
                if (searchModal && searchModal.classList.contains('active')) {
                    closeSearch();
                } else {
                    openSearch();
                }
            });
        });

        var searchCloseBtn = document.querySelector('[data-close-search]');
        if (searchCloseBtn) searchCloseBtn.addEventListener('click', closeSearch);

        if (mobileToggle) {
            mobileToggle.addEventListener('click', function() {
                if (mobileDrawer && mobileDrawer.classList.contains('open')) {
                    closeMobileDrawer();
                } else {
                    openMobileDrawer();
                }
            });
        }

        var mobileCloseBtn = document.querySelector('[data-close-mobile-drawer]');
        if (mobileCloseBtn) mobileCloseBtn.addEventListener('click', closeMobileDrawer);

        if (searchModal) {
            searchModal.addEventListener('click', function(e) {
                if (e.target === searchModal) closeSearch();
            });
        }

        if (mobileDrawer) {
            mobileDrawer.addEventListener('click', function(e) {
                if (e.target === mobileDrawer) closeMobileDrawer();
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeSearch();
                closeMobileDrawer();
            }
        });

        window.addEventListener('resize', updateMobileLayout);
        updateMobileLayout();
    })();

    // ===== Motion Observer =====
    (function initMotion() {
        if (!window.IntersectionObserver) {
            document.querySelectorAll('.motion-reveal, .motion-stagger').forEach(function(el) {
                el.classList.add('motion-visible');
            });
            return;
        }
        var reveals = document.querySelectorAll('.motion-reveal');
        if (!reveals.length) return;

        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (!entry.isIntersecting) return;
                var el = entry.target;
                el.classList.add('motion-visible');

                var staggers = el.querySelectorAll('.motion-stagger');
                staggers.forEach(function(child, i) {
                    child.style.transitionDelay = (i * 80) + 'ms';
                    child.classList.add('motion-visible');
                });

                observer.unobserve(el);
            });
        }, { rootMargin: '0px 0px -60px 0px', threshold: 0.1 });

        reveals.forEach(function(el) { observer.observe(el); });
    })();

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

        var cover = bannerCover;
        cover.style.opacity = '0';
        cover.style.transform = 'translateX(10px)';
        setTimeout(function() {
            cover.src = card.dataset.cover;
            cover.alt = card.dataset.title;
            cover.style.opacity = '1';
            cover.style.transform = 'translateX(0)';
        }, 350);

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

    // Pause auto-rotate when user hovers/focuses the banner
    var heroBanner = document.querySelector('.hero-banner');
    if (heroBanner) {
        heroBanner.addEventListener('mouseenter', function() {
            if (bannerInterval) clearInterval(bannerInterval);
            bannerInterval = null;
        });
        heroBanner.addEventListener('mouseleave', resetBannerInterval);
        heroBanner.addEventListener('focusin', function() {
            if (bannerInterval) clearInterval(bannerInterval);
            bannerInterval = null;
        });
        heroBanner.addEventListener('focusout', resetBannerInterval);
    }

    // Favorite toggle
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.favorite-btn');
        if (!btn) return;

        var storyId = btn.dataset.storyId;
        if (!storyId) return;

        fetch('/wp-json/hdk/v1/stories/' + storyId + '/favorite', {
            method: 'POST',
            headers: restHeaders()
        })
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
                redirectToLogin();
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
            headers: restHeaders('application/json'),
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
                redirectToLogin();
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
            headers: restHeaders('application/json'),
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
                redirectToLogin();
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

        fetch('/wp-json/hdk/v1/daily-claim', {
            method: 'POST',
            headers: restHeaders()
        })
            .then(function(r) { return r.json().then(function(d) { return {status: r.status, data: d}; }); })
            .then(function(result) {
                if (result.status === 200 && result.data.success) {
                    btn.textContent = 'Đã nhận +' + result.data.credits_earned + ' hạt!';
                    btn.style.background = 'var(--color-success)';
                    btn.style.color = 'var(--color-on-success)';
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
                redirectToLogin();
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
                headers: restHeaders('application/json'),
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
                        var chapterNumber = parseInt(ch.chapter_number, 10);
                        var cls = 'toc-chapter';
                        if (chapterNumber === currentChapter) cls += ' current';
                        var lockIcon = '🔓';
                        if (ch.is_purchased) lockIcon = '✅';
                        else if (ch.is_locked) lockIcon = '🔒';
                        var url = '/' + storySlug + '?chuong=' + chapterNumber;
                        html += '<a href="' + url + '" class="' + cls + '">' +
                            '<span class="toc-chapter-num">' + chapterNumber + '</span>' +
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

    // ===== Notifications =====
    var notifBell = document.getElementById('notif-bell');
    if (notifBell) {
        function updateUnreadCount() {
            fetch('/wp-json/hdk/v1/notifications/unread-count')
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    var badge = document.getElementById('notif-badge');
                    if (!badge) return;
                    if (d.count > 0) {
                        badge.style.display = 'inline-block';
                        badge.textContent = d.count > 99 ? '99+' : d.count;
                    } else {
                        badge.style.display = 'none';
                    }
                })
                .catch(function(){});
        }

        updateUnreadCount();
        setInterval(updateUnreadCount, 30000); // Poll every 30s

        // Dropdown toggle
        window.toggleNotifDropdown = function() {
            var dd = document.getElementById('notif-dropdown');
            var isOpen = dd.style.display === 'block';
            dd.style.display = isOpen ? 'none' : 'block';
            if (!isOpen && !dd.dataset.loaded) loadNotifDropdown();
        };

        document.addEventListener('click', function(e) {
            var dd = document.getElementById('notif-dropdown');
            var bell = document.getElementById('notif-bell');
            if (dd && bell && !bell.contains(e.target) && !dd.contains(e.target)) {
                dd.style.display = 'none';
            }
        });

        function loadNotifDropdown() {
            var dd = document.getElementById('notif-dropdown');
            var list = document.getElementById('notif-list');
            fetch('/wp-json/hdk/v1/notifications?page=1')
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    var notifs = d.rows || [];
                    if (!notifs.length) {
                        list.innerHTML = '<div style="padding:16px;text-align:center;color:var(--color-text-muted);">Không có thông báo</div>';
                    } else {
                        var html = '';
                        notifs.forEach(function(n) {
                            var bg = n.is_read == 1 ? 'var(--color-bg)' : 'var(--color-primary-light)';
                            html += '<a href="' + (n.link || '#') + '" class="notif-item" style="display:flex;gap:12px;padding:10px 16px;background:' + bg + ';text-decoration:none;color:var(--color-text-primary);border-bottom:1px solid var(--color-border-light);">' +
                                '<div style="flex:1;">' +
                                '<div style="font-weight:' + (n.is_read == 1 ? '400' : '600') + ';margin-bottom:2px;">' + escapeHtml(n.title) + '</div>' +
                                '<div style="color:var(--color-text-muted);font-size:12px;">' + escapeHtml(n.message) + '</div>' +
                                '<div style="color:var(--color-text-muted);font-size:11px;margin-top:2px;">' + n.created_at.substr(11,5) + ' ' + n.created_at.substr(0,10) + '</div>' +
                                '</div>' +
                                (n.is_read == 1 ? '' : '<span style="width:8px;height:8px;border-radius:50%;background:var(--color-primary);flex-shrink:0;margin-top:4px;"></span>') +
                                '</a>';
                        });
                        list.innerHTML = html;
                    }
                    dd.dataset.loaded = '1';
                })
                .catch(function() {
                    list.innerHTML = '<div style="padding:16px;text-align:center;color:var(--color-text-muted);">Lỗi tải thông báo</div>';
                });
        }

        window.markAllRead = function() {
            fetch('/wp-json/hdk/v1/notifications/read', {
                method: 'POST',
                headers: restHeaders(),
                body: '{}'
            })
                .then(function() {
                    updateUnreadCount();
                    var dd = document.getElementById('notif-dropdown');
                    if (dd) dd.dataset.loaded = '';
                    var list = document.getElementById('notif-list');
                    if (list) list.innerHTML = '<div style="padding:16px;text-align:center;color:var(--color-text-muted);">Đã đọc tất cả</div>';
                    location.reload();
                });
        };
    }

    // ===== Report Modal =====
    window.toggleReportModal = function() {
        document.getElementById('report-modal').style.display = 'flex';
    };

    window.submitReport = function(e) {
        e.preventDefault();
        var type = document.getElementById('report-type').value;
        if (!type) return;

        var data = new FormData();
        data.append('story_id', document.getElementById('report-story-id').value);
        data.append('chapter_number', document.getElementById('report-chapter').value);
        data.append('report_type', type);
        data.append('note', document.getElementById('report-note').value);

        var btn = document.querySelector('#report-form button[type="submit"]');
        btn.disabled = true; btn.textContent = 'Đang gửi...';

        fetch('/wp-json/hdk/v1/reports', {
            method: 'POST',
            headers: restHeaders(),
            body: data
        })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                var msg = document.getElementById('report-msg');
                msg.style.display = 'block';
                msg.style.color = 'var(--color-success)';
                msg.textContent = 'Đã gửi báo lỗi. Cảm ơn bạn!';
                setTimeout(function() { document.getElementById('report-modal').style.display = 'none'; }, 1500);
            })
            .catch(function() {
                btn.disabled = false; btn.textContent = 'Gửi báo lỗi';
            });
    };

})();
