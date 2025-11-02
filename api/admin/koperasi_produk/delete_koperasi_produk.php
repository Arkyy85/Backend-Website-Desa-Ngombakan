<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../../config/config_database.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

// Secret key (samakan dengan yang digunakan saat membuat token)
$secret_key = "#112q282232%@!Q#1@!122221!@1";

// --- JWT AUTH CHECK (format & urutan sesuai standar) ---
$allHeaders = function_exists('getallheaders') ? getallheaders() : [];
$authHeader = null;
if (is_array($allHeaders)) {
    foreach ($allHeaders as $k => $v) {
        if (strtolower($k) === 'authorization') { $authHeader = $v; break; }
    }
}
// fallback ke $_SERVER jika header tidak ada di getallheaders()
if ($authHeader === null) {
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}

if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $m)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authorization header tidak ditemukan atau format salah.']);
    exit;
}

$jwt = $m[1];

try {
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
    // Pastikan token mengandung data user (sesuai payload login)
    $user = $decoded->user ?? $decoded->data ?? null;
    if (!$user) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token tidak memiliki data user.']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token tidak valid atau sudah kedaluwarsa: ' . $e->getMessage()]);
    exit;
}
// --- END JWT AUTH CHECK ---

// Pastikan koneksi PDO tersedia
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection not found.']);
    exit;
}

try {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) $input = [];

    // ID bisa dari body JSON atau query param ?id=...
    $id = isset($input['id']) ? (int)$input['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Parameter 'id' wajib untuk menghapus."]);
        exit;
    }

    // ambil data (jika ada gambar, bisa diproses untuk dihapus file fisik)
    $chk = $pdo->prepare("SELECT id, gambar FROM koperasi_produk WHERE id = :id LIMIT 1");
    $chk->execute([':id' => $id]);
    $row = $chk->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Produk dengan id={$id} tidak ditemukan."]);
        exit;
    }

    // opsi: hapus file fisik jika ada (sesuaikan path storage Anda)
    // if (!empty($row['gambar'])) {
    //     $filePath = __DIR__ . '/../../../' . ltrim($row['gambar'], '/');
    //     if (file_exists($filePath)) {
    //         @unlink($filePath);
    //     }
    // }

    // delete dalam transaksi untuk safety
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("DELETE FROM koperasi_produk WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $pdo->commit();

    echo json_encode(["success" => true, "message" => "Produk berhasil dihapus.", "id" => $id], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Kesalahan database: " . $e->getMessage()]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Kesalahan server: " . $e->getMessage()]);
}
