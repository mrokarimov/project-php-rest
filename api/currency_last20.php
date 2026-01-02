<?php
include 'db.php';

$sql = "
SELECT currency_code, rate, created_at
FROM currency_quotes
ORDER BY created_at DESC
LIMIT 20
";

$result = mysqli_query($conn, $sql);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        "currency" => $row["currency_code"],
        "rate" => (float)$row["rate"],
        "timestamp" => $row["created_at"]
    ];
}

echo json_encode($data);
