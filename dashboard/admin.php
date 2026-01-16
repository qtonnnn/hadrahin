<?php
require_once '../config/koneksi.php';
require_once '../core/middleware.php';

// wajib login
cekLogin();

// wajib admin
cekRole(['admin']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Admin</title>
</head>
<body>

<h2>Dashboard Admin</h2>

<p>
    Admin berhasil login.<br>
    Selamat datang, <strong><?= $_SESSION['nama']; ?></strong>
</p>

<h3>Menu Admin</h3>
<ul>
    <li><a href="<?= BASE_URL; ?>/modules/user/index.php">Kelola User</a></li>
</ul>

<a href="<?= BASE_URL; ?>/auth/logout.php">Logout</a>

</body>
</html>
