<?php
/**
 * Halaman Dokumentasi Booking Acara
 * Menggunakan sidebar layout yang konsisten
 */

// Definisikan BASE_URL jika belum ada
if (!defined('BASE_URL')) {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/hadrahin';
    define('BASE_URL', $base_url);
}

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

// Check permission (Admin only)
if ($_SESSION['peran'] != 'admin') {
    header('Location: index.php?error=permission_denied');
    exit;
}

// Get booking ID
$id_booking = $_GET['id'] ?? 0;

if (empty($id_booking) || !is_numeric($id_booking)) {
    header('Location: index.php?error=invalid_id');
    exit;
}

// Get booking data
try {
    $stmt = $pdo->prepare("SELECT * FROM booking_acara WHERE id_booking = ?");
    $stmt->execute([$id_booking]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        header('Location: index.php?error=not_found');
        exit;
    }
} catch (PDOException $e) {
    header('Location: index.php?error=' . urlencode($e->getMessage()));
    exit;
}

// Get existing documentation
try {
    $stmt = $pdo->prepare("SELECT * FROM dokumentasi_acara WHERE id_booking = ? ORDER BY created_at DESC");
    $stmt->execute([$id_booking]);
    $dokumentasi = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dokumentasi = [];
}

// Handle file upload
$upload_error = '';
$upload_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_dokumentasi'])) {
    if (!empty($_FILES['file_dokumentasi']['name'])) {
        $file = $_FILES['file_dokumentasi'];
        $keterangan = trim($_POST['keterangan'] ?? '');
        
        // Validate file
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/quicktime', 'video/x-msvideo'];
        $max_size = 50 * 1024 * 1024; // 50MB
        
        if (!in_array($file['type'], $allowed_types)) {
            $upload_error = 'Tipe file tidak diizinkan. Gunakan gambar (JPG, PNG, GIF) atau video (MP4).';
        } elseif ($file['size'] > $max_size) {
            $upload_error = 'File terlalu besar. Maksimal 50MB.';
        } else {
            // Create upload directory if not exists
            $upload_dir = '../../assets/uploads/dokumentasi/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'acara_' . $id_booking . '_' . time() . '.' . $ext;
            $target_path = $upload_dir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO dokumentasi_acara (id_booking, file_path, keterangan, user_record) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$id_booking, 'assets/uploads/dokumentasi/' . $filename, $keterangan ?: null, $_SESSION['user_id']]);
                    $upload_success = 'Dokumentasi berhasil diupload!';
                    
                    // Refresh dokumentasi list
                    $stmt = $pdo->prepare("SELECT * FROM dokumentasi_acara WHERE id_booking = ? ORDER BY created_at DESC");
                    $stmt->execute([$id_booking]);
                    $dokumentasi = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $upload_error = 'Gagal menyimpan ke database: ' . $e->getMessage();
                }
            } else {
                $upload_error = 'Gagal mengupload file.';
            }
        }
    } else {
        $upload_error = 'Pilih file terlebih dahulu.';
    }
}

// Handle delete dokumentasi
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['hapus'])) {
    $id_dokumentasi = $_GET['hapus'];
    
    try {
        $stmt = $pdo->prepare("SELECT file_path FROM dokumentasi_acara WHERE id_dokumentasi = ? AND id_booking = ?");
        $stmt->execute([$id_dokumentasi, $id_booking]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($doc) {
            // Delete file
            $file_path = '../../' . $doc['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM dokumentasi_acara WHERE id_dokumentasi = ?");
            $stmt->execute([$id_dokumentasi]);
            
            $upload_success = 'Dokumentasi berhasil dihapus!';
            
            // Refresh dokumentasi list
            $stmt = $pdo->prepare("SELECT * FROM dokumentasi_acara WHERE id_booking = ? ORDER BY created_at DESC");
            $stmt->execute([$id_booking]);
            $dokumentasi = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $upload_error = 'Gagal menghapus dokumentasi: ' . $e->getMessage();
    }
}

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

// Include header dengan sidebar
include '../../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2">Dokumentasi: <?= htmlspecialchars($booking['nama_acara']) ?></h1>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Kembali
        </a>
    </div>
</div>

<!-- Toast Messages -->
<?php if (!empty($upload_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($upload_error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($upload_success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($upload_success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Booking Info Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="mb-1"><?= htmlspecialchars($booking['nama_acara']) ?></h5>
                <p class="mb-0 text-muted">
                    <i class="fas fa-calendar me-2"></i><?= date('d F Y', strtotime($booking['tanggal_acara'])) ?>
                    <span class="mx-2">|</span>
                    <i class="fas fa-clock me-2"></i><?= date('H:i', strtotime($booking['jam_mulai'])) ?> WIB
                    <span class="mx-2">|</span>
                    <i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($booking['lokasi']) ?>
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-2 mt-md-0">
                <span class="badge <?= $status_badges[$booking['status']] ?> fs-6">
                    <?= $status_labels[$booking['status']] ?>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Upload Form -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-cloud-upload-alt me-2"></i>Upload Dokumentasi</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Pilih File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="file_dokumentasi" accept="image/*,video/*" required>
                        <small class="text-muted">Format: JPG, PNG, GIF, MP4. Maksimal 50MB.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <input type="text" class="form-control" name="keterangan" placeholder="Contoh: Foto saat opening ceremony">
                    </div>
                    <button type="submit" name="upload_dokumentasi" class="btn btn-primary w-100">
                        <i class="fas fa-upload me-2"></i>Upload
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Existing Documentation -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-images me-2"></i>Dokumentasi (<?= count($dokumentasi) ?> file)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($dokumentasi)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-photo-video fa-4x mb-3 d-block text-secondary"></i>
                        <p class="mb-0">Belum ada dokumentasi untuk acara ini.</p>
                        <small>Upload foto atau video untuk mendokumentasikan acara.</small>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($dokumentasi as $doc): ?>
                            <?php
                            $file_path = '../../' . $doc['file_path'];
                            $is_video = preg_match('/\.(mp4|mov|avi|webm)$/i', $doc['file_path']);
                            $ext = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
                            ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100">
                                    <?php if ($is_video): ?>
                                        <video controls class="card-img-top" style="height: 180px; object-fit: cover;">
                                            <source src="<?= htmlspecialchars($doc['file_path']) ?>" type="video/<?= $ext ?>">
                                            Browser tidak mendukung video.
                                        </video>
                                    <?php elseif (file_exists($file_path)): ?>
                                        <a href="<?= htmlspecialchars($doc['file_path']) ?>" target="_blank">
                                            <img src="<?= htmlspecialchars($doc['file_path']) ?>" class="card-img-top" alt="Dokumentasi" style="height: 180px; object-fit: cover;">
                                        </a>
                                    <?php else: ?>
                                        <div class="bg-secondary text-white d-flex align-items-center justify-content-center card-img-top" style="height: 180px;">
                                            <i class="fas fa-file fa-3x"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-body p-2">
                                        <p class="card-text small mb-1 text-truncate" title="<?= htmlspecialchars($doc['keterangan'] ?? '') ?>">
                                            <?= htmlspecialchars($doc['keterangan'] ?? 'Tanpa keterangan') ?>
                                        </p>
                                        <small class="text-muted d-block"><?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?></small>
                                    </div>
                                    <?php if ($_SESSION['peran'] == 'admin'): ?>
                                        <div class="card-footer p-2 bg-transparent">
                                            <a href="dokumentasi.php?id=<?= $id_booking ?>&hapus=<?= $doc['id_dokumentasi'] ?>" 
                                               class="btn btn-sm btn-outline-danger w-100"
                                               onclick="return confirm('Yakin hapus dokumentasi ini?')">
                                                <i class="fas fa-trash me-1"></i>Hapus
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

