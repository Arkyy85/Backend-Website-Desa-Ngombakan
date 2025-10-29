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
        echo json_encode(["success"=>false,"message"=>"Format JSON tidak valid."]);
        exit;
    }

    $nomor_peraturan   = trim($input['nomor_peraturan'] ?? '');
    $judul             = trim($input['judul'] ?? '');
    $deskripsi         = $input['deskripsi'] ?? null;
    $kategori          = trim($input['kategori'] ?? null);
    $tanggal_penetapan = $input['tanggal_penetapan'] ?? null; // YYYY-MM-DD or null
    $file_path         = trim($input['file_path'] ?? null);
    $file_size         = isset($input['file_size']) ? (int)$input['file_size'] : null;
    $is_active         = isset($input['is_active']) ? (int)$input['is_active'] : 1;

    // Validasi wajib
    if ($nomor_peraturan === '' || $judul === '') {
        http_response_code(400);
        echo json_encode(["success"=>false,"message"=>"Field 'nomor_peraturan' dan 'judul' wajib diisi."]);
        exit;
    }

    // Validasi tanggal jika dikirim
    if ($tanggal_penetapan !== null && $tanggal_penetapan !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $tanggal_penetapan);
        if (!($d && $d->format('Y-m-d') === $tanggal_penetapan)) {
            http_response_code(400);
            echo json_encode(["success"=>false,"message"=>"Format 'tanggal_penetapan' tidak valid. Gunakan YYYY-MM-DD."]);
            exit;
        }
    } else {
        $tanggal_penetapan = null;
    }

    $sql = "INSERT INTO peraturan_desa
            (nomor_peraturan, judul, deskripsi, kategori, tanggal_penetapan, file_path, file_size, is_active)
            VALUES
            (:nomor_peraturan, :judul, :deskripsi, :kategori, :tanggal_penetapan, :file_path, :file_size, :is_active)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nomor_peraturan'   => $nomor_peraturan,
        ':judul'             => $judul,
        ':deskripsi'         => $deskripsi !== '' ? $deskripsi : null,
        ':kategori'          => $kategori !== '' ? $kategori : null,
        ':tanggal_penetapan' => $tanggal_penetapan,
        ':file_path'         => $file_path !== '' ? $file_path : null,
        ':file_size'         => $file_size,
        ':is_active'         => $is_active
    ]);

    $id = $pdo->lastInsertId();

    echo json_encode(["success"=>true,"message"=>"Peraturan desa berhasil ditambahkan.","id"=>$id], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success"=>false,"message"=>"Kesalahan database: ".$e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success"=>false,"message"=>"Kesalahan server: ".$e->getMessage()]);
}
