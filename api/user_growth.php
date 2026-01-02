<?php
include 'db.php';

$sql = "SELECT day, users FROM user_growth";
$result = mysqli_query($conn, $sql);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode($data);
