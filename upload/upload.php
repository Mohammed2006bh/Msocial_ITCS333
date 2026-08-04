<?php
require_once __DIR__ . '/../PHPhelper/auth_check.php';
$theme = $_SESSION['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html data-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Post - Msocial</title>
    <link rel="stylesheet" href="../Global.css?v=<?php echo filemtime('../Global.css'); ?>">
    <script>window.THEME_API = '../PHPhelper/theme.php';</script>
    <script defer src="../js/theme.js?v=<?php echo filemtime('../js/theme.js'); ?>"></script>
    <script defer src="upload.js"></script>
</head>
<body>
    <header class="app-header">
        <div class="bar-wing bar-wing-left" aria-hidden="true"></div>
        <div class="bar-pill">
            <a href="../dashboard/dashborad.php"><img src="../assets/HMS_logo.png" class="logo-img" alt="Msocial"></a>
            <form id="global-search-form" class="header-search">
                <input type="text" id="global-search-input" placeholder="Search...">
                <button type="submit" aria-label="Search">&#128269;</button>
            </form>
            <label id="mobile-menu-toggle" class="hamburger-btn" aria-label="Menu">
                <input type="checkbox" aria-expanded="false">
                <span class="top"></span>
                <span class="mid"></span>
                <span class="bot"></span>
            </label>
            <nav id="mobile-menu" class="mobile-menu" aria-hidden="true">
                <a href="../top_profile/top_profile.php">Top Profiles</a>
                <label id="theme-toggle" class="theme-switch">
                    <input type="checkbox">
                    <span class="sr-only">Dark mode</span>
                    <span class="toggle"><span class="indicator"></span></span>
                </label>
                <a href="#" id="logout-link">Logout</a>
            </nav>
        </div>
        <div class="bar-wing bar-wing-right" aria-hidden="true"></div>
    </header>

    <main class="auth-page">
        <div class="form-card">
            <h2>Create a new post</h2>
            <form id="create-post-form">
                <div><textarea id="post-content" placeholder="What's on your mind?" required></textarea></div>
                <div>Attach an image or video (optional): <input type="file" id="post-media" accept="image/*, video/*"></div>
                <button type="submit">Post</button>
            </form>

            <div id="post-message"></div>
        </div>
    </main>

    <nav class="bottom-nav-island" aria-label="Main navigation">
        <div class="bar-wing bar-wing-left" aria-hidden="true"></div>
        <div class="bar-pill">
            <a href="../dashboard/dashborad.php">Dashboard</a>
            <a href="../upload/upload.php">Create New Post</a>
            <a href="../profile/profile.php">My Profile</a>
        </div>
        <div class="bar-wing bar-wing-right" aria-hidden="true"></div>
    </nav>

    <script defer src="../js/global-search.js"></script>
</body>
</html>
