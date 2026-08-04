<?php
// index.php - project entry point
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard/dashborad.php");
} else {
    header("Location: login/login.php");
}
exit();
?>
