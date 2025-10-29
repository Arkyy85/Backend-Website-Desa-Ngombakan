<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../../config/config_database.php';

$input = json_decode(file_get_contents("php://input"), true);
$id = $input['id'] ?? '';

if (empty($id)) {
    echo json_encode(["status" => "error", "message" => "ID wajib diisi."]);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM agenda WHERE id = :id");
    $stmt->execute([':id' => $id]);

    echo json_encode(["status" => "success", "message" => "Agenda berhasil dihapus."]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
