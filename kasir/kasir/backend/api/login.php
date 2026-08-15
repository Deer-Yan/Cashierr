<?php
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

$data = jsonInput();
$email = trim(strtolower($data['email'] ?? ''));
$password = $data['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Email dan password wajib diisi.']);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Email atau password salah.']);
    exit;
}

// Jika yang login adalah akun staff, semua data (barang/penjualan/riwayat)
// tetap merujuk ke toko milik admin (owner_id), bukan id akun staff itu sendiri.
$dataOwnerId = $user['id'];
$namaToko = $user['nama_toko'];

if ($user['role'] === 'staff' && $user['owner_id']) {
    $stmt = $pdo->prepare('SELECT nama_toko FROM users WHERE id = ?');
    $stmt->execute([$user['owner_id']]);
    $owner = $stmt->fetch();
    if ($owner) {
        $dataOwnerId = $user['owner_id'];
        $namaToko = $owner['nama_toko'];
    }
}

session_regenerate_id(true);
$_SESSION['user_id'] = $dataOwnerId;
$_SESSION['account_id'] = $user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['nama_pengguna'] = $user['nama_pengguna'];
$_SESSION['nama_toko'] = $namaToko;
$_SESSION['email'] = $user['email'];

echo json_encode(['success' => true, 'message' => 'Login berhasil.', 'data' => ['role' => $user['role']]]);
