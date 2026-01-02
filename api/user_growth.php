<?php
require_once __DIR__ . "/db.php";

$stmt = $conn->query("
    SELECT day, users
    FROM user_growth
    ORDER BY id
");

echo json_encode($stmt->fetchAll());
