<?php
require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables - only call this once
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Use $_ENV to fetch values (more reliable than getenv())
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USERNAME', $_ENV['DB_USERNAME'] ?? '');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');
define('DB_NAME', $_ENV['DB_DATABASE'] ?? '');

// Create database connection
function getDbConnection() {
    try {
        $conn = new PDO("mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

function jsonResponse($success, $data = null, $error = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error
    ]);
    exit;
}

// Session start
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

ini_set('display_errors', 1);
error_reporting(E_ALL);