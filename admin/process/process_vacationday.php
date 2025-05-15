<?php
header('Content-Type: application/json'); // กำหนดให้ response เป็น JSON

include('../conn/conn.php');

// ตรวจสอบว่าเป็นการร้องขอแบบ POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit();
}

// รับข้อมูลจากฟอร์ม
$empID = isset($_POST['empID']) ? intval($_POST['empID']) : 0;
$vacationday = isset($_POST['vacationday']) ? intval($_POST['vacationday']) : 0;

// ตรวจสอบข้อมูล
if ($empID <= 0 || $vacationday < 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ข้อมูลไม่ถูกต้อง'
    ]);
    exit();
}

// อัพเดตค่า Vacationday ในตาราง employees
$sql = "UPDATE employees SET Vacationday = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL'
    ]);
    exit();
}

$stmt->bind_param('ii', $vacationday, $empID);
if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'อัพเดตวันลาสะสมเรียบร้อยแล้ว'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการอัพเดตข้อมูล'
    ]);
}

$stmt->close();
$conn->close();
?>