<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tangani preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../../config/config_database.php';

try {
    // baca JSON body
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = [];

    // wajib ada id untuk update
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Parameter "id" wajib untuk update.']);
        exit;
    }

    // daftar kolom yang boleh diupdate dan tipe sederhana untuk validasi
    $allowed = [
        'nama'      => 's',
        'jabatan'   => 's',
        'kategori'  => 's', // harus salah satu dari allowedKategori
        'nip'       => 's',
        'foto'      => 's',
        'email'     => 's',
        'telepon'   => 's',
        'alamat'    => 's',
        'facebook'  => 's',
        'twitter'   => 's',
        'instagram' => 's',
        'urutan'    => 'i',
        'is_active' => 'i'
    ];

    $allowedKategori = ['kepala_desa', 'perangkat_desa', 'kepala_dusun'];

    // cek apakah row ada
    $check = $pdo->prepare("SELECT id FROM perangkat_desa WHERE id = :id LIMIT 1");
    $check->execute([':id' => $id]);
    $exists = $check->fetch(PDO::FETCH_ASSOC);
    if (!$exists) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => "Data perangkat_desa dengan id={$id} tidak ditemukan."]);
        exit;
    }

    // bangun bagian SET dinamis
    $setParts = [];
    $params = [];

    foreach ($allowed as $col => $type) {
        if (array_key_exists($col, $input)) {
            // validasi kategori jika dikirim
            if ($col === 'kategori' && $input[$col] !== null) {
                if (!in_array($input[$col], $allowedKategori)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Kategori tidak valid. Gunakan salah satu: ' . implode(', ', $allowedKategori)]);
                    exit;
                }
            }

            // jika null => set kolom = NULL (tanpa placeholder)
            if ($input[$col] === null) {
                $setParts[] = "$col = NULL";
            } else {
                $setParts[] = "$col = :$col";
                // cast untuk integer fields
                if ($type === 'i') {
                    $params[":$col"] = (int)$input[$col];
                } elseif ($type === 'd') {
                    $params[":$col"] = (float)$input[$col];
                } else {
                    $params[":$col"] = $input[$col];
                }
            }
        }
    }

    if (empty($setParts)) {
        echo json_encode(['success' => false, 'message' => 'Tidak ada field yang dikirim untuk diupdate.']);
        exit;
    }

    // tambahkan updated_at
    $setParts[] = "updated_at = NOW()";

    // mulai transaction
    $pdo->beginTransaction();

    $sql = "UPDATE perangkat_desa SET " . implode(', ', $setParts) . " WHERE id = :id LIMIT 1";
    $params[':id'] = $id;

    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute($params);

    if (!$ok) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal mengeksekusi query update.']);
        exit;
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Data perangkat desa berhasil diupdate.',
        'id' => $id
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kesalahan database: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()]);
}
