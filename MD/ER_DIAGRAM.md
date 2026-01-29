
# Entity Relationship Diagram - Hadrah Database

Visualisasi relasi database sistem informasi grup hadrah dengan tata letak yang lebih terstruktur.

---

## Diagram Relasi Database

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                            TABEL DENGAN RELASI                               │
└─────────────────────────────────────────────────────────────────────────────┘

                         ┌──────────────────────┐
                         │        user          │
                         ├──────────────────────┤
                         │ id_user (PK)         │
                         │ username             │
                         │ password             │
                         │ nama_lengkap         │
                         │ no_hp                │
                         │ peran                │
                         │ status_aktif         │
                         │ created_at           │
                         │ updated_at           │
                         │ user_modified        │
                         │ user_record          │
                         └──────────┬───────────┘
                                    │
                    ┌───────────────┼───────────────┐
                    │               │               │
                    ▼               ▼               ▼
        ┌──────────────────┐  ┌───────────────────┐
        │ absen_latihan    │  │    keuangan       │
        ├──────────────────┤  ├───────────────────┤
        │ id_absen (PK)    │  │ id_kas (PK)       │
        │ id_jadwal (FK) ──┼──┼─┐ id_user (FK)    │◄───┐
        │ id_user (FK) ────┼──┼─┘ tipe            │    │
        │ status_hadir     │  │   jumlah          │    │
        │ jam_absen        │  │   keterangan      │    │
        │ created_at       │  │   tanggal         │    │
        │ updated_at       │  │   created_at      │    │
        │ user_modified    │  │   updated_at      │    │
        │ user_record      │  │   user_modified   │    │
        └──────────┬───────┘  │   user_record     │    │
                   │          └───────────────────┘    │
                   │                                   │
                   ▼                                   │
        ┌──────────────────┐                           │
        │ jadwal_latihan   │                           │
        ├──────────────────┤                           │
        │ id_jadwal (PK)   │                           │
        │ tanggal          │                           │
        │ jam_mulai        │                           │
        │ lokasi           │                           │
        │ status           │                           │
        │ catatan          │                           │
        │ created_at       │                           │
        │ updated_at       │                           │
        │ user_modified    │                           │
        │ user_record      │                           │
        └──────────────────┘                           │
                                                       │
                                                       │
┌──────────────────────────────────────────────────────┘
│
│       ┌──────────────────────┐        ┌──────────────────────┐
│       │   booking_acara      │◄───────│      dresscode       │
│       ├──────────────────────┤        ├──────────────────────┤
│       │ id_booking (PK)      │        │ id_dresscode (PK)    │
│       │ id_user (FK) ────────┼──┐     │ nama_pakaian         │
│       │ id_dresscode (FK) ───┼──┼────►│ deskripsi            │
│       │ nama_acara           │  │     │ warna                │
│       │ nama_pemesan         │  │     │ status               │
│       │ no_hp_pemesan        │  │     │ created_at           │
│       │ tanggal_acara        │  │     │ updated_at           │
│       │ lokasi               │  │     │ user_modified        │
│       │ status               │  │     │ user_record          │
│       │ created_at           │  │     └──────────────────────┘
│       │ updated_at           │  │
│       │ user_modified        │  │
│       │ user_record          │  │
│       └──────────┬───────────┘  │
│                  │              │
│                  ▼              │
│       ┌──────────────────────┐  │
│       │ dokumentasi_acara    │  │
│       ├──────────────────────┤  │
│       │ id_dokumentasi (PK)  │  │
│       │ id_booking (FK)      │  │
│       │ file_path            │  │
│       │ keterangan           │  │
│       │ created_at           │  │
│       │ updated_at           │  │
│       │ user_modified        │  │
│       │ user_record          │  │
│       └──────────────────────┘  │
│                                 │
└─────────────────────────────────┴──────────────────────────────────────────┘

       ┌───────────────────────────────────────────┐
       │               TABEL ALAT                  │
       ├───────────────────────────────────────────┤
       │ id_alat (PK)                              │
       │ nama_alat                                 │
       │ jumlah_baik                               │
       │ jumlah_rusak                              │
       │ id_user (FK) ─────────────────────────┐   │
       │ created_at                           │   │
       │ updated_at                           │   │
       │ user_modified                        │   │
       │ user_record                          │   │
       └───────────────────────────────────────┘   │
                                                 │
                                                 └──────────────────────────► (FK ke user, ON DELETE SET NULL)
```

---

## Ringkasan Relasi

Database hadrah memiliki beberapa relasi foreign key yang menghubungkan antar tabel. Tabel **absen_latihan** memiliki dua foreign key, yaitu `id_jadwal` yang mengacu ke tabel **jadwal_latihan** untuk mencatat jadwal latihan mana yang diabsen, dan `id_user` yang mengacu ke tabel **user** untuk mencatat siapa yang hadir. Tabel **keuangan** juga berelasi dengan tabel **user** melalui kolom `id_user` untuk mencatat siapa yang melakukan transaksi keuangan. Sementara itu, tabel **dokumentasi_acara** terhubung ke tabel **booking_acara** melalui `id_booking` untuk menyimpan file dokumentasi dari setiap acara yang di-booking. Tabel **booking_acara** juga memiliki foreign key `id_dresscode` yang mengacu ke tabel **dresscode** untuk menentukan pakaian yang digunakan pada acara tersebut.

Terdapat beberapa tabel yang berdiri sendiri atau menjadi referensi utama. Tabel **alat** memiliki foreign key `id_user` (dapat NULL) yang mengacu ke tabel **user** untuk mencatat siapa yang terakhir mengupdate data alat, namun secara default berdiri sendiri tanpa relasi wajib. Tabel **jadwal_latihan** menjadi tabel referensi yang dirujuk oleh tabel absen_latihan, namun tidak memiliki foreign key ke tabel lain. Tabel **dresscode** menjadi tabel referensi yang dirujuk oleh booking_acara, berfungsi untuk mengelola data pakaian/seragam yang dapat digunakan berulang kali.

Semua tabel (kecuali user) memiliki kolom audit untuk tracking: `created_at`, `updated_at`, `user_modified` (user yang terakhir modify), dan `user_record` (user yang membuat record).

---

## Legenda

- **PK** = Primary Key
- **FK** = Foreign Key
- **▼** = Arah relasi (dari parent ke child)
- **◄** = Relasi balik ke tabel user

---

## Catatan Penting

✓ Semua relasi foreign key dipertahankan sesuai struktur asli
✓ Tabel **user** adalah tabel utama yang berelasi dengan keuangan dan absen_latihan
✓ Tabel **jadwal_latihan** TIDAK terhubung dengan **booking_acara**
✓ Tabel **alat** memiliki foreign key `id_user` (dapat NULL, ON DELETE SET NULL)
✓ Semua tabel memiliki kolom audit: `created_at`, `updated_at`, `user_modified`, `user_record`
✓ Kolom **no_hp_pemesan** berada di tabel **booking_acara** (bukan di tabel keuangan)

