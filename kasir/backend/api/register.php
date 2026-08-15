<?php
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

$data = jsonInput();
$nama_pengguna = trim($data['nama_pengguna'] ?? '');
$nama_toko = trim($data['nama_toko'] ?? '');
$email = trim(strtolower($data['email'] ?? ''));
$password = $data['password'] ?? '';

if (!$nama_pengguna || !$nama_toko || !$email || !$password) {
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

// Password dienkripsi menggunakan bcrypt (password_hash) - lebih aman dari md5
$hashed = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare('INSERT INTO users (nama_pengguna, nama_toko, email, password) VALUES (?, ?, ?, ?)');
$stmt->execute([$nama_pengguna, $nama_toko, $email, $hashed]);

echo json_encode(['success' => true, 'message' => 'Registrasi berhasil, silakan login.']);
