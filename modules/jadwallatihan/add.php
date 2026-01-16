<?php
// add.php - Form tambah jadwal latihan + proses insert

require_once '../../config/koneksi.php';
require_once '../../core/middleware.php';
require_once 'validasi.php';

// Wajib login
cekLogin();

// Wajib admin atau pembina
cekRole(['admin', 'pembina']);

// Proses simpan data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $jam_mulai = mysqli_real_escape_string($koneksi, $_POST['jam_mulai']);
    $jam_selesai = mysqli_real_escape_string($koneksi, $_POST['jam_selesai']);
    $lokasi = mysqli_real_escape_string($koneksi, $_POST['lokasi']);
    $created_by = $_SESSION['user_id'];
    
    // Validasi input
    $errors = [];
    
    if (empty($tanggal)) {
        $errors[] = "Tanggal wajib diisi";
    }
    if (empty($jam_mulai)) {
        $errors[] = "Jam mulai wajib diisi";
    }
    if (empty($jam_selesai)) {
        $errors[] = "Jam selesai wajib diisi";
    }
    if (empty($lokasi)) {
        $errors[] = "Lokasi wajib diisi";
    }
    if ($jam_mulai >= $jam_selesai) {
        $errors[] = "Jam selesai harus lebih besar dari jam mulai";
    }
    
    // Validasi bentrok jadwal
    if (empty($errors)) {
        if (cekBentrokJadwal($tanggal, $jam_mulai, $jam_selesai)) {
            $errors[] = "Jadwal bentrok dengan jadwal yang sudah ada pada tanggal dan jam tersebut";
        }
    }
    
    // Jika tidak ada error, simpan data
    if (empty($errors)) {
        $sql = "INSERT INTO jadwal_latihan (tanggal, jam_mulai, jam_selesai, lokasi, status, created_by) 
                VALUES ('$tanggal', '$jam_mulai', '$jam_selesai', '$lokasi', 'aktif', '$created_by')";
        
        if (mysqli_query($koneksi, $sql)) {
            header('Location: index.php?msg=Jadwal+berhasil+ditambahkan');
            exit;
        } else {
            $errors[] = "Gagal menyimpan data: " . mysqli_error($koneksi);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Jadwal Latihan</title>
</head>
<body>
    <h2>Tambah Jadwal Latihan</h2>
    
    <p><a href="index.php">« Kembali ke Daftar Jadwal</a></p>
    
    <?php if (!empty($errors)): ?>
        <div style="color: red;">
            <strong>Error:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <label for="tanggal">Tanggal *</label><br>
        <input type="date" id="tanggal" name="tanggal" value="<?= isset($_POST['tanggal']) ? $_POST['tanggal'] : '' ?>" required><br><br>
        
        <label for="jam_mulai">Jam Mulai *</label><br>
        <input type="time" id="jam_mulai" name="jam_mulai" value="<?= isset($_POST['jam_mulai']) ? $_POST['jam_mulai'] : '' ?>" required><br><br>
        
        <label for="jam_selesai">Jam Selesai *</label><br>
        <input type="time" id="jam_selesai" name="jam_selesai" value="<?= isset($_POST['jam_selesai']) ? $_POST['jam_selesai'] : '' ?>" required><br><br>
        
        <label for="lokasi">Lokasi *</label><br>
        <input type="text" id="lokasi" name="lokasi" value="<?= isset($_POST['lokasi']) ? htmlspecialchars($_POST['lokasi']) : '' ?>" required><br><br>
        
        <button type="submit">Simpan</button>
    </form>
</body>
</html>

