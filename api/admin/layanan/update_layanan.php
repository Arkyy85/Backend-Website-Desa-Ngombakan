<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
require_once __DIR__ . '/../../../config/config_database.php';

function make_slug($s) {
    $s = strtolower($s);
    $s = preg_replace('/[^\p{L}\p{Nd}]+/u', '-', $s);
    $s = trim($s, '-');
    return $s;
}

try {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) { http_response_code(400); echo json_encode(["success"=>false,"message"=>"Format JSON tidak valid."]); exit; }

    $id = isset($input['id']) ? (int)$input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(["success"=>false,"message"=>"Parameter 'id' wajib untuk update."]); exit; }

    // check exists
    $chk = $pdo->prepare("SELECT id, slug FROM layanan WHERE id = :id LIMIT 1");
    $chk->execute([':id'=>$id]);
    $orig = $chk->fetch(PDO::FETCH_ASSOC);
    if (!$orig) { http_response_code(404); echo json_encode(["success"=>false,"message"=>"Layanan tidak ditemukan."]); exit; }

    $allowed = ['nama_layanan','slug','deskripsi','icon','waktu_proses','biaya','syarat','prosedur','catatan','urutan','is_active'];
    $set = []; $params = [];

    foreach ($allowed as $col) {
        if (array_key_exists($col, $input)) {
            if ($input[$col] === null) {
                $set[] = "$col = NULL";
            } else {
                if ($col === 'syarat' || $col === 'prosedur') {
                    if (!is_array($input[$col])) {
                        http_response_code(400);
                        echo json_encode(["success"=>false,"message"=> "'$col' harus berupa array jika dikirim."]);
                        exit;
                    }
                    $params[":$col"] = json_encode(array_values($input[$col]), JSON_UNESCAPED_UNICODE);
                    $set[] = "$col = :$col";
                } else {
                    $params[":$col"] = $input[$col];
                    $set[] = "$col = :$col";
                }
            }
        }
    }

    if (empty($set)) { echo json_encode(["success"=>false,"message"=>"Tidak ada field untuk diupdate."]); exit; }

    // handle slug uniqueness if slug changed or empty
    if (array_key_exists('slug', $input)) {
        $newSlug = trim($input['slug']);
        if ($newSlug === '') {
            // if empty, generate from nama_layanan if provided, else keep original
            if (isset($input['nama_layanan']) && trim($input['nama_layanan']) !== '') {
                $newSlug = make_slug($input['nama_layanan']);
            } else {
                $newSlug = $orig['slug'];
            }
        }
        // ensure unique
        $base = $newSlug;
        $i = 0;
        while (true) {
            $check = $pdo->prepare("SELECT id FROM layanan WHERE slug = :slug AND id != :id LIMIT 1");
            $check->execute([':slug'=>$newSlug, ':id'=>$id]);
            if (!$check->fetch(PDO::FETCH_ASSOC)) break;
            $i++;
            $newSlug = $base . '-' . $i;
        }
        // set param and ensure the set contains slug assignment
        $params[':slug'] = $newSlug;
        // if slug wasn't in $set (it was null earlier), ensure it's included — but we added from array_key_exists
    }

    $set[] = "updated_at = NOW()";
    $sql = "UPDATE layanan SET " . implode(", ", $set) . " WHERE id = :id LIMIT 1";
    $params[':id'] = $id;

    $pdo->beginTransaction();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pdo->commit();

    echo json_encode(['success'=>true,'message'=>'Layanan berhasil diupdate.','id'=>$id], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kesalahan database: '.$e->getMessage()]);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Kesalahan server: '.$e->getMessage()]);
}
