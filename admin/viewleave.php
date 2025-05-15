<?php
session_start();
ob_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');
set_time_limit(0);

include('conn/conn.php');
date_default_timezone_set('Asia/Bangkok');
$customCurrentDate = new DateTime('');

// ดึงข้อมูลจากตาราง year
$sql_year = "SELECT yearstart1, yearend1, yearstart2, yearend2, `update` FROM year LIMIT 1";
$result_year = $conn->query($sql_year);
if (!$result_year) {
    die("Error fetching year data: " . $conn->error);
}
$yearData = $result_year->fetch_assoc();

$yearStart1 = new DateTime($yearData['yearstart1']);
$yearEnd1 = new DateTime($yearData['yearend1']);
$yearStart2 = new DateTime($yearData['yearstart2']);
$yearEnd2 = new DateTime($yearData['yearend2']);
$lastUpdate = !is_null($yearData['update']) ? new DateTime($yearData['update']) : null;

// ตรวจสอบช่วงเวลา
$isInPeriod = ($customCurrentDate >= $yearStart1 && $customCurrentDate <= $yearEnd2);
$canUpdateStackedDays = true;
if ($isInPeriod || ($lastUpdate !== null && $customCurrentDate <= $yearEnd2)) {
    $canUpdateStackedDays = false;
}
// ดึงข้อมูลการลาที่อนุมัติจากตาราง leaves และนับจำนวนครั้งที่ลา
$sql_leaves = "
    SELECT l.employeesid, lt.leavetypename, COUNT(*) as leave_count
    FROM leaves l
    JOIN leavetype lt ON l.leavetype = lt.leavetypeid
    WHERE l.leavestatus = 'อนุมัติ'
    AND l.leavestart BETWEEN ? AND ?
    GROUP BY l.employeesid, lt.leavetypename
";
$stmt_leaves = $conn->prepare($sql_leaves);
$stmt_leaves->bind_param(
    'ss',
    $yearStart1->format('Y-m-d'),
    $yearEnd2->format('Y-m-d')
);
if (!$stmt_leaves->execute()) {
    error_log("Failed to execute leaves query: " . $stmt_leaves->error);
    $leaveCountByEmployee = [];
} else {
    $result_leaves = $stmt_leaves->get_result();
    $leaveCountByEmployee = [];
    while ($row = $result_leaves->fetch_assoc()) {
        $leaveCountByEmployee[$row['employeesid']][$row['leavetypename']] = $row['leave_count'];
    }
}
$stmt_leaves->close();

// ดึงข้อมูลพนักงาน
$sql_employees = "SELECT id, fname, lname, staffstatus, startwork, startappoint FROM employees";
$result_employees = $conn->query($sql_employees);
if (!$result_employees) {
    die("Error fetching employees: " . $conn->error);
}

$employees = [];
$totalEmployees = 0;
while ($row = $result_employees->fetch_assoc()) {
    $startDateString = !is_null($row['startappoint']) ? $row['startappoint'] : $row['startwork'];
    if (!$startDateString) {
        error_log("Employee ID {$row['id']}: startwork and startappoint are null");
        continue;
    }
    $startDate = new DateTime($startDateString);
    $totalMonths = max(0, $customCurrentDate->diff($startDate)->y * 12 + $customCurrentDate->diff($startDate)->m);

    $employees[$row['id']] = [
        'fullname' => $row['fname'] . ' ' . $row['lname'],
        'staffstatus' => $row['staffstatus'],
        'totalMonths' => $totalMonths
    ];
    $totalEmployees++;
}

// ดึงข้อมูล leavetype
$sql_leavetype = "SELECT leavetypeid, leavetypename, staffid, leaveofyear, stackleaveday, workage, workage_type, workageday FROM leavetype";
$result_leavetype = $conn->query($sql_leavetype);
if (!$result_leavetype) {
    die("Error fetching leavetype: " . $conn->error);
}

$leaveTypes = [];
$leavetypeDetails = [];
$leavetypeNames = [];
while ($row = $result_leavetype->fetch_assoc()) {
    $leaveTypes[$row['staffid']][$row['leavetypename']][] = $row;  //เก็บข้อมูลตามที่เลือก   leavetypeid, leavetypename, staffid, leaveofyear, stackleaveday, workage, workage_type, workageday
    $leavetypeDetails[$row['leavetypeid']] = [
        'leaveofyear' => $row['leaveofyear'] ?? 0,
        'stackleaveday' => $row['stackleaveday'] ?? 0,
        'leavetypename' => $row['leavetypename'],
        'staffid' => $row['staffid']
    ];
    $leavetypeNames[$row['leavetypeid']] = $row['leavetypename'];
}

// ดึงข้อมูล staffstatus
$sql_staffstatus = "SELECT staffid, staffname FROM staffstatus";
$result_staffstatus = $conn->query($sql_staffstatus);
if (!$result_staffstatus) {
    die("Error fetching staffstatus: " . $conn->error);
}
$staffstatusNames = [];
while ($row = $result_staffstatus->fetch_assoc()) {
    $staffstatusNames[$row['staffid']] = $row['staffname'];
}

// ฟังก์ชันเลือก leavetype
function selectLeaveType($leaves, $totalMonths) // ข้อมูลที่ถูกเลือกตาม $staffstatus , อายุงาน
{
    $selectedLeave = null;
    foreach ($leaves as $leave) {
        if (!isset($leave['workageday']) || !isset($leave['workage'])) {
            error_log("Missing workageday or workage for leavetypeid: {$leave['leavetypeid']}");
            continue;
        }
        if ($leave['workage'] == 3) {
            $selectedLeave = $leave;
            error_log("Selected leavetypeid: {$leave['leavetypeid']} (workage = 3)");
            break;
        }
    }
    if ($selectedLeave === null) {
        foreach ($leaves as $leave) {
            if (!isset($leave['workageday']) || !isset($leave['workage'])) continue;
            if ($leave['workage'] == 2 && $totalMonths < $leave['workageday']) {
                $selectedLeave = $leave;
                error_log("Selected leavetypeid: {$leave['leavetypeid']} (workage = 2, totalMonths = $totalMonths, workageday = {$leave['workageday']})");
                break;
            }
        }
    }
    if ($selectedLeave === null) {
        foreach ($leaves as $leave) {
            if (!isset($leave['workageday']) || !isset($leave['workage'])) continue;
            if ($leave['workage'] == 1 && $totalMonths >= $leave['workageday']) {
                $selectedLeave = $leave;
                error_log("Selected leavetypeid: {$leave['leavetypeid']} (workage = 1, totalMonths = $totalMonths, workageday = {$leave['workageday']})");
                break;
            }
        }
    }
    return $selectedLeave;
}

// ตัวแปรสำหรับปุ่ม "อัพเดทข้อมูล"
$leaveDataForUpdate = [];
$usedLeavetypenamesForUpdate = [];
$sql_leaveday_for_update = "SELECT ld.leavedayid, ld.empid, ld.leavetype, ld.staffstatus, ld.day, ld.stackleaveday, ld.pending_deduction_days, e.fname, e.lname, e.startwork, e.startappoint 
                           FROM leaveday ld JOIN employees e ON ld.empid = e.id";
$result_leaveday_for_update = $conn->query($sql_leaveday_for_update);
if (!$result_leaveday_for_update) {
    die("Error fetching leaveday for update: " . $conn->error);
}
while ($row = $result_leaveday_for_update->fetch_assoc()) {
    $startDateString = !is_null($row['startappoint']) ? $row['startappoint'] : $row['startwork'];
    $startDate = new DateTime($startDateString);
    $totalMonths = max(0, $customCurrentDate->diff($startDate)->y * 12 + $customCurrentDate->diff($startDate)->m);
    $leaveDataForUpdate[$row['empid']][$row['leavetype']] = [
        'leavedayid' => $row['leavedayid'],
        'fullname' => $row['fname'] . ' ' . $row['lname'],
        'leavetype' => $row['leavetype'],
        'staffstatus' => $row['staffstatus'],
        'day' => $row['day'] ?? 0,
        'stackleaveday' => $row['stackleaveday'] ?? 0,
        'pending_deduction_days' => $row['pending_deduction_days'] ?? 0,
        'totalMonths' => $totalMonths
    ];
    $leavetypename = $leavetypeNames[$row['leavetype']] ?? '';
    if (!empty($leavetypename) && !in_array($leavetypename, $usedLeavetypenamesForUpdate)) {
        $usedLeavetypenamesForUpdate[] = $leavetypename;
    }
}
sort($usedLeavetypenamesForUpdate);

// ฟังก์ชันประมวลผล batch สำหรับอัพเดท leavetype
function processBatch($conn, $batch, $batchSize, $employees, $leaveTypes, $leavetypeNames, &$leaveDataForUpdate, &$usedLeavetypenamesForUpdate)
{
    $updateCount = 0;
    $updateMessages = [];
    $offset = $batch * $batchSize;
    $batchEmployees = array_slice($employees, $offset, $batchSize, true);

    if (empty($batchEmployees)) {
        return ['updateCount' => 0, 'updateMessages' => ["Batch $batch ว่างเปล่า"]];
    }

    $conn->begin_transaction();
    try {
        foreach ($batchEmployees as $empID => $empData) {
            if (empty($empID)) {
                $updateMessages[] = "พนักงาน ID ว่างเปล่า: ไม่สามารถดำเนินการได้";
                error_log("Empty empID detected");
                continue;
            }
            $staffstatus = $empData['staffstatus']; // 1
            $totalMonths = $empData['totalMonths'];
            $fullname = $empData['fullname'];

            if (!isset($leaveTypes[$staffstatus])) {
                $updateMessages[] = "พนักงาน ID $empID ($fullname): ไม่พบ leaveTypes สำหรับ staffstatus $staffstatus";
                error_log("No leaveTypes for staffstatus $staffstatus for empID $empID");
                continue;
            }

            foreach ($leaveTypes[$staffstatus] as $leaveTypeName => $leaves) { //แสดงข้อมูลที่ $staffstatus จัดกลุ่มประเภทวันลาตาม $leaveTypeName
                $selectedLeave = selectLeaveType($leaves, $totalMonths);  //
                if (!$selectedLeave) {
                    $updateMessages[] = "พนักงาน ID $empID ($fullname): ข้าม $leaveTypeName เนื่องจากไม่พบ leavetype ที่เหมาะสม";
                    error_log("No suitable leavetype for $leaveTypeName for empID $empID, totalMonths: $totalMonths");
                    continue;
                } 
                $newLeavetype = $selectedLeave['leavetypeid'];
                $newLeavetypeName = $leavetypeNames[$newLeavetype] ?? 'ไม่ระบุ';

                $found = false;
                $existingRecord = null;
                if (isset($leaveDataForUpdate[$empID])) {
                    foreach ($leaveDataForUpdate[$empID] as $data) {
                        $currentLeavetypeName = $leavetypeNames[$data['leavetype']] ?? '';
                        if ($currentLeavetypeName == $leaveTypeName) {
                            $found = true;
                            $existingRecord = $data;
                            break;
                        }
                    }
                }

                if ($found) {
                    if ($existingRecord['leavetype'] != $newLeavetype) {
                        $oldLeavetypeName = $leavetypeNames[$existingRecord['leavetype']] ?? 'ไม่ระบุ';
                        $currentPendingDeductionDays = $existingRecord['pending_deduction_days'];
                        $sql_update = "UPDATE leaveday SET leavetype = ?, pending_deduction_days = ? WHERE leavedayid = ?";
                        $stmt_update = $conn->prepare($sql_update);
                        $stmt_update->bind_param('iii', $newLeavetype, $currentPendingDeductionDays, $existingRecord['leavedayid']);
                        if ($stmt_update->execute()) {
                            $updateCount++;
                            $updateMessages[] = "พนักงาน ID $empID ($fullname): เปลี่ยน $leaveTypeName จาก leavetype {$existingRecord['leavetype']} ($oldLeavetypeName) เป็น $newLeavetype ($newLeavetypeName), คงค่า pending_deduction_days: $currentPendingDeductionDays";
                            error_log("Updated leavedayid {$existingRecord['leavedayid']} for empID $empID: leavetype from {$existingRecord['leavetype']} to $newLeavetype");
                            $leaveDataForUpdate[$empID][$newLeavetype] = $leaveDataForUpdate[$empID][$existingRecord['leavetype']];
                            $leaveDataForUpdate[$empID][$newLeavetype]['leavetype'] = $newLeavetype;
                            unset($leaveDataForUpdate[$empID][$existingRecord['leavetype']]);
                            if (!in_array($newLeavetypeName, $usedLeavetypenamesForUpdate)) {
                                $usedLeavetypenamesForUpdate[] = $newLeavetypeName;
                                sort($usedLeavetypenamesForUpdate);
                            }
                        } else {
                            throw new Exception("ไม่สามารถอัพเดท leavetype สำหรับ leavedayid {$existingRecord['leavedayid']}: " . $stmt_update->error);
                        }
                        $stmt_update->close();
                    } else {
                        $updateMessages[] = "พนักงาน ID $empID ($fullname): ข้าม $leaveTypeName เนื่องจาก leavetype {$existingRecord['leavetype']} ตรงกับ $newLeavetype แล้ว";
                        error_log("Skipped empID $empID for $leaveTypeName: leavetype {$existingRecord['leavetype']} already matches $newLeavetype");
                    }
                } else {
                    $initialPendingDeductionDays = 0;
                    $sql_insert = "INSERT INTO leaveday (empid, leavetype, staffstatus, day, stackleaveday, pending_deduction_days) VALUES (?, ?, ?, 0, 0, ?)";
                    $stmt_insert = $conn->prepare($sql_insert);
                    $stmt_insert->bind_param('iiii', $empID, $newLeavetype, $staffstatus, $initialPendingDeductionDays);
                    if ($stmt_insert->execute()) {
                        $newLeavedayid = $conn->insert_id;
                        $updateCount++;
                        $updateMessages[] = "พนักงาน ID $empID ($fullname): เพิ่ม $leaveTypeName (leavetype $newLeavetype, leavedayid $newLeavedayid), ตั้งค่า pending_deduction_days: $initialPendingDeductionDays";
                        error_log("Inserted new leavedayid $newLeavedayid for empID $empID: leavetype $newLeavetype");
                        $leaveDataForUpdate[$empID][$newLeavetype] = [
                            'leavedayid' => $newLeavedayid,
                            'fullname' => $fullname,
                            'leavetype' => $newLeavetype,
                            'staffstatus' => $staffstatus,
                            'day' => 0,
                            'stackleaveday' => 0,
                            'pending_deduction_days' => $initialPendingDeductionDays,
                            'totalMonths' => $totalMonths
                        ];
                        if (!in_array($newLeavetypeName, $usedLeavetypenamesForUpdate)) {
                            $usedLeavetypenamesForUpdate[] = $newLeavetypeName;
                            sort($usedLeavetypenamesForUpdate);
                        }
                    } else {
                        throw new Exception("ไม่สามารถเพิ่ม leaveday สำหรับ empid $empID: " . $stmt_insert->error);
                    }
                    $stmt_insert->close();
                }
            }
        }
        $conn->commit();
        return ['updateCount' => $updateCount, 'updateMessages' => $updateMessages];
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in batch $batch: " . $e->getMessage());
        return ['updateCount' => 0, 'updateMessages' => ["เกิดข้อผิดพลาดใน batch $batch: " . $e->getMessage()]];
    }
}

// ตรวจสอบข้อมูลที่ต้องอัพเดท
$pendingUpdateCountForUpdate = 0;
$pendingUpdateDetailsForUpdate = [];
$selectedLeaveTypesForUpdate = [];
foreach ($employees as $empID => $empData) {
    $staffstatus = $empData['staffstatus'];  // A  1
    $totalMonths = $empData['totalMonths']; // 18
    if (isset($leaveTypes[$staffstatus])) {
        foreach ($leaveTypes[$staffstatus] as $leaveTypeName => $leaves) {  // จัดเรียงข้อมูลแบ่งแยกตาม staffstatus 
            $selectedLeave = selectLeaveType($leaves, $totalMonths);
            if ($selectedLeave) {
                $selectedLeaveTypesForUpdate[$empID][$leaveTypeName] = $selectedLeave;
                $newLeavetype = $selectedLeave['leavetypeid'];
                $found = false;
                if (isset($leaveDataForUpdate[$empID])) {
                    foreach ($leaveDataForUpdate[$empID] as $data) {
                        if (isset($data['leavetype']) && isset($leavetypeNames[$data['leavetype']]) && $leavetypeNames[$data['leavetype']] == $leaveTypeName) {
                            $found = true;
                            if ($data['leavetype'] != $newLeavetype) {
                                $pendingUpdateCountForUpdate++;
                                $pendingUpdateDetailsForUpdate[] = "พนักงาน ID $empID: ควรเปลี่ยน $leaveTypeName จาก leavetype {$data['leavetype']} เป็น $newLeavetype";
                            }
                            break;
                        }
                    }
                }
                if (!$found) {
                    $pendingUpdateCountForUpdate++;
                    $pendingUpdateDetailsForUpdate[] = "พนักงาน ID $empID: ควรเพิ่ม $leaveTypeName (leavetype $newLeavetype)";
                }
            }
        }
    }
}

// เพิ่ม token เพื่อป้องกันการส่งฟอร์มซ้ำ
if (!isset($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(16));
}
$currentFormToken = $_SESSION['form_token'];

// อัพเดท leavetype เมื่อกดปุ่ม "อัพเดทข้อมูล"
if (isset($_POST['update_leaveday']) && isset($_POST['form_token']) && $_POST['form_token'] === $_SESSION['form_token']) {
    $batchSize = 50;
    $totalBatches = ceil($totalEmployees / $batchSize);
    $updateCountForUpdate = 0;
    $updateMessagesForUpdate = [];

    for ($batch = 0; $batch < $totalBatches; $batch++) {
        $result = processBatch($conn, $batch, $batchSize, $employees, $leaveTypes, $leavetypeNames, $leaveDataForUpdate, $usedLeavetypenamesForUpdate);
        $updateCountForUpdate += $result['updateCount'];
        $updateMessagesForUpdate = array_merge($updateMessagesForUpdate, $result['updateMessages']);
        gc_collect_cycles();
    }

    $_SESSION['alert'] = [
        'type' => 'success',
        'message' => "อัพเดทสำเร็จ จำนวนข้อมูลที่อัพเดท $updateCountForUpdate รายการ",
        'updateMessages' => $updateMessagesForUpdate
    ];

    $_SESSION['form_token'] = bin2hex(random_bytes(16));
}

// ตัวแปรสำหรับปุ่ม "อัพเดทวันลาสะสม"
$leaveDataForStacked = [];
$usedLeavetypenamesForStacked = [];
$sql_leaveday_for_stacked = "SELECT ld.leavedayid, ld.empid, ld.leavetype, ld.staffstatus, ld.day, ld.stackleaveday, ld.pending_deduction_days, e.fname, e.lname, e.startwork, e.startappoint 
                            FROM leaveday ld JOIN employees e ON ld.empid = e.id";
$result_leaveday_for_stacked = $conn->query($sql_leaveday_for_stacked);
if (!$result_leaveday_for_stacked) {
    die("Error fetching leaveday for stacked days: " . $conn->error);
}
while ($row = $result_leaveday_for_stacked->fetch_assoc()) {
    $startDateString = !is_null($row['startappoint']) ? $row['startappoint'] : $row['startwork'];
    $startDate = new DateTime($startDateString);
    $totalMonths = max(0, $customCurrentDate->diff($startDate)->y * 12 + $customCurrentDate->diff($startDate)->m);
    $leaveDataForStacked[$row['empid']][$row['leavetype']] = [
        'leavedayid' => $row['leavedayid'],
        'fullname' => $row['fname'] . ' ' . $row['lname'],
        'leavetype' => $row['leavetype'],
        'staffstatus' => $row['staffstatus'],
        'day' => $row['day'] ?? 0,
        'stackleaveday' => $row['stackleaveday'] ?? 0,
        'pending_deduction_days' => $row['pending_deduction_days'] ?? 0,
        'totalMonths' => $totalMonths
    ];
    $leavetypename = $leavetypeNames[$row['leavetype']] ?? '';
    if (!empty($leavetypename) && !in_array($leavetypename, $usedLeavetypenamesForStacked)) {
        $usedLeavetypenamesForStacked[] = $leavetypename;
    }
}
sort($usedLeavetypenamesForStacked);


// อัพเดทวันลาสะสม
if (isset($_POST['update_stacked_days']) && isset($_POST['form_token']) && $_POST['form_token'] === $_SESSION['form_token']) {
    $conn->begin_transaction();
    try {
        $updateMessagesForStacked = [];
        $updateCountForStacked = 0;
        $skippedCountForStacked = 0;

        foreach ($employees as $empID => $empData) {
            $staffstatus = $empData['staffstatus'];
            $totalMonths = $empData['totalMonths'];
            if (isset($leaveTypes[$staffstatus])) {
                foreach ($leaveTypes[$staffstatus] as $leaveTypeName => $leaves) { //จัดกลุ่มข้อมูลของประเภทการลาตาม staffstatus
                    $selectedLeave = selectLeaveType($leaves, $totalMonths); // 
                    if (!$selectedLeave) {
                        $updateMessagesForStacked[] = "พนักงาน ID $empID ({$empData['fullname']}): ข้าม $leaveTypeName เนื่องจากไม่พบ leavetype ที่เหมาะสม";
                        error_log("No suitable leavetype for $leaveTypeName for empID $empID");
                        $skippedCountForStacked++;
                        continue;
                    }

                    $leavetypeid = $selectedLeave['leavetypeid'];
                    $leaveofyear = $selectedLeave['leaveofyear'] ?? 0;
                    $stackleaveday = $leavetypeDetails[$leavetypeid]['stackleaveday'] ?? 0;

                    // คำนวณขีดจำกัดใหม่ของ stackleaveday: stackleaveday - leaveofyear
                    $stackleaveday_limit = max(0, $stackleaveday - $leaveofyear);

                    // ค้นหาข้อมูล leaveday ที่มีอยู่
                    $existingRecord = null;
                    if (isset($leaveDataForStacked[$empID][$leavetypeid])) {
                        $existingRecord = $leaveDataForStacked[$empID][$leavetypeid];
                    }

                    // คำนวณจำนวนวันที่ลาที่ใช้ไป
                    $total_leave_days = $leaveDaysByEmployee[$empID][$leaveTypeName] ?? 0;

                    if ($existingRecord) {
                        $currentDay = $existingRecord['day'] ?? 0;
                        $currentStackleaveday = $existingRecord['stackleaveday'] ?? 0;
                        $pending_deduction_days = $existingRecord['pending_deduction_days'] ?? 0;

                        // ตั้งค่า day ใหม่เป็น leaveofyear
                        $newDay = $leaveofyear;

                        // คำนวณวันลาที่เหลือจากวันลาประจำปี (ก่อนอัพเดท)
                        $remainingDays = max(0, $currentDay - $total_leave_days);

                        // คำนวณ stackleaveday ใหม่: เพิ่มวันลาที่เหลือเข้าไป
                        $newStackleaveday = $currentStackleaveday + $remainingDays;

                        // จำกัด stackleaveday ไม่ให้เกิน stackleaveday_limit
                        $newStackleaveday = min($newStackleaveday, $stackleaveday_limit);

                        // หัก pending_deduction_days หลังจากการอัพเดท
                        $deductionFromDay = 0;
                        $deductionFromStack = 0;
                        if ($pending_deduction_days > 0) {
                            // หักจาก day ก่อน
                            $deductionFromDay = min($pending_deduction_days, $newDay);
                            $newDay = max(0, $newDay - $deductionFromDay);
                            $remainingDeduction = $pending_deduction_days - $deductionFromDay;

                            // ถ้ายังมีวันต้องหักต่อ ให้หักจาก stackleaveday
                            if ($remainingDeduction > 0) {
                                $deductionFromStack = min($remainingDeduction, $newStackleaveday);
                                $newStackleaveday = max(0, $newStackleaveday - $deductionFromStack);
                            }

                            // บันทึก log การหัก
                            $updateMessagesForStacked[] = "พนักงาน ID $empID ({$empData['fullname']}): หัก pending_deduction_days $deductionFromDay วันจาก day, $deductionFromStack วันจาก stackleaveday สำหรับ $leaveTypeName (leavetype $leavetypeid)";
                            error_log("Deducted $deductionFromDay days from day, $deductionFromStack days from stackleaveday for empID $empID, leavetype $leavetypeid");

                            // รีเซ็ต pending_deduction_days เป็น 0
                            $pending_deduction_days = 0;
                        }

                        // อัพเดทข้อมูลในตาราง leaveday
                        $sql_update = "UPDATE leaveday SET day = ?, stackleaveday = ?, pending_deduction_days = ? WHERE leavedayid = ?";
                        $stmt_update = $conn->prepare($sql_update);
                        $stmt_update->bind_param('iiii', $newDay, $newStackleaveday, $pending_deduction_days, $existingRecord['leavedayid']);
                        if ($stmt_update->execute()) {
                            $updateCountForStacked++;
                            $updateMessagesForStacked[] = "พนักงาน ID $empID ({$empData['fullname']}): อัพเดท $leaveTypeName (leavetype $leavetypeid) -> day: $newDay, stackleaveday: $newStackleaveday (เพิ่ม $remainingDays วัน, limit: $stackleaveday_limit), pending_deduction_days: $pending_deduction_days";
                            error_log("Updated leavedayid {$existingRecord['leavedayid']} for empID $empID: day = $newDay, stackleaveday = $newStackleaveday, pending_deduction_days = $pending_deduction_days");

                            // อัพเดทข้อมูลใน $leaveDataForStacked และ $leaveDataForUpdate
                            foreach ([&$leaveDataForStacked, &$leaveDataForUpdate] as &$leaveDataSet) {
                                if (isset($leaveDataSet[$empID][$leavetypeid])) {
                                    $leaveDataSet[$empID][$leavetypeid]['day'] = $newDay;
                                    $leaveDataSet[$empID][$leavetypeid]['stackleaveday'] = $newStackleaveday;
                                    $leaveDataSet[$empID][$leavetypeid]['pending_deduction_days'] = $pending_deduction_days;
                                }
                            }
                        } else {
                            throw new Exception("ไม่สามารถอัพเดท leavedayid {$existingRecord['leavedayid']}: " . $stmt_update->error);
                        }
                        $stmt_update->close();
                    } else {
                        // ถ้าไม่มีข้อมูล leaveday ให้เพิ่มใหม่
                        $newDay = $leaveofyear;
                        $newStackleaveday = 0; // เริ่มต้น stackleaveday เป็น 0
                        $initialPendingDeductionDays = 0; // โดยปกติจะเป็น 0 สำหรับข้อมูลใหม่

                        // หัก pending_deduction_days (เพิ่มไว้เพื่อความครบถ้วน)
                        $deductionFromDay = 0;
                        $deductionFromStack = 0;
                        if ($initialPendingDeductionDays > 0) {
                            $deductionFromDay = min($initialPendingDeductionDays, $newDay);
                            $newDay = max(0, $newDay - $deductionFromDay);
                            $remainingDeduction = $initialPendingDeductionDays - $deductionFromDay;

                            if ($remainingDeduction > 0) {
                                $deductionFromStack = min($remainingDeduction, $newStackleaveday);
                                $newStackleaveday = max(0, $newStackleaveday - $deductionFromStack);
                            }

                            $updateMessagesForStacked[] = "พนักงาน ID $empID ({$empData['fullname']}): หัก pending_deduction_days $deductionFromDay วันจาก day, $deductionFromStack วันจาก stackleaveday สำหรับ $leaveTypeName (leavetype $leavetypeid)";
                            error_log("Deducted $deductionFromDay days from day, $deductionFromStack days from stackleaveday for empID $empID, leavetype $leavetypeid");

                            $initialPendingDeductionDays = 0;
                        }

                        $sql_insert = "INSERT INTO leaveday (empid, leavetype, staffstatus, day, stackleaveday, pending_deduction_days) VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt_insert = $conn->prepare($sql_insert);
                        $stmt_insert->bind_param('iiiiii', $empID, $leavetypeid, $staffstatus, $newDay, $newStackleaveday, $initialPendingDeductionDays);
                        if ($stmt_insert->execute()) {
                            $newLeavedayid = $conn->insert_id;
                            $updateCountForStacked++;
                            $updateMessagesForStacked[] = "พนักงาน ID $empID ({$empData['fullname']}): เพิ่ม $leaveTypeName (leavetype $leavetypeid, leavedayid $newLeavedayid) -> day: $newDay, stackleaveday: $newStackleaveday, pending_deduction_days: $initialPendingDeductionDays";
                            error_log("Inserted new leavedayid $newLeavedayid for empID $empID: leavetype $leavetypeid");

                            // อัพเดทข้อมูลใน $leaveDataForStacked และ $leaveDataForUpdate
                            foreach ([&$leaveDataForStacked, &$leaveDataForUpdate] as &$leaveDataSet) {
                                $leaveDataSet[$empID][$leavetypeid] = [
                                    'leavedayid' => $newLeavedayid,
                                    'fullname' => $empData['fullname'],
                                    'leavetype' => $leavetypeid,
                                    'staffstatus' => $staffstatus,
                                    'day' => $newDay,
                                    'stackleaveday' => $newStackleaveday,
                                    'pending_deduction_days' => $initialPendingDeductionDays,
                                    'totalMonths' => $totalMonths
                                ];
                            }

                            // อัพเดท usedLeavetypenamesForStacked และ usedLeavetypenamesForUpdate
                            $leavetypename = $leavetypeNames[$leavetypeid] ?? '';
                            if (!empty($leavetypename) && !in_array($leavetypename, $usedLeavetypenamesForStacked)) {
                                $usedLeavetypenamesForStacked[] = $leavetypename;
                                sort($usedLeavetypenamesForStacked);
                            }
                            if (!empty($leavetypename) && !in_array($leavetypename, $usedLeavetypenamesForUpdate)) {
                                $usedLeavetypenamesForUpdate[] = $leavetypename;
                                sort($usedLeavetypenamesForUpdate);
                            }
                        } else {
                            throw new Exception("ไม่สามารถเพิ่ม leaveday สำหรับ empid $empID: " . $stmt_insert->error);
                        }
                        $stmt_insert->close();
                    }
                }
            }
        }

        // อัพเดทข้อมูลตาราง year
        $yearStart1->modify('+1 year');
        $yearEnd1->modify('+1 year');
        $yearStart2->modify('+1 year');
        $yearEnd2->modify('+1 year');
        $updateDate = $customCurrentDate->format('Y-m-d H:i:s');

        $formattedYearStart1 = $yearStart1->format('Y-m-d');
        $formattedYearEnd1 = $yearEnd1->format('Y-m-d');
        $formattedYearStart2 = $yearStart2->format('Y-m-d');
        $formattedYearEnd2 = $yearEnd2->format('Y-m-d');

        $sql_update_year = "UPDATE year SET yearstart1 = ?, yearend1 = ?, yearstart2 = ?, yearend2 = ?, `update` = ? WHERE yearstart1 = ?";
        $stmt_update_year = $conn->prepare($sql_update_year);
        $stmt_update_year->bind_param('ssssss', $formattedYearStart1, $formattedYearEnd1, $formattedYearStart2, $formattedYearEnd2, $updateDate, $yearData['yearstart1']);
        if (!$stmt_update_year->execute()) {
            throw new Exception("ไม่สามารถอัพเดทตาราง year ได้: " . $stmt_update_year->error);
        }
        $stmt_update_year->close();

        $conn->commit();
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => "อัพเดทวันลาตามปีงบประมาณสำเร็จ จำนวนที่อัพเดท: $updateCountForStacked, ข้าม: $skippedCountForStacked",
            'updateMessages' => $updateMessagesForStacked
        ];
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => "เกิดข้อผิดพลาด: " . $e->getMessage(),
            'updateMessages' => $updateMessagesForStacked
        ];
        error_log("Error updating stacked days: " . $e->getMessage());
    }
    $_SESSION['form_token'] = bin2hex(random_bytes(16));
}


// อัพเดทวันลาใน modal
if (isset($_POST['update_day']) && isset($_POST['form_token']) && $_POST['form_token'] === $_SESSION['form_token']) {
    $leavedayid = $_POST['leavedayid'];
    $new_day = isset($_POST['new_day']) ? intval($_POST['new_day']) : 0;
    $new_stackleaveday = isset($_POST['new_stackleaveday']) ? intval($_POST['new_stackleaveday']) : 0;

    if (empty($leavedayid)) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => 'ข้อมูลไม่ครบถ้วน: leavedayid หายไป'
        ];
    } else {
        $sql_update_day = "UPDATE leaveday SET day = ?, stackleaveday = ? WHERE leavedayid = ?";
        $stmt_update_day = $conn->prepare($sql_update_day);
        $stmt_update_day->bind_param('iii', $new_day, $new_stackleaveday, $leavedayid);
        if ($stmt_update_day->execute()) {
            foreach ([&$leaveDataForUpdate, &$leaveDataForStacked] as &$leaveDataSet) {
                foreach ($leaveDataSet as $empID => $leaveRecords) {
                    foreach ($leaveRecords as $leavetype => $data) {
                        if ($data['leavedayid'] == $leavedayid) {
                            $leaveDataSet[$empID][$leavetype]['day'] = $new_day;
                            $leaveDataSet[$empID][$leavetype]['stackleaveday'] = $new_stackleaveday;
                            break 2;
                        }
                    }
                }
            }
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => "อัพเดทสำเร็จ"
            ];
        } else {
            $_SESSION['alert'] = [
                'type' => 'error',
                'message' => "ไม่สามารถอัพเดทจำนวนวันลาได้: " . $stmt_update_day->error
            ];
        }
        $stmt_update_day->close();
    }

    $_SESSION['form_token'] = bin2hex(random_bytes(16));
}

// ดึงข้อมูลการลาในปีงบประมาณ
$sql_leave_days = "SELECT lt.leavetypename, l.employeesid, SUM(l.day) as total_leave_days 
                   FROM leaves l 
                   JOIN leavetype lt ON l.leavetype = lt.leavetypeid 
                   WHERE l.leavestart BETWEEN ? AND ? 
                   AND l.leavestatus = 'อนุมัติ'
                   GROUP BY lt.leavetypename, l.employeesid";
$stmt_leave_days = $conn->prepare($sql_leave_days);
$stmt_leave_days->bind_param('ss', $yearStart1->format('Y-m-d'), $yearEnd2->format('Y-m-d'));
$stmt_leave_days->execute();
$result_leave_days = $stmt_leave_days->get_result();

$leaveDaysByEmployee = [];
while ($row = $result_leave_days->fetch_assoc()) {
    $leaveDaysByEmployee[$row['employeesid']][$row['leavetypename']] = $row['total_leave_days'] ?? 0;
}
$stmt_leave_days->close();

// จัดการ alert
$showAlert = false;
$alertType = '';
$alertMessage = '';
$updateMessages = [];
if (isset($_SESSION['alert'])) {
    $showAlert = true;
    $alertType = $_SESSION['alert']['type'];
    $alertMessage = $_SESSION['alert']['message'];
    $updateMessages = $_SESSION['alert']['updateMessages'] ?? [];
    unset($_SESSION['alert']);
}

// ดึงสถานะตาราง
$activeTab = isset($_SESSION['table_state']['activeTab']) ? $_SESSION['table_state']['activeTab'] : null;
$tablePages = isset($_SESSION['table_state']['pages']) ? $_SESSION['table_state']['pages'] : [];
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/table.css">
    <title>Dashboard</title>
    <style>
        @media (max-width: 576px) {
            .modal-dialog {
                margin: 0.5rem;
            }

            .modal-content {
                font-size: 0.9rem;
            }

            .modal-header h5 {
                font-size: 1.1rem;
            }

            .modal-body {
                padding: 1rem;
            }

            .form-control {
                font-size: 0.9rem;
            }

            .btn {
                font-size: 0.9rem;
                padding: 0.5rem;
            }
        }

        @media (min-width: 576px) {
            .modal-sm {
                max-width: 400px;
            }
        }

        .table-responsive {
            width: 100%;
        }

        table.dataTable {
            width: 100% !important;
        }

        #update-log {
            white-space: pre-wrap;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            display: none;
        }
    </style>
</head>

<body>
    <?php include('component/sidebar.php'); ?>
    <main class="main container3" id="main">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">อัพเดทสิทธิ์ประเภทการลา</h3>
                <p class="card-text mt-2 mb-0">วันที่ปัจจุบัน: <?= $customCurrentDate->format('d/m/') . ($customCurrentDate->format('Y')) ?></p>
                <p class="card-text mt-2 mb-0">จำนวนข้อมูลที่ต้องอัพเดท: <strong><?= $pendingUpdateCountForUpdate ?> รายการ</strong></p>
                <form method="POST" class="mt-2 d-inline">
                    <input type="hidden" name="form_token" value="<?= htmlspecialchars($currentFormToken) ?>">
                    <button type="submit" name="update_leaveday" class="btn btn-primary" <?= $pendingUpdateCountForUpdate == 0 ? 'disabled' : '' ?>>อัพเดทข้อมูล</button>
                </form>
                <form method="POST" class="mt-2 d-inline">
                    <input type="hidden" name="form_token" value="<?= htmlspecialchars($currentFormToken) ?>">
                    <button type="submit" name="update_stacked_days" class="btn btn-info" <?= !$canUpdateStackedDays ? 'disabled title="ไม่สามารถอัพเดทได้ในช่วงวันที่กำหนด หรือรอจนกว่าจะเลยวันที่สิ้นสุดรอบที่สอง"' : '' ?>>อัพเดทวันลาสะสม</button>
                </form>
                <button onclick="window.open('print_report.php', '_blank')" class="btn btn-success mt-2">Print Report</button>
            </div>
            <div class="card-body">
                <?php if (empty($usedLeavetypenamesForUpdate)): ?>
                    <div class="alert alert-info mt-3" role="alert">
                        ยังไม่มีข้อมูลการลาในระบบ กรุณากด "อัพเดทข้อมูล" เพื่อเพิ่มข้อมูล
                    </div>
                <?php else: ?>
                    <ul class="nav nav-tabs" id="leaveTypeTab" role="tablist">
                        <?php foreach ($usedLeavetypenamesForUpdate as $index => $leavetypename): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?= ($activeTab == str_replace(' ', '-', $leavetypename) || ($index === 0 && !$activeTab)) ? 'active' : '' ?>"
                                    id="<?= str_replace(' ', '-', $leavetypename) ?>-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#<?= str_replace(' ', '-', $leavetypename) ?>"
                                    type="button"
                                    role="tab"
                                    aria-controls="<?= str_replace(' ', '-', $leavetypename) ?>"
                                    aria-selected="<?= ($activeTab == str_replace(' ', '-', $leavetypename) || ($index === 0 && !$activeTab)) ? 'true' : 'false' ?>">
                                    <?= $leavetypename ?>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="tab-content" id="leaveTypeTabContent">
                        <?php foreach ($usedLeavetypenamesForUpdate as $index => $leavetypename): ?>
                            <div class="tab-pane fade <?= ($activeTab == str_replace(' ', '-', $leavetypename) || ($index === 0 && !$activeTab)) ? 'show active' : '' ?>"
                                id="<?= str_replace(' ', '-', $leavetypename) ?>"
                                role="tabpanel"
                                aria-labelledby="<?= str_replace(' ', '-', $leavetypename) ?>-tab">
                                <div class="table-responsive mt-3">
                                    <table id="example-<?= str_replace(' ', '-', $leavetypename) ?>"
                                        class="table table-striped table-bordered dt-responsive nowrap"
                                        style="width:100%">
                                        <thead>
                                            <tr>
                                                <th hidden>ลำดับ</th>
                                                <th>ชื่อ-นามสกุล</th>
                                                <th>สถานะพนักงาน</th>
                                                <th>วันลาประจำปี</th>
                                                <th>วันลาสะสม</th>
                                                <!-- <th>หักในปีถัดไป</th> -->
                                                <th>จำนวนวันที่ลา</th>
                                                <th>จำนวนครั้งที่ลา</th>
                                                <th>วันลาคงเหลือ</th>
                                                <th>การดำเนินการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($employees as $empID => $empData): ?>
                                                <tr>
                                                    <?php
                                                    $staffstatus = $empData['staffstatus'];
                                                    $totalMonths = $empData['totalMonths'];
                                                    $selectedLeavetype = null;
                                                    $day = 0;
                                                    $stackleaveday = 0;
                                                    $stackleaveday_max = 0;
                                                    $leaveofyear = 0;
                                                    $stackleaveday_limit = 0; // เพิ่มตัวแปรสำหรับขีดจำกัดใหม่
                                                    $leavedayid = null;
                                                    $pending_deduction_days = 0;
                                                    $leave_count = 0; // ตัวแปรสำหรับจำนวนครั้งที่ลา
                                                    if (isset($leaveTypes[$staffstatus][$leavetypename])) {
                                                        $selectedLeave = $selectedLeaveTypesForUpdate[$empID][$leavetypename] ?? selectLeaveType($leaveTypes[$staffstatus][$leavetypename], $totalMonths);
                                                        if ($selectedLeave) {
                                                            $selectedLeavetype = $selectedLeave['leavetypeid'];
                                                            $leaveofyear = $selectedLeave['leaveofyear'] ?? 0;
                                                            $stackleaveday_max = $leavetypeDetails[$selectedLeavetype]['stackleaveday'] ?? 0;
                                                            // คำนวณขีดจำกัดใหม่: stackleaveday - leaveofyear
                                                            $stackleaveday_limit = max(0, $stackleaveday_max - $leaveofyear);
                                                            $day = 0;
                                                            $stackleaveday = 0;
                                                            $pending_deduction_days = 0;
                                                            $leavedayid = null;
                                                            $existingLeavetype = null;
                                                            if (isset($leaveDataForUpdate[$empID])) {
                                                                foreach ($leaveDataForUpdate[$empID] as $data) {
                                                                    if (isset($data['leavetype']) && isset($leavetypeNames[$data['leavetype']]) && $leavetypeNames[$data['leavetype']] == $leavetypename) {
                                                                        $existingLeavetype = $data['leavetype'];
                                                                        if ($data['leavetype'] == $selectedLeavetype) {
                                                                            $day = $data['day'];
                                                                            $stackleaveday = $data['stackleaveday'];
                                                                            $pending_deduction_days = $data['pending_deduction_days'];
                                                                            $leavedayid = $data['leavedayid'];
                                                                        }
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                            if ($existingLeavetype && $existingLeavetype != $selectedLeavetype) {
                                                                $day = 0;
                                                                $stackleaveday = 0;
                                                                $pending_deduction_days = 0;
                                                                $leavedayid = null;
                                                            }
                                                            $leave_count = $leaveCountByEmployee[$empID][$leavetypename] ?? 0;
                                                        }
                                                    }
                                                    // คำนวณจำนวนวันที่ลา
                                                    $total_leave_days = $leaveDaysByEmployee[$empID][$leavetypename] ?? 0;
                                                    ?>
                                                    <td hidden><?= $empID ?></td>
                                                    <td><?= $empData['fullname'] ?></td>
                                                    <td><?= isset($staffstatusNames[$empData['staffstatus']]) ? $staffstatusNames[$empData['staffstatus']] : 'ไม่ระบุ' ?></td>
                                                    <td><?= $day ?>/<?= $leaveofyear ?: '0' ?></td>
                                                    <td><?= $stackleaveday ?>/<?= $stackleaveday_limit ?: '0' ?></td> <!-- เปลี่ยนจาก $stackleaveday_max เป็น $stackleaveday_limit -->
                                                    <!-- <td><?= $pending_deduction_days ?: '0' ?></td> -->
                                                    <td><?= $total_leave_days ?> วัน</td>
                                                    <td><?= $leave_count ?> ครั้ง</td> <!-- แสดงจำนวนครั้งที่ลา -->
                                                    <td><?= $day + $stackleaveday ?> วัน</td>
                                                    <td>
                                                        <?php if ($selectedLeavetype && $leavedayid): ?>
                                                            <button type="button" class="btn btn-warning btn-sm edit-day-btn"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#editDayModal"
                                                                data-leavedayid="<?= $leavedayid ?>"
                                                                data-day="<?= $day ?>"
                                                                data-stackleaveday="<?= $stackleaveday ?>"
                                                                data-leaveofyear="<?= $leaveofyear ?>"
                                                                data-stackleavedaymax="<?= $stackleaveday_limit ?>"> <!-- เปลี่ยนจาก $stackleaveday_max เป็น $stackleaveday_limit -->
                                                                แก้ไข
                                                            </button>
                                                        <?php else: ?>
                                                            <span>ไม่มีข้อมูล</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div id="update-log" <?= !empty($updateMessages) ? '' : 'style="display: none;"' ?>>
                    <?= !empty($updateMessages) ? implode("\n", array_map('htmlspecialchars', $updateMessages)) : '' ?>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editDayModal" tabindex="-1" aria-labelledby="editDayModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editDayModalLabel">แก้ไขจำนวนวันลา</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="new_day" class="form-label">จำนวนวันลาใหม่</label>
                                <input type="number" class="form-control" id="new_day" name="new_day" min="0">
                            </div>
                            <div class="mb-3">
                                <label for="new_stackleaveday" class="form-label">จำนวนวันลาสะสมใหม่</label>
                                <input type="number" class="form-control" id="new_stackleaveday" name="new_stackleaveday" min="0">
                            </div>
                            <input type="hidden" id="leavedayid" name="leavedayid">
                            <input type="hidden" name="form_token" value="<?= htmlspecialchars($currentFormToken) ?>">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                            <button type="submit" name="update_day" class="btn btn-primary">บันทึก</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php include('component/footer.php'); ?>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/main.js"></script>
    <script>
 $(document).ready(function() {
    var dataTables = {};

    // ฟังก์ชันสำหรับบันทึกสถานะตารางลงใน Local Storage
    function saveTableState(tabId, page) {
        try {
            // อ่านข้อมูลปัจจุบันจาก Local Storage
            let tableState = localStorage.getItem('tableState') ? JSON.parse(localStorage.getItem('tableState')) : {
                activeTab: null,
                pages: {}
            };

            // อัพเดทสถานะ
            tableState.activeTab = tabId;
            tableState.pages[tabId] = page;

            // บันทึกกลับไปยัง Local Storage
            localStorage.setItem('tableState', JSON.stringify(tableState));
            console.log('Table state saved to Local Storage:', tableState);
        } catch (e) {
            console.error('Error saving table state to Local Storage:', e);
        }
    }

    // ฟังก์ชันสำหรับโหลดสถานะตารางจาก Local Storage
    function loadTableState() {
        try {
            if (localStorage.getItem('tableState')) {
                return JSON.parse(localStorage.getItem('tableState'));
            }
            return { activeTab: null, pages: {} };
        } catch (e) {
            console.error('Error loading table state from Local Storage:', e);
            return { activeTab: null, pages: {} };
        }
    }

    // โหลดสถานะเมื่อหน้าโหลด
    let tableState = loadTableState();

    // ตั้งค่า DataTable
    <?php foreach ($usedLeavetypenamesForUpdate as $leavetypename): ?>
        (function() {
            var tableId = 'example-<?= str_replace(' ', '-', $leavetypename) ?>';
            var tabId = '<?= str_replace(' ', '-', $leavetypename) ?>';
            // ใช้หน้าที่บันทึกไว้ใน Local Storage หรือค่าเริ่มต้นจาก PHP
            var savedPage = tableState.pages[tabId] !== undefined 
                ? tableState.pages[tabId] 
                : <?php echo isset($tablePages[str_replace(' ', '-', $leavetypename)]) ? $tablePages[str_replace(' ', '-', $leavetypename)] : 0; ?>;

            dataTables[tabId] = $('#' + tableId).DataTable({
                responsive: true,
                autoWidth: false,
                scrollX: true,
                pageLength: 5,
                lengthMenu: [
                    [5, 15, 25, 50, -1],
                    [5, 15, 25, 50, "ทั้งหมด"]
                ],
                language: {
                    lengthMenu: "แสดง _MENU_ รายการ",
                    zeroRecords: "ไม่พบข้อมูล",
                    info: "แสดงหน้า _PAGE_ จาก _PAGES_",
                    infoEmpty: "ไม่มีข้อมูล",
                    infoFiltered: "(กรองจากทั้งหมด _MAX_ รายการ)",
                    search: "ค้นหา:",
                    paginate: {
                        first: "หน้าแรก",
                        last: "หน้าสุดท้าย",
                        next: "ถัดไป",
                        previous: "ก่อนหน้า"
                    }
                },
                displayStart: savedPage * 5 // เริ่มต้นที่หน้าที่บันทึกไว้
            });

            // บันทึกสถานะเมื่อเปลี่ยนหน้า
            dataTables[tabId].on('page', function() {
                var currentPage = dataTables[tabId].page();
                saveTableState(tabId, currentPage);
            });
        })();
    <?php endforeach; ?>

    // อัพเดทตารางเมื่อเปลี่ยนแท็บ
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        var targetTab = $(e.target).data('bs-target');
        var tableId = targetTab.replace('#', 'example-');
        var tabId = targetTab.replace('#', '');
        saveTableState(tabId, dataTables[tabId].page());
        if (dataTables[tabId]) {
            dataTables[tabId].columns.adjust().responsive.recalc();
        }
    });

    // อัพเดทตารางเมื่อหน้าต่างเปลี่ยนขนาด
    $(window).on('resize', function() {
        $.each(dataTables, function(key, table) {
            table.columns.adjust().responsive.recalc();
        });
    });

    // ตั้งค่า modal
    $(document).on('click', '.edit-day-btn', function() {
        const leavedayid = $(this).data('leavedayid');
        const day = $(this).data('day');
        const stackleaveday = $(this).data('stackleaveday');
        const leaveofyear = $(this).data('leaveofyear');
        const stackleavedaymax = $(this).data('stackleavedaymax');
        $('#editDayModal #leavedayid').val(leavedayid);
        $('#editDayModal #new_day').val(day).attr('max', leaveofyear);
        $('#editDayModal #new_stackleaveday').val(stackleaveday).attr('max', stackleavedaymax);
    });

    // ป้องกันการส่งฟอร์มซ้ำเมื่อรีเฟรช
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }

    // ตั้งค่าแท็บเริ่มต้นจาก Local Storage
    if (tableState.activeTab) {
        $(`button[data-bs-target="#${tableState.activeTab}"]`).tab('show');
    }

    // แสดง SweetAlert และรีเฟรชหน้า
    <?php if ($showAlert): ?>
        console.log('Update Log:');
        <?php foreach ($updateMessages as $index => $message): ?>
            console.log(`[${<?= $index + 1 ?>}] <?= addslashes($message) ?>`);
        <?php endforeach; ?>
        Swal.fire({
            icon: <?= json_encode($alertType) ?>,
            title: <?= $alertType === 'success' ? json_encode('สำเร็จ') : json_encode('เกิดข้อผิดพลาด') ?>,
            html: <?= json_encode($alertMessage . (!empty($updateMessages) ? '<pre style="text-align: left; font-size: 0.9em; max-height: 300px; overflow-y: auto;">' . implode("\n", array_map('htmlspecialchars', $updateMessages)) . '</pre>' : '')) ?>,
            confirmButtonText: 'ตกลง'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.reload();
            }
        });
    <?php endif; ?>
});
    </script>
</body>

</html>

<?php
$conn->close();
ob_end_flush();
?>