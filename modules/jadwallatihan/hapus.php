<?php
/**
 * Hapus Jadwal Latihan - Konfirmasi Hapus
 */

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
require_once '../../includes/cache.php';

$page_title = "Hapus Jadwal Latihan";

// Get current user role
$user_peran = $_SESSION['peran'] ?? 'anggota';
$user_id = $_SESSION['user_id'] ?? 0;

// Get jadwal ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php?msg=error');
    exit;
}

// Check permission - only admin can delete
if ($user_peran !== 'admin') {
    header('Location: index.php?msg=access_denied');
    exit;
}

// Fetch jadwal data
$stmt = $pdo->prepare("SELECT * FROM jadwal_latihan WHERE id_jadwal = ?");
$stmt->execute([$id]);
$jadwal = $stmt->fetch();

if (!$jadwal) {
    header('Location: index.php?msg=error');
    exit;
}

// Handle delete confirmation
if (isset($_POST['konfirmasi']) && $_POST['konfirmasi'] === 'ya') {
    // Delete jadwal (CASCADE will delete related absen_latihan records)
    $stmt = $pdo->prepare("DELETE FROM jadwal_latihan WHERE id_jadwal = ?");
    $stmt->execute([$id]);

    // Clear cache after delete
    Cache::delete('jadwal_stats');
    Cache::delete('jadwal_max_date');

    header('Location: index.php?msg=hapus_sukes');
    exit;
}

// Include header dengan sidebar
include '../../includes/header.php';
?>

<!-- Delete Confirmation Card -->
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white py-3">
                <div class="d-flex align-items-center">
                    <a href="index.php" class="btn btn-outline-light me-3">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h4 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i><?= $page_title ?></h4>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Peringatan!</strong> Tindakan ini tidak dapat dibatalkan.
                </div>
                
                <!-- Jadwal Info -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title text-danger mb-3">Data Jadwal yang akan dihapus:</h5>
                        <?php
                        $tanggal = new DateTime($jadwal['tanggal']);
                        $status_class = match($jadwal['status']) {
                            'selesai' => 'bg-success',
                            'dibatalkan' => 'bg-danger',
                            default => 'bg-warning text-dark'
                        };
                        ?>
                        <div class="row mb-2">
                            <div class="col-4 fw-bold">Tanggal:</div>
                            <div class="col-8">
                                <?= $tanggal->format('l, d F Y') ?>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 fw-bold">Jam:</div>
                            <div class="col-8">
                                <i class="far fa-clock text-muted me-1"></i>
                                <?= date('H:i', strtotime($jadwal['jam_mulai'])) ?>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 fw-bold">Lokasi:</div>
                            <div class="col-8">
                                <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                <?= htmlspecialchars($jadwal['lokasi']) ?>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 fw-bold">Status:</div>
                            <div class="col-8">
                                <span class="badge <?= $status_class ?>">
                                    <?= ucfirst($jadwal['status']) ?>
                                </span>
                            </div>
                        </div>
                        <?php if (!empty($jadwal['catatan'])): ?>
                            <div class="row">
                                <div class="col-4 fw-bold">Catatan:</div>
                                <div class="col-8"><?= htmlspecialchars($jadwal['catatan']) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Warning about related data -->
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Data terkait juga akan dihapus!</strong><br>
                    Semua data absensi yang terkait dengan jadwal ini akan dihapus secara permanen.
                </div>
                
                <!-- Confirmation Form -->
                <form method="POST">
                    <input type="hidden" name="konfirmasi" value="ya">
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger btn-lg">
                            <i class="fas fa-trash me-2"></i>Ya, Hapus Permanen
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

