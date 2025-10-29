<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../../config/config_database.php';

try {
    // Ambil parameter id (jika ada)
    $id = $_GET['id'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM artikel WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            echo json_encode(["success" => true, "data" => $data], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Artikel tidak ditemukan."]);
        }
    } else {
        // Jika tidak ada ID, tampilkan semua
        $stmt = $pdo->query("SELECT * FROM artikel ORDER BY created_at DESC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $data], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Kesalahan database: " . $e->getMessage()]);
}
