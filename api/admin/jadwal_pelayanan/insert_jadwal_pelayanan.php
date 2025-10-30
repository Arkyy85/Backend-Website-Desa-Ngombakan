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

    $hari = isset($input['hari']) ? strtolower(trim($input['hari'])) : '';
    $jam_buka = isset($input['jam_buka']) && $input['jam_buka'] !== '' ? trim($input['jam_buka']) : null;
    $jam_tutup = isset($input['jam_tutup']) && $input['jam_tutup'] !== '' ? trim($input['jam_tutup']) : null;
    $is_buka = isset($input['is_buka']) ? (int)$input['is_buka'] : 1;
    $keterangan = isset($input['keterangan']) ? trim($input['keterangan']) : null;

    $allowed = ['senin','selasa','rabu','kamis','jumat','sabtu','minggu'];
    if ($hari === '' || !in_array($hari, $allowed)) {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>"Field 'hari' wajib dan harus salah satu: ".implode(', ', $allowed)]);
        exit;
    }

    // validate time format HH:MM:SS or HH:MM
    $validateTime = function($t){
        if ($t === null) return true;
        $d = \DateTime::createFromFormat('H:i:s', $t);
        if ($d && $d->format('H:i:s') === $t) return true;
        $d2 = \DateTime::createFromFormat('H:i', $t);
        return ($d2 && $d2->format('H:i') === $t);
    };

    if (!$validateTime($jam_buka) || !$validateTime($jam_tutup)) {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>"Format 'jam_buka' atau 'jam_tutup' tidak valid. Gunakan HH:MM atau HH:MM:SS."]);
        exit;
    }

    // Normalize times to HH:MM:SS if provided as HH:MM
    $normalizeTime = function($t){
        if ($t === null) return null;
        if (preg_match('/^\d{2}:\d{2}$/', $t)) return $t . ':00';
        return $t;
    };

    $jam_buka = $normalizeTime($jam_buka);
    $jam_tutup = $normalizeTime($jam_tutup);

    // check uniqueness hari
    $check = $pdo->prepare("SELECT id FROM jadwal_pelayanan WHERE hari = :hari LIMIT 1");
    $check->execute([':hari'=>$hari]);
    if ($check->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(409);
        echo json_encode(['success'=>false,'message'=>"Jadwal untuk hari '{$hari}' sudah ada."]);
        exit;
    }

    $sql = "INSERT INTO jadwal_pelayanan (hari, jam_buka, jam_tutup, is_buka, keterangan)
            VALUES (:hari, :jam_buka, :jam_tutup, :is_buka, :keterangan)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':hari' => $hari,
        ':jam_buka' => $jam_buka,
        ':jam_tutup' => $jam_tutup,
        ':is_buka' => $is_buka,
        ':keterangan' => $keterangan !== '' ? $keterangan : null
    ]);

    $id = $pdo->lastInsertId();
    echo json_encode(['success'=>true,'message'=>'Jadwal berhasil ditambahkan.','id'=>$id], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kesalahan database: '.$e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kesalahan server: '.$e->getMessage()]);
}
