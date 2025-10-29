<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../../config/config_database.php';

try {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) { http_response_code(400); echo json_encode(["success"=>false,"message"=>"Format JSON tidak valid."]); exit; }

    $id = isset($input['id']) ? (int)$input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(["success"=>false,"message"=>"Parameter 'id' wajib untuk update."]); exit; }

    // cek ada
    $chk = $pdo->prepare("SELECT id FROM peraturan_desa WHERE id = :id LIMIT 1");
    $chk->execute([':id'=>$id]);
    if (!$chk->fetch(PDO::FETCH_ASSOC)) { http_response_code(404); echo json_encode(["success"=>false,"message"=>"Data tidak ditemukan."]); exit; }

    $allowed = ['nomor_peraturan','judul','deskripsi','kategori','tanggal_penetapan','file_path','file_size','is_active'];
    $set = []; $params = [];

    foreach ($allowed as $col) {
        if (array_key_exists($col, $input)) {
            if ($input[$col] === null) {
                $set[] = "$col = NULL";
            } else {
                // basic validation for tanggal_penetapan
                if ($col === 'tanggal_penetapan' && $input[$col] !== '') {
                    $d = DateTime::createFromFormat('Y-m-d', $input[$col]);
                    if (!($d && $d->format('Y-m-d') === $input[$col])) {
                        http_response_code(400);
                        echo json_encode(["success"=>false,"message"=>"Format 'tanggal_penetapan' tidak valid. Gunakan YYYY-MM-DD."]);
                        exit;
                    }
                }
                $set[] = "$col = :$col";
                $params[":$col"] = $input[$col];
            }
        }
    }

    if (empty($set)) { echo json_encode(["success"=>false,"message"=>"Tidak ada field untuk diupdate."]); exit; }

    $set[] = "updated_at = NOW()";
    $sql = "UPDATE peraturan_desa SET ".implode(", ", $set)." WHERE id = :id LIMIT 1";
    $params[':id'] = $id;

    $pdo->beginTransaction();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pdo->commit();

    echo json_encode(['success'=>true,'message'=>'Peraturan desa berhasil diupdate.','id'=>$id], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kesalahan database: '.$e->getMessage()]);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kesalahan server: '.$e->getMessage()]);
}
