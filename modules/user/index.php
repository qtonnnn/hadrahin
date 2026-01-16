<?php
// proteksi halaman - hanya admin yang boleh akses
require_once '../../config/koneksi.php';
require_once '../../core/middleware.php';

cekLogin();
cekRole(['admin']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kelola User - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .btn { padding: 5px 10px; text-decoration: none; color: white; border-radius: 4px; }
        .btn-tambah { background-color: #2196F3; margin-bottom: 10px; display: inline-block; }
        .btn-edit { background-color: #FF9800; }
        .btn-hapus { background-color: #f44336; border: none; cursor: pointer; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .alert-success { background-color: #d4edda; color: #155724; }
        .alert-error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h1>Kelola User</h1>
    
    <a href="tambah.php" class="btn btn-tambah">+ Tambah User</a>
    <a href="../../dashboard/admin.php" class="btn" style="background-color: #607D8B;">&larr; Kembali</a>
    <br><br>

    <?php
    // Tampilkan pesan sukses/error
    if (isset($_SESSION['message'])) {
        echo '<div class="alert alert-' . $_SESSION['message_type'] . '">' . $_SESSION['message'] . '</div>';
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
    ?>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Lengkap</th>
                <th>Username</th>
                <th>Role</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query = mysqli_query($koneksi, "SELECT * FROM user ORDER BY id_user DESC");
            $no = 1;
            while ($user = mysqli_fetch_assoc($query)) {
                $status = $user['status_aktif'] ? 'Aktif' : 'Non-aktif';
                $status_color = $user['status_aktif'] ? 'green' : 'red';
                echo '<tr>';
                echo '<td>' . $no++ . '</td>';
                echo '<td>' . htmlspecialchars($user['nama_lengkap']) . '</td>';
                echo '<td>' . htmlspecialchars($user['username']) . '</td>';
                echo '<td>' . ucfirst(htmlspecialchars($user['peran'])) . '</td>';
                echo '<td><span style="color: ' . $status_color . ';">' . $status . '</span></td>';
                echo '<td>';
                echo '<a href="edit.php?id=' . $user['id_user'] . '" class="btn btn-edit">Edit</a> ';
                // Jangan hapus diri sendiri
                if ($user['id_user'] != $_SESSION['user_id']) {
                    echo '<a href="hapus.php?id=' . $user['id_user'] . '" class="btn btn-hapus" onclick="return confirm(\'Yakin hapus user ' . htmlspecialchars($user['username']) . '?\')">Hapus</a>';
                } else {
                    echo '<span style="color: #999;">(Anda)</span>';
                }
                echo '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>
</body>
</html>

