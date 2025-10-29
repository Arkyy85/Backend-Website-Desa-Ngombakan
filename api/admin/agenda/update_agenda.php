<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../../config/config_database.php';

try {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Format JSON tidak valid."]);
        exit;
    }

    $id = isset($input['id']) ? (int)$input['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Parameter 'id' wajib untuk update."]);
        exit;
    }

    // fields yang boleh diupdate
    $allowed = [
        'judul','deskripsi','tanggal_mulai','tanggal_selesai','waktu_mulai','waktu_selesai',
        'lokasi','kategori','penyelenggara','is_active'
    ];

    $set = [];
    $params = [];

    foreach ($allowed as $col) {
        if (array_key_exists($col, $input)) {
            if ($input[$col] === null) {
                $set[] = "$col = NULL";
            } else {
                $set[] = "$col = :$col";
                $params[":$col"] = $input[$col];
            }
        }
    }

    if (empty($set)) {
        echo json_encode(["success" => false, "message" => "Tidak ada field yang dikirim untuk diupdate."]);
        exit;
    }

    // basic date/time validation if present
    if (isset($params[':tanggal_mulai'])) {
        $d = DateTime::createFromFormat('Y-m-d', $params[':tanggal_mulai']);
        if (!($d && $d->format('Y-m-d') === $params[':tanggal_mulai'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Format 'tanggal_mulai' tidak valid. Gunakan YYYY-MM-DD."]);
            exit;
        }
    }
    if (isset($params[':tanggal_selesai'])) {
        $d = DateTime::createFromFormat('Y-m-d', $params[':tanggal_selesai']);
        if (!($d && $d->format('Y-m-d') === $params[':tanggal_selesai'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Format 'tanggal_selesai' tidak valid. Gunakan YYYY-MM-DD."]);
            exit;
        }
    }
    if (isset($params[':waktu_mulai'])) {
        $t = DateTime::createFromFormat('H:i:s', $params[':waktu_mulai']);
        if (!($t && $t->format('H:i:s') === $params[':waktu_mulai'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Format 'waktu_mulai' tidak valid. Gunakan HH:MM:SS."]);
            exit;
        }
    }
    if (isset($params[':waktu_selesai'])) {
        $t = DateTime::createFromFormat('H:i:s', $params[':waktu_selesai']);
        if (!($t && $t->format('H:i:s') === $params[':waktu_selesai'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Format 'waktu_selesai' tidak valid. Gunakan HH:MM:SS."]);
            exit;
        }
    }

    // tambahkan updated_at
    $set[] = "updated_at = NOW()";

    $sql = "UPDATE agenda SET " . implode(", ", $set) . " WHERE id = :id LIMIT 1";
    $params[':id'] = $id;

    $pdo->beginTransaction();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Agenda berhasil diupdate.',
        'id' => $id
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kesalahan database: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
}
