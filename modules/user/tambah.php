<?php
// proteksi halaman - hanya admin yang boleh akses
require_once '../../config/koneksi.php';
require_once '../../core/middleware.php';

cekLogin();
cekRole(['admin']);

// Jika form disubmit, proses simpan
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $peran = $_POST['peran'];
    
    // Validasi
    $errors = [];
    if (empty($nama_lengkap)) {
        $errors[] = 'Nama lengkap wajib diisi';
    }
    if (empty($username)) {
        $errors[] = 'Username wajib diisi';
    }
    if (empty($password)) {
        $errors[] = 'Password wajib diisi';
    }
    if (strlen($password) < 3) {
        $errors[] = 'Password minimal 3 karakter';
    }
    
    // Cek username sudah ada atau belum
    $cek = mysqli_query($koneksi, "SELECT id_user FROM user WHERE username = '" . mysqli_real_escape_string($koneksi, $username) . "'");
    if (mysqli_num_rows($cek) > 0) {
        $errors[] = 'Username sudah digunakan';
    }
    
    if (empty($errors)) {
        // Insert ke database (password disimpan plain text sesuai permintaan)
        $query = mysqli_query($koneksi, "
            INSERT INTO user (nama_lengkap, username, password, peran, status_aktif, tanggal_gabung, user_record, created_at) 
            VALUES (
                '" . mysqli_real_escape_string($koneksi, $nama_lengkap) . "',
                '" . mysqli_real_escape_string($koneksi, $username) . "',
                '" . mysqli_real_escape_string($koneksi, $password) . "',
                '" . mysqli_real_escape_string($koneksi, $peran) . "',
                1,
                CURDATE(),
                '" . mysqli_real_escape_string($koneksi, $_SESSION['nama']) . "',
                NOW()
            )
        ");
        
        if ($query) {
            $_SESSION['message'] = 'User berhasil ditambahkan!';
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
    <title>Tambah User - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-container { max-width: 500px; margin: 0 auto; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input[type="text"], input[type="password"], select { 
            width: 100%; padding: 8px; margin-top: 5px; 
            border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;
        }
        .btn { padding: 10px 20px; text-decoration: none; color: white; border-radius: 4px; border: none; cursor: pointer; margin-top: 15px; }
        .btn-simpan { background-color: #4CAF50; }
        .btn-batal { background-color: #f44336; display: inline-block; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .alert-error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Tambah User</h1>
        
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
            <input type="text" id="username" name="username" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>
            
            <label for="nama_lengkap">Nama Lengkap *</label>
            <input type="text" id="nama_lengkap" name="nama_lengkap" value="<?= isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : '' ?>" required>
            
<label for="password">Password *</label>
            <div style="position: relative;">
                <input type="password" id="password" name="password" required minlength="3" style="padding-right: 40px;">
                <span onclick="togglePassword('password')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">👁️</span>
            </div>
            
            <label for="peran">Peran *</label>
            <select id="peran" name="peran" required>
                <option value="admin" <?= (isset($_POST['peran']) && $_POST['peran'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                <option value="pembina" <?= (isset($_POST['peran']) && $_POST['peran'] == 'pembina') ? 'selected' : '' ?>>Pembina</option>
                <option value="anggota" <?= (isset($_POST['peran']) && $_POST['peran'] == 'anggota') ? 'selected' : '' ?>>Anggota</option>
            </select>
            
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

