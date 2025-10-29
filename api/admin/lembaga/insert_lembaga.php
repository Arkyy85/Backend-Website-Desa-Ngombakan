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
    $nama_lembaga       = trim($input['nama_lembaga'] ?? '');
    $slug               = trim($input['slug'] ?? '');
    $tipe               = trim($input['tipe'] ?? '');
    $deskripsi          = $input['deskripsi'] ?? null;
    $jumlah_anggota     = isset($input['jumlah_anggota']) ? (int)$input['jumlah_anggota'] : 0;
    $alamat_sekretariat = $input['alamat_sekretariat'] ?? null;
    $telepon            = $input['telepon'] ?? null;
    $email              = $input['email'] ?? null;
    $program_kegiatan   = $input['program_kegiatan'] ?? null;
    $is_active          = isset($input['is_active']) ? (int)$input['is_active'] : 1;

    // Validasi wajib isi
    if (empty($nama_lembaga) || empty($slug) || empty($tipe)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Field 'nama_lembaga', 'slug', dan 'tipe' wajib diisi."
        ]);
        exit;
    }

    // Validasi tipe lembaga
    $allowedTipe = ['rt', 'rw', 'bpd', 'pkk', 'karang_taruna', 'posyandu', 'lainnya'];
    if (!in_array($tipe, $allowedTipe)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Tipe tidak valid. Gunakan salah satu: " . implode(", ", $allowedTipe)
        ]);
        exit;
    }

    // Cek slug agar unik
    $checkSlug = $pdo->prepare("SELECT id FROM lembaga WHERE slug = :slug LIMIT 1");
    $checkSlug->execute([':slug' => $slug]);
    if ($checkSlug->fetch()) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "message" => "Slug sudah digunakan, silakan gunakan slug lain."
        ]);
        exit;
    }

    // Query insert
    $sql = "INSERT INTO lembaga (
                nama_lembaga, slug, tipe, deskripsi, jumlah_anggota,
                alamat_sekretariat, telepon, email, program_kegiatan, is_active
            ) VALUES (
                :nama_lembaga, :slug, :tipe, :deskripsi, :jumlah_anggota,
                :alamat_sekretariat, :telepon, :email, :program_kegiatan, :is_active
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nama_lembaga'       => $nama_lembaga,
        ':slug'               => $slug,
        ':tipe'               => $tipe,
        ':deskripsi'          => $deskripsi,
        ':jumlah_anggota'     => $jumlah_anggota,
        ':alamat_sekretariat' => $alamat_sekretariat,
        ':telepon'            => $telepon,
        ':email'              => $email,
        ':program_kegiatan'   => $program_kegiatan,
        ':is_active'          => $is_active
    ]);

    // Ambil ID terakhir
    $insertId = $pdo->lastInsertId();

    echo json_encode([
        "success" => true,
        "message" => "Data lembaga berhasil ditambahkan.",
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
