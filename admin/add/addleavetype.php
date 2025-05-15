<?php
include('../conn/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leavetypename = $_POST['leavetypename'];
    $staffid = $_POST['staffid'];
    $workage = $_POST['workage'];
    $gender = empty($_POST['gender']!="") ? NULL :  $_POST['gender'];
    $workageday = ($workage == '3') ? 0 : $_POST['workageday'];
    $leaveofyear = $_POST['leaveofyear'];
    $stackleaveday = empty($_POST['stackleaveday']) ? 0 : $_POST['stackleaveday'];
    $nameform = empty($_POST['nameform']) ? NULL : $_POST['nameform'];

    $sql = "INSERT INTO leavetype (leavetypename, staffid,gender, workage, workageday, leaveofyear, stackleaveday, nameform) 
            VALUES (?, ?, ?, ?, ?, ?, ?,?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sisiiiss", $leavetypename, $staffid,$gender ,$workage, $workageday, $leaveofyear, $stackleaveday, $nameform);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'เพิ่มประเภทการลาสำเร็จ']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . mysqli_error($conn)]);
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}
?>