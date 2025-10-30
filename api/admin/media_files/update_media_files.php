<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../../config/config_database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Format JSON tidak valid.']); exit; }

    $id = isset($input['id']) ? (int)$input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>"Parameter 'id' wajib untuk update."]); exit; }

    // cek exist
    $chk = $pdo->prepare("SELECT * FROM media_files WHERE id = :id LIMIT 1");
    $chk->execute([':id'=>$id]);
    $orig = $chk->fetch(PDO::FETCH_ASSOC);
    if (!$orig) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'File tidak ditemukan.']); exit; }

    $allowed = ['file_name','file_path','file_type','file_size','kategori','uploaded_by'];
    $set = []; $params = [];

    foreach ($allowed as $col) {
        if (array_key_exists($col, $input)) {
            $val = $input[$col];
            if ($val === null || $val === '') {
                $set[] = "$col = NULL";
            } else {
                $set[] = "$col = :$col";
                if ($col === 'file_size' || $col === 'uploaded_by') $params[":$col"] = (int)$val;
                else $params[":$col"] = $val;
            }
        }
    }

    if (empty($set)) { echo json_encode(['success'=>false,'message'=>'Tidak ada field untuk diupdate.']); exit; }

    $set[] = "created_at = created_at"; // no-op to keep formatting; updated_at updated automatically by DB trigger
    $sql = "UPDATE media_files SET " . implode(", ", $set) . ", created_at = created_at WHERE id = :id LIMIT 1";
    // Note: using updated_at ON UPDATE CURRENT_TIMESTAMP in table will update timestamp automatically on update
    $params[':id'] = $id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['success'=>true,'message'=>'Media file berhasil diupdate.','id'=>$id], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kesalahan database: '.$e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kesalahan server: '.$e->getMessage()]);
}
