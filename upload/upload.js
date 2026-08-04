// upload/upload.js
window.API_BASE = '../MAPI/api.php';

document.getElementById('create-post-form').addEventListener('submit', function (e) {
    e.preventDefault();

    const content = document.getElementById('post-content').value.trim();
    const mediaFile = document.getElementById('post-media').files[0];
    const messageBox = document.getElementById('post-message');

    if (!content) {
        alert('Please write something to post.');
        return;
    }

    const formData = new FormData();
    formData.append('content', content);
    if (mediaFile) {
        formData.append('media', mediaFile);
    }

    fetch(window.API_BASE + '?action=create-post', {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
    })
        .then(function (res) { return res.json(); })
        .then(function (result) {
            if (result.status === 'success') {
                messageBox.textContent = 'Posted! Redirecting to your feed...';
                window.location.href = '../dashboard/dashborad.php';
            } else {
                messageBox.textContent = result.message || 'Could not create post';
            }
        })
        .catch(function (err) {
            console.log('Create post error:', err);
            messageBox.textContent = 'Network error while posting.';
        });
});
