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
    $id = $_GET['id'] ?? null;
    $tipe = $_GET['tipe'] ?? null;
    $kategori = $_GET['kategori'] ?? null;

    if ($id) {
        // Get by ID
        $stmt = $pdo->prepare("SELECT * FROM galeri WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            echo json_encode(["success" => true, "data" => $data], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Galeri tidak ditemukan."]);
        }
    } else {
        // Filter opsional: tipe & kategori
        $query = "SELECT * FROM galeri WHERE 1=1";
        $params = [];

        if (!empty($tipe)) {
            $query .= " AND tipe = :tipe";
            $params[':tipe'] = $tipe;
        }

        if (!empty($kategori)) {
            $query .= " AND kategori = :kategori";
            $params[':kategori'] = $kategori;
        }

        $query .= " ORDER BY urutan ASC, created_at DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["success" => true, "data" => $data], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Kesalahan database: " . $e->getMessage()]);
}
