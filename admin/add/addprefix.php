<?php
header('Content-Type: application/json');
include('../conn/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prefixname = mysqli_real_escape_string($conn, $_POST['prefixname']);

    // Check for duplicate
    $check_sql = "SELECT COUNT(*) as count FROM prefix WHERE prefixname = '$prefixname'";
    $check_result = mysqli_query($conn, $check_sql);
    $row = mysqli_fetch_assoc($check_result);

    if ($row['count'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'คำนำหน้านี้มีอยู่แล้ว']);
        exit();
    }

    $sql = "INSERT INTO prefix (prefixname) VALUES ('$prefixname')";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'เพิ่มคำนำหน้าสำเร็จ']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

mysqli_close($conn);