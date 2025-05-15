<?php
include('../conn/conn.php');
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_id = $_POST['leave_id'];
    $current_status = $_POST['current_status'];
    $employeesid = $_POST['employeesid'];
    $reject_reason = $_POST['reject_reason'];
    
    // อัพเดทสถานะการลาเป็น "ไม่อนุมัติ"
    $sql = "UPDATE leaves SET 
            leavestatus = 'ไม่อนุมัติ',
            reason = ?
            WHERE leavesid = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $reject_reason, $leave_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'ไม่อนุมัติใบลาเรียบร้อยแล้ว'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการอัพเดทข้อมูล'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}
?>