<?php
require_once __DIR__ . "/db.php";

$stmt = $conn->query("
    SELECT source, value
    FROM traffic_sources
    ORDER BY value DESC
");

echo json_encode($stmt->fetchAll());
