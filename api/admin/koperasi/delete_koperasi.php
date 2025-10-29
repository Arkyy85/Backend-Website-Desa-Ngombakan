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
    // cek ada
    $check = $pdo->prepare("SELECT id FROM koperasi WHERE id = 1 LIMIT 1");
    $check->execute();
    $row = $check->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Data koperasi tidak ditemukan.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Hapus record
    $stmt = $pdo->prepare("DELETE FROM koperasi WHERE id = 1 LIMIT 1");
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Data koperasi berhasil dihapus.'
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Kesalahan database: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Kesalahan server: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
