<?php
include('conn/conn.php');

date_default_timezone_set('Asia/Bangkok');
$customCurrentDate = new DateTime();

// ดึงข้อมูลพนักงาน โดย JOIN กับตาราง prefix เพื่อดึง prefixname
$sql_employees = "SELECT e.id, e.fname, e.lname, e.staffstatus, e.startwork, e.prefix, p.prefixname 
                  FROM employees e 
                  LEFT JOIN prefix p ON e.prefix = p.prefixid";
$result_employees = $conn->query($sql_employees);

$employees = [];
while ($row = $result_employees->fetch_assoc()) {
    $startDate = new DateTime($row['startwork']);
    $totalMonths = max(0, $customCurrentDate->diff($startDate)->y * 12 + $customCurrentDate->diff($startDate)->m);

    $employees[$row['id']] = [
        'id' => $row['id'], // เก็บ id เพื่อใช้ในการค้นหาข้อมูลการลา
        'prefixname' => $row['prefixname'] ?? '',
        'fullname' => $row['fname'] . ' ' . $row['lname'],
        'staffstatus' => $row['staffstatus'],
        'totalMonths' => $totalMonths
    ];
}

// ดึงข้อมูลสถานะพนักงาน
$sql_staffstatus = "SELECT staffid, staffname FROM staffstatus";
$result_staffstatus = $conn->query($sql_staffstatus);
$staffstatusNames = [];
while ($row = $result_staffstatus->fetch_assoc()) {
    $staffstatusNames[$row['staffid']] = $row['staffname'];
}

// กำหนดลำดับตาม staffname
$staffstatusOrderByName = [
    'ข้าราชการ' => 1,
    'พนักงานในสถาบันอุดมศึกษา' => 2,
    'พนักงานราชการ' => 3,
    'ลูกจ้างเงินรายได้' => 4,
    'ลูกจ้างประจำ' => 5
];

// เรียงลำดับพนักงานตาม staffstatus และเพิ่มการเรียงรองตามชื่อ
usort($employees, function($a, $b) use ($staffstatusNames, $staffstatusOrderByName) {
    $statusNameA = isset($staffstatusNames[$a['staffstatus']]) ? $staffstatusNames[$a['staffstatus']] : 'ไม่ระบุ';
    $statusNameB = isset($staffstatusNames[$b['staffstatus']]) ? $staffstatusNames[$b['staffstatus']] : 'ไม่ระบุ';
    $orderA = isset($staffstatusOrderByName[$statusNameA]) ? $staffstatusOrderByName[$statusNameA] : PHP_INT_MAX;
    $orderB = isset($staffstatusOrderByName[$statusNameB]) ? $staffstatusOrderByName[$statusNameB] : PHP_INT_MAX;

    // ถ้า order เท่ากัน ให้เรียงตาม fullname
    if ($orderA === $orderB) {
        return strcmp($a['fullname'], $b['fullname']);
    }
    return $orderA <=> $orderB;
});

// ดึงข้อมูลจากตาราง year เพื่อใช้กำหนดค่าเริ่มต้นของ input
$sql_year = "SELECT yearstart1, yearend1, yearstart2, yearend2 FROM year LIMIT 1";
$result_year = $conn->query($sql_year);
$yearData = $result_year->fetch_assoc();

// ตรวจสอบว่ามีข้อมูลในตาราง year หรือไม่
if (!$yearData) {
    die("ไม่พบข้อมูลในตาราง year");
}

// กำหนดค่าเริ่มต้นสำหรับ input จากตาราง year
$default_period1_start = $yearData['yearstart1'];
$default_period1_end = $yearData['yearend1'];
$default_period2_start = $yearData['yearstart2'];
$default_period2_end = $yearData['yearend2'];

// รับค่าจาก input หากผู้ใช้เลือกวันที่ใหม่ มิฉะนั้นใช้ค่าเริ่มต้นจากตาราง year
$period1_start = isset($_GET['period1_start']) ? $_GET['period1_start'] : $default_period1_start;
$period1_end = isset($_GET['period1_end']) ? $_GET['period1_end'] : $default_period1_end;
$period2_start = isset($_GET['period2_start']) ? $_GET['period2_start'] : $default_period2_start;
$period2_end = isset($_GET['period2_end']) ? $_GET['period2_end'] : $default_period2_end;

// ฟังก์ชันแปลงวันที่เป็นรูปแบบภาษาไทย
function thaiDateFormat($date) {
    $months = [
        1 => 'มกราคม',
        2 => 'กุมภาพันธ์',
        3 => 'มีนาคม',
        4 => 'เมษายน',
        5 => 'พฤษภาคม',
        6 => 'มิถุนายน',
        7 => 'กรกฎาคม',
        8 => 'สิงหาคม',
        9 => 'กันยายน',
        10 => 'ตุลาคม',
        11 => 'พฤศจิกายน',
        12 => 'ธันวาคม'
    ];

    $dateObj = new DateTime($date);
    $day = (int)$dateObj->format('d');
    $month = (int)$dateObj->format('m');
    $year = (int)$dateObj->format('Y') + 543;

    return "$day {$months[$month]} $year";
}

// แปลงวันที่จาก input เป็นรูปแบบภาษาไทยเพื่อแสดงผล
$period1_start_display = thaiDateFormat($period1_start);
$period1_end_display = thaiDateFormat($period1_end);
$period2_start_display = thaiDateFormat($period2_start);
$period2_end_display = thaiDateFormat($period2_end);

// ดึงปีจาก period2_end (แปลงเป็นพ.ศ.)
$budgetYear = (int)(new DateTime($period2_end))->format('Y') + 543;

// กำหนดข้อความสำหรับส่วนหัว (ใช้วันที่จาก input)
$headerRow1 = "รายงานสถิติวันลาหยุดของ ข้าราชการพลเรือน พนักงานในสถาบันอุดมศึกษา พนักงานราชการ ลูกจ้างเงินรายได้";
$headerRow2 = "สำนักส่งเสริมวิชาการและงานทะเบียน มหาวิทยาลัยเทคโนโลยีราชมงคลอีสาน นครราชสีมา";
$headerRow3 = "ปีงบประมาณ $budgetYear ($period1_start_display - $period2_end_display)";

// กำหนดข้อความสำหรับส่วนหัวของตาราง (ใช้วันที่จาก input)
$period1_text = "ช่วงที่ 1: $period1_start_display - $period1_end_display";
$period2_text = "ช่วงที่ 2: $period2_start_display - $period2_end_display";

// ดึงประเภทการลาจากตาราง leaveday โดย JOIN กับ leavetype เพื่อให้ได้ leavetypename
$usedLeavetypenames = [];
$leavetypeMapping = [];
$sql_leaveday = "SELECT DISTINCT ld.leavetype, lt.leavetypename 
                 FROM leaveday ld 
                 JOIN leavetype lt ON ld.leavetype = lt.leavetypeid";
$result_leaveday = $conn->query($sql_leaveday);

while ($row = $result_leaveday->fetch_assoc()) {
    $leavetypeid = $row['leavetype'];
    $leavetypename = $row['leavetypename'];
    $leavetypeMapping[$leavetypeid] = $leavetypename;
    if (!in_array($leavetypename, $usedLeavetypenames)) {
        $usedLeavetypenames[] = $leavetypename;
    }
}
sort($usedLeavetypenames);

// ดึงข้อมูลการลาสำหรับช่วงที่ 1 (จากตาราง leaves)
$leaveRecordsPeriod1 = [];
$sql_leave = "SELECT employeesid, leavetype, COUNT(*) as leave_count, SUM(day) as total_days 
              FROM `leaves` 
              WHERE leavestart BETWEEN ? AND ? AND leavestatus = 'อนุมัติ' AND note ='ลา'
              GROUP BY employeesid, leavetype";
$stmt_leave = $conn->prepare($sql_leave);
if ($stmt_leave === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt_leave->bind_param('ss', $period1_start, $period1_end);
if (!$stmt_leave->execute()) {
    die("Execute failed: " . $stmt_leave->error);
}
$result_leave = $stmt_leave->get_result();

while ($row = $result_leave->fetch_assoc()) {
    $empid = $row['employeesid'];
    $leavetype = $row['leavetype'];
    $leavetypename = isset($leavetypeMapping[$leavetype]) ? $leavetypeMapping[$leavetype] : null;
    if ($leavetypename) {
        $leaveRecordsPeriod1[$empid][$leavetypename] = [
            'count' => $row['leave_count'],
            'days' => $row['total_days']
        ];
    }
}
$stmt_leave->close();

// ดึงข้อมูลการลาสำหรับช่วงที่ 2 (จากตาราง leaves)
$leaveRecordsPeriod2 = [];
$stmt_leave = $conn->prepare($sql_leave);
if ($stmt_leave === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt_leave->bind_param('ss', $period2_start, $period2_end);
if (!$stmt_leave->execute()) {
    die("Execute failed: " . $stmt_leave->error);
}
$result_leave = $stmt_leave->get_result();

while ($row = $result_leave->fetch_assoc()) {
    $empid = $row['employeesid'];
    $leavetype = $row['leavetype'];
    $leavetypename = isset($leavetypeMapping[$leavetype]) ? $leavetypeMapping[$leavetype] : null;
    if ($leavetypename) {
        $leaveRecordsPeriod2[$empid][$leavetypename] = [
            'count' => $row['leave_count'],
            'days' => $row['total_days']
        ];
    }
}
$stmt_leave->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 16px;
        }
        .report-section {
            margin-bottom: 20px;
            page-break-after: always;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .report-table th, .report-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        .report-table th {
            background-color: #f2f2f2;
        }
        .report-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .group-header {
            background-color: #e0e0e0;
            font-weight: bold;
        }
        .report-header {
            text-align: center;
            margin-bottom: 10px;
        }
        .report-header p {
            margin: 5px 0;
        }
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        @media print {
            body {
                margin: 0;
                font-size: 14px;
            }
            .no-print {
                display: none;
            }
            .report-table th, .report-table td {
                padding: 5px;
            }
        }
    </style>
</head>
<body>
    <!-- ส่วน input -->
    <div class="no-print">
        <form method="GET" action="" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    <label for="period1_start" class="form-label">ช่วงที่ 1 เริ่ม:</label>
                    <input type="date" name="period1_start" id="period1_start" class="form-control" value="<?= $default_period1_start ?>">
                    <small class="form-text text-muted">วันที่เลือก: <?= $period1_start_display ?></small>
                </div>
                <div class="col-md-3">
                    <label for="period1_end" class="form-label">ช่วงที่ 1 สิ้นสุด:</label>
                    <input type="date" name="period1_end" id="period1_end" class="form-control" value="<?= $default_period1_end ?>">
                    <small class="form-text text-muted">วันที่เลือก: <?= $period1_end_display ?></small>
                </div>
                <div class="col-md-3">
                    <label for="period2_start" class="form-label">ช่วงที่ 2 เริ่ม:</label>
                    <input type="date" name="period2_start" id="period2_start" class="form-control" value="<?= $default_period2_start ?>">
                    <small class="form-text text-muted">วันที่เลือก: <?= $period2_start_display ?></small>
                </div>
                <div class="col-md-3">
                    <label for="period2_end" class="form-label">ช่วงที่ 2 สิ้นสุด:</label>
                    <input type="date" name="period2_end" id="period2_end" class="form-control" value="<?= $default_period2_end ?>">
                    <small class="form-text text-muted">วันที่เลือก: <?= $period2_end_display ?></small>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-2">แสดงรายงาน</button>
        </form>
    </div>

    <!-- ช่วงที่ 1 -->
    <div class="report-section">
        <div class="report-header">
            <p><?= $headerRow1 ?></p>
            <p><?= $headerRow2 ?></p>
            <p><?= $headerRow3 ?></p>
        </div>
        <h2>รายงานข้อมูลการลาของพนักงาน - <?= $period1_text ?></h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th rowspan="2">ลำดับ</th>
                    <th rowspan="2">ชื่อ-นามสกุล</th>
                    <?php foreach ($usedLeavetypenames as $leavetypename): ?>
                        <th colspan="2"><?= $leavetypename ?></th>
                    <?php endforeach; ?>
                    <th rowspan="2">หมายเหตุ</th>
                </tr>
                <tr>
                    <?php foreach ($usedLeavetypenames as $leavetypename): ?>
                        <th>จำนวนครั้ง</th>
                        <th>จำนวนวัน</th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $counter = 1;
                $currentStaffStatus = null;
                foreach ($employees as $empData):
                    $staffstatus = $empData['staffstatus'];
                    $empID = $empData['id']; // ใช้ id จาก $empData
                    $staffStatusName = isset($staffstatusNames[$staffstatus]) ? $staffstatusNames[$staffstatus] : 'ไม่ระบุ';

                    // ถ้าเปลี่ยนกลุ่มสถานะ ให้เพิ่มแถวระบุกลุ่ม
                    if ($currentStaffStatus !== $staffStatusName) {
                        $currentStaffStatus = $staffStatusName;
                        $colspan = 2 + (count($usedLeavetypenames) * 2);
                        ?>
                        <tr class="group-header">
                            <td colspan="<?= $colspan + 1 ?>"><?= $currentStaffStatus ?></td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <td><?= $counter++ ?></td>
                        <td><?= $empData['prefixname'] . ' ' . $empData['fullname'] ?></td>
                        <?php
                        foreach ($usedLeavetypenames as $leavetypename) {
                            $leaveCount1 = 0;
                            $totalDays1 = 0;

                            if (isset($leaveRecordsPeriod1[$empID][$leavetypename])) {
                                $leaveCount1 = $leaveRecordsPeriod1[$empID][$leavetypename]['count'];
                                $totalDays1 = $leaveRecordsPeriod1[$empID][$leavetypename]['days'];
                            }
                            ?>
                            <td><?= ($leaveCount1 > 0) ? $leaveCount1 : '-' ?></td>
                            <td><?= ($totalDays1 > 0) ? $totalDays1 : '-' ?></td>
                        <?php } ?>
                        <td></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- ช่วงที่ 2 -->
    <div class="report-section">
        <div class="report-header">
            <p><?= $headerRow1 ?></p>
            <p><?= $headerRow2 ?></p>
            <p><?= $headerRow3 ?></p>
        </div>
        <h2>รายงานข้อมูลการลาของพนักงาน - <?= $period2_text ?></h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th rowspan="2">ลำดับ</th>
                    <th rowspan="2">ชื่อ-นามสกุล</th>
                    <?php foreach ($usedLeavetypenames as $leavetypename): ?>
                        <th colspan="2"><?= $leavetypename ?></th>
                    <?php endforeach; ?>
                    <th rowspan="2">หมายเหตุ</th>
                </tr>
                <tr>
                    <?php foreach ($usedLeavetypenames as $leavetypename): ?>
                        <th>จำนวนครั้ง</th>
                        <th>จำนวนวัน</th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $counter = 1;
                $currentStaffStatus = null;
                foreach ($employees as $empData):
                    $staffstatus = $empData['staffstatus'];
                    $empID = $empData['id']; // ใช้ id จาก $empData
                    $staffStatusName = isset($staffstatusNames[$staffstatus]) ? $staffstatusNames[$staffstatus] : 'ไม่ระบุ';

                    // ถ้าเปลี่ยนกลุ่มสถานะ ให้เพิ่มแถวระบุกลุ่ม
                    if ($currentStaffStatus !== $staffStatusName) {
                        $currentStaffStatus = $staffStatusName;
                        $colspan = 2 + (count($usedLeavetypenames) * 2);
                        ?>
                        <tr class="group-header">
                            <td colspan="<?= $colspan + 1 ?>"><?= $currentStaffStatus ?></td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <td><?= $counter++ ?></td>
                        <td><?= $empData['prefixname'] . ' ' . $empData['fullname'] ?></td>
                        <?php
                        foreach ($usedLeavetypenames as $leavetypename) {
                            $leaveCount2 = 0;
                            $totalDays2 = 0;

                            if (isset($leaveRecordsPeriod2[$empID][$leavetypename])) {
                                $leaveCount2 = $leaveRecordsPeriod2[$empID][$leavetypename]['count'];
                                $totalDays2 = $leaveRecordsPeriod2[$empID][$leavetypename]['days'];
                            }
                            ?>
                            <td><?= ($leaveCount2 > 0) ? $leaveCount2 : '-' ?></td>
                            <td><?= ($totalDays2 > 0) ? $totalDays2 : '-' ?></td>
                        <?php } ?>
                        <td></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>

<?php $conn->close(); ?>
