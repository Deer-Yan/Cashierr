<?php
if (session_status() === PHP_SESSION_NONE) {
    // Frontend & backend berjalan di origin berbeda (port berbeda), tapi tetap
    // same-site (sama-sama "localhost"), jadi SameSite=Lax cukup untuk fetch
    // ber-credentials tanpa perlu HTTPS di lingkungan development.
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'samesite' => 'Lax',
        'secure' => false,
        'httponly' => true,
    ]);
    session_start();
}

function requireAuthApi() {
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Sesi tidak valid, silakan login kembali.']);
        exit;
    }
}

function requireAuthPage() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

function requireAdminApi() {
    requireAuthApi();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Hanya admin toko yang bisa melakukan aksi ini.']);
        exit;
    }
}

// user_id di session selalu merujuk ke id "pemilik data" (toko), baik yang login
// adalah akun admin maupun akun staff di bawahnya — jadi query barang/penjualan
// tetap otomatis konsisten tanpa perlu cek role di setiap endpoint.
function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function jsonInput() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
