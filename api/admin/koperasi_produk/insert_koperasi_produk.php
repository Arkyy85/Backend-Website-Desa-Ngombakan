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

    $nama_produk = trim($input['nama_produk'] ?? '');
    $deskripsi   = $input['deskripsi'] ?? null;
    $kategori    = isset($input['kategori']) ? trim($input['kategori']) : null;
    $harga       = isset($input['harga']) && $input['harga'] !== '' ? (float)$input['harga'] : null;
    $satuan      = isset($input['satuan']) ? trim($input['satuan']) : null;
    $gambar      = isset($input['gambar']) ? trim($input['gambar']) : null;
    $stok        = isset($input['stok']) ? (int)$input['stok'] : 0;
    $is_active   = isset($input['is_active']) ? (int)$input['is_active'] : 1;

    if ($nama_produk === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Field 'nama_produk' wajib diisi."]);
        exit;
    }

    $sql = "INSERT INTO koperasi_produk
            (nama_produk, deskripsi, kategori, harga, satuan, gambar, stok, is_active)
            VALUES
            (:nama_produk, :deskripsi, :kategori, :harga, :satuan, :gambar, :stok, :is_active)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nama_produk' => $nama_produk,
        ':deskripsi'   => $deskripsi !== '' ? $deskripsi : null,
        ':kategori'    => $kategori !== '' ? $kategori : null,
        ':harga'       => $harga,
        ':satuan'      => $satuan !== '' ? $satuan : null,
        ':gambar'      => $gambar !== '' ? $gambar : null,
        ':stok'        => $stok,
        ':is_active'   => $is_active
    ]);

    $id = $pdo->lastInsertId();

    echo json_encode(["success" => true, "message" => "Produk berhasil ditambahkan.", "id" => $id], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Kesalahan database: " . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Kesalahan server: " . $e->getMessage()]);
}
