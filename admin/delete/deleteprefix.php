<?php
header('Content-Type: application/json');
include('../conn/conn.php');

if (isset($_POST['id'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);

    // Check if prefix exists first
    $check_sql = "SELECT prefixid FROM prefix WHERE prefixid = '$id'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Prefix exists, try to delete
        $sql = "DELETE FROM prefix WHERE prefixid = '$id'";
        
        if (mysqli_query($conn, $sql)) {
            if (mysqli_affected_rows($conn) > 0) {
                echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลคำนำหน้าสำเร็จ']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบข้อมูลได้']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลคำนำหน้าที่ต้องการลบ']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'ไม่ได้ระบุรหัสคำนำหน้า']);
}

mysqli_close($conn);