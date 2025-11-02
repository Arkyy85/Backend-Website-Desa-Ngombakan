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

// 🔐 SECRET KEY (samakan dengan di login)
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
// --- END AUTH CHECK ---

try {
    // Ambil input JSON
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Format JSON tidak valid."]);
        exit;
    }

    // Ambil data dari input
    $judul       = trim($input['judul'] ?? '');
    $deskripsi   = $input['deskripsi'] ?? null;
    $tipe        = strtolower(trim($input['tipe'] ?? ''));
    $file_path   = $input['file_path'] ?? null;
    $video_url   = $input['video_url'] ?? null;
    $thumbnail   = $input['thumbnail'] ?? null;
    $kategori    = $input['kategori'] ?? null;
    $urutan      = isset($input['urutan']) ? (int)$input['urutan'] : 0;
    $is_active   = isset($input['is_active']) ? (int)$input['is_active'] : 1;

    // Validasi wajib isi
    if (empty($judul) || empty($tipe)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Field 'judul' dan 'tipe' wajib diisi."
        ]);
        exit;
    }

    // Validasi tipe
    $allowedTipe = ['foto', 'video'];
    if (!in_array($tipe, $allowedTipe)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Tipe tidak valid. Gunakan salah satu: " . implode(", ", $allowedTipe)
        ]);
        exit;
    }

    // Validasi tambahan berdasarkan tipe
    if ($tipe === 'foto' && empty($file_path)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Untuk tipe 'foto', field 'file_path' wajib diisi."
        ]);
        exit;
    }

    if ($tipe === 'video' && empty($video_url)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Untuk tipe 'video', field 'video_url' wajib diisi."
        ]);
        exit;
    }

    // Query insert
    $sql = "INSERT INTO galeri (
                judul, deskripsi, tipe, file_path, video_url, thumbnail, kategori, urutan, is_active
            ) VALUES (
                :judul, :deskripsi, :tipe, :file_path, :video_url, :thumbnail, :kategori, :urutan, :is_active
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':judul'      => $judul,
        ':deskripsi'  => $deskripsi,
        ':tipe'       => $tipe,
        ':file_path'  => $file_path,
        ':video_url'  => $video_url,
        ':thumbnail'  => $thumbnail,
        ':kategori'   => $kategori,
        ':urutan'     => $urutan,
        ':is_active'  => $is_active
    ]);

    $insertId = $pdo->lastInsertId();

    echo json_encode([
        "success" => true,
        "message" => "Data galeri berhasil ditambahkan.",
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
