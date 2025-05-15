<?php
header('Content-Type: application/json; charset=utf-8');
include('../conn/conn.php');

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    // Check if subdepart exists
    $check_sql = "SELECT subdepartid FROM subdepart WHERE subdepartid = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'ไม่พบข้อมูลงานที่ต้องการลบ'
        ]);
        exit;
    }

    // Delete subdepart
    $sql = "DELETE FROM subdepart WHERE subdepartid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'ลบข้อมูลงานเรียบร้อยแล้ว'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่สามารถลบข้อมูลได้'
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $stmt->error
        ]);
    }

    $stmt->close();
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'ไม่ได้ระบุรหัสงาน'
    ]);
}

$conn->close();
