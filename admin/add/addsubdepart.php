<?php
header('Content-Type: application/json; charset=utf-8');
include('../conn/conn.php');

if (isset($_POST['subdepartname']) && isset($_POST['headepartid'])) {
    $subdepartname = $_POST['subdepartname'];
    $headepartid = $_POST['headepartid'];

    // Check for duplicate name
    $check_sql = "SELECT subdepartid FROM subdepart WHERE subdepartname = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $subdepartname);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'ชื่องานนี้มีอยู่แล้วในระบบ'
        ]);
        exit;
    }

    // Insert new subdepart
    $sql = "INSERT INTO subdepart (subdepartname, headepartid) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $subdepartname, $headepartid);

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'เพิ่มงานใหม่เรียบร้อยแล้ว'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการเพิ่มข้อมูล: ' . $stmt->error
        ]);
    }

    $stmt->close();
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'
    ]);
}

$conn->close();
