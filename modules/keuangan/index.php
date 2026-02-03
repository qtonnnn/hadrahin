<?php
/**
 * Keuangan - Halaman Daftar Transaksi Kas
 * Menggunakan sidebar layout yang konsisten dengan modul lain
 */

// Definisikan BASE_URL jika belum ada
if (!defined('BASE_URL')) {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/hadrahin';
    define('BASE_URL', $base_url);
}

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

$page_title = "Keuangan";

// Search dengan Validasi
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search = htmlspecialchars(strip_tags($search), ENT_QUOTES, 'UTF-8');
if (strlen($search) > 100) $search = substr($search, 0, 100);

// Filter tipe
$tipe_filter = isset($_GET['tipe']) ? $_GET['tipe'] : '';
if (!in_array($tipe_filter, ['', 'pemasukan', 'pengeluaran'])) {
    $tipe_filter = '';
}

// Filter bulan
$bulan_filter = isset($_GET['bulan']) ? $_GET['bulan'] : '';
if (!preg_match('/^\d{4}-\d{2}$/', $bulan_filter)) {
    $bulan_filter = '';
}

// Build query dengan search dan filter
$where_clause = "1=1";
$params = [];

if ($search) {
    $where_clause .= " AND (k.keterangan LIKE ? OR k.kategori LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($tipe_filter) {
    $where_clause .= " AND k.tipe = ?";
    $params[] = $tipe_filter;
}

if ($bulan_filter) {
    $where_clause .= " AND DATE_FORMAT(k.tanggal, '%Y-%m') = ?";
    $params[] = $bulan_filter;
}

// Get all transaksi kas
$query = "SELECT k.*, u.nama_lengkap 
          FROM keuangan k 
          LEFT JOIN user u ON k.id_user = u.id_user
          WHERE $where_clause
          ORDER BY k.tanggal DESC, k.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$kas_list = $stmt->fetchAll();

// Calculate statistics
$total_pemasukan = 0;
$total_pengeluaran = 0;

foreach ($kas_list as $kas) {
    if ($kas['tipe'] === 'pemasukan') {
        $total_pemasukan += (float)$kas['jumlah'];
    } else {
        $total_pengeluaran += (float)$kas['jumlah'];
    }
}

$saldo_kas = $total_pemasukan - $total_pengeluaran;
$total_transaksi = count($kas_list);

// Format currency
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Include header dengan sidebar
include '../../includes/header.php';
?>

<!-- Floating Action Button -->
<a href="tambah.php" class="floating-btn" title="Tambah Transaksi">
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
            $toastMessage = 'Transaksi berhasil ditambahkan!';
            break;
        case 'edit_sukes':
            $toastClass = 'bg-success';
            $toastMessage = 'Transaksi berhasil diperbarui!';
            break;
        case 'hapus_sukes':
            $toastClass = 'bg-success';
            $toastMessage = 'Transaksi berhasil dihapus!';
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

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end" id="filterForm">
            <div class="col-md-4">
                <label for="search" class="form-label">Pencarian</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" id="searchInput" class="form-control" 
                           placeholder="Cari keterangan..." 
                           value="<?= htmlspecialchars($search) ?>"
                           maxlength="100"
                           autocomplete="off">
                </div>
            </div>
            <div class="col-md-3">
                <label for="tipe" class="form-label">Tipe Transaksi</label>
                <select name="tipe" id="tipeFilter" class="form-select">
                    <option value="">Semua Tipe</option>
                    <option value="pemasukan" <?= $tipe_filter === 'pemasukan' ? 'selected' : '' ?>>Pemasukan</option>
                    <option value="pengeluaran" <?= $tipe_filter === 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="bulan" class="form-label">Bulan</label>
                <input type="month" name="bulan" id="bulanFilter" class="form-control"
                       value="<?= htmlspecialchars($bulan_filter) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form on filter change
    const filterForm = document.getElementById('filterForm');
    const inputs = filterForm.querySelectorAll('input, select');
    
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            filterForm.submit();
        });
    });
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-hide search parameters after 5 seconds
    setTimeout(function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('search') || urlParams.has('tipe') || urlParams.has('bulan')) {
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    }, 5000);
});
</script>

<!-- Kas List - Card View for Mobile, Table for Desktop -->
<div class="card">
    <div class="card-header py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list me-1"></i> Riwayat Transaksi Kas
            </h6>
            <span class="badge bg-secondary"><?= $total_transaksi ?> transaksi</span>
        </div>
    </div>
    <div class="card-body p-0">
        <!-- Desktop Table View -->
        <div class="d-none d-md-block">
            <div class="table-responsive-custom">
                <table class="table table-hover align-middle mb-0 table-condensed-custom">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">No</th>
                            <th>Tanggal</th>
                            <th>Tipe</th>
                            <th>Kategori</th>
                            <th class="text-end">Jumlah</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($kas_list)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-wallet fa-3x mb-3 d-block text-secondary"></i>
                                    <?= $search || $tipe_filter || $bulan_filter ? 'Tidak ada transaksi yang ditemukan' : 'Belum ada data transaksi kas' ?>
                                    <div class="mt-3">
                                        <?php if ($search || $tipe_filter || $bulan_filter): ?>
                                            <a href="index.php" class="btn btn-outline-secondary me-2">
                                                <i class="fas fa-arrow-left me-1"></i>Kembali
                                            </a>
                                        <?php endif; ?>
                                        <a href="tambah.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus me-1"></i> Tambah Transaksi
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($kas_list as $i => $kas): ?>
                                <tr>
                                    <td class="text-center text-muted fw-semibold"><?= $i + 1 ?></td>
                                    <td class="text-nowrap">
                                        <span class="d-block"><?= date('d/m/Y', strtotime($kas['tanggal'])) ?></span>
                                        <small class="text-muted d-block d-lg-none"><?= date('H:i', strtotime($kas['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($kas['tipe'] === 'pemasukan'): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-arrow-down me-1"></i>Pemasukan
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">
                                                <i class="fas fa-arrow-up me-1"></i>Pengeluaran
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($kas['kategori'] ?? '-') ?>">
                                        <span class="fw-medium"><?= htmlspecialchars($kas['kategori'] ?? '-') ?></span>
                                        <?php if (!empty($kas['keterangan'])): ?>
                                            <small class="d-block text-muted text-truncate" style="max-width: 180px;">
                                                <?= htmlspecialchars($kas['keterangan']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end <?= $kas['tipe'] === 'pemasukan' ? 'text-success' : 'text-danger' ?>">
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold">
                                                <?= $kas['tipe'] === 'pemasukan' ? '+' : '-' ?>
                                                <?= formatRupiah($kas['jumlah']) ?>
                                            </span>
                                            <?php if ($kas['tipe'] === 'pengeluaran' && $saldo_kas < 0): ?>
                                                <small class="text-warning fw-semibold">(Saldo Negatif)</small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button class="btn btn-outline-info" 
                                                    onclick="viewDetail(<?= $kas['id_kas'] ?>)"
                                                    title="Detail"
                                                    data-bs-toggle="tooltip">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="edit.php?id=<?= $kas['id_kas'] ?>" 
                                               class="btn btn-outline-warning"
                                               title="Edit"
                                               data-bs-toggle="tooltip">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="hapus.php?id=<?= $kas['id_kas'] ?>" 
                                               class="btn btn-outline-danger"
                                               title="Hapus"
                                               data-bs-toggle="tooltip"
                                               onclick="return confirm('Hapus transaksi ini?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile Card View -->
        <div class="d-md-none">
            <?php if (empty($kas_list)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-wallet fa-3x mb-3 d-block text-secondary"></i>
                    <?= $search || $tipe_filter || $bulan_filter ? 'Tidak ada transaksi yang ditemukan' : 'Belum ada data transaksi kas' ?>
                    <div class="mt-3">
                        <a href="tambah.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> Tambah Transaksi
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($kas_list as $i => $kas): ?>
                    <div class="kas-card p-3 border-bottom">
                        <div class="d-flex align-items-start">
                            <div class="kas-icon <?= $kas['tipe'] === 'pemasukan' ? 'bg-success' : 'bg-danger' ?> text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; font-size: 20px;">
                                <i class="fas <?= $kas['tipe'] === 'pemasukan' ? 'fa-arrow-down' : 'fa-arrow-up' ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="badge <?= $kas['tipe'] === 'pemasukan' ? 'bg-success' : 'bg-danger' ?> mb-1">
                                            <?= ucfirst($kas['tipe']) ?>
                                        </span>
                                        <h5 class="mb-0"><?= htmlspecialchars($kas['kategori'] ?? '-') ?></h5>
                                    </div>
                                    <span class="<?= $kas['tipe'] === 'pemasukan' ? 'text-success' : 'text-danger' ?> fw-bold">
                                        <?= $kas['tipe'] === 'pemasukan' ? '+' : '-' ?>
                                        <?= formatRupiah($kas['jumlah']) ?>
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted d-block">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?= date('d/m/Y', strtotime($kas['tanggal'])) ?>
                                    </small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-info btn-sm flex-grow-1" onclick="viewDetail(<?= $kas['id_kas'] ?>)">
                                        <i class="fas fa-eye me-1"></i>Detail
                                    </button>
                                    <a href="edit.php?id=<?= $kas['id_kas'] ?>" class="btn btn-outline-warning btn-sm flex-grow-1">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </a>
                                    <a href="hapus.php?id=<?= $kas['id_kas'] ?>" class="btn btn-outline-danger btn-sm flex-grow-1">
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

<style>
.kas-card {
    background: #fff;
}

.kas-card:last-child {
    border-bottom: none !important;
}

.kas-icon {
    flex-shrink: 0;
}

/* Table Styling Improvements */
.table-condensed-custom {
    font-size: 0.85rem;
    width: 100%;
    table-layout: fixed;
}

.table-condensed-custom th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
    white-space: nowrap;
    vertical-align: middle;
}

.table-condensed-custom td {
    vertical-align: middle;
    padding: 0.5rem 0.3rem;
    border-top: 1px solid #eee;
}

/* Column Width Adjustments */
.table-condensed-custom th:nth-child(1), /* No */
.table-condensed-custom td:nth-child(1) {
    width: 50px;
    text-align: center;
}

.table-condensed-custom th:nth-child(2), /* Tanggal */
.table-condensed-custom td:nth-child(2) {
    width: 100px;
    min-width: 100px;
}

.table-condensed-custom th:nth-child(3), /* Tipe */
.table-condensed-custom td:nth-child(3) {
    width: 120px;
    min-width: 120px;
}

.table-condensed-custom th:nth-child(4), /* Kategori */
.table-condensed-custom td:nth-child(4) {
    min-width: 150px;
}

.table-condensed-custom th:nth-child(5), /* Jumlah */
.table-condensed-custom td:nth-child(5) {
    width: 150px;
    min-width: 150px;
    text-align: right;
}

.table-condensed-custom th:nth-child(6), /* Aksi */
.table-condensed-custom td:nth-child(6) {
    width: 140px;
    text-align: center;
    min-width: 140px;
}

/* Badge Styling for Type Column */
.table-condensed-custom .badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

/* Amount Column Styling */
.table-condensed-custom td:nth-child(5) {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* Action Buttons */
.table-condensed-custom .btn-group-sm > .btn {
    padding: 0.25rem 0.4rem;
    font-size: 0.75rem;
    border-radius: 0.25rem;
    margin: 0 1px;
}

/* Hover Effect */
.table-condensed-custom tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

/* Responsive adjustments */
@media (max-width: 1200px) {
    .table-condensed-custom {
        font-size: 0.8rem;
    }
    
    .table-condensed-custom th:nth-child(5),
    .table-condensed-custom td:nth-child(5) {
        width: 130px;
        min-width: 130px;
    }
}

/* For empty state */
.table-condensed-custom tbody td[colspan="6"] {
    padding: 3rem 1rem;
    text-align: center;
    color: #6c757d;
    font-style: italic;
}

/* Responsive table container */
.table-responsive-custom {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin: 0 -1rem;
    padding: 0 1rem;
}

/* Scrollbar styling for table */
.table-responsive-custom::-webkit-scrollbar {
    height: 6px;
}

.table-responsive-custom::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.table-responsive-custom::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}

.table-responsive-custom::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}
</style>

<!-- Statistics Cards - Moved to Bottom -->
<div class="row mt-4">
    <div class="col-md-4 col-6 mb-3">
        <div class="stat-card stat-success">
            <div class="stat-icon">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="stat-value"><?= formatRupiah($total_pemasukan) ?></div>
            <div class="stat-label">Total Pemasukan</div>
        </div>
    </div>
    <div class="col-md-4 col-6 mb-3">
        <div class="stat-card stat-danger">
            <div class="stat-icon">
                <i class="fas fa-arrow-up"></i>
            </div>
            <div class="stat-value"><?= formatRupiah($total_pengeluaran) ?></div>
            <div class="stat-label">Total Pengeluaran</div>
        </div>
    </div>
    <div class="col-md-4 col-12 mb-3">
        <div class="stat-card <?= $saldo_kas >= 0 ? 'stat-primary' : 'stat-warning' ?>">
            <div class="stat-icon">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="stat-value"><?= formatRupiah($saldo_kas) ?></div>
            <div class="stat-label">Saldo Kas</div>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-receipt me-2"></i>Detail Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <!-- Content loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function viewDetail(id) {
    fetch(`view_detail_ajax.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('detailContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('detailModal')).show();
        })
        .catch(error => {
            alert('Gagal memuat detail: ' + error.message);
        });
}
</script>

<?php include '../../includes/footer.php'; ?>

