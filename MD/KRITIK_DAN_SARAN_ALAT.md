# Kritik dan Saran — Modul Alat

## Ringkasan
Dokumen ini berisi pengamatan, kritik, dan saran perbaikan untuk modul `alat` pada sistem Hadrahin. Tujuan: meningkatkan keandalan, keamanan, dan pengalaman pengguna.

## Kritik / Temuan
- Validasi input lemah: beberapa parameter (mis. nama alat, jumlah) tampak hanya divalidasi di sisi klien atau tidak ketat di server.
- Kemungkinan duplikasi entri: tidak ada mekanisme kuat untuk mencegah data alat yang sama dimasukkan berkali-kali.
- Error handling terbatas: pesan kesalahan sering generik (500) sehingga sulit melakukan debugging. Tidak semua operasi memberikan feedback yang jelas ke pengguna.
- Akses dan otorisasi: perubahan alat tampaknya dapat diakses tanpa pengecekan role yang tegas di beberapa endpoint API.
- Upload file (jika ada): tidak terlihat pemeriksaan tipe/ukuran file, rawan upload file tidak diinginkan.
- UX: formulir tambah/edit tidak menampilkan validasi real-time atau konfirmasi yang memadai untuk operasi berisiko.

## Saran Perbaikan
- Perkuat validasi sisi server: gunakan whitelist/regex untuk nama, batas untuk jumlah, dan sanitasi input untuk menghindari injection.
- Tambahkan pengecekan duplikat: sebelum insert, cek kombinasi kunci unik (mis. nama + tipe) dan berikan opsi merge atau tolak.
- Perbaiki error handling: tangkap exception spesifik dan kembalikan pesan yang informatif; log error lengkap ke file log terpisah.
- Terapkan kontrol akses: pastikan endpoint CRUD hanya bisa diakses oleh role yang sesuai (admin/petugas), dengan pengecekan sesi dan CSRF token.
- Validasi upload file: batasi tipe MIME, ukuran maksimum, dan simpan file dengan nama acak; jalankan scanning sederhana jika memungkinkan.
- Tingkatkan UX: tambahkan validasi real-time menggunakan JS, konfirmasi sebelum hapus, dan pesan sukses/gagal yang jelas.
- Tambahkan audit trail: simpan riwayat perubahan alat (siapa, kapan, apa yang diubah) untuk keperluan audit.

## Prioritas Implementasi (saran)
1. Validasi dan kontrol akses (tinggi)
2. Error handling & logging (tinggi)
3. Cek duplikat & audit trail (sedang)
4. Validasi upload & UX improvements (rendah–sedang)

## Notes untuk Developer
- Periksa file modules/alat untuk titik masuk API dan tambahkan unit/integration tests untuk operasi CRUD.
- Pertimbangkan menggunakan prepared statements atau ORM untuk mencegah SQL injection.

---
Dokumen ini dibuat untuk membantu tim memperkuat modul `alat` dan meminimalkan risiko operasional.

## Kritik pada Logika Bisnis (Business Logic)
- Pembaruan kuantitas/tersedia: logika yang mengubah stok/kuantitas tampak rentan terhadap race condition jika dua request paralel mengubah nilai yang sama tanpa mekanisme locking atau transaksi.
- Kurangnya atomicity: operasi kompleks (mis. meminjam/mengembalikan alat) tidak tampak dibungkus dalam transaksi DB, berisiko meninggalkan data inkonsisten saat error.
- Validasi aturan bisnis di server: beberapa aturan (mis. tidak boleh mengurangi jumlah di bawah 0, batas peminjaman) harus ditegakkan di server, bukan hanya di UI.
- Idempotensi API: endpoint yang mengubah state tidak selalu idempotent; panggilan ulang bisa menyebabkan efek ganda.
- Penghapusan data tanpa cek dependensi: menghapus entri alat mungkin tidak memeriksa referensi (pinjaman, riwayat), menyebabkan referential integrity terputus.
- Normalisasi dan unit konsistensi: tidak ada jaminan bahwa unit ukuran atau tipe alat konsisten di seluruh entri, menyulitkan agregasi dan perbandingan.

## Saran Perbaikan pada Logika
- Gunakan transaksi DB untuk operasi multi-step: bungkus insert/update/delete terkait dalam transaksi sehingga rollback terjadi saat error.
- Terapkan locking atau optimistic concurrency control: tambahkan `version`/`updated_at` dan cek saat update, atau gunakan SELECT ... FOR UPDATE untuk operasi kritis.
- Validasi aturan bisnis di server: buat lapisan service yang memeriksa batasan (stok tidak negatif, batas peminjaman per anggota) sebelum commit.
- Buat API idempotent atau sediakan token idempotensi: untuk operasi yang bisa dipanggil ulang (mis. konfirmasi pembayaran atau pengiriman), gunakan token idempotensi.
- Jaga referential integrity: tambahkan foreign key di DB dan larang penghapusan jika masih ada referensi, atau gunakan soft-delete dengan audit.
- Standarisasi data: gunakan enum/lookup table untuk tipe/unit alat, serta normalisasi nama untuk menghindari duplikat semantik.
- Tambahkan tes logika bisnis: buat test otomatis untuk skenario concurrency, rollback, dan aturan batasan bisnis.

## Rekomendasi Teknis Singkat
- Database: tambahkan constraint unik, foreign keys, dan index pada kolom yang sering dicari.
- API: gunakan HTTP status codes yang sesuai (409 untuk conflict, 422 untuk validation error).
- Backend: gunakan prepared statements / PDO dengan transaksi; hindari concatenated SQL.
- Observabilitas: tambahkan metrik dan logging yang mencatat operasi CRUD penting beserta user-id.

---
Update ini menambahkan fokus pada risiko logika bisnis dan langkah mitigasinya.
