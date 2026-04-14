<?php
/**
 * API: Check Duplicate Jadwal Latihan
 * Validasi realtime untuk tanggal dan jam jadwal latihan
 */

require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Set response type to JSON
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit;
}

// Get input data
$tanggal = $_POST['tanggal'] ?? '';
$jam_mulai = $_POST['jam_mulai'] ?? '';
$exclude_id = isset($_POST['exclude_id']) ? (int)$_POST['exclude_id'] : 0;

// Validate input
if (empty($tanggal) || empty($jam_mulai)) {
    echo json_encode(['success' => false, 'message' => 'Tanggal dan jam wajib diisi']);
    exit;
}

// Check for duplicate jadwal
try {
    if ($exclude_id > 0) {
        // Exclude current jadwal (for edit mode)
        $stmt = $pdo->prepare("SELECT id_jadwal FROM jadwal_latihan WHERE tanggal = ? AND jam_mulai = ? AND id_jadwal != ?");
        $stmt->execute([$tanggal, $jam_mulai, $exclude_id]);
    } else {
        // Check all jadwal (for add mode)
        $stmt = $pdo->prepare("SELECT id_jadwal FROM jadwal_latihan WHERE tanggal = ? AND jam_mulai = ?");
        $stmt->execute([$tanggal, $jam_mulai]);
    }
    
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo json_encode([
            'success' => true,
            'exists' => true,
            'message' => 'Jadwal latihan pada tanggal dan jam tersebut sudah ada!'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'exists' => false,
            'message' => 'Jadwal tersedia'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error database: ' . $e->getMessage()]);
}

