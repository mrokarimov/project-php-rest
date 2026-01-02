<?php
include("db.php");

$r = mysqli_query($conn, "SELECT COUNT(*) AS total FROM claps");
$data = mysqli_fetch_assoc($r);

echo json_encode([
  "total" => (int)$data["total"]
]);
