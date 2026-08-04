// js/postgrid.js
// Shared grid rendering + popup (video/image, publisher link, comments,
// like, delete) used by dashboard.js, profile.js and search.js.
//
// Requires the page to define:
//   window.API_BASE   -> relative path to MAPI/api.php
//   a container element passed to renderGrid()

(function () {
    const DEFAULT_AVATAR = '../assets/HMS_logo.png';

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text == null ? '' : text;
        return div.innerHTML;
    }

    function avatarUrl(avatar) {
        return avatar ? '../ALLupload/' + encodeURIComponent(avatar) : DEFAULT_AVATAR;
    }

    function mediaUrl(filename) {
        return '../ALLupload/' + encodeURIComponent(filename);
    }

    // Renders a list of posts as a grid of clickable cells.
    function renderGrid(posts, containerEl, onChanged) {
        containerEl.innerHTML = '';

        if (!posts.length) {
            containerEl.textContent = 'No posts yet.';
            return;
        }

        const grid = document.createElement('div');
        grid.className = 'feed-grid';

        posts.forEach(function (post) {
            const cell = document.createElement('div');
            cell.className = 'grid-cell';
            cell.setAttribute('data-id', post.id);

            if (post.media_type === 'video' && post.image) {
                cell.innerHTML =
                    '<video src="' + mediaUrl(post.image) + '" muted autoplay loop playsinline></video>' +
                    '<span class="like-badge">' + post.like_count + ' likes</span>';
            } else if (post.media_type === 'image' && post.image) {
                cell.innerHTML =
                    '<img src="' + mediaUrl(post.image) + '" alt="post image">' +
                    '<span class="like-badge">' + post.like_count + ' likes</span>';
            } else {
                cell.innerHTML =
                    '<div class="text-cell">' + escapeHtml(post.content) + '</div>' +
                    '<span class="like-badge">' + post.like_count + ' likes</span>';
            }

            cell.addEventListener('click', function () {
                openPostPopup(post.id, onChanged);
            });

            grid.appendChild(cell);
        });

        containerEl.appendChild(grid);
    }

    function closePopup() {
        const overlay = document.getElementById('post-popup-overlay');
        if (overlay) overlay.remove();
    }

    function openPostPopup(postId, onChanged) {
        fetch(window.API_BASE + '?action=get-post&id=' + postId, { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (result) {
                if (result.status !== 'success') {
                    alert(result.message || 'Could not load the post');
                    return;
                }
                renderPopup(result.data.post, result.data.comments, onChanged);
            })
            .catch(function (err) {
                console.log('Get post error:', err);
                alert('Network error while loading the post.');
            });
    }

    function renderPopup(post, comments, onChanged) {
        closePopup();

        const overlay = document.createElement('div');
        overlay.id = 'post-popup-overlay';
        overlay.className = 'popup-overlay';

        let mediaHtml = '';
        if (post.media_type === 'video' && post.image) {
            mediaHtml = '<video src="' + mediaUrl(post.image) + '" controls autoplay style="width:100%"></video>';
        } else if (post.media_type === 'image' && post.image) {
            mediaHtml = '<img src="' + mediaUrl(post.image) + '" style="width:100%">';
        }

        let commentsHtml = '';
        comments.forEach(function (c) {
            commentsHtml += '<div class="comment" data-id="' + c.id + '">' +
                '<img src="' + avatarUrl(c.avatar) + '" width="24" height="24"> ' +
                '<strong>' + escapeHtml(c.username) + '</strong>: ' + escapeHtml(c.content) +
                (c.is_owner ? ' <button class="delete-comment-btn" data-id="' + c.id + '">x</button>' : '') +
                '</div>';
        });
        if (!comments.length) {
            commentsHtml = '<p>No comments yet.</p>';
        }

        overlay.innerHTML =
            '<div class="popup-box">' +
                '<button id="popup-close-btn" type="button">Close</button>' +
                mediaHtml +
                '<p>' + escapeHtml(post.content) + '</p>' +
                '<p><a href="../profile/profile.php?id=' + post.user_id + '">' +
                    '<img src="' + avatarUrl(post.avatar) + '" width="32" height="32"> ' +
                    '<strong>' + escapeHtml(post.username) + '</strong>' +
                '</a></p>' +
                '<p>' +
                    '<span class="like-count">' + post.like_count + '</span> likes ' +
                    '<button id="popup-like-btn">' + (post.is_liked ? 'Unlike' : 'Like') + '</button> ' +
                    (post.is_owner ? '<button id="popup-delete-btn">Delete Post</button>' : '') +
                '</p>' +
                '<h3>Comments</h3>' +
                '<div id="popup-comments">' + commentsHtml + '</div>' +
                '<form id="popup-comment-form">' +
                    '<input type="text" id="popup-comment-input" placeholder="Add a comment..." required>' +
                    '<button type="submit">Send</button>' +
                '</form>' +
            '</div>';

        document.body.appendChild(overlay);

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closePopup();
        });
        document.getElementById('popup-close-btn').addEventListener('click', closePopup);

        document.getElementById('popup-like-btn').addEventListener('click', function () {
            const action = post.is_liked ? 'unlike' : 'like';
            fetch(window.API_BASE + '?action=' + action + '&id=' + post.id, {
                method: 'POST',
                credentials: 'same-origin'
            })
                .then(function (res) { return res.json(); })
                .then(function () {
                    if (onChanged) onChanged();
                    openPostPopup(post.id, onChanged);
                })
                .catch(function (err) { console.log('Like error:', err); });
        });

        const deleteBtn = document.getElementById('popup-delete-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function () {
                if (!confirm('Are you sure you want to delete this post?')) return;
                fetch(window.API_BASE + '?action=delete-post&id=' + post.id, {
                    method: 'POST',
                    credentials: 'same-origin'
                })
                    .then(function (res) { return res.json(); })
                    .then(function () {
                        closePopup();
                        if (onChanged) onChanged();
                    })
                    .catch(function (err) { console.log('Delete post error:', err); });
            });
        }

        document.querySelectorAll('.delete-comment-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const commentId = btn.getAttribute('data-id');
                fetch(window.API_BASE + '?action=delete-comment&id=' + commentId, {
                    method: 'POST',
                    credentials: 'same-origin'
                })
                    .then(function (res) { return res.json(); })
                    .then(function () { openPostPopup(post.id, onChanged); })
                    .catch(function (err) { console.log('Delete comment error:', err); });
            });
        });

        document.getElementById('popup-comment-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const input = document.getElementById('popup-comment-input');
            const content = input.value.trim();
            if (!content) return;

            fetch(window.API_BASE + '?action=add-comment', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ post_id: post.id, content: content })
            })
                .then(function (res) { return res.json(); })
                .then(function (result) {
                    if (result.status === 'success') {
                        input.value = '';
                        openPostPopup(post.id, onChanged);
                    } else {
                        alert(result.message || 'Could not add comment');
                    }
                })
                .catch(function (err) { console.log('Add comment error:', err); });
        });
    }

    // Renders a vertical single-column feed of full post cards (mobile view).
    function renderFeed(posts, containerEl, onChanged) {
        containerEl.innerHTML = '';

        if (!posts.length) {
            containerEl.textContent = 'No posts yet.';
            return;
        }

        const list = document.createElement('div');
        list.className = 'feed-list';

        posts.forEach(function (post) {
            const article = document.createElement('article');
            article.className = 'feed-post';
            article.setAttribute('data-id', post.id);

            // Media
            let mediaHtml = '';
            if (post.media_type === 'video' && post.image) {
                mediaHtml = '<div class="feed-post-media">' +
                    '<video src="' + mediaUrl(post.image) + '" muted autoplay loop playsinline></video>' +
                    '</div>';
            } else if (post.media_type === 'image' && post.image) {
                mediaHtml = '<div class="feed-post-media">' +
                    '<img src="' + mediaUrl(post.image) + '" alt="post image">' +
                    '</div>';
            }

            // Recent comments (newest 3)
            let commentsHtml = '';
            const recentComments = (post.comments || []).slice(-3).reverse();
            recentComments.forEach(function (c) {
                commentsHtml += '<div class="feed-comment" data-id="' + c.id + '">' +
                    '<img src="' + avatarUrl(c.avatar) + '" width="28" height="28" alt="">' +
                    '<div class="feed-comment-content">' +
                        '<strong>' + escapeHtml(c.username) + '</strong>' +
                        escapeHtml(c.content) +
                        (c.is_owner ? ' <button class="delete-comment-btn" data-id="' + c.id + '">x</button>' : '') +
                    '</div>' +
                '</div>';
            });
            if (!recentComments.length) {
                commentsHtml = '<p style="font-size:13px;color:var(--text);opacity:0.7;margin:0 0 10px;">No comments yet.</p>';
            }

            article.innerHTML =
                mediaHtml +
                '<div class="feed-post-body">' +
                    '<div class="feed-post-header">' +
                        '<img src="' + avatarUrl(post.avatar) + '" width="36" height="36" alt="">' +
                        '<a href="../profile/profile.php?id=' + post.user_id + '">' + escapeHtml(post.username) + '</a>' +
                    '</div>' +
                    '<div class="feed-post-content">' + escapeHtml(post.content) + '</div>' +
                    '<div class="feed-post-actions">' +
                        '<button class="feed-like-btn">' + (post.is_liked ? 'Unlike' : 'Like') + '</button>' +
                        '<span class="feed-like-count">' + post.like_count + ' likes</span>' +
                        (post.is_owner ? ' <button class="delete-post-btn">Delete Post</button>' : '') +
                    '</div>' +
                    '<div class="feed-post-comments">' +
                        '<h4>Recent comments</h4>' +
                        '<div class="feed-comments-list">' + commentsHtml + '</div>' +
                        '<form class="feed-add-comment">' +
                            '<input type="text" placeholder="Add a comment..." required>' +
                            '<button type="submit">Send</button>' +
                        '</form>' +
                    '</div>' +
                '</div>';

            // Media click -> open popup (for video controls / full image)
            const mediaEl = article.querySelector('.feed-post-media');
            if (mediaEl) {
                mediaEl.addEventListener('click', function () {
                    openPostPopup(post.id, onChanged);
                });
            }

            // Like
            const likeBtn = article.querySelector('.feed-like-btn');
            likeBtn.addEventListener('click', function () {
                const action = post.is_liked ? 'unlike' : 'like';
                fetch(window.API_BASE + '?action=' + action + '&id=' + post.id, {
                    method: 'POST',
                    credentials: 'same-origin'
                })
                    .then(function (res) { return res.json(); })
                    .then(function (result) {
                        if (result.status === 'success') {
                            post.is_liked = !post.is_liked;
                            post.like_count = post.is_liked ? post.like_count + 1 : post.like_count - 1;
                            likeBtn.textContent = post.is_liked ? 'Unlike' : 'Like';
                            article.querySelector('.feed-like-count').textContent = post.like_count + ' likes';
                            if (onChanged) onChanged();
                        }
                    })
                    .catch(function (err) { console.log('Like error:', err); });
            });

            // Delete post
            const deleteBtn = article.querySelector('.delete-post-btn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function () {
                    if (!confirm('Are you sure you want to delete this post?')) return;
                    fetch(window.API_BASE + '?action=delete-post&id=' + post.id, {
                        method: 'POST',
                        credentials: 'same-origin'
                    })
                        .then(function (res) { return res.json(); })
                        .then(function () {
                            article.remove();
                            if (onChanged) onChanged();
                        })
                        .catch(function (err) { console.log('Delete post error:', err); });
                });
            }

            // Delete comment
            article.querySelectorAll('.delete-comment-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const commentId = btn.getAttribute('data-id');
                    fetch(window.API_BASE + '?action=delete-comment&id=' + commentId, {
                        method: 'POST',
                        credentials: 'same-origin'
                    })
                        .then(function (res) { return res.json(); })
                        .then(function () {
                            // Refresh this post card to update comments
                            refreshPostCard(post, article, onChanged);
                        })
                        .catch(function (err) { console.log('Delete comment error:', err); });
                });
            });

            // Add comment
            const commentForm = article.querySelector('.feed-add-comment');
            commentForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const input = commentForm.querySelector('input');
                const content = input.value.trim();
                if (!content) return;

                fetch(window.API_BASE + '?action=add-comment', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ post_id: post.id, content: content })
                })
                    .then(function (res) { return res.json(); })
                    .then(function (result) {
                        if (result.status === 'success') {
                            input.value = '';
                            refreshPostCard(post, article, onChanged);
                        } else {
                            alert(result.message || 'Could not add comment');
                        }
                    })
                    .catch(function (err) { console.log('Add comment error:', err); });
            });

            list.appendChild(article);
        });

        containerEl.appendChild(list);
    }

    // Refreshes a single feed-post card after comment changes.
    function refreshPostCard(post, articleEl, onChanged) {
        fetch(window.API_BASE + '?action=get-post&id=' + post.id, { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (result) {
                if (result.status !== 'success') return;
                const updated = result.data.post;
                updated.comments = result.data.comments;
                // Update local post object reference
                Object.assign(post, updated);
                // Re-render this card by replacing innerHTML and rebinding events is complex;
                // simpler: replace the whole card with a fresh render of this single post.
                const container = articleEl.parentNode;
                const tempContainer = document.createElement('div');
                renderFeed([updated], tempContainer, onChanged);
                const newArticle = tempContainer.querySelector('.feed-post');
                if (newArticle) {
                    container.replaceChild(newArticle, articleEl);
                }
                if (onChanged) onChanged();
            })
            .catch(function (err) { console.log('Refresh post error:', err); });
    }

    window.PostGrid = {
        renderGrid: renderGrid,
        renderFeed: renderFeed,
        openPostPopup: openPostPopup,
        escapeHtml: escapeHtml,
        avatarUrl: avatarUrl,
        mediaUrl: mediaUrl
    };
})();
