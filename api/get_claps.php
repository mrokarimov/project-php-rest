<?php
require_once __DIR__ . "/db.php";

try {
    $stmt = $conn->query("SELECT total FROM claps WHERE id = 1");
    $row = $stmt->fetch();

    echo json_encode([
        "claps" => (int)$row["total"]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Failed to get claps",
        "details" => $e->getMessage()
    ]);
}
