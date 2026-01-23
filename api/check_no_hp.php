<?php
header('Content-Type: application/json');

// Include database connection
require_once '../config/database.php';

// Check if no_hp parameter is provided
if (!isset($_GET['no_hp']) || empty($_GET['no_hp'])) {
    echo json_encode(['exists' => false, 'error' => 'Nomor HP tidak valid']);
    exit;
}

$no_hp = trim($_GET['no_hp']);

// Validate format
if (!preg_match('/^[0-9]{10,15}$/', $no_hp)) {
    echo json_encode(['exists' => false, 'error' => 'Format nomor HP tidak valid']);
    exit;
}

// Check if exclude_id parameter is provided (for edit form)
$exclude_id = isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : 0;

try {
    if ($exclude_id > 0) {
        // Exclude current user when checking during edit
        $stmt = $db->prepare("SELECT id_user FROM user WHERE no_hp = ? AND id_user != ?");
        $stmt->execute([$no_hp, $exclude_id]);
    } else {
        $stmt = $db->prepare("SELECT id_user FROM user WHERE no_hp = ?");
        $stmt->execute([$no_hp]);
    }
    
    $exists = $stmt->fetch() !== false;
    
    echo json_encode(['exists' => $exists]);
} catch (PDOException $e) {
    // Log error but don't expose details
    error_log("Check no_hp error: " . $e->getMessage());
    echo json_encode(['exists' => false, 'error' => 'Terjadi kesalahan']);
}

