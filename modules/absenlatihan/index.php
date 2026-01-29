<?php
/**
 * Modul Absensi Latihan - Halaman Daftar Absensi
 * Menampilkan semua data absensi latihan dengan filter dan pencarian
 */

// Definisikan BASE_URL jika belum ada
if (!defined('BASE_URL')) {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/hadrahin';
    define('BASE_URL', $base_url);
}

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
require_once '../../includes/cache.php';

$page_title = "Absensi Latihan";

// Get current user role
$user_peran = $_SESSION['peran'] ?? 'anggota';
$user_id = $_SESSION['user_id'] ?? 0;

// Check permission - only admin and pembina can access
if (!in_array($user_peran, ['admin', 'pembina'])) {
    header('Location: ../dashboard/' . $user_peran . '.php?msg=access_denied');
    exit;
}

// Pagination
$limit = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Search & Filter dengan Validasi
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search = htmlspecialchars(strip_tags($search), ENT_QUOTES, 'UTF-8');
if (strlen($search) > 100) $search = substr($search, 0, 100);

// Validasi status_filter dengan whitelist
$allowed_status = ['hadir', 'izin', 'alpa', ''];
$status_filter = isset($_GET['status']) && in_array($_GET['status'], $allowed_status)
    ? $_GET['status'] : '';

// Validasi jadwal_filter
$jadwal_filter = isset($_GET['jadwal']) ? (int)$_GET['jadwal'] : 0;

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

// Cari tanggal terbaru di absen_latihan untuk default end date dengan caching
$max_date_result = Cache::remember('absen_max_date', function() use ($pdo) {
    $stmt = $pdo->query("SELECT MAX(j.tanggal) as max_date FROM absen_latihan a JOIN jadwal_latihan j ON a.id_jadwal = j.id_jadwal");
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
    $where_conditions[] = "(u.nama_lengkap LIKE ? OR u.username LIKE ? OR j.lokasi LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($status_filter)) {
    $where_conditions[] = "a.status_hadir = ?";
    $params[] = $status_filter;
}

if (!empty($jadwal_filter)) {
    $where_conditions[] = "a.id_jadwal = ?";
    $params[] = $jadwal_filter;
}

// Filter periode tanggal
if (!empty($tanggal_mulai) && !empty($tanggal_selesai)) {
    $where_conditions[] = "j.tanggal BETWEEN ? AND ?";
    $params[] = $tanggal_mulai;
    $params[] = $tanggal_selesai;
} elseif (!empty($tanggal_mulai)) {
    $where_conditions[] = "j.tanggal >= ?";
    $params[] = $tanggal_mulai;
} elseif (!empty($tanggal_selesai)) {
    $where_conditions[] = "j.tanggal <= ?";
    $params[] = $tanggal_selesai;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Count total absensi
$count_sql = "SELECT COUNT(*) FROM absen_latihan a
              JOIN jadwal_latihan j ON a.id_jadwal = j.id_jadwal
              JOIN user u ON a.id_user = u.id_user $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch absensi data
$sql = "SELECT a.*, j.tanggal, j.jam_mulai, j.lokasi, j.catatan,
               u.nama_lengkap, u.username, u.peran
        FROM absen_latihan a
        JOIN jadwal_latihan j ON a.id_jadwal = j.id_jadwal
        JOIN user u ON a.id_user = u.id_user
        $where_clause
        ORDER BY j.tanggal DESC, j.jam_mulai DESC, u.nama_lengkap ASC
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$absensis = $stmt->fetchAll();

// Get jadwal list for filter dropdown
$jadwal_list = Cache::remember('jadwal_list', function() use ($pdo) {
    $stmt = $pdo->query("SELECT id_jadwal, tanggal, jam_mulai, lokasi
                        FROM jadwal_latihan
                        ORDER BY tanggal DESC, jam_mulai DESC");
    return $stmt->fetchAll();
}, 600);

// Include header dengan sidebar
include '../../includes/header.php';
?>

<!-- Toast Messages -->
<?php if (isset($_GET['msg'])): ?>
    <?php
    $toastClass = '';
    $toastMessage = '';

    if ($_GET['msg'] === 'export_sukes') {
        $toastClass = 'bg-success';
        $toastMessage = 'Data absensi berhasil diekspor!';
    } elseif ($_GET['msg'] === 'error') {
        $toastClass = 'bg-danger';
        $toastMessage = 'Terjadi kesalahan!';
    } elseif ($_GET['msg'] === 'access_denied') {
        $toastClass = 'bg-warning';
        $toastMessage = 'Anda tidak memiliki akses untuk operasi ini!';
    } elseif ($_GET['msg'] === 'no_data') {
        $toastClass = 'bg-info';
        $toastMessage = 'Tidak ada data absensi untuk diekspor!';
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
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control"
                               placeholder="Cari nama, username, lokasi..."
                               value="<?= htmlspecialchars($search) ?>"
                               maxlength="100">
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="hadir" <?= $status_filter === 'hadir' ? 'selected' : '' ?>>Hadir</option>
                        <option value="izin" <?= $status_filter === 'izin' ? 'selected' : '' ?>>Izin</option>
                        <option value="alpa" <?= $status_filter === 'alpa' ? 'selected' : '' ?>>Alpa</option>
                    </select>
                </div>

                <!-- Jadwal Filter -->
                <div class="col-md-3">
                    <select name="jadwal" class="form-select">
                        <option value="">Semua Jadwal</option>
                        <?php foreach ($jadwal_list as $jadwal): ?>
                            <option value="<?= $jadwal['id_jadwal'] ?>" <?= $jadwal_filter == $jadwal['id_jadwal'] ? 'selected' : '' ?>>
                                <?= date('d/m/Y H:i', strtotime($jadwal['tanggal'] . ' ' . $jadwal['jam_mulai'])) ?> - <?= htmlspecialchars($jadwal['lokasi']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tanggal Mulai -->
                <div class="col-md-2">
                    <input type="date" name="tanggal_mulai" class="form-control"
                           value="<?= htmlspecialchars($tanggal_mulai) ?>" title="Dari Tanggal">
                </div>

                <!-- Tanggal Selesai -->
                <div class="col-md-2">
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

<!-- Export Button -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Data Absensi Latihan</h5>
    <?php if ($total_records > 0): ?>
        <a href="export.php?<?= http_build_query($_GET) ?>" class="btn btn-success">
            <i class="fas fa-download me-2"></i>Export Excel
        </a>
    <?php else: ?>
        <button class="btn btn-secondary" disabled title="Tidak ada data untuk diekspor">
            <i class="fas fa-download me-2"></i>Export Excel
        </button>
    <?php endif; ?>
</div>

<!-- Absensi List - Table View -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="50" class="text-center">No</th>
                        <th>Jadwal Latihan</th>
                        <th>Anggota</th>
                        <th>Status</th>
                        <th>Waktu Absen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($absensis)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fas fa-clipboard-list fa-3x mb-3 d-block text-secondary"></i>
                                <?= $search || $status_filter || $jadwal_filter ? 'Tidak ada data absensi yang ditemukan.' : 'Belum ada data absensi latihan.' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($absensis as $i => $absen): ?>
                            <?php
                            $tanggal = new DateTime($absen['tanggal']);
                            $status_class = match($absen['status_hadir']) {
                                'hadir' => 'bg-success',
                                'izin' => 'bg-warning text-dark',
                                'alpa' => 'bg-danger',
                                default => 'bg-secondary'
                            };
                            $status_icon = match($absen['status_hadir']) {
                                'hadir' => 'fa-check-circle',
                                'izin' => 'fa-exclamation-triangle',
                                'alpa' => 'fa-times-circle',
                                default => 'fa-question-circle'
                            };
                            ?>
                            <tr>
                                <td class="text-center text-muted"><?= $offset + $i + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px; font-size: 11px;">
                                            <?= $tanggal->format('d') ?>
                                            <span class="d-block" style="font-size: 8px;"><?= $tanggal->format('M') ?></span>
                                        </div>
                                        <div>
                                            <div class="fw-bold small"><?= $tanggal->format('l, d M Y') ?></div>
                                            <small class="text-muted">
                                                <i class="far fa-clock me-1"></i><?= date('H:i', strtotime($absen['jam_mulai'])) ?>
                                                <i class="fas fa-map-marker-alt ms-2 me-1"></i><?= htmlspecialchars($absen['lokasi']) ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($absen['nama_lengkap']) ?></div>
                                        <small class="text-muted">@<?= htmlspecialchars($absen['username']) ?></small>
                                        <span class="badge bg-light text-dark ms-1"><?= ucfirst($absen['peran']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $status_class ?>">
                                        <i class="fas <?= $status_icon ?> me-1"></i>
                                        <?= ucfirst($absen['status_hadir']) ?>
                                    </span>
                                </td>
                                <td>
                                    <i class="far fa-calendar-check text-muted me-1"></i>
                                    <?= date('d/m/Y H:i', strtotime($absen['jam_absen'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&jadwal=<?= $jadwal_filter ?>&tanggal_mulai=<?= urlencode($tanggal_mulai) ?>&tanggal_selesai=<?= urlencode($tanggal_selesai) ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&jadwal=<?= $jadwal_filter ?>&tanggal_mulai=<?= urlencode($tanggal_mulai) ?>&tanggal_selesai=<?= urlencode($tanggal_selesai) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&jadwal=<?= $jadwal_filter ?>&tanggal_mulai=<?= urlencode($tanggal_mulai) ?>&tanggal_selesai=<?= urlencode($tanggal_selesai) ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    <div class="text-center text-muted small mt-2">
        Menampilkan <?= count($absensis) ?> dari <?= $total_records ?> data absensi
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mt-4">
    <?php
    // Get stats with caching
    $stats = Cache::remember('absensi_stats', function() use ($pdo) {
        $stmt = $pdo->query("SELECT
            SUM(CASE WHEN status_hadir = 'hadir' THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN status_hadir = 'izin' THEN 1 ELSE 0 END) as izin,
            SUM(CASE WHEN status_hadir = 'alpa' THEN 1 ELSE 0 END) as alpa
            FROM absen_latihan");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }, 600); // Cache 10 menit

    $hadir_count = $stats['hadir'] ?? 0;
    $izin_count = $stats['izin'] ?? 0;
    $alpa_count = $stats['alpa'] ?? 0;
    $total_absensi = $hadir_count + $izin_count + $alpa_count;
    ?>
    <div class="col-md-4 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle fa-2x me-3"></i>
                    <div>
                        <div class="h4 mb-0"><?= $hadir_count ?></div>
                        <small>Hadir</small>
                        <?php if ($total_absensi > 0): ?>
                            <div class="small mt-1"><?= round(($hadir_count / $total_absensi) * 100, 1) ?>%</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                    <div>
                        <div class="h4 mb-0"><?= $izin_count ?></div>
                        <small>Izin</small>
                        <?php if ($total_absensi > 0): ?>
                            <div class="small mt-1"><?= round(($izin_count / $total_absensi) * 100, 1) ?>%</div>
                        <?php endif; ?>
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
                        <div class="h4 mb-0"><?= $alpa_count ?></div>
                        <small>Alpa</small>
                        <?php if ($total_absensi > 0): ?>
                            <div class="small mt-1"><?= round(($alpa_count / $total_absensi) * 100, 1) ?>%</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
