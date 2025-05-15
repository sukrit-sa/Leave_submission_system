<?php
header('Content-Type: application/json');
include('../conn/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staffid = mysqli_real_escape_string($conn, $_POST['staffid']);
    $staffname = mysqli_real_escape_string($conn, $_POST['staffname']);

    // Check for duplicate except current record
    $check_sql = "SELECT COUNT(*) as count FROM staffstatus WHERE staffname = '$staffname' AND staffid != '$staffid'";
    $check_result = mysqli_query($conn, $check_sql);
    $row = mysqli_fetch_assoc($check_result);

    if ($row['count'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'สถานะงานนี้มีอยู่แล้ว']);
        exit();
    }

    $sql = "UPDATE staffstatus SET staffname = '$staffname' WHERE staffid = '$staffid'";
    
    if (mysqli_query($conn, $sql)) {
        if (mysqli_affected_rows($conn) > 0) {
            echo json_encode(['status' => 'success', 'message' => 'แก้ไขสถานะงานสำเร็จ']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ไม่มีการเปลี่ยนแปลงข้อมูล']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

mysqli_close($conn);