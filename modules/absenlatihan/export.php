<?php
/**
 * Export Data Absensi Latihan ke Excel
 * Menggunakan PHPExcel atau library serupa untuk generate file Excel
 */

// Definisikan BASE_URL jika belum ada
if (!defined('BASE_URL')) {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/hadrahin';
    define('BASE_URL', $base_url);
}

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

$page_title = "Export Absensi Latihan";

// Get current user role
$user_peran = $_SESSION['peran'] ?? 'anggota';
$user_id = $_SESSION['user_id'] ?? 0;

// Check permission - only admin and pembina can access
if (!in_array($user_peran, ['admin', 'pembina'])) {
    header('Location: ../dashboard/' . $user_peran . '.php?msg=access_denied');
    exit;
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) && in_array($_GET['status'], ['hadir', 'izin', 'alpa']) ? $_GET['status'] : '';
$jadwal_filter = isset($_GET['jadwal']) ? (int)$_GET['jadwal'] : 0;
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : '';
$tanggal_selesai = isset($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : '';

// Build WHERE clause (same as index.php)
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(u.nama_lengkap LIKE ? OR u.username LIKE ? OR j.lokasi LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($status_filter)) {
    $where_conditions[] = "a.status_hadir = ?";
    $params[] = $status_filter;
}

if (!empty($jadwal_filter)) {
    $where_conditions[] = "a.id_jadwal = ?";
    $params[] = $jadwal_filter;
}

// Filter periode tanggal
if (!empty($tanggal_mulai) && !empty($tanggal_selesai)) {
    $where_conditions[] = "j.tanggal BETWEEN ? AND ?";
    $params[] = $tanggal_mulai;
    $params[] = $tanggal_selesai;
} elseif (!empty($tanggal_mulai)) {
    $where_conditions[] = "j.tanggal >= ?";
    $params[] = $tanggal_mulai;
} elseif (!empty($tanggal_selesai)) {
    $where_conditions[] = "j.tanggal <= ?";
    $params[] = $tanggal_selesai;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Count total records for export
$count_sql = "SELECT COUNT(*) FROM absen_latihan a
              JOIN jadwal_latihan j ON a.id_jadwal = j.id_jadwal
              JOIN user u ON a.id_user = u.id_user $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();

// Check if there are records to export
if ($total_records <= 0) {
    header('Location: index.php?msg=no_data');
    exit;
}

// Fetch all absensi data for export (no pagination)
$sql = "SELECT a.*, j.tanggal, j.jam_mulai, j.lokasi, j.catatan, j.status as status_jadwal,
               u.nama_lengkap, u.username, u.peran, u.no_hp
        FROM absen_latihan a
        JOIN jadwal_latihan j ON a.id_jadwal = j.id_jadwal
        JOIN user u ON a.id_user = u.id_user
        $where_clause
        ORDER BY j.tanggal DESC, j.jam_mulai DESC, u.nama_lengkap ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$absensis = $stmt->fetchAll();

// Generate Excel file using simple CSV approach (since we don't have PHPExcel installed)
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="data_absensi_latihan_' . date('Y-m-d_H-i-s') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Write BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write header
fputcsv($output, [
    'No',
    'Tanggal Latihan',
    'Jam Mulai',
    'Lokasi',
    'Status Jadwal',
    'Nama Anggota',
    'Username',
    'No HP',
    'Peran',
    'Status Absensi',
    'Waktu Absen',
    'Catatan Jadwal'
]);

// Write data
$no = 1;
foreach ($absensis as $absen) {
    fputcsv($output, [
        $no++,
        date('d/m/Y', strtotime($absen['tanggal'])),
        date('H:i', strtotime($absen['jam_mulai'])),
        $absen['lokasi'],
        ucfirst($absen['status_jadwal']),
        $absen['nama_lengkap'],
        $absen['username'],
        $absen['no_hp'] ?? '-',
        ucfirst($absen['peran']),
        ucfirst($absen['status_hadir']),
        date('d/m/Y H:i:s', strtotime($absen['jam_absen'])),
        $absen['catatan'] ?? '-'
    ]);
}

fclose($output);
exit;
?>
