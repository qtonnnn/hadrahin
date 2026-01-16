Baik. Kita **ulang dan jelaskan SEMUA dari nol**, versi **utuh tapi tetap waras**, supaya kalau besok kamu buka lagi proyek ini, kamu tidak bertanya “ini tabel buat apa sih dulu”.

Anggap ini **dokumentasi sistem versi manusia**, bukan bacaan skripsi.

---

# GAMBARAN UMUM SISTEM

Ini adalah **sistem manajemen tim hadrah**, dengan fokus:

* latihan
* absensi
* alat
* booking acara
* arsip (history)
* dokumentasi acara

Prinsip besar yang kamu pakai (dan ini bagus):

* admin & pembina pegang kontrol
* anggota pasif secara input
* history dipisah dari data aktif
* dokumentasi hanya untuk acara yang selesai

Sekarang kita masuk satu per satu.

---

## 1. Tabel `user`

**Peran:**
Menyimpan **SEMUA orang** di sistem.

Isi:

* admin
* pembina
* anggota

Fungsi utama:

* login & hak akses
* referensi ke absensi
* audit (siapa input, siapa edit)

Kenapa penting:

* semua aktivitas sistem ujung-ujungnya balik ke `user`
* satu sumber kebenaran untuk manusia

Dipakai oleh:

* `absen_latihan`
* `jadwal_latihan` (via user_record)
* `booking_acara`
* `alat`
* history & dokumentasi

---

## 2. Tabel `jadwal_latihan`

**Peran:**
Menyimpan **rencana latihan**.

Isi:

* tanggal
* jam
* lokasi
* status (direncanakan / selesai / dibatalkan)

Catatan penting:

* ❌ tidak ada dresscode
* ❌ tidak ada materi khusus
* latihan = bebas seragam

Kenapa dibuat terpisah:

* latihan itu kegiatan rutin
* bukan acara
* punya siklus sendiri

Dipakai oleh:

* `absen_latihan`
* `history_latihan`

---

## 3. Tabel `absen_latihan`

**Peran:**
Ini **ABSENSI LATIHAN RESMI**.

Relasi:

* user ↔ jadwal_latihan (many-to-many)

Isi:

* siapa
* di latihan mana
* status hadir (hadir / izin / alpa)

Aturan sistem:

* diinput oleh **admin atau pembina**
* anggota tidak absen sendiri
* satu user hanya satu absen per jadwal

Kenapa penting:

* dasar evaluasi
* dasar laporan
* bukti kedisiplinan

---

## 4. Tabel `alat`

**Peran:**
Inventaris alat hadrah.

Isi:

* nama alat
* jumlah
* kondisi
* catatan

Catatan desain:

* sengaja **sederhana**
* belum ada peminjaman
* belum ada relasi acara

Kenapa:

* inventaris harus stabil dulu
* fitur lanjutan nanti

---

## 5. Tabel `booking_acara`

**Peran:**
Mengelola **permintaan dan pelaksanaan acara**.

Isi:

* nama acara
* pemesan & kontak
* tanggal & lokasi
* dresscode
* status (menunggu / diterima / ditolak / selesai)

Aturan penting:

* diinput oleh **admin**
* pembina boleh ikut mengubah status
* dicek bentrok jadwal

Ini pusat koordinasi acara.

---

## 6. Kontrol Bentrok Jadwal

**Fungsi logika (bukan tabel):**

* mencegah dua acara di tanggal sama
* atau bentrok jam (opsional)

Status yang dihitung:

* menunggu
* diterima

Yang tidak dihitung:

* ditolak
* selesai

Sistem:

* memberi peringatan
* keputusan tetap di admin

---

## 7. Tabel `history_latihan`

**Peran:**
Arsip latihan yang **SUDAH SELESAI**.

Isi:

* ringkasan latihan
* total hadir / izin / alpa
* catatan akhir

Sifat:

* read-only
* tidak diubah ulang
* untuk laporan & evaluasi

Absensi detail tetap di `absen_latihan`.

---

## 8. Tabel `history_acara`

**Peran:**
Arsip acara yang **SUDAH SELESAI**.

Isi:

* snapshot acara
* tanggal, lokasi
* dresscode
* catatan

Kenapa tidak pakai `booking_acara` saja?

* booking = data hidup
* history = arsip
* fungsi berbeda

---

## 9. Tabel `dokumentasi_acara`

**Peran:**
Menyimpan **foto dokumentasi acara**.

Relasi:

* hanya ke `history_acara`

Isi:

* path file gambar
* keterangan foto
* siapa yang upload

Aturan keras:

* ❌ tidak bisa upload sebelum acara selesai
* ❌ gambar tidak disimpan di database
* ✅ database hanya simpan path

Ini bikin dokumentasi:

* rapi
* tidak bisa dimanipulasi
* jelas milik acara apa

---

## ALUR BESAR SISTEM (DARI AWAL SAMPAI ARSIP)

### Latihan

1. Admin buat `jadwal_latihan`
2. Latihan berjalan
3. Admin/pembina isi `absen_latihan`
4. Status jadwal → `selesai`
5. Masuk `history_latihan`

### Acara

1. Pemesan hubungi admin
2. Admin input `booking_acara`
3. Cek bentrok
4. Status diputuskan
5. Acara berlangsung
6. Status → `selesai`
7. Masuk `history_acara`
8. Upload foto ke `dokumentasi_acara`

---


### ERD SISTEM MANAJEMEN TIM HADRAH
1. Daftar Entitas (Tabel)

Entitas utama yang kita punya:

user

jadwal_latihan

absen_latihan

alat

booking_acara

history_latihan

history_acara

dokumentasi_acara

---

USER
----
id_user (PK)
nama_user
peran
...

   |1
   |
   |∞
ABSEN_LATIHAN
-------------
id_absen (PK)
id_user (FK)
id_jadwal (FK)
status_hadir

   ∞
   |
   |1
JADWAL_LATIHAN
--------------
id_jadwal (PK)
tanggal
status
...


JADWAL_LATIHAN
--------------
id_jadwal (PK)
   |
   |1
   |
   |1
HISTORY_LATIHAN
---------------
id_history_latihan (PK)
id_jadwal (FK)
total_hadir
...


BOOKING_ACARA
-------------
id_booking (PK)
nama_acara
status
dresscode
   |
   |1
   |
   |1
HISTORY_ACARA
-------------
id_history_acara (PK)
id_booking (FK)
nama_acara
tanggal_acara
   |
   |1
   |
   |∞
DOKUMENTASI_ACARA
-----------------
id_dokumentasi (PK)
id_history_acara (FK)
file_path


ALAT
----
id_alat (PK)
nama_alat
jumlah
kondisi

----


1️⃣ user ↔ jadwal_latihan

❌ Tidak langsung

Kenapa?

relasinya many-to-many

diselesaikan oleh tabel absen_latihan

2️⃣ user ↔ absen_latihan

Relasi:

user (1) → absen_latihan (∞)

Artinya:

satu user bisa punya banyak absensi

tapi tiap absensi cuma milik satu user

3️⃣ jadwal_latihan ↔ absen_latihan

Relasi:

jadwal_latihan (1) → absen_latihan (∞)

Artinya:

satu jadwal punya banyak data absensi

📌 Inilah inti sistem absensi latihan.

4️⃣ jadwal_latihan ↔ history_latihan

Relasi:

1 : 1

Artinya:

satu jadwal latihan

menghasilkan satu arsip history saat selesai

History tidak ada tanpa jadwal.

5️⃣ booking_acara ↔ history_acara

Relasi:

1 : 1

Artinya:

satu booking acara

menghasilkan satu history acara setelah selesai

Booking = proses
History = arsip

6️⃣ history_acara ↔ dokumentasi_acara

Relasi:

history_acara (1) → dokumentasi_acara (∞)

Artinya:

satu acara bisa punya banyak foto

satu foto hanya milik satu acara

📸 Dokumentasi tidak boleh hidup tanpa history.

7️⃣ alat

Saat ini:

belum berelasi langsung

Ini disengaja.

inventaris dulu

pemakaian alat = tahap lanjutan

ERD yang baik itu tidak maksa semua tabel saling nempel.