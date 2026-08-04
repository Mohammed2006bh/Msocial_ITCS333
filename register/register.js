// register/register.js
const API_BASE = '../MAPI/api.php';

document.getElementById('register-form').addEventListener('submit', function (e) {
    e.preventDefault();

    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm-password').value;
    const bio = document.getElementById('bio').value.trim();
    const avatarFile = document.getElementById('avatar-file').files[0];
    const messageBox = document.getElementById('register-message');

    if (password !== confirmPassword) {
        messageBox.textContent = 'Password and Confirm password do not match!';
        return;
    }

    const formData = new FormData();
    formData.append('username', username);
    formData.append('password', password);
    formData.append('bio', bio);
    if (avatarFile) {
        formData.append('avatar', avatarFile);
    }

    fetch(API_BASE + '?action=register', {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
    })
        .then(function (res) { return res.json(); })
        .then(function (result) {
            messageBox.textContent = result.message || '';
            if (result.status === 'success') {
                window.location.href = '../login/login.php';
            }
        })
        .catch(function (err) {
            console.log('Register error:', err);
            messageBox.textContent = 'Network error. Please try again.';
        });
});
