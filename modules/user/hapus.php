<?php
// proteksi halaman - hanya admin yang boleh akses
require_once '../../config/koneksi.php';
require_once '../../core/middleware.php';

cekLogin();
cekRole(['admin']);

// Validasi id_user
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = 'ID user tidak valid';
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

$id_user = (int)$_GET['id'];

// Cek apakah user yang akan dihapus adalah diri sendiri
if ($id_user == $_SESSION['user_id']) {
    $_SESSION['message'] = 'Anda tidak dapat menghapus akun sendiri!';
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

// Ambil data user yang akan dihapus
$query = mysqli_query($koneksi, "SELECT username FROM user WHERE id_user = $id_user");
$user = mysqli_fetch_assoc($query);

if (!$user) {
    $_SESSION['message'] = 'User tidak ditemukan';
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

// Hapus user dari database
$query = mysqli_query($koneksi, "DELETE FROM user WHERE id_user = $id_user");

if ($query) {
    $_SESSION['message'] = 'User ' . htmlspecialchars($user['username']) . ' berhasil dihapus!';
    $_SESSION['message_type'] = 'success';
} else {
    $_SESSION['message'] = 'Gagal menghapus user: ' . mysqli_error($koneksi);
    $_SESSION['message_type'] = 'error';
}

header('Location: index.php');
exit;

