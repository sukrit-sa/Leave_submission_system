<?php
include('../conn/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leaveId = $_POST['leave_id'];
    
    $sql = "UPDATE leaves SET 
            leavestatus = 'ไม่อนุมัติ'
            WHERE leavesid = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $leaveId);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'ปฏิเสธการลาเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการปฏิเสธการลา']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>