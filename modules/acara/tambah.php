<?php
/**
 * Tambah Booking Acara Baru - Form dengan sidebar layout
 */

// Definisikan BASE_URL jika belum ada
if (!defined('BASE_URL')) {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/hadrahin';
    define('BASE_URL', $base_url);
}

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

$page_title = "Tambah Booking Acara";

// Check permission (Admin only)
if ($_SESSION['peran'] != 'admin') {
    header('Location: index.php?error=permission_denied');
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

    $errors = [];

    // Validation
    if (empty($nama_acara)) {
        $errors[] = "Nama acara wajib diisi!";
    } elseif (strlen($nama_acara) < 3) {
        $errors[] = "Nama acara minimal 3 karakter!";
    }

    if (empty($nama_pemesan)) {
        $errors[] = "Nama pemesan wajib diisi!";
    }

    if (empty($tanggal_acara)) {
        $errors[] = "Tanggal acara wajib diisi!";
    } elseif (strtotime($tanggal_acara) < strtotime(date('Y-m-d'))) {
        $errors[] = "Tanggal acara tidak boleh kurang dari hari ini!";
    }

    if (empty($jam_mulai)) {
        $errors[] = "Jam mulai wajib diisi!";
    }

    if (empty($lokasi)) {
        $errors[] = "Lokasi wajib diisi!";
    }

    if (empty($id_dresscode)) {
        $errors[] = "Dresscode wajib dipilih!";
    }

    if (empty($_POST['id_user'])) {
        $errors[] = "Penanggung jawab wajib dipilih!";
    } elseif (!is_numeric($_POST['id_user'])) {
        $errors[] = "Penanggung jawab tidak valid!";
    }

    // Insert booking
    if (empty($errors)) {
        try {
            $query = "INSERT INTO booking_acara 
                      (id_user, nama_acara, nama_pemesan, no_hp_pemesan, tanggal_acara, jam_mulai, lokasi, id_dresscode, status, keterangan, user_record) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'menunggu', ?, ?)";
            
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
                $keterangan ?: null,
                $_SESSION['user_id']
            ]);

            $id_booking = $pdo->lastInsertId();

            // Log activity
            error_log("Booking acara baru dibuat: ID=$id_booking, Nama=$nama_acara, User=" . $_SESSION['user_id']);

            header('Location: index.php?success=booking_created');
            exit;

        } catch (PDOException $e) {
            $errors[] = "Gagal membuat booking: " . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
?>

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
                <form method="POST" id="bookingForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nama_acara" class="form-label">Nama Acara <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                <input type="text" class="form-control" id="nama_acara" name="nama_acara" 
                                       value="<?= htmlspecialchars($_POST['nama_acara'] ?? '') ?>" 
                                       placeholder="Contoh: Manggung di Pernikahan" required minlength="3" maxlength="150">
                            </div>
                            <div class="form-text">Minimal 3 karakter.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="nama_pemesan" class="form-label">Nama Pemesan <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="nama_pemesan" name="nama_pemesan" 
                                       value="<?= htmlspecialchars($_POST['nama_pemesan'] ?? '') ?>" 
                                       placeholder="Nama orang yang memesan" required maxlength="100">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="no_hp_pemesan" class="form-label">Nomor HP Pemesan</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" class="form-control" id="no_hp_pemesan" name="no_hp_pemesan" 
                                       value="<?= htmlspecialchars($_POST['no_hp_pemesan'] ?? '') ?>" 
                                       placeholder="Contoh: 081234567890" maxlength="20">
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="tanggal_acara" class="form-label">Tanggal Acara <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                <input type="date" class="form-control" id="tanggal_acara" name="tanggal_acara" 
                                       value="<?= htmlspecialchars($_POST['tanggal_acara'] ?? '') ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="jam_mulai" class="form-label">Jam Mulai <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                <input type="time" class="form-control" id="jam_mulai" name="jam_mulai" 
                                       value="<?= htmlspecialchars($_POST['jam_mulai'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="lokasi" class="form-label">Lokasi <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                <input type="text" class="form-control" id="lokasi" name="lokasi" 
                                       value="<?= htmlspecialchars($_POST['lokasi'] ?? '') ?>" 
                                       placeholder="Alamat lengkap acara" required maxlength="150">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="id_dresscode" class="form-label">Dresscode <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tshirt"></i></span>
                                <select class="form-select" id="id_dresscode" name="id_dresscode" required>
                                    <option value="">-- Pilih Dresscode --</option>
                                    <?php foreach ($dresscodes as $dc): ?>
                                        <option value="<?= $dc['id_dresscode'] ?>" 
                                            <?= (isset($_POST['id_dresscode']) && $_POST['id_dresscode'] == $dc['id_dresscode']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($dc['nama_pakaian']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if (empty($dresscodes)): ?>
                                <div class="form-text text-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Tidak ada dresscode aktif. Silakan tambah dresscode di menu Dresscode terlebih dahulu.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="id_user" class="form-label">Penanggung Jawab <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user-tie"></i></span>
                                <select class="form-select" id="id_user" name="id_user" required>
                                    <option value="">-- Pilih Penanggung Jawab --</option>
                                    <?php foreach ($penanggung_jawab as $pj): ?>
                                        <option value="<?= $pj['id_user'] ?>" 
                                            <?= (isset($_POST['id_user']) && $_POST['id_user'] == $pj['id_user']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($pj['nama_lengkap']) ?> (<?= ucfirst($pj['peran']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if (empty($penanggung_jawab)): ?>
                                <div class="form-text text-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Tidak ada admin aktif.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="3" 
                                      placeholder="Catatan tambahan tentang acara..."><?= htmlspecialchars($_POST['keterangan'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary btn-lg" onclick="showConfirmModal()">
                            <i class="fas fa-save me-2"></i>Simpan Booking
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
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>Konfirmasi Booking
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Apakah data berikut sudah benar?</p>
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
                            <span class="badge bg-warning text-dark">Menunggu Konfirmasi</span>
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
                    <i class="fas fa-check me-1"></i>Konfirmasi
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation and confirmation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('bookingForm');
    const inputs = {
        nama_acara: document.getElementById('nama_acara'),
        nama_pemesan: document.getElementById('nama_pemesan'),
        tanggal_acara: document.getElementById('tanggal_acara'),
        jam_mulai: document.getElementById('jam_mulai'),
        lokasi: document.getElementById('lokasi')
    };

    function showError(input, message) {
        // Handle untuk select element (dresscode)
        if (input.tagName === 'DIV' && input.classList.contains('input-group')) {
            input.classList.add('is-invalid');
            let errorDiv = input.parentElement.querySelector('.dresscode-error');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback d-block dresscode-error';
                input.parentElement.appendChild(errorDiv);
            }
            errorDiv.textContent = message;
            return false;
        }
        
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
        // Handle untuk select element
        if (input.tagName === 'DIV' && input.classList.contains('input-group')) {
            input.classList.remove('is-invalid');
            const errorDiv = input.parentElement.querySelector('.dresscode-error');
            if (errorDiv) errorDiv.remove();
            return true;
        }
        
        const parent = input.parentElement.parentElement;
        const errorDiv = parent.querySelector('.invalid-feedback');
        input.classList.remove('is-invalid');
        if (errorDiv) {
            errorDiv.remove();
        }
        return true;
    }

    // Validation for each field
    inputs.nama_acara.addEventListener('input', function() {
        if (this.value.length === 0) return clearError(this);
        if (this.value.length < 3) return showError(this, 'Nama acara minimal 3 karakter!');
        return clearError(this);
    });

    inputs.nama_pemesan.addEventListener('input', function() {
        if (this.value.length === 0) return clearError(this);
        if (this.value.length < 2) return showError(this, 'Nama pemesan minimal 2 karakter!');
        return clearError(this);
    });

    inputs.tanggal_acara.addEventListener('change', function() {
        if (!this.value) return clearError(this);
        if (new Date(this.value) < new Date(new Date().toISOString().split('T')[0])) {
            return showError(this, 'Tanggal tidak boleh kurang dari hari ini!');
        }
        return clearError(this);
    });

    inputs.jam_mulai.addEventListener('change', function() {
        if (!this.value) return showError(this, 'Jam mulai wajib diisi!');
        return clearError(this);
    });

    inputs.jam_mulai.addEventListener('blur', checkDuplicateAcara);
    inputs.tanggal_acara.addEventListener('change', function() {
        if (inputs.jam_mulai.value) checkDuplicateAcara();
    });

    inputs.lokasi.addEventListener('input', function() {
        if (this.value.length === 0) return clearError(this);
        if (this.value.length < 5) return showError(this, 'Lokasi minimal 5 karakter!');
        return clearError(this);
    });

    // Dresscode validation (change event untuk select)
    inputs.id_dresscode.addEventListener('change', function() {
        if (!this.value) return showError(this.parentElement.parentElement, 'Dresscode wajib dipilih!');
        clearError(this.parentElement.parentElement);
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        let isValid = true;
        
        // Validate all required fields
        Object.keys(inputs).forEach(key => {
            const input = inputs[key];
            if (input.hasAttribute('required') && !input.value.trim()) {
                showError(input, 'Field ini wajib diisi!');
                isValid = false;
            }
        });

        // Additional validation
        if (inputs.nama_acara.value.length < 3) {
            showError(inputs.nama_acara, 'Nama acara minimal 3 karakter!');
            isValid = false;
        }

        if (inputs.lokasi.value.length < 5) {
            showError(inputs.lokasi, 'Lokasi minimal 5 karakter!');
            isValid = false;
        }

        // Dresscode validation
        if (!inputs.id_dresscode.value) {
            showError(inputs.id_dresscode.parentElement.parentElement, 'Dresscode wajib dipilih!');
            isValid = false;
        }

        if (isValid) {
            showConfirmModal();
        }
    });
});

// Global inputs reference for confirmation modal
const inputs = {
    nama_acara: document.getElementById('nama_acara'),
    nama_pemesan: document.getElementById('nama_pemesan'),
    tanggal_acara: document.getElementById('tanggal_acara'),
    jam_mulai: document.getElementById('jam_mulai'),
    lokasi: document.getElementById('lokasi')
};

// Check duplicate booking acara
function checkDuplicateAcara() {
    const tanggal = inputs.tanggal_acara.value;
    const jam = inputs.jam_mulai.value;
    
    if (tanggal && jam) {
        fetch('../api/check_acara.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'tanggal=' + encodeURIComponent(tanggal) + '&jam_mulai=' + encodeURIComponent(jam)
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

// Show confirmation modal
function showConfirmModal() {
    document.getElementById('confirmNamaAcara').textContent = inputs.nama_acara ? inputs.nama_acara.value.trim() : '-';
    document.getElementById('confirmPemesan').textContent = inputs.nama_pemesan ? inputs.nama_pemesan.value.trim() : '-';
    document.getElementById('confirmTanggal').textContent = inputs.tanggal_acara && inputs.tanggal_acara.value 
        ? new Date(inputs.tanggal_acara.value).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) 
        : '-';
    document.getElementById('confirmJam').textContent = inputs.jam_mulai ? inputs.jam_mulai.value : '-';
    document.getElementById('confirmLokasi').textContent = inputs.lokasi ? inputs.lokasi.value.trim() : '-';
    
    const modalEl = document.getElementById('confirmModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function submitForm() {
    const form = document.getElementById('bookingForm');
    if (form) {
        form.submit();
    }
}
</script>

<?php include '../../includes/footer.php'; ?>

