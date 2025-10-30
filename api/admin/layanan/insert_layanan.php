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
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Format JSON tidak valid."]);
        exit;
    }

    $nama_layanan = trim($input['nama_layanan'] ?? '');
    $slug         = isset($input['slug']) ? trim($input['slug']) : null;
    $deskripsi    = $input['deskripsi'] ?? null;
    $icon         = isset($input['icon']) ? trim($input['icon']) : null;
    $waktu_proses = isset($input['waktu_proses']) ? trim($input['waktu_proses']) : null;
    $biaya        = isset($input['biaya']) ? trim($input['biaya']) : null;
    $syarat       = array_key_exists('syarat', $input) ? $input['syarat'] : null; // expect array or null
    $prosedur     = array_key_exists('prosedur', $input) ? $input['prosedur'] : null; // expect array or null
    $catatan      = isset($input['catatan']) ? $input['catatan'] : null;
    $urutan       = isset($input['urutan']) ? (int)$input['urutan'] : 0;
    $is_active    = isset($input['is_active']) ? (int)$input['is_active'] : 1;

    if ($nama_layanan === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Field 'nama_layanan' wajib diisi."]);
        exit;
    }

    // generate slug if not provided
    if (empty($slug)) $slug = make_slug($nama_layanan);
    if ($slug === '') $slug = time();

    // normalize syarat/prosedur to JSON strings or null
    if ($syarat !== null) {
        if (!is_array($syarat)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "'syarat' harus berupa array (JSON array)."]);
            exit;
        }
        $syarat_json = json_encode(array_values($syarat), JSON_UNESCAPED_UNICODE);
    } else $syarat_json = null;

    if ($prosedur !== null) {
        if (!is_array($prosedur)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "'prosedur' harus berupa array (JSON array)."]);
            exit;
        }
        $prosedur_json = json_encode(array_values($prosedur), JSON_UNESCAPED_UNICODE);
    } else $prosedur_json = null;

    // ensure slug unique — if exists, append suffix
    $baseSlug = $slug;
    $i = 0;
    while (true) {
        $check = $pdo->prepare("SELECT id FROM layanan WHERE slug = :slug LIMIT 1");
        $check->execute([':slug' => $slug]);
        if (!$check->fetch(PDO::FETCH_ASSOC)) break;
        $i++;
        $slug = $baseSlug . '-' . $i;
    }

    $sql = "INSERT INTO layanan
            (nama_layanan, slug, deskripsi, icon, waktu_proses, biaya, syarat, prosedur, catatan, urutan, is_active)
            VALUES
            (:nama_layanan, :slug, :deskripsi, :icon, :waktu_proses, :biaya, :syarat, :prosedur, :catatan, :urutan, :is_active)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nama_layanan' => $nama_layanan,
        ':slug'         => $slug,
        ':deskripsi'    => $deskripsi !== '' ? $deskripsi : null,
        ':icon'         => $icon !== '' ? $icon : null,
        ':waktu_proses' => $waktu_proses !== '' ? $waktu_proses : null,
        ':biaya'        => $biaya !== '' ? $biaya : null,
        ':syarat'       => $syarat_json,
        ':prosedur'     => $prosedur_json,
        ':catatan'      => $catatan !== '' ? $catatan : null,
        ':urutan'       => $urutan,
        ':is_active'    => $is_active
    ]);

    $id = $pdo->lastInsertId();
    echo json_encode(["success" => true, "message" => "Layanan berhasil ditambahkan.", "id" => $id, "slug" => $slug], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success"=>false,"message"=>"Kesalahan database: ".$e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success"=>false,"message"=>"Kesalahan server: ".$e->getMessage()]);
}
