// js/global-search.js
// Redirects header search submissions on non-search pages to the search page.
(function () {
    const form = document.getElementById('global-search-form');
    const input = document.getElementById('global-search-input');
    if (!form || !input) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const query = input.value.trim();
        if (!query) return;
        window.location.href = '../search/search.php?q=' + encodeURIComponent(query);
    });
})();
