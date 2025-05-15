<?php
header('Content-Type: application/json; charset=utf-8');
include('../conn/conn.php');

if (isset($_POST['id']) && isset($_POST['name'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];

    // Check for duplicate name excluding current department
    $check_duplicate = "SELECT headepartid FROM headepart WHERE headepartname = ? AND headepartid != ?";
    $check_stmt = $conn->prepare($check_duplicate);
    $check_stmt->bind_param("si", $name, $id);
    $check_stmt->execute();
    $duplicate_result = $check_stmt->get_result();

    if ($duplicate_result->num_rows > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'ชื่อแผนกนี้มีอยู่แล้วในระบบ'
        ]);
        exit;
    }

    // Check if department exists
    $check_sql = "SELECT headepartid FROM headepart WHERE headepartid = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'ไม่พบข้อมูลแผนกที่ต้องการแก้ไข'
        ]);
        exit;
    }

    // อัพเดทข้อมูล
    $sql = "UPDATE headepart SET headepartname = ? WHERE headepartid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $name, $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'แก้ไขข้อมูลแผนกเรียบร้อยแล้ว'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่มีการเปลี่ยนแปลงข้อมูล'
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการแก้ไขข้อมูล: ' . $stmt->error
        ]);
    }

    $stmt->close();
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'ข้อมูลไม่ครบถ้วน'
    ]);
}

$conn->close();
?>
