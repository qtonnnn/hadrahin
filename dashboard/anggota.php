<?php
require_once '../config/koneksi.php';
require_once '../core/middleware.php';

// wajib login
cekLogin();

// wajib anggota (semua peran bisa akses dashboard anggota)
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Anggota</title>
</head>
<body>

<h2>Dashboard Anggota</h2>

<p>
    Anggota berhasil login.<br>
    Selamat datang, <strong><?= $_SESSION['nama']; ?></strong>
</p>

<a href="<?= BASE_URL; ?>/auth/logout.php">Logout</a>

</body>
</html>
