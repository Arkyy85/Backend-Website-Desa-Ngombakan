<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Tangani preflight request (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../../config/config_database.php';

try {
    // Pastikan koneksi PDO aktif
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception("Database connection not found.");
    }

    // Cek apakah ada data
    $check = $pdo->query("SELECT id FROM site_settings LIMIT 1");
    $row = $check->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode([
            "status" => "error",
            "message" => "Tidak ada data site_settings yang tersimpan."
        ]);
        exit;
    }

    $id = (int)$row['id'];

    // Hapus data (karena hanya ada satu baris)
    $delete = $pdo->prepare("DELETE FROM site_settings WHERE id = ? LIMIT 1");
    $delete->execute([$id]);

    echo json_encode([
        "status" => "success",
        "message" => "Site settings berhasil dihapus.",
        "id" => $id
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
