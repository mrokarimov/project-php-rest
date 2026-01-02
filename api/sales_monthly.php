<?php
require_once __DIR__ . "/db.php";

$stmt = $conn->query("
    SELECT month, value
    FROM sales_monthly
    ORDER BY month
");

echo json_encode($stmt->fetchAll());
