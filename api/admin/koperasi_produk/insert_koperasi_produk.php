<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../../config/config_database.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

try {
    // 🔒 CEK JWT AUTH (format & urutan sesuai standar)
    $secret_key = "#112q282232%@!Q#1@!122221!@1";

    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    $token = null;

    if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
    }

    // fallback ke $_SERVER kalau getallheaders tidak tersedia atau nama header berbeda
    if (!$token) {
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
        // ambil informasi user jika tersedia (tetap tidak digunakan di insert karena tabel tidak punya created_by)
        $user = $decoded->user ?? $decoded->data ?? null;
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

    // 🔧 PROSES INPUT
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Format JSON tidak valid."]);
        exit;
    }

    $nama_produk = trim($input['nama_produk'] ?? '');
    $deskripsi   = array_key_exists('deskripsi', $input) ? $input['deskripsi'] : null;
    $kategori    = isset($input['kategori']) ? trim($input['kategori']) : null;
    $harga       = isset($input['harga']) && $input['harga'] !== '' ? (string)$input['harga'] : null; // simpan sebagai string supaya sesuai DECIMAL
    $satuan      = isset($input['satuan']) ? trim($input['satuan']) : null;
    $gambar      = isset($input['gambar']) ? trim($input['gambar']) : null;
    $stok        = isset($input['stok']) ? (int)$input['stok'] : 0;
    $is_active   = isset($input['is_active']) ? (int)$input['is_active'] : 1;

    if ($nama_produk === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Field 'nama_produk' wajib diisi."]);
        exit;
    }

    // validasi harga jika diberikan (boleh null)
    if ($harga !== null) {
        // terima angka dengan atau tanpa desimal, tapi pastikan numeric
        if (!is_numeric($harga)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Field 'harga' harus berupa angka atau dikosongkan."]);
            exit;
        }
        // format ke 2 desimal (optional)
        $harga = number_format((float)$harga, 2, '.', '');
    }

    // 🔄 INSERT PRODUK BARU (sesuai struktur tabel yang valid)
    $sql = "INSERT INTO koperasi_produk
            (nama_produk, deskripsi, kategori, harga, satuan, gambar, stok, is_active)
            VALUES
            (:nama_produk, :deskripsi, :kategori, :harga, :satuan, :gambar, :stok, :is_active)";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':nama_produk', $nama_produk, PDO::PARAM_STR);
    // bind nullable fields properly
    if ($deskripsi === null || $deskripsi === '') {
        $stmt->bindValue(':deskripsi', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':deskripsi', $deskripsi, PDO::PARAM_STR);
    }
    if ($kategori === null || $kategori === '') {
        $stmt->bindValue(':kategori', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':kategori', $kategori, PDO::PARAM_STR);
    }
    if ($harga === null) {
        $stmt->bindValue(':harga', null, PDO::PARAM_NULL);
    } else {
        // gunakan string yang sudah terformat
        $stmt->bindValue(':harga', $harga);
    }
    if ($satuan === null || $satuan === '') {
        $stmt->bindValue(':satuan', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':satuan', $satuan, PDO::PARAM_STR);
    }
    if ($gambar === null || $gambar === '') {
        $stmt->bindValue(':gambar', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':gambar', $gambar, PDO::PARAM_STR);
    }
    $stmt->bindValue(':stok', $stok, PDO::PARAM_INT);
    $stmt->bindValue(':is_active', $is_active, PDO::PARAM_INT);

    $stmt->execute();

    $id = (int)$pdo->lastInsertId();

    echo json_encode([
        "success" => true,
        "message" => "Produk berhasil ditambahkan.",
        "id" => $id
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Kesalahan database: " . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Kesalahan server: " . $e->getMessage()]);
}
