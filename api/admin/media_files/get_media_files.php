<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../../config/config_database.php';

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id > 0) {
        $sql = "SELECT * FROM media_files WHERE id = :id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id'=>$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo json_encode(['success'=>true,'message'=>'Detail media file.','data'=>$row], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode(['success'=>false,'message'=>'File tidak ditemukan.']);
        }
        exit;
    }

    $kategori = isset($_GET['kategori']) ? trim($_GET['kategori']) : null;
    $uploaded_by = isset($_GET['uploaded_by']) ? (int)$_GET['uploaded_by'] : null;
    $q = isset($_GET['q']) ? trim($_GET['q']) : null;
    $page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1,min(200,(int)$_GET['limit'])) : 20;
    $offset = ($page - 1) * $limit;

    $where = []; $params = [];

    if ($kategori !== null && $kategori !== '') {
        $where[] = "kategori = :kategori";
        $params[':kategori'] = $kategori;
    }
    if ($uploaded_by !== null && $uploaded_by > 0) {
        $where[] = "uploaded_by = :uploaded_by";
        $params[':uploaded_by'] = $uploaded_by;
    }
    if ($q !== null && $q !== '') {
        $where[] = "file_name LIKE :q";
        $params[':q'] = '%'.$q.'%';
    }

    $sql = "SELECT id, file_name, file_path, file_type, file_size, kategori, uploaded_by, created_at
            FROM media_files";
    if (!empty($where)) $sql .= " WHERE ".implode(" AND ", $where);
    $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // total count
    $countSql = "SELECT COUNT(*) AS total FROM media_files";
    if (!empty($where)) $countSql .= " WHERE ".implode(" AND ", $where);
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $k=>$v) $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $tmp = $countStmt->fetch(PDO::FETCH_ASSOC);
    $total = $tmp ? (int)$tmp['total'] : count($rows);

    echo json_encode([
        'success'=>true,
        'message'=>'Daftar media files.',
        'page'=>$page,'limit'=>$limit,'total'=>$total,'data'=>$rows
    ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kesalahan database: '.$e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kesalahan server: '.$e->getMessage()]);
}
