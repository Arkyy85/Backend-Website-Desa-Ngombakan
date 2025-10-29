<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Tangani preflight CORS (OPTIONS)
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
    $judul         = trim($input['judul'] ?? '');
    $slug          = trim($input['slug'] ?? '');
    $kategori      = trim($input['kategori'] ?? '');
    $ringkasan     = trim($input['ringkasan'] ?? '');
    $konten        = trim($input['konten'] ?? '');
    $gambar_utama  = trim($input['gambar_utama'] ?? '');
    $penulis       = trim($input['penulis'] ?? '');
    $views         = isset($input['views']) ? (int)$input['views'] : 0;
    $is_published  = isset($input['is_published']) ? (int)$input['is_published'] : 1;
    $published_at  = $input['published_at'] ?? null;

    // Validasi wajib isi
    if (empty($judul) || empty($slug) || empty($konten)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Field 'judul', 'slug', dan 'konten' wajib diisi."
        ]);
        exit;
    }

    // Cek apakah slug sudah digunakan
    $checkSlug = $pdo->prepare("SELECT id FROM berita WHERE slug = :slug LIMIT 1");
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
    $sql = "INSERT INTO berita (
                judul, slug, kategori, ringkasan, konten, 
                gambar_utama, penulis, views, is_published, published_at
            ) VALUES (
                :judul, :slug, :kategori, :ringkasan, :konten,
                :gambar_utama, :penulis, :views, :is_published, :published_at
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':judul'        => $judul,
        ':slug'         => $slug,
        ':kategori'     => $kategori ?: null,
        ':ringkasan'    => $ringkasan ?: null,
        ':konten'       => $konten,
        ':gambar_utama' => $gambar_utama ?: null,
        ':penulis'      => $penulis ?: null,
        ':views'        => $views,
        ':is_published' => $is_published,
        ':published_at' => $published_at
    ]);

    // Ambil ID terakhir
    $insertId = $pdo->lastInsertId();

    echo json_encode([
        "success" => true,
        "message" => "Berita berhasil ditambahkan.",
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
