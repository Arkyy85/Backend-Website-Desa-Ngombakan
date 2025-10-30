<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../../config/config_database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'Format JSON tidak valid.']);
        exit;
    }

    $id = isset($input['id']) ? (int)$input['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>"Parameter 'id' wajib untuk update."]);
        exit;
    }

    // cek exist
    $chk = $pdo->prepare("SELECT * FROM jadwal_pelayanan WHERE id = :id LIMIT 1");
    $chk->execute([':id'=>$id]);
    $orig = $chk->fetch(PDO::FETCH_ASSOC);
    if (!$orig) {
        http_response_code(404);
        echo json_encode(['success'=>false,'message'=>'Jadwal tidak ditemukan.']);
        exit;
    }

    $allowed = ['hari','jam_buka','jam_tutup','is_buka','keterangan'];
    $set = [];
    $params = [];

    $allowedHari = ['senin','selasa','rabu','kamis','jumat','sabtu','minggu'];

    foreach ($allowed as $col) {
        if (array_key_exists($col, $input)) {
            $val = $input[$col];
            if ($col === 'hari') {
                $val = strtolower(trim($val));
                if ($val === '' || !in_array($val, $allowedHari)) {
                    http_response_code(400);
                    echo json_encode(['success'=>false,'message'=>"Field 'hari' tidak valid. Gunakan salah satu: ".implode(', ',$allowedHari)]);
                    exit;
                }
                // if changing hari, ensure uniqueness (excluding current id)
                $check = $pdo->prepare("SELECT id FROM jadwal_pelayanan WHERE hari = :hari AND id != :id LIMIT 1");
                $check->execute([':hari'=>$val, ':id'=>$id]);
                if ($check->fetch(PDO::FETCH_ASSOC)) {
                    http_response_code(409);
                    echo json_encode(['success'=>false,'message'=>"Jadwal untuk hari '{$val}' sudah ada."]);
                    exit;
                }
                $set[] = "hari = :hari";
                $params[':hari'] = $val;
            } elseif ($col === 'jam_buka' || $col === 'jam_tutup') {
                if ($val === null || $val === '') {
                    $set[] = "$col = NULL";
                } else {
                    // validate time
                    $d = \DateTime::createFromFormat('H:i:s', $val) ?: \DateTime::createFromFormat('H:i', $val);
                    if (!$d) {
                        http_response_code(400);
                        echo json_encode(['success'=>false,'message'=>"Format '$col' tidak valid. Gunakan HH:MM atau HH:MM:SS."]);
                        exit;
                    }
                    // normalize
                    $time = $d->format('H:i:s');
                    $set[] = "$col = :$col";
                    $params[":$col"] = $time;
                }
            } elseif ($col === 'is_buka') {
                $set[] = "is_buka = :is_buka";
                $params[':is_buka'] = (int)$val;
            } else {
                if ($val === null || $val === '') {
                    $set[] = "$col = NULL";
                } else {
                    $set[] = "$col = :$col";
                    $params[":$col"] = $val;
                }
            }
        }
    }

    if (empty($set)) {
        echo json_encode(['success'=>false,'message'=>'Tidak ada field untuk diupdate.']);
        exit;
    }

    $set[] = "updated_at = NOW()";
    $sql = "UPDATE jadwal_pelayanan SET ".implode(", ", $set)." WHERE id = :id LIMIT 1";
    $params[':id'] = $id;

    $pdo->beginTransaction();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pdo->commit();

    echo json_encode(['success'=>true,'message'=>'Jadwal berhasil diupdate.','id'=>$id], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kesalahan database: '.$e->getMessage()]);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kesalahan server: '.$e->getMessage()]);
}
