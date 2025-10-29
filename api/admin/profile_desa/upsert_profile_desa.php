<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// tangani preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../../config/config_database.php';

// Pastikan koneksi PDO ada
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection not found."]);
    exit;
}

// ambil input JSON
$input = json_decode(file_get_contents("php://input"), true);
if (!is_array($input)) $input = [];

// definisi field dan tipe (untuk casting)
// tipe hanya untuk memastikan nilai dikonversi ke int/float jika perlu saat bind
$fields = [
    'nama_desa'       => 'string', // NOT NULL
    'kode_desa'       => 'string',
    'kecamatan'       => 'string',
    'kabupaten'       => 'string',
    'provinsi'        => 'string',
    'kode_pos'        => 'string',
    'sejarah'         => 'string',
    'visi'            => 'string',
    'misi'            => 'string',
    'luas_wilayah'    => 'float', // decimal
    'batas_utara'     => 'string',
    'batas_selatan'   => 'string',
    'batas_timur'     => 'string',
    'batas_barat'     => 'string',
    'jumlah_penduduk' => 'int',
    'jumlah_kk'       => 'int',
    'jumlah_laki'     => 'int',
    'jumlah_perempuan'=> 'int'
];

// karena tabel enforce single row with id=1, kita target id 1
$targetId = 1;

try {
    $pdo->beginTransaction();

    // cek ada row id=1 ?
    $chkStmt = $pdo->prepare("SELECT id FROM profil_desa WHERE id = :id LIMIT 1");
    $chkStmt->execute([':id' => $targetId]);
    $existsRow = $chkStmt->fetch(PDO::FETCH_ASSOC);
    $exists = ($existsRow !== false);

    if ($exists) {
        // BUILD UPDATE: hanya kolom yang ada di input akan diupdate
        $updateParts = [];
        $params = [];

        foreach ($fields as $name => $type) {
            if (array_key_exists($name, $input)) {
                if ($input[$name] === null) {
                    // set column to SQL NULL
                    $updateParts[] = "{$name} = NULL";
                } else {
                    $paramName = ":" . $name;
                    $updateParts[] = "{$name} = {$paramName}";
                    // cast value according to type
                    if ($type === 'int') {
                        $params[$paramName] = (int)$input[$name];
                    } elseif ($type === 'float') {
                        $params[$paramName] = (float)$input[$name];
                    } else {
                        $params[$paramName] = (string)$input[$name];
                    }
                }
            }
        }

        if (empty($updateParts)) {
            // tidak ada perubahan
            $pdo->commit();
            echo json_encode(["status" => "success", "message" => "Tidak ada perubahan. Tidak ada field yang dikirim untuk update.", "id" => $targetId]);
            exit;
        }

        // tambahkan updated_at
        $updateParts[] = "updated_at = NOW()";

        $sql = "UPDATE profil_desa SET " . implode(", ", $updateParts) . " WHERE id = :id LIMIT 1";
        $params[':id'] = $targetId;

        $stmt = $pdo->prepare($sql);
        // bind params (PDO->execute with assoc array handles types reasonably; NULLs already handled by SQL literal)
        $ok = $stmt->execute($params);

        if ($ok) {
            $pdo->commit();
            echo json_encode(["status" => "success", "message" => "Profil desa berhasil diupdate.", "id" => $targetId]);
            exit;
        } else {
            throw new Exception("Execute failed while updating profil_desa.");
        }
    } else {
        // INSERT: semua kolom harus diisi (kecuali yang dikirim null)
        // nama_desa wajib
        if (!array_key_exists('nama_desa', $input) || $input['nama_desa'] === null || trim((string)$input['nama_desa']) === '') {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Field 'nama_desa' wajib diisi untuk pembuatan profil desa."]);
            exit;
        }

        $insertCols = ['id']; // kita set id = 1
        $placeholders = [':id'];
        $insertParams = [':id' => $targetId];

        foreach ($fields as $name => $type) {
            $insertCols[] = $name;
            $ph = ':' . $name;
            $placeholders[] = $ph;

            if (array_key_exists($name, $input)) {
                if ($input[$name] === null) {
                    $insertParams[$ph] = null;
                } else {
                    if ($type === 'int') {
                        $insertParams[$ph] = (int)$input[$name];
                    } elseif ($type === 'float') {
                        $insertParams[$ph] = (float)$input[$name];
                    } else {
                        $insertParams[$ph] = (string)$input[$name];
                    }
                }
            } else {
                // not provided -> NULL
                $insertParams[$ph] = null;
            }
        }

        $sql = "INSERT INTO profil_desa (" . implode(", ", $insertCols) . ") VALUES (" . implode(", ", $placeholders) . ")";
        $stmt = $pdo->prepare($sql);

        // bind explicitly to handle NULL types (optional but clearer)
        foreach ($insertParams as $ph => $val) {
            if ($val === null) {
                $stmt->bindValue($ph, null, PDO::PARAM_NULL);
            } elseif (is_int($val)) {
                $stmt->bindValue($ph, $val, PDO::PARAM_INT);
            } else {
                // treat floats & strings as string; MySQL will cast float as needed
                $stmt->bindValue($ph, $val, PDO::PARAM_STR);
            }
        }

        $executed = $stmt->execute();
        if (!$executed) {
            throw new Exception("Execute failed while inserting profil_desa.");
        }

        $pdo->commit();
        echo json_encode(["status" => "success", "message" => "Profil desa berhasil dibuat.", "id" => $targetId]);
        exit;
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    exit;
}
