<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// tangani preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/config_database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

$secret_key = "#112q282232%@!Q#1@!122221!@1";

// Durasi access token: 24 jam (dalam detik)
$access_expiration  = 24 * 60 * 60; // 86400

$response = ["status" => "error", "message" => "Terjadi kesalahan."];

// Pastikan $pdo ada
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection (PDO) tidak ditemukan. Periksa config_database.php"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // dukung JSON atau form data
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $email = '';
    $password = '';

    if (stripos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (is_array($input)) {
            $email = trim($input['email'] ?? '');
            $password = $input['password'] ?? '';
        }
    } else {
        // form-data / x-www-form-urlencoded
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
    }

    if ($email === '' || $password === '') {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Email dan password wajib diisi."]);
        exit;
    }

    try {
        // ambil user berdasarkan email
        $sql = "SELECT id, username, email, password, nama_lengkap, role, foto, is_active
                FROM users
                WHERE email = :email
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "User tidak ditemukan."]);
            exit;
        }

        if (!isset($user['is_active']) || (int)$user['is_active'] !== 1) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Akun tidak aktif."]);
            exit;
        }

        $dbPass = $user['password'] ?? '';
        $password_valid = false;

        // Legacy MD5 (32 hex)
        if (strlen($dbPass) === 32 && ctype_xdigit($dbPass)) {
            $password_valid = (md5($password) === $dbPass);
        } else {
            // modern hash
            if (password_verify($password, $dbPass)) {
                $password_valid = true;
            } else {
                // fallback plain (legacy) - only if necessary
                if ($password === $dbPass) $password_valid = true;
            }
        }

        if (!$password_valid) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Email atau password salah."]);
            exit;
        }

        // berhasil login -> buat access token (exp = now + 24 jam)
        $issuedAt = time();
        $accessExp = $issuedAt + $access_expiration;

        $payload_access = [
            "iat" => $issuedAt,
            "exp" => $accessExp,
            "user" => [
                "id" => (int)$user['id'],
                "username" => $user['username'],
                "email" => $user['email'],
                "nama_lengkap" => $user['nama_lengkap'],
                "role" => $user['role']
            ]
        ];

        $access_token = JWT::encode($payload_access, $secret_key, 'HS256');

        // update last_login
        $upd = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
        $upd->execute([':id' => $user['id']]);

        // respon user tanpa password (tanpa refresh token)
        $userPublic = [
            "id" => (int)$user['id'],
            "username" => $user['username'],
            "email" => $user['email'],
            "nama_lengkap" => $user['nama_lengkap'],
            "role" => $user['role'],
            "foto" => $user['foto'],
            "is_active" => (int)$user['is_active']
        ];

        $response = [
            "status" => "success",
            "message" => "Login berhasil",
            "access_token" => $access_token,
            "expires_in" => $access_expiration,
            "user" => $userPublic
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Kesalahan database: " . $e->getMessage()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
    }

    exit;
}

// jika bukan POST (seharusnya tidak sampai sini)
http_response_code(405);
echo json_encode(["status" => "error", "message" => "Method tidak diizinkan"]);
