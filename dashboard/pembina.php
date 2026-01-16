<?php
require_once '../config/koneksi.php';
require_once '../core/middleware.php';

// wajib login
cekLogin();

// wajib pembina
cekRole(['pembina']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Pembina</title>
</head>
<body>

<h2>Dashboard Pembina</h2>

<p>
    Pembina berhasil login.<br>
    Selamat datang, <strong><?= $_SESSION['nama']; ?></strong>
</p>

<a href="<?= BASE_URL; ?>/auth/logout.php">Logout</a>

</body>
</html>
