<?php
/**
 * Absensi Latihan - Form Input Absensi
 * Menampilkan form checkbox untuk mengabsen anggota pada jadwal tertentu
 */

// Definisikan BASE_URL jika belum ada
if (!defined('BASE_URL')) {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/hadrahin';
    define('BASE_URL', $base_url);
}

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

$page_title = "Input Absensi Latihan";

// Get current user role
$user_peran = $_SESSION['peran'] ?? 'anggota';
$user_id = $_SESSION['user_id'] ?? 0;

// Check permission - only admin and pembina can access
if (!in_array($user_peran, ['admin', 'pembina'])) {
    header('Location: ../dashboard/' . $user_peran . '.php?msg=access_denied');
    exit;
}

// Get jadwal ID from URL
$id_jadwal = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_jadwal <= 0) {
    header('Location: index.php?msg=error');
    exit;
}

// Get jadwal details
$stmt = $pdo->prepare("SELECT * FROM jadwal_latihan WHERE id_jadwal = ?");
$stmt->execute([$id_jadwal]);
$jadwal = $stmt->fetch();

if (!$jadwal) {
    header('Location: index.php?msg=error');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Get all anggota (users with peran = 'anggota')
        $stmt = $pdo->query("SELECT id_user, nama_lengkap FROM user WHERE peran = 'anggota' AND status_aktif = 1 ORDER BY nama_lengkap");
        $anggotas = $stmt->fetchAll();

        foreach ($anggotas as $anggota) {
            $id_user = $anggota['id_user'];
            $status_key = "status_$id_user";
            $status = isset($_POST[$status_key]) ? $_POST[$status_key] : 'alpa';

            // Validate status
            if (!in_array($status, ['hadir', 'izin', 'alpa'])) {
                $status = 'alpa';
            }

            // Check if absensi already exists
            $stmt = $pdo->prepare("SELECT id_absen FROM absen_latihan WHERE id_jadwal = ? AND id_user = ?");
            $stmt->execute([$id_jadwal, $id_user]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE absen_latihan SET
                    status_hadir = ?,
                    jam_absen = NOW(),
                    updated_at = NOW(),
                    user_modified = ?
                    WHERE id_absen = ?");
                $stmt->execute([$status, $user_id, $existing['id_absen']]);
            } else {
                // Insert new record
                $stmt = $pdo->prepare("INSERT INTO absen_latihan
                    (id_jadwal, id_user, status_hadir, jam_absen, user_record, user_modified)
                    VALUES (?, ?, ?, NOW(), ?, ?)");
                $stmt->execute([$id_jadwal, $id_user, $status, $user_id, $user_id]);
            }
        }

        // Update jadwal status to 'selesai' after absensi is completed
        $stmt = $pdo->prepare("UPDATE jadwal_latihan SET status = 'selesai', updated_at = NOW() WHERE id_jadwal = ?");
        $stmt->execute([$id_jadwal]);

        $pdo->commit();

        // Redirect back to jadwal latihan with success message
        header('Location: ../jadwallatihan/index.php?msg=absen_sukes');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error saving absensi: " . $e->getMessage());
        $error_message = "Terjadi kesalahan saat menyimpan data absensi.";
    }
}

// Get search parameter for filtering anggota
$search_anggota = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_anggota = htmlspecialchars(strip_tags($search_anggota), ENT_QUOTES, 'UTF-8');
if (strlen($search_anggota) > 100) $search_anggota = substr($search_anggota, 0, 100);

// Get all anggota with optional search filter
if (!empty($search_anggota)) {
    $stmt = $pdo->prepare("SELECT id_user, nama_lengkap, username FROM user WHERE peran = 'anggota' AND status_aktif = 1 AND (nama_lengkap LIKE ? OR username LIKE ?) ORDER BY nama_lengkap");
    $search_param = "%$search_anggota%";
    $stmt->execute([$search_param, $search_param]);
} else {
    $stmt = $pdo->query("SELECT id_user, nama_lengkap, username FROM user WHERE peran = 'anggota' AND status_aktif = 1 ORDER BY nama_lengkap");
}
$anggotas = $stmt->fetchAll();

// Kosongkan array status - semua anggota akan ditampilkan tanpa ada yang tercentang
$absensi_status = [];

// Include header dengan sidebar
include '../../includes/header.php';
?>

<!-- Jadwal Info Card -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-clipboard-check me-2"></i>
            Absensi Latihan
        </h5>
    </div>
    <div class="card-body">
        <?php
        $tanggal = new DateTime($jadwal['tanggal']);
        ?>
        <div class="row">
            <div class="col-md-6">
                <h6>Informasi Jadwal</h6>
                <div class="d-flex align-items-center mb-2">
                    <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                        <div class="text-center">
                            <?= $tanggal->format('d') ?>
                            <div style="font-size: 10px;"><?= $tanggal->format('M') ?></div>
                        </div>
                    </div>
                    <div>
                        <h6 class="mb-0"><?= $tanggal->format('l, d F Y') ?></h6>
                        <small class="text-muted">
                            <i class="far fa-clock me-1"></i><?= date('H:i', strtotime($jadwal['jam_mulai'])) ?>
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <h6>Lokasi & Catatan</h6>
                <p class="mb-1">
                    <i class="fas fa-map-marker-alt text-danger me-2"></i>
                    <strong><?= htmlspecialchars($jadwal['lokasi']) ?></strong>
                </p>
                <?php if (!empty($jadwal['catatan'])): ?>
                    <p class="mb-0 text-muted small">
                        <i class="fas fa-sticky-note me-2"></i>
                        <?= htmlspecialchars($jadwal['catatan']) ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Error Message -->
<?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?= $error_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Modal Konfirmasi Simpan Absensi -->
<div class="modal fade" id="konfirmasiModal" tabindex="-1" aria-labelledby="konfirmasiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="konfirmasiModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Simpan Absensi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-clipboard-check fa-4x text-primary mb-3 d-block"></i>
                <h5 class="mb-3">Apakah Anda yakin ingin menyimpan data absensi ini?</h5>
                <p class="text-muted mb-0">Pastikan semua data absensi sudah benar sebelum disimpan.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary btn-lg px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Batal
                </button>
                <button type="button" class="btn btn-primary btn-lg px-4" id="konfirmasiSimpan">
                    <i class="fas fa-check me-2"></i>Ya, Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Absensi Form -->
<form method="GET" id="searchForm">
    <input type="hidden" name="id" value="<?= $id_jadwal ?>">
</form>

<form method="POST" id="absensiForm">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0">
                    <i class="fas fa-users me-2"></i>
                    Daftar Anggota (<span id="totalAnggota"><?= count($anggotas) ?></span> orang)
                </h6>
                <div class="d-flex align-items-center gap-2">
                    <div class="input-group" style="width: 250px;">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control form-control-sm" 
                               name="search" placeholder="Cari nama..."
                               value="<?= htmlspecialchars($search_anggota) ?>"
                               form="searchForm">
                        <?php if (!empty($search_anggota)): ?>
                            <a href="?id=<?= $id_jadwal ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="setAllStatus('hadir')">
                            <i class="fas fa-check-circle me-1"></i>Hadir Semua
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="setAllStatus('izin')">
                            <i class="fas fa-exclamation-triangle me-1"></i>Izin Semua
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="setAllStatus('alpa')">
                            <i class="fas fa-times-circle me-1"></i>Alpa Semua
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($anggotas)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-users fa-3x mb-3 d-block text-secondary"></i>
                    <h6>
                        <?php if (!empty($search_anggota)): ?>
                            Tidak ada anggota ditemukan untuk "<strong><?= htmlspecialchars($search_anggota) ?></strong>"
                        <?php else: ?>
                            Tidak ada anggota yang terdaftar
                        <?php endif; ?>
                    </h6>
                    <p class="mb-0">
                        <?php if (!empty($search_anggota)): ?>
                            <a href="?id=<?= $id_jadwal ?>" class="text-primary">Tampilkan semua anggota</a>
                        <?php else: ?>
                            Silakan tambahkan anggota terlebih dahulu.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($anggotas as $anggota): ?>
                        <?php
                        // Default: tidak ada yang tercentang (kosong)
                        $current_status = '';
                        $status_class = 'border-secondary';
                        $checked_hadir = '';
                        $checked_izin = '';
                        $checked_alpa = '';
                        ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card h-100 absensi-card <?= $status_class ?>" data-user-id="<?= $anggota['id_user'] ?>">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 14px;">
                                            <?= strtoupper(substr($anggota['nama_lengkap'], 0, 1)) ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 small fw-bold"><?= htmlspecialchars($anggota['nama_lengkap']) ?></h6>
                                            <small class="text-muted">@<?= htmlspecialchars($anggota['username']) ?></small>
                                        </div>
                                    </div>

                                    <div class="absensi-options">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input status-radio" type="radio"
                                                   name="status_<?= $anggota['id_user'] ?>" value="hadir"
                                                   id="hadir_<?= $anggota['id_user'] ?>" <?= $checked_hadir ?>>
                                            <label class="form-check-label text-success fw-bold" for="hadir_<?= $anggota['id_user'] ?>">
                                                <i class="fas fa-check-circle me-1"></i>Hadir
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input status-radio" type="radio"
                                                   name="status_<?= $anggota['id_user'] ?>" value="izin"
                                                   id="izin_<?= $anggota['id_user'] ?>" <?= $checked_izin ?>>
                                            <label class="form-check-label text-warning fw-bold" for="izin_<?= $anggota['id_user'] ?>">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Izin
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input status-radio" type="radio"
                                                   name="status_<?= $anggota['id_user'] ?>" value="alpa"
                                                   id="alpa_<?= $anggota['id_user'] ?>" <?= $checked_alpa ?>>
                                            <label class="form-check-label text-danger fw-bold" for="alpa_<?= $anggota['id_user'] ?>">
                                                <i class="fas fa-times-circle me-1"></i>Alpa
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($anggotas)): ?>
            <div class="card-footer text-center">
                <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#konfirmasiModal">
                    <i class="fas fa-save me-2"></i>Simpan Absensi
                </button>
                <a href="../jadwallatihan/index.php" class="btn btn-secondary btn-lg ms-2">
                    <i class="fas fa-arrow-left me-1"></i>Kembali
                </a>
            </div>
        <?php endif; ?>
    </div>
</form>

<style>
.absensi-card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.absensi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.absensi-options {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.absensi-options .form-check {
    margin: 0;
}

.absensi-options .form-check-input:checked + .form-check-label {
    font-weight: bold;
}

.bg-light-success {
    background-color: rgba(25, 135, 84, 0.1) !important;
}

.bg-light-warning {
    background-color: rgba(255, 193, 7, 0.1) !important;
}

.bg-light-danger {
    background-color: rgba(220, 53, 69, 0.1) !important;
}
</style>

<script>
// Function to set all status
function setAllStatus(status) {
    const radios = document.querySelectorAll(`input[type="radio"][value="${status}"]`);
    radios.forEach(radio => {
        radio.checked = true;
        updateCardStatus(radio);
    });
}

// Function to update card appearance based on selected status
function updateCardStatus(radio) {
    const card = radio.closest('.absensi-card');
    const userId = card.dataset.userId;
    const status = radio.value;

    // Remove all status classes
    card.classList.remove('border-success', 'bg-light-success', 'border-warning', 'bg-light-warning', 'border-danger', 'bg-light-danger', 'border-secondary');

    // Add appropriate class
    switch(status) {
        case 'hadir':
            card.classList.add('border-success', 'bg-light-success');
            break;
        case 'izin':
            card.classList.add('border-warning', 'bg-light-warning');
            break;
        case 'alpa':
            card.classList.add('border-danger', 'bg-light-danger');
            break;
        default:
            card.classList.add('border-secondary');
    }
}

// Add event listeners to radio buttons
document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('.status-radio');
    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            updateCardStatus(this);
        });
    });

    // Auto-submit search form on typing with debounce
    const searchInput = document.querySelector('input[name="search"]');
    const searchForm = document.getElementById('searchForm');
    let debounceTimer;

    if (searchInput && searchForm) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                searchForm.submit();
            }, 500); // Wait 500ms after typing stops
        });
    }

    // Modal confirmation handling
    const saveButton = document.querySelector('button[data-bs-target="#konfirmasiModal"]');
    const konfirmasiSimpan = document.getElementById('konfirmasiSimpan');
    const form = document.getElementById('absensiForm');
    const modalBody = document.querySelector('#konfirmasiModal .modal-body');
    const modalTitle = document.querySelector('#konfirmasiModal .modal-title');
    const modalHeader = document.querySelector('#konfirmasiModal .modal-header');

    // Check if all attendance data is filled
    function checkAllDataFilled() {
        const allRadios = document.querySelectorAll('.status-radio');
        let allChecked = true;
        const checkedUsers = new Set();

        allRadios.forEach(radio => {
            if (radio.checked) {
                checkedUsers.add(radio.name);
            }
        });

        // Get total unique radio groups (one per user)
        const totalUsers = document.querySelectorAll('.absensi-card').length;
        
        if (checkedUsers.size < totalUsers) {
            allChecked = false;
        }

        return { allChecked, missingCount: totalUsers - checkedUsers.size };
    }

    // Save button click handler
    if (saveButton && form) {
        saveButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            const validation = checkAllDataFilled();
            
            if (!validation.allChecked) {
                // Show error modal instead of confirmation
                const modal = new bootstrap.Modal(document.getElementById('konfirmasiModal'));
                
                // Update modal content for error
                modalHeader.classList.remove('bg-warning', 'text-dark');
                modalHeader.classList.add('bg-danger', 'text-white');
                modalTitle.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Data Belum Lengkap';
                modalBody.innerHTML = `
                    <i class="fas fa-exclamation-triangle fa-4x text-danger mb-3 d-block"></i>
                    <h5 class="mb-3">Ada ${validation.missingCount} anggota yang belum diabsen!</h5>
                    <p class="text-muted mb-0">Silakan isi status absensi untuk semua anggota sebelum menyimpan.</p>
                `;
                
                // Change confirm button to just close
                const footer = document.querySelector('#konfirmasiModal .modal-footer');
                footer.innerHTML = `
                    <button type="button" class="btn btn-secondary btn-lg px-4" data-bs-dismiss="modal">
                        <i class="fas fa-check me-2"></i>Mengerti
                    </button>
                `;
                
                modal.show();
                
                // Remove backdrop manually when modal is hidden
                document.getElementById('konfirmasiModal').addEventListener('hidden.bs.modal', function() {
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    resetModalContent();
                });
                
                return false;
            }
            
            // If all data is filled, show confirmation
            resetModalContent();
            const modal = new bootstrap.Modal(document.getElementById('konfirmasiModal'));
            modal.show();
        });
    }

    // Reset modal content function
    function resetModalContent() {
        modalHeader.classList.remove('bg-danger', 'text-white');
        modalHeader.classList.add('bg-warning', 'text-dark');
        modalTitle.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Simpan Absensi';
        modalBody.innerHTML = `
            <i class="fas fa-clipboard-check fa-4x text-primary mb-3 d-block"></i>
            <h5 class="mb-3">Apakah Anda yakin ingin menyimpan data absensi ini?</h5>
            <p class="text-muted mb-0">Pastikan semua data absensi sudah benar sebelum disimpan.</p>
        `;
        
        const footer = document.querySelector('#konfirmasiModal .modal-footer');
        footer.innerHTML = `
            <button type="button" class="btn btn-secondary btn-lg px-4" data-bs-dismiss="modal">
                <i class="fas fa-times me-2"></i>Batal
            </button>
            <button type="button" class="btn btn-primary btn-lg px-4" id="konfirmasiSimpan">
                <i class="fas fa-check me-2"></i>Ya, Simpan
            </button>
        `;
        
        // Re-attach confirm handler
        document.getElementById('konfirmasiSimpan').addEventListener('click', function() {
            form.submit();
            const modal = bootstrap.Modal.getInstance(document.getElementById('konfirmasiModal'));
            if (modal) {
                modal.hide();
            }
        });
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
