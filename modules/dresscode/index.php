<?php
/**
 * Manajemen Dresscode - Halaman Daftar Dresscode
 * Menggunakan sidebar layout yang konsisten dengan dashboard admin
 */

// Definisikan BASE_URL jika belum ada
if (!defined('BASE_URL')) {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/hadrahin';
    define('BASE_URL', $base_url);
}

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

$page_title = "Kelola Dresscode";

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search dengan Validasi
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search = htmlspecialchars(strip_tags($search), ENT_QUOTES, 'UTF-8');
if (strlen($search) > 100) $search = substr($search, 0, 100);

// Filter status
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
if (!in_array($status_filter, ['aktif', 'nonaktif', ''])) {
    $status_filter = '';
}

// Count total dresscode
$where_clause = '';
$params = [];
if ($search) {
    $where_clause = "WHERE nama_pakaian LIKE ? OR deskripsi LIKE ? OR warna LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}
if ($status_filter) {
    $where_clause .= ($where_clause ? ' AND ' : 'WHERE ') . "status = ?";
    $params[] = $status_filter;
}

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM dresscode " . $where_clause);
$count_stmt->execute($params);
$total_dresscode = $count_stmt->fetchColumn();
$total_pages = ceil($total_dresscode / $limit);

// Fetch dresscode
$sql = "SELECT * FROM dresscode $where_clause ORDER BY id_dresscode DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dresscode_list = $stmt->fetchAll();

// Include header dengan sidebar
include '../../includes/header.php';
?>

<!-- Floating Action Button -->
<a href="tambah.php" class="floating-btn" title="Tambah Dresscode">
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
            $toastMessage = 'Dresscode baru berhasil ditambahkan!';
            break;
        case 'edit_sukes':
            $toastClass = 'bg-success';
            $toastMessage = 'Data dresscode berhasil diperbarui!';
            break;
        case 'hapus_sukes':
            $toastClass = 'bg-success';
            $toastMessage = 'Dresscode berhasil dihapus!';
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

<!-- Search & Filter Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-end search-form">
            <div class="input-group" style="max-width: 300px;">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" name="search" class="form-control" 
                       placeholder="Cari dresscode..." 
                       value="<?= htmlspecialchars($search) ?>"
                       maxlength="100">
            </div>
            <div style="width: 150px;">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="aktif" <?= $status_filter === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= $status_filter === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
                <?php if ($search || $status_filter): ?>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Dresscode List - Card View for Mobile, Table for Desktop -->
<div class="card">
    <div class="card-body p-0">
        <!-- Desktop Table View -->
        <div class="d-none d-md-block">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="50" class="text-center">No</th>
                        <th>Nama Pakaian</th>
                        <th>Deskripsi</th>
                        <th>Warna</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                        <th width="120" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dresscode_list)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-tshirt fa-3x mb-3 d-block text-secondary"></i>
                                <?= ($search || $status_filter) ? 'Tidak ada dresscode yang ditemukan.' : 'Belum ada data dresscode.' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dresscode_list as $i => $dc): ?>
                            <tr>
                                <td class="text-center text-muted"><?= $offset + $i + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px; font-size: 16px;">
                                            <i class="fas fa-tshirt"></i>
                                        </div>
                                        <span class="fw-500"><?= htmlspecialchars($dc['nama_pakaian']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $deskripsi = $dc['deskripsi'] ?: '-';
                                    echo strlen($deskripsi) > 50 ? htmlspecialchars(substr($deskripsi, 0, 50)) . '...' : htmlspecialchars($deskripsi);
                                    ?>
                                </td>
                                <td>
                                    <?php if ($dc['warna']): ?>
                                        <?php
                                        $warna = htmlspecialchars($dc['warna']);
                                        // Convert hex to RGB
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
                                            <i class="fas fa-palette me-1"></i><?= htmlspecialchars($dc['warna']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $dc['status'] === 'aktif' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $dc['status'] === 'aktif' ? '<i class="fas fa-check-circle me-1"></i>Aktif' : '<i class="fas fa-times-circle me-1"></i>Nonaktif' ?>
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    <?= date('d/m/Y', strtotime($dc['created_at'])) ?>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-info" title="Detail"
                                            onclick="showDetailModal(<?= $dc['id_dresscode'] ?>, '<?= htmlspecialchars($dc['nama_pakaian'], ENT_QUOTES) ?>', '<?= htmlspecialchars($dc['deskripsi'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($dc['warna'] ?? '', ENT_QUOTES) ?>', '<?= $dc['status'] ?>')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="edit.php?id=<?= $dc['id_dresscode'] ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="hapus.php?id=<?= $dc['id_dresscode'] ?>" class="btn btn-sm btn-outline-danger" title="Hapus">
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
            <?php if (empty($dresscode_list)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-tshirt fa-3x mb-3 d-block text-secondary"></i>
                    <?= ($search || $status_filter) ? 'Tidak ada dresscode yang ditemukan.' : 'Belum ada data dresscode.' ?>
                </div>
            <?php else: ?>
                <?php foreach ($dresscode_list as $i => $dc): ?>
                    <div class="dresscode-card p-3 border-bottom">
                        <div class="d-flex align-items-start">
                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; font-size: 20px;">
                                <i class="fas fa-tshirt"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="mb-0"><?= htmlspecialchars($dc['nama_pakaian']) ?></h5>
                                    <span class="badge <?= $dc['status'] === 'aktif' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $dc['status'] === 'aktif' ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                </div>
                                <p class="text-muted small mb-2">
                                    <?= $dc['deskripsi'] ?: 'Tidak ada deskripsi' ?>
                                </p>
                                <div class="d-flex gap-2 mb-2">
                                    <?php if ($dc['warna']): ?>
                                        <?php
                                        $warna = htmlspecialchars($dc['warna']);
                                        // Convert hex to RGB
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
                                            <i class="fas fa-palette me-1"></i><?= htmlspecialchars($dc['warna']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="badge bg-light text-dark">
                                        <i class="far fa-calendar-alt me-1"></i><?= date('d/m/Y', strtotime($dc['created_at'])) ?>
                                    </span>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="edit.php?id=<?= $dc['id_dresscode'] ?>" class="btn btn-outline-warning btn-sm flex-grow-1">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </a>
                                    <a href="hapus.php?id=<?= $dc['id_dresscode'] ?>" class="btn btn-outline-danger btn-sm flex-grow-1">
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

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    <div class="text-center text-muted small mt-2">
        Menampilkan <?= count($dresscode_list) ?> dari <?= $total_dresscode ?> dresscode
    </div>
<?php endif; ?>

<!-- Statistics Cards - Moved to Bottom -->
<div class="row mt-4">
    <?php
    $count_aktif = $pdo->query("SELECT COUNT(*) FROM dresscode WHERE status = 'aktif'")->fetchColumn();
    $count_nonaktif = $pdo->query("SELECT COUNT(*) FROM dresscode WHERE status = 'nonaktif'")->fetchColumn();
    ?>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-success">
            <div class="stat-icon">
                <i class="fas fa-tshirt"></i>
            </div>
            <div class="stat-value"><?= $count_aktif ?></div>
            <div class="stat-label">Dresscode Aktif</div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-secondary">
            <div class="stat-icon" style="background: rgba(108, 117, 125, 0.1); color: #6c757d;">
                <i class="fas fa-eye-slash"></i>
            </div>
            <div class="stat-value"><?= $count_nonaktif ?></div>
            <div class="stat-label">Dresscode Nonaktif</div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon">
                <i class="fas fa-list"></i>
            </div>
            <div class="stat-value"><?= $total_dresscode ?></div>
            <div class="stat-label">Total Dresscode</div>
        </div>
    </div>
</div>

<style>
/* Custom styles for dresscode cards on mobile */
.dresscode-card {
    background: #fff;
}

.dresscode-card:last-child {
    border-bottom: none !important;
}

@media (max-width: 575.98px) {
    .search-form .input-group {
        width: 100% !important;
        margin-bottom: 0.5rem;
    }
    
    .search-form select {
        width: 100% !important;
        margin-bottom: 0.5rem;
    }
    
    .search-form .d-flex {
        width: 100%;
    }
    
    .search-form .d-flex .btn {
        flex: 1;
    }
}

/* Modal Detail Dresscode Styles */
.dresscode-profile-header {
    position: relative;
    overflow: hidden;
}

.dresscode-profile-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.5;
}

.dresscode-info-card {
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

.dresscode-info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.dresscode-info-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.dresscode-info-content {
    flex-grow: 1;
    min-width: 0;
}

.dresscode-info-content small {
    letter-spacing: 0.5px;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .dresscode-info-card {
        padding: 12px;
    }
    
    .dresscode-info-icon {
        width: 44px;
        height: 44px;
    }
    
    .dresscode-avatar-xl {
        width: 80px !important;
        height: 80px !important;
        font-size: 32px !important;
    }
    
    .dresscode-profile-header {
        padding: 2rem 1rem !important;
    }
}
</style>

<!-- Modal Detail Dresscode -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="detailModalLabel">
                    <i class="fas fa-tshirt me-2"></i>Detail Dresscode
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Profile Header -->
                <div class="dresscode-profile-header bg-success bg-gradient text-white p-4 text-center">
                    <div class="dresscode-avatar-xl bg-white text-success rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3 shadow" id="detailAvatar" style="width: 100px; height: 100px; font-size: 40px;">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <h4 class="mb-1 fw-bold" id="detailNamaPakaian">-</h4>
                    <span class="badge bg-white text-success fs-6" id="detailStatus">-</span>
                </div>
                
                <!-- Dresscode Info Cards -->
                <div class="p-4">
                    <!-- Warna Section -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="dresscode-info-icon dresscode-info-icon bg-success bg-opacity-10 text-success rounded-3 p-3">
                                <i class="fas fa-palette fa-lg"></i>
                            </div>
                            <h6 class="mb-0 text-success fw-bold">Warna</h6>
                        </div>
                        <div class="card border-0 bg-light rounded-4">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center gap-3">
                                    <span id="detailWarnaBadge" class="badge" style="width: 60px; height: 30px;"></span>
                                    <span class="fw-bold text-dark" id="detailWarnaText">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Deskripsi Section -->
                    <div class="mb-0">
                        <div class="d-flex align-items-center mb-3">
                            <div class="dresscode-info-icon dresscode-info-icon bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                                <i class="fas fa-align-left fa-lg"></i>
                            </div>
                            <h6 class="mb-0 text-primary fw-bold">Deskripsi</h6>
                        </div>
                        <div class="card border-0 bg-light rounded-4">
                            <div class="card-body p-3">
                                <p class="mb-0 text-dark" id="detailDeskripsi">-</p>
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

<script>
// Fungsi untuk menampilkan modal detail
function showDetailModal(idDresscode, namaPakaian, deskripsi, warna, status) {
    // Set data dasar
    document.getElementById('detailNamaPakaian').textContent = namaPakaian || '-';
    document.getElementById('detailDeskripsi').textContent = deskripsi || 'Tidak ada deskripsi';
    document.getElementById('detailWarnaText').textContent = warna || '-';
    
    // Set warna badge
    const warnaBadge = document.getElementById('detailWarnaBadge');
    if (warna) {
        // Convert hex to RGB untuk text color
        const hex = ltrim(warna, '#');
        let textColor = '#000000';
        if (hex.length === 6) {
            const r = hexdec(substr(hex, 0, 2));
            const g = hexdec(substr(hex, 2, 2));
            const b = hexdec(substr(hex, 4, 2));
            const brightness = ((r * 299) + (g * 587) + (b * 114)) / 1000;
            textColor = brightness > 128 ? '#000000' : '#ffffff';
        }
        warnaBadge.style.backgroundColor = warna;
        warnaBadge.style.color = textColor;
        warnaBadge.style.border = '1px solid ' + warna;
        warnaBadge.textContent = warna;
    } else {
        warnaBadge.style.backgroundColor = '#e9ecef';
        warnaBadge.style.color = '#6c757d';
        warnaBadge.style.border = '1px solid #dee2e6';
        warnaBadge.textContent = '-';
    }
    
    // Set status badge
    const statusEl = document.getElementById('detailStatus');
    if (status === 'aktif') {
        statusEl.className = 'badge bg-white text-success fs-6';
        statusEl.innerHTML = '<i class="fas fa-check-circle me-1"></i>Aktif';
    } else {
        statusEl.className = 'badge bg-white text-secondary fs-6';
        statusEl.innerHTML = '<i class="fas fa-times-circle me-1"></i>Nonaktif';
    }
    
    // Tampilkan modal
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

// Helper function untuk substr
function substr(str, start, length) {
    return str.substring(start, start + length);
}

// Helper function untuk hexdec
function hexdec(hex) {
    return parseInt(hex, 16);
}

// Helper function untuk ltrim
function ltrim(str, char) {
    return str.replace(new RegExp('^' + char + '+'), '');
}
</script>

<?php include '../../includes/footer.php'; ?>

