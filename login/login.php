<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$theme = $_SESSION['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html data-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Msocial</title>
    <link rel="stylesheet" href="../Global.css?v=<?php echo filemtime('../Global.css'); ?>">
    <script>window.THEME_API = '../PHPhelper/theme.php';</script>
    <script defer src="../js/theme.js?v=<?php echo filemtime('../js/theme.js'); ?>"></script>
    <script defer src="login.js"></script>
</head>
<body>
    <header class="app-header">
        <div class="bar-wing bar-wing-left" aria-hidden="true"></div>
        <div class="bar-pill">
            <a href="../login/login.php"><img src="../assets/HMS_logo.png" class="logo-img" alt="Msocial"></a>
            <label id="mobile-menu-toggle" class="hamburger-btn" aria-label="Menu">
                <input type="checkbox" aria-expanded="false">
                <span class="top"></span>
                <span class="mid"></span>
                <span class="bot"></span>
            </label>
            <nav id="mobile-menu" class="mobile-menu" aria-hidden="true">
                <label id="theme-toggle" class="theme-switch">
                    <input type="checkbox">
                    <span class="sr-only">Dark mode</span>
                    <span class="toggle"><span class="indicator"></span></span>
                </label>
            </nav>
        </div>
        <div class="bar-wing bar-wing-right" aria-hidden="true"></div>
    </header>

    <main class="auth-page">
        <div class="auth-card">
            <h2>Login</h2>
            <div id="login-message"></div>

            <form id="login-form">
                <div><input id="username" placeholder="Username" required></div>
                <div><input type="password" id="password" placeholder="Password" required></div>
                <label class="neu-checkbox">
                    <input type="checkbox" id="remember-me">
                    <span class="checkmark"></span>
                    Remember me
                </label>
                <button type="submit">Login</button>
            </form>

            <p>No account? <a href="../register/register.php">Register here</a></p>
        </div>
    </main>
</body>
</html>
