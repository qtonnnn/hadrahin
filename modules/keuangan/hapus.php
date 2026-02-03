<?php
/**
 * Hapus Transaksi Kas - Konfirmasi Penghapusan dengan sidebar layout
 */

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

// Check if user is admin
if ($_SESSION['peran'] !== 'admin') {
    header('Location: index.php?msg=error');
    exit;
}

// Get id_kas from URL
$id_kas = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_kas <= 0) {
    header('Location: index.php?msg=error');
    exit;
}

// Get kas data
$stmt = $pdo->prepare("SELECT k.*, u.nama_lengkap FROM keuangan k LEFT JOIN user u ON k.id_user = u.id_user WHERE k.id_kas = ?");
$stmt->execute([$id_kas]);
$kas = $stmt->fetch();

if (!$kas) {
    header('Location: index.php?msg=error');
    exit;
}

// Handle delete (direct)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && $_POST['delete'] === 'yes') {
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("DELETE FROM keuangan WHERE id_kas = ?");
        $stmt->execute([$id_kas]);
        
        $pdo->commit();
        header('Location: index.php?msg=hapus_sukes');
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        header('Location: index.php?msg=error');
        exit;
    }
}

// Format currency
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Include header
include '../../includes/header.php';
?>

<!-- Confirmation Card -->
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body text-center p-5">
                <div class="text-danger mb-4" style="font-size: 4rem;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                
                <h4 class="text-danger mb-3">Hapus Transaksi</h4>
                
                <p class="text-muted mb-4">
                    Apakah Anda yakin ingin menghapus transaksi berikut?
                </p>
                
                <!-- Transaction Details -->
                <div class="text-start mb-4">
                    <div class="alert alert-light border rounded p-3">
                        <div class="row mb-2">
                            <div class="col-4 text-muted">Tipe:</div>
                            <div class="col-8">
                                <?php if ($kas['tipe'] === 'pemasukan'): ?>
                                    <span class="badge bg-success"><i class="fas fa-arrow-down me-1"></i>Pemasukan</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="fas fa-arrow-up me-1"></i>Pengeluaran</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 text-muted">Kategori:</div>
                            <div class="col-8 fw-bold"><?= htmlspecialchars($kas['kategori'] ?? '-') ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 text-muted">Jumlah:</div>
                            <div class="col-8 fw-bold <?= $kas['tipe'] === 'pemasukan' ? 'text-success' : 'text-danger' ?>">
                                <?= $kas['tipe'] === 'pemasukan' ? '+' : '-' ?>
                                <?= formatRupiah($kas['jumlah']) ?>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 text-muted">Tanggal:</div>
                            <div class="col-8"><?= date('d/m/Y', strtotime($kas['tanggal'])) ?></div>
                        </div>
                        <?php if ($kas['keterangan']): ?>
                            <div class="row">
                                <div class="col-4 text-muted">Keterangan:</div>
                                <div class="col-8"><?= htmlspecialchars($kas['keterangan']) ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($kas['nama_lengkap']): ?>
                            <div class="row mt-2">
                                <div class="col-4 text-muted">Dicatat Oleh:</div>
                                <div class="col-8"><?= htmlspecialchars($kas['nama_lengkap']) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="alert alert-danger mb-4 text-start">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Peringatan!</strong> Tindakan ini akan menghapus transaksi secara permanen dan tidak dapat dibatalkan.
                </div>
                
                <form method="POST">
                    <input type="hidden" name="delete" value="yes">
                    
                    <div class="d-flex justify-content-center gap-2">
                        <a href="index.php" class="btn btn-secondary px-4">
                            <i class="fas fa-times me-1"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-danger px-4">
                            <i class="fas fa-trash me-1"></i> Hapus Transaksi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

