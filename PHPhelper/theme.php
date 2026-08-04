<?php
// PHPhelper/theme.php
// Small endpoint used by js/theme.js to toggle the dark/light mode and
// persist the choice inside the PHP session so it survives page reloads
// and navigation across the whole site.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'light';
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'toggle') {
    $_SESSION['theme'] = ($_SESSION['theme'] === 'dark') ? 'light' : 'dark';
}

echo json_encode(['status' => 'success', 'theme' => $_SESSION['theme']]);
?>
