<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../../config/config_database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = [];
    $id = isset($input['id']) ? (int)$input['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

    if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>"Parameter 'id' wajib untuk menghapus."]); exit; }

    $chk = $pdo->prepare("SELECT id, file_path FROM media_files WHERE id = :id LIMIT 1");
    $chk->execute([':id'=>$id]);
    $row = $chk->fetch(PDO::FETCH_ASSOC);
    if (!$row) { http_response_code(404); echo json_encode(['success'=>false,'message'=>"Media file dengan id={$id} tidak ditemukan."]); exit; }

    // jika ingin menghapus file fisik, aktifkan kode ini dan sesuaikan base path
    // $fileRel = ltrim($row['file_path'], '/');
    // $fileFull = realpath(__DIR__ . '/../../../' . $fileRel);
    // if ($fileFull && file_exists($fileFull)) {
    //     @unlink($fileFull);
    // }

    $stmt = $pdo->prepare("DELETE FROM media_files WHERE id = :id LIMIT 1");
    $stmt->execute([':id'=>$id]);

    echo json_encode(['success'=>true,'message'=>'Media file berhasil dihapus.','id'=>$id], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kesalahan database: '.$e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kesalahan server: '.$e->getMessage()]);
}
