<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../../config/config_database.php';

try {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Format JSON tidak valid."]);
        exit;
    }

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

    $allowed = ['nama_produk','deskripsi','kategori','harga','satuan','gambar','stok','is_active'];
    $set = [];
    $params = [];

    foreach ($allowed as $col) {
        if (array_key_exists($col, $input)) {
            if ($input[$col] === null) {
                $set[] = "$col = NULL";
            } else {
                $set[] = "$col = :$col";
                if ($col === 'harga') {
                    $params[":$col"] = (float)$input[$col];
                } elseif ($col === 'stok' || $col === 'is_active') {
                    $params[":$col"] = (int)$input[$col];
                } else {
                    $params[":$col"] = $input[$col];
                }
            }
        }
    }

    if (empty($set)) {
        echo json_encode(["success" => false, "message" => "Tidak ada field untuk diupdate."]);
        exit;
    }

    $set[] = "updated_at = NOW()";
    $sql = "UPDATE koperasi_produk SET " . implode(", ", $set) . " WHERE id = :id LIMIT 1";
    $params[':id'] = $id;

    $pdo->beginTransaction();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pdo->commit();

    echo json_encode(["success" => true, "message" => "Produk berhasil diupdate.", "id" => $id], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Kesalahan database: " . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Kesalahan server: " . $e->getMessage()]);
}
