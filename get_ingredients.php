<?php
include 'connect.php';

header('Content-Type: application/json');

$query = "SELECT name FROM inventory ORDER BY name ASC";
$result = mysqli_query($conn, $query);

$ingredients = [];
while ($row = mysqli_fetch_assoc($result)) {
    $ingredients[] = $row['name'];
}

echo json_encode($ingredients);
?>