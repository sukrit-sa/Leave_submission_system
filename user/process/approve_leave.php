<?php
include('../conn/conn.php');

// เริ่ม session ถ้ายังไม่ได้เริ่ม
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบว่าเป็นผู้ใช้ที่ล็อกอินแล้ว
if (!isset($_SESSION['ID'])) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาล็อกอินก่อน']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leaveId = $_POST['leave_id'];
    $currentStatus = $_POST['current_status'];
    $employeesid = $_POST['employeesid'];
    $leavetype = $_POST['leavetype'];
    $leaveday = $_POST['leaveday'];
    $note = $_POST['note'];

    // ดึง ID ของผู้รับรองจาก session
    $approverId = $_SESSION['ID'];

    // ดึงข้อมูล approver1, approver2, approver3 และ leavestart จากตาราง leaves
    $sql_approver = "SELECT approver1, approver2, approver3, leavestart FROM leaves WHERE leavesid = ?";
    $stmt_approver = $conn->prepare($sql_approver);
    $stmt_approver->bind_param('i', $leaveId);
    $stmt_approver->execute();
    $result_approver = $stmt_approver->get_result();
    $leaveData = $result_approver->fetch_assoc();
    $stmt_approver->close();

    if (!$leaveData) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลใบลานี้']);
        exit();
    }

    // ดึงข้อมูล yearend2 จากตาราง year
    $sql_year = "SELECT yearend2 FROM year LIMIT 1";
    $result_year = $conn->query($sql_year);
    if (!$result_year) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถดึงข้อมูลปีงบประมาณได้']);
        exit();
    }
    $yearData = $result_year->fetch_assoc();
    $yearEnd2 = new DateTime($yearData['yearend2']);
    $leaveStart = new DateTime($leaveData['leavestart']);

    // ตรวจสอบว่า approverId อยู่ในคอลัมน์ใด
    $approvedDateColumn = null;
    if ($leaveData['approver1'] == $approverId) {
        $approvedDateColumn = 'approved_date1';
    } elseif ($leaveData['approver2'] == $approverId) {
        $approvedDateColumn = 'approved_date2';
    } elseif ($leaveData['approver3'] == $approverId) {
        $approvedDateColumn = 'approved_date3';
    }

    // ถ้าไม่พบ approverId ในคอลัมน์ใด ๆ
    if (!$approvedDateColumn) {
        echo json_encode(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์อนุมัติใบลานี้']);
        exit();
    }

    // วันที่รับรอง (วันที่ปัจจุบัน)
    $approvedDate = date('Y-m-d H:i:s');

    // Update leave status based on current status
    switch ($currentStatus) {
        case 'รอหัวหน้าอนุมัติ':
            if ($note == 'ยกเลิกลาและคืนวันลา' || $note == 'ยกเลิกลา') {
                $newStatus = 'รอผอ.อนุมัติ';
                $sql = "UPDATE leaves SET leavestatus = ?, approved_cancel1 = ? WHERE leavesid = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ssi', $newStatus, $approvedDate, $leaveId);
            }
            if ($note == 'ลา') {
                $newStatus = 'รอรองผอ.อนุมัติ';
                $sql = "UPDATE leaves SET leavestatus = ?, $approvedDateColumn = ? WHERE leavesid = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ssi', $newStatus, $approvedDate, $leaveId);
            }
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'อนุมัติการลาเรียบร้อยแล้ว']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการอนุมัติ']);
            }
            $stmt->close();
            break;

        case 'รอรองผอ.อนุมัติ':
            $newStatus = 'รอผอ.อนุมัติ';
            $sql = "UPDATE leaves SET leavestatus = ?, $approvedDateColumn = ? WHERE leavesid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssi', $newStatus, $approvedDate, $leaveId);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'อนุมัติการลาเรียบร้อยแล้ว']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการอนุมัติ']);
            }
            $stmt->close();
            break;

        case 'รอผอ.อนุมัติ':
            // เริ่ม Transaction
            $conn->begin_transaction();

            try {
                if ($note == 'ลา') {
                    // 1. ดึงข้อมูลจากตาราง leaveday
                    $sql_leaveday = "SELECT day, stackleaveday, staffstatus FROM leaveday WHERE empid = ? AND leavetype = ?";
                    $stmt_leaveday = $conn->prepare($sql_leaveday);
                    $stmt_leaveday->bind_param("ii", $employeesid, $leavetype);
                    $stmt_leaveday->execute();
                    $result_leaveday = $stmt_leaveday->get_result();

                    if ($result_leaveday->num_rows == 0) {
                        throw new Exception('ไม่พบข้อมูลการลาสำหรับพนักงานและประเภทการลานี้');
                    }

                    $leaveday_data = $result_leaveday->fetch_assoc();
                    $availableDays = $leaveday_data['day']; // จำนวนวันที่ลาประจำปี
                    $stackedDays = $leaveday_data['stackleaveday']; // จำนวนวันที่ลาสะสม
                    $staffstatus = $leaveday_data['staffstatus'];

                    // 2. ตรวจสอบว่า leavestart อยู่หลัง yearend2 หรือไม่
                    if ($leaveStart > $yearEnd2) {
                        // อัพเดท pending_deduction_days ในตาราง leaveday
                        $sql_update_pending = "UPDATE leaveday SET pending_deduction_days = pending_deduction_days + ? WHERE empid = ? AND leavetype = ? AND staffstatus = ?";
                        $stmt_update_pending = $conn->prepare($sql_update_pending);
                        $stmt_update_pending->bind_param('iiii', $leaveday, $employeesid, $leavetype, $staffstatus);
                        if (!$stmt_update_pending->execute()) {
                            throw new Exception("เกิดข้อผิดพลาดในการอัพเดท pending_deduction_days: " . $stmt_update_pending->error);
                        }
                        $stmt_update_pending->close();

                        // 3. อัพเดท leaves (รวมวันที่รับรอง)
                        $newStatus = 'อนุมัติ';
                        $sql = "UPDATE leaves SET leavestatus = ?, $approvedDateColumn = ? WHERE leavesid = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param('ssi', $newStatus, $approvedDate, $leaveId);
                        if (!$stmt->execute()) {
                            throw new Exception("เกิดข้อผิดพลาดในการอัพเดทสถานะการลา: " . $stmt->error);
                        }

                        $conn->commit();
                        echo json_encode(['status' => 'success', 'message' => 'อนุมัติการลาเรียบร้อยแล้ว และรอการหักวันลาตามปีงบประมาณ']);
                    } else {
                        // กรณี leavestart ไม่เกิน yearend2 ให้หักวันลาทันที
                        // 2. คำนวณการหักวัน (หัก day ก่อน stackleaveday)
                        $daysToDeductFromAnnual = 0;
                        $daysToDeductFromStacked = 0;

                        if ($availableDays >= $leaveday) {
                            // ถ้า day เพียงพอ
                            $daysToDeductFromAnnual = $leaveday;
                        } else {
                            // ถ้า day ไม่เพียงพอ
                            $daysToDeductFromAnnual = $availableDays;
                            $daysToDeductFromStacked = $leaveday - $availableDays;
                            if ($daysToDeductFromStacked > $stackedDays) {
                                throw new Exception("จำนวนวันที่ลาสะสมไม่เพียงพอ! ต้องการ $daysToDeductFromStacked วัน แต่มี $stackedDays วัน");
                            }
                        }

                        // 3. อัพเดท leaves (รวมวันที่รับรอง)
                        $newStatus = 'อนุมัติ';
                        $sql = "UPDATE leaves SET leavestatus = ?, $approvedDateColumn = ? WHERE leavesid = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param('ssi', $newStatus, $approvedDate, $leaveId);
                        if (!$stmt->execute()) {
                            throw new Exception("เกิดข้อผิดพลาดในการอัพเดทสถานะการลา: " . $stmt->error);
                        }

                        // 4. อัพเดท leaveday
                        $sql2 = "UPDATE leaveday SET day = day - ?, stackleaveday = stackleaveday - ? WHERE leavetype = ? AND empid = ?";
                        $stmt2 = $conn->prepare($sql2);
                        $stmt2->bind_param('iiii', $daysToDeductFromAnnual, $daysToDeductFromStacked, $leavetype, $employeesid);
                        if (!$stmt2->execute()) {
                            throw new Exception("เกิดข้อผิดพลาดในการอัพเดทวันลา: " . $stmt2->error);
                        }

                        $conn->commit();
                        echo json_encode(['status' => 'success', 'message' => 'อนุมัติการลาเรียบร้อยแล้ว']);
                    }
                } else if ($note == 'ยกเลิกลาและคืนวันลา') {
                    // 1. ดึงข้อมูลจากตาราง leavetype เพื่อหาค่า leaveofyear และ stackleaveday_max
                    $sql_leavetype = "SELECT leaveofyear, stackleaveday AS stackleaveday_max FROM leavetype WHERE leavetypeid = ?";
                    $stmt_leavetype = $conn->prepare($sql_leavetype);
                    $stmt_leavetype->bind_param("i", $leavetype);
                    $stmt_leavetype->execute();
                    $result_leavetype = $stmt_leavetype->get_result();

                    if ($result_leavetype->num_rows == 0) {
                        throw new Exception('ไม่พบข้อมูลประเภทการลานี้ในตาราง leavetype');
                    }

                    $leavetype_data = $result_leavetype->fetch_assoc();
                    $maxAnnualDays = $leavetype_data['leaveofyear']; // จำนวนวันสูงสุดของวันลาประจำปี
                    $maxStackedDays = $leavetype_data['stackleaveday_max']; // จำนวนวันสูงสุดของวันลาสะสม

                    // 2. ดึงข้อมูลจากตาราง leaveday รวมถึง pending_deduction_days
                    $sql_leaveday = "SELECT day, stackleaveday, pending_deduction_days FROM leaveday WHERE empid = ? AND leavetype = ?";
                    $stmt_leaveday = $conn->prepare($sql_leaveday);
                    $stmt_leaveday->bind_param("ii", $employeesid, $leavetype);
                    $stmt_leaveday->execute();
                    $result_leaveday = $stmt_leaveday->get_result();

                    if ($result_leaveday->num_rows == 0) {
                        throw new Exception('ไม่พบข้อมูลการลาสำหรับพนักงานและประเภทการลานี้');
                    }

                    $leaveday_data = $result_leaveday->fetch_assoc();
                    $currentDays = $leaveday_data['day']; // จำนวนวันที่ลาประจำปีปัจจุบัน
                    $stackedDays = $leaveday_data['stackleaveday']; // จำนวนวันที่ลาสะสม
                    $pendingDeductionDays = $leaveday_data['pending_deduction_days'] ?? 0; // จำนวนวันที่รอหัก

                    // 3. ตรวจสอบว่า leavestart อยู่หลัง yearend2 หรือไม่
                    if ($leaveStart > $yearEnd2) {
                        // ลด pending_deduction_days ตามจำนวนวันที่คืน (leaveday)
                        $newPendingDeductionDays = max(0, $pendingDeductionDays - $leaveday); // ป้องกันไม่ให้ติดลบ
                        $sql_update_pending = "UPDATE leaveday SET pending_deduction_days = ? WHERE empid = ? AND leavetype = ?";
                        $stmt_update_pending = $conn->prepare($sql_update_pending);
                        $stmt_update_pending->bind_param('iii', $newPendingDeductionDays, $employeesid, $leavetype);
                        if (!$stmt_update_pending->execute()) {
                            throw new Exception("เกิดข้อผิดพลาดในการอัพเดท pending_deduction_days: " . $stmt_update_pending->error);
                        }
                        $stmt_update_pending->close();
                    }

                    // 4. คำนวณการคืนวัน (คืนให้ day ก่อน stackleaveday)
                    // คืนให้ day ก่อน
                    $remainingCapacityInAnnual = $maxAnnualDays - $currentDays; // จำนวนวันที่สามารถคืนได้ใน day
                    $daysToAddToAnnual = min($leaveday, $remainingCapacityInAnnual); // คืนเท่าที่จำเป็น
                    $remainingDays = $leaveday - $daysToAddToAnnual; // วันที่เหลือหลังจากคืน day

                    // ถ้ามีวันเหลือ คืนให้ stackleaveday
                    $daysToAddToStacked = 0;
                    if ($remainingDays > 0) {
                        $remainingCapacityInStacked = $maxStackedDays - $stackedDays; // จำนวนวันที่สามารถคืนได้ใน stackleaveday
                        $daysToAddToStacked = min($remainingDays, $remainingCapacityInStacked); // คืนเท่าที่จำเป็น
                    }

                    // 5. อัพเดท leaves (รวมวันที่รับรอง)
                    $newStatus = 'อนุมัติ';
                    $sql = "UPDATE leaves SET leavestatus = ?, approved_cancel3 = ? WHERE leavesid = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('ssi', $newStatus, $approvedDate, $leaveId);
                    if (!$stmt->execute()) {
                        throw new Exception("เกิดข้อผิดพลาดในการอัพเดทสถานะการลา: " . $stmt->error);
                    }

                    // 6. อัพเดท leaveday
                    $sql2 = "UPDATE leaveday SET day = day + ?, stackleaveday = stackleaveday + ? WHERE leavetype = ? AND empid = ?";
                    $stmt2 = $conn->prepare($sql2);
                    $stmt2->bind_param('iiii', $daysToAddToAnnual, $daysToAddToStacked, $leavetype, $employeesid);
                    if (!$stmt2->execute()) {
                        throw new Exception("เกิดข้อผิดพลาดในการอัพเดทวันลา: " . $stmt2->error);
                    }

                    $conn->commit();
                    echo json_encode(['status' => 'success', 'message' => 'อนุมัติการยกเลิกและคืนวันลาเรียบร้อยแล้ว']);
                } else {
                    // กรณีอื่นๆ (เช่น note == 'ยกเลิกลา')
                    $newStatus = 'อนุมัติ';
                    $sql = "UPDATE leaves SET leavestatus = ?, approved_cancel3 = ? WHERE leavesid = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('ssi', $newStatus, $approvedDate, $leaveId);
                    
                    if ($stmt->execute()) {
                        $conn->commit();
                        echo json_encode(['status' => 'success', 'message' => 'อนุมัติการยกเลิกลาเรียบร้อยแล้ว']);
                    } else {
                        throw new Exception('เกิดข้อผิดพลาดในการอนุมัติ');
                    }
                }
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            } finally {
                if (isset($stmt)) $stmt->close();
                if (isset($stmt2)) $stmt2->close();
                if (isset($stmt_leaveday)) $stmt_leaveday->close();
                if (isset($stmt_leavetype)) $stmt_leavetype->close();
            }
            break;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>