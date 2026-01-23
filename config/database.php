<?php
// Base URL Configuration - only define in web context
if (php_sapi_name() !== 'cli' && isset($_SERVER['HTTP_HOST'])) {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/hadrahin';
    define('BASE_URL', $base_url);
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'hadrahin');
define('DB_USER', 'root');
define('DB_PASS', '');

// PDO Connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Also create $db alias for backward compatibility
    $db = $pdo;
} catch (PDOException $e) {
    // Log error ke file, jangan tampilkan detail ke user
    error_log("Database Error: " . $e->getMessage());
    die("Koneksi database gagal. Silakan coba lagi nanti.");
}
?>

