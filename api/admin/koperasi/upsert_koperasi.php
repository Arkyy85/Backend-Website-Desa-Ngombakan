<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../../config/config_database.php';

try {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Format JSON tidak valid."]);
        exit;
    }

    // Ambil field (biarkan null jika tidak dikirim)
    $nama_koperasi  = isset($input['nama_koperasi']) ? trim($input['nama_koperasi']) : null;
    $deskripsi      = array_key_exists('deskripsi', $input) ? $input['deskripsi'] : null;
    $visi           = array_key_exists('visi', $input) ? $input['visi'] : null;
    $misi           = array_key_exists('misi', $input) ? $input['misi'] : null;
    $alamat         = array_key_exists('alamat', $input) ? $input['alamat'] : null;
    $telepon        = array_key_exists('telepon', $input) ? $input['telepon'] : null;
    $email          = array_key_exists('email', $input) ? $input['email'] : null;
    $jam_operasional= array_key_exists('jam_operasional', $input) ? $input['jam_operasional'] : null;
    $layanan        = array_key_exists('layanan', $input) ? $input['layanan'] : null;

    // Minimal validation: nama_koperasi wajib
    if ($nama_koperasi === null || $nama_koperasi === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Field 'nama_koperasi' wajib diisi."]);
        exit;
    }

    // cek apakah row id=1 sudah ada
    $check = $pdo->prepare("SELECT id FROM koperasi WHERE id = 1 LIMIT 1");
    $check->execute();
    $exists = $check->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        // UPDATE
        $sql = "UPDATE koperasi SET
                    nama_koperasi = :nama_koperasi,
                    deskripsi = :deskripsi,
                    visi = :visi,
                    misi = :misi,
                    alamat = :alamat,
                    telepon = :telepon,
                    email = :email,
                    jam_operasional = :jam_operasional,
                    layanan = :layanan,
                    updated_at = NOW()
                WHERE id = 1
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nama_koperasi'   => $nama_koperasi,
            ':deskripsi'       => $deskripsi !== '' ? $deskripsi : null,
            ':visi'            => $visi !== '' ? $visi : null,
            ':misi'            => $misi !== '' ? $misi : null,
            ':alamat'          => $alamat !== '' ? $alamat : null,
            ':telepon'         => $telepon !== '' ? $telepon : null,
            ':email'           => $email !== '' ? $email : null,
            ':jam_operasional' => $jam_operasional !== '' ? $jam_operasional : null,
            ':layanan'         => $layanan !== '' ? $layanan : null
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Data koperasi berhasil diperbarui (update).',
            'id' => 1
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    } else {
        // INSERT (paksa id = 1)
        $sql = "INSERT INTO koperasi
                    (id, nama_koperasi, deskripsi, visi, misi, alamat, telepon, email, jam_operasional, layanan)
                VALUES
                    (1, :nama_koperasi, :deskripsi, :visi, :misi, :alamat, :telepon, :email, :jam_operasional, :layanan)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nama_koperasi'   => $nama_koperasi,
            ':deskripsi'       => $deskripsi !== '' ? $deskripsi : null,
            ':visi'            => $visi !== '' ? $visi : null,
            ':misi'            => $misi !== '' ? $misi : null,
            ':alamat'          => $alamat !== '' ? $alamat : null,
            ':telepon'         => $telepon !== '' ? $telepon : null,
            ':email'           => $email !== '' ? $email : null,
            ':jam_operasional' => $jam_operasional !== '' ? $jam_operasional : null,
            ':layanan'         => $layanan !== '' ? $layanan : null
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Data koperasi berhasil dibuat (insert).',
            'id' => 1
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
        'message' => 'Kesalahan server: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
