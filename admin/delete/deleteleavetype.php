<?php
header('Content-Type: application/json');
include('../conn/conn.php');

if (isset($_POST['id'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);

    // Check if leave type exists first
    $check_sql = "SELECT leavetypeid FROM leavetype WHERE leavetypeid = '$id'";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) > 0) {
        // Leave type exists, try to delete
        $sql = "DELETE FROM leavetype WHERE leavetypeid = '$id'";

        if (mysqli_query($conn, $sql)) {
            if (mysqli_affected_rows($conn) > 0) {
                echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลประเภทการลาสำเร็จ']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบข้อมูลได้']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลประเภทการลาที่ต้องการลบ']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'ไม่ได้ระบุรหัสประเภทการลา']);
}

mysqli_close($conn);
