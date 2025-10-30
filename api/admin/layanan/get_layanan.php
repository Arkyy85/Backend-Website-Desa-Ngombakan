<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
require_once __DIR__ . '/../../../config/config_database.php';

try {
    // detail by id or slug
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $slug = isset($_GET['slug']) ? trim($_GET['slug']) : null;
    if ($id > 0 || ($slug !== null && $slug !== '')) {
        if ($id > 0) {
            $sql = "SELECT * FROM layanan WHERE id = :id LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id'=>$id]);
        } else {
            $sql = "SELECT * FROM layanan WHERE slug = :slug LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':slug'=>$slug]);
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            // decode syarat/prosedur if not null
            $row['syarat'] = $row['syarat'] ? json_decode($row['syarat'], true) : null;
            $row['prosedur'] = $row['prosedur'] ? json_decode($row['prosedur'], true) : null;
            echo json_encode(['success'=>true,'message'=>'Detail layanan.','data'=>$row], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode(['success'=>false,'message'=>'Layanan tidak ditemukan.','data'=>null]);
        }
        exit;
    }

    // list with filters & pagination
    $q = isset($_GET['q']) ? trim($_GET['q']) : null; // search nama/deskripsi
    $is_active = isset($_GET['is_active']) ? trim($_GET['is_active']) : null;
    $page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1,min(200,(int)$_GET['limit'])) : 20;
    $offset = ($page -1) * $limit;

    $where = []; $params = [];

    if ($q !== null && $q !== '') {
        $where[] = "(nama_layanan LIKE :q OR deskripsi LIKE :q)";
        $params[':q'] = '%'.$q.'%';
    }
    if ($is_active !== null && $is_active !== '') {
        $where[] = "is_active = :is_active";
        $params[':is_active'] = (int)$is_active;
    }

    $sql = "SELECT id, nama_layanan, slug, deskripsi, icon, waktu_proses, biaya, syarat, prosedur, catatan, urutan, is_active, created_at, updated_at
            FROM layanan";
    if (!empty($where)) $sql .= " WHERE ".implode(" AND ", $where);
    $sql .= " ORDER BY urutan ASC, id ASC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // decode JSON fields
    foreach ($rows as &$r) {
        $r['syarat'] = $r['syarat'] ? json_decode($r['syarat'], true) : null;
        $r['prosedur'] = $r['prosedur'] ? json_decode($r['prosedur'], true) : null;
    }

    // total count
    $countSql = "SELECT COUNT(*) AS total FROM layanan";
    if (!empty($where)) $countSql .= " WHERE ".implode(" AND ", $where);
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $k=>$v) $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $tmp = $countStmt->fetch(PDO::FETCH_ASSOC);
    $total = $tmp ? (int)$tmp['total'] : count($rows);

    echo json_encode([
        'success'=>true,
        'message'=>'Daftar layanan.',
        'page'=>$page,'limit'=>$limit,'total'=>$total,
        'data'=>$rows
    ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kesalahan database: '.$e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kesalahan server: '.$e->getMessage()]);
}
