<?php
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuthApi();
header('Content-Type: application/json');

$user_id = currentUserId();

// Total transaksi & omzet hari ini
$stmt = $pdo->prepare('SELECT COUNT(*) AS jumlah_transaksi, COALESCE(SUM(total_harga),0) AS omzet FROM penjualan WHERE user_id = ? AND DATE(created_at) = CURDATE()');
$stmt->execute([$user_id]);
$today = $stmt->fetch();

// Total barang
$stmt = $pdo->prepare('SELECT COUNT(*) AS total_barang FROM barang WHERE user_id = ?');
$stmt->execute([$user_id]);
$totalBarang = $stmt->fetch()['total_barang'];

// Barang stok menipis (<= 5)
$stmt = $pdo->prepare('SELECT id, nama_barang, stok FROM barang WHERE user_id = ? AND stok <= 5 ORDER BY stok ASC');
$stmt->execute([$user_id]);
$lowStock = $stmt->fetchAll();

// Barang terlaris (top 5 all time)
$stmt = $pdo->prepare('
    SELECT pd.nama_barang, SUM(pd.qty) AS total_terjual
    FROM penjualan_detail pd
    JOIN penjualan p ON p.id = pd.penjualan_id
    WHERE p.user_id = ?
    GROUP BY pd.nama_barang
    ORDER BY total_terjual DESC
    LIMIT 5
');
$stmt->execute([$user_id]);
$topBarang = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'data' => [
        'jumlah_transaksi_hari_ini' => (int) $today['jumlah_transaksi'],
        'omzet_hari_ini' => (float) $today['omzet'],
        'total_barang' => (int) $totalBarang,
        'low_stock' => $lowStock,
        'top_barang' => $topBarang,
    ],
]);
