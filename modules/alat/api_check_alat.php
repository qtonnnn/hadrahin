<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

// Get input
$nama_alat = trim($_GET['nama_alat'] ?? '');
$exclude_id = isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : 0;
$mode = $_GET['mode'] ?? 'add'; // 'add' or 'edit'

$response = [
    'valid' => true,
    'message' => ''
];

// Validate
if (empty($nama_alat)) {
    $response['valid'] = false;
    $response['message'] = 'Nama alat wajib diisi';
} elseif (strlen($nama_alat) < 2) {
    $response['valid'] = false;
    $response['message'] = 'Nama alat minimal 2 karakter';
} elseif (strlen($nama_alat) > 100) {
    $response['valid'] = false;
    $response['message'] = 'Nama alat maksimal 100 karakter';
} else {
    // Check duplicate
    if ($mode === 'edit' && $exclude_id > 0) {
        $stmt = $pdo->prepare("SELECT id_alat FROM alat WHERE nama_alat = ? AND id_alat != ?");
        $stmt->execute([$nama_alat, $exclude_id]);
    } else {
        $stmt = $pdo->prepare("SELECT id_alat FROM alat WHERE nama_alat = ?");
        $stmt->execute([$nama_alat]);
    }
    
    if ($stmt->rowCount() > 0) {
        $response['valid'] = false;
        $response['message'] = 'Nama alat sudah digunakan';
    } else {
        $response['valid'] = true;
        $response['message'] = 'Nama alat tersedia';
    }
}

echo json_encode($response);
exit;

