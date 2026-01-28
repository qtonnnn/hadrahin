<?php
/**
 * Dashboard Pembina - Hadrah
 * Halaman dashboard untuk peran pembina
 */

// Start session dan include database
session_start();
require_once '../config/database.php';

// Include auth check untuk keamanan session
require_once '../includes/auth_check.php';

// Validasi role - hanya pembina dan admin yang boleh akses
if ($_SESSION['peran'] !== 'pembina' && $_SESSION['peran'] !== 'admin') {
    header('Location: anggota.php');
    exit;
}

// Ambil data user dari database
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM user WHERE id_user = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pembina - Hadrah</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🥁</text></svg>">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <h1>Hadrah - Dashboard Pembina</h1>
        <div class="user-info">
            <span>Halo, <?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
            <span class="role-badge">PEMBINA</span>
            <a href="../auth/logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-card">
            <h2>Selamat Datang di Dashboard Pembina</h2>
            <p>Monitor kehadiran dan kelola latihan grup hadrah dari dashboard ini.</p>
            <span class="role-badge">Role: <?= htmlspecialchars($_SESSION['peran']) ?></span>
        </div>

        <div class="quick-actions">
            <div class="action-card">
                <h3>Jadwal Latihan</h3>
                <p>Lihat dan buat jadwal latihan baru</p>
                <a href="../modules/jadwallatihan/index.php">Lihat Jadwal</a>
            </div>

            <div class="action-card">
                <h3>Absensi</h3>
                <p>Catat kehadiran anggota latihan</p>
                <a href="../modules/absen/absen.php">Input Absensi</a>
            </div>

            <div class="action-card">
                <h3>Booking Acara</h3>
                <p>Lihat pesanan manggung yang masuk</p>
                <a href="../modules/acara/index.php">Lihat Booking</a>
            </div>

            <div class="action-card">
                <h3>Laporan</h3>
                <p>Lihat laporan kehadiran anggota</p>
                <a href="../modules/absen/laporan.php">Lihat Laporan</a>
            </div>
        </div>
    </div>
</body>
</html>

