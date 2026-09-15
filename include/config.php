<?php
// Shared configuration for the CU Student ID-card module.
define('DB_HOST', 'localhost');
define('DB_NAME', 'idcard_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_PATH', dirname(__DIR__));
define('IDCARD_UPLOAD_PATH', BASE_PATH . '/uploads/idcard');

try {
    $con = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $exception) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}
