<?php
/**
 * Tambah Jadwal Latihan Baru - Form dengan sidebar layout
 */

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
require_once '../../includes/cache.php';

$page_title = "Tambah Jadwal Latihan";

// Get current user role
$user_peran = $_SESSION['peran'] ?? 'guest';
$user_id = $_SESSION['user_id'] ?? 0;

// Check permission
if ($user_peran !== 'admin' && $user_peran !== 'pembina') {
    header('Location: index.php?msg=access_denied');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = $_POST['tanggal'];
    $jam_mulai = $_POST['jam_mulai'];
    $lokasi = trim($_POST['lokasi']);
    $catatan = trim($_POST['catatan'] ?? '');
    $status = 'direncanakan';

    $errors = [];

    // Validate required fields
    if (empty($tanggal)) {
        $errors[] = "Tanggal latihan wajib diisi";
    }
    if (empty($jam_mulai)) {
        $errors[] = "Jam mulai wajib diisi";
    }
    if (empty($lokasi)) {
        $errors[] = "Lokasi latihan wajib diisi";
    }

    // Validate date is not in the past
    if (!empty($tanggal)) {
        $tanggal_obj = new DateTime($tanggal);
        $today = new DateTime('today');
        if ($tanggal_obj < $today) {
            $errors[] = "Tanggal tidak boleh kurang dari hari ini";
        }
    }

    // Validate lokasi length
    if (strlen($lokasi) < 3) {
        $errors[] = "Lokasi minimal 3 karakter";
    }
    if (strlen($lokasi) > 150) {
        $errors[] = "Lokasi maksimal 150 karakter";
    }

    // Validate catatan length
    if (!empty($catatan) && strlen($catatan) > 500) {
        $errors[] = "Catatan maksimal 500 karakter";
    }

    // Check for duplicate schedule
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id_jadwal FROM jadwal_latihan WHERE tanggal = ? AND jam_mulai = ?");
        $stmt->execute([$tanggal, $jam_mulai]);
        if ($stmt->fetch()) {
            $errors[] = "Jadwal latihan pada tanggal dan jam tersebut sudah ada";
        }
    }

    // Insert jadwal
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO jadwal_latihan (tanggal, jam_mulai, lokasi, catatan, status, user_record) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tanggal, $jam_mulai, $lokasi, $catatan, $status, $user_id]);

        // Clear cache after insert
        Cache::delete('jadwal_stats');
        Cache::delete('jadwal_max_date');

        header('Location: index.php?msg=tambah_sukes');
        exit;
    }
}

// Include header dengan sidebar
include '../../includes/header.php';
?>

<!-- Error Messages Container -->
<div id="error-container"></div>

<!-- Form Card -->
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center">
                    <a href="index.php" class="btn btn-outline-secondary me-3">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h4 class="mb-0"><i class="fas fa-calendar-plus me-2"></i><?= $page_title ?></h4>
                </div>
            </div>
            <div class="card-body">
                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" autocomplete="off" id="jadwalForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tanggal" class="form-label">Tanggal Latihan <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                <input type="date" class="form-control" id="tanggal" name="tanggal" 
                                       value="<?= htmlspecialchars($_POST['tanggal'] ?? date('Y-m-d')) ?>" 
                                       min="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="jam_mulai" class="form-label">Jam Mulai <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                <input type="time" class="form-control" id="jam_mulai" name="jam_mulai" 
                                       value="<?= htmlspecialchars($_POST['jam_mulai'] ?? '19:00') ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="lokasi" class="form-label">Lokasi Latihan <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                            <input type="text" class="form-control" id="lokasi" name="lokasi" 
                                   value="<?= htmlspecialchars($_POST['lokasi'] ?? '') ?>" 
                                   placeholder="Masukkan lokasi latihan" required maxlength="150">
                        </div>
                        <div class="form-text">Contoh: Masjid Al-Hidayah, Ruang Serbaguna, dll.</div>
                    </div>

                    <div class="mb-3">
                        <label for="catatan" class="form-label">Catatan</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-sticky-note"></i></span>
                            <textarea class="form-control" id="catatan" name="catatan" rows="3" 
                                      placeholder="Tambahkan catatan jika diperlukan..." maxlength="500"><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
                        </div>
                        <div class="form-text">Maksimal 500 karakter. <span id="charCount">0</span>/500</div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary btn-lg" onclick="showConfirmModal()">
                            <i class="fas fa-save me-2"></i>Simpan
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Konfirmasi Jadwal Latihan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Apakah data berikut sudah benar?</p>
                <div class="alert alert-light border rounded p-3">
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Tanggal:</div>
                        <div class="col-8" id="confirmTanggal">-</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Jam:</div>
                        <div class="col-8" id="confirmJam">-</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Lokasi:</div>
                        <div class="col-8" id="confirmLokasi">-</div>
                    </div>
                    <div class="row">
                        <div class="col-4 fw-bold">Catatan:</div>
                        <div class="col-8" id="confirmCatatan">-</div>
                    </div>
                </div>
                <div class="form-text"><i class="fas fa-info-circle me-1"></i>Pastikan data sudah benar sebelum menyimpan.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Batal</button>
                <button type="button" class="btn btn-primary" onclick="submitForm()"><i class="fas fa-check me-1"></i>Konfirmasi</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const catatanInput = document.getElementById('catatan');
    const charCount = document.getElementById('charCount');
    const tanggalInput = document.getElementById('tanggal');
    const jamInput = document.getElementById('jam_mulai');
    const lokasiInput = document.getElementById('lokasi');
    const form = document.getElementById('jadwalForm');
    
    // Character counter
    function updateCharCount() {
        const count = catatanInput.value.length;
        charCount.textContent = count;
    }
    catatanInput.addEventListener('input', updateCharCount);
    updateCharCount();

    // Date validation
    const today = new Date().toISOString().split('T')[0];
    tanggalInput.setAttribute('min', today);

    // Form validation
    function showError(input, message) {
        const parent = input.parentElement.parentElement;
        let errorDiv = parent.querySelector('.invalid-feedback');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback d-block';
            parent.appendChild(errorDiv);
        }
        input.classList.add('is-invalid');
        errorDiv.textContent = message;
        return false;
    }

    function clearError(input) {
        const parent = input.parentElement.parentElement;
        const errorDiv = parent.querySelector('.invalid-feedback');
        input.classList.remove('is-invalid');
        if (errorDiv) {
            errorDiv.remove();
        }
        return true;
    }

    // Lokasi validation
    lokasiInput.addEventListener('input', function() {
        const lokasi = this.value;
        if (lokasi.length === 0) return clearError(this);
        if (lokasi.length < 3) return showError(this, 'Lokasi minimal 3 karakter!');
        if (lokasi.length > 150) return showError(this, 'Lokasi maksimal 150 karakter!');
        return clearError(this);
    });

    // Realtime validation for duplicate jadwal (blur event)
    function checkDuplicateJadwal() {
        const tanggal = tanggalInput.value;
        const jam = jamInput.value;
        
        if (tanggal && jam) {
            fetch('../api/check_jadwal.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'tanggal=' + encodeURIComponent(tanggal) + '&jam_mulai=' + encodeURIComponent(jam)
            })
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    showError(jamInput, 'Jadwal pada tanggal dan jam tersebut sudah ada!');
                } else {
                    clearError(jamInput);
                }
            })
            .catch(() => {});
        }
    }

    jamInput.addEventListener('blur', checkDuplicateJadwal);
    tanggalInput.addEventListener('change', function() {
        if (jamInput.value) checkDuplicateJadwal();
    });

    form.addEventListener('submit', function(e) {
        let isValid = true;
        const requiredFields = [tanggalInput, jamInput, lokasiInput];
        
        requiredFields.forEach(field => {
            if (!field.value.trim() && field.hasAttribute('required')) {
                showError(field, 'Field ini wajib diisi!');
                isValid = false;
            }
        });

        if (!isValid) {
            e.preventDefault();
            const firstError = form.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
});

// Show confirmation modal
function showConfirmModal() {
    const tanggal = document.getElementById('tanggal').value;
    const jam = document.getElementById('jam_mulai').value;
    const lokasi = document.getElementById('lokasi').value.trim() || '-';
    const catatan = document.getElementById('catatan').value.trim() || 'Tidak ada';
    
    // Format date
    let formattedDate = '-';
    if (tanggal) {
        const dateObj = new Date(tanggal + 'T00:00:00');
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        formattedDate = dateObj.toLocaleDateString('id-ID', options);
    }
    
    // Format time
    let formattedTime = '-';
    if (jam) {
        const [hours, minutes] = jam.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        formattedTime = `${hour12}:${minutes} ${ampm}`;
    }
    
    document.getElementById('confirmTanggal').textContent = formattedDate;
    document.getElementById('confirmJam').textContent = formattedTime;
    document.getElementById('confirmLokasi').textContent = lokasi;
    document.getElementById('confirmCatatan').textContent = catatan;
    
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    modal.show();
}

function submitForm() {
    document.getElementById('jadwalForm').submit();
}
</script>

<?php include '../../includes/footer.php'; ?>

