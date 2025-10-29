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

// Pastikan koneksi PDO tersedia
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database tidak tersedia.'
    ]);
    exit;
}

try {
    // Query ambil data profil_desa id=1
    $sql = "
        SELECT 
            id,
            nama_desa,
            kode_desa,
            kecamatan,
            kabupaten,
            provinsi,
            kode_pos,
            sejarah,
            visi,
            misi,
            luas_wilayah,
            batas_utara,
            batas_selatan,
            batas_timur,
            batas_barat,
            jumlah_penduduk,
            jumlah_kk,
            jumlah_laki,
            jumlah_perempuan,
            created_at,
            updated_at
        FROM profil_desa
        WHERE id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => 1]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode([
            'success' => true,
            'message' => 'Data profil desa berhasil diambil.',
            'data' => $data
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Data profil desa belum tersedia.',
            'data' => null
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

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
