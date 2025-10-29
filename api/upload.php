<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/config_database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

$secret_key = "#112q282232%@!Q#1@!122221!@1";

$response = ["status" => "error", "message" => "Terjadi kesalahan."];

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$token = null;

if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}

if (!$token) {
    echo json_encode(["status" => "error", "message" => "Token Authorization dibutuhkan"]);
    exit;
}

try {
    $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Token tidak valid: " . $e->getMessage()]);
    exit;
}

$baseUrl = "https://billing.hallosemarang.com";

// Pastikan folder uploads ada
$uploadDir = __DIR__ . "/../uploads/teknisi/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file'])) {
        $fileTmpPath = $_FILES['file']['tmp_name'];
        $fileExtension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

        // Generate nama unik
        $newFileName = uniqid('img_', true) . "." . strtolower($fileExtension);

        $destPath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $fileUrl = $baseUrl . "/uploads/teknisi/" . $newFileName;
            echo json_encode([
                "status" => "success",
                "message" => "File berhasil diupload",
                "path" => $fileUrl
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Gagal memindahkan file"
            ]);
        }
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "File tidak ditemukan di request"
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Hanya POST method yang diperbolehkan"
    ]);
}
