# TODO - Change Password Feature

## Summary
Implementasi fitur "Ubah Password Sendiri" untuk pembina dan anggota dengan modal pada halaman login.

## Files Created/Modified

### Created
- [x] `api/change_password.php` - API endpoint untuk memproses perubahan password
- [x] `api/check_username_exists.php` - API endpoint untuk validasi username real-time

### Modified
- [x] `auth/login.php` - Menambahkan tombol "Ubah Password" dan modal form

## Features Implemented

### API Endpoint (`api/change_password.php`)
- Validasi method POST
- Validasi input: username, password lama, password baru, konfirmasi password
- Verifikasi password lama sebelum update menggunakan `password_verify()`
- Hashing password baru dengan `password_hash()`
- Logging aktivitas perubahan password
- Error handling komprehensif

### API Check Username (`api/check_username_exists.php`)
- Validasi method GET
- Cek eksistensi username di database
- Response format yang aman dengan message default

### Login Page (`auth/login.php`)
- Tombol "Ubah Password" yang minimalis
- Modal popup dengan form Change Password
- Real-time validation untuk setiap field
- Validasi username tidak ditemukan dengan API call
- Indikator visual: border hijau/merah, pesan validasi

## Keamanan
- [x] Validasi input tidak kosong
- [x] Verifikasi password lama sebelum update
- [x] Hashing password dengan PASSWORD_DEFAULT
- [x] Validasi konfirmasi password cocok
- [x] Anti-cache headers
- [x] Error logging

## Perbaikan Terbaru
- [x] Handler JavaScript dengan null safety untuk pesan
- [x] Response default jika message undefined
- [x] Validasi response.ok sebelum parsing JSON

## Testing Checklist
- [ ] Test dengan username yang tidak terdaftar
- [ ] Test dengan password lama yang salah
- [ ] Test dengan password baru terlalu pendek
- [ ] Test dengan konfirmasi password tidak cocok
- [ ] Test dengan data valid - password berubah
- [ ] Test modal dapat dibuka/tutup dengan benar
- [ ] Test close on overlay click
- [ ] Test close on Escape key

