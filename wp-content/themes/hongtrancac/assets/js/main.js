/**
 * Hong Tran Cac - Main JavaScript
 * Handles: banner, favorites, ratings, comments.
 */

(function() {
    'use strict';

    document.documentElement.classList.add('motion-enabled');

    window.hdkIcon = function(name, className) {
        var cls = 'hdk-icon hdk-icon-' + name + (className ? ' ' + className : '');
        var icons = {
            sun: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>',
            moon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.985 12.486a9 9 0 1 1-9.473-9.472c.405-.022.617.46.402.803a6 6 0 0 0 8.268 8.268c.344-.215.825-.004.803.401"/></svg>',
            star: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/></svg>',
            'star-filled': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/></svg>',
            play: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 5a2 2 0 0 1 3.008-1.728l11.997 6.998a2 2 0 0 1 .003 3.458l-12 7A2 2 0 0 1 5 19z"/></svg>',
            pause: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="14" y="3" width="5" height="18" rx="1"/><rect x="5" y="3" width="5" height="18" rx="1"/></svg>',
            x: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>',
            lock: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
            unlock: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>',
            check: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
            heart: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5"/></svg>',
            gem: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.5 3 8 9l4 13 4-13-2.5-6"/><path d="M17 3a2 2 0 0 1 1.6.8l3 4a2 2 0 0 1 .013 2.382l-7.99 10.986a2 2 0 0 1-3.247 0l-7.99-10.986A2 2 0 0 1 2.4 7.8l2.998-3.997A2 2 0 0 1 7 3z"/><path d="M2 9h20"/></svg>',
        };
        var svg = icons[name] || '';
        if (!svg) return '';
        return svg.replace('<svg', '<svg class="' + cls + '" aria-hidden="true"');
    };

    function siteUrl(path) {
        var base = (window.hdkApi && window.hdkApi.homeUrl) || '/';
        if (path) return base.replace(/\/$/, '') + '/' + path.replace(/^\//, '');
        return base;
    }

    function apiUrl(path) {
        var base = (window.hdkApi && window.hdkApi.restBase) || siteUrl('wp-json/hdk/v1');
        return base.replace(/\/$/, '') + path;
    }

    function restNonce() {
        return window.hdkRestNonce || '';
    }

    function restHeaders(contentType) {
        var headers = { 'X-WP-Nonce': restNonce() };
        if (contentType) headers['Content-Type'] = contentType;
        return headers;
    }

    function redirectToLogin() {
        var fallback = siteUrl('dang-nhap?redirect_to=' + encodeURIComponent(window.location.href));
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
            toggleBtn.innerHTML = theme === 'dark' ? window.hdkIcon('sun') : window.hdkIcon('moon');
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
        var searchInput = document.getElementById('site-search-input');
        var searchForm = document.getElementById('site-search-form');
        var searchResults = document.getElementById('site-search-results');
        var searchStatus = document.getElementById('site-search-status');
        var mobileDrawer = document.getElementById('mobile-drawer');
        var mainNav = document.getElementById('main-nav');
        var mobileToggle = document.querySelector('.mobile-menu-toggle');
        var searchTimer = null;
        var lastSearch = '';

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
            searchModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('search-open');
            document.querySelectorAll('.search-toggle').forEach(function(el) {
                el.setAttribute('aria-expanded', 'true');
            });
            if (searchInput) setTimeout(function() { searchInput.focus(); }, 10);
        }

        function closeSearch() {
            if (!searchModal) return;
            searchModal.classList.remove('active');
            searchModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('search-open');
            document.querySelectorAll('.search-toggle').forEach(function(el) {
                el.setAttribute('aria-expanded', 'false');
            });
        }

        function openMobileDrawer() {
            if (mobileDrawer) mobileDrawer.classList.add('open');
        }

        function closeMobileDrawer() {
            if (mobileDrawer) mobileDrawer.classList.remove('open');
        }

        function escapeHtml(value) {
            return String(value || '').replace(/[&<>"']/g, function(ch) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                }[ch];
            });
        }

        function storyStatusLabel(status) {
            if (status === 'completed') return 'Hoàn thành';
            if (status === 'dropped') return 'Ngừng';
            return 'Đang ra';
        }

        function renderSearchResults(data) {
            if (!searchResults) return;
            var stories = data && Array.isArray(data.stories) ? data.stories : [];
            if (!stories.length) {
                searchResults.innerHTML = '<div class="search-empty">Không tìm thấy truyện phù hợp.</div>';
                return;
            }

            searchResults.innerHTML = '<div class="search-results-title">Truyện</div>' + stories.map(function(story) {
                var slug = encodeURIComponent(story.slug || '');
                var cover = story.cover_url || '';
                var meta = [
                    story.author_name || '',
                    story.total_chapters ? (story.total_chapters + ' chương') : '',
                    story.average_rating ? (window.hdkIcon('star') + ' ' + parseFloat(story.average_rating).toFixed(1)) : ''
                ].filter(Boolean).join(' · ');
                return '<a class="search-result-card" href="' + siteUrl(slug) + '">' +
                    '<img src="' + escapeHtml(cover) + '" alt="' + escapeHtml(story.title) + '">' +
                    '<span class="search-result-copy">' +
                        '<strong>' + escapeHtml(story.title) + '</strong>' +
                        '<small>' + escapeHtml(meta || storyStatusLabel(story.status)) + '</small>' +
                    '</span>' +
                '</a>';
            }).join('');
        }

        function runSearch(query) {
            if (!searchStatus || !searchResults) return;
            var q = (query || '').trim();
            if (q.length < 2) {
                lastSearch = '';
                searchStatus.textContent = 'Nhập ít nhất 2 ký tự để tìm truyện.';
                searchResults.innerHTML = '';
                return;
            }
            if (q === lastSearch) return;
            lastSearch = q;
            searchStatus.textContent = 'Đang tìm...';
            fetch(apiUrl('/search?q=') + encodeURIComponent(q), {
                headers: restHeaders()
            })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    searchStatus.textContent = 'Kết quả nhanh';
                    renderSearchResults(data);
                })
                .catch(function() {
                    searchStatus.textContent = 'Không thể tải kết quả tìm kiếm.';
                    searchResults.innerHTML = '';
                });
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

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                if (searchTimer) clearTimeout(searchTimer);
                searchTimer = setTimeout(function() {
                    runSearch(searchInput.value);
                }, 250);
            });
        }

        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                var q = searchInput ? searchInput.value.trim() : '';
                if (!q) {
                    e.preventDefault();
                    if (searchInput) searchInput.focus();
                }
            });
        }

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

        // Bottom nav search button
        var bottomSearchBtn = document.querySelector('[data-bottom-nav-action="search"]');
        if (bottomSearchBtn) {
            bottomSearchBtn.addEventListener('click', function() {
                var searchToggle = document.querySelector('.search-toggle');
                if (searchToggle) searchToggle.click();
            });
        }

        // Bottom nav active state
        (function initBottomNavActive() {
            var items = document.querySelectorAll('.mobile-bottom-nav .bottom-nav-item[href]');
            if (!items.length) return;
            var currentPath = window.location.pathname.replace(/\/$/, '');
            var currentTab = new URLSearchParams(window.location.search).get('tab') || '';
            items.forEach(function(item) {
                var href = item.getAttribute('href');
                if (!href) return;
                var itemUrl;
                try {
                    itemUrl = new URL(href, window.location.origin);
                } catch (e) {
                    return;
                }
                var itemPath = itemUrl.pathname.replace(/\/$/, '');
                var itemTab = itemUrl.searchParams.get('tab') || '';
                var isAccount = itemPath.endsWith('/tai-khoan');
                if (isAccount && itemTab) {
                    item.classList.toggle('active', currentPath === itemPath && currentTab === itemTab);
                    return;
                }
                if (isAccount) {
                    item.classList.toggle('active', currentPath === itemPath && !currentTab);
                    return;
                }
                if (itemPath && (currentPath === itemPath || (itemPath.length > 1 && currentPath.indexOf(itemPath) === 0))) {
                    item.classList.add('active');
                }
            });
        })();
    })();

    // ===== Avatar Upload (Account Settings) =====
    (function initAvatarUpload() {
        var dropzone = document.getElementById('avatar-dropzone');
        var fileInput = document.getElementById('avatar-file-input');
        var preview = document.getElementById('avatar-preview');
        var previewImg = document.getElementById('avatar-preview-img');
        var errorEl = document.getElementById('avatar-error');
        var hiddenUrl = document.getElementById('account-avatar-url');
        var ALLOWED = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        var MAX_SIZE = 3 * 1024 * 1024;

        if (!dropzone || !fileInput || !preview) return;

        function showError(msg) {
            if (!errorEl) return;
            errorEl.textContent = msg;
            errorEl.style.display = 'block';
            dropzone.classList.add('has-error');
        }

        function clearError() {
            if (!errorEl) return;
            errorEl.textContent = '';
            errorEl.style.display = 'none';
            dropzone.classList.remove('has-error');
        }

        function showPreview(file) {
            clearError();
            var reader = new FileReader();
            reader.onload = function(ev) {
                if (!previewImg) {
                    var img = document.createElement('img');
                    img.id = 'avatar-preview-img';
                    img.alt = 'Avatar xem trước';
                    img.src = ev.target.result;
                    preview.innerHTML = '';
                    preview.appendChild(img);
                    previewImg = img;
                } else {
                    previewImg.src = ev.target.result;
                }
                preview.classList.add('has-preview');
                dropzone.classList.add('has-file');
            };
            reader.onerror = function() {
                showError('Không thể đọc file ảnh.');
            };
            reader.readAsDataURL(file);
        }

        function validateFile(file) {
            if (ALLOWED.indexOf(file.type) === -1) {
                showError('Chỉ chấp nhận file JPG, PNG, WebP hoặc GIF.');
                return false;
            }
            if (file.size > MAX_SIZE) {
                showError('Dung lượng tối đa 3MB.');
                return false;
            }
            return true;
        }

        function handleFile(file) {
            if (!validateFile(file)) {
                fileInput.value = '';
                return;
            }
            showPreview(file);
        }

        function setInputFile(file) {
            if (!window.DataTransfer) return false;
            try {
                var dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileInput.files = dataTransfer.files;
                return true;
            } catch (e) {
                return false;
            }
        }

        if (previewImg) {
            preview.classList.add('has-preview');
        }

        // Click dropzone -> open file picker
        dropzone.addEventListener('click', function() {
            fileInput.click();
        });

        dropzone.addEventListener('keydown', function(e) {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            e.preventDefault();
            fileInput.click();
        });

        // File input change
        fileInput.addEventListener('change', function() {
            if (fileInput.files && fileInput.files.length) {
                handleFile(fileInput.files[0]);
            }
        });

        // Drag events
        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('dragover');
        });

        dropzone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dragover');
        });

        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dragover');
            if (e.dataTransfer.files && e.dataTransfer.files.length) {
                var file = e.dataTransfer.files[0];
                if (setInputFile(file)) {
                    handleFile(file);
                } else {
                    showError('Trình duyệt không hỗ trợ kéo thả file, hãy bấm để chọn ảnh.');
                }
            }
        });
    })();

    // ===== Motion Observer =====
    (function initMotion() {
        try {
            var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        } catch (e) {
            var prefersReduced = false;
        }
        if (prefersReduced) {
            document.documentElement.classList.remove('motion-enabled');
            return;
        }
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
    var bannerInfo = document.querySelector('.banner-info');
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

        var bannerPrev = document.querySelector('.banner-nav-prev');
        var bannerNext = document.querySelector('.banner-nav-next');
        if (bannerPrev) {
            bannerPrev.addEventListener('click', function() {
                resetBannerInterval();
                updateBannerActive((bannerActiveIndex - 1 + bannerTotal) % bannerTotal);
            });
        }
        if (bannerNext) {
            bannerNext.addEventListener('click', function() {
                resetBannerInterval();
                updateBannerActive((bannerActiveIndex + 1) % bannerTotal);
            });
        }

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
        if (bannerInfo) {
            bannerInfo.classList.remove('is-changing');
            void bannerInfo.offsetWidth;
            bannerInfo.classList.add('is-changing');
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

        fetch(apiUrl('/stories/' + storyId + '/favorite'), {
            method: 'POST',
            headers: restHeaders()
        })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.dataset.favorited = data.favorited ? '1' : '0';
                btn.innerHTML = window.hdkIcon('heart') + ' ' + (data.favorited ? 'Bỏ yêu thích' : 'Yêu thích');
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

        fetch(apiUrl('/stories/' + storyId + '/rating'), {
            method: 'POST',
            headers: restHeaders('application/json'),
            body: JSON.stringify({ rating: rating })
        })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var stars = widget.querySelectorAll('.star');
                stars.forEach(function(s, i) {
                    s.innerHTML = i < rating ? window.hdkIcon('star-filled', 'hdk-icon-filled') : window.hdkIcon('star');
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

        fetch(apiUrl('/comments'), {
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
        if (!btn.dataset.originalHtml) btn.dataset.originalHtml = btn.innerHTML;
        btn.innerHTML = 'Đang xử lý…';

        fetch(apiUrl('/daily-claim'), {
            method: 'POST',
            headers: restHeaders()
        })
            .then(function(r) { return r.json().then(function(d) { return {status: r.status, data: d}; }); })
            .then(function(result) {
                if (result.status === 200 && result.data.success) {
                    btn.innerHTML = 'Đã nhận +' + result.data.credits_earned + ' Linh Thạch!';
                    btn.style.background = 'var(--color-success)';
                    btn.style.color = 'var(--color-on-success)';
                    btn.style.borderColor = 'var(--color-success)';
                    setTimeout(function() { location.reload(); }, 1500);
                } else if (result.status === 409) {
                    btn.innerHTML = btn.dataset.originalHtml;
                    btn.disabled = true;
                    btn.style.opacity = '0.5';
                } else {
                    btn.innerHTML = btn.dataset.originalHtml;
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
            fetch(apiUrl('/reader-prefs'), {
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
        fetch(apiUrl('/reader-prefs'))
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
            
            fetch(apiUrl('/chapters/') + STORY_ID)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var chapters = data.chapters || [];
                    var currentChapter = parseInt(readerContent.dataset.chapterNumber);
                    var html = '';
                    chapters.forEach(function(ch) {
                        var chapterNumber = parseInt(ch.chapter_number, 10);
                        var cls = 'toc-chapter';
                        if (chapterNumber === currentChapter) cls += ' current';
                    var lockIcon = window.hdkIcon('unlock');
                    if (ch.is_purchased) lockIcon = window.hdkIcon('check');
                    else if (ch.is_locked) lockIcon = window.hdkIcon('lock');
                        var url = siteUrl(storySlug + '?chuong=' + chapterNumber);
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

    // ===== Audio Mini Player =====
    (function initAudioPlayer() {
        var audio = null;
        var player = null;
        var titleEl = null;
        var metaEl = null;
        var playBtn = null;
        var progressEl = null;
        var currentItem = null;

        function ensurePlayer() {
            if (player) return;
            player = document.createElement('div');
            player.className = 'hdk-audio-player';
            player.innerHTML =
                '<div class="hdk-audio-copy">' +
                    '<strong></strong>' +
                    '<span></span>' +
                '</div>' +
                '<button type="button" class="hdk-audio-control" aria-label="Phát hoặc tạm dừng">' + window.hdkIcon('play') + '</button>' +
                '<div class="hdk-audio-progress"><span></span></div>' +
                '<button type="button" class="hdk-audio-close" aria-label="Đóng player">' + window.hdkIcon('x') + '</button>';
            document.body.appendChild(player);
            titleEl = player.querySelector('strong');
            metaEl = player.querySelector('span');
            playBtn = player.querySelector('.hdk-audio-control');
            progressEl = player.querySelector('.hdk-audio-progress span');
            audio = new Audio();

            playBtn.addEventListener('click', function() {
                if (!audio.src) return;
                if (audio.paused) audio.play();
                else audio.pause();
            });
            player.querySelector('.hdk-audio-close').addEventListener('click', function() {
                audio.pause();
                player.classList.remove('active');
            });
            audio.addEventListener('play', function() {
                playBtn.innerHTML = window.hdkIcon('pause');
                if (currentItem) saveListeningHistory(currentItem);
            });
            audio.addEventListener('pause', function() {
                playBtn.innerHTML = window.hdkIcon('play');
            });
            audio.addEventListener('timeupdate', function() {
                if (!audio.duration || !progressEl) return;
                progressEl.style.width = Math.min(100, (audio.currentTime / audio.duration) * 100) + '%';
            });
            audio.addEventListener('ended', function() {
                playBtn.innerHTML = window.hdkIcon('play');
                if (progressEl) progressEl.style.width = '0%';
            });
        }

        function saveListeningHistory(item) {
            if (!window.hdkRestNonce || item.saved) return;
            item.saved = true;
            fetch(apiUrl('/listening-history'), {
                method: 'POST',
                headers: restHeaders('application/json'),
                body: JSON.stringify({
                    title: item.storyTitle || item.audioTitle,
                    url: item.storyUrl || window.location.href,
                    position: item.audioTitle || 'Đang nghe'
                })
            }).catch(function(){});
        }

        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.hdk-audio-play');
            if (!btn) return;
            var src = btn.dataset.audioSrc || '';
            if (!src) return;
            ensurePlayer();
            currentItem = {
                audioTitle: btn.dataset.audioTitle || 'Audio truyện',
                storyTitle: btn.dataset.storyTitle || '',
                storyUrl: btn.dataset.storyUrl || window.location.href,
                saved: false
            };
            titleEl.textContent = currentItem.audioTitle;
            metaEl.textContent = currentItem.storyTitle || 'Hồng Trần Các';
            if (audio.src !== src) {
                audio.src = src;
                if (progressEl) progressEl.style.width = '0%';
            }
            player.classList.add('active');
            audio.play().catch(function() {
                playBtn.innerHTML = window.hdkIcon('play');
            });
        });
    })();

    // ===== Notifications =====
    var notifBell = document.getElementById('notif-bell');
    if (notifBell) {
        function updateUnreadCount() {
            fetch(apiUrl('/notifications/unread-count'))
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
            fetch(apiUrl('/notifications?page=1'))
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
            fetch(apiUrl('/notifications/read'), {
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

    // ===== Female Home Ranking Tabs =====
    (function initFnhRankTabs() {
        var tabs = document.querySelectorAll('.fnh-rank-tab');
        if (!tabs.length) return;

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                var period = this.getAttribute('data-fnh-period');
                if (!period) return;

                // Update tabs
                tabs.forEach(function(t) {
                    t.setAttribute('aria-selected', 'false');
                    t.setAttribute('tabindex', '-1');
                    t.classList.remove('active');
                });
                this.setAttribute('aria-selected', 'true');
                this.setAttribute('tabindex', '0');
                this.classList.add('active');

                // Update panels
                var panels = document.querySelectorAll('.fnh-rank-panel');
                panels.forEach(function(panel) {
                    if (panel.getAttribute('data-fnh-panel') === period) {
                        panel.removeAttribute('hidden');
                        panel.classList.remove('hidden');
                    } else {
                        panel.setAttribute('hidden', '');
                        panel.classList.add('hidden');
                    }
                });
            });

            // Keyboard navigation
            tab.addEventListener('keydown', function(e) {
                var currentIndex = Array.prototype.indexOf.call(tabs, this);
                var nextIndex;
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    nextIndex = (currentIndex + 1) % tabs.length;
                } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    nextIndex = (currentIndex - 1 + tabs.length) % tabs.length;
                }
                if (nextIndex !== undefined) {
                    tabs[nextIndex].focus();
                    tabs[nextIndex].click();
                }
            });
        });
    })();

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

        fetch(apiUrl('/reports'), {
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

    // ===== Floating Header: hide on scroll down, show on scroll up =====
    (function() {
        var header = document.querySelector('.site-header');
        if (!header) return;

        var lastY = window.scrollY || window.pageYOffset;
        var ticking = false;
        var deltaThreshold = 6;      // px of scroll before deciding direction
        var showAtTop = 120;          // keep header visible within this distance from top

        function overlayOpen() {
            if (document.body.classList.contains('search-open')) return true;
            var drawer = document.getElementById('mobile-drawer');
            if (drawer && drawer.classList.contains('open')) return true;
            var notif = document.getElementById('notif-dropdown');
            if (notif && notif.style.display !== 'none' && notif.style.display !== '') return true;
            var userMenu = document.getElementById('user-dropdown-menu');
            if (userMenu && userMenu.style.display !== 'none' && userMenu.style.display !== '') return true;
            return false;
        }

        function update() {
            ticking = false;
            var y = window.scrollY || window.pageYOffset;
            var delta = y - lastY;

            if (y <= showAtTop) {
                header.classList.remove('is-hidden');
            } else if (!overlayOpen()) {
                if (delta > deltaThreshold) {
                    header.classList.add('is-hidden');
                } else if (delta < -deltaThreshold) {
                    header.classList.remove('is-hidden');
                }
            }
            lastY = y;
        }

        function onScroll() {
            if (!ticking) {
                ticking = true;
                window.requestAnimationFrame(update);
            }
        }

        window.addEventListener('scroll', onScroll, { passive: true });
    })();

})();
