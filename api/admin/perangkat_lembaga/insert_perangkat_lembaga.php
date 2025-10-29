<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Tangani preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../../config/config_database.php';

try {
    // Ambil input JSON
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Format JSON tidak valid."
        ]);
        exit;
    }

    // Ambil data dari input
    $lembaga_id = isset($input['lembaga_id']) ? (int)$input['lembaga_id'] : 0;
    $nama       = trim($input['nama'] ?? '');
    $jabatan    = trim($input['jabatan'] ?? '');
    $foto       = $input['foto'] ?? null;
    $email      = $input['email'] ?? null;
    $telepon    = $input['telepon'] ?? null;
    $alamat     = $input['alamat'] ?? null;
    $facebook   = $input['facebook'] ?? null;
    $twitter    = $input['twitter'] ?? null;
    $instagram  = $input['instagram'] ?? null;
    $urutan     = isset($input['urutan']) ? (int)$input['urutan'] : 0;
    $is_active  = isset($input['is_active']) ? (int)$input['is_active'] : 1;

    // Validasi wajib isi
    if ($lembaga_id <= 0 || empty($nama) || empty($jabatan)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Field 'lembaga_id', 'nama', dan 'jabatan' wajib diisi."
        ]);
        exit;
    }

    // Cek apakah lembaga_id valid
    $checkLembaga = $pdo->prepare("SELECT id FROM lembaga WHERE id = :id LIMIT 1");
    $checkLembaga->execute([':id' => $lembaga_id]);
    if (!$checkLembaga->fetch()) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Lembaga dengan ID $lembaga_id tidak ditemukan."
        ]);
        exit;
    }

    // Query insert
    $sql = "INSERT INTO lembaga_perangkat (
                lembaga_id, nama, jabatan, foto, email, telepon, alamat,
                facebook, twitter, instagram, urutan, is_active
            ) VALUES (
                :lembaga_id, :nama, :jabatan, :foto, :email, :telepon, :alamat,
                :facebook, :twitter, :instagram, :urutan, :is_active
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':lembaga_id' => $lembaga_id,
        ':nama'       => $nama,
        ':jabatan'    => $jabatan,
        ':foto'       => $foto,
        ':email'      => $email,
        ':telepon'    => $telepon,
        ':alamat'     => $alamat,
        ':facebook'   => $facebook,
        ':twitter'    => $twitter,
        ':instagram'  => $instagram,
        ':urutan'     => $urutan,
        ':is_active'  => $is_active
    ]);

    // Ambil ID terakhir
    $insertId = $pdo->lastInsertId();

    echo json_encode([
        "success" => true,
        "message" => "Data perangkat lembaga berhasil ditambahkan.",
        "id" => $insertId
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
