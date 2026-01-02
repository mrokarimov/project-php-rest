<?php
include "db.php";

$sql = "SELECT month, value FROM sales_monthly";
$stmt = $conn->query($sql);

$data = $stmt->fetchAll();

echo json_encode($data);
