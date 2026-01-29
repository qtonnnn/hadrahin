<?php
/**
 * Inventaris Alat - Halaman Daftar Alat
 * Menggunakan sidebar layout yang konsisten dengan modul lain
 */

// Definisikan BASE_URL jika belum ada
if (!defined('BASE_URL')) {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/hadrahin';
    define('BASE_URL', $base_url);
}

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

$page_title = "Inventaris Alat";

// Search dengan Validasi
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search = htmlspecialchars(strip_tags($search), ENT_QUOTES, 'UTF-8');
if (strlen($search) > 100) $search = substr($search, 0, 100);

// Build query dengan search
$where_clause = '';
$params = [];
if ($search) {
    $where_clause = "WHERE a.nama_alat LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param];
}

// Get all alat with their pengguna
$query = "SELECT a.*, 
          (SELECT COUNT(*) FROM alat_pengguna WHERE id_alat = a.id_alat AND status = 'aktif') as jumlah_pengguna
          FROM alat a 
          $where_clause
          ORDER BY a.nama_alat ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$alat_list = $stmt->fetchAll();

// Get pengguna for each alat
$pengguna_map = [];
foreach ($alat_list as $alat) {
    $query_pengguna = "SELECT ap.*, u.nama_lengkap 
                       FROM alat_pengguna ap 
                       JOIN user u ON ap.id_user = u.id_user 
                       WHERE ap.id_alat = :id_alat 
                       ORDER BY ap.status DESC, u.nama_lengkap ASC";
    $stmt_pengguna = $pdo->prepare($query_pengguna);
    $stmt_pengguna->execute(['id_alat' => $alat['id_alat']]);
    $pengguna_map[$alat['id_alat']] = $stmt_pengguna->fetchAll();
}

// Calculate statistics
$total_alat = count($alat_list);
$total_baik = array_sum(array_column($alat_list, 'jumlah_baik'));
$total_rusak = array_sum(array_column($alat_list, 'jumlah_rusak'));
$total_semua = $total_baik + $total_rusak;
$persen_baik = $total_semua > 0 ? round(($total_baik / $total_semua) * 100) : 0;

// Include header dengan sidebar
include '../../includes/header.php';
?>

<!-- Floating Action Button -->
<a href="tambah.php" class="floating-btn" title="Tambah Alat">
    <i class="fas fa-plus"></i>
</a>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card stat-primary">
            <div class="stat-icon">
                <i class="fas fa-music"></i>
            </div>
            <div class="stat-value"><?= $total_alat ?></div>
            <div class="stat-label">Jenis Alat</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card stat-success">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-value"><?= $total_baik ?></div>
            <div class="stat-label">Kondisi Baik</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card stat-danger">
            <div class="stat-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-value"><?= $total_rusak ?></div>
            <div class="stat-label">Kondisi Rusak</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #6f42c1, #6610f2);">
            <div class="stat-icon">
                <i class="fas fa-chart-pie"></i>
            </div>
            <div class="stat-value"><?= $persen_baik ?>%</div>
            <div class="stat-label">Kondisi Baik</div>
        </div>
    </div>
</div>

<!-- Toast Messages -->
<?php if (isset($_GET['msg'])): ?>
    <?php
    $toastClass = '';
    $toastMessage = '';
    
    switch ($_GET['msg']) {
        case 'tambah_sukes':
            $toastClass = 'bg-success';
            $toastMessage = 'Alat baru berhasil ditambahkan!';
            break;
        case 'edit_sukes':
            $toastClass = 'bg-success';
            $toastMessage = 'Data alat berhasil diperbarui!';
            break;
        case 'hapus_sukes':
            $toastClass = 'bg-success';
            $toastMessage = 'Alat berhasil dihapus!';
            break;
        case 'error':
            $toastClass = 'bg-danger';
            $toastMessage = 'Terjadi kesalahan!';
            break;
    }
    
    if ($toastMessage):
    ?>
    <div class="position-fixed top-0 start-50 translate-middle-x mt-5" style="z-index: 9999">
        <div id="liveToast" class="toast align-items-center text-white <?= $toastClass ?> border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check-circle me-2"></i><?= $toastMessage ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var toastEl = document.getElementById('liveToast');
            var toast = new bootstrap.Toast(toastEl, { delay: 3000 });
            toast.show();
            
            setTimeout(function() {
                window.history.replaceState({}, document.title, window.location.pathname);
            }, 3100);
        });
    </script>
    <?php endif; ?>
<?php endif; ?>

<!-- Search Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-end search-form">
            <div class="input-group" style="max-width: 300px;">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" name="search" class="form-control" 
                       placeholder="Cari alat..." 
                       value="<?= htmlspecialchars($search) ?>"
                       maxlength="100">
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i>Cari
                </button>
                <?php if ($search): ?>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Alat List - Card View for Mobile, Table for Desktop -->
<div class="card">
    <div class="card-header py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list me-1"></i> Daftar Inventaris Alat
            </h6>
        </div>
    </div>
    <div class="card-body p-0">
        <!-- Desktop Table View -->
        <div class="d-none d-md-block">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="50" class="text-center">No</th>
                        <th>Nama Alat</th>
                        <th width="100" class="text-center">Baik</th>
                        <th width="100" class="text-center">Rusak</th>
                        <th>Pengguna (Aktif)</th>
                        <th width="120" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($alat_list)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-music fa-3x mb-3 d-block text-secondary"></i>
                                <?= $search ? 'Tidak ada alat yang ditemukan dengan kata kunci "' . htmlspecialchars($search) . '"' : 'Belum ada data alat' ?>
                                <div class="mt-3">
                                    <?php if ($search): ?>
                                        <a href="index.php" class="btn btn-outline-secondary me-2">
                                            <i class="fas fa-arrow-left me-1"></i>Kembali
                                        </a>
                                    <?php endif; ?>
                                    <a href="tambah.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus me-1"></i> Tambah Alat
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($alat_list as $i => $alat): ?>
                            <tr>
                                <td class="text-center text-muted"><?= $i + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px; font-size: 16px;">
                                            <i class="fas fa-music"></i>
                                        </div>
                                        <span class="fw-500"><?= htmlspecialchars($alat['nama_alat']) ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success"><?= $alat['jumlah_baik'] ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($alat['jumlah_rusak'] > 0): ?>
                                        <span class="badge bg-danger"><?= $alat['jumlah_rusak'] ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($pengguna_map[$alat['id_alat']])): ?>
                                        <?php $aktif_users = array_filter($pengguna_map[$alat['id_alat']], function($p) { return $p['status'] == 'aktif'; }); ?>
                                        <?php if (!empty($aktif_users)): ?>
                                            <div class="d-flex flex-wrap gap-1">
                                                <?php foreach ($aktif_users as $pengguna): ?>
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-user me-1"></i><?= htmlspecialchars($pengguna['nama_lengkap']) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">-</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="edit.php?id=<?= $alat['id_alat'] ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="hapus.php?id=<?= $alat['id_alat'] ?>" class="btn btn-sm btn-outline-danger" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="d-md-none">
            <?php if (empty($alat_list)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-music fa-3x mb-3 d-block text-secondary"></i>
                    Belum ada data alat
                    <div class="mt-3">
                        <a href="tambah.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> Tambah Alat
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($alat_list as $i => $alat): ?>
                    <div class="alat-card p-3 border-bottom">
                        <div class="d-flex align-items-start">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; font-size: 20px;">
                                <i class="fas fa-music"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="mb-0"><?= htmlspecialchars($alat['nama_alat']) ?></h5>
                                </div>
                                <div class="d-flex gap-2 mb-2">
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle me-1"></i>Baik: <?= $alat['jumlah_baik'] ?>
                                    </span>
                                    <span class="badge bg-danger">
                                        <i class="fas fa-times-circle me-1"></i>Rusak: <?= $alat['jumlah_rusak'] ?>
                                    </span>
                                </div>
                                <?php if (!empty($pengguna_map[$alat['id_alat']])): ?>
                                    <?php $aktif_users = array_filter($pengguna_map[$alat['id_alat']], function($p) { return $p['status'] == 'aktif'; }); ?>
                                    <?php if (!empty($aktif_users)): ?>
                                        <div class="mb-2">
                                            <small class="text-muted d-block mb-1">Pengguna:</small>
                                            <div class="d-flex flex-wrap gap-1">
                                                <?php foreach ($aktif_users as $pengguna): ?>
                                                    <span class="badge bg-info">
                                                        <?= htmlspecialchars($pengguna['nama_lengkap']) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <div class="d-flex gap-2">
                                    <a href="edit.php?id=<?= $alat['id_alat'] ?>" class="btn btn-outline-warning btn-sm flex-grow-1">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </a>
                                    <a href="hapus.php?id=<?= $alat['id_alat'] ?>" class="btn btn-outline-danger btn-sm flex-grow-1">
                                        <i class="fas fa-trash me-1"></i>Hapus
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Legend -->
<div class="card mt-4">
    <div class="card-body">
        <h6 class="font-weight-bold mb-2">Keterangan Status Pengguna:</h6>
        <div class="d-flex flex-wrap gap-3">
            <span><span class="badge bg-info me-1">Aktif</span> Sedang digunakan</span>
            <span><span class="badge bg-secondary me-1">Dikembalikan</span> Sudah dikembalikan</span>
        </div>
    </div>
</div>

<style>
.alat-card {
    background: #fff;
}

.alat-card:last-child {
    border-bottom: none !important;
}
</style>

<?php include '../../includes/footer.php'; ?>

