<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

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
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Format JSON tidak valid."]);
        exit;
    }

    $id = $input['id'] ?? null;
    if (empty($id)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Parameter 'id' wajib diisi."]);
        exit;
    }

    // Ambil data input (boleh sebagian)
    $judul         = $input['judul'] ?? null;
    $slug          = $input['slug'] ?? null;
    $kategori      = $input['kategori'] ?? null;
    $ringkasan     = $input['ringkasan'] ?? null;
    $konten        = $input['konten'] ?? null;
    $gambar_utama  = $input['gambar_utama'] ?? null;
    $penulis       = $input['penulis'] ?? null;
    $views         = $input['views'] ?? null;
    $is_published  = $input['is_published'] ?? null;
    $published_at  = $input['published_at'] ?? null;

    // Bangun query dinamis
    $fields = [];
    $params = [':id' => $id];

    $map = [
        'judul' => $judul,
        'slug' => $slug,
        'kategori' => $kategori,
        'ringkasan' => $ringkasan,
        'konten' => $konten,
        'gambar_utama' => $gambar_utama,
        'penulis' => $penulis,
        'views' => $views,
        'is_published' => $is_published,
        'published_at' => $published_at
    ];

    foreach ($map as $key => $value) {
        if ($value !== null) {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Tidak ada data yang diupdate."]);
        exit;
    }

    $sql = "UPDATE artikel SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(["success" => true, "message" => "Data artikel berhasil diperbarui."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Kesalahan database: " . $e->getMessage()]);
}
