// login/login.js
const API_BASE = '../MAPI/api.php';

document.getElementById('login-form').addEventListener('submit', function (e) {
    e.preventDefault();

    const data = {
        username: document.getElementById('username').value.trim(),
        password: document.getElementById('password').value
    };

    const messageBox = document.getElementById('login-message');

    fetch(API_BASE + '?action=login', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
        .then(function (res) { return res.json(); })
        .then(function (result) {
            if (result.status === 'success') {
                window.location.href = '../dashboard/dashborad.php';
            } else {
                messageBox.textContent = result.message || 'Login failed';
            }
        })
        .catch(function (err) {
            console.log('Login error:', err);
            messageBox.textContent = 'Network error. Please try again.';
        });
});
