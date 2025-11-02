<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../../config/config_database.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secret_key = 'GANTI_DENGAN_SECRET_KEY_MU'; // ganti dengan secret key yang sama seperti saat membuat token

try {
    // ===== JWT AUTH START (format & urutan sesuai standar) =====
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

    if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authorization header tidak ditemukan atau format salah.']);
        exit;
    }

    $jwt = $matches[1];

    try {
        $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token tidak valid atau kedaluwarsa.']);
        exit;
    }

    // Ambil data user/role dari token (jika ada)
    $userId = $decoded->data->user_id ?? ($decoded->user_id ?? null);
    $role   = $decoded->data->role ?? ($decoded->role ?? null);

    // Batasi hanya admin (opsional — hapus blok ini jika tidak ingin membatasi)
    if ($role !== null && $role !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Akses ditolak (role tidak mencukupi).']);
        exit;
    }
    // ===== JWT AUTH END =====

    // Pastikan koneksi PDO tersedia
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection not found.']);
        exit;
    }

    // Ambil input JSON
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Format JSON tidak valid."]);
        exit;
    }

    // id wajib untuk update
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Parameter 'id' wajib untuk update."]);
        exit;
    }

    // cek apakah produk ada
    $chk = $pdo->prepare("SELECT id FROM koperasi_produk WHERE id = :id LIMIT 1");
    $chk->execute([':id' => $id]);
    if (!$chk->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Produk tidak ditemukan."]);
        exit;
    }

    // kolom yang boleh diupdate
    $allowed = ['nama_produk','deskripsi','kategori','harga','satuan','gambar','stok','is_active'];
    $set = [];
    $params = [];

    foreach ($allowed as $col) {
        if (array_key_exists($col, $input)) {
            $val = $input[$col];
            if ($val === null) {
                $set[] = "$col = NULL";
            } else {
                // validasi / casting berdasarkan kolom
                if ($col === 'harga') {
                    if ($val === '' || $val === null) {
                        $set[] = "harga = NULL";
                    } else {
                        if (!is_numeric($val)) {
                            http_response_code(400);
                            echo json_encode(["success" => false, "message" => "Field 'harga' harus berupa angka atau kosongkan."]);
                            exit;
                        }
                        // format ke 2 desimal agar sesuai DECIMAL(15,2)
                        $formatted = number_format((float)$val, 2, '.', '');
                        $set[] = "harga = :harga";
                        $params[':harga'] = $formatted;
                    }
                } elseif ($col === 'stok' || $col === 'is_active') {
                    $set[] = "$col = :$col";
                    $params[":$col"] = (int)$val;
                } else {
                    // string fields
                    if ($val === '') {
                        // if empty string we store NULL to be consistent (optional)
                        $set[] = "$col = NULL";
                    } else {
                        $set[] = "$col = :$col";
                        $params[":$col"] = $val;
                    }
                }
            }
        }
    }

    if (empty($set)) {
        echo json_encode(["success" => false, "message" => "Tidak ada field untuk diupdate."]);
        exit;
    }

    // always update timestamp
    $set[] = "updated_at = NOW()";

    $sql = "UPDATE koperasi_produk SET " . implode(", ", $set) . " WHERE id = :id LIMIT 1";
    $params[':id'] = $id;

    // eksekusi dalam transaksi
    $pdo->beginTransaction();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Produk berhasil diupdate.",
        "id" => $id
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Kesalahan database: " . $e->getMessage()]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Kesalahan server: " . $e->getMessage()]);
}
