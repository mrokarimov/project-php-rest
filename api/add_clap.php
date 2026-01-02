<?php
require_once __DIR__ . "/db.php";

try {
    $stmt = $conn->prepare("
        UPDATE claps
        SET total = total + 1
        WHERE id = 1
        RETURNING total
    ");
    $stmt->execute();

    $row = $stmt->fetch();

    echo json_encode([
        "claps" => (int)$row["total"]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Failed to add clap",
        "details" => $e->getMessage()
    ]);
}
