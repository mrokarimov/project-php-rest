<?php
include "db.php";

$stmt = $conn->query("SELECT month, value FROM sales_monthly");
$data = $stmt->fetchAll();

echo json_encode($data);
