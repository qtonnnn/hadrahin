<?php
/**
 * Dashboard Admin - Hadrah
 * Halaman dashboard untuk peran admin dengan sidebar dan statistik real-time
 */

// Start session dan include database
session_start();
require_once '../config/database.php';

// Include auth check untuk keamanan session
require_once '../includes/auth_check.php';

// Ambil data user dari database
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM user WHERE id_user = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Ambil statistik untuk initial load
$stats = [];

// Total Users
$stmt = $pdo->query("SELECT COUNT(*) as total FROM user");
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Check if there's a training schedule today
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM jadwal_latihan WHERE tanggal = ? AND status = 'direncanakan' ORDER BY jam_mulai ASC");
$stmt->execute([$today]);
$jadwal_hari_ini = $stmt->fetchAll();
$stats['has_jadwal_hari_ini'] = !empty($jadwal_hari_ini);
$stats['jadwal_hari_ini_list'] = $jadwal_hari_ini;

// Active Users
$stmt = $pdo->query("SELECT COUNT(*) as active FROM user WHERE status_aktif = 1");
$stats['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['active'];

// Users by Role
$query = "SELECT peran, COUNT(*) as count FROM user GROUP BY peran";
$stmt = $pdo->query($query);
$stats['users_by_role'] = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats['users_by_role'][$row['peran']] = $row['count'];
}

// Jadwal Bulan Ini
$bulan_ini = date('Y-m');
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM jadwal_latihan WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?");
$stmt->execute([$bulan_ini]);
$stats['jadwal_bulan_ini'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Booking Aktif
$stmt = $pdo->query("SELECT COUNT(*) as total FROM booking_acara WHERE status IN ('menunggu', 'diterima')");
$stats['booking_aktif'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Absensi Statistik
$stmt = $pdo->query("SELECT
    COUNT(*) as total_absensi,
    SUM(CASE WHEN status_hadir = 'hadir' THEN 1 ELSE 0 END) as hadir,
    SUM(CASE WHEN status_hadir = 'izin' THEN 1 ELSE 0 END) as izin,
    SUM(CASE WHEN status_hadir = 'alpa' THEN 1 ELSE 0 END) as alpa
FROM absen_latihan");
$absensi_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['absensi'] = [
    'total' => $absensi_stats['total_absensi'] ?? 0,
    'hadir' => $absensi_stats['hadir'] ?? 0,
    'izin' => $absensi_stats['izin'] ?? 0,
    'alpa' => $absensi_stats['alpa'] ?? 0
];

// Absensi hari ini (jika ada jadwal)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM absen_latihan a
                       JOIN jadwal_latihan j ON a.id_jadwal = j.id_jadwal
                       WHERE j.tanggal = CURDATE()");
$stmt->execute();
$stats['absensi_hari_ini'] = $stmt->fetchColumn();

// ============================================
// KEUANGAN STATISTICS
// ============================================

// Basic Financial Summary
$stmt = $pdo->query("SELECT 
    COALESCE(SUM(CASE WHEN tipe = 'pemasukan' THEN jumlah ELSE 0 END), 0) as pemasukan,
    COALESCE(SUM(CASE WHEN tipe = 'pengeluaran' THEN jumlah ELSE 0 END), 0) as pengeluaran
FROM keuangan");
$data = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['keuangan'] = [
    'pemasukan' => $data['pemasukan'],
    'pengeluaran' => $data['pengeluaran'],
    'saldo' => $data['pemasukan'] - $data['pengeluaran']
];

// Bulan ini
$bulan_ini_start = date('Y-m-01');
$bulan_ini_end = date('Y-m-t');
$stmt = $pdo->prepare("SELECT 
    COALESCE(SUM(CASE WHEN tipe = 'pemasukan' THEN jumlah ELSE 0 END), 0) as pemasukan,
    COALESCE(SUM(CASE WHEN tipe = 'pengeluaran' THEN jumlah ELSE 0 END), 0) as pengeluaran
FROM keuangan WHERE tanggal BETWEEN ? AND ?");
$stmt->execute([$bulan_ini_start, $bulan_ini_end]);
$data_bulan_ini = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['keuangan']['bulan_ini_pemasukan'] = $data_bulan_ini['pemasukan'];
$stats['keuangan']['bulan_ini_pengeluaran'] = $data_bulan_ini['pengeluaran'];
$stats['keuangan']['bulan_ini_saldo'] = $data_bulan_ini['pemasukan'] - $data_bulan_ini['pengeluaran'];

// Bulan lalu
$bulan_lalu_start = date('Y-m-01', strtotime('-1 month'));
$bulan_lalu_end = date('Y-m-t', strtotime('-1 month'));
$stmt = $pdo->prepare("SELECT 
    COALESCE(SUM(CASE WHEN tipe = 'pemasukan' THEN jumlah ELSE 0 END), 0) as pemasukan,
    COALESCE(SUM(CASE WHEN tipe = 'pengeluaran' THEN jumlah ELSE 0 END), 0) as pengeluaran
FROM keuangan WHERE tanggal BETWEEN ? AND ?");
$stmt->execute([$bulan_lalu_start, $bulan_lalu_end]);
$data_bulan_lalu = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['keuangan']['bulan_lalu_pemasukan'] = $data_bulan_lalu['pemasukan'];
$stats['keuangan']['bulan_lalu_pengeluaran'] = $data_bulan_lalu['pengeluaran'];

// Kategori Pemasukan Breakdown
$stmt = $pdo->query("SELECT kategori, COALESCE(SUM(jumlah), 0) as total 
                     FROM keuangan 
                     WHERE tipe = 'pemasukan' AND kategori IS NOT NULL 
                     GROUP BY kategori 
                     ORDER BY total DESC");
$stats['keuangan']['kategori_pemasukan'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kategori Pengeluaran Breakdown
$stmt = $pdo->query("SELECT kategori, COALESCE(SUM(jumlah), 0) as total 
                     FROM keuangan 
                     WHERE tipe = 'pengeluaran' AND kategori IS NOT NULL 
                     GROUP BY kategori 
                     ORDER BY total DESC");
$stats['keuangan']['kategori_pengeluaran'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Transaksi Terbaru (5 terakhir)
$stmt = $pdo->query("SELECT * FROM keuangan ORDER BY created_at DESC LIMIT 5");
$stats['keuangan']['recent_transactions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Data Tren 6 Bulan Terakhir (untuk chart)
$tren_data = [];
for ($i = 5; $i >= 0; $i--) {
    $bulan = date('Y-m', strtotime("-{$i} months"));
    $bulan_awal = $bulan . '-01';
    $bulan_akhir = date('Y-m-t', strtotime($bulan));
    
    $stmt = $pdo->prepare("SELECT 
        COALESCE(SUM(CASE WHEN tipe = 'pemasukan' THEN jumlah ELSE 0 END), 0) as pemasukan,
        COALESCE(SUM(CASE WHEN tipe = 'pengeluaran' THEN jumlah ELSE 0 END), 0) as pengeluaran
    FROM keuangan WHERE tanggal BETWEEN ? AND ?");
    $stmt->execute([$bulan_awal, $bulan_akhir]);
    $data_tren = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $tren_data[] = [
        'bulan' => $bulan,
        'label' => date('M y', strtotime($bulan)),
        'pemasukan' => (float)$data_tren['pemasukan'],
        'pengeluaran' => (float)$data_tren['pengeluaran'],
        'saldo' => (float)$data_tren['pemasukan'] - (float)$data_tren['pengeluaran']
    ];
}
$stats['keuangan']['tren_6_bulan'] = $tren_data;

// Transaksi Hari Ini
$stmt = $pdo->prepare("SELECT 
    COALESCE(SUM(CASE WHEN tipe = 'pemasukan' THEN jumlah ELSE 0 END), 0) as pemasukan,
    COALESCE(SUM(CASE WHEN tipe = 'pengeluaran' THEN jumlah ELSE 0 END), 0) as pengeluaran,
    COUNT(*) as total_transaksi
FROM keuangan WHERE tanggal = CURDATE()");
$stmt->execute();
$stats['keuangan']['hari_ini'] = $stmt->fetch(PDO::FETCH_ASSOC);

// Total Transaksi
$stmt = $pdo->query("SELECT COUNT(*) as total FROM keuangan");
$stats['keuangan']['total_transaksi'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// User Growth Data (12 bulan terakhir)
$query = "SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
          FROM user 
          GROUP BY DATE_FORMAT(created_at, '%Y-%m')
          ORDER BY month ASC
          LIMIT 12";
$stmt = $pdo->query($query);
$stats['user_growth'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// DRESSCODE STATISTICS
// ============================================

// Total dresscode
$stmt = $pdo->query("SELECT COUNT(*) as total FROM dresscode");
$stats['dresscode']['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Dresscode aktif
$stmt = $pdo->query("SELECT COUNT(*) as aktif FROM dresscode WHERE status = 'aktif'");
$stats['dresscode']['aktif'] = $stmt->fetch(PDO::FETCH_ASSOC)['aktif'];

// Dresscode nonaktif
$stmt = $pdo->query("SELECT COUNT(*) as nonaktif FROM dresscode WHERE status = 'nonaktif'");
$stats['dresscode']['nonaktif'] = $stmt->fetch(PDO::FETCH_ASSOC)['nonaktif'];

// Dresscode yang digunakan di booking aktif
$stmt = $pdo->query("SELECT COUNT(DISTINCT id_dresscode) FROM booking_acara WHERE status IN ('menunggu', 'diterima') AND id_dresscode IS NOT NULL");
$stats['dresscode']['digunakan'] = $stmt->fetchColumn();

// Dresscode by status for chart
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM dresscode GROUP BY status");
$stats['dresscode']['by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Most used dresscode (by booking count)
$stmt = $pdo->query("SELECT d.id_dresscode, d.nama_pakaian, d.warna, COUNT(b.id_booking) as penggunaan 
                     FROM dresscode d 
                     LEFT JOIN booking_acara b ON d.id_dresscode = b.id_dresscode 
                     GROUP BY d.id_dresscode 
                     ORDER BY penggunaan DESC 
                     LIMIT 3");
$stats['dresscode']['most_used'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent dresscode activities
$stmt = $pdo->query("SELECT id_dresscode, nama_pakaian, status, created_at FROM dresscode ORDER BY created_at DESC LIMIT 3");
$recent_dresscode_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// ALAT (INVENTORY) STATISTICS
// ============================================

// Total alat
$stmt = $pdo->query("SELECT COUNT(*) as total FROM alat");
$stats['alat']['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total kondisi baik
$stmt = $pdo->query("SELECT COALESCE(SUM(jumlah_baik), 0) as total_baik FROM alat");
$stats['alat']['total_baik'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_baik'];

// Total kondisi rusak
$stmt = $pdo->query("SELECT COALESCE(SUM(jumlah_rusak), 0) as total_rusak FROM alat");
$stats['alat']['total_rusak'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_rusak'];

// Total keseluruhan
$stats['alat']['total_semua'] = $stats['alat']['total_baik'] + $stats['alat']['total_rusak'];
$stats['alat']['persen_baik'] = $stats['alat']['total_semua'] > 0 ? round(($stats['alat']['total_baik'] / $stats['alat']['total_semua']) * 100) : 0;

// Alat by condition for chart
$stmt = $pdo->query("SELECT 
                        SUM(jumlah_baik) as baik,
                        SUM(jumlah_rusak) as rusak
                     FROM alat");
$alat_kondisi = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['alat']['kondisi'] = [
    'baik' => (int)$alat_kondisi['baik'],
    'rusak' => (int)$alat_kondisi['rusak']
];

// Most valuable alat (highest quantity)
$stmt = $pdo->query("SELECT id_alat, nama_alat, (jumlah_baik + jumlah_rusak) as total_stok 
                     FROM alat 
                     ORDER BY total_stok DESC 
                     LIMIT 3");
$stats['alat']['most_stok'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Alat yang sering dipinjam
$stmt = $pdo->query("SELECT a.id_alat, a.nama_alat, COUNT(ap.id_alat_pengguna) as total_dipinjam
                     FROM alat a 
                     LEFT JOIN alat_pengguna ap ON a.id_alat = ap.id_alat AND ap.status = 'aktif'
                     GROUP BY a.id_alat 
                     ORDER BY total_dipinjam DESC 
                     LIMIT 3");
$stats['alat']['sering_dipinjam'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent Activities
$recent_activities = [];

// Recent users
$stmt = $pdo->query("SELECT id_user, username, created_at FROM user ORDER BY created_at DESC LIMIT 3");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $u) {
    $recent_activities[] = [
        'type' => 'user',
        'title' => 'User baru: ' . htmlspecialchars($u['username']),
        'icon' => 'fa-user-plus',
        'icon_bg' => 'bg-primary',
        'created_at' => $u['created_at']
    ];
}

// Recent jadwal
$stmt = $pdo->query("SELECT id_jadwal, tanggal, lokasi, created_at FROM jadwal_latihan ORDER BY created_at DESC LIMIT 2");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $j) {
    $recent_activities[] = [
        'type' => 'jadwal',
        'title' => 'Jadwal: ' . date('d/m/Y', strtotime($j['tanggal'])),
        'icon' => 'fa-calendar-plus',
        'icon_bg' => 'bg-success',
        'created_at' => $j['created_at']
    ];
}

// Recent booking
$stmt = $pdo->query("SELECT id_booking, nama_acara, status, created_at FROM booking_acara ORDER BY created_at DESC LIMIT 2");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $b) {
    $recent_activities[] = [
        'type' => 'booking',
        'title' => 'Booking: ' . htmlspecialchars($b['nama_acara']),
        'icon' => 'fa-calendar-check',
        'icon_bg' => 'bg-info',
        'created_at' => $b['created_at']
    ];
}

// Sort by date
usort($recent_activities, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$recent_activities = array_slice($recent_activities, 0, 5);

// ============================================
// ACTIVITY CHART DATA
// ============================================

// Get activities count by type for last 7 days
$activity_chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $activity_chart_data[$date] = [
        'date' => $date,
        'label' => date('d/m', strtotime($date)),
        'user' => 0,
        'jadwal' => 0,
        'booking' => 0,
        'alat' => 0,
        'dresscode' => 0
    ];
}

// Count users by date
$stmt = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM user WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at)");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (isset($activity_chart_data[$row['date']])) {
        $activity_chart_data[$row['date']]['user'] = (int)$row['count'];
    }
}

// Count jadwal by date
$stmt = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM jadwal_latihan WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at)");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (isset($activity_chart_data[$row['date']])) {
        $activity_chart_data[$row['date']]['jadwal'] = (int)$row['count'];
    }
}

// Count booking by date
$stmt = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM booking_acara WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at)");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (isset($activity_chart_data[$row['date']])) {
        $activity_chart_data[$row['date']]['booking'] = (int)$row['count'];
    }
}

// Count alat activities by date
$stmt = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM alat WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at)");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (isset($activity_chart_data[$row['date']])) {
        $activity_chart_data[$row['date']]['alat'] = (int)$row['count'];
    }
}

// Count dresscode activities by date
$stmt = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM dresscode WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at)");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (isset($activity_chart_data[$row['date']])) {
        $activity_chart_data[$row['date']]['dresscode'] = (int)$row['count'];
    }
}

// Total activities by type for pie chart
$activity_type_totals = [
    'User' => 0,
    'Jadwal' => 0,
    'Booking' => 0,
    'Alat' => 0,
    'Dresscode' => 0
];

foreach ($activity_chart_data as $day) {
    $activity_type_totals['User'] += $day['user'];
    $activity_type_totals['Jadwal'] += $day['jadwal'];
    $activity_type_totals['Booking'] += $day['booking'];
    $activity_type_totals['Alat'] += $day['alat'];
    $activity_type_totals['Dresscode'] += $day['dresscode'];
}

// Helper function untuk time ago
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->d > 0) return $diff->d . ' hari yang lalu';
    if ($diff->h > 0) return $diff->h . ' jam yang lalu';
    if ($diff->i > 0) return $diff->i . ' menit yang lalu';
    return 'Baru saja';
}

// Helper function untuk mendapatkan periode jadwal (1 bulan ini sampai data terakhir)
function getJadwalPeriode($pdo) {
    // Tanggal 1 bulan ini
    $start_date = new DateTime(date('Y-m-01'));
    
    // Cari tanggal terbaru di jadwal_latihan
    $stmt = $pdo->query("SELECT MAX(tanggal) as max_date FROM jadwal_latihan");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!empty($result['max_date'])) {
        $end_date = new DateTime($result['max_date']);
    } else {
        // Jika tidak ada data, gunakan tanggal sekarang
        $end_date = new DateTime();
    }
    
    return [
        'start' => $start_date,
        'end' => $end_date,
        'start_formatted' => $start_date->format('Y-m-d'),
        'end_formatted' => $end_date->format('Y-m-d')
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <!-- Tambahkan ini di head -->
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard Admin - Hadrah App</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🥁</text></svg>">
    <!-- Admin CSS - Inline Styles -->
    <style>
/* ==========================================================================
   Admin Dashboard Styles - Hadrah App
   ========================================================================== */

/* Global Box Sizing */
*, *::before, *::after {
    box-sizing: border-box;
}

html, body {
    overflow-x: hidden;
    max-width: 100%;
    margin: 0;
    padding: 0;
}

body {
    padding-top: 0 !important;
}

/* CSS Variables */
:root {
    --sidebar-width: 260px;
    --sidebar-bg: #ffffff;
    --sidebar-hover: #f8f9fa;
    --sidebar-active: #e9ecef;
    --text-primary: #212529;
    --text-secondary: #6c757d;
    --primary-color: #0d6efd;
    --success-color: #198754;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
    --info-color: #0dcaf0;
    --border-color: #dee2e6;
    --shadow-sm: 0 .125rem .25rem rgba(0,0,0,.075);
    --shadow-md: 0 .5rem 1rem rgba(0,0,0,.15);
    --shadow-lg: 0 1rem 3rem rgba(0,0,0,.175);
    --transition: all 0.3s ease;
}

/* ==========================================================================
   Layout
   ========================================================================== */

.admin-wrapper {
    min-height: 100vh;
    background-color: #f5f6fa;
    overflow-x: hidden;
    max-width: 100vw;
    width: 100%;
}

.admin-layout {
    display: flex;
    min-height: 100vh;
}

/* ==========================================================================
   Hamburger Menu Button
   ========================================================================== */

.hamburger-btn {
    display: none;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
    cursor: pointer;
    transition: var(--transition);
}

.hamburger-btn:hover {
    background: rgba(255, 255, 255, 0.25);
}

.hamburger-btn i {
    font-size: 1.25rem;
    color: #fff;
    transition: var(--transition);
}

.hamburger-btn.active {
    background: rgba(255, 255, 255, 0.25);
}

.hamburger-btn.active i::before {
    content: '\f00d';
}

/* ==========================================================================
   Sidebar Overlay
   ========================================================================== */

.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.sidebar-overlay.show {
    display: block;
    opacity: 1;
}

/* ==========================================================================
   Sidebar Styles
   ========================================================================== */

.sidebar {
    width: var(--sidebar-width);
    background: var(--sidebar-bg);
    border-right: 1px solid var(--border-color);
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    overflow-y: auto;
    z-index: 1000;
    transition: transform 0.3s ease, -webkit-transform 0.3s ease;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
}

.sidebar.collapsed {
    transform: translateX(-100%);
}

.sidebar-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    background: linear-gradient(135deg, #198754 0%, #146c43 100%);
}

.sidebar-header .brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: #fff;
    text-decoration: none;
    font-weight: 700;
    font-size: 1.25rem;
}

.sidebar-header .brand i {
    font-size: 1.5rem;
}

.sidebar-nav {
    padding: 1rem 0;
}

.nav-section {
    margin-bottom: 1.5rem;
}

.nav-section-title {
    padding: 0.5rem 1.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-secondary);
}

.nav-item {
    margin: 0.25rem 0.75rem;
    position: relative;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: var(--text-primary);
    text-decoration: none;
    border-radius: 0.5rem;
    transition: var(--transition);
    font-weight: 500;
}

.nav-link i {
    width: 1.25rem;
    text-align: center;
    font-size: 1.1rem;
    color: var(--text-secondary);
    transition: var(--transition);
}

.nav-link:hover {
    background: var(--sidebar-hover);
}

.nav-link:hover i {
    color: var(--primary-color);
}

.nav-link.active {
    background: rgba(13, 110, 253, 0.1);
    color: var(--primary-color);
}

.nav-link.active i {
    color: var(--primary-color);
}

.nav-link .badge {
    margin-left: auto;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
}

/* ==========================================================================
   Main Content Area
   ========================================================================== */

.main-content {
    flex: 1;    
    margin-left: var(--sidebar-width);
    margin-top: 0;
    padding: 70px 0 0 0;
    width: calc(100% - var(--sidebar-width));
    max-width: 100%;
    overflow-x: hidden;
    transition: margin-left 0.3s ease;
}

.main-content.expanded {
    margin-left: 0;
}

.content-header {
    background: linear-gradient(135deg, #198754 0%, #146c43 100%);
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: fixed;
    top: 0;
    left: var(--sidebar-width);
    right: 0;
    width: calc(100% - var(--sidebar-width));
    z-index: 1000;
    color: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: left 0.3s ease, width 0.3s ease;
}

/* Responsive - ketika sidebar disembunyikan */
@media (max-width: 1199.98px) {
    .content-header {
        left: 0;
        width: 100%;
    }
    
    .main-content {
        margin-left: 0;
    }
}

.content-header h1 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    color: #fff;
}

.content-header p {
    color: rgba(255, 255, 255, 0.8);
}

.content-body {
    padding: 2rem;
}

/* ==========================================================================
   Stat Cards
   ========================================================================== */

.stat-card {
    background: #fff;
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--primary-color);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.stat-card.stat-primary::before { background: var(--primary-color); }
.stat-card.stat-success::before { background: var(--success-color); }
.stat-card.stat-warning::before { background: var(--warning-color); }
.stat-card.stat-info::before { background: var(--info-color); }
.stat-card.stat-danger::before { background: var(--danger-color); }

.stat-card .stat-icon {
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.stat-card.stat-primary .stat-icon {
    background: rgba(13, 110, 253, 0.1);
    color: var(--primary-color);
}

.stat-card.stat-success .stat-icon {
    background: rgba(25, 135, 84, 0.1);
    color: var(--success-color);
}

.stat-card.stat-warning .stat-icon {
    background: rgba(255, 193, 7, 0.1);
    color: #b38600;
}

.stat-card.stat-info .stat-icon {
    background: rgba(13, 202, 240, 0.1);
    color: #0aa2c0;
}

.stat-card.stat-danger .stat-icon {
    background: rgba(220, 53, 69, 0.1);
    color: var(--danger-color);
}

.stat-card .stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stat-card .stat-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.stat-card .stat-trend {
    font-size: 0.75rem;
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.stat-card .stat-trend.up {
    color: var(--success-color);
}

.stat-card .stat-trend.down {
    color: var(--danger-color);
}

/* ==========================================================================
   Charts Section
   ========================================================================== */

.chart-card {
    background: #fff;
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
}

.chart-card .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.chart-card .card-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.chart-card .card-subtitle {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
}

.chart-container {
    position: relative;
    height: 300px;
}

/* ==========================================================================
   Quick Actions
   ========================================================================== */

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.5rem 1rem;
    background: #fff;
    border: 1px solid var(--border-color);
    border-radius: 0.75rem;
    text-decoration: none;
    color: var(--text-primary);
    transition: var(--transition);
    gap: 0.75rem;
}

.quick-action-btn:hover {
    border-color: var(--primary-color);
    background: rgba(13, 110, 253, 0.02);
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.quick-action-btn i {
    font-size: 2rem;
    color: var(--primary-color);
}

.quick-action-btn span {
    font-weight: 500;
    font-size: 0.875rem;
    text-align: center;
}

/* ==========================================================================
   Recent Activities
   ========================================================================== */

.activity-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid var(--border-color);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.activity-icon.bg-primary {
    background: rgba(13, 110, 253, 0.1);
    color: var(--primary-color);
}

.activity-icon.bg-success {
    background: rgba(25, 135, 84, 0.1);
    color: var(--success-color);
}

.activity-icon.bg-warning {
    background: rgba(255, 193, 7, 0.1);
    color: #b38600;
}

.activity-icon.bg-info {
    background: rgba(13, 202, 240, 0.1);
    color: #0aa2c0;
}

.activity-content {
    flex: 1;
}

.activity-title {
    font-weight: 500;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.activity-time {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

/* ==========================================================================
   User Info Section
   ========================================================================== */

.user-info-dropdown {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 1rem;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 0.5rem;
    color: #fff;
    cursor: pointer;
    transition: var(--transition);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.user-info-dropdown:hover {
    background: rgba(255, 255, 255, 0.25);
}

.user-avatar {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
    border: 2px solid rgba(255, 255, 255, 0.5);
}

.user-details {
    text-align: left;
}

.user-name {
    font-weight: 600;
    font-size: 0.875rem;
}

.user-role {
    font-size: 0.75rem;
    opacity: 0.8;
}

/* ==========================================================================
   Welcome Card
   ========================================================================== */

.welcome-card {
    background: linear-gradient(135deg, #198754 0%, #146c43 100%);
    color: #fff;
    padding: 2rem;
    border-radius: 0.75rem;
    margin-bottom: 2rem;
}

.welcome-card h2 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.welcome-card p {
    opacity: 0.9;
    margin: 0;
}

/* ==========================================================================
   Loading States
   ========================================================================== */

.skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
    border-radius: 0.5rem;
}

@keyframes skeleton-loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.skeleton-text {
    height: 1rem;
    margin-bottom: 0.5rem;
}

.skeleton-title {
    height: 1.5rem;
    width: 60%;
    margin-bottom: 1rem;
}

/* ==========================================================================
   Floating Action Button
   ========================================================================== */

.floating-btn {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #198754 0%, #146c43 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    box-shadow: 0 4px 15px rgba(25, 135, 84, 0.4);
    z-index: 1000;
    transition: all 0.3s ease;
    text-decoration: none;
}

.floating-btn:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 6px 20px rgba(25, 135, 84, 0.5);
    color: #fff;
}

.floating-btn:active {
    transform: translateY(0) scale(0.98);
}

@media (max-width: 575.98px) {
    .floating-btn {
        bottom: 1.5rem;
        right: 1.5rem;
        width: 50px;
        height: 50px;
        font-size: 1.25rem;
    }

    /* User Card List - Mobile View */
    .user-card-list {
        padding: 0;
    }

    .user-card {
        background: #fff;
        border-bottom: 1px solid var(--border-color);
        padding: 1rem;
    }

    .user-card:last-child {
        border-bottom: none;
    }

    .user-card-header {
        display: flex;
        align-items: center;
        margin-bottom: 0.75rem;
    }

    .user-card-info {
        flex: 1;
        min-width: 0;
    }

    .user-card-info h5 {
        font-size: 1rem;
        font-weight: 600;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-card-info small {
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-card-body {
        margin-bottom: 0.75rem;
    }

    .user-card-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.35rem 0;
        font-size: 0.875rem;
    }

    .user-card-footer {
        display: flex;
        gap: 0.5rem;
    }

    .user-card-footer .btn {
        flex: 1;
        justify-content: center;
    }
}

/* ==========================================================================
   Responsive Design
   ========================================================================== */

@media (max-width: 1199.98px) {
    /* Show hamburger button on tablet and mobile */
    .hamburger-btn {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Fix: Sidebar harus di atas header */
    .sidebar {
        transform: translateX(-100%);
        height: 100vh;
        top: 0;
        display: flex;
        flex-direction: column;
        z-index: 1100;
    }

    .sidebar.show {
        transform: translateX(0);
        box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
    }

    /* Make sidebar nav scrollable */
    .sidebar-nav {
        flex: 1;
        overflow-y: auto;
        padding-bottom: 2rem;
    }

    /* Fix: Gap pada mobile */
    .main-content {
        margin-left: 0;
        margin-top: 0;
        padding-top: 60px;
    }

    /* Fix: Header harus full width */
    .content-header {
        padding: 1rem 1.5rem;
        left: 0;
        width: 100%;
        z-index: 1050;
    }

    .content-body {
        padding: 1.5rem;
    }

    /* Sidebar brand on mobile - keep text visible */
    .sidebar-header {
        padding: 1.25rem 1.5rem;
        padding-top: max(1.25rem, env(safe-area-inset-top, 1.25rem));
    }

    .sidebar-header .brand {
        justify-content: flex-start;
        gap: 0.75rem;
    }

    .sidebar-header .brand span {
        display: inline !important;
    }

    .sidebar-header .brand i {
        font-size: 1.5rem;
    }

    /* Show nav section titles on mobile */
    .nav-section-title {
        padding: 0.5rem 1rem;
        font-size: 0.75rem;
    }

    /* Nav links on mobile - show icon and text */
    .nav-link {
        padding: 0.75rem 1rem;
        justify-content: flex-start;
    }

    .nav-link span {
        display: inline !important;
        font-size: 0.9rem;
    }

    .nav-link i {
        width: 1.5rem;
        font-size: 1.1rem;
    }

    .nav-item {
        margin: 0.25rem 0.75rem;
    }
}

@media (max-width: 991.98px) {
    .stat-card {
        margin-bottom: 1rem;
    }

    .quick-actions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 767.98px) {
    /* Fix: Gap lebih kecil untuk mobile */
    .main-content {
        padding: 56px 0 0 0;
    }
    
    @media (max-width: 1199.98px) {
        .main-content {
            margin-top: 0;
        }
    }
    
    /* Fix: Header lebih pendek untuk mobile */
    .content-header {
        padding: 0.75rem 1rem;
        height: 56px;
        display: flex;
        align-items: center;
        flex-direction: row;
        gap: 1rem;
    }
    
    .content-header h1 {
        font-size: 1.1rem;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 60%;
    }
    
    .quick-actions-grid {
        grid-template-columns: 1fr;
    }

    .content-body {
        padding: 1rem;
    }

    .user-info-dropdown {
        padding: 0.4rem 0.75rem;
    }

    .user-details {
        display: none;
    }

    .welcome-card {
        padding: 1.5rem;
    }

    .welcome-card h2 {
        font-size: 1.25rem;
    }
}

@media (max-width: 575.98px) {
    /* Fix: Header lebih kompak */
    .content-header {
        padding: 0.5rem 0.75rem;
        height: 52px;
    }
    
    .main-content {
        padding-top: 52px;
    }
    
    .content-header h1 {
        font-size: 1rem;
        max-width: 50%;
    }
    
    .hamburger-btn {
        padding: 0.35rem 0.5rem;
        margin-right: 0.5rem;
    }
    
    .hamburger-btn i {
        font-size: 1rem;
    }
    
    .sidebar-header {
        padding: 1rem;
        padding-top: max(1rem, env(safe-area-inset-top, 1rem));
    }
    
    .stat-card .stat-value {
        font-size: 1.5rem;
    }

    .stat-card .stat-icon {
        width: 3rem;
        height: 3rem;
        font-size: 1.25rem;
    }

    .chart-card {
        padding: 1rem;
    }

    .chart-card .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .chart-container {
        height: 250px;
    }

    .welcome-card {
        padding: 1.25rem;
    }

    .welcome-card h2 {
        font-size: 1.1rem;
    }

    .welcome-card p {
        font-size: 0.9rem;
    }

    /* User Index Page Responsive */
    .page-header-actions {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 1rem !important;
    }

    .page-header-actions .btn {
        width: 100%;
        justify-content: center;
    }

    .search-form .input-group {
        flex-wrap: wrap !important;
        max-width: 100% !important;
    }

    .search-form .input-group-text {
        display: none;
    }

    .search-form .form-control {
        border-radius: 0.375rem !important;
        margin-bottom: 0.5rem;
    }

    .search-form .btn {
        width: 100%;
        border-radius: 0.375rem !important;
    }

    .search-form .btn-outline-secondary {
        display: none;
    }
    
    .floating-btn {
        bottom: 1.5rem;
        right: 1.5rem;
        width: 50px;
        height: 50px;
        font-size: 1.25rem;
    }

    /* User Card List - Mobile View */
    .user-card-list {
        padding: 0;
    }

    .user-card {
        background: #fff;
        border-bottom: 1px solid var(--border-color);
        padding: 1rem;
    }

    .user-card:last-child {
        border-bottom: none;
    }

    .user-card-header {
        display: flex;
        align-items: center;
        margin-bottom: 0.75rem;
    }

    .user-card-info {
        flex: 1;
        min-width: 0;
    }

    .user-card-info h5 {
        font-size: 1rem;
        font-weight: 600;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-card-info small {
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-card-body {
        margin-bottom: 0.75rem;
    }

    .user-card-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.35rem 0;
        font-size: 0.875rem;
    }

    .user-card-footer {
        display: flex;
        gap: 0.5rem;
    }

    .user-card-footer .btn {
        flex: 1;
        justify-content: center;
    }
}
    </style>
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    
    <div class="admin-wrapper">
        <div class="admin-layout">
            <!-- Sidebar -->
            <aside class="sidebar" id="sidebar">
                <div class="sidebar-header">
                    <a href="<?= BASE_URL ?>/dashboard/admin.php" class="brand">
                        <i class="bi bi-house-heart-fill"></i>
                        <span>Hadrah App</span>
                    </a>
                </div>
                
                <nav class="sidebar-nav">
                    <div class="nav-section">
                        <div class="nav-section-title">Menu Utama</div>
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/dashboard/admin.php" class="nav-link active">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/modules/user/index.php" class="nav-link">
                                    <i class="fas fa-users"></i>
                                    <span>Manajemen User</span>
                                    <span class="badge bg-primary rounded-pill"><?= $stats['total_users'] ?></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/modules/jadwallatihan/index.php" class="nav-link">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Jadwal Latihan</span>
                                </a>
                            </li>
                            <li class="nav-item">
<a href="<?= BASE_URL ?>/modules/absenlatihan/index.php" class="nav-link">
                                    <i class="fas fa-clipboard-check"></i>
                                    <span>Absensi Latihan</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/modules/acara/index.php" class="nav-link">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Booking Acara</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="nav-section">
                        <div class="nav-section-title">Manajemen</div>
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/modules/alat/index.php" class="nav-link">
                                    <i class="fas fa-music"></i>
                                    <span>Inventaris Alat</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/modules/keuangan/index.php" class="nav-link">
                                    <i class="fas fa-wallet"></i>
                                    <span>Keuangan</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/modules/dresscode/index.php" class="nav-link">
                                    <i class="fas fa-tshirt"></i>
                                    <span>Dresscode</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="nav-section">
                        <div class="nav-section-title">Sistem</div>
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/modules/user/edit.php?id=<?= $user_id ?>" class="nav-link">
                                    <i class="fas fa-user-cog"></i>
                                    <span>Profil Saya</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-link">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>
            </aside>
            
            <!-- Main Content -->
            <main class="main-content" id="mainContent">
                <!-- Header -->
                <header class="content-header">
                    <div class="d-flex align-items-center gap-3">
                        <!-- Hamburger Button -->
                        <button class="hamburger-btn" id="hamburgerBtn" onclick="toggleSidebar()" aria-label="Toggle sidebar">
                            <i class="fas fa-bars"></i>
                        </button>
                        
                        <div>
                            <h1 class="mb-1">Dashboard Admin</h1>
                            <p class="text-muted mb-0 small">Kelola sistem informasi grup hadrah</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center gap-3">
                        <div class="user-info-dropdown" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                <?= strtoupper(substr($user['username'] ?? 'A', 0, 2)) ?>
                            </div>
                            <div class="user-details">
                                <div class="user-name"><?= htmlspecialchars(explode(' ', $user['nama_lengkap'] ?? $_SESSION['nama'])[0]) ?></div>
                                <div class="user-role">Administrator</div>
                            </div>
                            <i class="fas fa-chevron-down ms-2"></i>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/modules/user/edit.php?id=<?= $user_id ?>">
                                    <i class="fas fa-user me-2"></i> Profil Saya
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </header>
                
                <!-- Content Body -->
                <div class="content-body">
<!-- Welcome Card -->
                    <div class="welcome-card">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="mb-2">Selamat Datang, <?= htmlspecialchars(explode(' ', $user['nama_lengkap'] ?? $_SESSION['nama'])[0]) ?>! 👋</h2>
                                <p class="mb-0 opacity-75">Ini adalah dashboard admin untuk mengelola seluruh sistem informasi grup hadrah.</p>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <div class="d-flex flex-column align-items-md-end gap-2">
                                    <span class="badge bg-white text-primary px-3 py-2">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= date('d M Y') ?>
                                    </span>
                                    <?php if ($stats['has_jadwal_hari_ini']): ?>
                                        <?php foreach ($stats['jadwal_hari_ini_list'] as $jadwal): ?>
                                            <a href="<?= BASE_URL ?>/modules/absenlatihan/absen.php?id=<?= $jadwal['id_jadwal'] ?>" class="btn btn-warning btn-sm fw-bold text-dark">
                                                <i class="fas fa-clipboard-check me-1"></i>
                                                ABSEN - <?= htmlspecialchars($jadwal['lokasi']) ?>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
<!-- Stats Row -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-card stat-primary">
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-value" id="stat-total-users"><?= $stats['total_users'] ?></div>
                                <div class="stat-label">Total User</div>
                                <div class="stat-trend up">
                                    <i class="fas fa-arrow-up"></i>
                                    <span id="stat-active-users"><?= $stats['active_users'] ?></span> aktif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-card stat-success">
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="stat-value" id="stat-jadwal"><?= $stats['jadwal_bulan_ini'] ?></div>
                                <div class="stat-label">Jadwal Latihan</div>
                                <div class="stat-trend">Bulan ini</div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-card stat-warning">
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="stat-value" id="stat-booking"><?= $stats['booking_aktif'] ?></div>
                                <div class="stat-label">Booking Aktif</div>
                                <div class="stat-trend">Menunggu/Diterima</div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-card stat-info">
                                <div class="stat-icon">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <div class="stat-value" id="stat-saldo">Rp <?= number_format($stats['keuangan']['saldo'], 0, ',', '.') ?></div>
                                <div class="stat-label">Saldo Kas</div>
                                <div class="stat-trend">
                                    <small>Pemasukan: Rp <?= number_format($stats['keuangan']['pemasukan'], 0, ',', '.') ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Absensi Statistics Row -->
                    <div class="row g-4 mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="card-title text-white mb-0">
                                            <i class="fas fa-clipboard-check me-2"></i>
                                            Statistik Absensi
                                        </h5>
                                        <a href="<?= BASE_URL ?>/modules/absenlatihan/index.php" class="btn btn-light btn-sm">
                                            <i class="fas fa-external-link-alt me-1"></i>Detail
                                        </a>
                                    </div>
                                    <div class="row g-3">
                                        <?php
                                        $total_absensi = $stats['absensi']['total'];
                                        $hadir_absensi = $stats['absensi']['hadir'];
                                        $izin_absensi = $stats['absensi']['izin'];
                                        $alpa_absensi = $stats['absensi']['alpa'];
                                        $hadir_percent = $total_absensi > 0 ? round(($hadir_absensi / $total_absensi) * 100) : 0;
                                        $izin_percent = $total_absensi > 0 ? round(($izin_absensi / $total_absensi) * 100) : 0;
                                        $alpa_percent = $total_absensi > 0 ? round(($alpa_absensi / $total_absensi) * 100) : 0;
                                        ?>
                                        <div class="col-md-3 col-6">
                                            <div class="text-center text-white">
                                                <div class="h2 mb-0 fw-bold"><?= $total_absensi ?></div>
                                                <small class="opacity-75">Total Absensi</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="text-center text-white">
                                                <div class="h2 mb-0 fw-bold text-success">
                                                    <i class="fas fa-check-circle"></i> <?= $hadir_absensi ?>
                                                </div>
                                                <small class="opacity-75">Hadir (<?= $hadir_percent ?>%)</small>
                                                <div class="progress mt-2" style="height: 6px;">
                                                    <div class="progress-bar bg-success" style="width: <?= $hadir_percent ?>%;"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="text-center text-white">
                                                <div class="h2 mb-0 fw-bold text-warning">
                                                    <i class="fas fa-exclamation-triangle"></i> <?= $izin_absensi ?>
                                                </div>
                                                <small class="opacity-75">Izin (<?= $izin_percent ?>%)</small>
                                                <div class="progress mt-2" style="height: 6px;">
                                                    <div class="progress-bar bg-warning" style="width: <?= $izin_percent ?>%;"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="text-center text-white">
                                                <div class="h2 mb-0 fw-bold text-danger">
                                                    <i class="fas fa-times-circle"></i> <?= $alpa_absensi ?>
                                                </div>
                                                <small class="opacity-75">Alpa (<?= $alpa_percent ?>%)</small>
                                                <div class="progress mt-2" style="height: 6px;">
                                                    <div class="progress-bar bg-danger" style="width: <?= $alpa_percent ?>%;"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dresscode Statistics Widget -->
                    <div class="row g-4 mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="card-title text-white mb-0">
                                            <i class="fas fa-tshirt me-2"></i>
                                            Statistik Dresscode
                                        </h5>
                                        <a href="<?= BASE_URL ?>/modules/dresscode/index.php" class="btn btn-light btn-sm">
                                            <i class="fas fa-external-link-alt me-1"></i>Kelola
                                        </a>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-3 col-6">
                                            <div class="text-center text-white">
                                                <div class="h2 mb-0 fw-bold"><?= $stats['dresscode']['total'] ?></div>
                                                <small class="opacity-75">Total Dresscode</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="text-center text-white">
                                                <div class="h2 mb-0 fw-bold text-success">
                                                    <i class="fas fa-check"></i> <?= $stats['dresscode']['aktif'] ?>
                                                </div>
                                                <small class="opacity-75">Dresscode Aktif</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="text-center text-white">
                                                <div class="h2 mb-0 fw-bold text-warning">
                                                    <i class="fas fa-pause"></i> <?= $stats['dresscode']['nonaktif'] ?>
                                                </div>
                                                <small class="opacity-75">Dresscode Nonaktif</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="text-center text-white">
                                                <div class="h2 mb-0 fw-bold text-info">
                                                    <i class="fas fa-calendar-check"></i> <?= $stats['dresscode']['digunakan'] ?>
                                                </div>
                                                <small class="opacity-75">Digunakan di Acara</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

<!-- Alat (Inventory) Statistics Widget -->
                    <div class="row g-4 mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #fd7e14 0%, #e55c00 100%);">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="card-title text-white mb-0">
                                            <i class="fas fa-music me-2"></i>
                                            Statistik Inventaris Alat
                                        </h5>
                                        <a href="<?= BASE_URL ?>/modules/alat/index.php" class="btn btn-light btn-sm">
                                            <i class="fas fa-external-link-alt me-1"></i>Kelola
                                        </a>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-3 col-6">
                                            <div class="text-center text-white">
                                                <div class="h2 mb-0 fw-bold"><?= $stats['alat']['total'] ?></div>
                                                <small class="opacity-75">Jenis Alat</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="text-center text-white">
                                                <div class="h2 mb-0 fw-bold text-success">
                                                    <i class="fas fa-check-circle"></i> <?= $stats['alat']['total_baik'] ?>
                                                </div>
                                                <small class="opacity-75">Kondisi Baik</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="text-center text-white">
                                                <div class="h2 mb-0 fw-bold text-danger">
                                                    <i class="fas fa-times-circle"></i> <?= $stats['alat']['total_rusak'] ?>
                                                </div>
                                                <small class="opacity-75">Kondisi Rusak</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="text-center text-white">
                                                <div class="h2 mb-0 fw-bold text-white">
                                                    <i class="fas fa-music"></i> <?= $stats['alat']['total_semua'] ?>
                                                </div>
                                                <small class="opacity-75">Total Alat</small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($stats['alat']['total_semua'] > 0): ?>
                                    <div class="mt-3">
                                        <div class="d-flex justify-content-between text-white mb-1">
                                            <small>Kondisi Baik Keseluruhan</small>
                                            <small class="fw-bold"><?= $stats['alat']['persen_baik'] ?>%</small>
                                        </div>
                                        <div class="progress" style="height: 8px; opacity: 0.8;">
                                            <div class="progress-bar bg-success" style="width: <?= $stats['alat']['persen_baik'] ?>%;"></div>
                                            <div class="progress-bar bg-danger" style="width: <?= 100 - $stats['alat']['persen_baik'] ?>%;"></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Acara Statistics Widget -->
                    <?php
                    // Get booking statistics
                    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM booking_acara GROUP BY status");
                    $booking_stats = [];
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $booking_stats[$row['status']] = $row['count'];
                    }
                    $total_booking = array_sum($booking_stats);
                    ?>
                    <div class="row g-4 mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #0dcaf0 0%, #0891b2 100%);">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="card-title text-white mb-0">
                                            <i class="fas fa-calendar-check me-2"></i>
                                            Statistik Booking Acara
                                        </h5>
                                        <a href="<?= BASE_URL ?>/modules/acara/index.php" class="btn btn-light btn-sm">
                                            <i class="fas fa-external-link-alt me-1"></i>Kelola
                                        </a>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-3 col-6">
                                            <div class="text-center text-white">
                                                <div class="h2 mb-0 fw-bold"><?= $total_booking ?></div>
                                                <small class="opacity-75">Total Booking</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="text-center text-white">
                                                <div class="h2 mb-0 fw-bold text-warning">
                                                    <i class="fas fa-clock"></i> <?= $booking_stats['menunggu'] ?? 0 ?>
                                                </div>
                                                <small class="opacity-75">Menunggu</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="text-center text-white">
                                                <div class="h2 mb-0 fw-bold text-success">
                                                    <i class="fas fa-check"></i> <?= $booking_stats['diterima'] ?? 0 ?>
                                                </div>
                                                <small class="opacity-75">Diterima</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="text-center text-white">
                                                <div class="h2 mb-0 fw-bold text-danger">
                                                    <i class="fas fa-times"></i> <?= $booking_stats['ditolak'] ?? 0 ?>
                                                </div>
                                                <small class="opacity-75">Ditolak</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-3 mt-2">
                                        <div class="col-md-3 col-6">
                                            <div class="text-center text-white">
                                                <div class="h4 mb-0 fw-bold text-info">
                                                    <i class="fas fa-check-double"></i> <?= $booking_stats['selesai'] ?? 0 ?>
                                                </div>
                                                <small class="opacity-75">Selesai</small>
                                            </div>
                                        </div>
                                        <?php if ($total_booking > 0): ?>
                                        <div class="col-md-9 col-6">
                                            <?php
                                            $menunggu_pct = round((($booking_stats['menunggu'] ?? 0) / $total_booking) * 100);
                                            $diterima_pct = round((($booking_stats['diterima'] ?? 0) / $total_booking) * 100);
                                            $ditolak_pct = round((($booking_stats['ditolak'] ?? 0) / $total_booking) * 100);
                                            $selesai_pct = round((($booking_stats['selesai'] ?? 0) / $total_booking) * 100);
                                            ?>
                                            <div class="d-flex gap-1">
                                                <div class="bg-warning" style="flex: <?= $menunggu_pct ?>; height: 8px; border-radius: 4px 0 0 4px;" title="Menunggu: <?= $menunggu_pct ?>%"></div>
                                                <div class="bg-success" style="flex: <?= $diterima_pct ?>; height: 8px;" title="Diterima: <?= $diterima_pct ?>%"></div>
                                                <div class="bg-danger" style="flex: <?= $ditolak_pct ?>; height: 8px;" title="Ditolak: <?= $ditolak_pct ?>%"></div>
                                                <div class="bg-info" style="flex: <?= $selesai_pct ?>; height: 8px; border-radius: 0 4px 4px 0;" title="Selesai: <?= $selesai_pct ?>%"></div>
                                            </div>
                                            <div class="d-flex justify-content-between mt-1">
                                                <small class="text-white opacity-75">Menunggu: <?= $menunggu_pct ?>%</small>
                                                <small class="text-white opacity-75">Diterima: <?= $diterima_pct ?>%</small>
                                                <small class="text-white opacity-75">Ditolak: <?= $ditolak_pct ?>%</small>
                                                <small class="text-white opacity-75">Selesai: <?= $selesai_pct ?>%</small>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- KEUANGAN STATISTICS WIDGET -->
                    <!-- ========================================== -->
                    <div class="row g-4 mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #198754 0%, #146c43 100%);">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="card-title text-white mb-0">
                                            <i class="fas fa-wallet me-2"></i>
                                            Statistik Keuangan
                                        </h5>
                                        <a href="<?= BASE_URL ?>/modules/keuangan/index.php" class="btn btn-light btn-sm">
                                            <i class="fas fa-external-link-alt me-1"></i>Detail Kas
                                        </a>
                                    </div>
                                    <div class="row g-3">
                                        <!-- Total Saldo -->
                                        <div class="col-md-3 col-6">
                                            <div class="text-center text-white">
                                                <div class="h3 mb-0 fw-bold">Rp <?= number_format($stats['keuangan']['saldo'], 0, ',', '.') ?></div>
                                                <small class="opacity-75">Total Saldo Kas</small>
                                            </div>
                                        </div>
                                        <!-- Total Pemasukan -->
                                        <div class="col-md-3 col-6">
                                            <div class="text-center text-white">
                                                <div class="h3 mb-0 fw-bold text-success">
                                                    <i class="fas fa-arrow-down"></i> Rp <?= number_format($stats['keuangan']['pemasukan'], 0, ',', '.') ?>
                                                </div>
                                                <small class="opacity-75">Total Pemasukan</small>
                                            </div>
                                        </div>
                                        <!-- Total Pengeluaran -->
                                        <div class="col-md-3 col-6">
                                            <div class="text-center text-white">
                                                <div class="h3 mb-0 fw-bold text-danger">
                                                    <i class="fas fa-arrow-up"></i> Rp <?= number_format($stats['keuangan']['pengeluaran'], 0, ',', '.') ?>
                                                </div>
                                                <small class="opacity-75">Total Pengeluaran</small>
                                            </div>
                                        </div>
                                        <!-- Total Transaksi -->
                                        <div class="col-md-3 col-6">
                                            <div class="text-center text-white">
                                                <div class="h3 mb-0 fw-bold text-info">
                                                    <i class="fas fa-list"></i> <?= $stats['keuangan']['total_transaksi'] ?? 0 ?>
                                                </div>
                                                <small class="opacity-75">Total Transaksi</small>
                                            </div>
                                        </div>
                                    </div>
                                    <hr style="border-color: rgba(255,255,255,0.3);">
                                    <!-- Bulan Ini vs Bulan Lalu -->
                                    <div class="row g-3 mt-2">
                                        <div class="col-md-4 col-12">
                                            <div class="text-center text-white">
                                                <div class="h5 mb-0">Bulan Ini</div>
                                                <div class="small opacity-75 mb-2">
                                                    <span class="text-success">+Rp <?= number_format($stats['keuangan']['bulan_ini_pemasukan'] ?? 0, 0, ',', '.') ?></span> | 
                                                    <span class="text-danger">-Rp <?= number_format($stats['keuangan']['bulan_ini_pengeluaran'] ?? 0, 0, ',', '.') ?></span>
                                                </div>
                                                <div class="badge bg-light text-success px-3 py-2">
                                                    Saldo: Rp <?= number_format($stats['keuangan']['bulan_ini_saldo'] ?? 0, 0, ',', '.') ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-6">
                                            <div class="text-center text-white">
                                                <div class="h5 mb-0">Hari Ini</div>
                                                <div class="small opacity-75 mb-2">
                                                    <span class="text-success">+Rp <?= number_format($stats['keuangan']['hari_ini']['pemasukan'] ?? 0, 0, ',', '.') ?></span> | 
                                                    <span class="text-danger">-Rp <?= number_format($stats['keuangan']['hari_ini']['pengeluaran'] ?? 0, 0, ',', '.') ?></span>
                                                </div>
                                                <div class="small">
                                                    <i class="fas fa-exchange-alt"></i> <?= $stats['keuangan']['hari_ini']['total_transaksi'] ?? 0 ?> transaksi
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-6">
                                            <div class="text-center text-white">
                                                <div class="h5 mb-0">Bulan Lalu</div>
                                                <div class="small opacity-75 mb-2">
                                                    <span class="text-success">+Rp <?= number_format($stats['keuangan']['bulan_lalu_pemasukan'] ?? 0, 0, ',', '.') ?></span> | 
                                                    <span class="text-danger">-Rp <?= number_format($stats['keuangan']['bulan_lalu_pengeluaran'] ?? 0, 0, ',', '.') ?></span>
                                                </div>
                                                <?php
                                                $growth = 0;
                                                if (($stats['keuangan']['bulan_lalu_pemasukan'] ?? 0) > 0) {
                                                    $growth = round((($stats['keuangan']['bulan_ini_pemasukan'] - $stats['keuangan']['bulan_lalu_pemasukan']) / $stats['keuangan']['bulan_lalu_pemasukan']) * 100, 1);
                                                }
                                                ?>
                                                <div class="badge <?= $growth >= 0 ? 'bg-light text-success' : 'bg-danger' ?> px-3 py-2">
                                                    <i class="fas <?= $growth >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' ?>"></i> <?= abs($growth) ?>% vs bulan lalu
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kategori Pemasukan & Pengeluaran Breakdown -->
                    <div class="row g-4 mb-4">
                        <!-- Kategori Pemasukan -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-success text-white py-3">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-arrow-down me-2"></i>
                                        Kategori Pemasukan
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($stats['keuangan']['kategori_pemasukan'])): ?>
                                        <div class="text-center py-4 text-muted">
                                            <i class="fas fa-wallet fa-3x mb-3 d-block text-secondary"></i>
                                            <p class="mb-0">Belum ada data pemasukan</p>
                                        </div>
                                    <?php else: ?>
                                        <?php 
                                        $total_pemasukan = array_sum(array_column($stats['keuangan']['kategori_pemasukan'], 'total'));
                                        foreach ($stats['keuangan']['kategori_pemasukan'] as $kat): 
                                            $percent = $total_pemasukan > 0 ? round(($kat['total'] / $total_pemasukan) * 100) : 0;
                                        ?>
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span><?= htmlspecialchars($kat['kategori']) ?></span>
                                                    <span class="fw-bold">Rp <?= number_format($kat['total'], 0, ',', '.') ?></span>
                                                </div>
                                                <div class="progress" style="height: 10px;">
                                                    <div class="progress-bar bg-success" style="width: <?= $percent ?>%;"></div>
                                                </div>
                                                <small class="text-muted"><?= $percent ?>% dari total pemasukan</small>
                                            </div>
                                        <?php endforeach; ?>
                                        <div class="text-center mt-3">
                                            <span class="badge bg-success px-3 py-2">
                                                Total: Rp <?= number_format($total_pemasukan, 0, ',', '.') ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Kategori Pengeluaran -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-danger text-white py-3">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-arrow-up me-2"></i>
                                        Kategori Pengeluaran
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($stats['keuangan']['kategori_pengeluaran'])): ?>
                                        <div class="text-center py-4 text-muted">
                                            <i class="fas fa-wallet fa-3x mb-3 d-block text-secondary"></i>
                                            <p class="mb-0">Belum ada data pengeluaran</p>
                                        </div>
                                    <?php else: ?>
                                        <?php 
                                        $total_pengeluaran = array_sum(array_column($stats['keuangan']['kategori_pengeluaran'], 'total'));
                                        foreach ($stats['keuangan']['kategori_pengeluaran'] as $kat): 
                                            $percent = $total_pengeluaran > 0 ? round(($kat['total'] / $total_pengeluaran) * 100) : 0;
                                        ?>
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span><?= htmlspecialchars($kat['kategori']) ?></span>
                                                    <span class="fw-bold">Rp <?= number_format($kat['total'], 0, ',', '.') ?></span>
                                                </div>
                                                <div class="progress" style="height: 10px;">
                                                    <div class="progress-bar bg-danger" style="width: <?= $percent ?>%;"></div>
                                                </div>
                                                <small class="text-muted"><?= $percent ?>% dari total pengeluaran</small>
                                            </div>
                                        <?php endforeach; ?>
                                        <div class="text-center mt-3">
                                            <span class="badge bg-danger px-3 py-2">
                                                Total: Rp <?= number_format($total_pengeluaran, 0, ',', '.') ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transaksi Terbaru & Tren Chart -->
                    <div class="row g-4 mb-4">
                        <!-- Transaksi Terbaru -->
                        <div class="col-lg-5">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header py-3">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-history me-2"></i>
                                        Transaksi Terbaru
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($stats['keuangan']['recent_transactions'])): ?>
                                        <div class="text-center py-4 text-muted">
                                            <i class="fas fa-receipt fa-3x mb-3 d-block text-secondary"></i>
                                            <p class="mb-0">Belum ada transaksi</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Tipe</th>
                                                        <th>Kategori</th>
                                                        <th>Jumlah</th>
                                                        <th>Tanggal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($stats['keuangan']['recent_transactions'] as $trx): ?>
                                                        <tr>
                                                            <td>
                                                                <span class="badge <?= $trx['tipe'] === 'pemasukan' ? 'bg-success' : 'bg-danger' ?>">
                                                                    <?= $trx['tipe'] === 'pemasukan' ? '<i class="fas fa-arrow-down"></i>' : '<i class="fas fa-arrow-up"></i>' ?>
                                                                    <?= ucfirst($trx['tipe']) ?>
                                                                </span>
                                                            </td>
                                                            <td><?= htmlspecialchars($trx['kategori'] ?? '-') ?></td>
                                                            <td class="fw-bold <?= $trx['tipe'] === 'pemasukan' ? 'text-success' : 'text-danger' ?>">
                                                                Rp <?= number_format($trx['jumlah'], 0, ',', '.') ?>
                                                            </td>
                                                            <td><?= date('d/m/Y', strtotime($trx['tanggal'])) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tren Keuangan Chart -->
                        <div class="col-lg-7">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header py-3">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-line me-2"></i>
                                        Tren Keuangan (6 Bulan Terakhir)
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="keuanganTrenChart" style="max-height: 250px;"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Main Content Grid -->
                    <div class="row g-4">
                        <!-- Activity Chart Section -->
                        <div class="col-lg-8">
                            <div class="chart-card">
                                <div class="card-header">
                                    <div>
                                        <h5 class="card-title mb-1">
                                            <i class="fas fa-chart-bar me-2 text-primary"></i>
                                            Aktivitas 7 Hari Terakhir
                                        </h5>
                                        <p class="card-subtitle mb-0">Grafik aktivitas sistem berdasarkan kategori</p>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary" onclick="refreshStats()">
                                        <i class="fas fa-sync-alt me-1"></i> Refresh
                                    </button>
                                </div>
                                <div class="chart-container">
                                    <canvas id="activityChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Activities -->
                        <div class="col-lg-4">
                            <div class="chart-card h-100">
                                <div class="card-header">
                                    <h5 class="card-title mb-1">
                                        <i class="fas fa-clock me-2 text-primary"></i>
                                        Aktivitas Terbaru
                                    </h5>
                                    <p class="card-subtitle mb-0">Aktivitas sistem terkini</p>
                                </div>
                                <ul class="activity-list" id="recent-activities">
                                    <?php foreach ($recent_activities as $activity): ?>
                                        <li class="activity-item">
                                            <div class="activity-icon <?= $activity['icon_bg'] ?>">
                                                <i class="fas <?= $activity['icon'] ?>"></i>
                                            </div>
                                            <div class="activity-content">
                                                <div class="activity-title"><?= htmlspecialchars($activity['title']) ?></div>
                                                <div class="activity-time">
                                                    <i class="far fa-clock me-1"></i>
                                                    <?= timeAgo($activity['created_at']) ?>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="chart-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-bolt me-2 text-warning"></i>
                                        Aksi Cepat
                                    </h5>
                                </div>
                                <div class="p-3">
                                    <div class="quick-actions-grid">
                                        <a href="<?= BASE_URL ?>/modules/user/tambah.php" class="quick-action-btn">
                                            <i class="fas fa-user-plus text-primary"></i>
                                            <span>Tambah User</span>
                                        </a>
                                        <a href="<?= BASE_URL ?>/modules/jadwallatihan/tambah.php" class="quick-action-btn">
                                            <i class="fas fa-calendar-plus text-success"></i>
                                            <span>Buat Jadwal</span>
                                        </a>
                                        <a href="<?= BASE_URL ?>/modules/acara/index.php" class="quick-action-btn">
                                            <i class="fas fa-clipboard-check text-info"></i>
                                            <span>Cek Booking</span>
                                        </a>
                                        <a href="<?= BASE_URL ?>/modules/keuangan/tambah.php" class="quick-action-btn">
                                            <i class="fas fa-file-invoice-dollar text-warning"></i>
                                            <span>Input Kas</span>
                                        </a>
                                        <a href="<?= BASE_URL ?>/modules/alat/index.php" class="quick-action-btn">
                                            <i class="fas fa-boxes text-secondary"></i>
                                            <span>Stok Alat</span>
                                        </a>
                                        <a href="<?= BASE_URL ?>/modules/dresscode/index.php" class="quick-action-btn">
                                            <i class="fas fa-tshirt text-danger"></i>
                                            <span>Dresscode</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Jadwal Latihan Widget -->
                    <div class="row mt-4">
                        <div class="col-lg-8">
                            <div class="chart-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-1">
                                        <i class="fas fa-calendar-alt me-2 text-success"></i>
                                        Jadwal Latihan Mendatang
                                    </h5>
                                    <p class="card-subtitle mb-0">Jadwal latihan yang akan datang</p>
                                </div>
                                <div class="p-3">
                                    <?php
                                    // Get upcoming schedules (next 5)
                                    $stmt = $pdo->query("SELECT * FROM jadwal_latihan WHERE status = 'direncanakan' AND tanggal >= CURDATE() ORDER BY tanggal ASC, jam_mulai ASC LIMIT 5");
                                    $upcoming_jadwals = $stmt->fetchAll();
                                    
                                    if (empty($upcoming_jadwals)):
                                    ?>
                                        <div class="text-center py-4 text-muted">
                                            <i class="fas fa-calendar-times fa-3x mb-3 d-block text-secondary"></i>
                                            <p class="mb-0">Belum ada jadwal latihan yang direncanakan</p>
                                            <a href="<?= BASE_URL ?>/modules/jadwallatihan/tambah.php" class="btn btn-primary mt-3">
                                                <i class="fas fa-plus me-1"></i>Buat Jadwal
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Tanggal</th>
                                                        <th>Jam</th>
                                                        <th>Lokasi</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($upcoming_jadwals as $jadwal): ?>
                                                        <?php
                                                        $tanggal = new DateTime($jadwal['tanggal']);
                                                        $is_today = $tanggal->format('Y-m-d') === date('Y-m-d');
                                                        ?>
                                                        <tr class="<?= $is_today ? 'table-warning' : '' ?>">
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div class="bg-<?= $is_today ? 'warning' : 'primary' ?> text-white rounded me-2 text-center" style="width: 40px; height: 40px; line-height: 1;">
                                                                        <div class="small"><?= $tanggal->format('d') ?></div>
                                                                        <div class="small" style="font-size: 9px;"><?= $tanggal->format('M') ?></div>
                                                                    </div>
                                                                    <div>
                                                                        <div class="fw-bold"><?= $tanggal->format('l') ?></div>
                                                                        <small class="text-muted"><?= $tanggal->format('Y') ?></small>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <i class="far fa-clock text-muted me-1"></i>
                                                                <?= date('H:i', strtotime($jadwal['jam_mulai'])) ?>
                                                            </td>
                                                            <td>
                                                                <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                                                <?= htmlspecialchars($jadwal['lokasi']) ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($is_today): ?>
                                                                    <span class="badge bg-warning text-dark">Hari Ini</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-success">Direncanakan</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="text-center mt-3">
                                            <a href="<?= BASE_URL ?>/modules/jadwallatihan/index.php" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-list me-1"></i>Lihat Semua Jadwal
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="chart-card h-100">
                                <div class="card-header">
                                    <h5 class="card-title mb-1">
                                        <i class="fas fa-chart-pie me-2 text-primary"></i>
                                        Status Jadwal
                                    </h5>
                                    <p class="card-subtitle mb-0">Ringkasan statistik</p>
                                </div>
                                <div class="p-3">
                                    <?php
                                    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM jadwal_latihan GROUP BY status");
                                    $jadwal_stats = $stmt->fetchAll();
                                    $total_jadwal = array_sum(array_column($jadwal_stats, 'count'));
                                    ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Total Jadwal</span>
                                            <span class="fw-bold"><?= $total_jadwal ?></span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar" style="width: 100%;"></div>
                                        </div>
                                    </div>
                                    <?php foreach ($jadwal_stats as $stat): ?>
                                        <?php
                                        $percent = $total_jadwal > 0 ? round(($stat['count'] / $total_jadwal) * 100) : 0;
                                        $color = match($stat['status']) {
                                            'direncanakan' => 'bg-warning',
                                            'selesai' => 'bg-success',
                                            'dibatalkan' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span><?= ucfirst($stat['status']) ?></span>
                                                <span class="fw-bold"><?= $stat['count'] ?></span>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar <?= $color ?>" style="width: <?= $percent ?>%;"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <hr>
                                    
                                    <?php
                                    // Jadwal hari ini
                                    $stmt = $pdo->query("SELECT COUNT(*) FROM jadwal_latihan WHERE tanggal = CURDATE() AND status = 'direncanakan'");
                                    $jadwal_hari_ini = $stmt->fetchColumn();
                                    ?>
                                    <div class="text-center p-3 bg-warning bg-opacity-10 rounded">
                                        <div class="h2 mb-0 text-warning">
                                            <i class="fas fa-calendar-day"></i>
                                        </div>
                                        <div class="h4 mb-0"><?= $jadwal_hari_ini ?></div>
                                        <small class="text-muted">Jadwal Hari Ini</small>
                                    </div>
                                    
                                    <div class="text-center mt-3">
                                        <a href="<?= BASE_URL ?>/modules/jadwallatihan/tambah.php" class="btn btn-success btn-sm w-100">
                                            <i class="fas fa-plus me-1"></i>Tambah Jadwal Baru
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Distribution (Role Breakdown) -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="chart-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-1">
                                        <i class="fas fa-users-cog me-2 text-primary"></i>
                                        Distribusi User
                                    </h5>
                                    <p class="card-subtitle mb-0">Berdasarkan peran</p>
                                </div>
                                <div class="chart-container" style="height: 200px;">
                                    <canvas id="roleDistributionChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="chart-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-1">
                                        <i class="fas fa-info-circle me-2 text-primary"></i>
                                        Info Sistem
                                    </h5>
                                    <p class="card-subtitle mb-0">Ringkasan aplikasi</p>
                                </div>
                                <div class="p-3">
                                    <table class="table table-sm mb-0">
                                        <tr>
                                            <td><i class="fas fa-user-shield text-danger me-2"></i>Admin</td>
                                            <td class="text-end fw-bold"><?= $stats['users_by_role']['admin'] ?? 0 ?></td>
                                        </tr>
                                        <tr>
                                            <td><i class="fas fa-user-tie text-warning me-2"></i>Pembina</td>
                                            <td class="text-end fw-bold"><?= $stats['users_by_role']['pembina'] ?? 0 ?></td>
                                        </tr>
                                        <tr>
                                            <td><i class="fas fa-user text-primary me-2"></i>Anggota</td>
                                            <td class="text-end fw-bold"><?= $stats['users_by_role']['anggota'] ?? 0 ?></td>
                                        </tr>
                                        <tr class="border-top">
                                            <td class="fw-bold">Total User</td>
                                            <td class="text-end fw-bold"><?= $stats['total_users'] ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dresscode Section -->
                    <div class="row mt-4">
                        <!-- Dresscode Distribution Chart -->
                        <div class="col-md-6">
                            <div class="chart-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-1">
                                        <i class="fas fa-tshirt me-2 text-primary"></i>
                                        Distribusi Dresscode
                                    </h5>
                                    <p class="card-subtitle mb-0">Berdasarkan status</p>
                                </div>
                                <div class="chart-container" style="height: 200px;">
                                    <canvas id="dresscodeDistributionChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Most Used Dresscode -->
                        <div class="col-md-6">
                            <div class="chart-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-1">
                                        <i class="fas fa-star me-2 text-warning"></i>
                                        Dresscode Populer
                                    </h5>
                                    <p class="card-subtitle mb-0">Paling sering digunakan</p>
                                </div>
                                <div class="p-3">
                                    <?php if (empty($stats['dresscode']['most_used'])): ?>
                                        <div class="text-center py-4 text-muted">
                                            <i class="fas fa-tshirt fa-3x mb-3 d-block text-secondary"></i>
                                            <p class="mb-0">Belum ada data dresscode</p>
                                            <a href="<?= BASE_URL ?>/modules/dresscode/tambah.php" class="btn btn-primary mt-3">
                                                <i class="fas fa-plus me-1"></i>Tambah Dresscode
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($stats['dresscode']['most_used'] as $i => $dc): ?>
                                            <?php
                                            $penggunaan = $dc['penggunaan'] ?? 0;
                                            $warna = $dc['warna'] ?: '#6c757d';
                                            // Calculate text color for badge
                                            $hex = ltrim($warna, '#');
                                            if (strlen($hex) === 6) {
                                                $r = hexdec(substr($hex, 0, 2));
                                                $g = hexdec(substr($hex, 2, 2));
                                                $b = hexdec(substr($hex, 4, 2));
                                                $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
                                                $text_color = $brightness > 128 ? '#000000' : '#ffffff';
                                            } else {
                                                $text_color = '#000000';
                                            }
                                            ?>
                                            <div class="d-flex align-items-center mb-3 <?= $i === 0 ? '' : 'border-top pt-3' ?>">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 16px;">
                                                    <?= $i + 1 ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold"><?= htmlspecialchars($dc['nama_pakaian']) ?></div>
                                                    <?php if ($dc['warna']): ?>
                                                        <span class="badge" style="background-color: <?= $warna ?>; color: <?= $text_color ?>; border: 1px solid <?= $warna ?>;">
                                                            <i class="fas fa-palette me-1"></i><?= htmlspecialchars($dc['warna']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-end">
                                                    <div class="h5 mb-0 text-primary"><?= $penggunaan ?></div>
                                                    <small class="text-muted">penggunaan</small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <div class="text-center mt-3">
                                            <a href="<?= BASE_URL ?>/modules/dresscode/index.php" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-list me-1"></i>Lihat Semua Dresscode
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alat (Inventory) Section -->
                    <div class="row mt-4">
                        <!-- Alat Distribution Chart -->
                        <div class="col-md-6">
                            <div class="chart-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-1">
                                        <i class="fas fa-music me-2 text-warning"></i>
                                        Kondisi Alat
                                    </h5>
                                    <p class="card-subtitle mb-0">Berdasarkan kondisi</p>
                                </div>
                                <div class="chart-container" style="height: 200px;">
                                    <canvas id="alatDistributionChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Alat dengan Stok Terbanyak -->
                        <div class="col-md-6">
                            <div class="chart-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-1">
                                        <i class="fas fa-boxes me-2 text-success"></i>
                                        Stok Alat Terbanyak
                                    </h5>
                                    <p class="card-subtitle mb-0">Inventaris berdasarkan jumlah</p>
                                </div>
                                <div class="p-3">
                                    <?php if (empty($stats['alat']['most_stok'])): ?>
                                        <div class="text-center py-4 text-muted">
                                            <i class="fas fa-music fa-3x mb-3 d-block text-secondary"></i>
                                            <p class="mb-0">Belum ada data alat</p>
                                            <a href="<?= BASE_URL ?>/modules/alat/tambah.php" class="btn btn-primary mt-3">
                                                <i class="fas fa-plus me-1"></i>Tambah Alat
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($stats['alat']['most_stok'] as $i => $alat): ?>
                                            <?php $total_stok = $alat['total_stok'] ?? 0; ?>
                                            <div class="d-flex align-items-center mb-3 <?= $i === 0 ? '' : 'border-top pt-3' ?>">
                                                <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 16px;">
                                                    <i class="fas fa-music"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold"><?= htmlspecialchars($alat['nama_alat']) ?></div>
                                                    <small class="text-muted">Total: <?= $total_stok ?> unit</small>
                                                </div>
                                                <div class="text-end">
                                                    <div class="h5 mb-0 text-warning"><?= $total_stok ?></div>
                                                    <small class="text-muted">unit</small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <div class="text-center mt-3">
                                            <a href="<?= BASE_URL ?>/modules/alat/index.php" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-list me-1"></i>Lihat Semua Alat
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Sidebar Toggle Functions
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const hamburger = document.getElementById('hamburgerBtn');
            
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
            hamburger.classList.toggle('active');
            
            // Prevent body scroll when sidebar is open
            document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
        }

        // Close sidebar when pressing escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const sidebar = document.getElementById('sidebar');
                if (sidebar.classList.contains('show')) {
                    toggleSidebar();
                }
            }
        });

// Close sidebar when window is resized to desktop size
        window.addEventListener('resize', function() {
            if (window.innerWidth > 1199.98) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                const hamburger = document.getElementById('hamburgerBtn');
                
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                hamburger.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        // Role Distribution Chart
        const roleCtx = document.getElementById('roleDistributionChart').getContext('2d');
        const roleData = <?php echo json_encode([
            'Admin' => $stats['users_by_role']['admin'] ?? 0,
            'Pembina' => $stats['users_by_role']['pembina'] ?? 0,
            'Anggota' => $stats['users_by_role']['anggota'] ?? 0
        ]); ?>;
        
        new Chart(roleCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(roleData),
                datasets: [{
                    data: Object.values(roleData),
                    backgroundColor: [
                        '#dc3545',
                        '#ffc107',
                        '#0d6efd'
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    }
                },
                cutout: '65%'
            }
        });
        
        // Dresscode Distribution Chart
        const dresscodeCtx = document.getElementById('dresscodeDistributionChart').getContext('2d');
        const dresscodeData = <?php 
            $dresscode_by_status = [];
            foreach ($stats['dresscode']['by_status'] as $row) {
                $dresscode_by_status[ucfirst($row['status'])] = (int)$row['count'];
            }
            echo json_encode($dresscode_by_status);
        ?>;
        
        new Chart(dresscodeCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(dresscodeData),
                datasets: [{
                    data: Object.values(dresscodeData),
                    backgroundColor: [
                        '#198754',
                        '#6c757d'
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    }
                },
                cutout: '65%'
            }
        });
        
// Alat (Inventory) Distribution Chart
        const alatCtx = document.getElementById('alatDistributionChart').getContext('2d');
        const alatData = <?php echo json_encode([
            'Baik' => $stats['alat']['kondisi']['baik'],
            'Rusak' => $stats['alat']['kondisi']['rusak']
        ]); ?>;
        
        new Chart(alatCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(alatData),
                datasets: [{
                    data: Object.values(alatData),
                    backgroundColor: [
                        '#198754',
                        '#dc3545'
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    }
                },
                cutout: '65%'
            }
        });
        
        // Activity Chart (Last 7 Days)
        const activityCtx = document.getElementById('activityChart').getContext('2d');
        
        const activityLabels = <?php echo json_encode(array_column($activity_chart_data, 'label')); ?>;
        const activityUserData = <?php echo json_encode(array_column($activity_chart_data, 'user')); ?>;
        const activityJadwalData = <?php echo json_encode(array_column($activity_chart_data, 'jadwal')); ?>;
        const activityBookingData = <?php echo json_encode(array_column($activity_chart_data, 'booking')); ?>;
        const activityAlatData = <?php echo json_encode(array_column($activity_chart_data, 'alat')); ?>;
        const activityDresscodeData = <?php echo json_encode(array_column($activity_chart_data, 'dresscode')); ?>;
        
        new Chart(activityCtx, {
            type: 'bar',
            data: {
                labels: activityLabels,
                datasets: [
                    {
                        label: 'User',
                        data: activityUserData,
                        backgroundColor: '#0d6efd',
                        borderColor: '#0d6efd',
                        borderWidth: 1,
                        stack: 'stack0'
                    },
                    {
                        label: 'Jadwal',
                        data: activityJadwalData,
                        backgroundColor: '#198754',
                        borderColor: '#198754',
                        borderWidth: 1,
                        stack: 'stack0'
                    },
                    {
                        label: 'Booking',
                        data: activityBookingData,
                        backgroundColor: '#0dcaf0',
                        borderColor: '#0dcaf0',
                        borderWidth: 1,
                        stack: 'stack0'
                    },
                    {
                        label: 'Alat',
                        data: activityAlatData,
                        backgroundColor: '#fd7e14',
                        borderColor: '#fd7e14',
                        borderWidth: 1,
                        stack: 'stack0'
                    },
                    {
                        label: 'Dresscode',
                        data: activityDresscodeData,
                        backgroundColor: '#6f42c1',
                        borderColor: '#6f42c1',
                        borderWidth: 1,
                        stack: 'stack0'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'rect'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: { size: 14 },
                        bodyFont: { size: 13 },
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        ticks: {
                            font: { size: 11 }
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: { size: 11 }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
        
        // Keuangan Trend Chart (6 Bulan Terakhir)
        const keuanganTrenCtx = document.getElementById('keuanganTrenChart').getContext('2d');
        const keuanganTrenLabels = <?php echo json_encode(array_column($stats['keuangan']['tren_6_bulan'], 'label')); ?>;
        const keuanganTrenPemasukan = <?php echo json_encode(array_column($stats['keuangan']['tren_6_bulan'], 'pemasukan')); ?>;
        const keuanganTrenPengeluaran = <?php echo json_encode(array_column($stats['keuangan']['tren_6_bulan'], 'pengeluaran')); ?>;
        
        new Chart(keuanganTrenCtx, {
            type: 'line',
            data: {
                labels: keuanganTrenLabels,
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: keuanganTrenPemasukan,
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#198754'
                    },
                    {
                        label: 'Pengeluaran',
                        data: keuanganTrenPengeluaran,
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#dc3545'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: { size: 14 },
                        bodyFont: { size: 13 },
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            font: { size: 11 }
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: { size: 11 },
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
        
        // Auto-refresh stats every 1 minute
        let refreshInterval;
        
        function refreshStats() {
            // Show loading state
            document.querySelectorAll('.stat-value').forEach(el => {
                el.style.opacity = '0.5';
            });
            
            fetch('<?= BASE_URL ?>/api/stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update stat values
                        document.getElementById('stat-total-users').textContent = data.data.total_users;
                        document.getElementById('stat-active-users').textContent = data.data.active_users;
                        document.getElementById('stat-jadwal').textContent = data.data.jadwal_bulan_ini;
                        document.getElementById('stat-booking').textContent = data.data.booking_aktif;
                        
                        const saldo = data.data.keuangan.saldo;
                        document.getElementById('stat-saldo').textContent = 'Rp ' + saldo.toLocaleString('id-ID');
                        
                        // Update activities
                        const activitiesList = document.getElementById('recent-activities');
                        activitiesList.innerHTML = '';
                        
                        data.data.recent_activities.forEach(activity => {
                            const li = document.createElement('li');
                            li.className = 'activity-item';
                            li.innerHTML = `
                                <div class="activity-icon ${activity.icon_bg}">
                                    <i class="fas ${activity.icon}"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">${activity.title}</div>
                                    <div class="activity-time">
                                        <i class="far fa-clock me-1"></i>
                                        ${activity.time_ago}
                                    </div>
                                </div>
                            `;
                            activitiesList.appendChild(li);
                        });
                        
                        // Update user count badge in sidebar
                        const userBadge = document.querySelector('.nav-link .badge');
                        if (userBadge) {
                            userBadge.textContent = data.data.total_users;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error refreshing stats:', error);
                })
                .finally(() => {
                    // Remove loading state
                    document.querySelectorAll('.stat-value').forEach(el => {
                        el.style.opacity = '1';
                    });
                });
        }
        
        // Start auto-refresh (every 1 minute = 60000ms)
        refreshInterval = setInterval(refreshStats, 60000);
        
        // Store interval ID for potential cleanup
        window.adminRefreshInterval = refreshInterval;
    </script>
</body>
</html>

