<?php
// PHPhelper/auth_check.php
// Include this file at the top of any page that requires a logged-in user.
// It starts the session (if not already started) and redirects to the login
// page when there is no active user session.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    // This file is included from a feature folder one level below the
    // project root (e.g. dashboard/, profile/), so the login page is
    // reached by going one level up first.
    header("Location: ../login/login.php");
    exit();
}
?>
