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
        echo json_encode(["success" => false, "message" => "Format JSON tidak valid."]);
        exit;
    }

    // Ambil data dari input
    $nama       = trim($input['nama'] ?? '');
    $jabatan    = trim($input['jabatan'] ?? '');
    $kategori   = trim($input['kategori'] ?? '');
    $nip        = $input['nip'] ?? null;
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
    if (empty($nama) || empty($jabatan) || empty($kategori)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Field 'nama', 'jabatan', dan 'kategori' wajib diisi."
        ]);
        exit;
    }

    // Validasi kategori
    $allowedKategori = ['kepala_desa', 'perangkat_desa', 'kepala_dusun'];
    if (!in_array($kategori, $allowedKategori)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Kategori tidak valid. Gunakan salah satu: " . implode(", ", $allowedKategori)
        ]);
        exit;
    }

    // Query insert
    $sql = "INSERT INTO perangkat_desa (
                nama, jabatan, kategori, nip, foto, email, telepon, alamat, facebook, twitter, instagram, urutan, is_active
            ) VALUES (
                :nama, :jabatan, :kategori, :nip, :foto, :email, :telepon, :alamat, :facebook, :twitter, :instagram, :urutan, :is_active
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nama'       => $nama,
        ':jabatan'    => $jabatan,
        ':kategori'   => $kategori,
        ':nip'        => $nip,
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
        "message" => "Data perangkat desa berhasil ditambahkan.",
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
