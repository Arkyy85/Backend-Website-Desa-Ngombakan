<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../../config/config_database.php';

try {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id > 0) {
        $sql = "SELECT * FROM aduan WHERE id = :id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo json_encode(['success'=>true,'message'=>'Detail aduan.','data'=>$row], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode(['success'=>false,'message'=>'Aduan tidak ditemukan.','data'=>null]);
        }
        exit;
    }

    // list
    $kategori = isset($_GET['kategori']) ? trim($_GET['kategori']) : null;
    $status   = isset($_GET['status']) ? trim($_GET['status']) : null;
    $from     = isset($_GET['from']) ? trim($_GET['from']) : null; // format YYYY-MM-DD
    $to       = isset($_GET['to']) ? trim($_GET['to']) : null;
    $page     = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
    $limit    = isset($_GET['limit']) ? max(1,min(100,(int)$_GET['limit'])) : 10;
    $offset   = ($page-1)*$limit;

    $where = [];
    $params = [];

    if ($kategori !== null && $kategori !== '') {
        $where[] = "kategori = :kategori";
        $params[':kategori'] = $kategori;
    }
    if ($status !== null && $status !== '') {
        $where[] = "status = :status";
        $params[':status'] = $status;
    }
    if ($from !== null && $from !== '') {
        $where[] = "DATE(created_at) >= :from";
        $params[':from'] = $from;
    }
    if ($to !== null && $to !== '') {
        $where[] = "DATE(created_at) <= :to";
        $params[':to'] = $to;
    }

    $sql = "SELECT id, nama_pelapor, email, telepon, kategori, judul, deskripsi, lokasi, lampiran, status, tanggapan, tanggal_tanggapan, created_at, updated_at
            FROM aduan";
    if (!empty($where)) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // total count
    $countSql = "SELECT COUNT(*) AS total FROM aduan";
    if (!empty($where)) $countSql .= " WHERE " . implode(" AND ", $where);
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $k=>$v) $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $tmp = $countStmt->fetch(PDO::FETCH_ASSOC);
    $total = $tmp ? (int)$tmp['total'] : count($data);

    echo json_encode([
        'success'=>true,
        'message'=>'Daftar aduan.',
        'page'=>$page,'limit'=>$limit,'total'=>$total,
        'data'=>$data
    ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kesalahan database: '.$e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kesalahan server: '.$e->getMessage()]);
}
