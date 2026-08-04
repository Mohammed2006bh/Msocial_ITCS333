// profile/profile.js
window.API_BASE = '../MAPI/api.php';

const postsBox = document.getElementById('user-posts');
const mobileQuery = window.matchMedia('(max-width: 768px)');
let currentPosts = [];

function applyLayout(posts) {
    if (mobileQuery.matches) {
        PostGrid.renderFeed(posts, postsBox, loadProfile);
    } else {
        PostGrid.renderGrid(posts, postsBox, loadProfile);
    }
}

function loadProfile() {
    const infoBox = document.getElementById('profile-info');
    postsBox.textContent = 'Loading posts...';
    infoBox.textContent = 'Loading profile...';

    fetch(window.API_BASE + '?action=user-profile&id=' + window.VIEWED_USER_ID, { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (result) {
            if (result.status !== 'success') {
                infoBox.textContent = result.message || 'Could not load profile';
                return;
            }
            renderProfile(result.data);
        })
        .catch(function (err) {
            console.log('Load profile error:', err);
            infoBox.textContent = 'Network error while loading profile.';
        });
}

function renderProfile(data) {
    const infoBox = document.getElementById('profile-info');
    const heading = document.getElementById('profile-heading');
    const user = data.user;

    heading.textContent = (data.is_self ? 'My Profile - ' : 'Profile - ') + user.username;

    let html =
        '<img src="' + PostGrid.avatarUrl(user.avatar) + '" width="80" height="80"><br>' +
        '<p><strong>' + PostGrid.escapeHtml(user.username) + '</strong></p>' +
        '<p>' + PostGrid.escapeHtml(user.bio || 'No bio yet.') + '</p>' +
        '<p>Followers: <span id="followers-count">' + data.followers_count + '</span></p>' +
        '<p>Total likes received: ' + data.total_likes + '</p>';

    if (!data.is_self) {
        html += '<button id="follow-btn">' + (data.is_following ? 'Unfollow' : 'Follow') + '</button>';
    }

    infoBox.innerHTML = html;

    const followBtn = document.getElementById('follow-btn');
    if (followBtn) {
        followBtn.addEventListener('click', function () {
            const action = data.is_following ? 'unfollow' : 'follow';
            fetch(window.API_BASE + '?action=' + action + '&id=' + user.id, {
                method: 'POST',
                credentials: 'same-origin'
            })
                .then(function (res) { return res.json(); })
                .then(function () { loadProfile(); })
                .catch(function (err) { console.log('Follow error:', err); });
        });
    }

    if (data.is_self) {
        document.getElementById('profile-edit-section').style.display = 'block';
        document.getElementById('bio').value = user.bio || '';
    }

    currentPosts = data.posts;
    applyLayout(currentPosts);
}

mobileQuery.addEventListener('change', function () {
    if (currentPosts.length) {
        applyLayout(currentPosts);
    }
});

if (window.IS_OWN_PROFILE) {
    document.getElementById('profile-form').addEventListener('submit', function (e) {
        e.preventDefault();

        const bio = document.getElementById('bio').value.trim();
        const avatarFile = document.getElementById('avatar-file').files[0];
        const messageBox = document.getElementById('profile-message');

        const formData = new FormData();
        formData.append('bio', bio);
        if (avatarFile) {
            formData.append('avatar', avatarFile);
        }

        fetch(window.API_BASE + '?action=update-profile', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
            .then(function (res) { return res.json(); })
            .then(function (result) {
                messageBox.textContent = result.message || '';
                loadProfile();
            })
            .catch(function (err) {
                console.log('Update profile error:', err);
                messageBox.textContent = 'Network error. Please try again.';
            });
    });
}

loadProfile();
