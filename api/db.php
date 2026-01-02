<?php
header("Content-Type: application/json");

$conn = mysqli_connect(
    "fdb1034.awardspace.net",
    "4703966_bazadanych",
    "QQ!!qq11",
    "4703966_bazadanych"
);

if (!$conn) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

echo json_encode(["status" => "DB OK"]);
