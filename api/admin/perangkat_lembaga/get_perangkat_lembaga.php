<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
require_once __DIR__ . '/../../../config/config_database.php';

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $lembaga_id = isset($_GET['lembaga_id']) ? (int)$_GET['lembaga_id'] : 0;

    if ($id > 0) {
        // Get by ID
        $stmt = $pdo->prepare("SELECT * FROM lembaga_perangkat WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Data tidak ditemukan."]);
            exit;
        }
        echo json_encode(["success" => true, "data" => $data]);
    } elseif ($lembaga_id > 0) {
        // Get by lembaga_id
        $stmt = $pdo->prepare("SELECT * FROM lembaga_perangkat WHERE lembaga_id = :lembaga_id ORDER BY urutan ASC, id ASC");
        $stmt->execute([':lembaga_id' => $lembaga_id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $data]);
    } else {
        // Get all
        $stmt = $pdo->query("SELECT * FROM lembaga_perangkat ORDER BY lembaga_id ASC, urutan ASC, id ASC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $data]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Kesalahan database: " . $e->getMessage()]);
}
