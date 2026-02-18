<?php
// Definisikan BASE_URL jika belum ada
if (!defined('BASE_URL')) {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/hadrahin';
    define('BASE_URL', $base_url);
}

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

$page_title = "Kelola Booking Acara";

// Get filter status
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$status_filter = in_array($status_filter, ['menunggu', 'diterima', 'ditolak', 'selesai', 'all']) ? $status_filter : 'all';

// Get period filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Validate dates
if (!empty($start_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
    $start_date = '';
}
if (!empty($end_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    $end_date = '';
}

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search = htmlspecialchars(strip_tags($search), ENT_QUOTES, 'UTF-8');
if (strlen($search) > 100) $search = substr($search, 0, 100);

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build query
$where_conditions = [];
$params = [];

if ($status_filter != 'all') {
    $where_conditions[] = "ba.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(ba.nama_acara LIKE ? OR ba.nama_pemesan LIKE ? OR ba.lokasi LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Period filter
if (!empty($start_date) && !empty($end_date)) {
    $where_conditions[] = "ba.tanggal_acara BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
} elseif (!empty($start_date)) {
    $where_conditions[] = "ba.tanggal_acara >= ?";
    $params[] = $start_date;
} elseif (!empty($end_date)) {
    $where_conditions[] = "ba.tanggal_acara <= ?";
    $params[] = $end_date;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Count total bookings
try {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM booking_acara ba {$where_clause}");
    $count_stmt->execute($params);
    $total_bookings = $count_stmt->fetchColumn();
} catch (PDOException $e) {
    $total_bookings = 0;
}
$total_pages = ceil($total_bookings / $limit);

// Get bookings with user and dresscode info
try {
    $query = "SELECT ba.*, u.nama_lengkap as penanggung_jawab, d.nama_pakaian as dresscode_name
              FROM booking_acara ba
              LEFT JOIN user u ON ba.id_user = u.id_user
              LEFT JOIN dresscode d ON ba.id_dresscode = d.id_dresscode
              {$where_clause}
              ORDER BY ba.created_at DESC
              LIMIT {$limit} OFFSET {$offset}";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    $bookings = [];
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

<!-- Toast Messages -->
<?php if (isset($_GET['success'])): ?>
    <?php
    $toastClass = '';
    $toastMessage = '';
    
    if ($_GET['success'] === 'booking_created') {
        $toastClass = 'bg-success';
        $toastMessage = 'Booking acara berhasil ditambahkan!';
    } elseif ($_GET['success'] === 'booking_updated') {
        $toastClass = 'bg-success';
        $toastMessage = 'Data booking berhasil diperbarui!';
    } elseif ($_GET['success'] === 'booking_deleted') {
        $toastClass = 'bg-success';
        $toastMessage = 'Booking berhasil dihapus!';
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


<!-- Floating Action Button -->
<?php if ($_SESSION['peran'] == 'admin'): ?>
    <a href="tambah.php" class="floating-btn" title="Tambah Booking">
        <i class="fas fa-plus"></i>
    </a>
<?php endif; ?>

<!-- Search & Filter Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="">
            <div class="row g-2">
                <!-- Start Date -->
                <div class="col-md-3">
                    <label class="form-label small text-muted">Tanggal Mulai:</label>
                    <input type="date" name="start_date" class="form-control" 
                           value="<?= htmlspecialchars($start_date) ?>">
                </div>
                
                <!-- End Date -->
                <div class="col-md-3">
                    <label class="form-label small text-muted">Tanggal Akhir:</label>
                    <input type="date" name="end_date" class="form-control" 
                           value="<?= htmlspecialchars($end_date) ?>">
                </div>
                
                <!-- Search -->
                <div class="col-md-3">
                    <label class="form-label small text-muted">Cari:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Nama acara, pemesan, lokasi..." 
                               value="<?= htmlspecialchars($search) ?>"
                               maxlength="100">
                    </div>
                </div>
                
                <!-- Status Filter Dropdown -->
                <div class="col-md-3">
                    <label class="form-label small text-muted">Status:</label>
                    <select name="status" class="form-select">
                        <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>Semua</option>
                        <option value="menunggu" <?= $status_filter == 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                        <option value="diterima" <?= $status_filter == 'diterima' ? 'selected' : '' ?>>Diterima</option>
                        <option value="ditolak" <?= $status_filter == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                        <option value="selesai" <?= $status_filter == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                    </select>
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

<!-- Bookings Card -->
<div class="card">
    <div class="card-body p-0">
        <!-- Desktop Table View -->
        <div class="d-none d-md-block">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="50" class="text-center">No</th>
                        <th>Nama Acara</th>
                        <th>Tanggal</th>
                        <th>Lokasi</th>
                        <th>Dresscode</th>
                        <th>Status</th>
                        <th width="150" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-calendar-times fa-3x mb-3 d-block text-secondary"></i>
                                <?= $search ? 'Tidak ada booking yang ditemukan.' : 'Tidak ada booking acara.' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $index => $booking): ?>
                            <?php 
                            $event_date = strtotime($booking['tanggal_acara']);
                            $today = strtotime(date('Y-m-d'));
                            $is_past = $event_date < $today && $booking['status'] != 'selesai';
                            ?>
                            <tr class="<?= $is_past ? 'opacity-50' : '' ?>">
                                <td class="text-center text-muted"><?= $offset + $index + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-info bg-opacity-25 text-info rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 14px;">
                                            <i class="fas fa-calendar"></i>
                                        </div>
                                        <div>
                                            <strong><?= htmlspecialchars($booking['nama_acara']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($booking['nama_pemesan']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($booking['tanggal_acara'])) ?>
                                    <br>
                                    <small class="text-muted"><?= date('H:i', strtotime($booking['jam_mulai'])) ?> WIB</small>
                                    <?php if ($is_past): ?>
                                        <br>
                                        <span class="badge bg-dark mt-1">
                                            <i class="fas fa-calendar-x me-1"></i>Terlewat
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($booking['lokasi']) ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= htmlspecialchars($booking['dresscode_name'] ?? 'Tidak ada') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $status_badges[$booking['status']] ?>">
                                        <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                                        <?= $status_labels[$booking['status']] ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-info" 
                                            onclick="viewBooking(<?= $booking['id_booking'] ?>, '<?= $booking['tanggal_acara'] ?>')"
                                            title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($_SESSION['peran'] == 'admin'): ?>
                                        <a href="edit.php?id=<?= $booking['id_booking'] ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php 
                                        $event_date = strtotime($booking['tanggal_acara']);
                                        $today = strtotime(date('Y-m-d'));
                                        $can_finish = $booking['status'] == 'diterima' && $event_date <= $today;
                                        ?>
                                        <?php if ($booking['status'] == 'menunggu'): ?>
                                            <button class="btn btn-sm btn-outline-success" 
                                                    onclick="updateStatus(<?= $booking['id_booking'] ?>, 'diterima')"
                                                    title="Terima">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="updateStatus(<?= $booking['id_booking'] ?>, 'ditolak')"
                                                    title="Tolak">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($booking['status'] == 'diterima'): ?>
                                            <?php if ($can_finish): ?>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="updateStatus(<?= $booking['id_booking'] ?>, 'selesai')"
                                                        title="Selesai">
                                                    <i class="fas fa-check-double"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-secondary" 
                                                        title="Belum waktunya - Acara belum berlangsung"
                                                        disabled>
                                                    <i class="fas fa-check-double"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($_SESSION['peran'] == 'admin'): ?>
                                            <a href="hapus.php?id=<?= $booking['id_booking'] ?>" class="btn btn-sm btn-outline-danger" 
                                               title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="d-md-none">
            <?php if (empty($bookings)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-calendar-times fa-3x mb-3 d-block text-secondary"></i>
                    <?= $search ? 'Tidak ada booking yang ditemukan.' : 'Tidak ada booking acara.' ?>
                </div>
            <?php else: ?>
                <?php foreach ($bookings as $index => $booking): ?>
                    <?php 
                    $event_date = strtotime($booking['tanggal_acara']);
                    $today = strtotime(date('Y-m-d'));
                    $is_past = $event_date < $today && $booking['status'] != 'selesai';
                    ?>
                    <div class="border-bottom p-3 <?= $is_past ? 'opacity-50' : '' ?>">
                        <div class="d-flex align-items-start mb-2">
                            <div class="bg-info bg-opacity-25 text-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?= htmlspecialchars($booking['nama_acara']) ?></h6>
                                <small class="text-muted"><?= htmlspecialchars($booking['nama_pemesan']) ?></small>
                            </div>
                            <span class="badge <?= $status_badges[$booking['status']] ?>">
                                <?= $status_labels[$booking['status']] ?>
                            </span>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6">
                                <small class="text-muted d-block"><i class="fas fa-calendar me-1"></i>Tanggal</small>
                                <span><?= date('d/m/Y', strtotime($booking['tanggal_acara'])) ?></span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block"><i class="fas fa-clock me-1"></i>Jam</small>
                                <span><?= date('H:i', strtotime($booking['jam_mulai'])) ?> WIB</span>
                            </div>
                        </div>
                        <?php if ($is_past): ?>
                            <div class="mb-2">
                                <span class="badge bg-dark">
                                    <i class="fas fa-calendar-x me-1"></i>Tanggal Acara Telah Terlewat
                                </span>
                            </div>
                        <?php endif; ?>
                        <div class="mb-2">
                            <small class="text-muted d-block"><i class="fas fa-map-marker-alt me-1"></i>Lokasi</small>
                            <span><?= htmlspecialchars($booking['lokasi']) ?></span>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted d-block"><i class="fas fa-tshirt me-1"></i>Dresscode</small>
                            <span class="badge bg-secondary"><?= htmlspecialchars($booking['dresscode_name'] ?? 'Tidak ada') ?></span>
                        </div>
                        <div class="d-flex flex-wrap gap-1 mt-2">
                            <button class="btn btn-sm btn-outline-info" onclick="viewBooking(<?= $booking['id_booking'] ?>, '<?= $booking['tanggal_acara'] ?>')">
                                <i class="fas fa-eye me-1"></i>Detail
                            </button>
                            <?php if ($_SESSION['peran'] == 'admin'): ?>
                                <a href="edit.php?id=<?= $booking['id_booking'] ?>" class="btn btn-sm btn-outline-warning">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </a>
                                <?php 
                                $event_date = strtotime($booking['tanggal_acara']);
                                $today = strtotime(date('Y-m-d'));
                                $can_finish = $booking['status'] == 'diterima' && $event_date <= $today;
                                ?>
                                <?php if ($booking['status'] == 'menunggu'): ?>
                                    <button class="btn btn-sm btn-outline-success" onclick="updateStatus(<?= $booking['id_booking'] ?>, 'diterima')">
                                        <i class="fas fa-check me-1"></i>Terima
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="updateStatus(<?= $booking['id_booking'] ?>, 'ditolak')">
                                        <i class="fas fa-times me-1"></i>Tolak
                                    </button>
                                <?php endif; ?>
                                <?php if ($booking['status'] == 'diterima'): ?>
                                    <?php if ($can_finish): ?>
                                        <button class="btn btn-sm btn-outline-primary" onclick="updateStatus(<?= $booking['id_booking'] ?>, 'selesai')">
                                            <i class="fas fa-check-double me-1"></i>Selesai
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" title="Belum waktunya - Acara belum berlangsung" disabled>
                                            <i class="fas fa-check-double me-1"></i>Selesai
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($_SESSION['peran'] == 'admin'): ?>
                                    <a href="hapus.php?id=<?= $booking['id_booking'] ?>" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash me-1"></i>Hapus
                                    </a>
                                <?php endif; ?>
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
                <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $p ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    <div class="text-center text-muted small mt-2">
        <?php if (!empty($start_date) || !empty($end_date)): ?>
            <span class="badge bg-info mb-2">
                <i class="fas fa-calendar-alt me-1"></i>
                Periode: <?= !empty($start_date) ? date('d/m/Y', strtotime($start_date)) : '-' ?> 
                s/d 
                <?= !empty($end_date) ? date('d/m/Y', strtotime($end_date)) : '-' ?>
            </span>
            <br>
        <?php endif; ?>
        Menampilkan <?= count($bookings) ?> dari <?= $total_bookings ?> booking
    </div>
<?php endif; ?>

<!-- Statistics Cards - Filtered by Period -->
<?php
// Build stats query based on period
$stats_where = "";
$stats_params = [];

if (!empty($start_date) && !empty($end_date)) {
    $stats_where = " WHERE tanggal_acara BETWEEN ? AND ?";
    $stats_params = [$start_date, $end_date];
} elseif (!empty($start_date)) {
    $stats_where = " WHERE tanggal_acara >= ?";
    $stats_params = [$start_date];
} elseif (!empty($end_date)) {
    $stats_where = " WHERE tanggal_acara <= ?";
    $stats_params = [$end_date];
}

// Get booking statistics
try {
    $stats = [];
    if (!empty($stats_where)) {
        $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM booking_acara {$stats_where} GROUP BY status");
        $stmt->execute($stats_params);
    } else {
        $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM booking_acara GROUP BY status");
    }
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats[$row['status']] = $row['count'];
    }
    
    // Get past events count (events with date before today and status != 'selesai')
    try {
        $today = date('Y-m-d');
        if (!empty($stats_where)) {
            $past_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM booking_acara WHERE tanggal_acara < ? AND status != 'selesai' {$stats_where}");
            $past_params = array_merge([$today], $stats_params);
            $past_stmt->execute($past_params);
        } else {
            $past_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM booking_acara WHERE tanggal_acara < ? AND status != 'selesai'");
            $past_stmt->execute([$today]);
        }
        $stats['terlewat'] = $past_stmt->fetchColumn();
    } catch (PDOException $e) {
        $stats['terlewat'] = 0;
    }
} catch (PDOException $e) {
    $stats = ['menunggu' => 0, 'diterima' => 0, 'ditolak' => 0, 'selesai' => 0, 'terlewat' => 0];
}

// Calculate total for percentage
$total_stats = array_sum($stats);
$menunggu_pct = $total_stats > 0 ? round(($stats['menunggu'] ?? 0) / $total_stats * 100) : 0;
$diterima_pct = $total_stats > 0 ? round(($stats['diterima'] ?? 0) / $total_stats * 100) : 0;
$ditolak_pct = $total_stats > 0 ? round(($stats['ditolak'] ?? 0) / $total_stats * 100) : 0;
$selesai_pct = $total_stats > 0 ? round(($stats['selesai'] ?? 0) / $total_stats * 100) : 0;
?>
<div class="row mt-4 mb-4">
    <div class="col-12">
        <h6 class="text-muted mb-3"><i class="fas fa-chart-bar me-2"></i>Statistik Booking Acara</h6>
    </div>
</div>

<!-- Main Stats Cards Row -->
<div class="row g-3 mb-4">
    <!-- Menunggu Card -->
    <div class="col-md-3 col-6">
        <div class="card bg-warning text-dark h-100 border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="h2 mb-0 fw-bold"><?= $stats['menunggu'] ?? 0 ?></div>
                        <small class="text-dark opacity-75">Menunggu</small>
                        <div class="mt-2">
                            <div class="progress" style="height: 4px; opacity: 0.5;">
                                <div class="progress-bar bg-dark" style="width: <?= $menunggu_pct ?>%"></div>
                            </div>
                            <small class="text-dark opacity-75"><?= $menunggu_pct ?>% dari total</small>
                        </div>
                    </div>
                    <div class="bg-dark bg-opacity-10 rounded p-2">
                        <i class="fas fa-clock fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Diterima Card -->
    <div class="col-md-3 col-6">
        <div class="card bg-success text-white h-100 border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="h2 mb-0 fw-bold"><?= $stats['diterima'] ?? 0 ?></div>
                        <small class="text-white opacity-75">Diterima</small>
                        <div class="mt-2">
                            <div class="progress bg-white bg-opacity-25" style="height: 4px;">
                                <div class="progress-bar bg-white" style="width: <?= $diterima_pct ?>%"></div>
                            </div>
                            <small class="text-white opacity-75"><?= $diterima_pct ?>% dari total</small>
                        </div>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded p-2">
                        <i class="fas fa-check fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ditolak Card -->
    <div class="col-md-3 col-6">
        <div class="card bg-danger text-white h-100 border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="h2 mb-0 fw-bold"><?= $stats['ditolak'] ?? 0 ?></div>
                        <small class="text-white opacity-75">Ditolak</small>
                        <div class="mt-2">
                            <div class="progress bg-white bg-opacity-25" style="height: 4px;">
                                <div class="progress-bar bg-white" style="width: <?= $ditolak_pct ?>%"></div>
                            </div>
                            <small class="text-white opacity-75"><?= $ditolak_pct ?>% dari total</small>
                        </div>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded p-2">
                        <i class="fas fa-times fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Selesai Card -->
    <div class="col-md-3 col-6">
        <div class="card bg-info text-white h-100 border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="h2 mb-0 fw-bold"><?= $stats['selesai'] ?? 0 ?></div>
                        <small class="text-white opacity-75">Selesai</small>
                        <div class="mt-2">
                            <div class="progress bg-white bg-opacity-25" style="height: 4px;">
                                <div class="progress-bar bg-white" style="width: <?= $selesai_pct ?>%"></div>
                            </div>
                            <small class="text-white opacity-75"><?= $selesai_pct ?>% dari total</small>
                        </div>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded p-2">
                        <i class="fas fa-check-double fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Terlewat Card - Full Width on Desktop -->
<div class="row mt-2">
    <div class="col-12">
        <div class="card bg-dark text-white border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="bg-white bg-opacity-25 rounded p-2 me-3">
                            <i class="fas fa-calendar-xmark fa-lg"></i>
                        </div>
                        <div>
                            <div class="h3 mb-0 fw-bold"><?= $stats['terlewat'] ?? 0 ?></div>
                            <small class="text-white opacity-75">Acara Terlewat</small>
                        </div>
                    </div>
                    <div class="text-end">
                        <small class="text-white opacity-75 d-block">Total Booking</small>
                        <div class="h4 mb-0"><?= $total_stats ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Detail Modal -->


<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Detail Booking Acara</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewContent">
                <!-- Content loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="dokumentasiLink" class="btn btn-info text-white">
                    <i class="fas fa-images me-2"></i>Lihat Dokumentasi
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Confirmation Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" id="statusModalHeader">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <span id="statusModalTitle">Konfirmasi Status</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="statusModalContent">
                    <!-- Content loaded dynamically -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Batal
                </button>
                <button type="button" class="btn" id="statusConfirmBtn">
                    <i class="fas fa-check me-1"></i>Ya, Konfirmasi
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function viewBooking(id, tanggalAcara) {
        fetch(`view_ajax.php?id=${id}`)
            .then(response => response.text())
            .then(html => {
                // Check if event date has passed
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const eventDate = new Date(tanggalAcara);
                const isPast = eventDate < today;
                
                // Add past event notification if applicable
                let contentWithNotification = html;
                if (isPast) {
                    const pastNotification = `
                        <div class="alert alert-dark border-2 border-dark mb-3" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-3" style="font-size: 1.5rem;"></i>
                                <div>
                                    <strong><i class="fas fa-calendar-x me-2"></i>Acara Sudah Terlewat</strong>
                                    <br>
                                    <small>Tanggal acara ini sudah berlalu. Dokumentasi tidak dapat diakses.</small>
                                </div>
                            </div>
                        </div>
                    `;
                    contentWithNotification = pastNotification + html;
                }
                
                document.getElementById('viewContent').innerHTML = contentWithNotification;
                
                // Check status from the HTML content
                const statusMatch = html.match(/status\.push\('(\w+?)'\)/);
                const statusBadge = document.querySelector('#viewModal .badge');
                
                const dokumentasiBtn = document.getElementById('dokumentasiLink');
                
                // Disable dokumentasi button if status is not 'selesai' or if date has passed
                if (statusBadge) {
                    const statusText = statusBadge.textContent.trim();
                    if (statusText !== 'Selesai' || isPast) {
                        dokumentasiBtn.classList.add('disabled');
                        dokumentasiBtn.classList.remove('btn-info', 'text-white');
                        dokumentasiBtn.classList.add('btn-secondary');
                        dokumentasiBtn.setAttribute('aria-disabled', 'true');
                        dokumentasiBtn.title = isPast ? 'Tidak dapat diakses - Acara sudah terlewat' : 'Hanya tersedia untuk acara yang sudah selesai';
                        dokumentasiBtn.onclick = function(e) { e.preventDefault(); };
                    } else {
                        dokumentasiBtn.classList.remove('disabled');
                        dokumentasiBtn.classList.add('btn-info', 'text-white');
                        dokumentasiBtn.classList.remove('btn-secondary');
                        dokumentasiBtn.removeAttribute('aria-disabled');
                        dokumentasiBtn.title = 'Lihat Dokumentasi';
                        dokumentasiBtn.onclick = null;
                        dokumentasiBtn.href = `dokumentasi.php?id=${id}`;
                    }
                } else {
                    // Fallback: check if Selesai badge exists in modal
                    if (html.includes('Selesai') && !isPast) {
                        dokumentasiBtn.classList.remove('disabled');
                        dokumentasiBtn.classList.add('btn-info', 'text-white');
                        dokumentasiBtn.classList.remove('btn-secondary');
                        dokumentasiBtn.removeAttribute('aria-disabled');
                        dokumentasiBtn.title = 'Lihat Dokumentasi';
                        dokumentasiBtn.onclick = null;
                        dokumentasiBtn.href = `dokumentasi.php?id=${id}`;
                    } else {
                        dokumentasiBtn.classList.add('disabled');
                        dokumentasiBtn.classList.remove('btn-info', 'text-white');
                        dokumentasiBtn.classList.add('btn-secondary');
                        dokumentasiBtn.setAttribute('aria-disabled', 'true');
                        dokumentasiBtn.title = isPast ? 'Tidak dapat diakses - Acara sudah terlewat' : 'Hanya tersedia untuk acara yang sudah selesai';
                        dokumentasiBtn.onclick = function(e) { e.preventDefault(); };
                    }
                }
                
                new bootstrap.Modal(document.getElementById('viewModal')).show();
            });
    }

    // Status configuration
    const statusConfig = {
        'diterima': {
            title: 'Terima Booking',
            message: 'Apakah Anda yakin ingin menerima booking acara ini?',
            btnClass: 'btn-success',
            icon: 'fa-check-circle',
            iconClass: 'text-success'
        },
        'ditolak': {
            title: 'Tolak Booking',
            message: 'Apakah Anda yakin ingin menolak booking acara ini?',
            btnClass: 'btn-danger',
            icon: 'fa-times-circle',
            iconClass: 'text-danger'
        },
        'selesai': {
            title: 'Selesai Booking',
            message: 'Apakah Anda yakin ingin menandai booking ini sebagai selesai?',
            btnClass: 'btn-primary',
            icon: 'fa-check-double',
            iconClass: 'text-primary'
        }
    };

    let currentStatusId = null;
    let currentStatusAction = null;

    function updateStatus(id, status) {
        const config = statusConfig[status];
        if (!config) return;
        
        currentStatusId = id;
        currentStatusAction = status;
        
        // Update modal content
        const modal = document.getElementById('statusModal');
        const header = document.getElementById('statusModalHeader');
        const title = document.getElementById('statusModalTitle');
        const content = document.getElementById('statusModalContent');
        const confirmBtn = document.getElementById('statusConfirmBtn');
        
        // Update header
        header.className = `modal-header bg-light`;
        title.innerHTML = `<i class="fas ${config.icon} ${config.iconClass} me-2"></i>${config.title}`;
        
        // Build content
        content.innerHTML = `
            <div class="text-center py-3">
                <div class="${config.iconClass} mb-3" style="font-size: 4rem;">
                    <i class="fas ${config.icon}"></i>
                </div>
                <p class="mb-3">${config.message}</p>
                <div class="alert alert-warning text-start">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Perhatian:</strong> Perubahan status akan dicatat dalam log sistem.
                </div>
            </div>
        `;
        
        // Update confirm button
        confirmBtn.className = `btn ${config.btnClass}`;
        confirmBtn.innerHTML = `<i class="fas fa-check me-1"></i>Ya, ${config.title.split(' ')[0]}`;
        
        // Show modal
        new bootstrap.Modal(modal).show();
    }

    // Handle confirm button click
    document.getElementById('statusConfirmBtn').addEventListener('click', function() {
        if (currentStatusId && currentStatusAction) {
            // Disable button to prevent double submit
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';
            
            fetch('update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id_booking=${currentStatusId}&status=${currentStatusAction}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Gagal mengubah status: ' + data.message);
                    this.disabled = false;
                    this.innerHTML = `<i class="fas fa-check me-1"></i>Ya, Konfirmasi`;
                    bootstrap.Modal.getInstance(document.getElementById('statusModal')).hide();
                }
            })
            .catch(error => {
                alert('Terjadi kesalahan: ' + error.message);
                this.disabled = false;
                this.innerHTML = `<i class="fas fa-check me-1"></i>Ya, Konfirmasi`;
                bootstrap.Modal.getInstance(document.getElementById('statusModal')).hide();
            });
        }
    });
</script>

<?php include '../../includes/footer.php'; ?>
