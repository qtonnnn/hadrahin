<?php
/**
 * Edit Dresscode - Form dengan sidebar layout
 */

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

$page_title = "Edit Dresscode";

// Get dresscode ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php?msg=error');
    exit;
}

// Fetch dresscode data
$stmt = $pdo->prepare("SELECT * FROM dresscode WHERE id_dresscode = ?");
$stmt->execute([$id]);
$dresscode = $stmt->fetch();

if (!$dresscode) {
    header('Location: index.php?msg=error');
    exit;
}

// Store original values
$original_nama = $dresscode['nama_pakaian'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_pakaian = trim($_POST['nama_pakaian'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $warna = trim($_POST['warna'] ?? '');
    $status = $_POST['status'] ?? 'aktif';

    $errors = [];

    // Validation
    if (empty($nama_pakaian)) {
        $errors[] = "Nama pakaian wajib diisi!";
    } elseif (strlen($nama_pakaian) > 100) {
        $errors[] = "Nama pakaian maksimal 100 karakter!";
    }

    if (!in_array($status, ['aktif', 'nonaktif'])) {
        $errors[] = "Status tidak valid!";
    }

    // Check unique nama_pakaian (exclude current dresscode)
    if (empty($errors) && $nama_pakaian !== $original_nama) {
        $stmt = $pdo->prepare("SELECT id_dresscode FROM dresscode WHERE nama_pakaian = ? AND id_dresscode != ?");
        $stmt->execute([$nama_pakaian, $id]);
        if ($stmt->fetch()) {
            $errors[] = "Nama pakaian sudah digunakan!";
        }
    }

    // Update dresscode
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE dresscode SET nama_pakaian = ?, deskripsi = ?, warna = ?, status = ?, user_modified = ?, updated_at = NOW() WHERE id_dresscode = ?");
        $stmt->execute([$nama_pakaian, $deskripsi ?: null, $warna ?: null, $status, $_SESSION['user_id'], $id]);
        
        header('Location: index.php?msg=edit_sukes');
        exit;
    }
}

// Include header dengan sidebar
include '../../includes/header.php';
?>

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

<!-- Form Card -->
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center">
                    <a href="index.php" class="btn btn-outline-secondary me-3">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h4 class="mb-0"><i class="fas fa-tshirt me-2"></i><?= $page_title ?></h4>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" id="dresscodeForm">
                    <input type="hidden" name="id" value="<?= $id ?>">

                    <div class="mb-3">
                        <label for="nama_pakaian" class="form-label">Nama Pakaian <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-tshirt"></i></span>
                            <input type="text" class="form-control" id="nama_pakaian" name="nama_pakaian" 
                                   placeholder="Masukkan nama pakaian" required maxlength="100"
                                   value="<?= htmlspecialchars($dresscode['nama_pakaian']) ?>">
                        </div>
                        <div class="form-text">Masukkan nama pakaian yang unik dan mudah diingat.</div>
                    </div>

                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"
                                      placeholder="Jelaskan detail pakaian..."><?= htmlspecialchars($dresscode['deskripsi'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="warna" class="form-label">Warna</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-palette"></i></span>
                            <input type="text" class="form-control" id="warna" name="warna" 
                                   placeholder="Contoh: Putih, Biru, Hitam" maxlength="50"
                                   value="<?= htmlspecialchars($dresscode['warna'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="status" class="form-label">Status</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-toggle-on"></i></span>
                            <select class="form-select" id="status" name="status">
                                <option value="aktif" <?= $dresscode['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                <option value="nonaktif" <?= $dresscode['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success btn-lg" onclick="showConfirmModal()">
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
                <h5 class="modal-title" id="confirmModalLabel"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Konfirmasi Perubahan Dresscode</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Apakah perubahan berikut sudah benar?</p>
                <div class="alert alert-light border rounded p-3">
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Nama Pakaian:</div>
                        <div class="col-8" id="confirmNamaPakaian">-</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Deskripsi:</div>
                        <div class="col-8" id="confirmDeskripsi">-</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Warna:</div>
                        <div class="col-8" id="confirmWarna">-</div>
                    </div>
                    <div class="row">
                        <div class="col-4 fw-bold">Status:</div>
                        <div class="col-8">
                            <span class="badge bg-success" id="confirmStatus">Aktif</span>
                        </div>
                    </div>
                </div>
                <div class="form-text"><i class="fas fa-info-circle me-1"></i>Pastikan data sudah benar sebelum menyimpan.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Batal</button>
                <button type="button" class="btn btn-success" onclick="submitForm()"><i class="fas fa-check me-1"></i>Konfirmasi</button>
            </div>
        </div>
    </div>
</div>

<script>
const dresscodeId = <?= $id ?>;

// Show confirmation modal
function showConfirmModal() {
    const namaPakaian = document.getElementById('nama_pakaian').value.trim() || '-';
    const deskripsi = document.getElementById('deskripsi').value.trim() || 'Tidak ada';
    const warna = document.getElementById('warna').value.trim() || 'Tidak ada';
    const statusSelect = document.getElementById('status');
    const statusText = statusSelect.options[statusSelect.selectedIndex].text;
    const statusValue = statusSelect.value;
    
    document.getElementById('confirmNamaPakaian').textContent = namaPakaian;
    document.getElementById('confirmDeskripsi').textContent = deskripsi;
    document.getElementById('confirmWarna').textContent = warna;
    document.getElementById('confirmStatus').textContent = statusText;
    document.getElementById('confirmStatus').className = statusValue === 'aktif' ? 'badge bg-success' : 'badge bg-secondary';
    
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    modal.show();
}

function submitForm() {
    document.getElementById('dresscodeForm').submit();
}

// Real-time Validation
document.addEventListener('DOMContentLoaded', function() {
    const namaPakaianInput = document.getElementById('nama_pakaian');
    const form = document.getElementById('dresscodeForm');
    let isChecking = false;

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

    // Nama pakaian validation on input
    namaPakaianInput.addEventListener('input', function() {
        const value = this.value;
        
        // Clear error first
        clearError(this);
        
        if (value.length === 0) {
            return false;
        }
        
        if (value.length > 100) {
            this.value = value.slice(0, 100);
            return showError(this, 'Nama pakaian maksimal 100 karakter!');
        }
        
        return true;
    });

    // Nama pakaian validation on blur (check unique via API, exclude current id)
    namaPakaianInput.addEventListener('blur', function() {
        const value = this.value.trim();
        
        if (value.length === 0) {
            return;
        }
        
        if (value.length > 100) {
            return;
        }
        
        // Check uniqueness via API (exclude current dresscode)
        if (!isChecking) {
            isChecking = true;
            
            fetch('../../api/check_dresscode_edit.php?nama_pakaian=' + encodeURIComponent(value) + '&exclude_id=' + dresscodeId)
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        showError(namaPakaianInput, 'Nama pakaian sudah digunakan!');
                    } else {
                        clearError(namaPakaianInput);
                    }
                    isChecking = false;
                })
                .catch(() => {
                    isChecking = false;
                });
        }
    });

    // Form submission validation
    form.addEventListener('submit', function(e) {
        let isValid = true;
        const namaPakaian = namaPakaianInput.value.trim();
        
        if (!namaPakaian) {
            showError(namaPakaianInput, 'Nama pakaian wajib diisi!');
            isValid = false;
        }
        
        if (namaPakaian.length > 100) {
            showError(namaPakaianInput, 'Nama pakaian maksimal 100 karakter!');
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

