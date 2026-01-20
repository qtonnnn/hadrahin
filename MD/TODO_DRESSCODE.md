# TODO - Update Dokumentasi Dresscode

## Tujuan
Mengupdate dokumentasi sistem untuk mencakup tabel dresscode dan relasinya.

## Langkah-langkah

### 1. Update config/hadrahin.sql
- [x] Tambah CREATE TABLE `dresscode`
- [x] Tambah ALTER TABLE `booking_acara` untuk FK
- [x] Tambah INSERT data contoh dresscode

### 2. Update config/penjelasan
- [ ] Tambah dokumentasi tabel `dresscode`
- [ ] Update diagram struktur database
- [ ] Update alur penggunaan

### 3. Update MD/ER_DIAGRAM.md
- [ ] Tambah tabel `dresscode` di diagram
- [ ] Tambah relasi FK ke `booking_acara`
- [ ] Update ringkasan relasi

### 4. Update MD/DIAGRAM_ALUR_SISTEM.md
- [ ] Tambah modul dresscode di arsitektur
- [ ] Tambah alur pengelolaan dresscode
- [ ] Update matriks hak akses

## Status: 
- √ config/hadrahin.sql SELESAI
- √ config/penjelasan SELESAI
- √ MD/ER_DIAGRAM.md SELESAI
- √ MD/DIAGRAM_ALUR_SISTEM.md SELESAI

