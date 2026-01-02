<?php
header("Content-Type: application/json");

// Render environment variables
$host = "dpg-d5clua3uibrs73crl27g-a";
$port = "5432";
$db   = "final_project_db_0g4f";
$user = "final_project_db_0g4f_user";
$pass = "NSZ83w7kEL1P33d1ghJhyKEV39syuyRH";

// Safety check (optional but professional)
if (!$host || !$port || !$db || !$user || !$pass) {
    http_response_code(500);
    echo json_encode([
        "error" => "Missing database environment variables"
    ]);
    exit;
}

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Database connection failed"
        // uncomment only for debugging:
        // ,"details" => $e->getMessage()
    ]);
    exit;
}
