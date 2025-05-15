<?php
header('Content-Type: application/json; charset=utf-8');
include('../conn/conn.php');

if (isset($_POST['subdepartid']) && isset($_POST['subdepartname']) && isset($_POST['headepartid'])) {
    $subdepartid = $_POST['subdepartid'];
    $subdepartname = $_POST['subdepartname'];
    $headepartid = $_POST['headepartid'];

    // Check for duplicate name excluding current record
    $check_sql = "SELECT subdepartid FROM subdepart WHERE subdepartname = ? AND subdepartid != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $subdepartname, $subdepartid);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'ชื่องานนี้มีอยู่แล้วในระบบ'
        ]);
        exit;
    }

    // Update subdepart
    $sql = "UPDATE subdepart SET subdepartname = ?, headepartid = ? WHERE subdepartid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $subdepartname, $headepartid, $subdepartid);

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'แก้ไขข้อมูลเรียบร้อยแล้ว'
        ]);
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
        'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'
    ]);
}

$conn->close();
    ?>