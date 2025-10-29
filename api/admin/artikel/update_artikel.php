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
