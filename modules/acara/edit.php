<?php
/**
 * Edit Booking Acara
 * Menggunakan sidebar layout yang konsisten
 */

// Definisikan BASE_URL jika belum ada
if (!defined('BASE_URL')) {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/hadrahin';
    define('BASE_URL', $base_url);
}

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

// Check permission (Admin only)
if ($_SESSION['peran'] != 'admin') {
    header('Location: index.php?error=permission_denied');
    exit;
}

// Get booking ID
$id_booking = $_GET['id'] ?? 0;

if (empty($id_booking) || !is_numeric($id_booking)) {
    header('Location: index.php?error=invalid_id');
    exit;
}

// Get booking data
try {
    $stmt = $pdo->prepare("SELECT * FROM booking_acara WHERE id_booking = ?");
    $stmt->execute([$id_booking]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        header('Location: index.php?error=not_found');
        exit;
    }
} catch (PDOException $e) {
    header('Location: index.php?error=' . urlencode($e->getMessage()));
    exit;
}

// Get all dresscodes for dropdown
try {
    $stmt = $pdo->query("SELECT id_dresscode, nama_pakaian FROM dresscode WHERE status = 'aktif' ORDER BY nama_pakaian");
    $dresscodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dresscodes = [];
}

// Get all admin users for penanggung jawab dropdown
try {
    $stmt = $pdo->query("SELECT id_user, nama_lengkap, peran FROM user WHERE peran = 'admin' AND status_aktif = 1 ORDER BY nama_lengkap");
    $penanggung_jawab = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $penanggung_jawab = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_acara = trim($_POST['nama_acara'] ?? '');
    $nama_pemesan = trim($_POST['nama_pemesan'] ?? '');
    $no_hp_pemesan = trim($_POST['no_hp_pemesan'] ?? '');
    $tanggal_acara = $_POST['tanggal_acara'] ?? '';
    $jam_mulai = $_POST['jam_mulai'] ?? '';
    $lokasi = trim($_POST['lokasi'] ?? '');
    $id_dresscode = !empty($_POST['id_dresscode']) ? (int)$_POST['id_dresscode'] : null;
    $id_user = !empty($_POST['id_user']) ? (int)$_POST['id_user'] : null;
    $keterangan = trim($_POST['keterangan'] ?? '');
    $status = $_POST['status'] ?? $booking['status'];

    // Validation
    $errors = [];

    if (empty($nama_acara)) {
        $errors['nama_acara'] = 'Nama acara harus diisi';
    }

    if (empty($nama_pemesan)) {
        $errors['nama_pemesan'] = 'Nama pemesan harus diisi';
    }

    if (empty($tanggal_acara)) {
        $errors['tanggal_acara'] = 'Tanggal acara harus diisi';
    }

    if (empty($jam_mulai)) {
        $errors['jam_mulai'] = 'Jam mulai harus diisi';
    }

    if (empty($lokasi)) {
        $errors['lokasi'] = 'Lokasi harus diisi';
    }

    if (empty($id_dresscode)) {
        $errors['id_dresscode'] = 'Dresscode wajib dipilih';
    }

    if (empty($_POST['id_user'])) {
        $errors['id_user'] = 'Penanggung jawab wajib dipilih';
    } elseif (!is_numeric($_POST['id_user'])) {
        $errors['id_user'] = 'Penanggung jawab tidak valid';
    }

    if (!in_array($status, ['menunggu', 'diterima', 'ditolak', 'selesai'])) {
        $errors['status'] = 'Status tidak valid';
    }

    if (empty($errors)) {
        // Update database
        try {
            $query = "UPDATE booking_acara 
                      SET id_user = ?, nama_acara = ?, nama_pemesan = ?, no_hp_pemesan = ?, 
                          tanggal_acara = ?, jam_mulai = ?, lokasi = ?, 
                          id_dresscode = ?, status = ?, keterangan = ?, updated_at = NOW(), 
                          user_modified = ?
                      WHERE id_booking = ?";

            $stmt = $pdo->prepare($query);
            $stmt->execute([
                $id_user,
                $nama_acara,
                $nama_pemesan,
                $no_hp_pemesan ?: null,
                $tanggal_acara,
                $jam_mulai,
                $lokasi,
                $id_dresscode,
                $status,
                $keterangan ?: null,
                $_SESSION['user_id'],
                $id_booking
            ]);

            // Log activity
            error_log("Booking acara diupdate: ID=$id_booking, User=" . $_SESSION['user_id']);

            header('Location: index.php?success=booking_updated');
            exit;

        } catch (PDOException $e) {
            $error_msg = 'Gagal mengupdate booking: ' . $e->getMessage();
        }
    }
}

$status_labels = [
    'menunggu' => 'Menunggu Konfirmasi',
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

<?php if (!empty($error_msg)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error_msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Form Card -->
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center">
                    <a href="index.php" class="btn btn-outline-secondary me-3">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Booking Acara</h4>
                </div>
            </div>
            <div class="card-body">
                <form id="editForm" method="POST">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Acara <span class="text-danger">*</span></label>
                            <input type="text" id="nama_acara" class="form-control <?= !empty($errors['nama_acara']) ? 'is-invalid' : '' ?>" 
                                   name="nama_acara" value="<?= htmlspecialchars($booking['nama_acara']) ?>" required>
                            <?php if (!empty($errors['nama_acara'])): ?>
                                <div class="invalid-feedback"><?= $errors['nama_acara'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Pemesan <span class="text-danger">*</span></label>
                            <input type="text" id="nama_pemesan" class="form-control <?= !empty($errors['nama_pemesan']) ? 'is-invalid' : '' ?>" 
                                   name="nama_pemesan" value="<?= htmlspecialchars($booking['nama_pemesan']) ?>" required>
                            <?php if (!empty($errors['nama_pemesan'])): ?>
                                <div class="invalid-feedback"><?= $errors['nama_pemesan'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">No HP Pemesan</label>
                            <input type="text" id="no_hp_pemesan" class="form-control" name="no_hp_pemesan" 
                                   value="<?= htmlspecialchars($booking['no_hp_pemesan'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Acara <span class="text-danger">*</span></label>
                            <input type="date" id="tanggal_acara" class="form-control <?= !empty($errors['tanggal_acara']) ? 'is-invalid' : '' ?>" 
                                   name="tanggal_acara" value="<?= $booking['tanggal_acara'] ?>" required>
                            <?php if (!empty($errors['tanggal_acara'])): ?>
                                <div class="invalid-feedback"><?= $errors['tanggal_acara'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Jam Mulai <span class="text-danger">*</span></label>
                            <input type="time" id="jam_mulai" class="form-control <?= !empty($errors['jam_mulai']) ? 'is-invalid' : '' ?>" 
                                   name="jam_mulai" value="<?= $booking['jam_mulai'] ?>" required>
                            <?php if (!empty($errors['jam_mulai'])): ?>
                                <div class="invalid-feedback"><?= $errors['jam_mulai'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lokasi <span class="text-danger">*</span></label>
                            <input type="text" id="lokasi" class="form-control <?= !empty($errors['lokasi']) ? 'is-invalid' : '' ?>" 
                                   name="lokasi" value="<?= htmlspecialchars($booking['lokasi']) ?>" required>
                            <?php if (!empty($errors['lokasi'])): ?>
                                <div class="invalid-feedback"><?= $errors['lokasi'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Dresscode <span class="text-danger">*</span></label>
                            <select class="form-select <?= !empty($errors['id_dresscode']) ? 'is-invalid' : '' ?>" 
                                    name="id_dresscode" id="id_dresscode" required>
                                <option value="">-- Pilih Dresscode --</option>
                                <?php foreach ($dresscodes as $dc): ?>
                                    <option value="<?= $dc['id_dresscode'] ?>" 
                                        <?= ($booking['id_dresscode'] == $dc['id_dresscode']) || (isset($_POST['id_dresscode']) && $_POST['id_dresscode'] == $dc['id_dresscode']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dc['nama_pakaian']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($errors['id_dresscode'])): ?>
                                <div class="invalid-feedback"><?= $errors['id_dresscode'] ?></div>
                            <?php endif; ?>
                            <?php if (empty($dresscodes)): ?>
                                <div class="form-text text-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Tidak ada dresscode aktif. Silakan tambah dresscode di menu Dresscode.
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Penanggung Jawab <span class="text-danger">*</span></label>
                            <select class="form-select <?= !empty($errors['id_user']) ? 'is-invalid' : '' ?>" 
                                    name="id_user" id="id_user" required>
                                <option value="">-- Pilih Penanggung Jawab --</option>
                                <?php foreach ($penanggung_jawab as $pj): ?>
                                    <option value="<?= $pj['id_user'] ?>" 
                                        <?= ($booking['id_user'] == $pj['id_user']) || (isset($_POST['id_user']) && $_POST['id_user'] == $pj['id_user']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pj['nama_lengkap']) ?> (<?= ucfirst($pj['peran']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($errors['id_user'])): ?>
                                <div class="invalid-feedback"><?= $errors['id_user'] ?></div>
                            <?php endif; ?>
                            <?php if (empty($penanggung_jawab)): ?>
                                <div class="form-text text-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Tidak ada admin aktif.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <?php foreach ($status_labels as $key => $label): ?>
                                    <option value="<?= $key ?>" <?= $booking['status'] == $key ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" name="keterangan" rows="3"><?= htmlspecialchars($booking['keterangan'] ?? '') ?></textarea>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary btn-lg" onclick="showConfirmModal()">
                            <i class="fas fa-save me-2"></i>Simpan Perubahan
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
                <h5 class="modal-title" id="confirmModalLabel">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>Konfirmasi Perubahan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Apakah Anda yakin ingin menyimpan perubahan?</p>
                <div class="alert alert-light border rounded p-3">
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Nama Acara:</div>
                        <div class="col-8" id="confirmNamaAcara">-</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Pemesan:</div>
                        <div class="col-8" id="confirmPemesan">-</div>
                    </div>
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
                        <div class="col-4 fw-bold">Status:</div>
                        <div class="col-8">
                            <span class="badge bg-warning text-dark" id="confirmStatus">Menunggu Konfirmasi</span>
                        </div>
                    </div>
                </div>
                <div class="form-text">
                    <i class="fas fa-info-circle me-1"></i>Pastikan data sudah benar sebelum menyimpan.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Batal
                </button>
                <button type="button" class="btn btn-primary" onclick="submitForm()">
                    <i class="fas fa-check me-1"></i>Ya, Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Global inputs reference for confirmation modal
const inputs = {
    nama_acara: document.getElementById('nama_acara'),
    nama_pemesan: document.getElementById('nama_pemesan'),
    tanggal_acara: document.getElementById('tanggal_acara'),
    jam_mulai: document.getElementById('jam_mulai'),
    lokasi: document.getElementById('lokasi'),
    status: document.querySelector('select[name="status"]')
};

// Show confirmation modal
function showConfirmModal() {
    document.getElementById('confirmNamaAcara').textContent = inputs.nama_acara ? inputs.nama_acara.value.trim() : '-';
    document.getElementById('confirmPemesan').textContent = inputs.nama_pemesan ? inputs.nama_pemesan.value.trim() : '-';
    document.getElementById('confirmTanggal').textContent = inputs.tanggal_acara && inputs.tanggal_acara.value 
        ? new Date(inputs.tanggal_acara.value).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) 
        : '-';
    document.getElementById('confirmJam').textContent = inputs.jam_mulai ? inputs.jam_mulai.value : '-';
    document.getElementById('confirmLokasi').textContent = inputs.lokasi ? inputs.lokasi.value.trim() : '-';
    
    if (inputs.status) {
        const statusText = inputs.status.options[inputs.status.selectedIndex].text;
        document.getElementById('confirmStatus').textContent = statusText;
    }
    
    const modalEl = document.getElementById('confirmModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function submitForm() {
    const form = document.getElementById('editForm');
    if (form) {
        form.submit();
    }
}

// Get current booking ID for duplicate check
const currentBookingId = <?= $id_booking ?>;

// Check duplicate booking acara
function checkDuplicateAcara() {
    const tanggal = inputs.tanggal_acara.value;
    const jam = inputs.jam_mulai.value;
    
    if (tanggal && jam) {
        fetch('../../api/check_acara.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'tanggal=' + encodeURIComponent(tanggal) + '&jam_mulai=' + encodeURIComponent(jam) + '&exclude_id=' + currentBookingId
        })
        .then(response => response.json())
        .then(data => {
            if (data.exists) {
                showError(inputs.jam_mulai, 'Booking acara pada tanggal dan jam tersebut sudah ada!');
            } else {
                clearError(inputs.jam_mulai);
            }
        })
        .catch(() => {});
    }
}

// Real-time validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editForm');
    const inputs = {
        nama_acara: document.getElementById('nama_acara'),
        nama_pemesan: document.getElementById('nama_pemesan'),
        tanggal_acara: document.getElementById('tanggal_acara'),
        jam_mulai: document.getElementById('jam_mulai'),
        lokasi: document.getElementById('lokasi'),
        id_dresscode: document.getElementById('id_dresscode')
    };

    function showError(input, message) {
        // Handle untuk select element (dresscode)
        if (input.tagName === 'SELECT') {
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
        
        const parent = input.parentElement;
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
        // Handle untuk select element
        if (input.tagName === 'SELECT') {
            const parent = input.parentElement.parentElement;
            input.classList.remove('is-invalid');
            const errorDiv = parent.querySelector('.invalid-feedback');
            if (errorDiv) errorDiv.remove();
            return true;
        }
        
        const parent = input.parentElement;
        const errorDiv = parent.querySelector('.invalid-feedback');
        input.classList.remove('is-invalid');
        if (errorDiv) {
            errorDiv.remove();
        }
        return true;
    }

    // Validation for each field
    if (inputs.nama_acara) {
        inputs.nama_acara.addEventListener('input', function() {
            if (this.value.length === 0) return clearError(this);
            if (this.value.length < 3) return showError(this, 'Nama acara minimal 3 karakter!');
            return clearError(this);
        });
    }

    if (inputs.nama_pemesan) {
        inputs.nama_pemesan.addEventListener('input', function() {
            if (this.value.length === 0) return clearError(this);
            if (this.value.length < 2) return showError(this, 'Nama pemesan minimal 2 karakter!');
            return clearError(this);
        });
    }

    if (inputs.tanggal_acara) {
        inputs.tanggal_acara.addEventListener('change', function() {
            if (!this.value) return clearError(this);
            if (new Date(this.value) < new Date(new Date().toISOString().split('T')[0])) {
                return showError(this, 'Tanggal tidak boleh kurang dari hari ini!');
            }
            if (inputs.jam_mulai && inputs.jam_mulai.value) checkDuplicateAcara();
            return clearError(this);
        });
    }

    if (inputs.jam_mulai) {
        inputs.jam_mulai.addEventListener('change', function() {
            if (!this.value) return showError(this, 'Jam mulai wajib diisi!');
            return clearError(this);
        });
        inputs.jam_mulai.addEventListener('blur', checkDuplicateAcara);
    }

    if (inputs.lokasi) {
        inputs.lokasi.addEventListener('input', function() {
            if (this.value.length === 0) return clearError(this);
            if (this.value.length < 5) return showError(this, 'Lokasi minimal 5 karakter!');
            return clearError(this);
        });
    }

    // Dresscode validation (change event untuk select)
    if (inputs.id_dresscode) {
        inputs.id_dresscode.addEventListener('change', function() {
            if (!this.value) return showError(this, 'Dresscode wajib dipilih!');
            return clearError(this);
        });
    }

    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Validate all required fields
        Object.keys(inputs).forEach(key => {
            const input = inputs[key];
            if (input && input.hasAttribute('required') && !input.value.trim()) {
                showError(input, 'Field ini wajib diisi!');
                isValid = false;
            }
        });

        // Additional validation
        if (inputs.nama_acara && inputs.nama_acara.value.length < 3) {
            showError(inputs.nama_acara, 'Nama acara minimal 3 karakter!');
            isValid = false;
        }

        if (inputs.lokasi && inputs.lokasi.value.length < 5) {
            showError(inputs.lokasi, 'Lokasi minimal 5 karakter!');
            isValid = false;
        }

        // Dresscode validation on submit
        if (inputs.id_dresscode && !inputs.id_dresscode.value) {
            showError(inputs.id_dresscode, 'Dresscode wajib dipilih!');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            const firstError = form.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>

