<?php
// Konfigurasi koneksi database
$DB_HOST = 'localhost';
$DB_NAME = 'kasir_toko';
$DB_USER = 'root';
$DB_PASS = '';

// Tangkap semua error/exception tak terduga (termasuk error query SQL, misalnya
// kolom belum ada karena database lama belum di-migrate) dan ubah jadi JSON yang
// jelas, bukan halaman kosong/putih yang membingungkan di sisi frontend.
set_exception_handler(function ($e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server: ' . $e->getMessage()]);
    exit;
});

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5, // gagal cepat kalau server DB tidak merespons, bukan menggantung
        ]
    );
    // Batasi waktu tunggu lock InnoDB supaya query yang "nyangkut" karena lock
    // (misal transaksi lain belum commit) gagal cepat dengan pesan jelas,
    // bukan membuat request menggantung tanpa batas waktu.
    $pdo->exec('SET SESSION innodb_lock_wait_timeout = 8');
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal: ' . $e->getMessage()]);
    exit;
}

// Migrasi otomatis ringan: kalau database dibuat sebelum fitur akun staff
// ditambahkan, kolom role/owner_id belum ada di tabel users. Daripada gagal
// diam-diam (role jadi NULL, akun admin lama ikut terkunci), cek dan tambahkan
// otomatis di sini supaya database lama tetap jalan tanpa migrasi manual.
// Ditandai dengan file penanda supaya pengecekan ini hanya jalan sekali saja
// (bukan di setiap request), mengurangi beban dan potensi race condition.
$migrationFlag = __DIR__ . '/.migrated';
if (!file_exists($migrationFlag)) {
    try {
        $hasRole = (int) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'role'"
        )->fetchColumn();
        if ($hasRole === 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('admin','staff') NOT NULL DEFAULT 'admin' AFTER password");
        }

        $hasOwnerId = (int) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'owner_id'"
        )->fetchColumn();
        if ($hasOwnerId === 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN owner_id INT DEFAULT NULL AFTER role");
            try {
                $pdo->exec("ALTER TABLE users ADD CONSTRAINT fk_users_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE");
            } catch (Exception $e) {
                // FK mungkin sudah ada atau nama constraint bentrok — bukan masalah fatal, lanjutkan.
            }
        }
        @file_put_contents($migrationFlag, date('c'));
    } catch (Exception $e) {
        // Kalau migrasi otomatis gagal (misal user DB tidak punya izin ALTER TABLE),
        // biarkan saja — endpoint terkait akan tetap memberi pesan error yang jelas
        // lewat exception handler di atas, bukan halaman kosong. Tidak menulis flag
        // supaya dicoba lagi di request berikutnya.
    }
}
