<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../../config/config_database.php';

try {
    // support ?id= or ?hari=, otherwise list all ordered by unique_hari order (we'll order by FIELD)
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $hari = isset($_GET['hari']) ? strtolower(trim($_GET['hari'])) : null;

    if ($id > 0) {
        $sql = "SELECT * FROM jadwal_pelayanan WHERE id = :id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id'=>$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo json_encode(['success'=>true,'message'=>'Detail jadwal.','data'=>$row], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode(['success'=>false,'message'=>'Jadwal tidak ditemukan.','data'=>null]);
        }
        exit;
    }

    if ($hari) {
        $sql = "SELECT * FROM jadwal_pelayanan WHERE hari = :hari LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':hari'=>$hari]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo json_encode(['success'=>true,'message'=>'Detail jadwal.','data'=>$row], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode(['success'=>false,'message'=>'Jadwal untuk hari tidak ditemukan.','data'=>null]);
        }
        exit;
    }

    // list all in weekday order
    $order = "FIELD(hari, 'senin','selasa','rabu','kamis','jumat','sabtu','minggu')";
    $sql = "SELECT * FROM jadwal_pelayanan ORDER BY $order, id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success'=>true,'message'=>'Daftar jadwal pelayanan.','total'=>count($rows),'data'=>$rows], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kesalahan database: '.$e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kesalahan server: '.$e->getMessage()]);
}
