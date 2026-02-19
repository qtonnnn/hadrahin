<?php
/**
 * Tambah Jadwal Latihan Baru - Form dengan sidebar layout
 * Mendukung recurring schedules (jadwal berulang)
 */

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
require_once '../../includes/cache.php';

$page_title = "Tambah Jadwal Latihan";

// Get current user role
$user_peran = $_SESSION['peran'] ?? 'guest';
$user_id = $_SESSION['user_id'] ?? 0;

// Check permission
if ($user_peran !== 'admin') {
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
    
    // Recurring schedule options
    $is_recurring = isset($_POST['is_recurring']) && $_POST['is_recurring'] == 1;
    $recurring_type = $_POST['recurring_type'] ?? 'weekly';
    $recurring_interval = isset($_POST['recurring_interval']) ? (int)$_POST['recurring_interval'] : 1;
    $recurring_end_date = $_POST['recurring_end_date'] ?? '';
    $recurring_days = isset($_POST['recurring_days']) ? $_POST['recurring_days'] : [];

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

    // Validate date is not in the past (for single schedule)
    if (!empty($tanggal) && !$is_recurring) {
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
    
    // Validate recurring options
    if ($is_recurring) {
        if (empty($recurring_end_date)) {
            $errors[] = "Tanggal akhir recurring wajib diisi";
        }
        if (strtotime($recurring_end_date) < strtotime($tanggal)) {
            $errors[] = "Tanggal akhir harus lebih besar dari tanggal mulai";
        }
        if ($recurring_type === 'weekly' && empty($recurring_days)) {
            $errors[] = "Pilih setidaknya satu hari untuk recurring mingguan";
        }
        if ($recurring_interval < 1 || $recurring_interval > 12) {
            $errors[] = "Interval recurring harus antara 1-12";
        }
    }

    // Check for duplicate schedule (only for single schedule)
    if (empty($errors) && !$is_recurring) {
        $stmt = $pdo->prepare("SELECT id_jadwal FROM jadwal_latihan WHERE tanggal = ? AND jam_mulai = ?");
        $stmt->execute([$tanggal, $jam_mulai]);
        if ($stmt->fetch()) {
            $errors[] = "Jadwal latihan pada tanggal dan jam tersebut sudah ada";
        }
    }

    // Insert jadwal(s)
    if (empty($errors)) {
        $inserted_count = 0;
        
        if ($is_recurring) {
            // Generate recurring schedules
            $generated_dates = generateRecurringDates($tanggal, $recurring_end_date, $recurring_type, $recurring_interval, $recurring_days);
            
            $pdo->beginTransaction();
            try {
                foreach ($generated_dates as $date) {
                    // Check duplicate for each date
                    $stmt = $pdo->prepare("SELECT id_jadwal FROM jadwal_latihan WHERE tanggal = ? AND jam_mulai = ?");
                    $stmt->execute([$date, $jam_mulai]);
                    if (!$stmt->fetch()) {
                        $stmt = $pdo->prepare("INSERT INTO jadwal_latihan (tanggal, jam_mulai, lokasi, catatan, status, user_record) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$date, $jam_mulai, $lokasi, $catatan, $status, $user_id]);
                        $inserted_count++;
                    }
                }
                $pdo->commit();
                
                // Add recurring info to catatan if multiple generated
                if ($inserted_count > 1) {
                    $catatan_baru = $catatan . "\n[Recurring: " . ucfirst($recurring_type) . " - {$inserted_count} jadwal dibuat]";
                    $stmt = $pdo->prepare("UPDATE jadwal_latihan SET catatan = ? WHERE tanggal = ? AND jam_mulai = ?");
                    $stmt->execute([$catatan_baru, $tanggal, $jam_mulai]);
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = "Gagal membuat jadwal berulang: " . $e->getMessage();
            }
        } else {
            // Single schedule
            $stmt = $pdo->prepare("INSERT INTO jadwal_latihan (tanggal, jam_mulai, lokasi, catatan, status, user_record) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tanggal, $jam_mulai, $lokasi, $catatan, $status, $user_id]);
            $inserted_count = 1;
        }

        if (empty($errors)) {
            // Clear cache after insert
            Cache::delete('jadwal_stats');
            Cache::delete('jadwal_max_date');

            $msg = $inserted_count > 1 ? 'tambah_banyak_sukes' : 'tambah_sukes';
            header("Location: index.php?msg={$msg}&count={$inserted_count}");
            exit;
        }
    }
}

// Function to generate recurring dates
function generateRecurringDates($start_date, $end_date, $type, $interval, $days) {
    $dates = [];
    $current = new DateTime($start_date);
    $end = new DateTime($end_date);
    
    // Adjust end date to include the end day
    $end = $end->modify('+1 day');
    
    while ($current < $end) {
        switch ($type) {
            case 'daily':
                // Every day
                $dates[] = $current->format('Y-m-d');
                $current->modify("+{$interval} day");
                break;
                
            case 'weekly':
                // Specific days of week
                $current_day = $current->format('w'); // 0 = Sunday
                if (in_array($current_day, $days)) {
                    $dates[] = $current->format('Y-m-d');
                }
                $current->modify('+1 day');
                break;
                
            case 'monthly':
                // Same day each month
                $dates[] = $current->format('Y-m-d');
                $current->modify("+{$interval} month");
                break;
                
            default:
                $dates[] = $current->format('Y-m-d');
                $current->modify('+1 day');
        }
    }
    
    return $dates;
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

                    <!-- Recurring Schedule Options -->
                    <div class="mb-4 p-3 bg-light rounded border">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_recurring" name="is_recurring" value="1" onchange="toggleRecurringOptions()">
                            <label class="form-check-label fw-bold" for="is_recurring">
                                <i class="fas fa-redo me-1"></i>Jadwal Berulang (Recurring)
                            </label>
                        </div>
                        
                        <div id="recurring_options" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="recurring_type" class="form-label">Tipe Pengulangan</label>
                                    <select class="form-select" id="recurring_type" name="recurring_type" onchange="toggleRecurringDays()">
                                        <option value="daily">Harian (Setiap Hari)</option>
                                        <option value="weekly" selected>Mingguan (Setiap Minggu)</option>
                                        <option value="monthly">Bulanan (Setiap Bulan)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="recurring_interval" class="form-label">Interval</label>
                                    <select class="form-select" id="recurring_interval" name="recurring_interval" onchange="calculateMinEndDate()">
                                        <option value="1" selected>Setiap 1</option>
                                        <option value="2">Setiap 2</option>
                                        <option value="3">Setiap 3</option>
                                        <option value="4">Setiap 4</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3" id="recurring_days_section">
                                <label class="form-label">Pilih Hari</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="day_0" name="recurring_days[]" value="0">
                                        <label class="form-check-label" for="day_0">Minggu</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="day_1" name="recurring_days[]" value="1">
                                        <label class="form-check-label" for="day_1">Senin</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="day_2" name="recurring_days[]" value="2">
                                        <label class="form-check-label" for="day_2">Selasa</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="day_3" name="recurring_days[]" value="3">
                                        <label class="form-check-label" for="day_3">Rabu</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="day_4" name="recurring_days[]" value="4">
                                        <label class="form-check-label" for="day_4">Kamis</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="day_5" name="recurring_days[]" value="5">
                                        <label class="form-check-label" for="day_5">Jumat</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="day_6" name="recurring_days[]" value="6">
                                        <label class="form-check-label" for="day_6">Sabtu</label>
                                    </div>
                                </div>
                                <div class="form-text">Pilih hari untuk recurring mingguan</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="recurring_end_date" class="form-label">Tanggal Berakhir</label>
                                <input type="date" class="form-control" id="recurring_end_date" name="recurring_end_date" min="">
                                <div class="form-text">Jadwal akan dibuat hingga tanggal ini</div>
                            </div>
                        </div>
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
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Catatan:</div>
                        <div class="col-8" id="confirmCatatan">-</div>
                    </div>
                    <div class="row mb-2" id="recurringInfoRow" style="display: none;">
                        <div class="col-4 fw-bold">Jadwal:</div>
                        <div class="col-8">
                            <span class="badge bg-info" id="confirmRecurring">-</span>
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
function toggleRecurringOptions() {
    const isRecurring = document.getElementById('is_recurring').checked;
    const optionsDiv = document.getElementById('recurring_options');
    const tanggalInput = document.getElementById('tanggal');
    const endDateInput = document.getElementById('recurring_end_date');
    
    if (isRecurring) {
        optionsDiv.style.display = 'block';
        // Allow past dates for recurring schedules
        tanggalInput.removeAttribute('min');
        // Calculate and set minimum end date based on interval
        calculateMinEndDate();
    } else {
        optionsDiv.style.display = 'none';
        // Reset to only allow future dates
        const today = new Date().toISOString().split('T')[0];
        tanggalInput.setAttribute('min', today);
        // Reset end date
        endDateInput.value = '';
        endDateInput.removeAttribute('min');
    }
}

function calculateMinEndDate() {
    const tanggalInput = document.getElementById('tanggal');
    const endDateInput = document.getElementById('recurring_end_date');
    const recurringType = document.getElementById('recurring_type').value;
    const recurringInterval = parseInt(document.getElementById('recurring_interval').value) || 1;
    
    if (!tanggalInput.value) return;
    
    const startDate = new Date(tanggalInput.value + 'T00:00:00');
    const minEndDate = new Date(startDate);
    
    // Calculate minimum end date based on interval type
    // Use 10 occurrences as minimum for reasonable schedule
    const occurrences = 10;
    
    switch (recurringType) {
        case 'daily':
            minEndDate.setDate(minEndDate.getDate() + (recurringInterval * occurrences));
            break;
        case 'weekly':
            minEndDate.setDate(minEndDate.getDate() + (recurringInterval * 7 * occurrences));
            break;
        case 'monthly':
            minEndDate.setMonth(minEndDate.getMonth() + (recurringInterval * occurrences));
            break;
    }
    
    // Format to YYYY-MM-DD
    const minDateStr = minEndDate.toISOString().split('T')[0];
    endDateInput.min = minDateStr;
    
    // If current end date is less than minimum, update it
    if (endDateInput.value && endDateInput.value < minDateStr) {
        endDateInput.value = minDateStr;
    }
}

function toggleRecurringDays() {
    const type = document.getElementById('recurring_type').value;
    const daysSection = document.getElementById('recurring_days_section');
    const endDateInput = document.getElementById('recurring_end_date');
    
    if (type === 'weekly') {
        daysSection.style.display = 'block';
    } else {
        daysSection.style.display = 'none';
    }
    
    // Recalculate min end date when type changes
    if (document.getElementById('is_recurring').checked) {
        calculateMinEndDate();
    }
}

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

    // Update end date min when start date changes
    tanggalInput.addEventListener('change', function() {
        const endDateInput = document.getElementById('recurring_end_date');
        if (endDateInput && this.value) {
            endDateInput.min = this.value;
            // If end date is before new start date, reset it
            if (endDateInput.value && endDateInput.value < this.value) {
                endDateInput.value = this.value;
            }
        }
    });

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
    
    // Get recurring info
    const isRecurring = document.getElementById('is_recurring').checked;
    const recurringInfoRow = document.getElementById('recurringInfoRow');
    const confirmRecurring = document.getElementById('confirmRecurring');
    
    if (isRecurring) {
        const recurringType = document.getElementById('recurring_type').value;
        const recurringInterval = document.getElementById('recurring_interval').value;
        const recurringEndDate = document.getElementById('recurring_end_date').value;
        const recurringDays = document.querySelectorAll('input[name="recurring_days[]"]:checked');
        
        let typeText = '';
        let daysText = '';
        
        switch(recurringType) {
            case 'daily': typeText = 'Harian'; break;
            case 'weekly': typeText = 'Mingguan'; break;
            case 'monthly': typeText = 'Bulanan'; break;
        }
        
        if (recurringType === 'weekly') {
            const days = [];
            recurringDays.forEach(d => days.push(d.nextElementSibling.textContent.trim()));
            daysText = days.length > 0 ? `(${days.join(', ')})` : '';
        }
        
        const endDateFormatted = recurringEndDate ? new Date(recurringEndDate + 'T00:00:00').toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }) : '-';
        
        confirmRecurring.innerHTML = `<i class="fas fa-redo me-1"></i>${typeText} ${daysText}<br><small>Setiap ${recurringInterval} | Sampai ${endDateFormatted}</small>`;
        recurringInfoRow.style.display = 'flex';
    } else {
        recurringInfoRow.style.display = 'none';
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

