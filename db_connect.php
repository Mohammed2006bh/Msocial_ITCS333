<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "msocial_db";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Output if fail
    die("Connection failed: " . $e->getMessage());
}
?>
