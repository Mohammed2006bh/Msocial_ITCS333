// top_profile/top_profile.js
const API_BASE = '../MAPI/api.php';

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text == null ? '' : text;
    return div.innerHTML;
}

fetch(API_BASE + '?action=rank', { credentials: 'same-origin' })
    .then(function (res) { return res.json(); })
    .then(function (result) {
        const box = document.getElementById('rank-list');
        if (result.status !== 'success') {
            box.textContent = result.message || 'Could not load ranking';
            return;
        }

        const users = result.data;
        if (!users.length) {
            box.textContent = 'No users yet.';
            return;
        }

        let html = '<ol>';
        users.forEach(function (user) {
            html += '<li><a href="../profile/profile.php?id=' + user.id + '">' +
                escapeHtml(user.username) + '</a> - ' + user.total_likes + ' likes</li>';
        });
        html += '</ol>';
        box.innerHTML = html;
    })
    .catch(function (err) {
        console.log('Rank error:', err);
        document.getElementById('rank-list').textContent = 'Network error.';
    });
