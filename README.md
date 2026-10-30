# CocoLink — Manajemen Ekosistem Limbah Kelapa

**Versi:** 1.0 — *MVP fungsional / untuk pengembangan dan demonstrasi*.  
**Stack:** PHP Native 8.1+, MySQL 8.0+ / MariaDB modern, PDO MySQL, Bootstrap 5, JavaScript, Leaflet + OpenStreetMap. Tidak membutuhkan framework PHP atau Composer. 

> Sistem ini adalah contoh perangkat lunak, **bukan aplikasi produksi yang telah diaudit keamanan, perpajakan, perlindungan data, atau kesiapan operasi bisnis**. Seluruh perusahaan/akun demo bersifat fiktif.

## 1. Instalasi XAMPP + phpMyAdmin

1. Ekstrak folder `CocoLink` ke `C:\xampp\htdocs\CocoLink` (nama folder disarankan `CocoLink`).
2. Jalankan **Apache** dan **MySQL** dari XAMPP.
3. Buka `http://localhost/phpmyadmin`. Masuk tab **Import**, pilih `CocoLink/database/cocolink.sql`, klik **Import**. File ini membuat database `cocolink` dan seluruh tabelnya. **Perhatian: SQL memakai DROP TABLE untuk database cocolink; jangan impor ulang pada database yang berisi data penting.**
4. Buka `config/config.php`. Default XAMPP: host `127.0.0.1`, user `root`, password kosong. Ubah jika MySQL berbeda.
5. Aktifkan ekstensi `pdo_mysql` dan `fileinfo` pada `php.ini` XAMPP. `mbstring` dan `iconv` disarankan. PDF/XLSX dibangun memakai PHP native; ekstensi `zip` tidak diperlukan. Restart Apache setelah mengubah `php.ini`.
6. Buat akun awal + data simulasi dengan terminal:
   ```bat
   cd C:\xampp\htdocs\CocoLink
   C:\xampp\php\php.exe tools\seed_demo.php
   ```
   Jalankan **sekali** pada database kosong. Script akan menolak memasukkan data demo apabila sudah ada pengguna.
7. Buka aplikasi melalui `http://localhost/CocoLink/`.

> Jika nama folder bukan `CocoLink`, URL harus menyesuaikan. Semua link internal mendeteksi path secara otomatis.

### Akun simulasi

| Role | Email | Password |
|---|---|---|
| Super Admin Platform | `admin@cocolink.test` | `CocoLink!2026` |
| Admin Koperasi Induk | `induk@cocolink.test` | `CocoLink!2026` |
| PT pemasok | `pt@cocolink.test` | `CocoLink!2026` |
| Koperasi anak | `anak@cocolink.test` | `CocoLink!2026` |
| Pembeli industri | `buyer@cocolink.test` | `CocoLink!2026` |
| Driver logistik | `driver@cocolink.test` | `CocoLink!2026` |

**Hapus akun dan data demo sebelum digunakan untuk operasi nyata.** Jangan memakai password ini di server publik.

## 2. Struktur folder

```text
CocoLink/
├── index.php                  # Router halaman dan entry point
├── report.php                 # Ekspor PDF, XLSX, invoice
├── download.php               # Bukti kirim dengan pemeriksaan akses
├── .htaccess                  # Aturan akses direktori
├── config/
│   └── config.php             # Pengaturan MySQL dan timezone
├── app/
│   ├── bootstrap.php          # PDO, sesi, keamanan, utility
│   ├── actions.php            # Proses bisnis POST + transaksi
│   ├── lib/
│   │   └── exports.php        # Pembuat PDF + Excel tanpa Composer
│   └── views/
│       ├── public.php         # Landing, login, pendaftaran
│       ├── layout.php         # Layout dashboard + CSS embedded
│       ├── pages.php          # Dispatcher halaman berdasarkan role
│       └── pages/             # Tiap halaman dipisah ke file sendiri
│           ├── dashboard.php
│           ├── verification.php
│           ├── accounts.php
│           ├── batches.php
│           ├── allocations.php
│           ├── products.php
│           ├── catalog.php
│           ├── orders.php
│           ├── shipments.php
│           ├── maps.php
│           ├── reports.php
│           └── audit.php
├── database/
│   └── cocolink.sql           # Skema dan master kategori kelapa
├── storage/
│   └── uploads/
│       └── .htaccess          # File bukti tidak terbuka langsung
└── tools/
    └── seed_demo.php         # Data contoh (hanya PHP CLI)
```

**UI:** CSS utama berada pada `<style>` di `app/views/layout.php` dan `app/views/public.php`, dengan beberapa `style="..."` spesifik komponen. Tidak perlu membuat/memanggil file CSS lokal tambahan. Bootstrap/font dan peta memakai CDN; **tampilan paling lengkap memerlukan koneksi internet**.

## 3. Pembagian peran

| Role | Akses |
|---|---|
| Super Admin Platform | Semua pengawasan dan operasional; tambah/nonaktif akun; audit |
| Koperasi Induk (`induk`) | Verifikasi PT, koperasi anak, pembeli; alokasi; verifikasi order; driver; laporan |
| PT pemasok (`supplier`) | Daftar PT + min. 5 koperasi; tambah batch limbah; pantau alokasi |
| Koperasi anak (`coop`) | Terima/tolak alokasi; catat konversi; kelola produk/stok |
| Pembeli (`buyer`) | Daftar sebagai industri atau pengepul; lihat katalog; pesan; invoice; konfirmasi barang |
| Driver (`driver`) | Terima penugasan; ajukan pickup untuk disetujui koperasi; pembaruan lokasi; bukti pengiriman |

Super Admin dikelola tim penyedia aplikasi dan bukan badan usaha pemasok. Koperasi Induk menjalankan validasi dan operasional marketplace.

## 4. Alur lengkap yang dapat diuji

### A. Onboarding PT dan syarat 5 anak

1. Kunjungi halaman **Daftarkan PT pemasok**.
2. Masukkan profil PT, NIB, koordinat, dan **setidaknya 5 koperasi anak berbeda** (nama, wilayah, fokus, alamat pickup).
3. Login sebagai Koperasi Induk, buka **Verifikasi**.
4. Verifikasi kelima koperasi setelah pemeriksaan identitas manual.
5. Klik **Setujui** pada PT; server akan menolak jika belum ada 5 koperasi anak yang valid.
6. PT valid bisa memasukkan batch limbah. PT yang menunggu verifikasi terlihat pada peta **khusus admin** (pin kuning); PT valid terlihat bagi pengguna lain (pin hijau).
7. Super Admin bisa menghubungkan akun pengguna koperasi anak ke profil koperasi yang sudah ada.

### B. Pasokan, alokasi, produksi

1. PT masuk → **Pasokan PT** → input batch, jenis limbah, berat kg.
2. Koperasi Induk masuk → **Alokasi** → pilih batch dan koperasi anak yang cocok. Alokasi tidak boleh melewati sisa batch.
3. Koperasi Anak → **Alokasi masuk** → terima atau tolak.
4. Koperasi Anak → **Produksi & stok** → daftarkan SKU, nama produk, harga.
5. Koperasi Anak → catat input limbah dan output produk; stok produk bertambah. Input tidak dapat melebihi jumlah alokasi.

### C. Pesanan industri dan logistik

1. Pembeli daftar → admin induk memverifikasi.
2. Pembeli → **Katalog** → pilih produk, berat, alamat tujuan, klik **Ajukan pesanan**.
3. Induk → **Pesanan** → setujui/tolak. Pada persetujuan, sistem mengunci baris stok produk dan mengurangi stok dengan transaksi database.
4. Induk → **Logistik** → assign driver (hanya untuk order yang disetujui).
5. Driver → **Tugas kirim** → **Terima** → **Ajukan pickup** dan isi keterangan. Koperasi Anak terkait / Admin Induk → **Validasi pickup** → **ACC/Tolak pickup**. Jika disetujui, driver dapat (opsional) update GPS lalu → **Barang sampai** (bukti wajib).
6. Pembeli → **Pesanan saya** → **Konfirmasi terima** setelah driver menyatakan barang terkirim.
7. Invoice tersedia dari status order disetujui dan dapat dibuka sebagai PDF.

### D. Laporan

Koperasi Induk dan Super Admin dapat mengekspor PDF atau Excel `.xlsx` untuk pesanan, batch limbah, inventaris produk, dan pengiriman. **Laporan membaca isi database pada saat ekspor**, bukan sinkronisasi streaming. PDF dan `.xlsx` dibuat langsung oleh utilitas PHP native, tanpa Composer ataupun ekstensi zip.

## 5. Skema database (14 tabel)

- `raw_types` — kategori daun, tempurung, sabut, batang, air, daging/ampas.
- `suppliers` — profil PT + NIB, alamat, koordinat, status verifikasi.
- `cooperatives` — profil koperasi anak, spesialisasi, dan hubungan PT.
- `buyer_profiles` — industri/pengepul dan status validasi.
- `users` — akun pengguna, hash password, role, status, relasi profil.
- `raw_batches` — batch bahan mentah PT.
- `allocations` — alokasi jumlah batch ke koperasi anak.
- `products` — katalog produk jadi dan stok tersisa.
- `production_logs` — jejak input limbah ke output produk.
- `orders` — transaksi permintaan industri dan statusnya.
- `order_items` — detail produk dan snapshot harga.
- `shipments` — satu tugas pengiriman untuk satu order.
- `shipment_events` — peristiwa logistik, GPS, foto/PDF bukti.
- `audit_logs` — aktivitas keamanan dan bisnis.

Relasi besar: `suppliers 1—N cooperatives`, `suppliers 1—N raw_batches`, `raw_batches 1—N allocations`, `allocations 1—N production_logs`, `cooperatives 1—N products`, `orders 1—N order_items`, `orders 1—1 shipments`, `shipments 1—N shipment_events`.

## 6. Data real-time dan GPS

- Dashboard **refresh otomatis setiap 30 detik**, bukan real-time WebSocket. Kejadian di database baru tampak saat refresh berikutnya.
- GPS driver hanya diperbarui **ketika driver mengirim update manual** dengan izin lokasi browser. Sistem belum mendukung live tracking terus-menerus atau routing otomatis.
- Leaflet dan tile OpenStreetMap **memerlukan internet**. Untuk lokasi PT, saat pendaftaran koordinat diisi manual.
- Status order dan shipment menjadi sumber audit. Bukti disimpan di `storage/uploads` dan hanya dibaca melalui `download.php` dengan pemeriksaan hak akses.

## 7. Batasan yang harus dikembangkan sebelum produksi

1. Belum ada unggah berkas legal dan proses due diligence terstruktur untuk NIB/koperasi; verifikasi sekarang lewat pemeriksaan manual pengelola.
2. Belum ada penghitungan ongkos kirim, perhitungan pajak/PPN, pembayaran, biaya platform, settlement antar entitas, refund, atau e-faktur. **PDF invoice ini bukan faktur pajak**.
3. Setiap form pemesanan saat ini memilih **satu SKU per pesanan** walaupun tabel `order_items` memungkinkan multi-item.
4. Koperasi anak mengikuti model **satu PT pemasok utama**. Jika koperasi yang sama harus menerima limbah banyak PT, ubah relasi menjadi tabel penghubung many-to-many.
5. Stok produk menggunakan kilogram. Untuk sapu satuan ikat, arang karung, minyak liter, perlu konversi satuan dan kebijakan penentuan harga.
6. Belum ada upload foto produk, pencatatan mutu laboratorium, retur, approval bukti pickup oleh induk, tanda tangan digital, edit profil/koordinat koperasi, atau dashboard keuangan perusahaan.
7. Belum ada pagination/filter lanjutan, notifikasi WhatsApp/email, alokasi kurir otomatis, optimasi rute, histori penjadwalan, atau aplikasi mobile terpisah.
8. Belum dilakukan pengujian integrasi pada XAMPP/MySQL di lingkungan Anda. Lakukan UAT, hardening server, backup otomatis, HTTPS, pembatasan upload, dan audit keamanan sebelum dipakai dengan pengguna sebenarnya.

## 8. Uji cepat

- Login induk → Verifikasi: lihat PT demo valid dan lima koperasi.
- Login PT → input batch pasokan baru.
- Login induk → alokasikan batch ke koperasi dengan fokus jenis yang sama.
- Login anak → terima alokasi → buat produk → catat produksi.
- Login pembeli → lihat katalog → order.
- Login induk → setujui order → assign driver.
- Login driver → terima tugas → ajukan pickup; login koperasi anak atau induk → ACC pickup; login driver → unggah bukti saat delivered.
- Login pembeli → konfirmasi penerimaan dan unduh invoice.
- Login induk → Laporan → unduh PDF/.xlsx.

**Catatan:** Password hash disimpan dengan `password_hash`; semua form POST dilindungi CSRF; query menggunakan PDO prepared statements; akses fitur di backend dicek menurut role. Sebagian perubahan stok memakai transaksi dan lock baris. Ini dasar yang baik untuk MVP, tetapi bukan pengganti pentest dan kontrol keamanan produksi.
