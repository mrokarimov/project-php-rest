<?php
include "db.php";

$sql = "SELECT day, users FROM user_growth";
$stmt = $conn->query($sql);

echo json_encode($stmt->fetchAll());
