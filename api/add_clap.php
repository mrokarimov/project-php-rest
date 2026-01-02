<?php
include("db.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo json_encode(["error"=>"Method Not Allowed"]);
  exit;
}

mysqli_query($conn, "INSERT INTO claps () VALUES ()");

echo json_encode([
  "status" => "ok",
  "message" => "Clap added"
]);
