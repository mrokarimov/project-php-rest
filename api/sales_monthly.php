<?php
require_once "db.php";

$stmt = $conn->query("
    SELECT month, value
    FROM sales_monthly
    ORDER BY month
");

echo json_encode($stmt->fetchAll());
