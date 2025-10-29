<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Tangani preflight
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

    $id = isset($input['id']) ? (int)$input['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Field 'id' wajib diisi."]);
        exit;
    }

    // Cek apakah data berita ada
    $check = $pdo->prepare("SELECT * FROM berita WHERE id = :id LIMIT 1");
    $check->execute([':id' => $id]);
    if (!$check->fetch()) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Data berita tidak ditemukan."]);
        exit;
    }

    // Field yang boleh diupdate
    $fields = [
        'judul', 'slug', 'kategori', 'ringkasan', 'konten',
        'gambar_utama', 'penulis', 'views', 'is_published', 'published_at'
    ];

    $updates = [];
    $params = [':id' => $id];

    foreach ($fields as $field) {
        if (isset($input[$field])) {
            $updates[] = "$field = :$field";
            $params[":$field"] = $input[$field];
        }
    }

    if (empty($updates)) {
        echo json_encode(["success" => false, "message" => "Tidak ada field yang diperbarui."]);
        exit;
    }

    // Jika slug berubah, pastikan tidak duplikat
    if (isset($input['slug'])) {
        $checkSlug = $pdo->prepare("SELECT id FROM berita WHERE slug = :slug AND id != :id");
        $checkSlug->execute([':slug' => $input['slug'], ':id' => $id]);
        if ($checkSlug->fetch()) {
            http_response_code(409);
            echo json_encode(["success" => false, "message" => "Slug sudah digunakan oleh berita lain."]);
            exit;
        }
    }

    $sql = "UPDATE berita SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(["success" => true, "message" => "Data berita berhasil diperbarui."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Kesalahan database: " . $e->getMessage()]);
}
