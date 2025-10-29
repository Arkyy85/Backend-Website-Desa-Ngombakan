<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../../config/config_database.php';

$input = json_decode(file_get_contents("php://input"), true);
$id = $input['id'] ?? '';
$title = $input['title'] ?? '';
$date = $input['date'] ?? '';
$time = $input['time'] ?? '';
$location = $input['location'] ?? '';
$category = $input['category'] ?? '';
$description = $input['description'] ?? '';
$participants = $input['participants'] ?? '';
$status = $input['status'] ?? '';

if (empty($id)) {
    echo json_encode(["status" => "error", "message" => "ID wajib diisi."]);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE agenda SET
        title = :title,
        date = :date,
        time = :time,
        location = :location,
        category = :category,
        description = :description,
        participants = :participants,
        status = :status
        WHERE id = :id");
    $stmt->execute([
        ':id' => $id,
        ':title' => $title,
        ':date' => $date ? date('Y-m-d', strtotime($date)) : null,
        ':time' => $time,
        ':location' => $location,
        ':category' => $category,
        ':description' => $description,
        ':participants' => $participants,
        ':status' => $status
    ]);

    echo json_encode(["status" => "success", "message" => "Agenda berhasil diperbarui."]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
