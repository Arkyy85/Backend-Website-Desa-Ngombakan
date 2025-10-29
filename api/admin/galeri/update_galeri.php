<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Tangani preflight (CORS)
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
    $judul       = $input['judul'] ?? null;
    $deskripsi   = $input['deskripsi'] ?? null;
    $tipe        = $input['tipe'] ?? null;
    $file_path   = $input['file_path'] ?? null;
    $video_url   = $input['video_url'] ?? null;
    $thumbnail   = $input['thumbnail'] ?? null;
    $kategori    = $input['kategori'] ?? null;
    $urutan      = $input['urutan'] ?? null;
    $is_active   = $input['is_active'] ?? null;

    $fields = [];
    $params = [':id' => $id];

    $map = [
        'judul' => $judul,
        'deskripsi' => $deskripsi,
        'tipe' => $tipe,
        'file_path' => $file_path,
        'video_url' => $video_url,
        'thumbnail' => $thumbnail,
        'kategori' => $kategori,
        'urutan' => $urutan,
        'is_active' => $is_active
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

    // Update query
    $sql = "UPDATE galeri SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(["success" => true, "message" => "Data galeri berhasil diperbarui."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Kesalahan database: " . $e->getMessage()]);
}
