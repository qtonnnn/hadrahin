<?php
header('Content-Type: application/json');

// Include database connection
require_once '../config/database.php';

// Check parameters
if (!isset($_GET['username']) || empty($_GET['username'])) {
    echo json_encode(['exists' => false, 'error' => 'Username tidak valid']);
    exit;
}

$username = trim($_GET['username']);
$excludeId = isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : 0;

try {
    if ($excludeId > 0) {
        // Check username excluding current user
        $stmt = $db->prepare("SELECT id_user FROM user WHERE username = ? AND id_user != ?");
        $stmt->execute([$username, $excludeId]);
    } else {
        // Check username (all users)
        $stmt = $db->prepare("SELECT id_user FROM user WHERE username = ?");
        $stmt->execute([$username]);
    }
    $exists = $stmt->fetch() !== false;
    
    echo json_encode(['exists' => $exists]);
} catch (PDOException $e) {
    error_log("Check username error: " . $e->getMessage());
    echo json_encode(['exists' => false, 'error' => 'Terjadi kesalahan']);
}

