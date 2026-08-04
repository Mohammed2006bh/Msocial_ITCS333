// js/theme.js - dark mode toggle, persisted in the PHP session
// Include this script on every page along with #theme-toggle and #mobile-menu-toggle.
// THEME_API must be defined before this script loads, pointing to
// PHPhelper/theme.php relative to the current page.

document.addEventListener('DOMContentLoaded', function () {
    var themeToggle = document.getElementById('theme-toggle');
    var themeCheckbox = themeToggle ? themeToggle.querySelector('input[type="checkbox"]') : null;
    var themeApi = window.THEME_API || 'PHPhelper/theme.php';

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        if (themeCheckbox) {
            themeCheckbox.checked = (theme === 'dark');
        }
    }

    // Apply theme already set by the server on page load
    applyTheme(document.documentElement.getAttribute('data-theme') || 'light');

    if (themeToggle && themeCheckbox) {
        themeCheckbox.addEventListener('change', function () {
            fetch(themeApi + '?action=toggle', { credentials: 'same-origin' })
                .then(function (res) { return res.json(); })
                .then(function (result) {
                    if (result.status === 'success') {
                        applyTheme(result.theme);
                    }
                })
                .catch(function (err) {
                    console.log('Theme toggle error:', err);
                });
        });
    }

    // Mobile hamburger menu toggle
    var menuToggle = document.getElementById('mobile-menu-toggle');
    var menuCheckbox = menuToggle ? menuToggle.querySelector('input[type="checkbox"]') : null;
    var mobileMenu = document.getElementById('mobile-menu');
    if (menuToggle && menuCheckbox && mobileMenu) {
        function setMenuOpen(isOpen) {
            menuCheckbox.checked = isOpen;
            mobileMenu.classList.toggle('open', isOpen);
            mobileMenu.setAttribute('aria-hidden', String(!isOpen));
            menuToggle.setAttribute('aria-expanded', String(isOpen));
        }

        menuCheckbox.addEventListener('change', function () {
            setMenuOpen(menuCheckbox.checked);
        });

        document.addEventListener('click', function (e) {
            if (!mobileMenu.contains(e.target) && !menuToggle.contains(e.target)) {
                setMenuOpen(false);
            }
        });
    }

    // Logout link (shared across pages)
    var logoutLink = document.getElementById('logout-link');
    if (logoutLink) {
        logoutLink.addEventListener('click', function (e) {
            e.preventDefault();
            var apiBase = window.API_BASE || '../MAPI/api.php';
            fetch(apiBase + '?action=logout', { credentials: 'same-origin' })
                .then(function () { window.location.href = '../login/login.php'; })
                .catch(function (err) { console.log('Logout error:', err); });
        });
    }
});
