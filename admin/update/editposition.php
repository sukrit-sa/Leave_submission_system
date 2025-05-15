<?php
header('Content-Type: application/json');
include('../conn/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $positionid = mysqli_real_escape_string($conn, $_POST['positionid']);
    $positionname = mysqli_real_escape_string($conn, $_POST['positionname']);
    $roleid = mysqli_real_escape_string($conn, $_POST['roleid']);

    // Check for duplicate except current record
    $check_sql = "SELECT COUNT(*) as count FROM position WHERE positionname = '$positionname' AND positionid != '$positionid'";
    $check_result = mysqli_query($conn, $check_sql);
    $row = mysqli_fetch_assoc($check_result);

    if ($row['count'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'ตำแหน่งนี้มีอยู่แล้ว']);
        exit();
    }

    $sql = "UPDATE position SET positionname = '$positionname', roleid = '$roleid' WHERE positionid = '$positionid'";

    if (mysqli_query($conn, $sql)) {
        if (mysqli_affected_rows($conn) > 0) {
            echo json_encode(['status' => 'success', 'message' => 'แก้ไขตำแหน่งสำเร็จ']);
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
