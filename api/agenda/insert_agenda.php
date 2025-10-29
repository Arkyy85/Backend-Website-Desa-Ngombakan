<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../../config/config_database.php';

$input = json_decode(file_get_contents("php://input"), true);
$title = $input['title'] ?? '';
$date = $input['date'] ?? '';
$time = $input['time'] ?? '';
$location = $input['location'] ?? '';
$category = $input['category'] ?? '';
$description = $input['description'] ?? '';
$participants = $input['participants'] ?? '';
$status = $input['status'] ?? 'upcoming';

if (empty($title) || empty($date)) {
    echo json_encode(["status" => "error", "message" => "Title dan date wajib diisi."]);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO agenda (title, date, time, location, category, description, participants, status)
                           VALUES (:title, :date, :time, :location, :category, :description, :participants, :status)");
    $stmt->execute([
        ':title' => $title,
        ':date' => date('Y-m-d', strtotime($date)),
        ':time' => $time,
        ':location' => $location,
        ':category' => $category,
        ':description' => $description,
        ':participants' => $participants,
        ':status' => $status
    ]);

    echo json_encode(["status" => "success", "message" => "Agenda berhasil ditambahkan."]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
