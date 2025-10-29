<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Tangani preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../../config/config_database.php';

try {
    // Ambil input JSON (karena DELETE tidak punya $_POST)
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Format JSON tidak valid."]);
        exit;
    }

    // Ambil ID lembaga yang akan dihapus
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Parameter 'id' wajib diisi dan harus berupa angka."
        ]);
        exit;
    }

    // Cek apakah data ada
    $check = $pdo->prepare("SELECT id, nama_lembaga FROM lembaga WHERE id = :id LIMIT 1");
    $check->execute([':id' => $id]);
    $lembaga = $check->fetch(PDO::FETCH_ASSOC);

    if (!$lembaga) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Data lembaga dengan ID $id tidak ditemukan."
        ]);
        exit;
    }

    // Hapus data
    $stmt = $pdo->prepare("DELETE FROM lembaga WHERE id = :id");
    $stmt->execute([':id' => $id]);

    echo json_encode([
        "success" => true,
        "message" => "Data lembaga '{$lembaga['nama_lembaga']}' berhasil dihapus.",
        "id" => $id
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
