<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Pastikan metode DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Metode request tidak diizinkan. Gunakan DELETE.'
    ]);
    exit;
}

// Muat konfigurasi database
require_once __DIR__ . '/../../../config/config_database.php';

// Pastikan koneksi tersedia
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database tidak tersedia.'
    ]);
    exit;
}

// Ambil body JSON (optional: id), default ke id=1 karena tabel hanya punya satu data
$input = json_decode(file_get_contents("php://input"), true);
$id = isset($input['id']) ? (int)$input['id'] : 1;

try {
    // Cek apakah data ada
    $checkStmt = $pdo->prepare("SELECT id FROM profil_desa WHERE id = :id LIMIT 1");
    $checkStmt->execute([':id' => $id]);
    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$exists) {
        echo json_encode([
            'success' => false,
            'message' => 'Data profil desa tidak ditemukan.'
        ]);
        exit;
    }

    // Hapus data
    $delStmt = $pdo->prepare("DELETE FROM profil_desa WHERE id = :id LIMIT 1");
    $executed = $delStmt->execute([':id' => $id]);
    $affected = $delStmt->rowCount();

    if ($executed && $affected > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Data profil desa berhasil dihapus.',
            'id' => $id
        ]);
    } else {
        throw new Exception("Tidak ada baris yang terhapus.");
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
