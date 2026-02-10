<?php
/**
 * Dashboard Anggota - Halaman Utama untuk Anggota
 * Desain landing page dengan tema Islami
 */

// DEBUG: Aktifkan error reporting untuk troubleshooting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session dan include database
session_start();

// Include database config
require_once '../config/database.php';

// Include auth check untuk keamanan session
require_once '../includes/auth_check.php';

// Ambil data user dari database
$user_id = $_SESSION['user_id'] ?? null;
if (empty($user_id)) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$query = "SELECT * FROM user WHERE id_user = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

// ============================================
// AMBIL DATA UNTUK DASHBOARD
// ============================================

// 1. Jadwal Latihan Mendatang
$stmt = $pdo->query("SELECT id_jadwal, tanggal, jam_mulai, lokasi FROM jadwal_latihan
WHERE status = 'direncanakan' AND tanggal >= CURDATE()
ORDER BY tanggal ASC, jam_mulai ASC LIMIT 5");
$jadwal_mendatang = $stmt->fetchAll();

// Query untuk semua jadwal (untuk modal dengan pagination)
$stmt = $pdo->query("SELECT id_jadwal, tanggal, jam_mulai, lokasi
FROM jadwal_latihan
WHERE status = 'direncanakan' AND tanggal >= CURDATE()
ORDER BY tanggal ASC, jam_mulai ASC");
$semua_jadwal = $stmt->fetchAll();
$jadwal_per_page = 3; // Dikurangi untuk performa lebih baik
$total_jadwal = count($semua_jadwal);
$total_pages_jadwal = ceil($total_jadwal / $jadwal_per_page);

// 2. Jadwal Hari Ini (jika ada)
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT id_jadwal, tanggal, jam_mulai, lokasi FROM jadwal_latihan WHERE tanggal = ? AND status = 'direncanakan' ORDER BY jam_mulai ASC");
$stmt->execute([$today]);
$jadwal_hari_ini = $stmt->fetchAll();

// 3. Booking Acara Mendatang (status menunggu saja untuk display utama)
$stmt = $pdo->query("SELECT ba.*, d.nama_pakaian as dresscode_name, d.warna as dresscode_warna, d.deskripsi as dresscode_deskripsi
FROM booking_acara ba
LEFT JOIN dresscode d ON ba.id_dresscode = d.id_dresscode
WHERE ba.status = 'menunggu' AND ba.tanggal_acara >= CURDATE()
ORDER BY ba.tanggal_acara ASC LIMIT 5");
$acara_mendatang = $stmt->fetchAll();

// Query untuk semua acara (untuk modal dengan pagination - semua status)
$stmt = $pdo->query("SELECT ba.id_booking, ba.nama_acara, ba.tanggal_acara, ba.lokasi, ba.status, d.nama_pakaian as dresscode_name, d.deskripsi as dresscode_deskripsi
FROM booking_acara ba
LEFT JOIN dresscode d ON ba.id_dresscode = d.id_dresscode
WHERE ba.tanggal_acara >= CURDATE()
ORDER BY ba.tanggal_acara ASC");
$semua_acara = $stmt->fetchAll();
$acara_per_page = 3; // Dikurangi untuk performa lebih baik
$total_acara = count($semua_acara);
$total_pages_acara = ceil($total_acara / $acara_per_page);

// 4. Statistik Kehadiran Anggota
$stmt = $pdo->prepare("SELECT
COUNT(*) as total_absensi,
SUM(CASE WHEN status_hadir = 'hadir' THEN 1 ELSE 0 END) as hadir,
SUM(CASE WHEN status_hadir = 'izin' THEN 1 ELSE 0 END) as izin,
SUM(CASE WHEN status_hadir = 'alpa' THEN 1 ELSE 0 END) as alpa
FROM absen_latihan WHERE id_user = ?");
$stmt->execute([$user_id]);
$absensi_user = $stmt->fetch();

// 5. Keuangan - Saldo Kas
$stmt = $pdo->query("SELECT
COALESCE(SUM(CASE WHEN tipe = 'pemasukan' THEN jumlah ELSE 0 END), 0) as pemasukan,
COALESCE(SUM(CASE WHEN tipe = 'pengeluaran' THEN jumlah ELSE 0 END), 0) as pengeluaran
FROM keuangan");
$keuangan = $stmt->fetch();
$saldo_kas = $keuangan['pemasukan'] - $keuangan['pengeluaran'];

// 5b. Keuangan - Detail 10 Transaksi Terakhir
$stmt = $pdo->query("SELECT k.*, u.nama_lengkap 
FROM keuangan k
LEFT JOIN user u ON k.id_user = u.id_user
ORDER BY k.tanggal DESC, k.id_kas DESC LIMIT 10");
$keuangan_detail = $stmt->fetchAll();

// 6. Struktur Tim (Pembina dan Pengurus)
$stmt = $pdo->query("SELECT nama_lengkap, peran, no_hp FROM user
WHERE peran IN ('pembina', 'admin') AND status_aktif = 1
ORDER BY FIELD(peran, 'pembina', 'admin')");
$pengurus = $stmt->fetchAll();

// 7. Total Anggota Aktif
$stmt = $pdo->query("SELECT COUNT(*) as total FROM user WHERE status_aktif = 1 AND peran = 'anggota'");
$total_anggota = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// 8. Presensi Terakhir (5 terakhir)
$stmt = $pdo->prepare("SELECT a.id_absen, a.id_jadwal, a.id_user, a.status_hadir, a.jam_absen, a.created_at, a.updated_at, j.tanggal, j.lokasi, j.jam_mulai
FROM absen_latihan a
JOIN jadwal_latihan j ON a.id_jadwal = j.id_jadwal
WHERE a.id_user = ?
ORDER BY j.tanggal DESC LIMIT 5");
$stmt->execute([$user_id]);
$riwayat_absensi = $stmt->fetchAll();

// 8b. Semua Presensi (untuk modal)
$stmt = $pdo->prepare("SELECT a.id_absen, a.id_jadwal, a.id_user, a.status_hadir, a.jam_absen, a.created_at, a.updated_at, j.tanggal, j.lokasi, j.jam_mulai
FROM absen_latihan a
JOIN jadwal_latihan j ON a.id_jadwal = j.id_jadwal
WHERE a.id_user = ?
ORDER BY j.tanggal DESC");
$stmt->execute([$user_id]);
$semua_absensi = $stmt->fetchAll();

// 9. Semua Dresscode (untuk modal)
$stmt = $pdo->query("SELECT * FROM dresscode ORDER BY nama_pakaian");
$semua_dresscode = $stmt->fetchAll();

// Helper functions
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function formatTanggal($dateString) {
    $hari = array("Minggu","Senin","Selasa","Rabu","Kamis","Jumat","Sabtu");
    $bulan = array("","Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember");
    $date = new DateTime($dateString);
    $dayOfWeek = $hari[$date->format('w')];
    $day = $date->format('d');
    $month = $bulan[$date->format('n')];
    $year = $date->format('Y');
    return $dayOfWeek . ', ' . $day . ' ' . $month . ' ' . $year;
}

function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->d > 0) return $diff->d . ' hari yang lalu';
    if ($diff->h > 0) return $diff->h . ' jam yang lalu';
    if ($diff->i > 0) return $diff->i . ' menit yang lalu';
    return 'Baru saja';
}

// PHP version of getContrastColor for inline styles
function getContrastColorPHP($hexColor) {
    if (empty($hexColor)) return '#000000';
    $hexColor = ltrim($hexColor, '#');
    if (strlen($hexColor) !== 6) return '#000000';
    
    $r = hexdec(substr($hexColor, 0, 2));
    $g = hexdec(substr($hexColor, 2, 2));
    $b = hexdec(substr($hexColor, 4, 2));
    
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    
    return $luminance > 0.5 ? '#000000' : '#ffffff';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Anggota - Tim Hadroh Husna Maulana</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Variabel CSS untuk tema Islami */
        :root {
            --primary: #1a472a;
            --secondary: #2d6a4f;
            --accent: #d4af37;
            --light: #f8f5e9;
            --dark: #0d2818;
            --success: #40916c;
            --warning: #e9c46a;
            --danger: #e74c3c;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --border-radius: 8px;
            --islamic-pattern: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><path d="M50,5 L60,40 L95,40 L65,60 L75,95 L50,75 L25,95 L35,60 L5,40 L40,40 Z" fill="%232d6a4f" opacity="0.1"/></svg>');
        }

        /* Reset dan gaya dasar */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light);
            color: #333;
            line-height: 1.6;
            background-image: var(--islamic-pattern);
        }

        .arabic-font {
            font-family: 'Amiri', serif;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header & Navigasi */
        header {
            background-color: var(--primary);
            color: white;
            padding: 0.75rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow);
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            font-size: 1.8rem;
            color: var(--accent);
        }

        .logo-text h1 {
            font-size: 1.4rem;
            font-weight: 700;
        }

        .logo-text .arabic {
            font-family: 'Amiri', serif;
            font-size: 1.1rem;
            color: var(--accent);
            margin-top: -2px;
        }

        .logo-text p {
            font-size: 0.7rem;
            opacity: 0.8;
            margin: 0;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        nav ul {
            display: flex;
            list-style: none;
            align-items: center;
            gap: 0.5rem;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        nav ul li a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--accent);
        }

        nav ul li a.active {
            color: var(--accent);
        }

        .logout-btn {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logout-btn:hover {
            background-color: var(--danger);
            border-color: var(--danger);
            color: white !important;
        }

        @media (max-width: 768px) {
            .header-container {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                padding: 0.5rem 15px;
            }
            
            .logo-text h1 {
                font-size: 1.1rem;
            }
            
            .logo-text .arabic {
                font-size: 0.9rem;
            }
            
            .logo-text p {
                display: none;
            }
            
            .logo {
                flex: 1;
                min-width: 0;
            }
            
            .logo-icon {
                font-size: 1.5rem;
            }
            
            .header-right {
                gap: 10px;
            }
            
            .user-info {
                display: none;
            }
            
            nav ul {
                gap: 0.25rem;
            }
            
            nav ul li a {
                padding: 0.5rem 0.75rem;
                font-size: 0.85rem;
            }
            
            nav ul li a i {
                margin-right: 4px;
            }
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(26, 71, 42, 0.9), rgba(45, 106, 79, 0.9)), url('https://images.unsplash.com/photo-1560969184-10fe8719e047?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 3rem 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .hero h2 {
            font-size: 2.3rem;
            margin-bottom: 1rem;
            font-weight: 800;
        }

        .hero .arabic {
            font-family: 'Amiri', serif;
            font-size: 1.8rem;
            color: var(--accent);
            margin: 1rem 0;
        }

        .hero p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .welcome-message {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-top: 1rem;
            backdrop-filter: blur(5px);
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        /* Section Umum */
        section {
            padding: 3rem 0;
        }

        .section-title {
            text-align: left;
            margin-bottom: 1.5rem;
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-title h2 {
            font-size: 1.8rem;
            color: var(--primary);
            display: inline-block;
            padding-bottom: 8px;
            position: relative;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            width: 50px;
            height: 4px;
            background-color: var(--accent);
            bottom: 0;
            left: 0;
            border-radius: 2px;
        }

        .view-all {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .view-all:hover {
            text-decoration: underline;
        }

        /* Kartu Dashboard */
        .dashboard-card {
            background-color: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: transform 0.3s;
            border-top: 5px solid var(--secondary);
            margin-bottom: 1.5rem;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background-color: var(--primary);
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-content {
            padding: 1.5rem;
        }

        /* Jadwal Latihan */
        .jadwal-list {
            list-style: none;
        }

        .jadwal-item {
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .jadwal-item:last-child {
            border-bottom: none;
        }

        .jadwal-info h4 {
            color: var(--primary);
            margin-bottom: 0.3rem;
        }

        .jadwal-meta {
            display: flex;
            gap: 15px;
            font-size: 0.9rem;
            color: #666;
        }

        .jadwal-meta i {
            color: var(--secondary);
            margin-right: 5px;
        }

        .jadwal-status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-direncanakan {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .status-selesai {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        /* Acara Mendatang */
        .acara-list {
            list-style: none;
        }

        .acara-item {
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }

        .acara-item:last-child {
            border-bottom: none;
        }

        .acara-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .acara-title h4 {
            color: var(--primary);
        }

        .dresscode-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--primary);
            border: 1px solid var(--secondary);
        }

        .acara-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            font-size: 0.9rem;
            color: #666;
        }

        .acara-detail {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .acara-detail i {
            color: var(--secondary);
        }

        /* Statistik Kehadiran */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-box {
            background-color: var(--light);
            border-radius: var(--border-radius);
            padding: 1rem;
            text-align: center;
            border: 1px solid rgba(45, 106, 79, 0.1);
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0.5rem 0;
        }

        .stat-label {
            color: #666;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .progress-container {
            margin-top: 1rem;
        }

        .progress-item {
            margin-bottom: 1rem;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .progress-bar {
            height: 8px;
            background-color: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
        }

        .progress-hadir {
            background-color: var(--success);
        }

        .progress-izin {
            background-color: var(--warning);
        }

        .progress-alpa {
            background-color: var(--danger);
        }

        /* Keuangan */
        .keuangan-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .keuangan-card {
            background-color: var(--light);
            border-radius: var(--border-radius);
            padding: 1rem;
            text-align: center;
            border: 1px solid rgba(45, 106, 79, 0.1);
        }

        .keuangan-icon {
            font-size: 2rem;
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }

        .keuangan-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }

        .pemasukan {
            color: var(--success);
        }

        .pengeluaran {
            color: var(--danger);
        }

        .saldo {
            color: var(--primary);
        }

        /* Pengurus Tim */
        .pengurus-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .pengurus-card {
            background-color: var(--light);
            border-radius: var(--border-radius);
            padding: 1rem;
            text-align: center;
            border: 1px solid rgba(45, 106, 79, 0.1);
        }

        .pengurus-icon {
            width: 60px;
            height: 60px;
            background-color: var(--secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 1.5rem;
        }

        .pengurus-nama {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.3rem;
        }

        .pengurus-peran {
            color: var(--secondary);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .pengurus-hp {
            color: #666;
            font-size: 0.8rem;
        }

        /* Riwayat Absensi */
        .riwayat-list {
            list-style: none;
        }

        .riwayat-item {
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .riwayat-item:last-child {
            border-bottom: none;
        }

        .riwayat-info h4 {
            color: var(--primary);
            margin-bottom: 0.3rem;
        }

        .riwayat-meta {
            display: flex;
            gap: 15px;
            font-size: 0.9rem;
            color: #666;
        }

        .riwayat-meta i {
            color: var(--secondary);
            margin-right: 5px;
        }

        .status-hadir {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-hadir {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .badge-menunggu {
            background-color: #fff3e0;
            color: #f57c00;
        }

        .badge-izin {
            background-color: #fff3e0;
            color: #f57c00;
        }

        .badge-alpa {
            background-color: #ffebee;
            color: #d32f2f;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .action-btn {
            background-color: white;
            border: 1px solid var(--secondary);
            border-radius: var(--border-radius);
            padding: 1rem;
            text-align: center;
            text-decoration: none;
            color: var(--primary);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .action-btn:hover {
            background-color: var(--secondary);
            color: white;
            transform: translateY(-3px);
        }

        .action-btn i {
            font-size: 1.5rem;
            color: var(--secondary);
        }

        .action-btn:hover i {
            color: white;
        }

        /* Footer */
        footer {
            background-color: var(--dark);
            color: white;
            padding: 2rem 0 1rem;
            margin-top: 2rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 1.5rem;
        }

        .footer-logo {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--accent);
        }

        .footer-logo .arabic {
            font-family: 'Amiri', serif;
            font-size: 1.3rem;
            color: white;
        }

        .footer-about p {
            margin-bottom: 1rem;
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .footer-links h3, .footer-contact h3 {
            font-size: 1.1rem;
            margin-bottom: 1rem;
            color: var(--accent);
        }

        .footer-links ul {
            list-style: none;
        }

        .footer-links ul li {
            margin-bottom: 0.5rem;
        }

        .footer-links ul li a {
            color: white;
            text-decoration: none;
            transition: color 0.3s;
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .footer-links ul li a:hover {
            color: var(--accent);
            opacity: 1;
        }

        .social-icons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-icons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }

        .social-icons a:hover {
            background-color: var(--accent);
            color: var(--dark);
            transform: translateY(-3px);
        }

        .copyright {
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.8rem;
            color: #aaa;
        }

        /* Responsif */
        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .hero h2 {
                font-size: 1.8rem;
            }
            
            .hero .arabic {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .hero h2 {
                font-size: 1.6rem;
            }
            
            .hero .arabic {
                font-size: 1.3rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .section-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .section-title h2 {
                font-size: 1.5rem;
            }
        }

        /* Dekorasi Islami */
        .islamic-decoration {
            position: relative;
            overflow: hidden;
        }

        .islamic-decoration::before {
            content: "﷽";
            position: absolute;
            font-family: 'Amiri', serif;
            font-size: 8rem;
            color: rgba(45, 106, 79, 0.03);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 0;
            pointer-events: none;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .alert-info {
            background-color: #e3f2fd;
            border-left: 4px solid #1976d2;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: var(--border-radius);
        }
        
/* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease-out;
            padding: 1rem;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        /* Loading Spinner */
        .modal-loading {
            display: none;
            text-align: center;
            padding: 2rem;
        }
        
        .modal-loading.active {
            display: block;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid var(--light);
            border-top: 4px solid var(--accent);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: white;
            border-radius: var(--border-radius);
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            background-color: var(--primary);
            color: white;
            padding: 1.2rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
        }
        
        .modal-header h3 {
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-close:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        /* Jadwal Modal */
        .jadwal-modal-list {
            list-style: none;
        }
        
        .jadwal-modal-item {
            background-color: var(--light);
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--secondary);
        }
        
        .jadwal-modal-item:last-child {
            margin-bottom: 0;
        }
        
        .jadwal-modal-item h4 {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .jadwal-modal-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .jadwal-modal-meta i {
            color: var(--secondary);
            margin-right: 5px;
        }
        
        /* Kontak Modal */
        .kontak-modal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .kontak-modal-card {
            background-color: var(--light);
            border-radius: var(--border-radius);
            padding: 1rem;
            text-align: center;
            border: 1px solid rgba(45, 106, 79, 0.1);
            transition: all 0.3s;
        }
        
        .kontak-modal-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }
        
        .kontak-modal-icon {
            width: 50px;
            height: 50px;
            background-color: var(--secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.8rem;
            color: white;
            font-size: 1.3rem;
        }
        
        .kontak-modal-nama {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.3rem;
        }
        
        .kontak-modal-peran {
            color: var(--secondary);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .kontak-modal-hp {
            color: #666;
            font-size: 0.9rem;
        }
        
        .kontak-modal-hp a {
            color: var(--secondary);
            text-decoration: none;
        }
        
        .kontak-modal-hp a:hover {
            text-decoration: underline;
        }
        
        /* Keuangan Modal */
        .keuangan-modal-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 576px) {
            .keuangan-modal-summary {
                grid-template-columns: 1fr;
            }
        }
        
        .keuangan-modal-card {
            background-color: var(--light);
            border-radius: var(--border-radius);
            padding: 1rem;
            text-align: center;
            border: 1px solid rgba(45, 106, 79, 0.1);
        }
        
        .keuangan-modal-card .keuangan-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .keuangan-modal-card .keuangan-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.3rem;
        }
        
        .keuangan-modal-card .keuangan-value {
            font-size: 1.2rem;
            font-weight: 700;
        }
        
        .keuangan-modal-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.5rem;
            font-size: 0.85rem;
        }
        
        @media (max-width: 576px) {
            .keuangan-modal-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
        
        .keuangan-modal-table th,
        .keuangan-modal-table td {
            padding: 0.75rem 0.5rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .keuangan-modal-table th {
            background-color: var(--light);
            color: var(--primary);
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .keuangan-modal-table tr:hover {
            background-color: rgba(45, 106, 79, 0.05);
        }
        
        .keuangan-modal-table .tipe-pemasukan {
            color: var(--success);
            font-weight: 600;
        }
        
        .keuangan-modal-table .tipe-pengeluaran {
            color: var(--danger);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <script>
        // Minimal fallback definitions to ensure inline onclick handlers do not fail
        // These are lightweight and will be overridden by the full implementations
        window.openModal = window.openModal || function(modalId) {
            try {
                var modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
            } catch (e) {
                // silently ignore fallback errors
            }
        };

        window.closeModal = window.closeModal || function(modalId) {
            try {
                var modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.remove('active');
                    document.body.style.overflow = 'auto';
                }
            } catch (e) {
                // silently ignore fallback errors
            }
        };
    </script>
    <!-- Header & Navigasi -->
    <header>
        <div class="container header-container">
            <div class="logo">
                <div class="logo-icon"><i class="fas fa-mosque"></i></div>
                <div class="logo-text">
                    <h1>Husna Maulana</h1>
                    <div class="arabic">حسناء المؤمنة</div>
                </div>
            </div>
            
            <div class="header-right">
                <nav id="mainNav">
                    <ul>
                        <li><a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container hero-content">
            <h2>Assalamu'alaikum <?php echo htmlspecialchars(explode(' ', $current_user['nama_lengkap'])[0]); ?>!</h2>
            <div class="arabic">أَعْلِنُوا هَذَا النِّكَاحَ، وَاجْعَلُوهُ فِي المَسَاجِدِ، وَاضْرِبُوا عَلَيْهِ بالدُّفوفِ</div>
            <p>Selamat datang di Dashboard Anggota Tim Hadroh Husna Maulana. Pantau jadwal, kehadiran, dan informasi terbaru di sini.</p>
            
            <div class="welcome-message">
                <p><i class="fas fa-info-circle"></i> <strong>Info:</strong> Saat ini terdapat <strong><?php echo count($jadwal_mendatang); ?> jadwal latihan</strong> mendatang dan <strong><?php echo count($acara_mendatang); ?> acara</strong> yang akan datang.</p>
            </div>
        </div>
    </section>

    <!-- Dashboard Content -->
    <section>
        <div class="container">
            <div class="dashboard-grid">
                <!-- Kolom Kiri -->
                <div>
                    <!-- Jadwal Latihan Hari Ini -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fas fa-calendar-day"></i> Jadwal Latihan Hari Ini</h3>
                            <button type="button" class="view-all" onclick="openModal('jadwalModal')">Lihat Semua</button>
                        </div>
                        <div class="card-content">
                            <?php if (count($jadwal_hari_ini) > 0): ?>
                                <ul class="jadwal-list">
                                    <?php foreach ($jadwal_hari_ini as $jadwal): ?>
<li class="jadwal-item">
                                        <div class="jadwal-info">
                                            <h4>Latihan #<?php echo $jadwal['id_jadwal']; ?></h4>
                                            <div class="jadwal-meta">
                                                <span><i class="far fa-clock"></i> <?php echo date('H:i', strtotime($jadwal['jam_mulai'])); ?></span>
                                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($jadwal['lokasi']); ?></span>
                                            </div>
                                        </div>
                                        <span class="jadwal-status status-direncanakan">Direncanakan</span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="far fa-calendar-check"></i>
                                    <p>Tidak ada jadwal latihan hari ini</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!--Acara Menunggu Konfirmasi -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fas fa-calendar-alt"></i> Acara Menunggu</h3>
                            <button type="button" class="view-all" onclick="openModal('acaraModal')">Lihat Semua</button>
                        </div>
                        <div class="card-content">
                            <?php if (count($acara_mendatang) > 0): ?>
                                <ul class="acara-list">
                                    <?php foreach ($acara_mendatang as $acara): ?>
                                    <li class="acara-item">
                                        <div class="acara-header">
                                            <div class="acara-title">
                                                <h4><?php echo htmlspecialchars($acara['nama_acara']); ?></h4>
                                            </div>
                                            <span class="status-hadir badge-menunggu">
                                                <i class="fas fa-clock me-1"></i>Menunggu
                                            </span>
                                        </div>
                                        <div class="acara-details">
                                            <div class="acara-detail">
                                                <i class="far fa-calendar"></i>
                                                <span><?php echo formatTanggal($acara['tanggal_acara']); ?></span>
                                            </div>
                                            <div class="acara-detail">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span><?php echo htmlspecialchars($acara['lokasi']); ?></span>
                                            </div>
                                        </div>
                                        <?php if ($acara['dresscode_name'] || $acara['dresscode_warna']): ?>
                                        <div class="mt-2">
                                            <button class="dresscode-badge-btn" 
                                                    style="background: transparent; border: 2px solid var(--secondary); color: var(--primary); padding: 0.4rem 0.8rem; border-radius: 20px; cursor: pointer; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 8px;"
                                                    onclick="showDresscodeDetail('<?php echo htmlspecialchars($acara['dresscode_name'] ?? 'Dresscode'); ?>', '<?php echo htmlspecialchars($acara['dresscode_warna'] ?? ''); ?>', '<?php echo htmlspecialchars($acara['dresscode_deskripsi'] ?? ''); ?>')">
                                                <i class="fas fa-tshirt"></i>
                                                <span><?php $dresscode_name = isset($acara['dresscode_name']) ? $acara['dresscode_name'] : 'Dresscode'; ?><?php echo htmlspecialchars($dresscode_name); ?></span>
                                                <?php if ($acara['dresscode_warna']): ?>
                                                <span style="display: inline-block; width: 14px; height: 14px; background-color: <?php echo htmlspecialchars($acara['dresscode_warna']); ?>; border-radius: 50%; border: 2px solid var(--secondary); vertical-align: middle;"></span>
                                                <?php endif; ?>
                                            </button>
                                        </div>
                                        <?php endif; ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="far fa-calendar-times"></i>
                                    <p>Tidak ada acara yang menunggu konfirmasi</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan -->
                <div>
                    <!-- Statistik Kehadiran -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-bar"></i> Statistik Kehadiran</h3>
                            <button type="button" class="view-all" onclick="openModal('absensiModal')">Detail</button>
                        </div>
                        <div class="card-content">
                            <div class="stats-grid">
                                <div class="stat-box">
                                    <div class="stat-label">Total</div>
                                    <div class="stat-value"><?php echo $absensi_user['total_absensi'] ?? 0; ?></div>
                                    <div class="stat-label">Kehadiran</div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-label">Hadir</div>
                                    <div class="stat-value"><?php echo $absensi_user['hadir'] ?? 0; ?></div>
                                    <div class="stat-label">Sesi</div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-label">Izin</div>
                                    <div class="stat-value"><?php echo $absensi_user['izin'] ?? 0; ?></div>
                                    <div class="stat-label">Sesi</div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-label">Alpa</div>
                                    <div class="stat-value"><?php echo $absensi_user['alpa'] ?? 0; ?></div>
                                    <div class="stat-label">Sesi</div>
                                </div>
                            </div>
                            
                            <?php if ($absensi_user['total_absensi'] > 0): ?>
                            <div class="progress-container">
                                <div class="progress-item">
                                    <div class="progress-label">
                                        <span>Hadir</span>
                                        <span><?php echo round(($absensi_user['hadir'] / $absensi_user['total_absensi']) * 100, 1); ?>%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill progress-hadir" style="width: <?php echo ($absensi_user['hadir'] / $absensi_user['total_absensi']) * 100; ?>%"></div>
                                    </div>
                                </div>
                                <div class="progress-item">
                                    <div class="progress-label">
                                        <span>Izin</span>
                                        <span><?php echo round(($absensi_user['izin'] / $absensi_user['total_absensi']) * 100, 1); ?>%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill progress-izin" style="width: <?php echo ($absensi_user['izin'] / $absensi_user['total_absensi']) * 100; ?>%"></div>
                                    </div>
                                </div>
                                <div class="progress-item">
                                    <div class="progress-label">
                                        <span>Alpa</span>
                                        <span><?php echo round(($absensi_user['alpa'] / $absensi_user['total_absensi']) * 100, 1); ?>%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill progress-alpa" style="width: <?php echo ($absensi_user['alpa'] / $absensi_user['total_absensi']) * 100; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-chart-line"></i>
                                    <p>Belum ada data kehadiran</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

<!-- Keuangan Tim -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fas fa-wallet"></i> Keuangan Tim</h3>
                            <button type="button" class="view-all" onclick="openModal('keuanganModal')">Detail</button>
                        </div>
                        <div class="card-content">
                            <div class="keuangan-summary">
                                <div class="keuangan-card">
                                    <div class="keuangan-icon"><i class="fas fa-money-bill-wave"></i></div>
                                    <div class="keuangan-label">Pemasukan</div>
                                    <div class="keuangan-value pemasukan"><?php echo formatRupiah($keuangan['pemasukan']); ?></div>
                                </div>
                                <div class="keuangan-card">
                                    <div class="keuangan-icon"><i class="fas fa-receipt"></i></div>
                                    <div class="keuangan-label">Pengeluaran</div>
                                    <div class="keuangan-value pengeluaran"><?php echo formatRupiah($keuangan['pengeluaran']); ?></div>
                                </div>
                            </div>
                            
                            <div class="keuangan-card">
                                <div class="keuangan-icon"><i class="fas fa-piggy-bank"></i></div>
                                <div class="keuangan-label">Saldo Kas</div>
                                <div class="keuangan-value saldo"><?php echo formatRupiah($saldo_kas); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Riwayat Absensi Terakhir -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Riwayat Absensi Terakhir</h3>
                    <button type="button" class="view-all" onclick="openModal('absensiModal')">Lihat Semua</button>
                </div>
                <div class="card-content">
                    <?php if (count($riwayat_absensi) > 0): ?>
                        <ul class="riwayat-list">
                            <?php foreach ($riwayat_absensi as $absensi): ?>
                            <li class="riwayat-item">
                                <div class="riwayat-info">
                                    <h4>Latihan - <?php echo formatTanggal($absensi['tanggal']); ?></h4>
                                    <div class="riwayat-meta">
                                        <span><i class="far fa-clock"></i> <?php echo date('H:i', strtotime($absensi['jam_mulai'])); ?></span>
                                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($absensi['lokasi']); ?></span>
                                    </div>
                                </div>
                                <span class="status-hadir badge-<?php echo $absensi['status_hadir']; ?>">
                                    <?php echo ucfirst($absensi['status_hadir']); ?>
                                </span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <p>Belum ada riwayat absensi</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pengurus Tim -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> Pengurus Tim</h3>
                    <button type="button" class="view-all" onclick="openModal('kontakModal')">Kontak Lain</button>
                </div>
                <div class="card-content">
                    <div class="pengurus-grid">
                        <?php foreach ($pengurus as $pengurus_item): ?>
                        <div class="pengurus-card">
                            <div class="pengurus-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="pengurus-nama"><?php echo htmlspecialchars($pengurus_item['nama_lengkap']); ?></div>
                            <div class="pengurus-peran"><?php echo ucfirst($pengurus_item['peran']); ?></div>
                            <div class="pengurus-hp"><?php echo htmlspecialchars($pengurus_item['no_hp']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <button type="button" class="action-btn" onclick="openModal('jadwalModal')">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Jadwal Latihan</span>
                </button>
                <button type="button" class="action-btn" onclick="openModal('acaraModal')">
                    <i class="fas fa-calendar-check"></i>
                    <span>Jadwal Acara</span>
                </button>
                <button type="button" class="action-btn" onclick="openModal('absensiModal')">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Absensi</span>
                </button>
                <button type="button" class="action-btn" onclick="openModal('keuanganModal')">
                    <i class="fas fa-wallet"></i>
                    <span>Keuangan</span>
                </button>
                <button type="button" class="action-btn" onclick="openModal('kontakModal')">
                    <i class="fas fa-address-book"></i>
                    <span>Kontak Tim</span>
                </button>
                <button type="button" class="action-btn" onclick="openModal('dresscodeAllModal')">
                    <i class="fas fa-tshirt"></i>
                    <span>Dresscode</span>
                </button>
            </div>
        </div>
    </section>

    <!-- Modal Jadwal Latihan -->
    <div class="modal-overlay" id="jadwalModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-calendar-alt"></i> Jadwal Latihan</h3>
                <button type="button" class="modal-close" onclick="closeModal('jadwalModal')">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Loading Indicator -->
                <div class="modal-loading active" id="jadwalLoading">
                    <div class="spinner"></div>
                    <p>Memuat data...</p>
                </div>
                
                <!-- Content -->
                <div id="jadwalPaginationContent" style="display: none;">
                    <?php if ($total_jadwal > 0): ?>
                        <ul class="jadwal-modal-list" id="jadwalList">
                            <?php 
                            $jadwal_current = array_slice($semua_jadwal, 0, $jadwal_per_page);
                            foreach ($jadwal_current as $jadwal): 
                            ?>
<li class="jadwal-modal-item">
                                <h4>Latihan #<?php echo $jadwal['id_jadwal']; ?></h4>
                                <div class="jadwal-modal-meta">
                                    <span><i class="far fa-calendar"></i> <?php echo formatTanggal($jadwal['tanggal']); ?></span>
                                    <span><i class="far fa-clock"></i> <?php echo date('H:i', strtotime($jadwal['jam_mulai'])); ?></span>
                                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($jadwal['lokasi']); ?></span>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages_jadwal > 1): ?>
                        <div class="pagination" style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 1rem;">
                            <button class="pagination-btn" onclick="changeJadwalPage(1)" style="padding: 0.5rem 1rem; border: 1px solid var(--secondary); background: white; color: var(--secondary); border-radius: 4px; cursor: pointer;">«</button>
                            <?php for($i = 1; $i <= $total_pages_jadwal; $i++): ?>
                            <button class="pagination-btn page-jadwal-btn <?php echo $i === 1 ? 'active' : ''; ?>" onclick="changeJadwalPage(<?php echo $i; ?>)" style="padding: 0.5rem 1rem; border: 1px solid var(--secondary); <?php echo $i === 1 ? 'background: var(--secondary); color: white;' : 'background: white; color: var(--secondary);'; ?> border-radius: 4px; cursor: pointer;"><?php echo $i; ?></button>
                            <?php endfor; ?>
                            <button class="pagination-btn" onclick="changeJadwalPage(<?php echo $total_pages_jadwal; ?>)" style="padding: 0.5rem 1rem; border: 1px solid var(--secondary); background: white; color: var(--secondary); border-radius: 4px; cursor: pointer;">»</button>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="far fa-calendar-times"></i>
                            <p>Tidak ada jadwal latihan mendatang</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Jadwal Acara -->
    <div class="modal-overlay" id="acaraModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-calendar-check"></i> Semua Jadwal Acara</h3>
                <button type="button" class="modal-close" onclick="closeModal('acaraModal')">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Loading Indicator -->
                <div class="modal-loading active" id="acaraLoading">
                    <div class="spinner"></div>
                    <p>Memuat data...</p>
                </div>
                
                <!-- Content -->
                <div id="acaraPaginationContent" style="display: none;">
                    <?php if ($total_acara > 0): ?>
                        <ul class="jadwal-modal-list" id="acaraList">
                            <?php 
                            $acara_current = array_slice($semua_acara, 0, $acara_per_page);
                            foreach ($acara_current as $acara): 
                            ?>
                            <li class="jadwal-modal-item">
                                <h4><?php echo htmlspecialchars($acara['nama_acara']); ?></h4>
                                <div class="jadwal-modal-meta">
                                    <span><i class="far fa-calendar"></i> <?php echo formatTanggal($acara['tanggal_acara']); ?></span>
                                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($acara['lokasi']); ?></span>
                                    <?php if ($acara['dresscode_name']): ?>
                                    <span><i class="fas fa-tshirt"></i> <?php echo htmlspecialchars($acara['dresscode_name']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="margin-top: 0.5rem;">
                                    <span class="status-hadir badge-<?php echo $acara['status']; ?>">
                                        <?php echo ucfirst($acara['status']); ?>
                                    </span>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages_acara > 1): ?>
                        <div class="pagination" style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 1rem;">
                            <button class="pagination-btn" onclick="changeAcaraPage(1)" style="padding: 0.5rem 1rem; border: 1px solid var(--secondary); background: white; color: var(--secondary); border-radius: 4px; cursor: pointer;">«</button>
                            <?php for($i = 1; $i <= $total_pages_acara; $i++): ?>
                            <button class="pagination-btn page-acara-btn <?php echo $i === 1 ? 'active' : ''; ?>" onclick="changeAcaraPage(<?php echo $i; ?>)" style="padding: 0.5rem 1rem; border: 1px solid var(--secondary); <?php echo $i === 1 ? 'background: var(--secondary); color: white;' : 'background: white; color: var(--secondary);'; ?> border-radius: 4px; cursor: pointer;"><?php echo $i; ?></button>
                            <?php endfor; ?>
                            <button class="pagination-btn" onclick="changeAcaraPage(<?php echo $total_pages_acara; ?>)" style="padding: 0.5rem 1rem; border: 1px solid var(--secondary); background: white; color: var(--secondary); border-radius: 4px; cursor: pointer;">»</button>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="far fa-calendar-times"></i>
                            <p>Tidak ada acara mendatang yang perlu dikonfirmasi</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Kontak -->
    <div class="modal-overlay" id="kontakModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-address-book"></i> Kontak Tim</h3>
                <button type="button" class="modal-close" onclick="closeModal('kontakModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="kontak-modal-grid">
                    <?php foreach ($pengurus as $pengurus_item): ?>
                    <div class="kontak-modal-card">
                        <div class="kontak-modal-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="kontak-modal-nama"><?php echo htmlspecialchars($pengurus_item['nama_lengkap']); ?></div>
                        <div class="kontak-modal-peran"><?php echo ucfirst($pengurus_item['peran']); ?></div>
                        <div class="kontak-modal-hp">
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $pengurus_item['no_hp']); ?>" target="_blank">
                                <i class="fab fa-whatsapp"></i> <?php echo htmlspecialchars($pengurus_item['no_hp']); ?>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php 
                    // Query untuk semua anggota aktif
                    $stmt = $pdo->query("SELECT nama_lengkap, peran, no_hp FROM user WHERE status_aktif = 1 AND peran = 'anggota' ORDER BY nama_lengkap");
                    $anggota = $stmt->fetchAll();
                    foreach ($anggota as $anggota_item): ?>
                    <div class="kontak-modal-card">
                        <div class="kontak-modal-icon" style="background-color: var(--primary);">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="kontak-modal-nama"><?php echo htmlspecialchars($anggota_item['nama_lengkap']); ?></div>
                        <div class="kontak-modal-peran"><?php echo ucfirst($anggota_item['peran']); ?></div>
                        <div class="kontak-modal-hp">
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $anggota_item['no_hp']); ?>" target="_blank">
                                <i class="fab fa-whatsapp"></i> <?php echo htmlspecialchars($anggota_item['no_hp']); ?>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Absensi -->
    <div class="modal-overlay" id="absensiModal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3><i class="fas fa-history"></i> Riwayat Absensi Lengkap</h3>
                <button type="button" class="modal-close" onclick="closeModal('absensiModal')">&times;</button>
            </div>
            <div class="modal-body">
                <?php if (count($semua_absensi) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="keuangan-modal-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Waktu</th>
                                    <th>Lokasi</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($semua_absensi as $absen): ?>
                                <tr>
                                    <td><?php echo formatTanggal($absen['tanggal']); ?></td>
                                    <td><?php echo date('H:i', strtotime($absen['jam_mulai'])); ?></td>
                                    <td><?php echo htmlspecialchars($absen['lokasi'] ?? '-'); ?></td>
                                    <td>
                                        <span class="status-hadir badge-<?php echo $absen['status_hadir']; ?>">
                                            <?php echo ucfirst($absen['status_hadir']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Statistik -->
                    <div class="stats-grid" style="margin-top: 1.5rem;">
                        <div class="stat-box">
                            <div class="stat-label">Total</div>
                            <div class="stat-value"><?php echo count($semua_absensi); ?></div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label">Hadir</div>
                            <div class="stat-value"><?php echo count(array_filter($semua_absensi, function($a) { return $a['status_hadir'] == 'hadir'; })); ?></div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label">Izin</div>
                            <div class="stat-value"><?php echo count(array_filter($semua_absensi, function($a) { return $a['status_hadir'] == 'izin'; })); ?></div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label">Alpa</div>
                            <div class="stat-value"><?php echo count(array_filter($semua_absensi, function($a) { return $a['status_hadir'] == 'alpa'; })); ?></div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <p>Belum ada riwayat absensi</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Keuangan -->
    <div class="modal-overlay" id="keuanganModal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3><i class="fas fa-wallet"></i> Detail Keuangan</h3>
                <button type="button" class="modal-close" onclick="closeModal('keuanganModal')">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Ringkasan -->
                <div class="keuangan-modal-summary">
                    <div class="keuangan-modal-card">
                        <div class="keuangan-icon" style="color: var(--success);">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div class="keuangan-label">Pemasukan</div>
                        <div class="keuangan-value tipe-pemasukan"><?php echo formatRupiah($keuangan['pemasukan']); ?></div>
                    </div>
                    <div class="keuangan-modal-card">
                        <div class="keuangan-icon" style="color: var(--danger);">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <div class="keuangan-label">Pengeluaran</div>
                        <div class="keuangan-value tipe-pengeluaran"><?php echo formatRupiah($keuangan['pengeluaran']); ?></div>
                    </div>
                    <div class="keuangan-modal-card">
                        <div class="keuangan-icon" style="color: var(--primary);">
                            <i class="fas fa-piggy-bank"></i>
                        </div>
                        <div class="keuangan-label">Saldo Kas</div>
                        <div class="keuangan-value" style="color: var(--primary);"><?php echo formatRupiah($saldo_kas); ?></div>
                    </div>
                </div>
                
                <!-- Tabel Transaksi -->
                <?php if (count($keuangan_detail) > 0): ?>
                    <table class="keuangan-modal-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Keterangan</th>
                                <th>Jumlah</th>
                                <th>Input</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($keuangan_detail as $transaksi): ?>
                            <tr>
                                <td><?php echo formatTanggal($transaksi['tanggal']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($transaksi['keterangan']); ?>
                                    <?php if ($transaksi['nama_lengkap']): ?>
                                    <br><small style="color: #999;">oleh: <?php echo htmlspecialchars($transaksi['nama_lengkap']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="<?php echo $transaksi['tipe'] === 'pemasukan' ? 'tipe-pemasukan' : 'tipe-pengeluaran'; ?>">
                                    <?php echo $transaksi['tipe'] === 'pemasukan' ? '+' : '-'; ?><?php echo formatRupiah($transaksi['jumlah']); ?>
                                </td>
                                <td>
                                    <span class="status-hadir badge-hadir">
                                        <?php echo $transaksi['nama_lengkap'] ? htmlspecialchars($transaksi['nama_lengkap']) : 'System'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <p>Belum ada data keuangan</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Dresscode Detail -->
    <div class="modal-overlay" id="dresscodeModal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3><i class="fas fa-tshirt me-2"></i>Detail Dresscode</h3>
                <button type="button" class="modal-close" onclick="closeModal('dresscodeModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="dresscodeDetailContent">
                    <div class="text-center mb-3">
                        <div style="width: 60px; height: 60px; background-color: var(--light); border-radius: 50%; margin: 0 auto; border: 3px solid var(--secondary); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-tshirt" style="font-size: 1.5rem; color: var(--secondary);"></i>
                        </div>
                    </div>
                    <h4 id="dresscodeName" class="text-center mb-2" style="color: var(--primary); font-size: 1.3rem;"></h4>
                    <div id="dresscodeColorInfo" class="text-center mb-3">
                        <span class="badge" id="dresscodeColorBadge" style="font-size: 0.9rem; padding: 0.5rem 1rem;"></span>
                    </div>
                    <div id="dresscodeDescription" class="text-center text-muted mb-3" style="font-size: 0.9rem; font-style: italic;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal All Dresscodes -->
    <div class="modal-overlay" id="dresscodeAllModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3><i class="fas fa-tshirt me-2"></i>Daftar Dresscode</h3>
                <button type="button" class="modal-close" onclick="closeModal('dresscodeAllModal')">&times;</button>
            </div>
            <div class="modal-body">
                <?php if (count($semua_dresscode) > 0): ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                        <?php foreach ($semua_dresscode as $dresscode): ?>
                        <div class="dresscode-card" 
                             style="background-color: var(--light); border-radius: var(--border-radius); padding: 1rem; text-align: center; border: 1px solid rgba(45, 106, 79, 0.1); cursor: pointer; transition: all 0.3s;"
                             onclick="showDresscodeDetail('<?php echo htmlspecialchars($dresscode['nama_pakaian']); ?>', '<?php echo htmlspecialchars($dresscode['warna'] ?? ''); ?>', '<?php echo htmlspecialchars($dresscode['deskripsi'] ?? ''); ?>')"
                             onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='var(--shadow)';"
                             onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='none';">
                            <?php if ($dresscode['warna']): ?>
                            <div style="width: 40px; height: 40px; border-radius: 50%; margin: 0 auto 0.8rem; border: 2px solid var(--secondary); display: flex; align-items: center; justify-content: center; background-color: <?php echo htmlspecialchars($dresscode['warna']); ?>;">
                                <span style="font-size: 0.7rem; color: <?php echo getContrastColorPHP($dresscode['warna']); ?>; font-weight: bold;"><?php echo htmlspecialchars($dresscode['warna']); ?></span>
                            </div>
                            <?php else: ?>
                            <div style="width: 40px; height: 40px; border-radius: 50%; margin: 0 auto 0.8rem; border: 2px solid var(--secondary); display: flex; align-items: center; justify-content: center; background-color: #f0f0f0;">
                                <i class="fas fa-tshirt" style="color: #999;"></i>
                            </div>
                            <?php endif; ?>
                            <div style="font-weight: 600; color: var(--primary); margin-bottom: 0.3rem; font-size: 0.9rem;"><?php echo htmlspecialchars($dresscode['nama_pakaian']); ?></div>
                            <div style="font-size: 0.75rem; color: #666;"><?php echo htmlspecialchars($dresscode['warna'] ?? '-'); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3" style="color: #666; font-size: 0.85rem;">
                        <i class="fas fa-info-circle me-1"></i>Klik pada dresscode untuk melihat detail
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-tshirt"></i>
                        <p>Belum ada dresscode tersedia</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-about">
                    <div class="footer-logo">Husna Maulana <span class="arabic">حسناء المؤمنة</span></div>
                    <p>© Tim Hadrah Husna Maulana Ketengan. Merawat tradisi, menguatkan ukhuwah.</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-whatsapp"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> Tim Hadroh Husna Maulana. Semua hak dilindungi. <span class="arabic">والحمد لله رب العالمين</span></p>
            </div>
        </div>
    </footer>

    <script>
        // Dashboard JavaScript initialized
        
        // Update active nav link saat scroll
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('section');
            const navLinks = document.querySelectorAll('nav a');
            
            let current = '';
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                
                if (pageYOffset >= sectionTop - 100) {
                    current = section.getAttribute('id');
                }
            });
            
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active');
                }
            });
        });
        
        // Animasi progress bar saat masuk viewport
        const observerOptions = {
            threshold: 0.5
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const progressBars = entry.target.querySelectorAll('.progress-fill');
                    progressBars.forEach(bar => {
                        const width = bar.style.width;
                        bar.style.width = '0';
                        setTimeout(() => {
                            bar.style.width = width;
                        }, 300);
                    });
                }
            });
        }, observerOptions);
        
        const dashboardCards = document.querySelectorAll('.dashboard-card');
        dashboardCards.forEach(card => {
            observer.observe(card);
        });
        
        // Modal Functions
        function openModal(modalId) {
            try {
                const modal = document.getElementById(modalId);
                if (!modal) return;

                modal.classList.add('active');
                document.body.style.overflow = 'hidden';

                // Robustly find loading and content areas inside this modal
                const loadingId = modalId.replace('Modal', 'Loading');
                let loading = document.getElementById(loadingId);
                // fallback: first .modal-loading inside modal
                if (!loading) loading = modal.querySelector('.modal-loading');

                let content = document.getElementById(modalId.replace('Modal', 'PaginationContent'));
                // fallback: first child of .modal-body that is not .modal-loading
                if (!content) {
                    const body = modal.querySelector('.modal-body');
                    if (body) {
                        content = Array.from(body.children).find(ch => !ch.classList.contains('modal-loading')) || null;
                    }
                }

                if (loading) loading.classList.add('active');
                if (content) content.style.display = 'none';

                // Tampilkan konten setelah animasi / delay
                setTimeout(() => {
                    if (loading) loading.classList.remove('active');
                    if (content) content.style.display = 'block';
                }, 300);
            } catch (err) {
                // silently ignore errors in openModal
            }
        }
        
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                // If there is still any other modal active, keep body locked
                const anyActive = document.querySelectorAll('.modal-overlay.active').length > 0;
                document.body.style.overflow = anyActive ? 'hidden' : 'auto';
                // reset z-index if it was elevated
                modal.style.zIndex = '';
            }
        }
        
        // Pagination Jadwal Latihan
        function changeJadwalPage(page) {
            const itemsPerPage = 3;
            const list = document.getElementById('jadwalList');
            const items = <?php echo json_encode($semua_jadwal); ?>;
            
            // Update buttons
            document.querySelectorAll('.page-jadwal-btn').forEach((btn, index) => {
                btn.classList.toggle('active', index + 1 === page);
                btn.style.background = index + 1 === page ? 'var(--secondary)' : 'white';
                btn.style.color = index + 1 === page ? 'white' : 'var(--secondary)';
            });
            
            // Get items for this page
            const start = (page - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            const pageItems = items.slice(start, end);
            
            // Render items - gunakan 'Latihan' + ID karena database tidak punya nama_sesi
            list.innerHTML = pageItems.map(jadwal => `
                <li class="jadwal-modal-item">
                    <h4>Latihan #${jadwal.id_jadwal || '-'}</h4>
                    <div class="jadwal-modal-meta">
                        <span><i class="far fa-calendar"></i> ${formatTanggalPHP(jadwal.tanggal)}</span>
                        <span><i class="far fa-clock"></i> ${jadwal.jam_mulai ? jadwal.jam_mulai.substring(0, 5) : '-'}</span>
                        <span><i class="fas fa-map-marker-alt"></i> ${jadwal.lokasi || '-'}</span>
                    </div>
                </li>
            `).join('');
        }
        
        // Pagination Jadwal Acara
        function changeAcaraPage(page) {
            const itemsPerPage = 3;
            const list = document.getElementById('acaraList');
            const items = <?php echo json_encode($semua_acara); ?>;
            
            // Update buttons
            document.querySelectorAll('.page-acara-btn').forEach((btn, index) => {
                btn.classList.toggle('active', index + 1 === page);
                btn.style.background = index + 1 === page ? 'var(--secondary)' : 'white';
                btn.style.color = index + 1 === page ? 'white' : 'var(--secondary)';
            });
            
            // Get items for this page
            const start = (page - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            const pageItems = items.slice(start, end);
            
            // Render items
            list.innerHTML = pageItems.map(acara => `
                <li class="jadwal-modal-item">
                    <h4>${acara.nama_acara}</h4>
                    <div class="jadwal-modal-meta">
                        <span><i class="far fa-calendar"></i> ${formatTanggalPHP(acara.tanggal_acara)}</span>
                        <span><i class="fas fa-map-marker-alt"></i> ${acara.lokasi || '-'}</span>
                        ${acara.dresscode_name ? `<span><i class="fas fa-tshirt"></i> ${acara.dresscode_name}</span>` : ''}
                    </div>
                    <div style="margin-top: 0.5rem;">
                        <span class="status-hadir badge-${acara.status}">${acara.status.charAt(0).toUpperCase() + acara.status.slice(1)}</span>
                    </div>
                </li>
            `).join('');
        }
        
        // Helper function untuk format tanggal
        function formatTanggalPHP(dateStr) {
            const hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const date = new Date(dateStr);
            return `${hari[date.getDay()]}, ${date.getDate()} ${bulan[date.getMonth() + 1]} ${date.getFullYear()}`;
        }
        
        // Close modal when clicking outside
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                    document.body.style.overflow = 'auto';
                }
            });
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                    modal.classList.remove('active');
                    document.body.style.overflow = 'auto';
                });
            }
        });
        
        // Dresscode Detail Modal Function
        function showDresscodeDetail(name, color, description) {
            const modal = document.getElementById('dresscodeModal');
            const nameEl = document.getElementById('dresscodeName');
            const colorBadge = document.getElementById('dresscodeColorBadge');
            const descEl = document.getElementById('dresscodeDescription');
            
            // Set dresscode name
            nameEl.textContent = name;
            
            // Set color badge
            if (color) {
                colorBadge.textContent = color;
                colorBadge.style.backgroundColor = color;
                colorBadge.style.color = getContrastColor(color);
                colorBadge.style.display = 'inline-block';
            } else {
                colorBadge.style.display = 'none';
            }
            
            // Set description
            if (description && description.trim() !== '') {
                descEl.textContent = '"' + description + '"';
                descEl.style.display = 'block';
            } else {
                descEl.style.display = 'none';
            }
            
            // Show modal and ensure it overlays any open list modal
            modal.classList.add('active');
            modal.style.zIndex = 1102; // higher than default overlay
            document.body.style.overflow = 'hidden';
        }
        
        // Helper function to get contrasting text color
        function getContrastColor(hexColor) {
            // Remove # if present
            hexColor = hexColor.replace('#', '');
            
            // Convert to RGB
            const r = parseInt(hexColor.substr(0, 2), 16);
            const g = parseInt(hexColor.substr(2, 2), 16);
            const b = parseInt(hexColor.substr(4, 2), 16);
            
            // Calculate luminance
            const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
            
            // Return black for light colors, white for dark colors
            return luminance > 0.5 ? '#000000' : '#ffffff';
        }
    </script>
</body>
</html>