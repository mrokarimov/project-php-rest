<?php
include 'db.php';

$sql = "SELECT category, count_value FROM product_categories";
$result = mysqli_query($conn, $sql);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode($data);
