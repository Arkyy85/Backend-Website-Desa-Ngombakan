<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . '/../../config/config_database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

// Secret key sama seperti di login.php
$secret_key = "#112q282232%@!Q#1@!122221!@1";

// Ambil Authorization header
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Authorization header tidak ditemukan."]);
    exit;
}

$authHeader = $headers['Authorization'];
if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Format Authorization header salah."]);
    exit;
}

$jwt = $matches[1];

// Verifikasi token JWT
try {
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
    // Jika perlu, kamu bisa akses data user dari token, contoh:
    // $user_id = $decoded->data->id ?? null;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Token tidak valid atau sudah kedaluwarsa."]);
    exit;
}

// Proses input JSON
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
    $stmt = $pdo->prepare("
        INSERT INTO agenda (title, date, time, location, category, description, participants, status)
        VALUES (:title, :date, :time, :location, :category, :description, :participants, :status)
    ");
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
