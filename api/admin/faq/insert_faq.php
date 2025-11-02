<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../../config/config_database.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

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

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500); echo json_encode(['success'=>false,'message'=>'Database connection not found.']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = [];

$pertanyaan = trim($input['pertanyaan'] ?? '');
$jawaban     = trim($input['jawaban'] ?? '');
$kategori    = isset($input['kategori']) ? trim($input['kategori']) : null;
$urutan      = isset($input['urutan']) ? (int)$input['urutan'] : 0;
$is_active   = isset($input['is_active']) ? (int)$input['is_active'] : 1;

if ($pertanyaan === '' || $jawaban === '') {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>"Field 'pertanyaan' dan 'jawaban' wajib diisi."]);
    exit;
}

try {
    $sql = "INSERT INTO faq (pertanyaan, jawaban, kategori, urutan, is_active)
            VALUES (:pertanyaan, :jawaban, :kategori, :urutan, :is_active)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':pertanyaan'=>$pertanyaan,
        ':jawaban'=>$jawaban,
        ':kategori'=>$kategori !== '' ? $kategori : null,
        ':urutan'=>$urutan,
        ':is_active'=>$is_active
    ]);
    $id = (int)$pdo->lastInsertId();
    echo json_encode(['success'=>true,'message'=>'FAQ berhasil dibuat.','id'=>$id]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Database error: '.$e->getMessage()]);
}
