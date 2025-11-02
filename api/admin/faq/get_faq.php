<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../../config/config_database.php';

// (Auth removed) -- endpoint GET FAQ is now public/no auth required

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Database connection not found.']);
    exit;
}

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $kategori = isset($_GET['kategori']) ? trim($_GET['kategori']) : null;
    $active = isset($_GET['active']) ? (int)$_GET['active'] : null;

    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM faq WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo json_encode(['success' => true, 'data' => $row], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            echo json_encode(['success' => false, 'message' => 'FAQ tidak ditemukan.'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        exit;
    }

    $where = [];
    $params = [];
    if ($kategori !== null && $kategori !== '') {
        $where[] = 'kategori = :kategori';
        $params[':kategori'] = $kategori;
    }
    if ($active !== null) {
        $where[] = 'is_active = :is_active';
        $params[':is_active'] = $active ? 1 : 0;
    }

    $sql = "SELECT * FROM faq" . (!empty($where) ? " WHERE " . implode(' AND ', $where) : "") . " ORDER BY urutan ASC, id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
