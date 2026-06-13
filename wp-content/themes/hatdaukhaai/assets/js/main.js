/**
 * Hạt Đậu Khả Ái - Main JavaScript
 * Handles: favorites, ratings, comments, dark mode toggle
 */

(function() {
    'use strict';

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
                btn.textContent = data.favorited ? '❤️ Bỏ yêu thích' : '🤍 Yêu thích';
                if (data.total_favorites !== undefined) {
                    var countEl = document.querySelector('.favorite-count');
                    if (countEl) countEl.textContent = data.total_favorites;
                }
            })
            .catch(function(err) {
                window.location.href = '/wp-login.php';
            });
    });

    // Star rating
    document.addEventListener('click', function(e) {
        var star = e.target.closest('.star');
        if (!star) return;

        var rating = parseInt(star.dataset.value);
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
                // Update stars visually
                var stars = widget.querySelectorAll('.star');
                stars.forEach(function(s, i) {
                    s.textContent = i < rating ? '★' : '☆';
                });
                // Update text
                var textEl = widget.querySelector('span:last-child');
                if (textEl && data.average_rating) {
                    textEl.textContent = '(' + data.average_rating + ' - ' + data.total_ratings + ' đánh giá)';
                }
            })
            .catch(function(err) {
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
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Đang gửi...'; }

        fetch('/wp-json/hdk/v1/comments', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ story_id: parseInt(storyId), chapter_number: 0, content: content })
        })
            .then(function(r) { return r.json(); })
            .then(function() {
                textarea.value = '';
                if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Gửi bình luận'; }
                // Reload comments (simple approach)
                location.reload();
            })
            .catch(function(err) {
                if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Gửi bình luận'; }
                window.location.href = '/wp-login.php';
            });
    });
})();
