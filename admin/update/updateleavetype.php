<?php
header('Content-Type: application/json');
include('../conn/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leavetypeid = mysqli_real_escape_string($conn, $_POST['leavetypeid']);
    $leavetypename = mysqli_real_escape_string($conn, $_POST['leavetypename']);
    $staffid = mysqli_real_escape_string($conn, $_POST['staffid']);
    $workage = mysqli_real_escape_string($conn, $_POST['workage']);
    $gender = empty($_POST['gender']!="") ? NULL :  $_POST['gender'];
    $workageday = ($workage == '3') ? 0 : mysqli_real_escape_string($conn, $_POST['workageday']);
    $leaveofyear = mysqli_real_escape_string($conn, $_POST['leaveofyear']);
    $stackleaveday = empty($_POST['stackleaveday']) ? 0 : mysqli_real_escape_string($conn, $_POST['stackleaveday']);
    $nameform = empty($_POST['nameform']) ? NULL : mysqli_real_escape_string($conn, $_POST['nameform']);

    // Check if leave type exists
    $check_sql = "SELECT leavetypeid FROM leavetype WHERE leavetypeid = '$leavetypeid'";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) > 0) {
        $sql = "UPDATE leavetype SET 
                leavetypename = '$leavetypename',
                gender = '$gender',
                staffid = '$staffid',
                workage = '$workage',
                workageday = '$workageday',
                leaveofyear = '$leaveofyear',
                stackleaveday = '$stackleaveday',
                nameform = " . ($nameform === NULL ? "NULL" : "'$nameform'") . "
                WHERE leavetypeid = '$leavetypeid'";

        if (mysqli_query($conn, $sql)) {
            echo json_encode(['status' => 'success', 'message' => 'อัพเดทข้อมูลสำเร็จ']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลที่ต้องการแก้ไข']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

mysqli_close($conn);
?>