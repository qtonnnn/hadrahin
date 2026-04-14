<?php
/**
 * Absensi Latihan - Form Input Absensi (IMPROVED VERSION)
 * Menampilkan form checkbox untuk mengabsen anggota pada jadwal tertentu
 * 
 * IMPROVEMENTS:
 * - Client-side search filtering (no page reload)
 * - Better progress tracking
 * - Improved validation with localStorage
 * - Better UX with visual feedback
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

// Check permission - only admin can access
if ($user_peran !== 'admin') {
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

// Get existing attendance data for this jadwal (to populate form with saved data)
$absensi_status = [];
if ($jadwal) {
    $stmt = $pdo->prepare("SELECT id_user, status_hadir FROM absen_latihan WHERE id_jadwal = ?");
    $stmt->execute([$id_jadwal]);
    $existing_absensi = $stmt->fetchAll();
    foreach ($existing_absensi as $absen) {
        $absensi_status[$absen['id_user']] = $absen['status_hadir'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Server-side validation: Get total count of all active anggota
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM user WHERE peran = 'anggota' AND status_aktif = 1");
    $totalAnggota = $stmt->fetch()['total'];
    
    // Get all anggota (users with peran = 'anggota')
    $stmt = $pdo->query("SELECT id_user, nama_lengkap FROM user WHERE peran = 'anggota' AND status_aktif = 1 ORDER BY nama_lengkap");
    $anggotas = $stmt->fetchAll();
    
    // Check if all anggota have status submitted (server-side validation)
    $submittedCount = 0;
    $missing_members = [];
    
    foreach ($anggotas as $anggota) {
        $id_user = $anggota['id_user'];
        $status_key = "status_$id_user";
        if (isset($_POST[$status_key]) && in_array($_POST[$status_key], ['hadir', 'izin', 'alpa'])) {
            $submittedCount++;
        } else {
            $missing_members[] = $anggota['nama_lengkap'];
        }
    }
    
    // If not all members have status, show error with detailed info
    if ($submittedCount < $totalAnggota) {
        $missing_count = count($missing_members);
        $error_message = "<strong>Masih ada {$missing_count} anggota yang belum diabsen!</strong><br><br>";
        $error_message .= "Anggota yang belum diabsen:<br>";
        $error_message .= "<ul class='mb-0 text-start'>";
        
        // Show first 10 missing members
        $display_count = min(10, count($missing_members));
        for ($i = 0; $i < $display_count; $i++) {
            $error_message .= "<li>" . htmlspecialchars($missing_members[$i]) . "</li>";
        }
        
        if (count($missing_members) > 10) {
            $error_message .= "<li class='text-muted'><em>... dan " . (count($missing_members) - 10) . " anggota lainnya</em></li>";
        }
        
        $error_message .= "</ul>";
    } else {
        try {
            $pdo->beginTransaction();

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
            header('Location: ../jadwallatihan/index.php?msg=absen_sukses');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Error saving absensi: " . $e->getMessage());
            $error_message = "Terjadi kesalahan saat menyimpan data absensi.";
        }
    }
}

// Get all anggota (ALWAYS load all, filtering will be done client-side)
$stmt = $pdo->query("SELECT id_user, nama_lengkap, username FROM user WHERE peran = 'anggota' AND status_aktif = 1 ORDER BY nama_lengkap");
$anggotas = $stmt->fetchAll();

// Get total count of all active anggota
$stmt = $pdo->query("SELECT COUNT(*) as total FROM user WHERE peran = 'anggota' AND status_aktif = 1");
$totalAnggota = $stmt->fetch()['total'];

// Include header dengan sidebar
include '../../includes/header.php';
?>

<!-- Hidden input for total anggota count -->
<input type="hidden" id="totalAnggotaGlobal" value="<?= $totalAnggota ?>">

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
        <div class="d-flex align-items-start">
            <i class="fas fa-exclamation-triangle me-3 mt-1" style="font-size: 1.5rem;"></i>
            <div class="flex-grow-1">
                <?= $error_message ?>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Progress Tracker Card -->
<div class="card mb-3">
    <div class="card-body py-3">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center">
                    <i class="fas fa-tasks text-primary me-3" style="font-size: 1.5rem;"></i>
                    <div class="flex-grow-1">
                        <h6 class="mb-1">Progress Absensi</h6>
                        <div class="progress" style="height: 20px;">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 0%">
                                <span id="progressText" class="fw-bold">0%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-2 mt-md-0">
                <div class="d-flex justify-content-md-end gap-2">
                    <span class="badge bg-success" id="hadirBadge">
                        <i class="fas fa-check-circle me-1"></i>Hadir: <strong id="hadirCount">0</strong>
                    </span>
                    <span class="badge bg-warning text-dark" id="izinBadge">
                        <i class="fas fa-exclamation-triangle me-1"></i>Izin: <strong id="izinCount">0</strong>
                    </span>
                    <span class="badge bg-danger" id="alpaBadge">
                        <i class="fas fa-times-circle me-1"></i>Alpa: <strong id="alpaCount">0</strong>
                    </span>
                    <span class="badge bg-secondary" id="belumBadge">
                        <i class="fas fa-question-circle me-1"></i>Belum: <strong id="belumCount"><?= $totalAnggota ?></strong>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

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
            <div class="modal-body text-center py-4" id="modalBodyContent">
                <i class="fas fa-clipboard-check fa-4x text-primary mb-3 d-block"></i>
                <h5 class="mb-3">Apakah Anda yakin ingin menyimpan data absensi ini?</h5>
                <p class="text-muted mb-0">Pastikan semua data absensi sudah benar sebelum disimpan.</p>
                
                <!-- Summary akan ditampilkan di sini -->
                <div id="summaryContainer" class="mt-3"></div>
            </div>
            <div class="modal-footer justify-content-center" id="modalFooterContent">
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
<form method="POST" id="absensiForm">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0">
                    <i class="fas fa-users me-2"></i>
                    Daftar Anggota 
                    <span class="badge bg-primary" id="displayCountBadge">
                        Menampilkan: <span id="visibleCount"><?= count($anggotas) ?></span> / <?= $totalAnggota ?>
                    </span>
                </h6>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Search Box -->
                    <div class="input-group" style="width: 250px;">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control form-control-sm" 
                               id="searchInput" placeholder="Cari nama atau username..."
                               autocomplete="off">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSearch" style="display: none;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <!-- Bulk Action Buttons -->
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="setAllStatus('hadir')" title="Set semua TERLIHAT menjadi Hadir">
                            <i class="fas fa-check-circle me-1"></i>Hadir Semua
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="setAllStatus('izin')" title="Set semua TERLIHAT menjadi Izin">
                            <i class="fas fa-exclamation-triangle me-1"></i>Izin Semua
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="setAllStatus('alpa')" title="Set semua TERLIHAT menjadi Alpa">
                            <i class="fas fa-times-circle me-1"></i>Alpa Semua
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Search Info -->
            <div id="searchInfo" class="alert alert-info mt-2 mb-0 py-2" style="display: none;">
                <small>
                    <i class="fas fa-info-circle me-1"></i>
                    Menampilkan hasil pencarian. Tombol "Hadir/Izin/Alpa Semua" hanya akan mengubah anggota yang terlihat.
                    <button type="button" class="btn btn-sm btn-link p-0 ms-2" id="clearSearchFromInfo">
                        Tampilkan Semua Anggota
                    </button>
                </small>
            </div>
        </div>
        <div class="card-body">
            <div id="noResultsMessage" class="text-center py-5 text-muted" style="display: none;">
                <i class="fas fa-search fa-3x mb-3 d-block text-secondary"></i>
                <h6>Tidak ada anggota ditemukan untuk "<strong id="searchTermDisplay"></strong>"</h6>
                <p class="mb-0">
                    <button type="button" class="btn btn-sm btn-primary" id="clearSearchFromNoResults">
                        <i class="fas fa-times me-1"></i>Hapus Pencarian
                    </button>
                </p>
            </div>
            
            <div class="row" id="anggotaContainer">
                <?php foreach ($anggotas as $anggota): ?>
                    <?php
                    // Check if there's existing attendance data
                    $existing_status = $absensi_status[$anggota['id_user']] ?? '';
                    
                    // Set status class based on existing status
                    switch($existing_status) {
                        case 'hadir':
                            $status_class = 'border-success bg-light-success';
                            break;
                        case 'izin':
                            $status_class = 'border-warning bg-light-warning';
                            break;
                        case 'alpa':
                            $status_class = 'border-danger bg-light-danger';
                            break;
                        default:
                            $status_class = 'border-secondary';
                    }
                    
                    // Set checked attributes
                    $checked_hadir = ($existing_status === 'hadir') ? 'checked' : '';
                    $checked_izin = ($existing_status === 'izin') ? 'checked' : '';
                    $checked_alpa = ($existing_status === 'alpa') ? 'checked' : '';
                    ?>
                    <div class="col-md-6 col-lg-4 mb-3 anggota-item" 
                         data-user-id="<?= $anggota['id_user'] ?>"
                         data-name="<?= strtolower(htmlspecialchars($anggota['nama_lengkap'])) ?>"
                         data-username="<?= strtolower(htmlspecialchars($anggota['username'])) ?>">
                        <div class="card h-100 absensi-card <?= $status_class ?>">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 14px;">
                                        <?= strtoupper(substr($anggota['nama_lengkap'], 0, 1)) ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 small fw-bold anggota-name"><?= htmlspecialchars($anggota['nama_lengkap']) ?></h6>
                                        <small class="text-muted anggota-username">@<?= htmlspecialchars($anggota['username']) ?></small>
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
        </div>

        <?php if (!empty($anggotas)): ?>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <a href="../jadwallatihan/index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Kembali
                    </a>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-danger" onclick="clearAllData()" title="Reset semua pilihan">
                            <i class="fas fa-redo me-1"></i>Reset Semua
                        </button>
                        <button type="button" class="btn btn-primary btn-lg" id="saveButton">
                            <i class="fas fa-save me-2"></i>Simpan Absensi
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</form>

<style>
.absensi-card {
    transition: all 0.3s ease;
    cursor: pointer;
    border-width: 2px;
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

/* Progress Bar Animations */
.progress-bar {
    transition: width 0.6s ease, background-color 0.3s ease;
}

/* Highlight search results */
.search-highlight {
    background-color: yellow;
    font-weight: bold;
    padding: 0 2px;
}

/* Smooth fade for filtered items */
.anggota-item {
    transition: opacity 0.3s ease;
}

.anggota-item.hidden {
    display: none !important;
}

/* Mobile Responsive Button Group Styles */
@media (max-width: 768px) {
    .btn-group[role="group"] {
        display: flex;
        flex-direction: column;
        width: 100%;
    }
    
    .btn-group[role="group"] .btn {
        margin-bottom: 0.5rem;
        border-radius: 0.375rem !important;
        width: 100%;
        text-align: center;
        justify-content: center;
    }
    
    .btn-group[role="group"] {
        border-radius: 0;
        box-shadow: none;
        border: none;
    }
    
    .btn-group[role="group"] .btn:not(:last-child) {
        margin-right: 0;
    }
    
    #displayCountBadge {
        display: block !important;
        margin-top: 0.5rem;
    }
}

@media (max-width: 576px) {
    .btn-group[role="group"] .btn {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
    
    .btn-group[role="group"] .btn i {
        margin-right: 0.5rem;
    }
    
    .input-group {
        width: 100% !important;
    }
}

/* Pulse animation for unsaved changes indicator */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.has-unsaved-changes {
    animation: pulse 2s infinite;
}
</style>

<script>
// =======================
// GLOBAL VARIABLES
// =======================
const currentJadwalId = <?= $id_jadwal ?>;
const totalAnggotaGlobal = <?= $totalAnggota ?>;
let searchTimeout = null;

// =======================
// LOCALSTORAGE FUNCTIONS
// =======================

function saveStatusToStorage() {
    const statuses = {};
    const radios = document.querySelectorAll('.status-radio:checked');
    
    radios.forEach(radio => {
        const name = radio.name;
        const value = radio.value;
        statuses[name] = value;
    });
    
    localStorage.setItem('unsaved_absensi_' + currentJadwalId, JSON.stringify(statuses));
    updateUnsavedIndicator();
}

function loadStatusFromStorage() {
    const savedStatuses = localStorage.getItem('unsaved_absensi_' + currentJadwalId);
    
    if (savedStatuses) {
        const statuses = JSON.parse(savedStatuses);
        
        Object.keys(statuses).forEach(name => {
            const radio = document.querySelector(`input[name="${name}"][value="${statuses[name]}"]`);
            if (radio) {
                radio.checked = true;
                updateCardStatus(radio);
            }
        });
        
        updateProgress();
    }
}

function clearUnsavedStatus() {
    localStorage.removeItem('unsaved_absensi_' + currentJadwalId);
    updateUnsavedIndicator();
}

function updateUnsavedIndicator() {
    const savedStatuses = localStorage.getItem('unsaved_absensi_' + currentJadwalId);
    const saveButton = document.getElementById('saveButton');
    
    if (savedStatuses && Object.keys(JSON.parse(savedStatuses)).length > 0) {
        saveButton.classList.add('has-unsaved-changes');
    } else {
        saveButton.classList.remove('has-unsaved-changes');
    }
}

// =======================
// PROGRESS TRACKING
// =======================

function updateProgress() {
    const savedStatuses = localStorage.getItem('unsaved_absensi_' + currentJadwalId);
    const statuses = savedStatuses ? JSON.parse(savedStatuses) : {};
    
    // Count by status
    let hadirCount = 0;
    let izinCount = 0;
    let alpaCount = 0;
    
    Object.values(statuses).forEach(status => {
        if (status === 'hadir') hadirCount++;
        else if (status === 'izin') izinCount++;
        else if (status === 'alpa') alpaCount++;
    });
    
    const totalFilled = hadirCount + izinCount + alpaCount;
    const belumCount = totalAnggotaGlobal - totalFilled;
    const percentage = (totalFilled / totalAnggotaGlobal) * 100;
    
    // Update progress bar
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    
    progressBar.style.width = percentage + '%';
    progressText.textContent = Math.round(percentage) + '%';
    
    // Update progress bar color
    progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated';
    if (percentage === 100) {
        progressBar.classList.add('bg-success');
    } else if (percentage >= 75) {
        progressBar.classList.add('bg-info');
    } else if (percentage >= 50) {
        progressBar.classList.add('bg-warning');
    } else {
        progressBar.classList.add('bg-danger');
    }
    
    // Update badges
    document.getElementById('hadirCount').textContent = hadirCount;
    document.getElementById('izinCount').textContent = izinCount;
    document.getElementById('alpaCount').textContent = alpaCount;
    document.getElementById('belumCount').textContent = belumCount;
}

// =======================
// CARD STATUS UPDATE
// =======================

function updateCardStatus(radio) {
    const card = radio.closest('.absensi-card');
    const status = radio.value;

    // Remove all status classes
    card.classList.remove('border-success', 'bg-light-success', 
                         'border-warning', 'bg-light-warning', 
                         'border-danger', 'bg-light-danger', 
                         'border-secondary');

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

// =======================
// BULK STATUS SETTER
// =======================

function setAllStatus(status) {
    // Only set status for VISIBLE cards
    const visibleCards = document.querySelectorAll('.anggota-item:not(.hidden)');
    
    visibleCards.forEach(item => {
        const radio = item.querySelector(`input[value="${status}"]`);
        if (radio) {
            radio.checked = true;
            updateCardStatus(radio);
        }
    });
    
    saveStatusToStorage();
    updateProgress();
    
    // Show notification
    const count = visibleCards.length;
    const statusText = status === 'hadir' ? 'Hadir' : (status === 'izin' ? 'Izin' : 'Alpa');
    showNotification(`${count} anggota ditandai sebagai ${statusText}`, 'success');
}

// =======================
// CLEAR ALL DATA
// =======================

function clearAllData() {
    if (confirm('Apakah Anda yakin ingin mereset semua pilihan absensi?')) {
        const radios = document.querySelectorAll('.status-radio');
        radios.forEach(radio => {
            radio.checked = false;
            const card = radio.closest('.absensi-card');
            card.classList.remove('border-success', 'bg-light-success', 
                                'border-warning', 'bg-light-warning', 
                                'border-danger', 'bg-light-danger');
            card.classList.add('border-secondary');
        });
        
        clearUnsavedStatus();
        updateProgress();
        showNotification('Semua pilihan absensi telah direset', 'info');
    }
}

// =======================
// SEARCH FUNCTIONALITY
// =======================

function performSearch(searchTerm) {
    const items = document.querySelectorAll('.anggota-item');
    const noResults = document.getElementById('noResultsMessage');
    const searchInfo = document.getElementById('searchInfo');
    const container = document.getElementById('anggotaContainer');
    let visibleCount = 0;
    
    searchTerm = searchTerm.toLowerCase().trim();
    
    items.forEach(item => {
        const name = item.dataset.name;
        const username = item.dataset.username;
        
        if (searchTerm === '' || name.includes(searchTerm) || username.includes(searchTerm)) {
            item.classList.remove('hidden');
            visibleCount++;
        } else {
            item.classList.add('hidden');
        }
    });
    
    // Update visible count
    document.getElementById('visibleCount').textContent = visibleCount;
    
    // Show/hide messages
    if (visibleCount === 0 && searchTerm !== '') {
        noResults.style.display = 'block';
        container.style.display = 'none';
        document.getElementById('searchTermDisplay').textContent = searchTerm;
    } else {
        noResults.style.display = 'none';
        container.style.display = 'flex';
    }
    
    // Show/hide search info
    if (searchTerm !== '') {
        searchInfo.style.display = 'block';
        document.getElementById('clearSearch').style.display = 'block';
    } else {
        searchInfo.style.display = 'none';
        document.getElementById('clearSearch').style.display = 'none';
    }
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    performSearch('');
}

// =======================
// VALIDATION & SAVE
// =======================

function validateBeforeSave() {
    const savedStatuses = localStorage.getItem('unsaved_absensi_' + currentJadwalId);
    const statuses = savedStatuses ? JSON.parse(savedStatuses) : {};
    const totalFilled = Object.keys(statuses).length;
    
    if (totalFilled < totalAnggotaGlobal) {
        const missing = totalAnggotaGlobal - totalFilled;
        return {
            valid: false,
            message: `Masih ada ${missing} anggota yang belum diabsen!`,
            missing: missing
        };
    }
    
    return {
        valid: true,
        statuses: statuses
    };
}

function showSaveModal() {
    const validation = validateBeforeSave();
    const modal = new bootstrap.Modal(document.getElementById('konfirmasiModal'));
    const modalBody = document.getElementById('modalBodyContent');
    const modalFooter = document.getElementById('modalFooterContent');
    const modalHeader = document.querySelector('#konfirmasiModal .modal-header');
    const modalTitle = document.getElementById('konfirmasiModalLabel');
    
    if (!validation.valid) {
        // Show error modal
        modalHeader.className = 'modal-header bg-danger text-white';
        modalTitle.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Data Tidak Lengkap';
        
        modalBody.innerHTML = `
            <i class="fas fa-exclamation-triangle fa-4x text-danger mb-3 d-block"></i>
            <h5 class="mb-3">${validation.message}</h5>
            <p class="text-muted mb-0">Silakan lengkapi status absensi untuk semua anggota sebelum menyimpan.</p>
        `;
        
        modalFooter.innerHTML = `
            <button type="button" class="btn btn-secondary btn-lg px-4" data-bs-dismiss="modal">
                <i class="fas fa-check me-2"></i>Mengerti
            </button>
        `;
    } else {
        // Show confirmation with summary
        modalHeader.className = 'modal-header bg-warning text-dark';
        modalTitle.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Simpan Absensi';
        
        // Count statuses
        let hadirCount = 0, izinCount = 0, alpaCount = 0;
        Object.values(validation.statuses).forEach(status => {
            if (status === 'hadir') hadirCount++;
            else if (status === 'izin') izinCount++;
            else if (status === 'alpa') alpaCount++;
        });
        
        modalBody.innerHTML = `
            <i class="fas fa-clipboard-check fa-4x text-primary mb-3 d-block"></i>
            <h5 class="mb-3">Apakah Anda yakin ingin menyimpan data absensi ini?</h5>
            <div id="summaryContainer" class="alert alert-light">
                <h6 class="mb-2">Ringkasan Absensi:</h6>
                <div class="d-flex justify-content-center gap-3">
                    <span class="badge bg-success fs-6">
                        <i class="fas fa-check-circle me-1"></i>Hadir: ${hadirCount}
                    </span>
                    <span class="badge bg-warning text-dark fs-6">
                        <i class="fas fa-exclamation-triangle me-1"></i>Izin: ${izinCount}
                    </span>
                    <span class="badge bg-danger fs-6">
                        <i class="fas fa-times-circle me-1"></i>Alpa: ${alpaCount}
                    </span>
                </div>
            </div>
            <p class="text-muted mb-0 mt-2"><small>Pastikan semua data sudah benar sebelum disimpan.</small></p>
        `;
        
        modalFooter.innerHTML = `
            <button type="button" class="btn btn-secondary btn-lg px-4" data-bs-dismiss="modal">
                <i class="fas fa-times me-2"></i>Batal
            </button>
            <button type="button" class="btn btn-primary btn-lg px-4" id="konfirmasiSimpan">
                <i class="fas fa-check me-2"></i>Ya, Simpan
            </button>
        `;
        
        // Attach save handler
        document.getElementById('konfirmasiSimpan').addEventListener('click', function() {
            document.getElementById('absensiForm').submit();
            clearUnsavedStatus();
        });
    }
    
    modal.show();
    
    // Cleanup backdrop on close
    document.getElementById('konfirmasiModal').addEventListener('hidden.bs.modal', function() {
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
    }, { once: true });
}

// =======================
// NOTIFICATION HELPER
// =======================

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// =======================
// EVENT LISTENERS
// =======================

document.addEventListener('DOMContentLoaded', function() {
    // Load saved statuses
    loadStatusFromStorage();
    updateProgress();
    
    // Radio button change listeners
    const radios = document.querySelectorAll('.status-radio');
    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            updateCardStatus(this);
            saveStatusToStorage();
            updateProgress();
        });
    });
    
    // Initialize card status based on pre-selected radios
    radios.forEach(radio => {
        if (radio.checked) {
            updateCardStatus(radio);
        }
    });
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performSearch(this.value);
        }, 300);
    });
    
    // Clear search buttons
    document.getElementById('clearSearch').addEventListener('click', clearSearch);
    document.getElementById('clearSearchFromInfo').addEventListener('click', clearSearch);
    document.getElementById('clearSearchFromNoResults').addEventListener('click', clearSearch);
    
    // Save button
    document.getElementById('saveButton').addEventListener('click', function(e) {
        e.preventDefault();
        showSaveModal();
    });
    
    // Warn before leaving with unsaved changes
    window.addEventListener('beforeunload', function(e) {
        const savedStatuses = localStorage.getItem('unsaved_absensi_' + currentJadwalId);
        if (savedStatuses && Object.keys(JSON.parse(savedStatuses)).length > 0) {
            e.preventDefault();
            e.returnValue = 'Anda memiliki perubahan yang belum disimpan. Yakin ingin meninggalkan halaman?';
            return e.returnValue;
        }
    });
    
    // Back button warning
    const backButton = document.querySelector('a[href*="jadwallatihan/index.php"]');
    if (backButton) {
        backButton.addEventListener('click', function(e) {
            const savedStatuses = localStorage.getItem('unsaved_absensi_' + currentJadwalId);
            if (savedStatuses && Object.keys(JSON.parse(savedStatuses)).length > 0) {
                if (!confirm('Anda memiliki perubahan yang belum disimpan. Yakin ingin kembali?')) {
                    e.preventDefault();
                    return false;
                }
                clearUnsavedStatus();
            }
        });
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            showSaveModal();
        }
        
        // Escape to clear search
        if (e.key === 'Escape') {
            if (searchInput.value !== '') {
                clearSearch();
            }
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>