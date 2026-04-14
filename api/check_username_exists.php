<?php
/**
 * API Check Username - Endpoint untuk validasi username real-time
 */

// Nonaktifkan display_errors untuk production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Anti-cache headers
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');

// Include database config
require_once '../config/database.php';

$response = [
    'exists' => false,
    'message' => 'Akun tidak ditemukan'
];

try {
    // Validasi method GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Method tidak valid');
    }

    // Validasi input
    $username = isset($_GET['username']) ? trim($_GET['username']) : '';

    if (empty($username)) {
        $response = [
            'exists' => false,
            'message' => 'Nama akun wajib diisi'
        ];
        echo json_encode($response);
        exit;
    }

    // Cari user berdasarkan username
    $stmt = $pdo->prepare("SELECT id_user, nama_lengkap, peran FROM user WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        
        // Admin tidak dapat menggunakan fitur ubah password sendiri
        if ($user['peran'] === 'admin') {
            $response = [
                'exists' => false,
                'message' => 'Akun tidak ditemukan'
            ];
        } else {
            $response = [
                'exists' => true,
                'message' => 'Akun ditemukan: ' . htmlspecialchars($user['nama_lengkap'])
            ];
        }
    } else {
        $response = [
            'exists' => false,
            'message' => 'Akun tidak ditemukan'
        ];
    }

} catch (Exception $e) {
    error_log("Check Username Error: " . $e->getMessage());
    $response = [
        'exists' => false,
        'message' => 'Gagal memverifikasi akun'
    ];
}

echo json_encode($response);

