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
$query = "SELECT a.*, a.keterangan,
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
    $query_pengguna = "SELECT ap.id_alat_pengguna, ap.id_alat, ap.id_user, ap.tanggal_diberikan, ap.status, ap.keterangan, ap.created_at, ap.updated_at,
                              u.username, u.no_hp, u.peran, u.status_aktif, u.nama_lengkap
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
        <form method="GET" class="d-flex search-form">
            <div class="input-group" style="max-width: 400px;">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" name="search" class="form-control" 
                       placeholder="Cari alat..." 
                       value="<?= htmlspecialchars($search) ?>"
                       maxlength="100">
                <?php if ($search): ?>
                    <a href="index.php" class="btn btn-outline-secondary">Reset</a>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">Cari</button>
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
                                            <?php $aktif_users_array = array_values($aktif_users); ?>
                                            <?php $total_aktif = count($aktif_users_array); ?>
                                            <?php $display_users = array_slice($aktif_users_array, 0, 3); ?>
                                            <div class="d-flex flex-wrap gap-1">
                                                <?php foreach ($display_users as $pengguna): ?>
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-user me-1"></i><?= htmlspecialchars($pengguna['nama_lengkap']) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                                <?php if ($total_aktif > 3): ?>
                                                    <span class="badge bg-warning text-dark" style="cursor: pointer;"
                                                          onclick="showDetailModal(<?= $alat['id_alat'] ?>, '<?= htmlspecialchars($alat['nama_alat'], ENT_QUOTES) ?>', <?= $alat['jumlah_baik'] ?>, <?= $alat['jumlah_rusak'] ?>, '<?= htmlspecialchars($alat['keterangan'] ?? '', ENT_QUOTES) ?>')"
                                                          title="Lihat semua pengguna aktif">
                                                        <i class="fas fa-plus-circle me-1"></i>+<?= $total_aktif - 3 ?> lainnya
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">-</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-info" title="Detail"
                                            onclick="showDetailModal(<?= $alat['id_alat'] ?>, '<?= htmlspecialchars($alat['nama_alat'], ENT_QUOTES) ?>', <?= $alat['jumlah_baik'] ?>, <?= $alat['jumlah_rusak'] ?>, '<?= htmlspecialchars($alat['keterangan'] ?? '', ENT_QUOTES) ?>')">
                                        <i class="fas fa-eye"></i>
                                    </button>
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
                                        <?php $aktif_users_array = array_values($aktif_users); ?>
                                        <?php $total_aktif = count($aktif_users_array); ?>
                                        <?php $display_users = array_slice($aktif_users_array, 0, 3); ?>
                                        <div class="mb-2">
                                            <small class="text-muted d-block mb-1">Pengguna:</small>
                                            <div class="d-flex flex-wrap gap-1">
                                                <?php foreach ($display_users as $pengguna): ?>
                                                    <span class="badge bg-info">
                                                        <?= htmlspecialchars($pengguna['nama_lengkap']) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                                <?php if ($total_aktif > 3): ?>
                                                    <span class="badge bg-warning text-dark" style="cursor: pointer;"
                                                          onclick="showDetailModal(<?= $alat['id_alat'] ?>, '<?= htmlspecialchars($alat['nama_alat'], ENT_QUOTES) ?>', <?= $alat['jumlah_baik'] ?>, <?= $alat['jumlah_rusak'] ?>, '<?= htmlspecialchars($alat['keterangan'] ?? '', ENT_QUOTES) ?>')"
                                                          title="Lihat semua pengguna aktif">
                                                        <i class="fas fa-plus-circle me-1"></i>+<?= $total_aktif - 3 ?> lainnya
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <div class="d-flex gap-2 mb-2">
                                    <button type="button" class="btn btn-outline-info btn-sm flex-grow-1"
                                            onclick="showDetailModal(<?= $alat['id_alat'] ?>, '<?= htmlspecialchars($alat['nama_alat'], ENT_QUOTES) ?>', <?= $alat['jumlah_baik'] ?>, <?= $alat['jumlah_rusak'] ?>, '<?= htmlspecialchars($alat['keterangan'] ?? '', ENT_QUOTES) ?>')">
                                        <i class="fas fa-eye me-1"></i>Detail
                                    </button>
                                </div>
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

<!-- Statistics Cards - Moved to Bottom -->
<div class="row mt-4">
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon">
                <i class="fas fa-music"></i>
            </div>
            <div class="stat-value"><?= $total_alat ?></div>
            <div class="stat-label">Jenis Alat</div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-success">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-value"><?= $total_baik ?></div>
            <div class="stat-label">Kondisi Baik</div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-danger">
            <div class="stat-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-value"><?= $total_rusak ?></div>
            <div class="stat-label">Kondisi Rusak</div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #6f42c1, #6610f2);">
            <div class="stat-icon">
                <i class="fas fa-chart-pie"></i>
            </div>
            <div class="stat-value"><?= $persen_baik ?>%</div>
            <div class="stat-label">Kondisi Baik</div>
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

<!-- Modal Detail Alat -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="detailModalLabel">
                    <i class="fas fa-music me-2"></i>Detail Alat
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Nama Alat -->
                <div class="text-center mb-4">
                    <h4 class="text-primary mb-0" id="detailNamaAlat">-</h4>
                </div>

                <!-- Status Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <div class="text-primary mb-2">
                                    <i class="fas fa-music fa-2x"></i>
                                </div>
                                <h5 class="card-title mb-1" id="detailTotal">-</h5>
                                <p class="card-text text-muted small">Total Unit</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <div class="text-success mb-2">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                                <h5 class="card-title mb-1 text-success" id="detailBaik">-</h5>
                                <p class="card-text text-muted small">Kondisi Baik</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card border-danger">
                            <div class="card-body text-center">
                                <div class="text-danger mb-2">
                                    <i class="fas fa-times-circle fa-2x"></i>
                                </div>
                                <h5 class="card-title mb-1 text-danger" id="detailRusak">-</h5>
                                <p class="card-text text-muted small">Kondisi Rusak</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Keterangan -->
                <div class="mb-4">
                    <h6 class="text-primary mb-2">
                        <i class="fas fa-sticky-note me-1"></i>Keterangan
                    </h6>
                    <div class="card">
                        <div class="card-body">
                            <p class="mb-0" id="detailKeterangan">-</p>
                        </div>
                    </div>
                </div>

                <!-- Pengguna Aktif -->
                <div class="mb-4">
                    <h6 class="text-primary mb-2">
                        <i class="fas fa-users me-1"></i>Pengguna Aktif
                        <span class="badge bg-info ms-2" id="detailTotalPenggunaAktif">0</span>
                    </h6>
                    <div id="detailPenggunaAktif">
                        <!-- Data akan diisi oleh JavaScript -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Pengguna -->
<div class="modal fade" id="userDetailModal" tabindex="-1" aria-labelledby="userDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="userDetailModalLabel">
                    <i class="fas fa-user-circle me-2"></i>Detail Pengguna
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Profile Header -->
                <div class="user-profile-header bg-info bg-gradient text-white p-4 text-center">
                    <div class="user-avatar-xl bg-white text-info rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3 shadow" id="userDetailAvatar" style="width: 100px; height: 100px; font-size: 40px;">
                        -
                    </div>
                    <h4 class="mb-1 fw-bold" id="userDetailNama">-</h4>
                    <span class="badge bg-white text-info fs-6" id="userDetailPeran">-</span>
                </div>
                
                <!-- User Info Cards -->
                <div class="p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-icon bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                                    <i class="fas fa-user fa-lg"></i>
                                </div>
                                <div class="info-content">
                                    <small class="text-muted text-uppercase small fw-500">Username</small>
                                    <div class="fw-bold text-dark" id="userDetailUsername">-</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-icon bg-success bg-opacity-10 text-success rounded-3 p-3">
                                    <i class="fas fa-phone fa-lg"></i>
                                </div>
                                <div class="info-content">
                                    <small class="text-muted text-uppercase small fw-500">No HP</small>
                                    <div class="fw-bold text-dark" id="userDetailNoHp">-</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-icon bg-warning bg-opacity-10 text-warning rounded-3 p-3">
                                    <i class="fas fa-toggle-on fa-lg"></i>
                                </div>
                                <div class="info-content">
                                    <small class="text-muted text-uppercase small fw-500">Status</small>
                                    <div class="mt-1" id="userDetailStatus">-</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-icon bg-danger bg-opacity-10 text-danger rounded-3 p-3">
                                    <i class="fas fa-calendar-alt fa-lg"></i>
                                </div>
                                <div class="info-content">
                                    <small class="text-muted text-uppercase small fw-500">Tanggal Diberikan</small>
                                    <div class="fw-bold text-dark" id="userDetailTanggal">-</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal Detail Pengguna Styles */
.user-profile-header {
    position: relative;
    overflow: hidden;
}

.user-profile-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.5;
}

.info-card {
    background: #fff;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.info-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.info-content {
    flex-grow: 1;
    min-width: 0;
}

.info-content small {
    letter-spacing: 0.5px;
}

.fw-500 {
    font-weight: 500;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .info-card {
        padding: 12px;
    }
    
    .info-icon {
        width: 44px;
        height: 44px;
    }
    
    #userDetailAvatar {
        width: 80px !important;
        height: 80px !important;
        font-size: 32px !important;
    }
    
    .user-profile-header {
        padding: 2rem 1rem !important;
    }
}
</style>

<script>
// Fungsi helper untuk escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Data pengguna global
const penggunaData = <?php echo json_encode($pengguna_map); ?>;

// Debug: log data pengguna
console.log('Data pengguna:', penggunaData);

function showDetailModal(idAlat, namaAlat, jumlahBaik, jumlahRusak, keterangan) {
    // Set data dasar
    document.getElementById('detailNamaAlat').textContent = namaAlat;
    document.getElementById('detailTotal').textContent = jumlahBaik + jumlahRusak;
    document.getElementById('detailBaik').textContent = jumlahBaik;
    document.getElementById('detailRusak').textContent = jumlahRusak;
    document.getElementById('detailKeterangan').textContent = keterangan || 'Tidak ada keterangan';

    // Set pengguna aktif
    const penggunaAktifContainer = document.getElementById('detailPenggunaAktif');
    const penggunaList = penggunaData[idAlat] || [];
    const aktifUsers = penggunaList.filter(p => p.status === 'aktif');

    // Update total pengguna aktif
    document.getElementById('detailTotalPenggunaAktif').textContent = aktifUsers.length;

    if (aktifUsers.length > 0) {
        let html = '<div class="d-flex flex-wrap gap-2">';
        aktifUsers.forEach(user => {
            const userId = user.id_user || user.id_user;
            html += `
                <span class="badge bg-info p-2" style="cursor: pointer;" 
                      onclick="showUserDetailModalById(${userId}, ${user.id_alat || idAlat})"
                      title="Klik untuk lihat detail pengguna">
                    <i class="fas fa-user me-1"></i>${escapeHtml(user.nama_lengkap || 'User')}
                </span>
            `;
        });
        html += '</div>';
        penggunaAktifContainer.innerHTML = html;
    } else {
        penggunaAktifContainer.innerHTML = '<p class="text-muted mb-0">Tidak ada pengguna aktif</p>';
    }

    // Tampilkan modal
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

// Fungsi untuk menampilkan modal detail pengguna berdasarkan ID
function showUserDetailModalById(userId, alatId) {
    // Cari data pengguna dari global data
    const penggunaList = penggunaData[alatId] || [];
    const user = penggunaList.find(p => p.id_user === parseInt(userId));
    
    if (!user) {
        console.error('User tidak ditemukan:', userId);
        return;
    }

    // Set data pengguna ke modal
    document.getElementById('userDetailAvatar').textContent = user.nama_lengkap ? user.nama_lengkap.charAt(0).toUpperCase() : '?';
    document.getElementById('userDetailNama').textContent = user.nama_lengkap || '-';
    document.getElementById('userDetailUsername').textContent = user.username || '-';
    document.getElementById('userDetailNoHp').textContent = user.no_hp || '-';

    // Set status badge
    const statusEl = document.getElementById('userDetailStatus');
    if (user.status === 'aktif') {
        statusEl.innerHTML = '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Aktif</span>';
    } else {
        statusEl.innerHTML = '<span class="badge bg-secondary"><i class="fas fa-times-circle me-1"></i>Dinonaktifkan</span>';
    }

    // Set peran badge
    const peranBadge = document.getElementById('userDetailPeran');
    const peranClass = user.peran === 'admin' ? 'bg-danger' : 'bg-primary';
    peranBadge.className = `badge ${peranClass}`;
    peranBadge.textContent = (user.peran || 'anggota').charAt(0).toUpperCase() + (user.peran || 'anggota').slice(1);

    // Format tanggal
    const tanggal = user.tanggal_diberikan ? new Date(user.tanggal_diberikan).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    }) : '-';
    document.getElementById('userDetailTanggal').textContent = tanggal;

    // Tampilkan modal
    new bootstrap.Modal(document.getElementById('userDetailModal')).show();
}
</script>

<?php include '../../includes/footer.php'; ?>

