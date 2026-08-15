<?php
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdminApi();
header('Content-Type: application/json');

$admin_id = $_SESSION['account_id'] ?? $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT id, nama_pengguna, email, created_at FROM users WHERE owner_id = ? AND role = "staff" ORDER BY created_at DESC');
$stmt->execute([$admin_id]);

echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
