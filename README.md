# MELORA KOSMETIK – INVENTORY MANAGEMENT SYSTEM
### Mini Project 3 Pertemuan 4: Relational Database + Advanced PHP

Melora Kosmetik adalah aplikasi pengelolaan persediaan (Inventory Management System) produk kecantikan berbasis web yang dibangun menggunakan **PHP Native 8+** dan **MySQL/MariaDB Relasional** sesuai dengan seluruh silabus dan instruksi pada **PPT Pertemuan 4: Relational Database + Advanced PHP**.

Aplikasi ini tidak hanya menyediakan fungsi CRUD standar, melainkan menerapkan integritas basis data relasional, transaksi atomik ACID (`BEGIN`, `COMMIT`, `ROLLBACK`, `SELECT ... FOR UPDATE`), laporan berbasis agregasi SQL, otorisasi berbasis peran (*Role-Based Access Control*), pencegahan injeksi keamanan, serta validasi aturan bisnis nyata.

---

## 1. Identitas Sistem & Fitur Utama

* **Nama Aplikasi:** MELORA KOSMETIK – INVENTORY MANAGEMENT SYSTEM
* **Tema Desain:** Modern, elegan, soft pink (`#e07a9b`, `#fdf7f9`), dusty rose, dan rose gold khas butik kosmetik wanita dengan tipografi Poppins yang bersih dan responsif.
* **Katalog Produk Contoh:** Lip Cream, Lip Tint, Foundation, Cushion, Bedak Padat, Mascara, Eyeliner, Sunscreen Gel, Moisturizer, Gentle Facial Wash, Serum Niacinamide, Toner AHA BHA, Blush On, Eyeshadow Palette.

### Modul & Fitur yang Tersedia:
1. **Autentikasi & Otorisasi:**
   * Login multi-peran (Admin & Staff) dengan verifikasi hash password (`password_verify()`).
   * Regenerasi ID sesi (`session_regenerate_id(true)`) saat login sukses untuk mencegah *session fixation*.
   * Otorisasi sisi server (*server-side authorization*) dengan pengalihan ke halaman **403 Forbidden** yang elegan jika Staff mencoba mengakses area administrasi.
   * Logout dengan penghancuran sesi yang aman.
2. **Dashboard Statistik Dinamis:**
   * 8 Kartu Indikator Utama: Total Produk, Total Kategori, Total Stok Fisik, Estimasi Nilai Inventory (`SUM(stock * price)`), Produk Stok Rendah (`stock <= minimum_stock`), Total Barang Masuk, Total Barang Keluar, dan Rasio Perputaran Stok.
   * Banner peringatan dini produk yang memerlukan restock segera.
   * Tabel transaksi mutasi stok terbaru.
3. **Master Data Kategori Kosmetik (CRUD):**
   * Tambah, lihat, edit, dan hapus kategori kosmetik.
   * Dilengkapi pencarian dan proteksi integritas `ON DELETE RESTRICT` (kategori yang memiliki produk tidak dapat dihapus sembarangan).
4. **Master Data Produk Kosmetik (CRUD & Advance Listing):**
   * Informasi lengkap: SKU unik, nama produk, kategori, merek/brand, harga jual, stok fisik, dan batas minimum stok.
   * Form produk terintegrasi dengan relasi *Many-to-Many* ke supplier dan penentuan harga beli (*supplier price*).
   * **Pencarian (Search):** Pencarian teks berdasarkan nama produk, SKU, atau brand.
   * **Filter Kategori:** Dropdown filter produk berdasarkan kategori terpilih.
   * **Pengurutan (Sorting):** Berdasarkan nama produk, harga, stok, atau SKU dengan arah ASC/DESC menggunakan **Allowlist** anti SQL Injection.
   * **Penomoran Halaman (Pagination):** Pembatasan data per halaman menggunakan formula `LIMIT` dan `OFFSET` dengan mempertahankan parameter filter saat berpindah halaman.
   * **Detail Produk:** Menampilkan informasi produk, status stok, daftar distributor resmi yang memasok, dan histori mutasi khusus produk tersebut.
   * **Proteksi Hapus Produk:** Dilindungi `ON DELETE RESTRICT` jika produk telah memiliki riwayat mutasi transaksi.
5. **Master Data Supplier & Pemasok (CRUD):**
   * Data pemasok: Nama perusahaan, nomor telepon/WhatsApp, email, dan alamat kantor/gudang.
   * Halaman detail supplier menampilkan seluruh produk kosmetik yang dipasok beserta harga belinya.
6. **Transaksi Barang Masuk (Stock In):**
   * Pencatatan penerimaan barang dari distributor/pemasok.
   * Menambah stok fisik produk dan mencatat histori transaksi ke tabel `stock_movements`.
   * Berjalan di dalam satu transaksi database (`beginTransaction`, `commit`, `rollBack`).
7. **Transaksi Barang Keluar (Stock Out):**
   * Pencatatan pengeluaran stok (penjualan kasir, pesanan marketplace, tester display, dll.).
   * Menggunakan penguncian baris `SELECT ... FOR UPDATE`.
   * **Aturan Bisnis Kritis:** Validasi stok mencukupi. Jika jumlah barang keluar melebihi stok yang tersedia, transaksi ditolak dan dibatalkan via `rollBack()`. Stok tidak akan pernah bernilai negatif.
8. **Riwayat Mutasi Transaksi Stok (Audit Trail):**
   * Riwayat komprehensif pergerakan barang dengan pencarian, filter jenis transaksi (Masuk / Keluar), tanggal transaksi, nama operator petugas, dan catatan.
9. **Laporan Inventory & Agregasi SQL:**
   * **Laporan Inventory:** Menampilkan SKU, nama produk, kategori, harga, total barang masuk, total barang keluar, stok akhir, batas minimum, nilai valuasi, dan status restock menggunakan query `JOIN`, `GROUP BY`, `SUM`, `COUNT`, dan `CASE WHEN`.
   * **Laporan Agregat Kategori:** Mengimplementasikan materi PPT Slide 14 & 15 mengenai perbandingan klausa `WHERE` (filter baris sebelum group by) vs `HAVING` (filter kelompok hasil agregasi) serta fungsi agregat `COUNT()`, `SUM()`, `AVG()`, `MIN()`, dan `MAX()`.
   * Tombol cetak (*Print*) dengan format ramah cetak (menyembunyikan sidebar dan navigasi secara otomatis).
10. **Manajemen Pengguna Sistem (Admin Only):**
    * Pengelolaan akun login (`users`) dan profil (`user_profiles`) dengan relasi *One-to-One*.
    * Penentuan peran Admin atau Staff.

---

## 2. Struktur Relasional Database

Database: `melora_kosmetik` (MySQL InnoDB)

| Tabel | Deskripsi | Jenis Relasi |
|---|---|---|
| `users` | Kredensial akun pengguna sistem (username, password_hash, role) | Induk |
| `user_profiles` | Profil lengkap pengguna (full_name, phone, address) | **One-to-One** dengan `users` (`user_id UNIQUE`) |
| `categories` | Kelompok kategori produk kecantikan | **One-to-Many** ke `products` |
| `products` | Katalog produk, SKU unik, harga jual, stok, dan minimum stok | Induk transaksi |
| `suppliers` | Data distributor & pemasok kosmetik | Banyak ke banyak dengan `products` |
| `product_suppliers` | Junction table relasi produk dan supplier dengan harga beli | **Many-to-Many** (`PRIMARY KEY (product_id, supplier_id)`) |
| `stock_movements` | Histori mutasi barang masuk (IN) dan keluar (OUT) | **One-to-Many** dari `products` & `users` |

---

## 3. Akun Pengujian (Demo Credentials)

Sistem telah dilengkapi data awal (seed data) akun untuk pengujian:

| Role | Username | Password | Hak Akses |
|---|---|---|---|
| **Admin** | `admin` | `password` | Akses penuh: Master data, transaksi, laporan, dan manajemen pengguna |
| **Staff** | `staff` | `password` | Akses operasional: Melihat produk & stok, mencatat barang masuk/keluar, melihat laporan. Dilarang mengakses manajemen pengguna (403 Forbidden). |

---

## 4. Cara Instalasi & Menjalankan di XAMPP

1. **Pastikan Apache dan MySQL Berjalan:**
   * Buka **XAMPP Control Panel**, lalu klik **Start** pada modul **Apache** dan **MySQL**.
2. **Lokasi Folder Proyek:**
   * Letakkan folder `melora-kosmetik` di direktori:
     ```
     C:\xampp\htdocs\melora-kosmetik\
     ```
3. **Import Database:**
   * Buka browser dan akses `http://localhost/phpmyadmin`.
   * Klik tab **Import**, pilih file:
     ```
     C:\xampp\htdocs\melora-kosmetik\database\schema.sql
     ```
   * Klik tombol **Import / Go**. Database `melora_kosmetik` beserta seluruh tabel, relasi foreign key, dan data awal akan terbuat secara otomatis.
4. **Buka Aplikasi:**
   * Buka browser dan arahkan ke alamat:
     ```
     http://localhost/melora-kosmetik/public/
     ```
   * Masuk menggunakan akun demo di atas.

---

## 5. Aturan Bisnis (Business Rules) yang Diterapkan

Sesuai materi slide PPT Pertemuan 4:
1. **SKU Unik:** Setiap produk kosmetik wajib memiliki SKU unik yang divalidasi di sisi server sebelum disimpan ke basis data.
2. **Nilai Non-Negatif:** Harga produk, stok, minimum stok, dan jumlah transaksi barang tidak boleh bernilai negatif.
3. **Jumlah Transaksi Positif:** Transaksi barang masuk dan keluar mensyaratkan jumlah unit berupa bilangan bulat lebih dari nol (`quantity > 0`).
4. **Proteksi Stok Negatif:** Barang keluar tidak boleh melebihi stok yang tersedia. Transaksi menggunakan `SELECT ... FOR UPDATE` untuk mencegah *race condition* saat validasi stok.
5. **Integritas Referensial (Referential Integrity):**
   * Kategori yang masih menaungi produk tidak dapat dihapus (`ON DELETE RESTRICT`).
   * Produk yang memiliki riwayat mutasi transaksi stok tidak dapat dihapus (`ON DELETE RESTRICT`).
   * Pengguna yang pernah mencatat transaksi mutasi stok tidak dapat dihapus (`ON DELETE RESTRICT`).
   * Supplier yang terhubung dengan produk tidak dapat dihapus (`ON DELETE RESTRICT`).
6. **Database Transaction (Atomicity):** Perubahan stok fisik pada tabel `products` dan pencatatan riwayat transaksi pada tabel `stock_movements` dibungkus dalam satu transaksi PDO (`beginTransaction`, `commit`, `rollBack`). Jika salah satu tahap gagal, seluruh perubahan dibatalkan.
7. **Keamanan Autentikasi & Sesi:**
   * Password disimpan dalam bentuk hash menggunakan algoritma Bcrypt via `password_hash()`.
   * Verifikasi autentikasi dilakukan menggunakan `password_verify()`.
   * `session_regenerate_id(true)` dipanggil tepat setelah login berhasil untuk mencegah serangan *session hijacking/fixation*.
8. **Otorisasi Sisi Server (Authorization):**
   * Pengecekan peran dilakukan di setiap file backend (`require_admin()`), bukan sekadar menyembunyikan navigasi di sisi antarmuka.
   * Apabila pengguna dengan role Staff mencoba membuka halaman `/users/` atau menghapus kategori/produk, server langsung merespons dengan kode HTTP `403 Forbidden` dan menampilkan halaman penolakan akses.
9. **Pencegahan SQL Injection:** Seluruh query yang menerima masukan dari pengguna menggunakan PDO Prepared Statement (`prepare()` dan `execute()` atau `bindValue()`). Nama kolom pengurutan (*sorting*) divalidasi terhadap array allowlist.
10. **Pencegahan Cross-Site Scripting (XSS):** Seluruh data dinamis dari basis data atau masukan formulir disaring menggunakan fungsi output encoding `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')` melalui helper `e()`.

---

## 6. Struktur Direktori Proyek

```
melora-kosmetik/
│
├── config/
│   └── database.php         # Konfigurasi koneksi PDO MySQL
│
├── helpers/
│   ├── auth.php             # Session, login check, role authorization, flash message
│   ├── validation.php       # Validasi input numerik, integritas, dan keunikan SKU/username
│   └── escape.php           # Output escaping (XSS protection), format rupiah & tanggal
│
├── public/
│   ├── index.php            # Gerbang pengalihan awal (login/dashboard)
│   ├── login.php            # Halaman login modern bernuansa kosmetik
│   ├── logout.php           # Penghancuran sesi dan logout
│   ├── dashboard.php        # Dashboard utama dengan 8 metrik statistik agregat
│   ├── 403.php              # Halaman respon error 403 Forbidden
│   │
│   ├── categories/
│   │   ├── index.php        # Daftar kategori dengan pencarian & agregat produk
│   │   ├── form.php         # Tambah & edit kategori (Admin)
│   │   └── delete.php       # Hapus kategori dengan proteksi FK RESTRICT (Admin)
│   │
│   ├── products/
│   │   ├── index.php        # Listing produk: Search, Filter, Sorting Allowlist, Pagination
│   │   ├── form.php         # Tambah & edit produk + relasi many-to-many supplier (Admin)
│   │   ├── detail.php       # Rincian produk, relasi supplier, dan riwayat mutasi
│   │   └── delete.php       # Hapus produk dengan proteksi FK RESTRICT (Admin)
│   │
│   ├── suppliers/
│   │   ├── index.php        # Daftar supplier resmi distributor
│   │   ├── form.php         # Tambah & edit supplier (Admin)
│   │   ├── detail.php       # Detail supplier dan daftar produk yang dipasok
│   │   └── delete.php       # Hapus supplier dengan proteksi FK RESTRICT (Admin)
│   │
│   ├── users/
│   │   ├── index.php        # Daftar pengguna sistem & profil (Admin only)
│   │   ├── form.php         # Tambah & edit pengguna (Admin only)
│   │   └── delete.php       # Hapus akun pengguna (Admin only)
│   │
│   ├── stock/
│   │   ├── index.php        # Pengalihan ke riwayat mutasi stok
│   │   ├── incoming.php     # Transaksi barang masuk (Stock In) dengan Transaction
│   │   ├── outgoing.php     # Transaksi barang keluar (Stock Out) dengan SELECT FOR UPDATE
│   │   ├── history.php      # Riwayat audit mutasi pergerakan stok lengkap
│   │   ├── form.php         # Pengarah transaksi stok
│   │   └── report.php       # Pengarah ke modul laporan
│   │
│   ├── reports/
│   │   ├── index.php        # Laporan ringkasan inventory (JOIN, GROUP BY, CASE WHEN)
│   │   └── categories.php   # Laporan analisis kategori (GROUP BY, HAVING, COUNT, SUM, AVG)
│   │
│   └── assets/
│       └── style.css        # Desain CSS tema soft pink modern, responsif & print style
│
├── views/
│   ├── header.php           # Header layout & navigasi atas
│   ├── sidebar.php          # Sidebar menu navigasi dinamis berbasis role
│   ├── footer.php           # Footer layout & script auto-dismiss alert
│   └── layout.php           # View layout helper
│
├── assets/
│   └── css/
│       └── style.css        # Berbagi berkas CSS styling
│
├── database/
│   └── schema.sql           # Skema database DDL, relasi relasional, dan seed data kosmetik
│
├── screenshots/             # Berkas tangkapan layar pengujian aplikasi
├── ERD.md                   # Diagram Mermaid ERD dan penjelasan relasi
└── README.md                # Panduan dokumentasi lengkap aplikasi
```

---

## 7. Bukti Pengujian & Tangkapan Layar (Testing Deliverables)

Pengujian fungsional dan keamanan aplikasi telah dilakukan secara menyeluruh:
1. **Login & Autentikasi:** Login berhasil sebagai Admin (`admin`/`password`) dan Staff (`staff`/`password`), password tersimpan dalam bentuk hash Bcrypt, ID sesi diperbarui via `session_regenerate_id(true)`.
2. **Otorisasi & Akses Terlarang:** Staff tidak dapat mengakses menu manajemen pengguna. Saat membuka `/public/users/`, server merespons dengan kode status **403 Forbidden** dan menampilkan peringatan otorisasi.
3. **Integritas Relasi Basis Data:**
   * Penghapusan kategori yang memiliki produk ditolak oleh basis data via `ON DELETE RESTRICT`.
   * Penghapusan produk yang memiliki transaksi stok ditolak via `ON DELETE RESTRICT`.
   * Relasi many-to-many pada `product_suppliers` menyimpan harga beli masing-masing distributor.
4. **Transaksi Barang Masuk & Keluar:**
   * Barang masuk berhasil menambah stok produk dan tercatat di histori mutasi dalam satu transaksi database.
   * Barang keluar berhasil mengurangi stok produk.
   * Kasus uji validasi error: Pengeluaran barang melebihi stok yang tersedia secara otomatis **ditolak** oleh server dan transaksi di-rollback tanpa mengubah stok produk.
5. **Listing Lanjutan:**
   * Pencarian nama dan SKU berfungsi dengan akurat.
   * Filter kategori menyaring produk secara tepat.
   * Sorting menggunakan allowlist pada kolom Nama, Harga, Stok, dan SKU (ASC/DESC).
   * Pagination membagi data 10 item per halaman dengan mempertahankan parameter query.
6. **Laporan Inventory:** Mengagregasi data aktual dari tabel `products`, `categories`, dan `stock_movements` menggunakan fungsi SQL `SUM(CASE WHEN ...)`, `COUNT()`, dan `GROUP BY`.
7. **Laporan Kategori:** Mengimplementasikan perbandingan klausa `WHERE` dan `HAVING` dengan fungsi agregat `COUNT()`, `SUM()`, dan `AVG()`.

---

© 2026 **MELORA KOSMETIK** &mdash; Inventory Management System. Dibuat untuk memenuhi tugas Mini Project 3 Pertemuan 4: Relational Database + Advanced PHP.
