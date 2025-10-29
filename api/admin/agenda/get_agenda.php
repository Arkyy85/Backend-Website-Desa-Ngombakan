<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../../config/config_database.php';

try {
    // jika id diberikan -> detail
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id > 0) {
        $sql = "SELECT id, judul, deskripsi, tanggal_mulai, tanggal_selesai, waktu_mulai, waktu_selesai,
                       lokasi, kategori, penyelenggara, is_active, created_at, updated_at
                FROM agenda
                WHERE id = :id
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            echo json_encode([
                'success' => true,
                'message' => 'Detail agenda ditemukan.',
                'data' => $row
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Agenda tidak ditemukan.',
                'data' => null
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // list dengan filter & pagination
    $kategori  = isset($_GET['kategori']) ? trim($_GET['kategori']) : null;
    $date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : null;
    $date_to   = isset($_GET['date_to']) ? trim($_GET['date_to']) : null;
    $page      = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit     = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
    $offset    = ($page - 1) * $limit;

    $where = [];
    $params = [];

    if ($kategori !== null && $kategori !== '') {
        $where[] = "kategori = :kategori";
        $params[':kategori'] = $kategori;
    }
    if ($date_from !== null && $date_from !== '') {
        $where[] = "tanggal_mulai >= :date_from";
        $params[':date_from'] = $date_from;
    }
    if ($date_to !== null && $date_to !== '') {
        $where[] = "tanggal_selesai <= :date_to";
        $params[':date_to'] = $date_to;
    }

    // Query utama (data)
    $sql = "SELECT id, judul, deskripsi, tanggal_mulai, tanggal_selesai, waktu_mulai, waktu_selesai,
                   lokasi, kategori, penyelenggara, is_active, created_at, updated_at
            FROM agenda";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY tanggal_mulai DESC, waktu_mulai ASC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    // bind filter params
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    // bind limit & offset as integers
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Query total dengan COUNT(*) (menggunakan same WHERE)
    $countSql = "SELECT COUNT(*) AS total FROM agenda";
    if (!empty($where)) {
        $countSql .= " WHERE " . implode(" AND ", $where);
    }
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v) {
        $countStmt->bindValue($k, $v);
    }
    $countStmt->execute();
    $totalRow = $countStmt->fetch(PDO::FETCH_ASSOC);
    $total = $totalRow ? (int)$totalRow['total'] : count($data);

    echo json_encode([
        'success' => true,
        'message' => 'Daftar agenda.',
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

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
