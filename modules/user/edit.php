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

// Ambil data user yang akan diedit
$query = mysqli_query($koneksi, "SELECT * FROM user WHERE id_user = $id_user");
$user = mysqli_fetch_assoc($query);

if (!$user) {
    $_SESSION['message'] = 'User tidak ditemukan';
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

// Jika form disubmit, proses update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $peran = $_POST['peran'];
    $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;
    
    // Validasi
    $errors = [];
    if (empty($nama_lengkap)) {
        $errors[] = 'Nama lengkap wajib diisi';
    }
    if (empty($username)) {
        $errors[] = 'Username wajib diisi';
    }
    
    // Cek username sudah ada atau belum (kecuali username sendiri)
    $cek = mysqli_query($koneksi, "SELECT id_user FROM user WHERE username = '" . mysqli_real_escape_string($koneksi, $username) . "' AND id_user != $id_user");
    if (mysqli_num_rows($cek) > 0) {
        $errors[] = 'Username sudah digunakan oleh user lain';
    }
    
    if (empty($errors)) {
        // Update ke database
        if (!empty($password)) {
            // Update dengan password baru
            $query = mysqli_query($koneksi, "
                UPDATE user SET 
                    nama_lengkap = '" . mysqli_real_escape_string($koneksi, $nama_lengkap) . "',
                    username = '" . mysqli_real_escape_string($koneksi, $username) . "',
                    password = '" . mysqli_real_escape_string($koneksi, $password) . "',
                    peran = '" . mysqli_real_escape_string($koneksi, $peran) . "',
                    status_aktif = $status_aktif,
                    user_modified = '" . mysqli_real_escape_string($koneksi, $_SESSION['nama']) . "',
                    updated_at = NOW()
                WHERE id_user = $id_user
            ");
        } else {
            // Update tanpa ubah password
            $query = mysqli_query($koneksi, "
                UPDATE user SET 
                    nama_lengkap = '" . mysqli_real_escape_string($koneksi, $nama_lengkap) . "',
                    username = '" . mysqli_real_escape_string($koneksi, $username) . "',
                    peran = '" . mysqli_real_escape_string($koneksi, $peran) . "',
                    status_aktif = $status_aktif,
                    user_modified = '" . mysqli_real_escape_string($koneksi, $_SESSION['nama']) . "',
                    updated_at = NOW()
                WHERE id_user = $id_user
            ");
        }
        
        if ($query) {
            // Jika user mengedit dirinya sendiri, update session
            if ($id_user == $_SESSION['user_id']) {
                $_SESSION['nama'] = $nama_lengkap;
                $_SESSION['role'] = $peran;
            }
            
            $_SESSION['message'] = 'User berhasil diperbarui!';
            $_SESSION['message_type'] = 'success';
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Gagal menyimpan ke database: ' . mysqli_error($koneksi);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit User - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-container { max-width: 500px; margin: 0 auto; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input[type="text"], input[type="password"], select { 
            width: 100%; padding: 8px; margin-top: 5px; 
            border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;
        }
        input[type="checkbox"] { margin-top: 10px; }
        .btn { padding: 10px 20px; text-decoration: none; color: white; border-radius: 4px; border: none; cursor: pointer; margin-top: 15px; }
        .btn-simpan { background-color: #4CAF50; }
        .btn-batal { background-color: #f44336; display: inline-block; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .alert-error { background-color: #f8d7da; color: #721c24; }
        .info { font-size: 12px; color: #666; margin-top: 3px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Edit User</h1>
        
        <a href="index.php" class="btn btn-batal">&larr; Kembali</a>
        <br><br>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
<form method="POST">
            <label for="username">Username *</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
            
            <label for="nama_lengkap">Nama Lengkap *</label>
            <input type="text" id="nama_lengkap" name="nama_lengkap" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
            
<label for="password">Password</label>
            <div style="position: relative;">
                <input type="password" id="password" name="password" style="padding-right: 40px;">
                <span onclick="togglePassword('password')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">👁️</span>
            </div>
            <div class="info">Kosongkan jika tidak ingin mengubah password</div>
            
            <label for="peran">Peran *</label>
            <select id="peran" name="peran" required>
                <option value="admin" <?= $user['peran'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="pembina" <?= $user['peran'] == 'pembina' ? 'selected' : '' ?>>Pembina</option>
                <option value="anggota" <?= $user['peran'] == 'anggota' ? 'selected' : '' ?>>Anggota</option>
            </select>
            
            <label>
                <input type="checkbox" name="status_aktif" <?= $user['status_aktif'] ? 'checked' : '' ?>>
                User Aktif
            </label>
            
            <button type="submit" class="btn btn-simpan">Simpan</button>
</form>
    </div>
    
    <script>
    function togglePassword(fieldId) {
        var field = document.getElementById(fieldId);
        if (field.type === "password") {
            field.type = "text";
        } else {
            field.type = "password";
        }
    }
    </script>
</body>
</html>

