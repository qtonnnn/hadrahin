<?php
require_once '../config/koneksi.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if ($username == '' || $password == '') {
    header('Location: login.php');
    exit;
}

//ambil data user berdasarkan username (menggunakan prepared statement untuk keamanan)
$stmt = mysqli_prepare($koneksi, "SELECT * FROM user WHERE username = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    die('Username tidak ditemukan');
}

// cek password - mendukung password hash maupun plain text
$password_valid = false;
if (password_get_info($user['password'])['algo'] > 0) {
    // Password sudah di-hash
    $password_valid = password_verify($password, $user['password']);
} else {
    // Password plain text (untuk compatibility dengan data lama)
    $password_valid = ($password === $user['password']);
}

if (!$password_valid) {
    die('Password salah');
}

// set session
$_SESSION['user_id'] = $user['id_user'];
$_SESSION['nama'] = $user['nama_lengkap'];
$_SESSION['role'] = $user['peran'];
$_SESSION['login'] = true;

// redirect sesuai peran
if ($user['peran'] == 'admin') {
    header('Location: ' . BASE_URL . '/dashboard/admin.php');
} elseif ($user['peran'] == 'pembina') {
    header('Location: ' . BASE_URL . '/dashboard/pembina.php');
} else {
    header('Location: ' . BASE_URL . '/dashboard/anggota.php');
}

exit;
