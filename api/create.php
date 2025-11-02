<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/config_database.php';
require_once __DIR__ . '/../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

$secret_key = "#112q282232%@!Q#1@!122221!@1";

$response = ["status" => "error", "message" => "Terjadi kesalahan."];

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$token = null;

if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}

if (!$token) {
    echo json_encode(["status" => "error", "message" => "Token Authorization dibutuhkan"]);
    exit;
}

try {
    $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Token tidak valid: " . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id       = $_POST['customer_id'] ?? null;
    $teknisi_id        = $_POST['teknisi_id'] ?? null;
    $odp_id            = $_POST['odp_id'] ?? null;
    $odp_port_id       = $_POST['odp_port_id'] ?? null;
    $olt_id            = $_POST['olt_id'] ?? null;
    $pon_id            = $_POST['pon_id'] ?? null;
    $ont_merk          = $_POST['ont_merk'] ?? null;
    $ont_sn            = $_POST['ont_sn'] ?? null;
    $redaman_pelanggan = $_POST['redaman_pelanggan'] ?? null;
    $panjang_kabel     = $_POST['panjang_kabel'] ?? null;
    $vlan              = $_POST['vlan'] ?? null;
    $ip_remote         = $_POST['ip_remote'] ?? null;
    $koordinat         = $_POST['koordinat'] ?? null;
    $noc               = $_POST['noc'] ?? null;
    $catatan           = $_POST['catatan'] ?? null;
    $status            = $_POST['status'] ?? 'pending';
    $foto_rumah        = $_POST['foto_rumah'] ?? null;
    $foto_instalasi    = $_POST['foto_instalasi'] ?? null;
    $foto_serial_ont   = $_POST['foto_serial_ont'] ?? null;
    $foto_redaman_odp  = $_POST['foto_redaman_odp'] ?? null;
    $finished_at       = $_POST['finished_at']?? null;

    if ($customer_id && $teknisi_id) {
        try {
            $stmt = $mysqli->prepare("
                INSERT INTO instalasi_pelanggan 
                (customer_id, teknisi_id, odp_id, odp_port_id, olt_id, pon_id, 
                 ont_merk, ont_sn, redaman_pelanggan, panjang_kabel, vlan, ip_remote, 
                 koordinat, noc, catatan, status, foto_rumah, foto_instalasi, foto_serial_ont, foto_redaman_odp, finished_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "iissssssdiisssssssss",
                $customer_id,
                $teknisi_id,
                $odp_id,
                $odp_port_id,
                $olt_id,
                $pon_id,
                $ont_merk,
                $ont_sn,
                $redaman_pelanggan,
                $panjang_kabel,
                $vlan,
                $ip_remote,
                $koordinat,
                $noc,
                $catatan,
                $status,
                $foto_rumah,
                $foto_instalasi,
                $foto_serial_ont,
                $foto_redaman_odp,
                $finished_at
            );

            if ($stmt->execute()) {
                $response = [
                    "status" => "success",
                    "message" => "Instalasi pelanggan berhasil ditambahkan",
                    "id" => $stmt->insert_id
                ];
            } else {
                $response = ["status" => "error", "message" => "Gagal menyimpan instalasi"];
            }
            $stmt->close();
        } catch (Exception $e) {
            $response = ["status" => "error", "message" => "Error: " . $e->getMessage()];
        }
    } else {
        $response = ["status" => "error", "message" => "customer_id dan teknisi_id wajib diisi"];
    }
}

echo json_encode($response);
