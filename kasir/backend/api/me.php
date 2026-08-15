<?php
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuthApi();
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'data' => [
        'nama_pengguna' => $_SESSION['nama_pengguna'],
        'nama_toko' => $_SESSION['nama_toko'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role'] ?? 'admin',
    ],
]);
