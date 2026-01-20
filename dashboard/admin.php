<?php
/**
 * Dashboard Admin - Hadrah
 * Halaman dashboard untuk peran admin
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Hadrah</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <h1>Hadrah - Admin Dashboard</h1>
        <div class="user-info">
            <span>Halo, <?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
            <span class="role-badge">ADMIN</span>
            <a href="../auth/logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-card">
            <h2>Selamat Datang di Dashboard Admin</h2>
            <p>Kelola seluruh sistem informasi grup hadrah dari dashboard ini.</p>
            <span class="role-badge">Role: <?= htmlspecialchars($_SESSION['peran']) ?></span>
        </div>

        <div class="quick-actions">
            <div class="action-card">
                <h3>Manajemen User</h3>
                <p>Kelola data admin, pembina, dan anggota</p>
                <a href="../modules/user/index.php">Kelola User</a>
            </div>

            <div class="action-card">
                <h3>Jadwal Latihan</h3>
                <p>Buat dan kelola jadwal latihan</p>
                <a href="../modules/absen/index.php">Kelola Jadwal</a>
            </div>

            <div class="action-card">
                <h3>Booking Acara</h3>
                <p>Kelola pesanan manggung grup</p>
                <a href="../modules/acara/index.php">Kelola Booking</a>
            </div>

            <div class="action-card">
                <h3>Inventaris Alat</h3>
                <p>Kelola alat musik dan inventaris</p>
                <a href="../modules/alat/index.php">Kelola Alat</a>
            </div>

            <div class="action-card">
                <h3>Keuangan</h3>
                <p>Kelola laporan kas dan keuangan</p>
                <a href="../modules/keuangan/index.php">Kelola Kas</a>
            </div>
        </div>
    </div>
</body>
</html>

