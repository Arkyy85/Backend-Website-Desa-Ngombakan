<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../../config/config_database.php';

try {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Format JSON tidak valid."]);
        exit;
    }

    $nama_pelapor = trim($input['nama_pelapor'] ?? '');
    $email        = trim($input['email'] ?? '');
    $telepon      = trim($input['telepon'] ?? '');
    $kategori     = trim($input['kategori'] ?? '');
    $judul        = trim($input['judul'] ?? '');
    $deskripsi    = trim($input['deskripsi'] ?? '');
    $lokasi       = trim($input['lokasi'] ?? null);
    $lampiran     = trim($input['lampiran'] ?? null); // path atau filename yang dihasilkan uploader
    $status       = $input['status'] ?? 'pending';

    // Validasi wajib
    if ($nama_pelapor === '' || $kategori === '' || $judul === '' || $deskripsi === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Field 'nama_pelapor', 'kategori', 'judul' dan 'deskripsi' wajib diisi."]);
        exit;
    }

    // validasi status jika dikirim
    $allowedStatus = ['pending','diproses','selesai','ditolak'];
    if (!in_array($status, $allowedStatus)) $status = 'pending';

    $sql = "INSERT INTO aduan
            (nama_pelapor, email, telepon, kategori, judul, deskripsi, lokasi, lampiran, status)
            VALUES
            (:nama_pelapor, :email, :telepon, :kategori, :judul, :deskripsi, :lokasi, :lampiran, :status)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nama_pelapor' => $nama_pelapor,
        ':email'        => $email !== '' ? $email : null,
        ':telepon'      => $telepon !== '' ? $telepon : null,
        ':kategori'     => $kategori,
        ':judul'        => $judul,
        ':deskripsi'    => $deskripsi,
        ':lokasi'       => $lokasi !== '' ? $lokasi : null,
        ':lampiran'     => $lampiran !== '' ? $lampiran : null,
        ':status'       => $status
    ]);

    $id = $pdo->lastInsertId();

    echo json_encode([
        "success" => true,
        "message" => "Aduan berhasil dikirim.",
        "id" => $id
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Kesalahan database: " . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Kesalahan server: " . $e->getMessage()]);
}
