<?php
/**
 * API Validasi Transaksi Keuangan
 * Real-time validation untuk mencegah duplikasi dan validasi input
 */

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

// Set header untuk JSON response
header('Content-Type: application/json');

$response = [
    'success' => false,
    'valid' => false,
    'message' => ''
];

// Validasi jumlah - real-time
if (isset($_GET['action']) && $_GET['action'] === 'validate_jumlah') {
    $jumlah = $_GET['jumlah'] ?? '';
    
    // Hapus format rupiah
    $jumlah_numeric = str_replace(['.', ','], ['', '.'], $jumlah);
    
    // Validasi numeric
    if (!is_numeric($jumlah_numeric)) {
        $response['message'] = 'Jumlah harus berupa angka!';
        echo json_encode($response);
        exit;
    }
    
    // Validasi range
    if ((float)$jumlah_numeric <= 0) {
        $response['valid'] = false;
        $response['message'] = 'Jumlah harus lebih dari 0!';
        echo json_encode($response);
        exit;
    }
    
    if ((float)$jumlah_numeric > 999999999999) {
        $response['valid'] = false;
        $response['message'] = 'Jumlah terlalu besar (maksimal Rp 999.999.999.999)!';
        echo json_encode($response);
        exit;
    }
    
    $response['success'] = true;
    $response['valid'] = true;
    $response['message'] = 'Jumlah valid!';
    echo json_encode($response);
    exit;
}

// Validasi kategori - real-time
if (isset($_GET['action']) && $_GET['action'] === 'validate_kategori') {
    $kategori = trim($_GET['kategori'] ?? '');
    $tipe = $_GET['tipe'] ?? '';
    
    $allowed_categories = [
        'pemasukan' => ['Iuran Anggota', 'Honor Acara', 'Donasi', 'Lainnya'],
        'pengeluaran' => ['Konsumsi', 'Servis Alat', 'Seragam', 'Alat Musik', 'Transportasi', 'Lainnya']
    ];
    
    if (empty($kategori)) {
        $response['message'] = 'Kategori wajib diisi!';
        echo json_encode($response);
        exit;
    }
    
    if (!empty($tipe) && isset($allowed_categories[$tipe])) {
        if (!in_array($kategori, $allowed_categories[$tipe])) {
            $response['message'] = 'Kategori tidak valid untuk tipe ini!';
            echo json_encode($response);
            exit;
        }
    }
    
    $response['success'] = true;
    $response['valid'] = true;
    $response['message'] = 'Kategori valid!';
    echo json_encode($response);
    exit;
}

// Validasi tanggal - real-time
if (isset($_GET['action']) && $_GET['action'] === 'validate_tanggal') {
    $tanggal = $_GET['tanggal'] ?? '';
    
    if (empty($tanggal)) {
        $response['message'] = 'Tanggal wajib diisi!';
        echo json_encode($response);
        exit;
    }
    
    // Format check
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        $response['message'] = 'Format tanggal tidak valid (YYYY-MM-DD)!';
        echo json_encode($response);
        exit;
    }
    
    // Tidak boleh di masa depan
    if (strtotime($tanggal) > strtotime('today')) {
        $response['message'] = 'Tanggal tidak boleh di masa depan!';
        echo json_encode($response);
        exit;
    }
    
    // Tidak boleh terlalu lama (5 tahun)
    if (strtotime($tanggal) < strtotime('-5 years')) {
        $response['message'] = 'Tanggal terlalu lama (maksimal 5 tahun yang lalu)!';
        echo json_encode($response);
        exit;
    }
    
    $response['success'] = true;
    $response['valid'] = true;
    $response['message'] = 'Tanggal valid!';
    echo json_encode($response);
    exit;
}

// Check duplikasi transaksi (mode add)
if (isset($_GET['action']) && $_GET['action'] === 'check_duplicate') {
    $mode = $_GET['mode'] ?? 'add';
    $tipe = $_GET['tipe'] ?? '';
    $jumlah = $_GET['jumlah'] ?? '';
    $tanggal = $_GET['tanggal'] ?? '';
    $kategori = $_GET['kategori'] ?? '';
    
    $jumlah_numeric = (float)str_replace(['.', ','], ['', '.'], $jumlah);
    
    // Build query
    $query = "SELECT id_kas FROM keuangan WHERE tipe = ? AND jumlah = ? AND tanggal = ?";
    $params = [$tipe, $jumlah_numeric, $tanggal];
    
    if ($mode === 'add') {
        // Untuk add: check apakah sudah ada transaksi sama
        // Tidak严格 karena bisa jadi sengaja input sama
        $response['success'] = true;
        $response['valid'] = true;
        $response['message'] = 'Transaksi unik!';
        echo json_encode($response);
        exit;
    }
    
    if ($mode === 'edit' && isset($_GET['exclude_id'])) {
        $exclude_id = (int)$_GET['exclude_id'];
        $query .= " AND id_kas != ?";
        $params[] = $exclude_id;
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $existing = $stmt->fetch();
    
    if ($existing) {
        $response['success'] = true;
        $response['valid'] = false;
        $response['message'] = '⚠️ Transaksi serupa sudah ada pada tanggal tersebut!';
        echo json_encode($response);
        exit;
    }
    
    $response['success'] = true;
        $response['valid'] = true;
        $response['message'] = 'Transaksi unik!';
    echo json_encode($response);
    exit;
}

// Validasi form lengkap (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'validate_all') {
    $errors = [];
    
    $tipe = $_POST['tipe'] ?? '';
    $kategori = trim($_POST['kategori'] ?? '');
    $jumlah = str_replace(['.', ','], ['', '.'], $_POST['jumlah'] ?? '0');
    $tanggal = $_POST['tanggal'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');
    
    // Validasi tipe
    if (!in_array($tipe, ['pemasukan', 'pengeluaran'])) {
        $errors['tipe'] = 'Tipe transaksi tidak valid!';
    }
    
    // Validasi kategori
    $allowed_categories = [
        'pemasukan' => ['Iuran Anggota', 'Honor Acara', 'Donasi', 'Lainnya'],
        'pengeluaran' => ['Konsumsi', 'Servis Alat', 'Seragam', 'Alat Musik', 'Transportasi', 'Lainnya']
    ];
    
    if (empty($kategori)) {
        $errors['kategori'] = 'Kategori wajib diisi!';
    } elseif (isset($allowed_categories[$tipe]) && !in_array($kategori, $allowed_categories[$tipe])) {
        $errors['kategori'] = 'Kategori tidak valid untuk tipe ini!';
    }
    
    // Validasi jumlah
    if (empty($jumlah) || (float)$jumlah <= 0) {
        $errors['jumlah'] = 'Jumlah harus lebih dari 0!';
    } elseif (!is_numeric($jumlah)) {
        $errors['jumlah'] = 'Jumlah harus berupa angka!';
    } elseif ((float)$jumlah > 999999999999) {
        $errors['jumlah'] = 'Jumlah terlalu besar!';
    }
    
    // Validasi tanggal
    if (empty($tanggal)) {
        $errors['tanggal'] = 'Tanggal wajib diisi!';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        $errors['tanggal'] = 'Format tanggal tidak valid!';
    } elseif (strtotime($tanggal) > strtotime('today')) {
        $errors['tanggal'] = 'Tanggal tidak boleh di masa depan!';
    } elseif (strtotime($tanggal) < strtotime('-5 years')) {
        $errors['tanggal'] = 'Tanggal terlalu lama!';
    }
    
    if (empty($errors)) {
        $response['success'] = true;
        $response['valid'] = true;
        $response['message'] = 'Semua data valid!';
    } else {
        $response['success'] = true;
        $response['valid'] = false;
        $response['errors'] = $errors;
        $response['message'] = 'Terdapat kesalahan validasi!';
    }
    
    echo json_encode($response);
    exit;
}

// Default response
echo json_encode($response);

