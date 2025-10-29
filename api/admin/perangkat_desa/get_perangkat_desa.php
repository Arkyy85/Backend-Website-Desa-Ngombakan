<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tangani preflight request (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../../config/config_database.php';

try {
    // Ambil parameter opsional (misalnya filter kategori atau status)
    $kategori = $_GET['kategori'] ?? null;
    $is_active = isset($_GET['is_active']) ? (int)$_GET['is_active'] : null;

    $query = "
        SELECT 
            id,
            nama,
            jabatan,
            kategori,
            nip,
            foto,
            email,
            telepon,
            alamat,
            facebook,
            twitter,
            instagram,
            urutan,
            is_active,
            created_at,
            updated_at
        FROM perangkat_desa
    ";

    // Buat kondisi dinamis jika ada filter
    $conditions = [];
    $params = [];

    if (!empty($kategori)) {
        $conditions[] = "kategori = :kategori";
        $params[':kategori'] = $kategori;
    }

    if ($is_active !== null) {
        $conditions[] = "is_active = :is_active";
        $params[':is_active'] = $is_active;
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY kategori, urutan ASC, nama ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => $data ? 'Data perangkat desa berhasil diambil.' : 'Belum ada data perangkat desa.',
        'count' => count($data),
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

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
        'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
