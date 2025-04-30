<?php

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// $debug = false;
// if ($debug) {
//     ini_set('display_errors', 1);
//     ini_set('display_startup_errors', 1);
//     error_reporting(E_ALL);
// }

// if (session_status() == PHP_SESSION_NONE) {
//     session_start();
// }

session_start();

$host = 'localhost';
$dbname = 'gestion_budget';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

?>