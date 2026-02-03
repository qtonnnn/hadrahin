<?php
/**
 * View Detail Transaksi Kas - AJAX
 */

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

// Validate input
$id_kas = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_kas <= 0) {
    echo '<div class="alert alert-danger">ID transaksi tidak valid</div>';
    exit;
}

// Get transaction details
try {
    $stmt = $pdo->prepare("
        SELECT k.*, u.nama_lengkap as recorded_by, u2.nama_lengkap as modified_by
        FROM keuangan k
        LEFT JOIN user u ON k.user_record = u.id_user
        LEFT JOIN user u2 ON k.user_modified = u2.id_user
        WHERE k.id_kas = ?
    ");
    $stmt->execute([$id_kas]);
    $kas = $stmt->fetch();
    
    if (!$kas) {
        echo '<div class="alert alert-danger">Transaksi tidak ditemukan</div>';
        exit;
    }
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}

// Get acara info if applicable
$acara_info = '';
if ($kas['id_acara']) {
    try {
        $stmt = $pdo->prepare("SELECT nama_acara, tanggal_acara, lokasi FROM booking_acara WHERE id_booking = ?");
        $stmt->execute([$kas['id_acara']]);
        $acara = $stmt->fetch();
        if ($acara) {
            $acara_info = '
                <tr>
                    <td class="text-muted" width="150">Acara:</td>
                    <td>' . htmlspecialchars($acara['nama_acara']) . '</td>
                </tr>
                <tr>
                    <td class="text-muted">Tanggal Acara:</td>
                    <td>' . date('d/m/Y', strtotime($acara['tanggal_acara'])) . '</td>
                </tr>
                <tr>
                    <td class="text-muted">Lokasi:</td>
                    <td>' . htmlspecialchars($acara['lokasi']) . '</td>
                </tr>
            ';
        }
    } catch (PDOException $e) {
        // Ignore
    }
}

// Format currency
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>

<table class="table table-borderless mb-0">
    <tr>
        <td class="text-muted" width="150">ID Transaksi:</td>
        <td><strong>#<?= str_pad($kas['id_kas'], 6, '0', STR_PAD_LEFT) ?></strong></td>
    </tr>
    <tr>
        <td class="text-muted">Tipe:</td>
        <td>
            <?php if ($kas['tipe'] === 'pemasukan'): ?>
                <span class="badge bg-success"><i class="fas fa-arrow-down me-1"></i>Pemasukan</span>
            <?php else: ?>
                <span class="badge bg-danger"><i class="fas fa-arrow-up me-1"></i>Pengeluaran</span>
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <td class="text-muted">Kategori:</td>
        <td><?= htmlspecialchars($kas['kategori'] ?? '-') ?></td>
    </tr>
    <?= $acara_info ?>
    <tr>
        <td class="text-muted">Jumlah:</td>
        <td>
            <strong class="<?= $kas['tipe'] === 'pemasukan' ? 'text-success' : 'text-danger' ?>" style="font-size: 1.2rem;">
                <?= $kas['tipe'] === 'pemasukan' ? '+' : '-' ?>
                <?= formatRupiah($kas['jumlah']) ?>
            </strong>
        </td>
    </tr>
    <tr>
        <td class="text-muted">Tanggal:</td>
        <td><?= date('d/m/Y', strtotime($kas['tanggal'])) ?></td>
    </tr>
    <?php if ($kas['keterangan']): ?>
    <tr>
        <td class="text-muted">Keterangan:</td>
        <td><?= htmlspecialchars($kas['keterangan']) ?></td>
    </tr>
    <?php endif; ?>
    <tr>
        <td class="text-muted">Dicatat Oleh:</td>
        <td><?= htmlspecialchars($kas['recorded_by'] ?? 'System') ?></td>
    </tr>
    <tr>
        <td class="text-muted">Dicatat Pada:</td>
        <td><?= date('d/m/Y H:i', strtotime($kas['created_at'])) ?></td>
    </tr>
    <?php if ($kas['modified_by']): ?>
    <tr>
        <td class="text-muted">Diubah Oleh:</td>
        <td><?= htmlspecialchars($kas['modified_by']) ?></td>
    </tr>
    <tr>
        <td class="text-muted">Diubah Pada:</td>
        <td><?= date('d/m/Y H:i', strtotime($kas['updated_at'])) ?></td>
    </tr>
    <?php endif; ?>
</table>

<div class="mt-3 pt-3 border-top">
    <small class="text-muted">
        <i class="fas fa-info-circle me-1"></i>
        Dibuat: <?= date('d F Y, H:i', strtotime($kas['created_at'])) ?>
        <?php if ($kas['updated_at'] != $kas['created_at']): ?>
            | Terakhir diperbarui: <?= date('d F Y, H:i', strtotime($kas['updated_at'])) ?>
        <?php endif; ?>
    </small>
</div>

