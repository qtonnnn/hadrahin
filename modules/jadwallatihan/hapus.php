<?php
// hapus.php - Proses hapus jadwal latihan

require_once '../../config/koneksi.php';
require_once '../../core/middleware.php';

// Wajib login
cekLogin();

// Wajib admin atau pembina
cekRole(['admin', 'pembina']);

// Validasi ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?msg=ID+tidak+valid');
    exit;
}

$id = (int)$_GET['id'];

// Hapus data jadwal
$sql = "DELETE FROM jadwal_latihan WHERE id = $id";

if (mysqli_query($koneksi, $sql)) {
    header('Location: index.php?msg=Jadwal+berhasil+dihapus');
    exit;
} else {
    header('Location: index.php?msg=Gagal+menghapus+jadwal');
    exit;
}

