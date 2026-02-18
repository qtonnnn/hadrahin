<?php
/**
 * Hapus User - Konfirmasi Hapus
 */

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

$page_title = "Hapus User";

// Get user ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php?msg=error');
    exit;
}

// Validasi: Jangan hapus diri sendiri
if ($id == $_SESSION['user_id']) {
    header('Location: index.php?msg=gagal&reason=self');
    exit;
}

// Fetch user data untuk konfirmasi
$stmt = $pdo->prepare("SELECT * FROM user WHERE id_user = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: index.php?msg=error');
    exit;
}

// Handle delete confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    // Hard delete: hapus permanen dari database
    $stmt = $pdo->prepare("DELETE FROM user WHERE id_user = ?");
    $stmt->execute([$id]);
    header('Location: index.php?msg=hapus_sukes');
    exit;
}

// Include header dengan sidebar
include '../../includes/header.php';
?>

<!-- Delete Confirmation Card -->
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white py-3">
                <div class="d-flex align-items-center">
                    <a href="index.php" class="btn btn-outline-light me-3">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h4 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i><?= $page_title ?></h4>
                </div>
            </div>
            <div class="card-body text-center">
                <div class="mb-4">
                    <div class="bg-danger text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-exclamation-triangle fa-3x"></i>
                    </div>
                    <h4 class="text-danger">Peringatan!</h4>
                    <p class="text-muted">Apakah Anda yakin ingin menghapus user berikut?</p>
                </div>

                <div class="card bg-light mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; font-size: 20px;">
                                <?= strtoupper(substr($user['username'], 0, 1)) ?>
                            </div>
                            <div class="text-start">
                                <strong><?= htmlspecialchars($user['nama_lengkap']) ?></strong><br>
                                <small class="text-muted">@<?= htmlspecialchars($user['username']) ?></small>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="badge <?= $user['peran'] === 'admin' ? 'bg-danger' : 'bg-primary' ?>">
                                <?= htmlspecialchars(ucfirst($user['peran'])) ?>
                            </span>
                            <span class="badge <?= $user['status_aktif'] ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $user['status_aktif'] ? 'Aktif' : 'Tidak Aktif' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Perhatian:</strong> Data yang terkait dengan user ini mungkin juga akan terhapus.
                </div>

                <form method="POST">
                    <input type="hidden" name="confirm" value="1">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger btn-lg" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?');">
                            <i class="fas fa-trash me-2"></i>Ya, Hapus User
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

