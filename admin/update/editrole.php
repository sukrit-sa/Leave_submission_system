<?php
header('Content-Type: application/json');
include('../conn/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roleid = mysqli_real_escape_string($conn, $_POST['roleid']);
    $rolename = mysqli_real_escape_string($conn, $_POST['rolename']);
    $level = mysqli_real_escape_string($conn, $_POST['level']);

    // Check for duplicate except current record
    $check_sql = "SELECT COUNT(*) as count FROM role WHERE rolename = '$rolename' AND roleid != '$roleid'";
    $check_result = mysqli_query($conn, $check_sql);
    $row = mysqli_fetch_assoc($check_result);

    if ($row['count'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'บทบาทนี้มีอยู่แล้ว']);
        exit();
    }

    $sql = "UPDATE role SET rolename = '$rolename', level = '$level' WHERE roleid = '$roleid'";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'แก้ไขบทบาทสำเร็จ']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

mysqli_close($conn);