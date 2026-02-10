<?php
/**
 * View Booking Details (AJAX)
 * Mengambil detail booking untuk ditampilkan di modal
 */

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

// Get booking ID
$id_booking = $_GET['id'] ?? 0;

if (empty($id_booking) || !is_numeric($id_booking)) {
    echo '<div class="alert alert-danger">ID booking tidak valid</div>';
    exit;
}

// Get booking data with user and dresscode info
try {
    $stmt = $pdo->prepare("SELECT ba.*, u.nama_lengkap as penanggung_jawab, d.nama_pakaian as dresscode_name
                           FROM booking_acara ba
                           LEFT JOIN user u ON ba.id_user = u.id_user
                           LEFT JOIN dresscode d ON ba.id_dresscode = d.id_dresscode
                           WHERE ba.id_booking = ?");
    $stmt->execute([$id_booking]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo '<div class="alert alert-danger">Booking tidak ditemukan</div>';
        exit;
    }
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}

$status_labels = [
    'menunggu' => 'Menunggu Konfirmasi',
    'diterima' => 'Diterima',
    'ditolak' => 'Ditolak',
    'selesai' => 'Selesai'
];

$status_badges = [
    'menunggu' => 'warning',
    'diterima' => 'success',
    'ditolak' => 'danger',
    'selesai' => 'info'
];
?>

<table class="table table-bordered">
    <tr>
        <th width="30%">Nama Acara</th>
        <td><?= htmlspecialchars($booking['nama_acara']) ?></td>
    </tr>
    <tr>
        <th>Status</th>
        <td>
            <span class="badge bg-<?= $status_badges[$booking['status']] ?>">
                <?= $status_labels[$booking['status']] ?>
            </span>
        </td>
    </tr>
    <tr>
        <th>Nama Pemesan</th>
        <td><?= htmlspecialchars($booking['nama_pemesan']) ?></td>
    </tr>
    <tr>
        <th>No HP Pemesan</th>
        <td><?= htmlspecialchars($booking['no_hp_pemesan'] ?? '-') ?></td>
    </tr>
    <tr>
        <th>Tanggal Acara</th>
        <td><?= date('d F Y', strtotime($booking['tanggal_acara'])) ?></td>
    </tr>
    <tr>
        <th>Jam Mulai</th>
        <td><?= date('H:i', strtotime($booking['jam_mulai'])) ?> WIB</td>
    </tr>
    <tr>
        <th>Lokasi</th>
        <td><?= htmlspecialchars($booking['lokasi']) ?></td>
    </tr>
    <tr>
        <th>Dresscode</th>
        <td><?= htmlspecialchars($booking['dresscode_name'] ?? 'Tidak ada') ?></td>
    </tr>
    <tr>
        <th>Penanggung Jawab</th>
        <td><?= htmlspecialchars($booking['penanggung_jawab'] ?? '-') ?></td>
    </tr>
    <tr>
        <th>Keterangan</th>
        <td><?= nl2br(htmlspecialchars($booking['keterangan'] ?? '-')) ?></td>
    </tr>
</table>

<div class="row mt-3">
    <div class="col-md-4">
        <small class="text-muted">Dibuat: <?= date('d/m/Y H:i', strtotime($booking['created_at'])) ?></small>
    </div>
    <div class="col-md-4">
        <small class="text-muted">Diubah: <?= date('d/m/Y H:i', strtotime($booking['updated_at'])) ?></small>
    </div>
</div>

