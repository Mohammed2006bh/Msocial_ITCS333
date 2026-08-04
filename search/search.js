// search/search.js
window.API_BASE = '../MAPI/api.php';

const searchForm = document.getElementById('global-search-form');
const searchInput = document.getElementById('global-search-input');
const postsBox = document.getElementById('posts-result');
const mobileQuery = window.matchMedia('(max-width: 768px)');
let currentPosts = [];

function applyLayout(posts) {
    if (mobileQuery.matches) {
        PostGrid.renderFeed(posts, postsBox);
    } else {
        PostGrid.renderGrid(posts, postsBox);
    }
}

function runSearch(query) {
    const usersBox = document.getElementById('users-result');

    if (!query) {
        usersBox.textContent = 'Type something and search.';
        postsBox.textContent = 'Type something and search.';
        currentPosts = [];
        return;
    }

    usersBox.textContent = 'Loading...';
    postsBox.textContent = 'Loading...';

    fetch(window.API_BASE + '?action=search&search=' + encodeURIComponent(query), {
        credentials: 'same-origin'
    })
        .then(function (res) { return res.json(); })
        .then(function (result) {
            if (result.status !== 'success') {
                usersBox.textContent = result.message || 'Search failed';
                postsBox.textContent = '';
                currentPosts = [];
                return;
            }
            renderUsers(result.data.users);
            currentPosts = result.data.posts;
            applyLayout(currentPosts);
        })
        .catch(function (err) {
            console.log('Search error:', err);
            usersBox.textContent = 'Network error.';
        });
}

mobileQuery.addEventListener('change', function () {
    if (currentPosts.length || postsBox.textContent !== 'Type something and search.') {
        applyLayout(currentPosts);
    }
});

searchForm.addEventListener('submit', function (e) {
    e.preventDefault();
    runSearch(searchInput.value.trim());
});

function renderUsers(users) {
    const box = document.getElementById('users-result');
    if (!users.length) {
        box.textContent = 'No users found.';
        return;
    }
    let html = '<ul>';
    users.forEach(function (user) {
        html += '<li><a href="../profile/profile.php?id=' + user.id + '">' +
            PostGrid.escapeHtml(user.username) + '</a> - ' + PostGrid.escapeHtml(user.bio || '') + '</li>';
    });
    html += '</ul>';
    box.innerHTML = html;
}

const params = new URLSearchParams(window.location.search);
const initialQuery = params.get('q');
if (initialQuery) {
    searchInput.value = initialQuery;
    runSearch(initialQuery.trim());
}
