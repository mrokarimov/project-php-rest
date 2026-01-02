<?php
include 'db.php';

$sql = "SELECT month, value FROM sales_monthly";
$result = mysqli_query($conn, $sql);

$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode($data);
