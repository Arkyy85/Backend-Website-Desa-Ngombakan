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

    // check exist
    $chk = $pdo->prepare("SELECT id FROM aduan WHERE id = :id LIMIT 1");
    $chk->execute([':id'=>$id]);
    if (!$chk->fetch(PDO::FETCH_ASSOC)) { http_response_code(404); echo json_encode(["success"=>false,"message"=>"Aduan tidak ditemukan."]); exit; }

    $allowed = ['nama_pelapor','email','telepon','kategori','judul','deskripsi','lokasi','lampiran','status','tanggapan','tanggal_tanggapan','is_active'];
    $set = []; $params = [];

    // validasi status jika dikirim
    $allowedStatus = ['pending','diproses','selesai','ditolak'];

    foreach ($allowed as $col) {
        if (array_key_exists($col, $input)) {
            if ($input[$col] === null) {
                $set[] = "$col = NULL";
            } else {
                if ($col === 'status') {
                    $val = $input[$col];
                    if (!in_array($val, $allowedStatus)) {
                        http_response_code(400);
                        echo json_encode(["success"=>false,"message"=>"Status tidak valid. Gunakan salah satu: ".implode(', ',$allowedStatus)]);
                        exit;
                    }
                    $params[":$col"] = $val;
                } else {
                    $params[":$col"] = $input[$col];
                }
                $set[] = "$col = :$col";
            }
        }
    }

    if (empty($set)) { echo json_encode(["success"=>false,"message"=>"Tidak ada field untuk diupdate."]); exit; }

    $set[] = "updated_at = NOW()";
    $sql = "UPDATE aduan SET ".implode(", ", $set)." WHERE id = :id LIMIT 1";
    $params[':id'] = $id;

    $pdo->beginTransaction();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pdo->commit();

    echo json_encode(['success'=>true,'message'=>'Aduan berhasil diupdate.','id'=>$id], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kesalahan database: '.$e->getMessage()]);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kesalahan server: '.$e->getMessage()]);
}
