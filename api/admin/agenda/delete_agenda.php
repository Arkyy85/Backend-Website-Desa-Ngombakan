<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../../config/config_database.php';

try {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) $input = [];

    // bisa ambil id dari body JSON atau query param ?id=...
    $id = isset($input['id']) ? (int)$input['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Parameter 'id' wajib untuk menghapus."]);
        exit;
    }

    // cek apakah ada
    $check = $pdo->prepare("SELECT id FROM agenda WHERE id = :id LIMIT 1");
    $check->execute([':id' => $id]);
    $found = $check->fetch(PDO::FETCH_ASSOC);
    if (!$found) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => "Agenda dengan id={$id} tidak ditemukan."]);
        exit;
    }

    // delete
    $stmt = $pdo->prepare("DELETE FROM agenda WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);

    echo json_encode(['success' => true, 'message' => 'Agenda berhasil dihapus.', 'id' => $id], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kesalahan database: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
}
