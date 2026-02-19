<?php
/**
 * Tambah Dresscode Baru - Form dengan sidebar layout
 * Fitur: Upload foto pakaian
 */

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

$page_title = "Tambah Dresscode";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_pakaian = trim($_POST['nama_pakaian'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $warna = trim($_POST['warna'] ?? '');
    $status = $_POST['status'] ?? 'aktif';
    $foto_filename = null;

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

    // Handle file upload
    if (!empty($_FILES['foto']['name'])) {
        $file = $_FILES['foto'];
        
        // Debug: Check if file was uploaded
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => 'File terlalu besar. Maksimal 2MB (cek pengaturan PHP).',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
                UPLOAD_ERR_NO_FILE => 'Tidak ada file dipilih',
                UPLOAD_ERR_NO_TMP_DIR => 'Folder temp tidak ditemukan',
                UPLOAD_ERR_CANT_WRITE => 'Gagal menulis ke disk',
                UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
            ];
            $errors[] = "Error upload: " . ($upload_errors[$file['error']] ?? 'Unknown error: ' . $file['error']);
        } else {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 2 * 1024 * 1024; // 2MB (sesuai dengan upload_max_filesize PHP)

            if (!in_array($file['type'], $allowed_types)) {
                $errors[] = "Tipe file tidak diizinkan. Gunakan gambar (JPG, PNG, GIF, WEBP).";
            } elseif ($file['size'] > $max_size) {
                $errors[] = "File terlalu besar. Maksimal 2MB.";
            } else {
                // Create upload directory if not exists
                $upload_dir = '../../assets/uploads/pakaian/';
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0777, true)) {
                        $errors[] = "Gagal membuat direktori upload!";
                    }
                }

                if (empty($errors)) {
                    // Generate unique filename
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $foto_filename = 'pakaian_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
                    $target_path = $upload_dir . $foto_filename;

                    // Try move_uploaded_file first, if fails try copy
                    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
                        // Fallback to copy if move_uploaded_file fails (e.g., on some server configs)
                        if (!copy($file['tmp_name'], $target_path)) {
                            $errors[] = "Gagal mengupload foto! Periksa permission folder.";
                            $foto_filename = null;
                        }
                    }
                }
            }
        }
    }

    // Check unique nama_pakaian
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id_dresscode FROM dresscode WHERE nama_pakaian = ?");
        $stmt->execute([$nama_pakaian]);
        if ($stmt->fetch()) {
            $errors[] = "Nama pakaian sudah digunakan!";
        }
    }

    // Insert dresscode
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO dresscode (nama_pakaian, deskripsi, warna, foto, status, user_record, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$nama_pakaian, $deskripsi ?: null, $warna ?: null, $foto_filename, $status, $_SESSION['user_id']]);
        
        header('Location: index.php?msg=tambah_sukes');
        exit;
    }
}

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
                <form method="POST" id="dresscodeForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="nama_pakaian" class="form-label">Nama Pakaian <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-tshirt"></i></span>
                            <input type="text" class="form-control" id="nama_pakaian" name="nama_pakaian" 
                                   value="<?= htmlspecialchars($_POST['nama_pakaian'] ?? '') ?>" 
                                   placeholder="Masukkan nama pakaian" required maxlength="100">
                        </div>
                        <div class="form-text">Masukkan nama pakaian yang unik dan mudah diingat.</div>
                    </div>

                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"
                                      placeholder="Jelaskan detail pakaian..."><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="warna" class="form-label">Warna</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-palette"></i></span>
                            <input type="text" class="form-control" id="warna" name="warna" 
                                   value="<?= htmlspecialchars($_POST['warna'] ?? '') ?>" 
                                   placeholder="Contoh: Putih, Biru, Hitam" maxlength="50">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="foto" class="form-label">Foto Pakaian</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-camera"></i></span>
                            <input type="file" class="form-control" id="foto" name="foto" accept="image/jpeg,image/png,image/gif,image/webp">
                        </div>
                        <div class="form-text">Format: JPG, PNG, GIF, WEBP. Maksimal 2MB.</div>
                        <div id="fotoPreview" class="mt-2 text-center" style="display: none;">
                            <img id="previewImg" src="" alt="Preview" class="img-thumbnail" style="max-height: 200px;">
                            <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="removePhoto()">
                                <i class="fas fa-times"></i> Hapus
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="status" class="form-label">Status</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-toggle-on"></i></span>
                            <select class="form-select" id="status" name="status">
                                <option value="aktif" <?= ($_POST['status'] ?? '') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                <option value="nonaktif" <?= ($_POST['status'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success btn-lg" onclick="showConfirmModal()">
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
                <h5 class="modal-title" id="confirmModalLabel"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Konfirmasi Data Dresscode</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Apakah data berikut sudah benar?</p>
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
// Photo preview functionality
document.getElementById('foto').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('fotoPreview');
    const previewImg = document.getElementById('previewImg');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
    }
});

function removePhoto() {
    document.getElementById('foto').value = '';
    document.getElementById('fotoPreview').style.display = 'none';
    document.getElementById('previewImg').src = '';
}

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

    // Nama pakaian validation on blur (check unique via API)
    namaPakaianInput.addEventListener('blur', function() {
        const value = this.value.trim();
        
        if (value.length === 0) {
            return;
        }
        
        if (value.length > 100) {
            return;
        }
        
        // Check uniqueness via API
        if (!isChecking) {
            isChecking = true;
            
            fetch('../../api/check_dresscode.php?nama_pakaian=' + encodeURIComponent(value))
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

