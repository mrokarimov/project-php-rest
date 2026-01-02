<?php
include 'db.php';

$sql = "SELECT source, percent FROM traffic_sources";
$result = mysqli_query($conn, $sql);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode($data);
