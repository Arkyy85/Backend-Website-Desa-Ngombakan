<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Tangani preflight CORS
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

    // Cek apakah data ada
    $check = $pdo->prepare("SELECT * FROM lembaga_perangkat WHERE id = :id LIMIT 1");
    $check->execute([':id' => $id]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Data lembaga_perangkat tidak ditemukan."]);
        exit;
    }

    // Update field jika ada
    $fields = [
        'lembaga_id', 'nama', 'jabatan', 'foto', 'email',
        'telepon', 'alamat', 'facebook', 'twitter', 'instagram',
        'urutan', 'is_active'
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

    $sql = "UPDATE lembaga_perangkat SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(["success" => true, "message" => "Data lembaga_perangkat berhasil diperbarui."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Kesalahan database: " . $e->getMessage()]);
}
