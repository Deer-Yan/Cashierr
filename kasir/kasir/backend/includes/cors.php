<?php
// CORS setup — frontend and backend now run on different origins/ports.
// Ganti FRONTEND_ORIGIN jika frontend Anda dijalankan di alamat/port lain.
define('FRONTEND_ORIGIN', getenv('FRONTEND_ORIGIN') ?: 'http://localhost:3000');

header('Access-Control-Allow-Origin: ' . FRONTEND_ORIGIN);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight request — jawab langsung tanpa perlu proses lebih lanjut
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
