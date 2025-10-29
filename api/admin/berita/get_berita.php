<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
require_once __DIR__ . '/../../../config/config_database.php';

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
    $kategori = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';

    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM berita WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Berita tidak ditemukan."]);
            exit;
        }
        echo json_encode(["success" => true, "data" => $data]);
    } elseif (!empty($slug)) {
        $stmt = $pdo->prepare("SELECT * FROM berita WHERE slug = :slug LIMIT 1");
        $stmt->execute([':slug' => $slug]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Berita tidak ditemukan."]);
            exit;
        }
        echo json_encode(["success" => true, "data" => $data]);
    } elseif (!empty($kategori)) {
        $stmt = $pdo->prepare("SELECT * FROM berita WHERE kategori = :kategori ORDER BY published_at DESC");
        $stmt->execute([':kategori' => $kategori]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $data]);
    } else {
        $stmt = $pdo->query("SELECT * FROM berita ORDER BY published_at DESC, id DESC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $data]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Kesalahan database: " . $e->getMessage()]);
}
