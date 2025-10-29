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

// Pastikan koneksi pdo ada
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection not found."]);
    exit;
}

// ambil input JSON
$input = json_decode(file_get_contents("php://input"), true);
if (!is_array($input)) $input = [];

// daftar kolom yang valid (urut sesuai definisi schema)
$fields = [
    'site_name',
    'site_tagline',
    'site_description',
    'site_logo',
    'site_favicon',
    'contact_email',
    'contact_phone',
    'contact_address',
    'maps_embed_url',
    'facebook_url',
    'twitter_url',
    'instagram_url',
    'youtube_url'
];

try {
    // mulai transaction untuk safety
    $pdo->beginTransaction();

    // cek apakah ada row di table site_settings (ambil id terbesar jika ada)
    $res = $pdo->query("SELECT id FROM site_settings ORDER BY id DESC LIMIT 1");
    $row = $res->fetch(PDO::FETCH_ASSOC);
    $exists = ($row !== false);
    $targetId = null;
    if ($exists) {
        $targetId = (int)$row['id'];
    }

    // jika ada id eksplisit dikirim, prioritaskan itu (cek keberadaan)
    if (isset($input['id']) && is_numeric($input['id'])) {
        $givenId = (int)$input['id'];
        $chk = $pdo->prepare("SELECT id FROM site_settings WHERE id = :id LIMIT 1");
        $chk->execute([':id' => $givenId]);
        $r = $chk->fetch(PDO::FETCH_ASSOC);
        if ($r !== false) {
            $targetId = $givenId;
            $exists = true;
        } else {
            // id diberikan tapi tidak ada -> akan insert (ignor id)
            $exists = false;
            $targetId = null;
        }
    }

    if ($exists && $targetId !== null) {
        // Build dynamic UPDATE: hanya kolom yang ada di input akan diupdate.
        $updateParts = [];
        $params = [];

        foreach ($fields as $f) {
            if (array_key_exists($f, $input)) {
                if ($input[$f] === null) {
                    // set column to SQL NULL
                    $updateParts[] = "$f = NULL";
                } else {
                    $paramName = ":$f";
                    $updateParts[] = "$f = $paramName";
                    $params[$paramName] = $input[$f];
                }
            }
        }

        if (empty($updateParts)) {
            // tidak ada field untuk diupdate
            $pdo->commit();
            echo json_encode(["status" => "success", "message" => "Tidak ada perubahan. Tidak ada field yang dikirim untuk update.", "id" => $targetId]);
            exit;
        }

        // tambahkan updated_at
        $updateParts[] = "updated_at = NOW()";

        $sql = "UPDATE site_settings SET " . implode(", ", $updateParts) . " WHERE id = :id LIMIT 1";
        $params[':id'] = $targetId;

        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute($params);
        if ($ok) {
            $pdo->commit();
            echo json_encode(["status" => "success", "message" => "Site settings berhasil diupdate.", "id" => $targetId]);
            exit;
        } else {
            throw new Exception("Execute failed while updating site_settings.");
        }
    } else {
        // lakukan INSERT (gunakan semua kolom — jika tidak dikirim, gunakan default atau NULL)
        // siapkan values array sesuai urutan $fields
        $insertCols = [];
        $insertPlaceholders = [];
        $insertParams = [];

        foreach ($fields as $f) {
            $insertCols[] = $f;
            if (array_key_exists($f, $input)) {
                if ($input[$f] === null) {
                    // gunakan NULL literal — untuk mempermudah, kita tetap buat placeholder dan bindValue(null, PDO::PARAM_NULL)
                    $ph = ":$f";
                    $insertPlaceholders[] = $ph;
                    $insertParams[$ph] = null;
                } else {
                    $ph = ":$f";
                    $insertPlaceholders[] = $ph;
                    $insertParams[$ph] = $input[$f];
                }
            } else {
                // belum dikirim => gunakan default schema untuk site_name, atau NULL
                $ph = ":$f";
                $insertPlaceholders[] = $ph;
                if ($f === 'site_name') {
                    $insertParams[$ph] = ($input['site_name'] ?? 'Desa Ngombakan');
                } else {
                    $insertParams[$ph] = null;
                }
            }
        }

        $sql = "INSERT INTO site_settings (" . implode(', ', $insertCols) . ") VALUES (" . implode(', ', $insertPlaceholders) . ")";
        $stmt = $pdo->prepare($sql);

        // Bind params explicitly to handle NULLs properly
        foreach ($insertParams as $ph => $val) {
            if ($val === null) {
                $stmt->bindValue($ph, null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue($ph, $val, PDO::PARAM_STR);
            }
        }

        $executed = $stmt->execute();
        if (!$executed) {
            throw new Exception("Execute failed while inserting site_settings.");
        }

        $newId = (int)$pdo->lastInsertId();
        $pdo->commit();
        echo json_encode(["status" => "success", "message" => "Site settings berhasil dibuat.", "id" => $newId]);
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
