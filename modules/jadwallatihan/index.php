<?php
// index.php - Menampilkan daftar jadwal latihan

require_once '../../config/koneksi.php';
require_once '../../core/middleware.php';

// Wajib login
cekLogin();

// Wajib admin atau pembina
cekRole(['admin', 'pembina']);

// Query JOIN untuk menampilkan jadwal latihan beserta nama pembuat
$query = mysqli_query($koneksi, "
    SELECT j.*, u.nama_user as pembuat 
    FROM jadwal_latihan j
    LEFT JOIN user u ON j.created_by = u.id_user
    ORDER BY j.tanggal DESC, j.jam_mulai DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daftar Jadwal Latihan</title>
</head>
<body>
    <h2>Daftar Jadwal Latihan</h2>
    
    <p>
        <a href="add.php">[+] Tambah Jadwal</a> | 
        <a href="../../dashboard/admin.php">Kembali ke Dashboard</a>
    </p>
    
    <?php if (isset($_GET['msg'])): ?>
        <p><strong><?= $_GET['msg'] ?></strong></p>
    <?php endif; ?>
    
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Jam</th>
                <th>Lokasi</th>
                <th>Status</th>
                <th>Pembuat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($query && mysqli_num_rows($query) > 0): ?>
                <?php $no = 1; ?>
                <?php while ($row = mysqli_fetch_assoc($query)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                        <td><?= $row['jam_mulai'] ?> - <?= $row['jam_selesai'] ?></td>
                        <td><?= htmlspecialchars($row['lokasi']) ?></td>
                        <td><?= $row['status'] ?></td>
                        <td><?= htmlspecialchars($row['pembuat'] ?? 'Tidak diketahui') ?></td>
                        <td>
                            <a href="edit.php?id=<?= $row['id'] ?>">Edit</a> | 
                            <a href="hapus.php?id=<?= $row['id'] ?>" onclick="return confirm('Yakin hapus jadwal ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" align="center">Belum ada jadwal latihan</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

