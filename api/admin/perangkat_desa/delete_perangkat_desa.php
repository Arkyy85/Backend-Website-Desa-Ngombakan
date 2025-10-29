<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tangani preflight (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../../config/config_database.php';

try {
    // Dapatkan data JSON atau parameter
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = [];

    $id = isset($input['id']) ? (int)$input['id'] : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Parameter "id" wajib diisi untuk menghapus data.'
        ]);
        exit;
    }

    // Cek apakah data ada
    $check = $pdo->prepare("SELECT id FROM perangkat_desa WHERE id = :id LIMIT 1");
    $check->execute([':id' => $id]);
    $exists = $check->fetch(PDO::FETCH_ASSOC);

    if (!$exists) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => "Data perangkat desa dengan id={$id} tidak ditemukan."
        ]);
        exit;
    }

    // Lakukan delete
    $stmt = $pdo->prepare("DELETE FROM perangkat_desa WHERE id = :id LIMIT 1");
    $deleted = $stmt->execute([':id' => $id]);

    if ($deleted) {
        echo json_encode([
            'success' => true,
            'message' => "Data perangkat desa dengan id={$id} berhasil dihapus."
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menghapus data perangkat desa.'
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Kesalahan database: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()
    ]);
}
