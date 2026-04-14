<?php
/**
 * API: Check Unique Dresscode Name (for Edit)
 * Validasi real-time untuk nama pakaian (exclude current id)
 */

require_once '../../config/database.php';
require_once '../../includes/auth_check.php';

header('Content-Type: application/json');

$response = [
    'success' => true,
    'exists' => false,
    'message' => ''
];

$nama_pakaian = isset($_GET['nama_pakaian']) ? trim($_GET['nama_pakaian']) : '';
$exclude_id = isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : 0;

if (empty($nama_pakaian)) {
    $response['success'] = false;
    $response['message'] = 'Nama pakaian tidak boleh kosong';
    echo json_encode($response);
    exit;
}

// Check if nama_pakaian already exists (exclude current dresscode)
if ($exclude_id > 0) {
    $stmt = $pdo->prepare("SELECT id_dresscode FROM dresscode WHERE nama_pakaian = ? AND id_dresscode != ?");
    $stmt->execute([$nama_pakaian, $exclude_id]);
} else {
    $stmt = $pdo->prepare("SELECT id_dresscode FROM dresscode WHERE nama_pakaian = ?");
    $stmt->execute([$nama_pakaian]);
}

if ($stmt->fetch()) {
    $response['exists'] = true;
    $response['message'] = 'Nama pakaian sudah digunakan!';
}

echo json_encode($response);

