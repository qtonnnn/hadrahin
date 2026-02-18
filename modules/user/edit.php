<?php
/**
 * Edit User - Form dengan sidebar layout
 */

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

$page_title = "Edit User";

// Get user ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php?msg=error');
    exit;
}

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM user WHERE id_user = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: index.php?msg=error');
    exit;
}

// Count total active admin users
$stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE peran = 'admin' AND status_aktif = 1");
$stmt->execute();
$activeAdminCount = $stmt->fetchColumn();

// Check if this is the only admin
$isOnlyAdmin = ($user['peran'] === 'admin' && $user['status_aktif'] == 1 && $activeAdminCount <= 1);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $peran = $_POST['peran'] ?? 'anggota';
    // Use hidden input value when checkbox is disabled, otherwise use checkbox value
    $status_aktif = isset($_POST['status_aktif']) ? (int)$_POST['status_aktif'] : 
                   (isset($_POST['status_aktif_checkbox']) ? 1 : 0);

    $errors = [];

    // Validasi username
    if (empty($username)) {
        $errors[] = "Username harus diisi!";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username minimal 3 karakter!";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username hanya boleh berisi huruf, angka, dan underscore!";
    } else {
        // Cek username unik (kecuali user sendiri)
        $stmt = $pdo->prepare("SELECT id_user FROM user WHERE username = ? AND id_user != ?");
        $stmt->execute([$username, $id]);
        if ($stmt->fetch()) {
            $errors[] = "Username sudah digunakan!";
        }
    }

    // Validasi password (opsional)
    if (!empty($password) && strlen($password) < 3) {
        $errors[] = "Password minimal 3 karakter!";
    }

    // Validasi konfirmasi password
    if (!empty($password) && $password !== $confirm_password) {
        $errors[] = "Konfirmasi password tidak cocok!";
    }

    // Validasi nama lengkap
    if (empty($nama_lengkap)) {
        $errors[] = "Nama lengkap harus diisi!";
    } elseif (strlen($nama_lengkap) < 2) {
        $errors[] = "Nama lengkap minimal 2 karakter!";
    }

    // Phone number is required for anggota, optional for admin
    if ($peran === 'anggota' && empty($no_hp)) {
        $errors[] = "Nomor HP wajib diisi untuk Peran Anggota!";
    } elseif (!empty($no_hp) && !preg_match('/^[0-9]{10,15}$/', $no_hp)) {
        $errors[] = "Nomor HP harus berupa angka (10-15 digit)!";
    }

    // Check phone number uniqueness (exclude current user)
    if (empty($errors) && !empty($no_hp)) {
        $stmt = $pdo->prepare("SELECT id_user FROM user WHERE no_hp = ? AND id_user != ?");
        $stmt->execute([$no_hp, $id]);
        if ($stmt->fetch()) {
            $errors[] = "Nomor HP sudah digunakan oleh user lain!";
        }
    }

    // Jika tidak ada error, update data
    if (empty($errors)) {
        if (!empty($password)) {
            // Update dengan password baru
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE user SET username = ?, password = ?, nama_lengkap = ?, no_hp = ?, peran = ?, status_aktif = ? WHERE id_user = ?");
            $stmt->execute([$username, $hashed_password, $nama_lengkap, $no_hp, $peran, $status_aktif, $id]);
        } else {
            // Update tanpa password
            $stmt = $pdo->prepare("UPDATE user SET username = ?, nama_lengkap = ?, no_hp = ?, peran = ?, status_aktif = ? WHERE id_user = ?");
            $stmt->execute([$username, $nama_lengkap, $no_hp, $peran, $status_aktif, $id]);
        }
        
        // Update session jika user yang diedit adalah user yang sedang login
        if ($id == $_SESSION['user_id']) {
            $_SESSION['nama_lengkap'] = $nama_lengkap;
            $_SESSION['peran'] = $peran;
        }
        
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
                    <h4 class="mb-0"><i class="fas fa-user-edit me-2"></i><?= $page_title ?></h4>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" autocomplete="off" id="userForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="username" name="username" 
                                       placeholder="Masukkan username" required
                                       value="<?= htmlspecialchars($user['username']) ?>">
                            </div>
                            <div class="form-text">Minimal 3 karakter, huruf/angka/underscore</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                                       placeholder="Masukkan nama lengkap" required
                                       value="<?= htmlspecialchars($user['nama_lengkap']) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="no_hp" class="form-label">Nomor Handphone <span id="no_hp_required" class="text-danger" style="display:none">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="tel" class="form-control" id="no_hp" name="no_hp" 
                                   placeholder="Masukkan nomor HP (contoh: 081234567890)" maxlength="15"
                                   value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>">
                        </div>
                        <div class="form-text" id="no_hp_help">Format: 10-15 digit angka. Wajib untuk Peran Anggota.</div>
                    </div>

                    <div class="mb-3">
                        <label for="peran" class="form-label">Peran <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                            <select class="form-select" id="peran" name="peran" required>
                                <option value="anggota" <?= $user['peran'] === 'anggota' ? 'selected' : '' ?>>Anggota</option>
                                <option value="admin" <?= $user['peran'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input type="hidden" name="status_aktif" value="<?= $user['status_aktif'] ?>">
                            <input class="form-check-input" type="checkbox" id="status_aktif" name="status_aktif_checkbox" 
                                   value="1"
                                   <?= $user['status_aktif'] ? 'checked' : '' ?>
                                   <?= $isOnlyAdmin ? 'disabled' : '' ?>>
                            <label class="form-check-label" for="status_aktif">User Aktif</label>
                        </div>
                        <?php if ($isOnlyAdmin): ?>
                            <div class="form-text text-warning mt-2">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Tidak dapat menonaktifkan akun karena ini adalah satu-satunya admin aktif di sistem.
                            </div>
                        <?php endif; ?>
                    </div>

                    <hr class="my-4">
                    <h6 class="text-muted mb-3"><i class="fas fa-key me-2"></i>Ubah Password (Opsional)</h6>
                    <p class="text-muted small mb-3">Kosongkan jika tidak ingin mengubah password.</p>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password Baru</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Masukkan password baru" minlength="6">
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">Minimal 3 karakter (kosongkan jika tidak diubah)</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="Ulangi password baru">
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
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
                <h5 class="modal-title" id="confirmModalLabel"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Konfirmasi Perubahan User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Apakah perubahan berikut sudah benar?</p>
                <div class="alert alert-light border rounded p-3">
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Username:</div>
                        <div class="col-8" id="confirmUsername">-</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Nama:</div>
                        <div class="col-8" id="confirmNama">-</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">No HP:</div>
                        <div class="col-8" id="confirmNoHp">-</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Peran:</div>
                        <div class="col-8">
                            <span class="badge bg-primary" id="confirmPeran">-</span>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Password:</div>
                        <div class="col-8" id="confirmPassword"><em>Tidak diubah</em></div>
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
                <button type="button" class="btn btn-primary" onclick="submitForm()"><i class="fas fa-check me-1"></i>Konfirmasi</button>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);
        const icon = this.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
});

// Show confirmation modal
function showConfirmModal() {
    const username = document.getElementById('username').value.trim() || '-';
    const namaLengkap = document.getElementById('nama_lengkap').value.trim() || '-';
    const noHp = document.getElementById('no_hp').value.trim() || 'Belum diisi';
    const peranSelect = document.getElementById('peran');
    const peranText = peranSelect.options[peranSelect.selectedIndex].text;
    const password = document.getElementById('password').value;
    const statusAktif = document.getElementById('status_aktif').checked;
    
    document.getElementById('confirmUsername').textContent = username;
    document.getElementById('confirmNama').textContent = namaLengkap;
    document.getElementById('confirmNoHp').textContent = noHp;
    document.getElementById('confirmPeran').textContent = peranText;
    document.getElementById('confirmPassword').innerHTML = password.length > 0 ? '<span class="text-success"><i class="fas fa-check me-1"></i> Akan diubah</span>' : '<em class="text-muted">Tidak diubah</em>';
    document.getElementById('confirmStatus').textContent = statusAktif ? 'Aktif' : 'Tidak Aktif';
    document.getElementById('confirmStatus').className = statusAktif ? 'badge bg-success' : 'badge bg-secondary';
    
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    modal.show();
}

function submitForm() {
    document.getElementById('userForm').submit();
}

// Realtime validation
document.addEventListener('DOMContentLoaded', function() {
    const userId = <?= $id ?>;
    const usernameInput = document.getElementById('username');
    const namaLengkapInput = document.getElementById('nama_lengkap');
    const noHpInput = document.getElementById('no_hp');
    const passwordInput = document.getElementById('password');
    const konfirmasiPasswordInput = document.getElementById('confirm_password');
    const form = document.getElementById('userForm');

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

    // Username validation
    usernameInput.addEventListener('input', function() {
        const username = this.value;
        if (username.length === 0) return clearError(this);
        if (username.length < 3) return showError(this, 'Username minimal 3 karakter!');
        if (!/^[a-zA-Z0-9_]+$/.test(username)) return showError(this, 'Username hanya boleh huruf, angka, dan underscore!');
        return clearError(this);
    });

    // Username blur - check uniqueness (exclude current user)
    usernameInput.addEventListener('blur', function() {
        const username = this.value;
        if (username.length >= 3 && /^[a-zA-Z0-9_]+$/.test(username)) {
            fetch('../../api/check_username_edit.php?username=' + encodeURIComponent(username) + '&exclude_id=' + userId)
                .then(response => response.json())
                .then(data => {
                    if (data.exists) showError(this, 'Username sudah digunakan!');
                    else clearError(this);
                })
                .catch(() => {});
        }
    });

    namaLengkapInput.addEventListener('input', function() {
        const nama = this.value;
        if (nama.length === 0) return clearError(this);
        if (nama.length < 2) return showError(this, 'Nama lengkap minimal 2 karakter!');
        return clearError(this);
    });

    function validateNoHp() {
        const noHp = noHpInput.value;
        const peran = document.getElementById('peran').value;
        const isRequired = peran === 'anggota';
        
        if (!isRequired && noHp.length === 0) return clearError(noHpInput);
        if (isRequired && noHp.length === 0) return showError(noHpInput, 'Nomor HP wajib diisi untuk Peran Anggota!');
        if (!/^[0-9]*$/.test(noHp)) return showError(noHpInput, 'Nomor HP hanya boleh berisi angka!');
        if (noHp.length > 0 && noHp.length < 10) return showError(noHpInput, 'Nomor HP minimal 10 digit!');
        if (noHp.length > 15) {
            noHpInput.value = noHp.slice(0, 15);
            return showError(noHpInput, 'Nomor HP maksimal 15 digit!');
        }
        return clearError(noHpInput);
    }

    noHpInput.addEventListener('input', validateNoHp);

    // Phone number blur - check uniqueness (exclude current user)
    noHpInput.addEventListener('blur', function() {
        const noHp = this.value;
        const peran = document.getElementById('peran').value;
        const isRequired = peran === 'anggota';
        
        if ((isRequired && noHp.length >= 10) || (!isRequired && noHp.length >= 10)) {
            fetch('../../api/check_no_hp.php?no_hp=' + encodeURIComponent(noHp) + '&exclude_id=' + userId)
                .then(response => response.json())
                .then(data => {
                    if (data.exists) showError(this, 'Nomor HP sudah digunakan!');
                    else clearError(this);
                })
                .catch(() => {});
        }
    });

    document.getElementById('peran').addEventListener('change', function() {
        const isRequired = this.value === 'anggota';
        document.getElementById('no_hp_required').style.display = isRequired ? 'inline' : 'none';
        if (isRequired) validateNoHp();
        else clearError(noHpInput);
    });

    // Initialize required indicator on page load
    const peran = document.getElementById('peran').value;
    const isRequired = peran === 'anggota';
    document.getElementById('no_hp_required').style.display = isRequired ? 'inline' : 'none';

    // Password validation (optional)
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        if (password.length === 0) return clearError(this);
        if (password.length < 3) return showError(this, 'Password minimal 3 karakter!');
        return clearError(this);
    });

    // Konfirmasi password validation (realtime)
    konfirmasiPasswordInput.addEventListener('input', function() {
        const password = passwordInput.value;
        const konfirmasi = this.value;
        if (konfirmasi.length === 0) return clearError(this);
        if (password !== konfirmasi) return showError(this, 'Konfirmasi password tidak cocok!');
        return clearError(this);
    });

    // Password match on both fields
    passwordInput.addEventListener('input', function() {
        const konfirmasi = konfirmasiPasswordInput.value;
        if (konfirmasi.length > 0 && this.value !== konfirmasi) {
            konfirmasiPasswordInput.classList.add('is-invalid');
            const parent = konfirmasiPasswordInput.parentElement.parentElement;
            let errorDiv = parent.querySelector('.invalid-feedback');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback d-block';
                parent.appendChild(errorDiv);
            }
            errorDiv.textContent = 'Konfirmasi password tidak cocok!';
        } else if (konfirmasi.length > 0) {
            clearError(konfirmasiPasswordInput);
        }
    });

    // Form submission validation
    form.addEventListener('submit', function(e) {
        let isValid = true;
        const requiredFields = [usernameInput, namaLengkapInput];
        
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
</script>

<?php include '../../includes/footer.php'; ?></new_str

