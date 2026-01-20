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
        │ status_hadir     │  │   kategori        │    │
        │ catatan          │  │   jumlah          │    │
        └──────────┬───────┘  │   keterangan      │    │
                   │          │   tanggal         │    │
                   │          │   bukti_transaksi │    │
                   │          └───────────────────┘    │
                   │                                   │
                   ▼                                   │
        ┌──────────────────┐                           │
        │ jadwal_latihan   │                           │
        ├──────────────────┤                           │
        │ id_jadwal (PK)   │                           │
        │ tanggal          │                           │
        │ jam_mulai        │                           │
        │ jam_selesai      │                           │
        │ lokasi           │                           │
        │ status           │                           │
        │ materi_latihan   │                           │
        │ catatan          │                           │
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
│       │ jam_mulai            │  │     └──────────────────────┘
│       │ lokasi               │  │
│       │ status               │  │
│       └──────────┬───────────┘  │
│                  │              │
│                  ▼              │
│       ┌──────────────────────┐  │
│       │ dokumentasi_acara    │  │
│       ├──────────────────────┤  │
│       │ id_dokumentasi (PK)  │  │
│       │ id_booking (FK)      │  │
│       │ file_path            │  │
│       │ tipe_file            │  │
│       │ keterangan           │  │
│       └──────────────────────┘  │
│                                 │
└─────────────────────────────────┴──────────────────────────────────────────┘
```

---

## Ringkasan Relasi

Database hadrah memiliki beberapa relasi foreign key yang menghubungkan antar tabel. Tabel **absen_latihan** memiliki dua foreign key, yaitu `id_jadwal` yang mengacu ke tabel **jadwal_latihan** untuk mencatat jadwal latihan mana yang diabsen, dan `id_user` yang mengacu ke tabel **user** untuk mencatat siapa yang hadir. Tabel **keuangan** juga berelasi dengan tabel **user** melalui kolom `id_user` untuk mencatat siapa yang melakukan transaksi keuangan. Sementara itu, tabel **dokumentasi_acara** terhubung ke tabel **booking_acara** melalui `id_booking` untuk menyimpan file dokumentasi dari setiap acara yang di-booking. Tabel **booking_acara** juga memiliki foreign key `id_dresscode` yang mengacu ke tabel **dresscode** untuk menentukan pakaian yang digunakan pada acara tersebut.

Terdapat tiga tabel yang bersifat independent atau menjadi referensi utama. Tabel **alat** berdiri sendiri tanpa relasi apapun dengan tabel lain, digunakan untuk mencatat inventaris alat hadrah. Tabel **jadwal_latihan** menjadi tabel referensi yang dirujuk oleh tabel absen_latihan, namun tidak memiliki foreign key ke tabel lain. Tabel **dresscode** menjadi tabel referensi yang dirujuk oleh booking_acara, berfungsi untuk mengelola data pakaian/seragam yang dapat digunakan berulang kali.

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
✓ Tabel **alat** berdiri sendiri tanpa relasi apapun