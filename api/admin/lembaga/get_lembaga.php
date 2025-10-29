<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Tangani preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../../config/config_database.php';

try {
    // Jika ada parameter ID, tampilkan satu data
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id = (int)$_GET['id'];

        $stmt = $pdo->prepare("SELECT * FROM lembaga WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            echo json_encode([
                "success" => true,
                "data" => $data
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "Data lembaga dengan ID $id tidak ditemukan."
            ]);
        }

        exit;
    }

    // Jika ada parameter tipe, filter berdasarkan tipe
    if (isset($_GET['tipe']) && !empty($_GET['tipe'])) {
        $tipe = trim($_GET['tipe']);
        $allowedTipe = ['rt', 'rw', 'bpd', 'pkk', 'karang_taruna', 'posyandu', 'lainnya'];

        if (!in_array($tipe, $allowedTipe)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Tipe tidak valid. Gunakan salah satu: " . implode(", ", $allowedTipe)
            ]);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM lembaga WHERE tipe = :tipe ORDER BY created_at DESC");
        $stmt->execute([':tipe' => $tipe]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "success" => true,
            "count" => count($data),
            "data" => $data
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // Jika tidak ada parameter, tampilkan semua data
    $stmt = $pdo->query("SELECT * FROM lembaga ORDER BY created_at DESC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "count" => count($data),
        "data" => $data
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Kesalahan database: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Kesalahan server: " . $e->getMessage()
    ]);
}
