<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
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

    // Ambil ID yang akan diupdate
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "ID lembaga tidak valid."]);
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

    // Cek apakah ID lembaga ada
    $checkExist = $pdo->prepare("SELECT id FROM lembaga WHERE id = :id LIMIT 1");
    $checkExist->execute([':id' => $id]);
    if (!$checkExist->fetch()) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Data lembaga dengan ID tersebut tidak ditemukan."
        ]);
        exit;
    }

    // Cek apakah slug digunakan oleh lembaga lain
    $checkSlug = $pdo->prepare("SELECT id FROM lembaga WHERE slug = :slug AND id != :id LIMIT 1");
    $checkSlug->execute([':slug' => $slug, ':id' => $id]);
    if ($checkSlug->fetch()) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "message" => "Slug sudah digunakan oleh lembaga lain. Gunakan slug yang berbeda."
        ]);
        exit;
    }

    // Query update
    $sql = "UPDATE lembaga SET
                nama_lembaga = :nama_lembaga,
                slug = :slug,
                tipe = :tipe,
                deskripsi = :deskripsi,
                jumlah_anggota = :jumlah_anggota,
                alamat_sekretariat = :alamat_sekretariat,
                telepon = :telepon,
                email = :email,
                program_kegiatan = :program_kegiatan,
                is_active = :is_active,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id";

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
        ':is_active'          => $is_active,
        ':id'                 => $id
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "success" => true,
            "message" => "Data lembaga berhasil diperbarui.",
            "id" => $id
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            "success" => true,
            "message" => "Tidak ada perubahan pada data lembaga.",
            "id" => $id
        ]);
    }

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
