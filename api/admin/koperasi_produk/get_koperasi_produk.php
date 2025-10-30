<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../../config/config_database.php';

try {
    // detail jika id diberikan
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id > 0) {
        $sql = "SELECT * FROM koperasi_produk WHERE id = :id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo json_encode(['success' => true, 'message' => 'Detail produk.', 'data' => $row], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan.', 'data' => null]);
        }
        exit;
    }

    // list
    $kategori = isset($_GET['kategori']) ? trim($_GET['kategori']) : null;
    $q        = isset($_GET['q']) ? trim($_GET['q']) : null; // search
    $min_price= isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
    $max_price= isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;
    $is_active= isset($_GET['is_active']) ? trim($_GET['is_active']) : null;
    $page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit    = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
    $offset   = ($page - 1) * $limit;

    $where = [];
    $params = [];

    if ($kategori !== null && $kategori !== '') {
        $where[] = "kategori = :kategori";
        $params[':kategori'] = $kategori;
    }
    if ($q !== null && $q !== '') {
        $where[] = "(nama_produk LIKE :q OR deskripsi LIKE :q)";
        $params[':q'] = '%' . $q . '%';
    }
    if ($min_price !== null) {
        $where[] = "harga >= :min_price";
        $params[':min_price'] = $min_price;
    }
    if ($max_price !== null) {
        $where[] = "harga <= :max_price";
        $params[':max_price'] = $max_price;
    }
    if ($is_active !== null && $is_active !== '') {
        $where[] = "is_active = :is_active";
        $params[':is_active'] = (int)$is_active;
    }

    $sql = "SELECT id, nama_produk, deskripsi, kategori, harga, satuan, gambar, stok, is_active, created_at, updated_at
            FROM koperasi_produk";
    if (!empty($where)) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // count total
    $countSql = "SELECT COUNT(*) AS total FROM koperasi_produk";
    if (!empty($where)) $countSql .= " WHERE " . implode(" AND ", $where);
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $tmp = $countStmt->fetch(PDO::FETCH_ASSOC);
    $total = $tmp ? (int)$tmp['total'] : count($data);

    echo json_encode([
        'success' => true,
        'message' => 'Daftar produk.',
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kesalahan database: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
}
