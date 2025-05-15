<?php
// เปิดการดีบั๊กชั่วคราว (ลบออกใน production)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// เริ่ม output buffering เพื่อป้องกัน output ที่ไม่ต้องการ
ob_start();

include('../conn/conn.php');  // Fix connection path

// ตั้งค่า header ให้ถูกต้อง
header('Content-Type: application/json; charset=UTF-8');

// ล้าง output buffer ถ้ามี
if (ob_get_length()) {
    ob_end_clean();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employeeid = $_POST['employeeid'];
    $address = $_POST['address'];
    $leavetype = $_POST['leavetype'];
    $leavestart = $_POST['leavestart'];
    $leaveend = $_POST['leaveend'];
    $leavestatus = 'รออนุมัติ';
    $reason = $_POST['reason'];
    $note = 'ลา';

    $sendDate = date('Y-m-d H:i:s');
    error_log("Step 1: Start processing leave request for employee $employeeid");

    // 1. ดึงช่วงปีงบประมาณจากตาราง year
    $sql_year = "SELECT yearstart1, yearend2 FROM year LIMIT 1";
    $result_year = $conn->query($sql_year);
    if ($result_year->num_rows == 0) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลช่วงปีงบประมาณ']);
        exit;
    }
    $year_data = $result_year->fetch_assoc();
    $yearstart1 = $year_data['yearstart1'];
    $yearend2 = $year_data['yearend2'];
    error_log("Step 2: Retrieved fiscal year: $yearstart1 to $yearend2");

    // 2. คำนวณจำนวนวันที่ลา (ไม่นับเสาร์-อาทิตย์ และวันหยุดในตาราง holiday)
    $startDate = new DateTime($leavestart);
    $endDate = new DateTime($leaveend);

    if ($startDate > $endDate) {
        echo json_encode(['status' => 'error', 'message' => 'วันที่สิ้นสุดต้องมากกว่าหรือเท่ากับวันที่เริ่มต้น']);
        exit;
    }

    // ตรวจสอบว่า leavestart และ leaveend อยู่ในช่วงปีงบประมาณหรือไม่
    // if ($leavestart < $yearstart1 || $leaveend > $yearend2) {
    //     echo json_encode(['status' => 'error', 'message' => 'วันที่ลาต้องอยู่ในช่วงปีงบประมาณ (' . $yearstart1 . ' ถึง ' . $yearend2 . ')']);
    //     exit;
    // }

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
    error_log("Step 3: Retrieved holidays: " . implode(", ", $holidays));

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
    error_log("Step 4: Leave period: $leavestart to $leaveend");
    error_log("Days calculated: " . implode(", ", $debugDays));
    error_log("Total days requested: $daysRequested");

    // ถ้าไม่มีวันทำงานเลย (เช่น ลาทั้งหมดเป็นวันหยุด)
    if ($daysRequested == 0) {
        echo json_encode(['status' => 'error', 'message' => 'วันที่เลือกทั้งหมดเป็นวันหยุด ไม่สามารถลาได้']);
        exit;
    }

    // 3. ตรวจสอบการลาซ้ำซ้อน
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
    error_log("Step 5: No overlapping leave requests");

    // 4. ตรวจสอบจำนวนวันที่ลาในตาราง leaveday
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
    error_log("Step 6: Available days: $availableDays, Stacked days: $stackedDays");

    // 5. ตรวจสอบจำนวนวัน
    $totalAvailableDays = $availableDays + $stackedDays; // รวมวันที่มีทั้งหมด
    if ($daysRequested > $totalAvailableDays) {
        // ถ้าจำนวนวันที่ลาเกินจากวันที่มีทั้งหมด
        $remainingDaysNeeded = $daysRequested - $totalAvailableDays;
        echo json_encode([
            'status' => 'error',
            'message' => "จำนวนวันที่ลาไม่เพียงพอ! คุณลา $daysRequested วัน, มีวันลาประจำปี $availableDays วัน, และวันลาสะสม $stackedDays วัน (รวม $totalAvailableDays วัน, ต้องการเพิ่ม $remainingDaysNeeded วัน)"
        ]);
        exit;
    }

    // 6. ตรวจสอบประเภทการลาและนับจำนวนครั้ง/วัน
    // ดึงชื่อประเภทการลา
    $sql_leavetype = "SELECT leavetypename FROM leavetype WHERE leavetypeid = ?";
    $stmt_leavetype = $conn->prepare($sql_leavetype);
    $stmt_leavetype->bind_param("i", $leavetype);
    $stmt_leavetype->execute();
    $result_leavetype = $stmt_leavetype->get_result();
    if ($result_leavetype->num_rows == 0) {
        echo json_encode(['status' => 'error', 'message' => ' ไม่พบประเภทการลานี้']);
        exit;
    }
    $leavetype_data = $result_leavetype->fetch_assoc();
    $leavetypename = $leavetype_data['leavetypename'];
    $stmt_leavetype->close();
    error_log("Step 7: Leave type: $leavetypename");

    // ถ้าเป็น "ลาพักผ่อน" ให้ตรวจสอบเงื่อนไขเพิ่มเติม
    if ($leavetypename == 'ลาป่วย'|| $leavetypename == 'ลากิจ' ) {
        // นับจำนวนครั้งและจำนวนวันที่ลาในช่วงปีงบประมาณ
        $sql_count_leaves = "SELECT COUNT(*) as leave_count, SUM(day) as total_days 
                            FROM leaves 
                            WHERE employeesid = ? 
                            AND leavetype = ? 
                            AND leavestart >= ? 
                            AND leaveend <= ? 
                            AND leavestatus = 'อนุมัติ'";
        $stmt_count_leaves = $conn->prepare($sql_count_leaves);
        $stmt_count_leaves->bind_param("iiss", $employeeid, $leavetype, $yearstart1, $yearend2);
        $stmt_count_leaves->execute();
        $result_count_leaves = $stmt_count_leaves->get_result();
        $leave_stats = $result_count_leaves->fetch_assoc();
        $leave_count = $leave_stats['leave_count'] ?? 0; // จำนวนครั้ง
        $total_days = $leave_stats['total_days'] ?? 0; // จำนวนวันรวม
        $stmt_count_leaves->close();

        // เพิ่มจำนวนวันของการลาครั้งนี้
        $new_total_days = $total_days + $daysRequested;

        // ตรวจสอบเงื่อนไข: ถ้าจำนวนครั้งครบ 9 หรือจำนวนวันรวมครบ 23
        if ($leave_count >= 9 || $new_total_days >= 23) {
            $note = 'ลาเกินกำหนด';
            $leavestatus = 'รอแอดมินอนุมัติ'; // ตั้งค่า leavestatus เป็น "รอแอดมินอนุมัติ"
            error_log("Employee $employeeid, Leave type 'ลาพักผ่อน': Leave count = $leave_count, Total days = $new_total_days, Note changed to 'ลาเกินกำหนด', Status changed to 'รอแอดมินอนุมัติ'");
        }
        error_log("Step 8: Leave count: $leave_count, Total days: $new_total_days, Note: $note");
    }

    // 7. เริ่ม transaction หากจำนวนวันเพียงพอ
    $conn->begin_transaction();
    error_log("Step 9: Transaction started");

    try {
        // Get supervisor information from POST
        $supervisor = !empty($_POST['supervisor']) ? intval($_POST['supervisor']) : null; // ใช้ 0 ถ้าไม่เลือก
        $deputy = !empty($_POST['deputy']) ? intval($_POST['deputy']) : null;
        $director = !empty($_POST['director']) ? intval($_POST['director']) : null;
        error_log("Step 10: Supervisor: " . ($supervisor ?? 'null') . ", Deputy: " . ($deputy ?? 'null') . ", Director: " . ($director ?? 'null'));

        // Set initial status based on approval chain, but only if not already set by "ลาพักผ่อน" condition
        if ($leavestatus !== 'รอแอดมินอนุมัติ') { // ตรวจสอบว่า leavestatus ยังไม่ถูกตั้งโดยเงื่อนไข "ลาพักผ่อน"
            if ($supervisor) {
                $leavestatus = 'รอหัวหน้าอนุมัติ';
            } elseif ($deputy) {
                $leavestatus = 'รอรองผอ.อนุมัติ';
            } else {
                $leavestatus = 'รอผอ.อนุมัติ';
            }
        }
        error_log("Step 11: Leave status set to: $leavestatus");

        // บันทึกข้อมูลการลา
        $sql = "INSERT INTO leaves (employeesid, address, leavetype, leavestart, leaveend, note, leavestatus, 
                approver1, approver2, approver3, day, send_date,reason) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssssiiiiss", $employeeid, $address, $leavetype, $leavestart, $leaveend, 
                         $note, $leavestatus, $supervisor, $deputy, $director, $daysRequested, $sendDate,$reason);

        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }

        $leaveid = $conn->insert_id;
        error_log("Step 12: Leave inserted with ID: $leaveid");

        // จัดการไฟล์แนบ
        if (!empty($_FILES['file']['name'][0])) {
            $uploadDir = "../../uploads/leaves/";  // Fix upload path
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    throw new Exception("ไม่สามารถสร้างโฟลเดอร์สำหรับอัปโหลดไฟล์ได้: $uploadDir");
                }
            }

            // ตรวจสอบสิทธิ์ของโฟลเดอร์
            if (!is_writable($uploadDir)) {
                throw new Exception("ไม่มีสิทธิ์ในการเขียนไฟล์ในโฟลเดอร์: $uploadDir");
            }

            $filesUploaded = [];
            foreach ($_FILES['file']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['file']['error'][$key] !== UPLOAD_ERR_OK) {
                    throw new Exception("ข้อผิดพลาดในการอัปโหลดไฟล์: " . $_FILES['file']['error'][$key]);
                }

                $file_name = $_FILES['file']['name'][$key];
                $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
                $new_file_name = time() . '_' . $key . '.' . $file_ext;
                $file_path = $uploadDir . $new_file_name;

                if (move_uploaded_file($tmp_name, $file_path)) {
                    // บันทึกข้อมูลไฟล์
                    $sql = "UPDATE leaves SET file = ? WHERE leavesid = ?";
                    $stmt_update = $conn->prepare($sql);
                    $stmt_update->bind_param("si", $new_file_name, $leaveid);
                    if (!$stmt_update->execute()) {
                        throw new Exception("Error updating file: " . $stmt_update->error);
                    }
                    $stmt_update->close();
                    $filesUploaded[] = $new_file_name;
                    error_log("Step 13: File uploaded: $new_file_name for leave ID: $leaveid");
                } else {
                    throw new Exception("ไม่สามารถย้ายไฟล์ไปยังโฟลเดอร์: $file_path");
                }
            }
            error_log("Step 13: All files uploaded: " . implode(", ", $filesUploaded));
        }

        $conn->commit();
        error_log("Step 14: Transaction committed");

        // ส่งข้อความแจ้งเตือนตามสถานะ
        if ($leavestatus === 'รอแอดมินอนุมัติ') {
            echo json_encode([
                'status' => 'success',
                'message' => 'ตอนนี้คุณได้ทำการ'.$leavetypename.'ครบ 8 ครั้ง หรือ 23 วันแล้ว โปรดรอแอดมินอนุมัติ'
            ]);
        } else {
            echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลการลาสำเร็จ']);
        }
        error_log("Step 15: Success response sent");
        exit; // ออกทันทีหลังจากส่ง JSON เพื่อป้องกัน output เพิ่มเติม
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        error_log("Step 16: Error response sent");
        exit; // ออกทันทีหลังจากส่ง JSON เพื่อป้องกัน output เพิ่มเติม
    } finally {
        // ตรวจสอบก่อนปิด statement เพื่อป้องกัน error
        if (isset($stmt) && $stmt instanceof mysqli_stmt) $stmt->close();
        if (isset($check_stmt) && $check_stmt instanceof mysqli_stmt) $check_stmt->close();
        if (isset($stmt_leaveday) && $stmt_leaveday instanceof mysqli_stmt) $stmt_leaveday->close();
        if (isset($stmt_leavetype) && $stmt_leavetype instanceof mysqli_stmt) $stmt_leavetype->close();
        if (isset($stmt_count_leaves) && $stmt_count_leaves instanceof mysqli_stmt) $stmt_count_leaves->close();
        if (isset($stmt_update) && $stmt_update instanceof mysqli_stmt) $stmt_update->close();
        error_log("Step 17: Statements closed");
    }
}
error_log("Step 18: Script completed");
exit; // ออกทันทีเพื่อป้องกัน output เพิ่มเติม
?>