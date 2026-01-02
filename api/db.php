<?php
header("Content-Type: application/json");

try {
    $conn = new PDO(
        "pgsql:host=dpg-d5c1ua3uibrs73crl27g-a;port=5432;dbname=final_project_db_0g4f",
        "final_project_db_0g4f_user",
        "NSZ83w7kEL1P33d1ghJhyKEV39syuyRH",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "DB connection failed",
        "details" => $e->getMessage()
    ]);
    exit;
}
