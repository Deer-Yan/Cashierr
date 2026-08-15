<?php
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdminApi();
header('Content-Type: application/json');

$admin_id = $_SESSION['account_id'] ?? $_SESSION['user_id'];
$data = jsonInput();
$id = (int) ($data['id'] ?? 0);

$stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? AND owner_id = ? AND role = "staff"');
$stmt->execute([$id, $admin_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Akun pengguna tidak ditemukan.']);
    exit;
}

$stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
$stmt->execute([$id]);

echo json_encode(['success' => true, 'message' => 'Akun pengguna berhasil dihapus.']);
