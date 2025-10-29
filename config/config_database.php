<?php
$db_host = '127.0.0.1';
$db_user = 'db2';
$db_pass = 'MKZ2rj4M2KBabTZ3';
$db_name = 'db2';
$db_port = 3306;

try {
    // Buat koneksi PDO
    $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Jika gagal, kirim JSON agar API tetap konsisten
    die(json_encode([
        "status" => "error",
        "message" => "Koneksi database gagal: " . $e->getMessage()
    ]));
}

// Base URL aplikasi
if (!defined('BASE_URL')) {
    define('BASE_URL', 'https://ftp2.hallosemarang.com/');
}
