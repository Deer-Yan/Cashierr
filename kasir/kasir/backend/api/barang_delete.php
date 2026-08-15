<?php
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuthApi();
header('Content-Type: application/json');

$user_id = currentUserId();
$data = jsonInput();
$id = (int) ($data['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM barang WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $user_id]);
$barang = $stmt->fetch();

if (!$barang) {
    echo json_encode(['success' => false, 'message' => 'Barang tidak ditemukan.']);
    exit;
}

$stmt = $pdo->prepare('DELETE FROM barang WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $user_id]);

if ($barang['foto']) {
    $path = __DIR__ . '/../uploads/' . $barang['foto'];
    if (file_exists($path)) {
        unlink($path);
    }
}

echo json_encode(['success' => true, 'message' => 'Barang berhasil dihapus.']);
