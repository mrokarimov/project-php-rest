<?php
require_once __DIR__ . "/db.php";

$stmt = $conn->query("
    SELECT category, value
    FROM product_categories
    ORDER BY value DESC
");

echo json_encode($stmt->fetchAll());
