<?php
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuthApi();
header('Content-Type: application/json');

$user_id = currentUserId();
$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $stmt = $pdo->prepare('SELECT * FROM barang WHERE user_id = ? AND nama_barang LIKE ? ORDER BY created_at DESC');
    $stmt->execute([$user_id, '%' . $search . '%']);
} else {
    $stmt = $pdo->prepare('SELECT * FROM barang WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$user_id]);
}

echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
