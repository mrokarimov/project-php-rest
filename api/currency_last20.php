<?php
require_once __DIR__ . "/db.php";

try {
    $stmt = $conn->query("
        SELECT
            rate_date AS date,
            rate
        FROM currency_rates
        ORDER BY rate_date DESC
        LIMIT 20
    ");

    $data = $stmt->fetchAll();

    echo json_encode(array_reverse($data));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Failed to fetch currency rates",
        "details" => $e->getMessage()
    ]);
}
