<?php
header('Content-Type: application/json');
include('../conn/conn.php');

if (isset($_POST['id'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);

    // Check if staff status exists first
    $check_sql = "SELECT staffid FROM staffstatus WHERE staffid = '$id'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Staff status exists, try to delete
        $sql = "DELETE FROM staffstatus WHERE staffid = '$id'";
        
        if (mysqli_query($conn, $sql)) {
            if (mysqli_affected_rows($conn) > 0) {
                echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลสถานะงานสำเร็จ']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบข้อมูลได้']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลสถานะงานที่ต้องการลบ']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'ไม่ได้ระบุรหัสสถานะงาน']);
}

mysqli_close($conn);