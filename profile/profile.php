<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit();
}
$theme = $_SESSION['theme'] ?? 'light';
$viewedId = isset($_GET['id']) ? (int) $_GET['id'] : (int) $_SESSION['user_id'];
$isOwnProfile = ($viewedId === (int) $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html data-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profile - Msocial</title>
    <link rel="stylesheet" href="../Global.css?v=<?php echo filemtime('../Global.css'); ?>">
    <script>
        window.THEME_API = '../PHPhelper/theme.php';
        window.VIEWED_USER_ID = <?php echo (int) $viewedId; ?>;
        window.IS_OWN_PROFILE = <?php echo $isOwnProfile ? 'true' : 'false'; ?>;
    </script>
    <script defer src="../js/theme.js?v=<?php echo filemtime('../js/theme.js'); ?>"></script>
    <script defer src="../js/postgrid.js"></script>
    <script defer src="profile.js"></script>
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

    <main class="page-container">
        <h2 id="profile-heading">Profile</h2>
        <div id="profile-info" class="profile-card">Loading profile...</div>

        <div id="profile-edit-section" class="profile-card" style="display:none;">
            <h3>Edit my Profile</h3>
            <div id="profile-message"></div>
            <form id="profile-form">
                <div>Bio: <textarea id="bio" placeholder="Tell people about yourself"></textarea></div>
                <div>New profile picture (optional): <input type="file" id="avatar-file" accept="image/*"></div>
                <button type="submit">Save Profile</button>
            </form>
        </div>

        <h2>Posts</h2>
        <div id="user-posts">Loading posts...</div>
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

    <a href="../upload/upload.php" class="fab-button" title="Create Post">+</a>

    <script defer src="../js/global-search.js"></script>
</body>
</html>
