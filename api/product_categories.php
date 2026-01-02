<?php
include "db.php";

$sql = "SELECT category, value FROM product_categories";
$stmt = $conn->query($sql);

echo json_encode($stmt->fetchAll());
