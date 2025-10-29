<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../config/config_database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

// Konfigurasi JWT
$secret_key = "#112q282232%@!Q#1@!122221!@1";
$access_expiration = 24 * 60 * 60; // 1 jam
$refresh_expiration = 30 * 24 * 60 * 60; // 30 hari

$response = ["status" => "error", "message" => "Terjadi kesalahan."];

// Baca input JSON
$input = json_decode(file_get_contents("php://input"), true);
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Email dan password wajib diisi."]);
    exit;
}

try {
    // Koneksi database dari config
    $conn = $pdo ?? null; // Pastikan $pdo dibuat di config_database.php
    if (!$conn) {
        echo json_encode(["status" => "error", "message" => "Koneksi database gagal."]);
        exit;
    }

    // Cek user
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email AND status = 'aktif' LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["status" => "error", "message" => "Email tidak terdaftar atau akun nonaktif."]);
        exit;
    }

    // Verifikasi password
    if (!password_verify($password, $user['password'])) {
        echo json_encode(["status" => "error", "message" => "Password salah."]);
        exit;
    }

    // Payload token
    $issuedAt = time();
    $access_payload = [
        'iss' => 'DesaAPI',
        'iat' => $issuedAt,
        'exp' => $issuedAt + $access_expiration,
        'data' => [
            'id' => $user['id'],
            'nama_lengkap' => $user['nama_lengkap'],
            'email' => $user['email'],
            'role' => $user['role'],
            'jabatan' => $user['jabatan']
        ]
    ];

    $refresh_payload = [
        'iss' => 'DesaAPI',
        'iat' => $issuedAt,
        'exp' => $issuedAt + $refresh_expiration,
        'data' => [
            'id' => $user['id']
        ]
    ];

    // Buat token JWT
    $access_token = JWT::encode($access_payload, $secret_key, 'HS256');
    $refresh_token = JWT::encode($refresh_payload, $secret_key, 'HS256');

    // Update waktu login terakhir
    $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = :id")
         ->execute([':id' => $user['id']]);

    // Kirim response sukses
    $response = [
        "status" => "success",
        "message" => "Login berhasil.",
        "access_token" => $access_token,
        "refresh_token" => $refresh_token,
        "user" => [
            "id" => $user['id'],
            "nama_lengkap" => $user['nama_lengkap'],
            "jabatan" => $user['jabatan'],
            "email" => $user['email'],
            "role" => $user['role']
        ]
    ];
} catch (Exception $e) {
    $response = [
        "status" => "error",
        "message" => "Terjadi kesalahan: " . $e->getMessage()
    ];
}

echo json_encode($response);
