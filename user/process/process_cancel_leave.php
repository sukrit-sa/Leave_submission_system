<?php
// ปิดการแสดงข้อผิดพลาด PHP เพื่อป้องกัน output ที่ไม่คาดคิด
ini_set('display_errors', 0);
error_reporting(E_ALL);

// เก็บ log ข้อผิดพลาดเพื่อดีบัก
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

session_start();
include('../conn/conn.php');

date_default_timezone_set('Asia/Bangkok');

// ตั้งค่า header ให้ระบุว่า response เป็น JSON
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leavesid = $_POST['leavesid'] ?? '';
    $userID = $_SESSION['ID'] ?? null;

    // ตรวจสอบว่า userID มีค่าหรือไม่
    if (empty($userID)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'ไม่พบข้อมูลผู้ใช้ใน session'
        ]);
        exit();
    }

    if (empty($leavesid)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'ข้อมูลไม่ครบถ้วน: leavesid หายไป'
        ]);
        exit();
    }

    // ดึงข้อมูลใบลา รวมถึงคอลัมน์ approver1
    $sql_leave = "SELECT leavestart, leavestatus, approver1 FROM leaves WHERE leavesid = ? AND employeesid = ?";
    $stmt_leave = $conn->prepare($sql_leave);
    if (!$stmt_leave) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Prepare statement failed: ' . $conn->error
        ]);
        exit();
    }
    $stmt_leave->bind_param('is', $leavesid,$userID);
    $stmt_leave->execute();
    $result_leave = $stmt_leave->get_result();
    $leave = $result_leave->fetch_assoc();
    $stmt_leave->close();

    if (!$leave) {
        echo json_encode([
            'status' => 'error',
            'message' => 'ไม่พบใบลานี้หรือคุณไม่มีสิทธิ์ยกเลิก'
        ]);
        exit();
    }

    // ตรวจสอบสถานะใบลา
    if (!in_array($leave['leavestatus'], ['รอหัวหน้าอนุมัติ', 'รอรองผอ.อนุมัติ', 'รอผอ.อนุมัติ', 'อนุมัติ', 'รออนุมัติ'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'ใบลานี้ไม่สามารถยกเลิกได้'
        ]);
        exit();
    }

    // ตรวจสอบเงื่อนไขวันเริ่มลา
    $startDate = new DateTime($leave['leavestart']);
    $currentDateTime = new DateTime();
    $currentDateTime->setTime(0, 0, 0); // ตั้งเวลาเป็น 00:00:00 เพื่อเปรียบเทียบเฉพาะวันที่

    $startDateMinusOne = clone $startDate;
    $startDateMinusOne->modify('-1 day');
    $startDateMinusOne->setTime(0, 0, 0);

    if ($currentDateTime > $startDateMinusOne) {
        // แปลงวันที่เป็น พ.ศ. เพื่อแสดงในข้อความ
        $startDateBE = $startDate->format('d/m/') . ($startDate->format('Y') + 543);
        echo json_encode([
            'status' => 'error',
            'message' => "ไม่สามารถยกเลิกได้ ต้องยกเลิกก่อนวันที่เริ่มลา ($startDateBE) อย่างน้อย 1 วัน"
        ]);
        exit();
    }

    // กำหนดวันที่สำหรับ send_cancel
    $approvedDate = date('Y-m-d');

    // อัพเดทสถานะและวันที่ send_cancel
    if ($leave['leavestatus'] == 'อนุมัติ') {
        $sql_update = "UPDATE leaves SET note = ?, leavestatus = ?, send_cancel = ? WHERE leavesid = ?";
        $note = 'ยกเลิกลาและคืนวันลา';
    } else {
        $sql_update = "UPDATE leaves SET note = ?, leavestatus = ?, send_cancel = ? WHERE leavesid = ?";
        $note = 'ยกเลิกลา';
    }

    $stmt_update = $conn->prepare($sql_update);
    if (!$stmt_update) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Prepare update statement failed: ' . $conn->error
        ]);
        exit();
    }

    // ตรวจสอบค่า approver1 เพื่อกำหนด leavestatus
    $leavestatus = ($leave['approver1'] !== null) ? 'รอหัวหน้าอนุมัติ' : 'รอผอ.อนุมัติ';

    // ผูกพารามิเตอร์
    $stmt_update->bind_param('sssi', $note, $leavestatus, $approvedDate, $leavesid);

    if ($stmt_update->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => "ยื่นยกเลิกใบลาสำเร็จ"
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => "ไม่สามารถยกเลิกใบลาได้: " . $stmt_update->error
        ]);
    }
    $stmt_update->close();
}

$conn->close();
?>