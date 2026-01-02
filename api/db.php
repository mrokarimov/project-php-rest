<?php
header("Content-Type: application/json");

$host = "dpg-d5clua3uibrs73crl27g-a";
$port = "5432";
$db   = "final_project_db_0g4f";
$user = "final_project_db_0g4f_user";
$pass = "NSZ83w7kEL1P33d1ghJhyKEV39syuyRH";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => $e->getMessage()
    ]);
    exit;
}
