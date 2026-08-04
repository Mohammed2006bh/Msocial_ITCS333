// dashboard/dashboard.js
window.API_BASE = '../MAPI/api.php';

const listBox = document.getElementById('posts-list');
const mobileQuery = window.matchMedia('(max-width: 768px)');
let currentPosts = [];

function applyLayout(posts) {
    if (mobileQuery.matches) {
        PostGrid.renderFeed(posts, listBox, loadPosts);
    } else {
        PostGrid.renderGrid(posts, listBox, loadPosts);
    }
}

function loadPosts() {
    listBox.textContent = 'Loading posts...';

    fetch(window.API_BASE + '?action=list-posts', { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (result) {
            if (result.status !== 'success') {
                listBox.textContent = result.message || 'Could not load posts';
                return;
            }
            currentPosts = result.data;
            applyLayout(currentPosts);
        })
        .catch(function (err) {
            console.log('Load posts error:', err);
            listBox.textContent = 'Network error while loading posts.';
        });
}

mobileQuery.addEventListener('change', function () {
    if (currentPosts.length) {
        applyLayout(currentPosts);
    }
});

loadPosts();
