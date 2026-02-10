<?php
/**
 * API: Check Unique Dresscode Name
 * Validasi real-time untuk nama pakaian
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

if (empty($nama_pakaian)) {
    $response['success'] = false;
    $response['message'] = 'Nama pakaian tidak boleh kosong';
    echo json_encode($response);
    exit;
}

// Check if nama_pakaian already exists
$stmt = $pdo->prepare("SELECT id_dresscode FROM dresscode WHERE nama_pakaian = ?");
$stmt->execute([$nama_pakaian]);

if ($stmt->fetch()) {
    $response['exists'] = true;
    $response['message'] = 'Nama pakaian sudah digunakan!';
}

echo json_encode($response);

