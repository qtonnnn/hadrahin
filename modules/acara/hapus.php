<?php
/**
 * Hapus Booking Acara - Konfirmasi Hapus
 */

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

$page_title = "Hapus Booking Acara";

// Check permission - only admin can delete
if ($_SESSION['peran'] !== 'admin') {
    header('Location: index.php?msg=access_denied');
    exit;
}

// Get booking ID from URL dengan validasi
$id_booking = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_booking <= 0) {
    header('Location: index.php?msg=error');
    exit;
}

// Fetch booking data untuk konfirmasi
try {
    $stmt = $pdo->prepare("SELECT ba.*, u.nama_lengkap as penanggung_jawab, d.nama_pakaian as dresscode_name
                           FROM booking_acara ba
                           LEFT JOIN user u ON ba.id_user = u.id_user
                           LEFT JOIN dresscode d ON ba.id_dresscode = d.id_dresscode
                           WHERE ba.id_booking = ?");
    $stmt->execute([$id_booking]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        header('Location: index.php?msg=error');
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching booking: " . $e->getMessage());
    header('Location: index.php?msg=error');
    exit;
}

// Status labels
$status_labels = [
    'menunggu' => 'Menunggu',
    'diterima' => 'Diterima',
    'ditolak' => 'Ditolak',
    'selesai' => 'Selesai'
];

$status_badges = [
    'menunggu' => 'bg-warning text-dark',
    'diterima' => 'bg-success',
    'ditolak' => 'bg-danger',
    'selesai' => 'bg-info'
];

// Handle delete confirmation
if (isset($_POST['konfirmasi']) && $_POST['konfirmasi'] === 'ya') {
    try {
        $pdo->beginTransaction();
        
        // Get booking name for logging
        $stmt = $pdo->prepare("SELECT nama_acara FROM booking_acara WHERE id_booking = ?");
        $stmt->execute([$id_booking]);
        $nama_acara = $stmt->fetch()['nama_acara'];
        
        // Delete booking (dokumentasi akan dihapus otomatis karena ON DELETE CASCADE)
        $stmt = $pdo->prepare("DELETE FROM booking_acara WHERE id_booking = ?");
        $stmt->execute([$id_booking]);
        
        $pdo->commit();
        
        // Log activity
        error_log("Booking acara dihapus: ID=$id_booking, Nama=$nama_acara, User=" . $_SESSION['user_id']);
        
        header('Location: index.php?success=booking_deleted');
        exit;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error deleting booking: " . $e->getMessage());
        header('Location: index.php?msg=error');
        exit;
    }
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
                    <strong>Peringatan!</strong> Tindakan ini tidak dapat dibatalkan. Data booking acara akan dihapus secara permanen.
                </div>
                
                <!-- Booking Info -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title text-danger mb-3">Data Booking yang akan dihapus:</h5>
                        
                        <div class="row mb-3">
                            <div class="col-4 fw-bold">Nama Acara:</div>
                            <div class="col-8">
                                <div class="d-flex align-items-center">
                                    <div class="bg-info bg-opacity-25 text-info rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 14px;">
                                        <i class="fas fa-calendar"></i>
                                    </div>
                                    <strong><?= htmlspecialchars($booking['nama_acara']) ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-2">
                            <div class="col-4 fw-bold">Pemesan:</div>
                            <div class="col-8"><?= htmlspecialchars($booking['nama_pemesan']) ?></div>
                        </div>
                        
                        <div class="row mb-2">
                            <div class="col-4 fw-bold">Tanggal Acara:</div>
                            <div class="col-8">
                                <i class="far fa-calendar text-muted me-1"></i>
                                <?= date('l, d F Y', strtotime($booking['tanggal_acara'])) ?>
                            </div>
                        </div>
                        
                        <div class="row mb-2">
                            <div class="col-4 fw-bold">Jam:</div>
                            <div class="col-8">
                                <i class="far fa-clock text-muted me-1"></i>
                                <?= date('H:i', strtotime($booking['jam_mulai'])) ?> WIB
                            </div>
                        </div>
                        
                        <div class="row mb-2">
                            <div class="col-4 fw-bold">Lokasi:</div>
                            <div class="col-8">
                                <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                <?= htmlspecialchars($booking['lokasi']) ?>
                            </div>
                        </div>
                        
                        <div class="row mb-2">
                            <div class="col-4 fw-bold">Dresscode:</div>
                            <div class="col-8">
                                <span class="badge bg-secondary">
                                    <?= htmlspecialchars($booking['dresscode_name'] ?? 'Tidak ada') ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="row mb-2">
                            <div class="col-4 fw-bold">Status:</div>
                            <div class="col-8">
                                <span class="badge <?= $status_badges[$booking['status']] ?>">
                                    <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                                    <?= $status_labels[$booking['status']] ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if (!empty($booking['keterangan'])): ?>
                            <div class="row">
                                <div class="col-4 fw-bold">Keterangan:</div>
                                <div class="col-8"><?= htmlspecialchars($booking['keterangan']) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Warning about related data -->
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Data terkait juga akan dihapus!</strong><br>
                    Semua dokumentasi foto/video yang terkait dengan booking ini akan dihapus secara permanen.
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

