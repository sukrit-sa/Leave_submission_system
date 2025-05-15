<?php
header('Content-Type: application/json');
include('../conn/conn.php');

if (isset($_POST['id'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);

    // Check if role is being used in position table
    $check_sql = "SELECT COUNT(*) as count FROM position WHERE roleid = '$id'";
    $check_result = mysqli_query($conn, $check_sql);
    $row = mysqli_fetch_assoc($check_result);

    if ($row['count'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบได้เนื่องจากมีการใช้งานบทบาทนี้อยู่']);
        exit();
    }

    $sql = "DELETE FROM role WHERE roleid = '$id'";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'ลบบทบาทสำเร็จ']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'ไม่ได้ระบุรหัสบทบาท']);
}

mysqli_close($conn);