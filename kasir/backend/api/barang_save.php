<?php
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuthApi();
header('Content-Type: application/json');

$user_id = currentUserId();

$id = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
$nama_barang = trim($_POST['nama_barang'] ?? '');
$harga_modal = (float) ($_POST['harga_modal'] ?? 0);
$harga_jual = (float) ($_POST['harga_jual'] ?? 0);
$stok = (int) ($_POST['stok'] ?? 0);

if (!$nama_barang || $harga_modal < 0 || $harga_jual < 0 || $stok < 0) {
    echo json_encode(['success' => false, 'message' => 'Data barang tidak valid.']);
    exit;
}

// Jika edit, pastikan barang milik user ini
$existingFoto = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM barang WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $user_id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        echo json_encode(['success' => false, 'message' => 'Barang tidak ditemukan.']);
        exit;
    }
    $existingFoto = $existing['foto'];
}

$fotoName = $existingFoto;

if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Format foto harus jpg, jpeg, png, atau webp.']);
        exit;
    }
    $fotoName = 'barang_' . $user_id . '_' . uniqid() . '.' . $ext;
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    move_uploaded_file($_FILES['foto']['tmp_name'], $uploadDir . $fotoName);

    // Hapus foto lama jika ada penggantian
    if ($existingFoto && file_exists($uploadDir . $existingFoto)) {
        unlink($uploadDir . $existingFoto);
    }
}

if ($id) {
    $stmt = $pdo->prepare('UPDATE barang SET nama_barang=?, foto=?, harga_modal=?, harga_jual=?, stok=? WHERE id=? AND user_id=?');
    $stmt->execute([$nama_barang, $fotoName, $harga_modal, $harga_jual, $stok, $id, $user_id]);
    echo json_encode(['success' => true, 'message' => 'Barang berhasil diperbarui.']);
} else {
    $stmt = $pdo->prepare('INSERT INTO barang (user_id, nama_barang, foto, harga_modal, harga_jual, stok) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$user_id, $nama_barang, $fotoName, $harga_modal, $harga_jual, $stok]);
    echo json_encode(['success' => true, 'message' => 'Barang berhasil ditambahkan.']);
}
