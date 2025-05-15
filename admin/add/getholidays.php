<?php
header('Content-Type: application/json');
include('../conn/conn.php');

$sql = "SELECT * FROM holiday";
$result = mysqli_query($conn, $sql);

$events = array();
while ($row = mysqli_fetch_assoc($result)) {
    $events[] = array(
        'id' => $row['holidayid'],
        'title' => $row['holidayname'],
        'start' => $row['holidayday'],
        'allDay' => true
    );
}

echo json_encode($events);
mysqli_close($conn);