<?php
header('Content-Type: application/json');

// Include database connection
require_once '../config/database.php';

// Check if username parameter is provided
if (!isset($_GET['username']) || empty($_GET['username'])) {
    echo json_encode(['exists' => false, 'error' => 'Username tidak valid']);
    exit;
}

$username = trim($_GET['username']);

try {
    $stmt = $db->prepare("SELECT id_user FROM user WHERE username = ?");
    $stmt->execute([$username]);
    $exists = $stmt->fetch() !== false;
    
    echo json_encode(['exists' => $exists]);
} catch (PDOException $e) {
    // Log error but don't expose details
    error_log("Check username error: " . $e->getMessage());
    echo json_encode(['exists' => false, 'error' => 'Terjadi kesalahan']);
}

