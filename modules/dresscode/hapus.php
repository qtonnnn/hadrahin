<?php
/**
 * Hapus Dresscode - Konfirmasi Hapus
 * 
 * @author Your Name
 * @version 1.0
 */

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

// Set page metadata
$page_title = "Hapus Dresscode";
$current_page = 'dresscode';

// Get dresscode ID from URL dengan validasi
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'text' => 'ID dresscode tidak valid!'
    ];
    header('Location: index.php');
    exit;
}

// Fetch dresscode data untuk konfirmasi
try {
    $stmt = $pdo->prepare("SELECT * FROM dresscode WHERE id_dresscode = ?");
    $stmt->execute([$id]);
    $dresscode = $stmt->fetch();
    
    if (!$dresscode) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'text' => 'Data dresscode tidak ditemukan!'
        ];
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching dresscode: " . $e->getMessage());
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'text' => 'Terjadi kesalahan saat mengambil data!'
    ];
    header('Location: index.php');
    exit;
}

// Handle delete confirmation - Hard delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM dresscode WHERE id_dresscode = ?");
        $stmt->execute([$id]);
        
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'text' => 'Dresscode berhasil dihapus!'
        ];
        
        header('Location: index.php');
        exit;
    } catch (PDOException $e) {
        error_log("Error deleting dresscode: " . $e->getMessage());
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'text' => 'Terjadi kesalahan saat menghapus data!'
        ];
    }
}

// Include header
include '../../includes/header.php';
?>

<!-- Delete Confirmation Card -->
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
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
                    <strong>Peringatan!</strong> Tindakan ini tidak dapat dibatalkan. Data dresscode akan dihapus secara permanen.
                </div>

                <!-- Dresscode Info -->
                <div class="card bg-light mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; font-size: 20px;">
                                <i class="fas fa-tshirt"></i>
                            </div>
                            <div>
                                <strong><?= htmlspecialchars($dresscode['nama_pakaian']) ?></strong><br>
                                <small class="text-muted">ID: #<?= str_pad($dresscode['id_dresscode'], 4, '0', STR_PAD_LEFT) ?></small>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="badge <?= $dresscode['status'] === 'aktif' ? 'bg-success' : 'bg-secondary' ?>">
                                <?= htmlspecialchars(ucfirst($dresscode['status'])) ?>
                            </span>
                            <?php if (!empty($dresscode['warna'])): ?>
                                <?php
                                $warna = htmlspecialchars($dresscode['warna']);
                                // Convert hex to RGB for brightness calculation
                                $hex = ltrim($warna, '#');
                                if (strlen($hex) === 6) {
                                    $r = hexdec(substr($hex, 0, 2));
                                    $g = hexdec(substr($hex, 2, 2));
                                    $b = hexdec(substr($hex, 4, 2));
                                    // Calculate brightness (YIQ)
                                    $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
                                    $text_color = $brightness > 128 ? '#000000' : '#ffffff';
                                } else {
                                    $text_color = '#000000';
                                }
                                ?>
                            <span class="badge" style="background-color: <?= $warna ?>; color: <?= $text_color ?>; border: 1px solid <?= $warna ?>;">
                                <i class="fas fa-palette me-1"></i><?= htmlspecialchars($dresscode['warna']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="confirm" value="1">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger btn-lg" onclick="return confirm('Apakah Anda yakin ingin menghapus dresscode ini?');">
                            <i class="fas fa-trash me-2"></i>Ya, Hapus Dresscode
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

