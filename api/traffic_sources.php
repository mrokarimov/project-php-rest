<?php
include "db.php";

$sql = "SELECT source, value FROM traffic_sources";
$stmt = $conn->query($sql);

echo json_encode($stmt->fetchAll());
