# Kasir Toko — Aplikasi Kasir Sederhana

Stack: **PHP native (tanpa framework)** untuk backend + **MySQL** + **HTML/CSS/JavaScript vanilla** untuk frontend, dipisah dalam folder berbeda dan berkomunikasi lewat REST API (JSON) + session cookie.
Ditambahkan juga **Node.js CLI tool** sebagai fitur tambahan untuk export laporan penjualan (lihat bagian bawah).

## Struktur Folder

```
kasir-toko/
├── backend/                # Server PHP — hanya API, tidak ada tampilan
│   ├── api/                 # Endpoint JSON: register, login, logout, me, barang, penjualan, riwayat, dashboard
│   ├── config/db.php        # Konfigurasi koneksi MySQL (PDO)
│   ├── includes/auth.php    # Session guard & helper
│   ├── includes/cors.php    # Header CORS agar frontend (origin lain) bisa akses API
│   ├── uploads/              # Tempat foto barang tersimpan
│   └── database.sql          # Skema database
├── frontend/                # Halaman statis — HTML + CSS + JS murni, tidak ada PHP
│   ├── index.html            # Login & Register
│   ├── dashboard.html
│   ├── barang.html
│   ├── penjualan.html
│   ├── riwayat.html
│   └── assets/
│       ├── css/style.css
│       └── js/
│           ├── config.js     # Alamat API_BASE ke backend
│           └── app.js        # Helper fetch, sidebar dinamis, auth guard
├── node-tools/               # CLI Node.js — fitur tambahan export laporan
└── README.md
```

Frontend **tidak** memanggil PHP sama sekali — semua halaman murni HTML statis yang memanggil backend lewat `fetch()` (AJAX) ke alamat yang diatur di `frontend/assets/js/config.js`.

## Fitur

- Register & Login (password di-hash dengan bcrypt via `password_hash`)
- Logout (hapus session)
- CRUD Barang (nama, foto, harga modal, harga jual, **stok**) — data terisolasi per toko
- Penjualan: pilih banyak barang + qty, hitung total otomatis, input uang & kembalian otomatis, **struk siap cetak**
- Riwayat Penjualan: tanggal/jam, total, detail barang & qty, filter per tanggal, **export CSV**
- **Dashboard tambahan**: omzet & jumlah transaksi hari ini, notifikasi stok menipis, barang terlaris
- Validasi stok otomatis berkurang saat transaksi & mencegah stok minus
- Desain responsif (desktop & mobile, sidebar collapsible)
- Node.js CLI untuk export laporan harian ke CSV di luar browser
- **Akun bertingkat (Kelola Pengguna)**: akun yang mendaftar (register) otomatis menjadi **admin** dengan akses penuh. Admin bisa menambah akun **staff** tambahan di bawah toko yang sama lewat menu *Kelola Pengguna* — akun staff login dengan email/password sendiri, tapi hanya bisa membuka halaman **Penjualan** dan melihat barang milik toko yang sama (data barang/transaksi selalu terikat ke toko, bukan ke akun individu).

## Langkah Instalasi

### 1. Siapkan database
Jalankan isi `backend/database.sql` di MySQL:
```
mysql -u root -p < backend/database.sql
```
atau import lewat phpMyAdmin.

### 2. Atur koneksi database (backend)
Buka `backend/config/db.php`, sesuaikan `$DB_HOST`, `$DB_NAME`, `$DB_USER`, `$DB_PASS` (default cocok untuk XAMPP: user `root`, password kosong).

### 3. Jalankan server backend (port 8000)
```
cd backend
php -S localhost:8000
```
Pastikan folder `backend/uploads/` bisa ditulis (`chmod -R 755 uploads` di Linux/Mac) — foto barang tersimpan di sini.

### 4. Jalankan server frontend (port 3000)
Di terminal terpisah:
```
cd frontend
php -S localhost:3000
```
(Server PHP di sini hanya dipakai sebagai static file server, tidak ada kode PHP yang dijalankan. Bisa juga pakai `npx serve` atau ekstensi Live Server jika mau.)

### 5. Sesuaikan alamat backend di frontend (jika perlu)
Jika backend/frontend dijalankan di host atau port lain dari default, ubah `frontend/assets/js/config.js`:
```js
const API_BASE = 'http://localhost:8000';
```
Dan set env `FRONTEND_ORIGIN` saat menjalankan backend jika origin frontend Anda bukan `http://localhost:3000`:
```
FRONTEND_ORIGIN=http://localhost:3000 php -S localhost:8000
```

### 6. Buka aplikasi
Kunjungi `http://localhost:3000` di browser → daftar akun baru → login → mulai kelola Barang & Penjualan.

### 7. (Opsional) Tambah akun staff
Login sebagai admin → buka menu **Kelola Pengguna** → **Tambah Pengguna** → isi nama, email, password.
Akun staff tersebut bisa langsung login di `index.html` dan akan otomatis diarahkan ke halaman Penjualan saja — mereka tidak bisa membuka Dashboard, Barang, Riwayat, atau Kelola Pengguna (baik lewat menu maupun dengan mengetik URL langsung, karena dicek juga oleh backend).

## (Fitur Tambahan) Export Laporan via Node.js CLI

Tool terpisah untuk generate laporan penjualan harian ke CSV tanpa buka browser — cocok untuk dijadwalkan otomatis (cron).

```
cd node-tools
npm install
node export-report.js --email=emailakun@toko.com --tanggal=2026-08-12
```

- `--tanggal` opsional, default = hari ini.
- Hasil CSV tersimpan di `node-tools/reports/`.
- Konfigurasi koneksi database ada di bagian atas `export-report.js` (`DB_CONFIG`), sesuaikan jika perlu.

## Catatan Keamanan & Teknis

- Backend melakukan **migrasi otomatis** saat pertama kali diakses: jika database dibuat sebelum fitur akun staff ada (kolom `role`/`owner_id` belum ada di tabel `users`), backend akan menambahkannya sendiri. Jadi database lama tetap jalan tanpa perlu import ulang atau `ALTER TABLE` manual.

- Password disimpan menggunakan **bcrypt** (`password_hash`/`password_verify`), bukan MD5 murni, karena MD5 sudah tidak aman untuk password.
- Karena backend dan frontend kini berbeda origin, backend mengirim header CORS (`backend/includes/cors.php`) dan session cookie diatur `SameSite=Lax` (aman untuk pengembangan lokal karena keduanya tetap dianggap "same-site": sama-sama `localhost`, hanya beda port). Semua panggilan `fetch()` di frontend menyertakan `credentials: 'include'` agar cookie sesi ikut terkirim.
- Setiap request ke API barang/penjualan/riwayat divalidasi lewat session (`requireAuthApi()`), dan semua query dibatasi `WHERE user_id = ?` agar data antar akun tidak tembus.
- Transaksi penjualan dibungkus `PDO transaction` + `SELECT ... FOR UPDATE` agar stok tidak minus saat diakses bersamaan.
- Jika nanti di-deploy ke domain publik (bukan localhost), ubah `secure => true` di `backend/includes/auth.php` dan jalankan keduanya via HTTPS.
