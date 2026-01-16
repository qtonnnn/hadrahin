<?php
// edit.php - Form edit jadwal latihan + proses update

require_once '../../config/koneksi.php';
require_once '../../core/middleware.php';
require_once 'validasi.php';

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

// Ambil data jadwal
$query = mysqli_query($koneksi, "SELECT * FROM jadwal_latihan WHERE id = $id");
if (!$query || mysqli_num_rows($query) == 0) {
    header('Location: index.php?msg=Jadwal+tidak+ditemukan');
    exit;
}

$jadwal = mysqli_fetch_assoc($query);

// Proses update data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $jam_mulai = mysqli_real_escape_string($koneksi, $_POST['jam_mulai']);
    $jam_selesai = mysqli_real_escape_string($koneksi, $_POST['jam_selesai']);
    $lokasi = mysqli_real_escape_string($koneksi, $_POST['lokasi']);
    $status = mysqli_real_escape_string($koneksi, $_POST['status']);
    
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
    
    // Validasi bentrok jadwal (exclude id yang sedang diedit)
    if (empty($errors)) {
        if (cekBentrokJadwal($tanggal, $jam_mulai, $jam_selesai, $id)) {
            $errors[] = "Jadwal bentrok dengan jadwal yang sudah ada pada tanggal dan jam tersebut";
        }
    }
    
    // Jika tidak ada error, update data
    if (empty($errors)) {
        $sql = "UPDATE jadwal_latihan 
                SET tanggal = '$tanggal', 
                    jam_mulai = '$jam_mulai', 
                    jam_selesai = '$jam_selesai', 
                    lokasi = '$lokasi', 
                    status = '$status' 
                WHERE id = $id";
        
        if (mysqli_query($koneksi, $sql)) {
            header('Location: index.php?msg=Jadwal+berhasil+diupdate');
            exit;
        } else {
            $errors[] = "Gagal mengupdate data: " . mysqli_error($koneksi);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Jadwal Latihan</title>
</head>
<body>
    <h2>Edit Jadwal Latihan</h2>
    
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
        <input type="date" id="tanggal" name="tanggal" value="<?= isset($_POST['tanggal']) ? $_POST['tanggal'] : $jadwal['tanggal'] ?>" required><br><br>
        
        <label for="jam_mulai">Jam Mulai *</label><br>
        <input type="time" id="jam_mulai" name="jam_mulai" value="<?= isset($_POST['jam_mulai']) ? $_POST['jam_mulai'] : $jadwal['jam_mulai'] ?>" required><br><br>
        
        <label for="jam_selesai">Jam Selesai *</label><br>
        <input type="time" id="jam_selesai" name="jam_selesai" value="<?= isset($_POST['jam_selesai']) ? $_POST['jam_selesai'] : $jadwal['jam_selesai'] ?>" required><br><br>
        
        <label for="lokasi">Lokasi *</label><br>
        <input type="text" id="lokasi" name="lokasi" value="<?= isset($_POST['lokasi']) ? htmlspecialchars($_POST['lokasi']) : htmlspecialchars($jadwal['lokasi']) ?>" required><br><br>
        
        <label for="status">Status *</label><br>
        <select id="status" name="status" required>
            <option value="aktif" <?= (isset($_POST['status']) ? $_POST['status'] : $jadwal['status']) == 'aktif' ? 'selected' : '' ?>>Aktif</option>
            <option value="selesai" <?= (isset($_POST['status']) ? $_POST['status'] : $jadwal['status']) == 'selesai' ? 'selected' : '' ?>>Selesai</option>
        </select><br><br>
        
        <button type="submit">Simpan</button>
    </form>
</body>
</html>

