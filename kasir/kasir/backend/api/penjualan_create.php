<?php
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuthApi();
header('Content-Type: application/json');

$user_id = currentUserId();
$data = jsonInput();
$items = $data['items'] ?? [];
$uang_dibayar = (float) ($data['uang_dibayar'] ?? 0);

if (!is_array($items) || count($items) === 0) {
    echo json_encode(['success' => false, 'message' => 'Pilih minimal satu barang.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $total_harga = 0;
    $validatedItems = [];

    foreach ($items as $item) {
        $barang_id = (int) ($item['barang_id'] ?? 0);
        $qty = (int) ($item['qty'] ?? 0);
        if ($qty <= 0) continue;

        $stmt = $pdo->prepare('SELECT * FROM barang WHERE id = ? AND user_id = ? FOR UPDATE');
        $stmt->execute([$barang_id, $user_id]);
        $barang = $stmt->fetch();

        if (!$barang) {
            throw new Exception('Barang tidak ditemukan.');
        }
        if ($barang['stok'] < $qty) {
            throw new Exception('Stok "' . $barang['nama_barang'] . '" tidak mencukupi (sisa ' . $barang['stok'] . ').');
        }

        $subtotal = $barang['harga_jual'] * $qty;
        $total_harga += $subtotal;

        $validatedItems[] = [
            'barang_id' => $barang['id'],
            'nama_barang' => $barang['nama_barang'],
            'harga_jual' => $barang['harga_jual'],
            'qty' => $qty,
            'subtotal' => $subtotal,
        ];
    }

    if (count($validatedItems) === 0) {
        throw new Exception('Tidak ada barang valid untuk dijual.');
    }

    if ($uang_dibayar < $total_harga) {
        throw new Exception('Uang yang diberikan kurang dari total belanja.');
    }

    $kembalian = $uang_dibayar - $total_harga;

    $stmt = $pdo->prepare('INSERT INTO penjualan (user_id, total_harga, uang_dibayar, kembalian) VALUES (?, ?, ?, ?)');
    $stmt->execute([$user_id, $total_harga, $uang_dibayar, $kembalian]);
    $penjualan_id = $pdo->lastInsertId();

    $stmtDetail = $pdo->prepare('INSERT INTO penjualan_detail (penjualan_id, barang_id, nama_barang, harga_jual, qty, subtotal) VALUES (?, ?, ?, ?, ?, ?)');
    $stmtStok = $pdo->prepare('UPDATE barang SET stok = stok - ? WHERE id = ?');

    foreach ($validatedItems as $vi) {
        $stmtDetail->execute([$penjualan_id, $vi['barang_id'], $vi['nama_barang'], $vi['harga_jual'], $vi['qty'], $vi['subtotal']]);
        $stmtStok->execute([$vi['qty'], $vi['barang_id']]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Transaksi berhasil disimpan.',
        'data' => [
            'id' => $penjualan_id,
            'total_harga' => $total_harga,
            'uang_dibayar' => $uang_dibayar,
            'kembalian' => $kembalian,
            'items' => $validatedItems,
        ],
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
