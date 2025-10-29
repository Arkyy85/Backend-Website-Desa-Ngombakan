<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Tangani preflight (CORS)
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
    $judul           = trim($input['judul'] ?? '');
    $deskripsi       = $input['deskripsi'] ?? null;
    $tanggal_mulai   = $input['tanggal_mulai'] ?? '';
    $tanggal_selesai = $input['tanggal_selesai'] ?? null;
    $waktu_mulai     = $input['waktu_mulai'] ?? null;
    $waktu_selesai   = $input['waktu_selesai'] ?? null;
    $lokasi          = $input['lokasi'] ?? null;
    $kategori        = $input['kategori'] ?? null;
    $penyelenggara   = $input['penyelenggara'] ?? null;
    $is_active       = isset($input['is_active']) ? (int)$input['is_active'] : 1;

    // Validasi wajib
    if (empty($judul) || empty($tanggal_mulai)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Field 'judul' dan 'tanggal_mulai' wajib diisi."
        ]);
        exit;
    }

    // Validasi format tanggal (YYYY-MM-DD)
    $d = DateTime::createFromFormat('Y-m-d', $tanggal_mulai);
    if (!($d && $d->format('Y-m-d') === $tanggal_mulai)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Format 'tanggal_mulai' tidak valid. Gunakan format YYYY-MM-DD."
        ]);
        exit;
    }

    if (!empty($tanggal_selesai)) {
        $d2 = DateTime::createFromFormat('Y-m-d', $tanggal_selesai);
        if (!($d2 && $d2->format('Y-m-d') === $tanggal_selesai)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Format 'tanggal_selesai' tidak valid. Gunakan format YYYY-MM-DD atau kosongkan."
            ]);
            exit;
        }
    }

    // Validasi waktu (HH:MM:SS) jika diberikan
    if (!empty($waktu_mulai)) {
        $t = DateTime::createFromFormat('H:i:s', $waktu_mulai);
        if (!($t && $t->format('H:i:s') === $waktu_mulai)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Format 'waktu_mulai' tidak valid. Gunakan format HH:MM:SS atau kosongkan."
            ]);
            exit;
        }
    }
    if (!empty($waktu_selesai)) {
        $t2 = DateTime::createFromFormat('H:i:s', $waktu_selesai);
        if (!($t2 && $t2->format('H:i:s') === $waktu_selesai)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Format 'waktu_selesai' tidak valid. Gunakan format HH:MM:SS atau kosongkan."
            ]);
            exit;
        }
    }

    // Insert ke DB menggunakan PDO
    $sql = "INSERT INTO agenda (
                judul, deskripsi, tanggal_mulai, tanggal_selesai,
                waktu_mulai, waktu_selesai, lokasi, kategori, penyelenggara, is_active
            ) VALUES (
                :judul, :deskripsi, :tanggal_mulai, :tanggal_selesai,
                :waktu_mulai, :waktu_selesai, :lokasi, :kategori, :penyelenggara, :is_active
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':judul'           => $judul,
        ':deskripsi'       => $deskripsi,
        ':tanggal_mulai'   => $tanggal_mulai,
        ':tanggal_selesai' => $tanggal_selesai,
        ':waktu_mulai'     => $waktu_mulai,
        ':waktu_selesai'   => $waktu_selesai,
        ':lokasi'          => $lokasi,
        ':kategori'        => $kategori,
        ':penyelenggara'   => $penyelenggara,
        ':is_active'       => $is_active
    ]);

    $insertId = $pdo->lastInsertId();

    echo json_encode([
        "success" => true,
        "message" => "Agenda berhasil ditambahkan.",
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
