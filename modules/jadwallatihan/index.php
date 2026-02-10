<?php
/**
 * Jadwal Latihan - Halaman Daftar Jadwal
 * Menggunakan sidebar layout yang konsisten dengan dashboard admin
 */

// Definisikan BASE_URL jika belum ada
if (!defined('BASE_URL')) {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/hadrahin';
    define('BASE_URL', $base_url);
}

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
require_once '../../includes/cache.php';

$page_title = "Jadwal Latihan";

// Get current user role
$user_peran = $_SESSION['peran'] ?? 'anggota';
$user_id = $_SESSION['user_id'] ?? 0;

// AUTO-CANCEL PAST SCHEDULES: Update schedules that have passed their date to 'dibatalkan'
// This runs automatically when the page loads
try {
    $stmt = $pdo->prepare("UPDATE jadwal_latihan 
                           SET status = 'dibatalkan', 
                               catatan = CONCAT(COALESCE(catatan, ''), ' [Otomatis dibatalkan: tanggal sudah terlewatkan]'),
                               updated_at = NOW()
                           WHERE status = 'direncanakan' 
                           AND tanggal < CURDATE()");
    $stmt->execute();
    $canceled_count = $stmt->rowCount();
    
    // Clear cache if any schedules were canceled
    if ($canceled_count > 0) {
        Cache::delete('jadwal_stats');
        Cache::delete('jadwal_max_date');
        Cache::delete('absen_max_date');
    }
} catch (Exception $e) {
    error_log("Error auto-canceling past schedules: " . $e->getMessage());
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Search & Filter dengan Validasi
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search = htmlspecialchars(strip_tags($search), ENT_QUOTES, 'UTF-8');
if (strlen($search) > 100) $search = substr($search, 0, 100);

// Validasi status_filter dengan whitelist
$allowed_status = ['direncanakan', 'selesai', 'dibatalkan', ''];
$status_filter = isset($_GET['status']) && in_array($_GET['status'], $allowed_status) 
    ? $_GET['status'] : '';

// Fungsi validasi format tanggal YYYY-MM-DD
function validateDateFormat($date, $format = 'Y-m-d') {
    if (empty($date)) return false;
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Validasi tanggal_mulai
$tanggal_mulai_default = date('Y-m-01');
if (isset($_GET['tanggal_mulai']) && !empty($_GET['tanggal_mulai'])) {
    $tanggal_mulai = validateDateFormat($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : $tanggal_mulai_default;
} else {
    $tanggal_mulai = $tanggal_mulai_default;
}

// Cari tanggal terbaru di jadwal_latihan untuk default end date dengan caching
$max_date_result = Cache::remember('jadwal_max_date', function() use ($pdo) {
    $stmt = $pdo->query("SELECT MAX(tanggal) as max_date FROM jadwal_latihan");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}, 600); // Cache 10 menit

// Validasi tanggal_selesai
$tanggal_selesai_default = $max_date_result['max_date'] ?? date('Y-m-d');
if (isset($_GET['tanggal_selesai']) && !empty($_GET['tanggal_selesai'])) {
    $tanggal_selesai = validateDateFormat($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : $tanggal_selesai_default;
} else {
    $tanggal_selesai = $tanggal_selesai_default;
}

// Validasi rentang tanggal (tanggal_mulai tidak boleh lebih besar dari tanggal_selesai)
if ($tanggal_mulai > $tanggal_selesai) {
    $temp = $tanggal_mulai;
    $tanggal_mulai = $tanggal_selesai;
    $tanggal_selesai = $temp;
}

// Build WHERE clause
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(lokasi LIKE ? OR catatan LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

// Filter periode tanggal
if (!empty($tanggal_mulai) && !empty($tanggal_selesai)) {
    $where_conditions[] = "tanggal BETWEEN ? AND ?";
    $params[] = $tanggal_mulai;
    $params[] = $tanggal_selesai;
} elseif (!empty($tanggal_mulai)) {
    $where_conditions[] = "tanggal >= ?";
    $params[] = $tanggal_mulai;
} elseif (!empty($tanggal_selesai)) {
    $where_conditions[] = "tanggal <= ?";
    $params[] = $tanggal_selesai;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Count total jadwal
$count_sql = "SELECT COUNT(*) FROM jadwal_latihan $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch jadwal
$sql = "SELECT * FROM jadwal_latihan $where_clause ORDER BY tanggal DESC, jam_mulai DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jadwals = $stmt->fetchAll();

// Handle delete
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];

    // Check permission - only admin can delete
    if ($user_peran === 'admin') {
        $stmt = $pdo->prepare("DELETE FROM jadwal_latihan WHERE id_jadwal = ?");
        $stmt->execute([$id]);

        // Clear cache after delete
        Cache::delete('jadwal_stats');
        Cache::delete('jadwal_max_date');

        header('Location: index.php?msg=hapus_sukes');
        exit;
    } else {
        header('Location: index.php?msg=error');
        exit;
    }
}

// Include header dengan sidebar
include '../../includes/header.php';
?>

<!-- Floating Action Button - Only for Admin & Pembina -->
<?php if (in_array($user_peran, ['admin', 'pembina'])): ?>
    <a href="tambah.php" class="floating-btn" title="Tambah Jadwal">
        <i class="fas fa-plus"></i>
    </a>
<?php endif; ?>

<!-- Toast Messages -->
<?php if (isset($_GET['msg'])): ?>
    <?php
    $toastClass = '';
    $toastMessage = '';
    
    if ($_GET['msg'] === 'tambah_sukes') {
        $toastClass = 'bg-success';
        $toastMessage = 'Jadwal latihan berhasil ditambahkan!';
    } elseif ($_GET['msg'] === 'edit_sukes') {
        $toastClass = 'bg-success';
        $toastMessage = 'Data jadwal latihan berhasil diperbarui!';
    } elseif ($_GET['msg'] === 'hapus_sukes') {
        $toastClass = 'bg-success';
        $toastMessage = 'Jadwal latihan berhasil dihapus!';
    } elseif ($_GET['msg'] === 'status_sukes') {
        $toastClass = 'bg-success';
        $toastMessage = 'Status jadwal berhasil diperbarui!';
    } elseif ($_GET['msg'] === 'error') {
        $toastClass = 'bg-danger';
        $toastMessage = 'Terjadi kesalahan!';
    } elseif ($_GET['msg'] === 'access_denied') {
        $toastClass = 'bg-warning';
        $toastMessage = 'Anda tidak memiliki akses untuk operasi ini!';
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

<!-- Search & Filter Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="">
            <div class="row g-2">
                <!-- Search -->
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Cari lokasi atau catatan..." 
                               value="<?= htmlspecialchars($search) ?>"
                               maxlength="100">
                    </div>
                </div>
                
                <!-- Status Filter -->
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="direncanakan" <?= $status_filter === 'direncanakan' ? 'selected' : '' ?>>Direncanakan</option>
                        <option value="selesai" <?= $status_filter === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                        <option value="dibatalkan" <?= $status_filter === 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                    </select>
                </div>
                
                <!-- Tanggal Mulai -->
                <div class="col-md-2">
                    <input type="date" name="tanggal_mulai" class="form-control" 
                           value="<?= htmlspecialchars($tanggal_mulai) ?>" title="Dari Tanggal">
                </div>
                
                <!-- Tanggal Selesai -->
                <div class="col-md-3">
                    <input type="date" name="tanggal_selesai" class="form-control" 
                           value="<?= htmlspecialchars($tanggal_selesai) ?>" title="Sampai Tanggal">
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-submit form when any filter changes
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const inputs = form.querySelectorAll('input[type="text"], input[type="date"], select');
    
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            form.submit();
        });
    });
});
</script>

<!-- Jadwal List - Card View for Mobile, Table for Desktop -->
<div class="card">
    <div class="card-body p-0">
        <!-- Desktop Table View -->
        <div class="d-none d-md-block">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="50" class="text-center">No</th>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Lokasi</th>
                        <th>Catatan</th>
                        <th>Status</th>
                        <th width="150" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($jadwals)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-calendar-times fa-3x mb-3 d-block text-secondary"></i>
                                <?= $search || $status_filter ? 'Tidak ada jadwal yang ditemukan.' : 'Belum ada data jadwal latihan.' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($jadwals as $i => $jadwal): ?>
                            <?php
                            $tanggal = new DateTime($jadwal['tanggal']);
                            $is_past = $tanggal < new DateTime('today');
                            $status_class = match($jadwal['status']) {
                                'selesai' => 'bg-success',
                                'dibatalkan' => 'bg-danger',
                                default => 'bg-warning text-dark'
                            };
                            ?>
                            <tr>
                                <td class="text-center text-muted"><?= $offset + $i + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px; font-size: 12px;">
                                            <?= $tanggal->format('d') ?>
                                            <span class="d-block" style="font-size: 9px;"><?= $tanggal->format('M') ?></span>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= $tanggal->format('l') ?></div>
                                            <small class="text-muted"><?= $tanggal->format('Y') ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <i class="far fa-clock text-muted me-1"></i>
                                    <?= date('H:i', strtotime($jadwal['jam_mulai'])) ?>
                                </td>
                                <td>
                                    <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                    <?= htmlspecialchars($jadwal['lokasi']) ?>
                                </td>
                                <td>
                                    <?php if (!empty($jadwal['catatan'])): ?>
                                        <span title="<?= htmlspecialchars($jadwal['catatan']) ?>">
                                            <?= htmlspecialchars(substr($jadwal['catatan'], 0, 30)) ?>
                                            <?= strlen($jadwal['catatan']) > 30 ? '...' : '' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $status_class ?>">
                                        <?= ucfirst($jadwal['status']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if (in_array($user_peran, ['admin', 'pembina'])): ?>
                                        <a href="edit.php?id=<?= $jadwal['id_jadwal'] ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php
                                        // Check if attendance should be disabled
                                        $jadwal_date = new DateTime($jadwal['tanggal']);
                                        $today = new DateTime('today');
                                        $is_before_date = $jadwal_date > $today;
                                        
                                        if (in_array($jadwal['status'], ['dibatalkan', 'selesai']) || $is_before_date): ?>
                                            <?php
                                            $tooltip_text = '';
                                            if ($is_before_date) {
                                                $tooltip_text = 'Absensi belum dapat dilakukan karena tanggal latihan belum tiba';
                                            } elseif ($jadwal['status'] === 'dibatalkan') {
                                                $tooltip_text = 'Absensi tidak dapat dilakukan karena jadwal telah dibatalkan';
                                            } elseif ($jadwal['status'] === 'selesai') {
                                                $tooltip_text = 'Absensi sudah dilakukan untuk jadwal ini';
                                            }
                                            ?>
                                            <button class="btn btn-sm btn-outline-secondary" disabled title="<?= $tooltip_text ?>">
                                                <i class="fas fa-clipboard-check"></i>
                                            </button>
                                        <?php else: ?>
                                            <a href="../absenlatihan/absen.php?id=<?= $jadwal['id_jadwal'] ?>" class="btn btn-sm btn-outline-primary" title="Absensi">
                                                <i class="fas fa-clipboard-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($user_peran === 'admin'): ?>
                                            <a href="hapus.php?id=<?= $jadwal['id_jadwal'] ?>" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="detail.php?id=<?= $jadwal['id_jadwal'] ?>" class="btn btn-sm btn-outline-secondary" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="jadwal-card-list d-md-none">
            <?php if (empty($jadwals)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-calendar-times fa-3x mb-3 d-block text-secondary"></i>
                    <?= $search || $status_filter ? 'Tidak ada jadwal yang ditemukan.' : 'Belum ada data jadwal latihan.' ?>
                </div>
            <?php else: ?>
                <?php foreach ($jadwals as $i => $jadwal): ?>
                    <?php
                    $tanggal = new DateTime($jadwal['tanggal']);
                    $status_class = match($jadwal['status']) {
                        'selesai' => 'bg-success',
                        'dibatalkan' => 'bg-danger',
                        default => 'bg-warning text-dark'
                    };
                    ?>
                    <div class="jadwal-card">
                        <div class="jadwal-card-header">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                                    <div class="text-center" style="font-size: 14px; line-height: 1.2;">
                                        <?= $tanggal->format('d') ?>
                                        <div style="font-size: 10px;"><?= $tanggal->format('M') ?></div>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="mb-0"><?= $tanggal->format('l') ?></h5>
                                    <small class="text-muted"><?= $tanggal->format('Y') ?></small>
                                </div>
                            </div>
                            <div class="ms-auto">
                                <span class="badge <?= $status_class ?>">
                                    <?= ucfirst($jadwal['status']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="jadwal-card-body">
                            <div class="jadwal-card-row">
                                <span class="text-muted"><i class="far fa-clock me-2"></i>Jam:</span>
                                <span><?= date('H:i', strtotime($jadwal['jam_mulai'])) ?></span>
                            </div>
                            <div class="jadwal-card-row">
                                <span class="text-muted"><i class="fas fa-map-marker-alt me-2"></i>Lokasi:</span>
                                <span><?= htmlspecialchars($jadwal['lokasi']) ?></span>
                            </div>
                            <?php if (!empty($jadwal['catatan'])): ?>
                                <div class="jadwal-card-row">
                                    <span class="text-muted"><i class="fas fa-sticky-note me-2"></i>Catatan:</span>
                                    <span><?= htmlspecialchars($jadwal['catatan']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="jadwal-card-footer">
                            <?php if (in_array($user_peran, ['admin', 'pembina'])): ?>
                                <a href="edit.php?id=<?= $jadwal['id_jadwal'] ?>" class="btn btn-outline-warning btn-sm">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </a>
                                <?php
                                // Check if attendance should be disabled
                                $jadwal_date = new DateTime($jadwal['tanggal']);
                                $today = new DateTime('today');
                                $is_before_date = $jadwal_date > $today;
                                
                                if (in_array($jadwal['status'], ['dibatalkan', 'selesai']) || $is_before_date): ?>
                                    <?php
                                    $tooltip_text = '';
                                    if ($is_before_date) {
                                        $tooltip_text = 'Absensi belum dapat dilakukan karena tanggal latihan belum tiba';
                                    } elseif ($jadwal['status'] === 'dibatalkan') {
                                        $tooltip_text = 'Absensi tidak dapat dilakukan karena jadwal telah dibatalkan';
                                    } elseif ($jadwal['status'] === 'selesai') {
                                        $tooltip_text = 'Absensi sudah dilakukan untuk jadwal ini';
                                    }
                                    ?>
                                    <button class="btn btn-outline-secondary btn-sm" disabled title="<?= $tooltip_text ?>">
                                        <i class="fas fa-clipboard-check me-1"></i>Absensi
                                    </button>
                                <?php else: ?>
                                    <a href="../absenlatihan/absen.php?id=<?= $jadwal['id_jadwal'] ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-clipboard-check me-1"></i>Absensi
                                    </a>
                                <?php endif; ?>
                                <?php if ($user_peran === 'admin'): ?>
                                    <a href="hapus.php?id=<?= $jadwal['id_jadwal'] ?>" class="btn btn-outline-danger btn-sm">
                                        <i class="fas fa-trash me-1"></i>Hapus
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="detail.php?id=<?= $jadwal['id_jadwal'] ?>" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-eye me-1"></i>Detail
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&tanggal_mulai=<?= urlencode($tanggal_mulai) ?>&tanggal_selesai=<?= urlencode($tanggal_selesai) ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&tanggal_mulai=<?= urlencode($tanggal_mulai) ?>&tanggal_selesai=<?= urlencode($tanggal_selesai) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&tanggal_mulai=<?= urlencode($tanggal_mulai) ?>&tanggal_selesai=<?= urlencode($tanggal_selesai) ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    <div class="text-center text-muted small mt-2">
        Menampilkan <?= count($jadwals) ?> dari <?= $total_records ?> jadwal
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mt-4">
    <?php
    // Get stats with caching
    $stats = Cache::remember('jadwal_stats', function() use ($pdo) {
        $stmt = $pdo->query("SELECT
            SUM(CASE WHEN status = 'direncanakan' THEN 1 ELSE 0 END) as direncanakan,
            SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
            SUM(CASE WHEN status = 'dibatalkan' THEN 1 ELSE 0 END) as dibatalkan
            FROM jadwal_latihan");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }, 600); // Cache 10 menit

    $rencana_count = $stats['direncanakan'] ?? 0;
    $selesai_count = $stats['selesai'] ?? 0;
    $batal_count = $stats['dibatalkan'] ?? 0;
    ?>
    <div class="col-md-4 mb-3">
        <div class="card bg-warning text-dark">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-calendar-alt fa-2x me-3"></i>
                    <div>
                        <div class="h4 mb-0"><?= $rencana_count ?></div>
                        <small>Direncanakan</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle fa-2x me-3"></i>
                    <div>
                        <div class="h4 mb-0"><?= $selesai_count ?></div>
                        <small>Selesai</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card bg-danger text-white">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-times-circle fa-2x me-3"></i>
                    <div>
                        <div class="h4 mb-0"><?= $batal_count ?></div>
                        <small>Dibatalkan</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Card List Styles for Mobile */
.jadwal-card-list {
    padding: 1rem;
}

.jadwal-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 1rem;
    overflow: hidden;
}

.jadwal-card-header {
    background: #f8f9fa;
    padding: 1rem;
    display: flex;
    align-items: center;
    border-bottom: 1px solid #e0e0e0;
}

.jadwal-card-body {
    padding: 1rem;
}

.jadwal-card-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.jadwal-card-row:last-child {
    border-bottom: none;
}

.jadwal-card-footer {
    padding: 1rem;
    background: #f8f9fa;
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.jadwal-card-footer .btn {
    flex: 1;
    min-width: 80px;
}
</style>

<?php include '../../includes/footer.php'; ?>

