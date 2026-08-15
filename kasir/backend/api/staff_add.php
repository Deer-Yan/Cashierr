<?php
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdminApi();
header('Content-Type: application/json');

$admin_id = $_SESSION['account_id'] ?? $_SESSION['user_id'];

$data = jsonInput();
$nama_pengguna = trim($data['nama_pengguna'] ?? '');
$email = trim(strtolower($data['email'] ?? ''));
$password = $data['password'] ?? '';

if (!$nama_pengguna || !$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Format email tidak valid.']);
    exit;
}
if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter.']);
    exit;
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email sudah terdaftar.']);
    exit;
}

$stmt = $pdo->prepare('SELECT nama_toko FROM users WHERE id = ?');
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

$hashed = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare('INSERT INTO users (nama_pengguna, nama_toko, email, password, role, owner_id) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->execute([$nama_pengguna, $admin['nama_toko'], $email, $hashed, 'staff', $admin_id]);

echo json_encode(['success' => true, 'message' => 'Akun pengguna berhasil ditambahkan.']);
