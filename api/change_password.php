<?php
/**
 * API Change Password - Endpoint untuk mengubah password sendiri
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

// Start session
session_start();

// Include database config
require_once '../config/database.php';

$response = [
    'success' => false,
    'message' => ''
];

try {
    // Validasi method POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method tidak valid', 400);
    }

    // Validasi input
    $username = trim($_POST['username'] ?? '');
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasi username
    if (empty($username)) {
        throw new Exception('Nama akun wajib diisi', 400);
    }

    // Validasi password lama
    if (empty($old_password)) {
        throw new Exception('Password lama wajib diisi', 400);
    }

    // Validasi password baru
    if (empty($new_password)) {
        throw new Exception('Password baru wajib diisi', 400);
    }

    if (strlen($new_password) < 3) {
        throw new Exception('Password baru minimal 3 karakter', 400);
    }

    // Validasi konfirmasi password
    if ($new_password !== $confirm_password) {
        throw new Exception('Konfirmasi password baru tidak cocok', 400);
    }

    // Cari user berdasarkan username
    $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Akun tidak ditemukan', 404);
    }

    $user = $stmt->fetch();

    // Admin tidak dapat menggunakan fitur ubah password sendiri
    if ($user['peran'] === 'admin') {
        throw new Exception('Akun tidak ditemukan', 404);
    }

    // Verifikasi password lama
    if (!password_verify($old_password, $user['password'])) {
        throw new Exception('Password lama yang dimasukkan tidak benar', 401);
    }

    // Hash password baru
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password
    $update_stmt = $pdo->prepare("UPDATE user SET password = ? WHERE id_user = ?");
    $update_stmt->execute([$hashed_password, $user['id_user']]);

    // Log aktivitas
    error_log("Password changed successfully for user: {$username}");

    $response['success'] = true;
    $response['message'] = 'Password berhasil diubah!';

} catch (Exception $e) {
    error_log("Change Password Error: " . $e->getMessage());
    
    // Set response message
    if ($e->getCode() === 400) {
        $response['message'] = $e->getMessage();
    } elseif ($e->getCode() === 401) {
        $response['message'] = $e->getMessage();
    } elseif ($e->getCode() === 404) {
        $response['message'] = 'Akun tidak ditemukan';
    } elseif ($e->getCode() === 500) {
        $response['message'] = 'Terjadi kesalahan sistem. Silakan coba lagi.';
    } else {
        $response['message'] = 'Terjadi kesalahan. Silakan coba lagi.';
    }
}

echo json_encode($response);

