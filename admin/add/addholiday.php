<?php
header('Content-Type: application/json');
include('../conn/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    // Check for duplicate date
    $check_sql = "SELECT COUNT(*) as count FROM holiday WHERE holidayday = '$date'";
    $check_result = mysqli_query($conn, $check_sql);
    $row = mysqli_fetch_assoc($check_result);

    if ($row['count'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'วันที่นี้ถูกกำหนดเป็นวันหยุดแล้ว']);
        exit();
    }

    $sql = "INSERT INTO holiday (holidayname, holidayday) VALUES ('$description', '$date')";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'เพิ่มวันหยุดสำเร็จ']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . mysqli_error($conn)]);
    }
}

mysqli_close($conn);