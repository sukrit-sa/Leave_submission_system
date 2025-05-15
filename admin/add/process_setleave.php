<?php
include('../conn/conn.php');  // Fix connection path

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ดีบั๊ก: ตรวจสอบค่า $_POST
    error_log("POST data: " . print_r($_POST, true));

    $employeeid = isset($_POST['employeeid']) ? intval($_POST['employeeid']) : 0;
    $address = isset($_POST['address']) ? $_POST['address'] : '';
    $leavetype = isset($_POST['leavetype']) ? intval($_POST['leavetype']) : 0;
    $leavestart = isset($_POST['leavestart']) ? $_POST['leavestart'] : '';
    $leaveend = isset($_POST['leaveend']) ? $_POST['leaveend'] : '';
    // ปรับการรับค่าให้ยอมรับ string ว่าง
    $send_date = isset($_POST['send_date']) ? $_POST['send_date'] : '';
    $approved_date1 = isset($_POST['approved_date1']) ? $_POST['approved_date1'] : NULL;
    $approved_date2 = isset($_POST['approved_date2']) ? $_POST['approved_date2'] : NULL;
    $approved_date3 = isset($_POST['approved_date3']) ? $_POST['approved_date3'] : NULL;
    $reasontext = isset($_POST['reasontext']) ? $_POST['reasontext'] : '';
    $note = 'ลา';
    $leavestatus = 'อนุมัติ';

    // 1. คำนวณจำนวนวันที่ลา (ไม่นับเสาร์-อาทิตย์ และวันหยุดในตาราง holiday)
    $startDate = new DateTime($leavestart);
    $endDate = new DateTime($leaveend);

    if ($startDate > $endDate) {
        echo json_encode(['status' => 'error', 'message' => 'วันที่สิ้นสุดต้องมากกว่าหรือเท่ากับวันที่เริ่มต้น']);
        exit;
    }

    // ดึงวันหยุดจากตาราง holiday ในช่วงวันที่ลา
    $sql_holiday = "SELECT holidayday FROM holiday WHERE holidayday BETWEEN ? AND ?";
    $stmt_holiday = $conn->prepare($sql_holiday);
    $stmt_holiday->bind_param("ss", $leavestart, $leaveend);
    $stmt_holiday->execute();
    $result_holiday = $stmt_holiday->get_result();

    $holidays = [];
    while ($row = $result_holiday->fetch_assoc()) {
        $holidays[] = $row['holidayday'];
    }
    $stmt_holiday->close();

    // คำนวณจำนวนวันที่ลา โดยไม่นับเสาร์-อาทิตย์ และวันหยุด
    $daysRequested = 0;
    $currentDate = clone $startDate;
    $debugDays = []; // เก็บข้อมูลวันสำหรับดีบั๊ก

    while ($currentDate <= $endDate) {
        $dayOfWeek = $currentDate->format('N'); // 1 (จันทร์) ถึง 7 (อาทิตย์)
        $currentDateStr = $currentDate->format('Y-m-d');

        if ($dayOfWeek < 6 && !in_array($currentDateStr, $holidays)) {
            $daysRequested++;
            $debugDays[] = "$currentDateStr (นับ)";
        } else {
            $reason = $dayOfWeek >= 6 ? "วันหยุดสุดสัปดาห์" : "วันหยุดในตาราง";
            $debugDays[] = "$currentDateStr (ไม่นับ: $reason)";
        }

        $currentDate->modify('+1 day');
    }

    // ดีบั๊ก: บันทึกวันที่ทั้งหมดที่คำนวณ
    error_log("Leave period: $leavestart to $leaveend");
    error_log("Days calculated: " . implode(", ", $debugDays));
    error_log("Total days requested: $daysRequested");

    // ถ้าไม่มีวันทำงานเลย (เช่น ลาทั้งหมดเป็นวันหยุด)
    if ($daysRequested == 0) {
        echo json_encode(['status' => 'error', 'message' => 'วันที่เลือกทั้งหมดเป็นวันหยุด ไม่สามารถลาได้']);
        exit;
    }

    // 2. ตรวจสอบการลาซ้ำซ้อน
    $check_sql = "SELECT * FROM leaves 
                  WHERE employeesid = ? 
                  AND ((leavestart BETWEEN ? AND ?) 
                  OR (leaveend BETWEEN ? AND ?)
                  OR (? BETWEEN leavestart AND leaveend)
                  OR (? BETWEEN leavestart AND leaveend))
                  AND leavestatus != 'rejected'";

    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("issssss", $employeeid, $leavestart, $leaveend, $leavestart, $leaveend, $leavestart, $leaveend);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'คุณมีการลาในช่วงเวลานี้แล้ว']);
        exit;
    }

    // 3. ตรวจสอบจำนวนวันที่ลาในตาราง leaveday
    $sql_leaveday = "SELECT day, stackleaveday FROM leaveday WHERE empid = ? AND leavetype = ?";
    $stmt_leaveday = $conn->prepare($sql_leaveday);
    $stmt_leaveday->bind_param("ii", $employeeid, $leavetype);
    $stmt_leaveday->execute();
    $result_leaveday = $stmt_leaveday->get_result();

    if ($result_leaveday->num_rows == 0) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลการลาสำหรับพนักงานและประเภทการลานี้']);
        exit;
    }

    $leaveday_data = $result_leaveday->fetch_assoc();
    $availableDays = $leaveday_data['day']; // จำนวนวันที่ลาประจำปี
    $stackedDays = $leaveday_data['stackleaveday']; // จำนวนวันที่ลาสะสม

    // 4. ตรวจสอบจำนวนวัน
    $totalAvailableDays = $availableDays + $stackedDays; // รวมวันที่มีทั้งหมด
    // if ($daysRequested > $totalAvailableDays) {
    //     // ถ้าจำนวนวันที่ลาเกินจากวันที่มีทั้งหมด
    //     $remainingDaysNeeded = $daysRequested - $totalAvailableDays;
    //     echo json_encode([
    //         'status' => 'error',
    //         'message' => "จำนวนวันที่ลาไม่เพียงพอ! คุณลา $daysRequested วัน, มีวันลาประจำปี $availableDays วัน, และวันลาสะสม $stackedDays วัน (รวม $totalAvailableDays วัน, ต้องการเพิ่ม $remainingDaysNeeded วัน)"
    //     ]);
    //     exit;
    // }

    // 5. เริ่ม transaction หากจำนวนวันเพียงพอ
    $conn->begin_transaction();

    try {
        // Get supervisor information from POST
        $supervisor = isset($_POST['supervisor']) && $_POST['supervisor'] !== '' ? intval($_POST['supervisor']) : null;
        $deputy = isset($_POST['deputy']) && $_POST['deputy'] !== '' ? intval($_POST['deputy']) : null;
        $director = isset($_POST['director']) && $_POST['director'] !== '' ? intval($_POST['director']) : null;

        // บันทึกข้อมูลการลา
        $sql = "INSERT INTO leaves (employeesid, address, leavetype, leavestart, leaveend, note, leavestatus, 
                approver1, approver2, approver3, day, send_date, approved_date1, approved_date2, approved_date3,reason) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssssiiiisssss", $employeeid, $address, $leavetype, $leavestart, $leaveend, 
                         $note, $leavestatus, $supervisor, $deputy, $director, $daysRequested, 
                         $send_date, $approved_date1, $approved_date2, $approved_date3,$reasontext);

        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }

        $leaveid = $conn->insert_id;
        $daysToDeductFromAnnual = 0;
        $daysToDeductFromStacked = 0;

        if ($availableDays >= $daysRequested) {
            // ถ้า day เพียงพอ
            $daysToDeductFromAnnual = $daysRequested;
        } else {
            // ถ้า day ไม่เพียงพอ
            $daysToDeductFromAnnual = $availableDays;
            $daysToDeductFromStacked = $daysRequested - $availableDays;
            if ($daysToDeductFromStacked > $stackedDays) {
                throw new Exception("จำนวนวันที่ลาสะสมไม่เพียงพอ! ต้องการ $daysToDeductFromStacked วัน แต่มี $stackedDays วัน");
            }
        }

        // อัพเดทตาราง leaveday
        $sql_update = "UPDATE leaveday SET day = GREATEST(0, day - ?), stackleaveday = GREATEST(0, stackleaveday - ?) WHERE empid = ? AND leavetype = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("iiii", $daysToDeductFromAnnual, $daysToDeductFromStacked, $employeeid, $leavetype);
        if (!$stmt_update->execute()) {
            throw new Exception("เกิดข้อผิดพลาดในการอัพเดทวันลา: " . $stmt_update->error);
        }

        // จัดการไฟล์แนบ
        if (!empty($_FILES['file']['name'][0])) {
            $uploadDir = "../../uploads/leaves/";  // ปรับ path ให้เหมาะสม
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            foreach ($_FILES['file']['tmp_name'] as $key => $tmp_name) {
                $file_name = $_FILES['file']['name'][$key];
                $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
                $new_file_name = time() . '_' . $key . '.' . $file_ext;
                $file_path = $uploadDir . $new_file_name;

                if (move_uploaded_file($tmp_name, $file_path)) {
                    // บันทึกข้อมูลไฟล์
                    $sql = "UPDATE leaves SET file = ? WHERE leavesid = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $new_file_name, $leaveid);
                    $stmt->execute();
                }
            }
        }

        $conn->commit();
        echo json_encode([
            'status' => 'success',
            'message' => 'บันทึกข้อมูลการลาสำเร็จ',
            'userID' => $employeeid  // เพิ่ม userID ใน response เพื่อใช้ในการ redirect
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        error_log($e->getMessage());  // Log the error
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    } finally {
        if (isset($stmt)) $stmt->close();
        if (isset($check_stmt)) $check_stmt->close();
        if (isset($stmt_leaveday)) $stmt_leaveday->close();
    }
}
?>