<?php
header("Content-Type: application/json");

$host = getenv("dpg-d5clua3uibrs73crl27g-a");
$port = getenv("5432");
$db   = getenv("final_project_db_0g4f");
$user = getenv("final_project_db_0g4f_user");
$pass = getenv("NSZ83w7kEL1P33d1ghJhyKEV39syuyRH");

if (!$host || !$port || !$db || !$user || !$pass) {
    http_response_code(500);
    echo json_encode(["error" => "Missing DB env variables"]);
    exit;
}

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "DB connection failed",
        "details" => $e->getMessage()
    ]);
    exit;
}
