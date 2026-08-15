<?php
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuthApi();
header('Content-Type: application/json');

$user_id = currentUserId();
$tanggal = trim($_GET['tanggal'] ?? '');

if ($tanggal !== '') {
    $stmt = $pdo->prepare('SELECT * FROM penjualan WHERE user_id = ? AND DATE(created_at) = ? ORDER BY created_at DESC');
    $stmt->execute([$user_id, $tanggal]);
} else {
    $stmt = $pdo->prepare('SELECT * FROM penjualan WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$user_id]);
}
$penjualanList = $stmt->fetchAll();

$result = [];
$stmtDetail = $pdo->prepare('SELECT nama_barang, harga_jual, qty, subtotal FROM penjualan_detail WHERE penjualan_id = ?');
foreach ($penjualanList as $p) {
    $stmtDetail->execute([$p['id']]);
    $p['items'] = $stmtDetail->fetchAll();
    $result[] = $p;
}

echo json_encode(['success' => true, 'data' => $result]);
