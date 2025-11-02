<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Tangani preflight (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../../config/config_database.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

// Secret key sama seperti di login.php
$secret_key = "#112q282232%@!Q#1@!122221!@1";

// --- JWT AUTH CHECK (format & urutan sesuai standar) ---
$headers = function_exists('getallheaders') ? getallheaders() : [];
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$token = null;

if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}

if (!$token) {
    // fallback ke $_SERVER kalau getallheaders tidak tersedia atau nama header berbeda
    if (!empty($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $m2)) {
        $token = $m2[1];
    } elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && preg_match('/Bearer\s(\S+)/', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $m3)) {
        $token = $m3[1];
    }
}

if (!$token) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Authorization header tidak ditemukan atau format salah."]);
    exit;
}

try {
    $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
    // ambil informasi user jika tersedia
    $user = $decoded->user ?? null;
    if (!$user) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token tidak memiliki data user.']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Token tidak valid: " . $e->getMessage()]);
    exit;
}
// --- END JWT AUTH CHECK ---

try {
    // Ambil input JSON
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Format JSON tidak valid."]);
        exit;
    }

    // Ambil data dari input
    $judul         = trim($input['judul'] ?? '');
    $slug          = trim($input['slug'] ?? '');
    $kategori      = $input['kategori'] ?? null;
    $ringkasan     = $input['ringkasan'] ?? null;
    $konten        = $input['konten'] ?? '';
    $gambar_utama  = $input['gambar_utama'] ?? null;
    $penulis       = $input['penulis'] ?? null;
    $views         = isset($input['views']) ? (int)$input['views'] : 0;
    $is_published  = isset($input['is_published']) ? (int)$input['is_published'] : 1;
    $published_at  = $input['published_at'] ?? null;

    // Validasi wajib isi
    if (empty($judul) || empty($slug) || empty($konten)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Field 'judul', 'slug', dan 'konten' wajib diisi."
        ]);
        exit;
    }

    // Query insert
    $sql = "INSERT INTO artikel (
                judul, slug, kategori, ringkasan, konten, gambar_utama, penulis,
                views, is_published, published_at
            ) VALUES (
                :judul, :slug, :kategori, :ringkasan, :konten, :gambar_utama, :penulis,
                :views, :is_published, :published_at
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':judul'         => $judul,
        ':slug'          => $slug,
        ':kategori'      => $kategori,
        ':ringkasan'     => $ringkasan,
        ':konten'        => $konten,
        ':gambar_utama'  => $gambar_utama,
        ':penulis'       => $penulis,
        ':views'         => $views,
        ':is_published'  => $is_published,
        ':published_at'  => $published_at
    ]);

    $insertId = $pdo->lastInsertId();

    echo json_encode([
        "success" => true,
        "message" => "Artikel berhasil ditambahkan.",
        "id" => $insertId
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Kesalahan database: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Kesalahan server: " . $e->getMessage()
    ]);
}
