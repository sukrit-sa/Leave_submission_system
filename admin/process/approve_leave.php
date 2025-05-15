<?php
include('../conn/conn.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับข้อมูลจาก AJAX และตรวจสอบคีย์
    $leaveId = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $supervisor_id = isset($_POST['supervisor_id']) && $_POST['supervisor_id'] !== '0' ? intval($_POST['supervisor_id']) : null;
    $deputy_id = isset($_POST['deputy_id']) && $_POST['deputy_id'] !== '0' ? intval($_POST['deputy_id']) : null;
    $director_id = isset($_POST['director_id']) && $_POST['director_id'] !== '0' ? intval($_POST['director_id']) : null;

    // ดีบั๊กข้อมูลที่รับมา
    error_log("Received POST data in approve_leave.php: " . json_encode($_POST));
    error_log("Processed values: leaveId=$leaveId, supervisor_id=" . ($supervisor_id ?? 'null') . ", deputy_id=" . ($deputy_id ?? 'null') . ", director_id=" . ($director_id ?? 'null'));

    // ตรวจสอบรหัสใบลา
    if ($leaveId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'รหัสใบลาไม่ถูกต้อง']);
        exit;
    }

    // ตรวจสอบว่ามีผู้รับรองอย่างน้อยหนึ่งคนหรือไม่
    if ($supervisor_id == null && $deputy_id == null && $director_id == null) {
        error_log("No approvers selected for leaveId=$leaveId");
        echo json_encode(['status' => 'error', 'message' => 'กรุณาเลือกผู้รับรองอย่างน้อยหนึ่งคน']);
        exit;
    }

    // กำหนดค่า leavestatus ตามเงื่อนไข
    if ($supervisor_id == null && $deputy_id != null) {
        $sql = "UPDATE leaves SET leavestatus = 'รอรองผอ.อนุมัติ' WHERE leavesid = ?";
        error_log("Setting leavestatus to 'รอรองผอ.อนุมัติ' for leaveId=$leaveId");
    } elseif ($supervisor_id == null && $deputy_id == null) {
        $sql = "UPDATE leaves SET leavestatus = 'รอผอ.อนุมัติ' WHERE leavesid = ?";
        error_log("Setting leavestatus to 'รอผอ.อนุมัติ' for leaveId=$leaveId");
    } else {
        $sql = "UPDATE leaves SET leavestatus = 'รอหัวหน้าอนุมัติ' WHERE leavesid = ?";
        error_log("Setting leavestatus to 'รอหัวหน้าอนุมัติ' for leaveId=$leaveId");
    }

    // เตรียมคำสั่ง SQL
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $leaveId);

    if ($stmt->execute()) {
        error_log("Successfully updated leavestatus for leaveId=$leaveId");
        echo json_encode(['status' => 'success']);
    } else {
        error_log("Failed to update leavestatus for leaveId=$leaveId: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

$conn->close();
?>