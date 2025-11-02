<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    exit; 
}

require_once __DIR__ . '/../../../config/config_database.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

// Secret key sama seperti di login.php
$secret_key = "#112q282232%@!Q#1@!122221!@1";

// --- JWT AUTH CHECK ---
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$token = null;

if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authorization header tidak ditemukan atau format salah.']);
    exit;
}

try {
    $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
    // Ambil informasi user dari token
    $user = $decoded->user ?? null;
    if (!$user) {
        // http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token tidak memiliki data user.']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token tidak valid: ' . $e->getMessage()]);
    exit;
}
// --- END AUTH ---

if (!isset($pdo) || !($pdo instanceof PDO)) { 
    http_response_code(500); 
    echo json_encode(['success' => false, 'message' => 'Database connection not found.']); 
    exit; 
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = [];

$id = isset($input['id']) ? (int)$input['id'] : 0;
if ($id <= 0) { 
    http_response_code(400); 
    echo json_encode(['success' => false, 'message' => 'Field id wajib untuk update.']); 
    exit; 
}

try {
    $check = $pdo->prepare("SELECT id FROM faq WHERE id = :id LIMIT 1");
    $check->execute([':id' => $id]);
    if (!$check->fetch(PDO::FETCH_ASSOC)) { 
        echo json_encode(['success' => false, 'message' => 'FAQ tidak ditemukan.']); 
        exit; 
    }

    $fields = ['pertanyaan','jawaban','kategori','urutan','is_active'];
    $parts = []; 
    $params = [];
    foreach ($fields as $f) {
        if (array_key_exists($f, $input)) {
            if ($input[$f] === null) { 
                $parts[] = "$f = NULL"; 
            } else { 
                $parts[] = "$f = :$f"; 
                $params[":$f"] = $input[$f]; 
            }
        }
    }
    if (empty($parts)) { 
        echo json_encode(['success' => true, 'message' => 'Tidak ada perubahan.']); 
        exit; 
    }

    $params[':id'] = $id;
    $sql = "UPDATE faq SET " . implode(', ', $parts) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['success' => true, 'message' => 'FAQ berhasil diupdate.', 'id' => $id]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
