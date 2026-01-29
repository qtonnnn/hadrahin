<?php
/**
 * Hapus Alat - Konfirmasi Penghapusan dengan sidebar layout
 */

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

// Check if user is admin
if ($_SESSION['peran'] !== 'admin') {
    header('Location: index.php?msg=error');
    exit;
}

// Get id_alat from URL
$id_alat = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_alat <= 0) {
    header('Location: index.php?msg=error');
    exit;
}

// Get alat data
$stmt = $pdo->prepare("SELECT nama_alat FROM alat WHERE id_alat = ?");
$stmt->execute([$id_alat]);
$alat = $stmt->fetch();

if (!$alat) {
    header('Location: index.php?msg=error');
    exit;
}

// Handle delete (direct)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && $_POST['delete'] === 'yes') {
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT nama_alat FROM alat WHERE id_alat = ?");
        $stmt->execute([$id_alat]);
        $alat_name = $stmt->fetch()['nama_alat'];
        
        $stmt = $pdo->prepare("DELETE FROM alat WHERE id_alat = ?");
        $stmt->execute([$id_alat]);
        
        $pdo->commit();
        header('Location: index.php?msg=hapus_sukes');
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        header('Location: index.php?msg=error');
        exit;
    }
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
                
                <h4 class="text-danger mb-3">Hapus Alat</h4>
                
                <p class="text-muted mb-4">
                    Apakah Anda yakin ingin menghapus alat berikut?
                </p>
                
                <div class="alert alert-warning mb-4">
                    <strong><?= htmlspecialchars($alat['nama_alat']) ?></strong>
                </div>
                
                <div class="alert alert-danger mb-4 text-start">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Peringatan!</strong> Tindakan ini akan menghapus:
                    <ul class="mb-0 mt-2">
                        <li>Data alat dari database</li>
                        <li>Semua data pengguna yang terkait</li>
                        <li>Tindakan ini tidak dapat dibatalkan!</li>
                    </ul>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="delete" value="yes">
                    
                    <div class="d-flex justify-content-center gap-2">
                        <a href="index.php" class="btn btn-secondary px-4">
                            <i class="fas fa-times me-1"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-danger px-4">
                            <i class="fas fa-trash me-1"></i> Hapus Alat
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

